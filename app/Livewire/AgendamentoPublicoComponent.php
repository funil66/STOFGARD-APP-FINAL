<?php

namespace App\Livewire;

use App\Models\AgendamentoPublico;
use App\Models\Configuracao;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * AgendamentoPublicoComponent — Componente Livewire para agendamento público.
 *
 * Estados (steps):
 * 1 → Escolher data
 * 2 → Escolher horário
 * 3 → Preencher dados
 * 4 → Pagar sinal PIX
 * 5 → Confirmado ✅
 */
class AgendamentoPublicoComponent extends Component
{
    public string $slug;
    public ?Tenant $tenant = null;
    public ?Configuracao $config = null;

    // Step atual (1-5)
    public int $step = 1;

    // Step 1: Seleção de data
    public ?string $dataSelecionada = null;
    public array $slotsDisponiveis = [];

    // Step 2: Seleção de horário
    public ?string $slotInicio = null;
    public ?string $slotFim = null;

    // Step 3: Dados do cliente
    public string $clienteNome = '';
    public string $clienteTelefone = '';
    public string $clienteEmail = '';
    public string $clienteObservacao = '';
    public string $tipoServico = '';

    // Step 4: PIX
    public ?string $pixCopiaCola = null;
    public ?string $pixExpiraEm = null;
    public ?float $valorSinal = null;
    public ?int $agendamentoId = null;

    // Mensagem de erro/sucesso
    public ?string $erro = null;
    public bool $processando = false;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        $this->tenant = Tenant::where('slug', $slug)->where('is_active', true)->first();

        if (!$this->tenant) {
            abort(404);
        }

