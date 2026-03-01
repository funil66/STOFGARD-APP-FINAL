<?php

namespace App\Http\Controllers;

use App\Models\AgendamentoPublico;
use App\Models\Configuracao;
use App\Models\Tenant;
use App\Services\GatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AgendamentoPublicoController — Página pública de agendamento tipo Calendly.
 * URL: stofgard.com.br/agendar/{slug}
 *
 * Flow:
 * 1. Cliente acessa a URL pública
 * 2. Escolhe data/horário disponível
 * 3. Informa dados (nome, telefone)
 * 4. Paga sinal via PIX (slot reservado por 30 min)
 * 5. Webhook PIX confirma → slot garantido → WhatsApp de confirmação
 */
class AgendamentoPublicoController extends Controller
{
    /**
     * Exibe a página pública de agendamento.
     */
    public function show(string $slug)
    {
        $tenant = Tenant::where('slug', $slug)->where('is_active', true)->first();

        if (!$tenant) {
            abort(404, 'Agenda não encontrada.');
        }

        // Inicializa contexto do tenant para buscar configurações
        tenancy()->initialize($tenant);

        $config = Configuracao::first();

        tenancy()->end();

        return view('agendamento.publico', [
            'tenant' => $tenant,
            'config' => $config,
            'slug' => $slug,
        ]);
    }

    /**
     * Retorna os horários disponíveis para uma data (AJAX/Livewire).
     */
    public function horariosDisponiveis(Request $request, string $slug)
    {
        $tenant = Tenant::where('slug', $slug)->where('is_active', true)->firstOrFail();
        tenancy()->initialize($tenant);

        $data = $request->date('data', 'Y-m-d');

        // Horários configurados (padrão: 08:00 às 18:00 a cada 60 min)
        $config = Configuracao::first();

        $horarioInicio = 8;
        $horarioFim = 18;
        $duracao = 60; // minutos

        $slots = [];

        for ($hora = $horarioInicio; $hora < $horarioFim; $hora++) {
            $inicio = $data->copy()->setTime($hora, 0);
            $fim = $inicio->copy()->addMinutes($duracao);

            // Verifica disponibilidade sem race condition
            $disponivel = AgendamentoPublico::slotDisponivel($inicio, $fim);

            $slots[] = [
                'hora' => $inicio->format('H:i'),
                'inicio' => $inicio->toISOString(),
                'fim' => $fim->toISOString(),
                'disponivel' => $disponivel,
            ];
        }

        tenancy()->end();

        return response()->json($slots);
    }

    /**
     * Cria a reserva do slot e gera o PIX de sinal.
     * Locking pessimista para prevenir double-booking.
     */
    public function reservar(Request $request, string $slug)
    {
        $request->validate([
            'data_hora_inicio' => 'required|date|after:now',
            'cliente_nome' => 'required|string|max:255',
            'cliente_telefone' => 'required|string|max:20',
            'cliente_email' => 'nullable|email|max:255',
            'tipo_servico' => 'nullable|string|max:100',
        ]);

        $tenant = Tenant::where('slug', $slug)->where('is_active', true)->firstOrFail();
        tenancy()->initialize($tenant);

        try {
            $agendamento = DB::transaction(function () use ($request, $tenant) {
                $inicio = \Carbon\Carbon::parse($request->data_hora_inicio);
                $fim = $inicio->copy()->addHour();

                // Verifica disponibilidade COM LOCK (evita race condition)
                $conflito = AgendamentoPublico::lockForUpdate()
                    ->where('status', '!=', 'cancelado')
                    ->where(function ($q) use ($inicio, $fim) {
                        $q->where('data_hora_inicio', '<', $fim)
                            ->where('data_hora_fim', '>', $inicio);
                    })
                    ->where(function ($q) {
                        $q->where('status', 'confirmado')
                            ->orWhere(function ($q2) {
                                $q2->where('status', 'reservado')
                                    ->where('reservado_ate', '>', now());
                            });
                    })
                    ->exists();

                if ($conflito) {
                    throw new \RuntimeException('Este horário acabou de ser reservado. Por favor, escolha outro.');
                }

                // Cria a reserva (slot travado por 30 min)
                $agendamento = AgendamentoPublico::create([
                    'data_hora_inicio' => $inicio,
                    'data_hora_fim' => $fim,
                    'status' => 'reservado',
                    'cliente_nome' => $request->cliente_nome,
                    'cliente_telefone' => $request->cliente_telefone,
                    'cliente_email' => $request->cliente_email,
                    'tipo_servico' => $request->tipo_servico,
                    'reservado_ate' => now()->addMinutes(30),
                    'token_confirmacao' => (string) Str::uuid(),
                ]);

                // Gera PIX de sinal (se gateway configurado)
                $this->gerarPixSinal($agendamento);

                return $agendamento;
            });

            tenancy()->end();

            return response()->json([
                'success' => true,
                'agendamento_id' => $agendamento->id,
                'pix_copia_cola' => $agendamento->pix_copia_cola,
                'pix_expira_em' => $agendamento->pix_expira_em?->toISOString(),
                'valor_sinal' => $agendamento->valor_sinal,
                'mensagem' => 'Slot reservado! Pague o sinal PIX para confirmar.',
            ]);

        } catch (\RuntimeException $e) {
            tenancy()->end();
            return response()->json(['success' => false, 'mensagem' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            tenancy()->end();
            Log::error('[AgendamentoPublico] Erro ao reservar', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'mensagem' => 'Erro ao processar. Tente novamente.'], 500);
        }
    }

    /**
     * Gera a cobrança PIX do sinal no gateway do tenant.
     */
    private function gerarPixSinal(AgendamentoPublico $agendamento): void
    {
        if (!GatewayService::estaConfigurado()) {
            return;
        }

        $config = Configuracao::first();
        $valor = (float) ($agendamento->valor_sinal ?? 50.00);

        try {
            // Cria um objeto mock compatível com GatewayService::gerarPix()
            $payload = new \stdClass();
            $payload->id = $agendamento->id;
            $payload->numero = "AGEND-{$agendamento->id}";
            $payload->valor_total = $valor;
            $payload->cliente = (object) [
                'id' => null,
                'nome' => $agendamento->cliente_nome,
                'email' => $agendamento->cliente_email,
                'celular' => $agendamento->cliente_telefone,
                'cpf' => null,
            ];

            // Injeta referência externa para o webhook identificar
            $pixData = GatewayService::gerarPix($payload);

            $agendamento->update([
                'gateway_cobranca_id' => $pixData['cobranca_id'] ?? null,
                'pix_copia_cola' => $pixData['pix_copia_cola'] ?? null,
                'pix_expira_em' => isset($pixData['expires_at']) ? \Carbon\Carbon::parse($pixData['expires_at']) : now()->addMinutes(30),
                'valor_sinal' => $valor,
            ]);

        } catch (\Exception $e) {
            Log::error('[AgendamentoPublico] Erro ao gerar PIX sinal', ['error' => $e->getMessage()]);
        }
    }
}