        tenancy()->initialize($this->tenant);
        $this->config = Configuracao::first();
    }

    // =========================================================================
    // Step 1 → 2: Selecionar data e carregar horários
    // =========================================================================

    public function selecionarData(string $data): void
    {
        $this->dataSelecionada = $data;
        $this->slotsDisponiveis = $this->carregarSlots($data);
        $this->step = 2;
        $this->erro = null;
    }

    private function carregarSlots(string $data): array
    {
        $slots = [];
        $duracao = 60; // minutos

        for ($hora = 8; $hora < 18; $hora++) {
            $inicio = Carbon::parse("{$data} {$hora}:00:00");
            $fim = $inicio->copy()->addMinutes($duracao);

            if ($inicio->isPast()) {
                continue;
            }

            $disponivel = AgendamentoPublico::slotDisponivel($inicio, $fim);

            $slots[] = [
                'hora' => $inicio->format('H:i'),
                'inicio' => $inicio->toISOString(),
                'fim' => $fim->toISOString(),
                'disponivel' => $disponivel,
            ];
        }

        return $slots;
    }

    // =========================================================================
    // Step 2 → 3: Selecionar horário
    // =========================================================================

    public function selecionarSlot(string $inicio, string $fim): void
    {
        $this->slotInicio = $inicio;
        $this->slotFim = $fim;
        $this->step = 3;
        $this->erro = null;
    }

    public function voltarParaData(): void
    {
        $this->step = 1;
    }

    public function voltarParaSlots(): void
    {
        $this->step = 2;
    }

    // =========================================================================
    // Step 3 → 4: Confirmar dados e gerar PIX
    // =========================================================================

    public function confirmarDados(): void
    {
        $this->validate([
            'clienteNome' => 'required|min:3',
            'clienteTelefone' => 'required|min:8',
            'clienteEmail' => 'nullable|email',
        ], [
            'clienteNome.required' => 'Por favor, informe seu nome.',
            'clienteNome.min' => 'Nome deve ter pelo menos 3 caracteres.',
            'clienteTelefone.required' => 'Por favor, informe seu telefone.',
            'clienteEmail.email' => 'E-mail inválido.',
        ]);

        $this->processando = true;
        $this->erro = null;

        try {
            $inicio = Carbon::parse($this->slotInicio);
            $fim = Carbon::parse($this->slotFim);

            $agendamento = DB::transaction(function () use ($inicio, $fim) {
                // Verifica disponibilidade com lock
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
                    throw new \RuntimeException('Este horário foi reservado agora mesmo. Escolha outro.');
                }

                return AgendamentoPublico::create([
                    'data_hora_inicio' => $inicio,
                    'data_hora_fim' => $fim,
                    'status' => 'reservado',
                    'cliente_nome' => $this->clienteNome,
                    'cliente_telefone' => $this->clienteTelefone,
                    'cliente_email' => $this->clienteEmail,
                    'cliente_observacao' => $this->clienteObservacao,
                    'tipo_servico' => $this->tipoServico,
                    'reservado_ate' => now()->addMinutes(30),
                    'token_confirmacao' => (string) Str::uuid(),
                ]);
            });

            $this->agendamentoId = $agendamento->id;

            // Tenta gerar PIX via gateway
            $this->tentarGerarPix($agendamento);

            $this->step = 4;

        } catch (\RuntimeException $e) {
            $this->erro = $e->getMessage();
            // Recarrega slots
            $this->slotsDisponiveis = $this->carregarSlots($this->dataSelecionada);
            $this->step = 2;
        } catch (\Exception $e) {
            Log::error('[AgendamentoLivewire] Erro', ['error' => $e->getMessage()]);
            $this->erro = 'Erro ao processar. Tente novamente em instantes.';
        } finally {
            $this->processando = false;
        }
    }

    private function tentarGerarPix(AgendamentoPublico $agendamento): void
    {
        try {
            $service = app(\App\Services\GatewayService::class);

            if (!\App\Services\GatewayService::estaConfigurado()) {
                // Sem gateway: mostra só a chave PIX manual
                $config = $this->config;
                if ($config?->pix_chave) {
                    $this->pixCopiaCola = $config->pix_chave;
                    $this->valorSinal = 50.00;
                }
                return;
            }

            // Cria objeto compatível com GatewayService
            $payload = new \stdClass();
            $payload->id = $agendamento->id;
            $payload->numero = "AGEND-{$agendamento->id}";
            $payload->valor_total = 50.00;
            $payload->cliente = (object) [
                'id' => null,
                'nome' => $agendamento->cliente_nome,
                'email' => $agendamento->cliente_email,
                'celular' => $agendamento->cliente_telefone,
                'cpf' => null,
            ];

            $pixData = \App\Services\GatewayService::gerarPix($payload);

            $agendamento->update([
                'gateway_cobranca_id' => $pixData['cobranca_id'] ?? null,
                'pix_copia_cola' => $pixData['pix_copia_cola'] ?? null,
                'pix_expira_em' => now()->addMinutes(30),
                'valor_sinal' => 50.00,
            ]);

            $this->pixCopiaCola = $pixData['pix_copia_cola'] ?? null;
            $this->pixExpiraEm = now()->addMinutes(30)->toISOString();
            $this->valorSinal = 50.00;

        } catch (\Exception $e) {
            Log::warning('[AgendamentoLivewire] PIX não gerado', ['error' => $e->getMessage()]);
            $this->pixCopiaCola = $this->config?->pix_chave;
        }
    }

    // =========================================================================
    // Polling: verificar se PIX foi pago
    // =========================================================================

    public function verificarPagamento(): void
    {
        if (!$this->agendamentoId) {
            return;
        }

        $agendamento = AgendamentoPublico::find($this->agendamentoId);

        if ($agendamento?->status === 'confirmado') {
            $this->step = 5;
        }
    }

    public function render()
    {
        return view('livewire.agendamento-publico-component', [
            'diasDisponiveis' => $this->getDiasDisponiveis(),
        ])->layout('layouts.agendamento', [
                    'tenant' => $this->tenant,
                    'config' => $this->config,
                ]);
    }

    private function getDiasDisponiveis(): array
    {
        $dias = [];
        $hoje = Carbon::today();

        for ($i = 1; $i <= 30; $i++) {
            $dia = $hoje->copy()->addDays($i);

            // Ignora domingos (ajustar conforme tenant futuramente)
            if ($dia->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }

            $dias[] = [
                'data' => $dia->format('Y-m-d'),
                'dia_semana' => $dia->locale('pt_BR')->dayName,
                'dia_mes' => $dia->format('d'),
                'mes' => $dia->locale('pt_BR')->monthName,
                'disponivel' => true,
            ];
        }

        return array_slice($dias, 0, 14); // Máximo 14 dias à frente
    }
}
