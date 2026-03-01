<?php

namespace App\Jobs;

use App\Models\Configuracao;
use App\Models\OrdemServico;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * EnviarSolicitacaoAvaliacaoJob — Fase 4: Máquina de Google Meu Negócio.
 *
 * Disparado 24h após uma OS ser concluída E paga.
 * Envia WhatsApp pedindo avaliação no Google Maps.
 *
 * Implements ShouldBeUnique para evitar duplicatas (uma avaliação por OS).
 */
class EnviarSolicitacaoAvaliacaoJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $ordemServicoId
    ) {
    }

    /**
     * Chave única para prevenir envio duplicado para a mesma OS.
     */
    public function uniqueId(): string
    {
        return "avaliacao_os_{$this->ordemServicoId}";
    }

    /**
     * TTL da unicidade: 48 horas.
     */
    public function uniqueFor(): int
    {
        return 60 * 48;
    }

    public function handle(): void
    {
        $os = OrdemServico::with(['cliente', 'financeiro'])->find($this->ordemServicoId);

        if (!$os) {
            Log::info('[AvaliacaoJob] OS não encontrada, abortando.', ['os_id' => $this->ordemServicoId]);
            return;
        }

        // Double-check: OS deve estar concluída E financeiro pago
        if ($os->status !== 'concluida') {
            Log::info('[AvaliacaoJob] OS não está concluída, abortando.', ['os_id' => $this->ordemServicoId, 'status' => $os->status]);
            return;
        }

        $financeiro = $os->financeiro;
        if (!$financeiro || $financeiro->status !== 'pago') {
            Log::info('[AvaliacaoJob] Financeiro não está pago, abortando.', ['os_id' => $this->ordemServicoId]);
            return;
        }

        $cliente = $os->cliente;
        if (!$cliente || !$cliente->celular) {
            Log::info('[AvaliacaoJob] Cliente sem celular, abortando.', ['os_id' => $this->ordemServicoId]);
            return;
        }

        // Verifica flag de proteção (double-guard além do ShouldBeUnique)
        if ($os->avaliacao_enviada) {
            Log::info('[AvaliacaoJob] Avaliação já foi enviada para esta OS, abortando.', ['os_id' => $this->ordemServicoId]);
            return;
        }

        // Busca configurações do tenant
        $config = Configuracao::first();

        // Verifica se o módulo está habilitado
        if (!($config?->habilitar_avaliacao_automatica ?? false)) {
            Log::info('[AvaliacaoJob] Avaliação automática desabilitada nas configurações.', ['os_id' => $this->ordemServicoId]);
            return;
        }

        $gmbLink = $config?->gmb_link;
        if (!$gmbLink) {
            Log::warning('[AvaliacaoJob] Link GMB não configurado. Configure em Configurações → Marketing.', ['os_id' => $this->ordemServicoId]);
            return;
        }

        // Monta a mensagem (usa template personalizado ou padrão)
        $nomeEmpresa = $config?->empresa_nome ?? 'nossa empresa';
        $templatePadrao = "Olá, {nome_cliente}! 😊\n\n"
            . "O serviço de *{nome_empresa}* atendeu suas expectativas?\n\n"
            . "Se ficou satisfeito, nos ajude muito com uma avaliação de ⭐⭐⭐⭐⭐ no Google:\n"
            . "{link_gmb}\n\n"
            . "Leva menos de 1 minuto e faz toda a diferença para continuarmos servindo bem! 🙏\n\n"
            . "_Equipe {nome_empresa}_";

        $template = $config?->mensagem_avaliacao ?: $templatePadrao;

        $mensagem = str_replace(
            ['{nome_cliente}', '{nome_empresa}', '{link_gmb}', '{numero_os}'],
            [$cliente->nome, $nomeEmpresa, $gmbLink, $os->numero_os],
            $template
        );

        // Despacha o WhatsApp
        SendWhatsAppJob::dispatch($cliente->celular, $mensagem);

        // === FASE 4: Seta flag para evitar reenvio ===
        $os->update([
            'avaliacao_enviada' => true,
            'avaliacao_enviada_em' => now(),
        ]);

        Log::info('[AvaliacaoJob] Solicitação de avaliação enviada via WhatsApp', [
            'os_id' => $this->ordemServicoId,
            'os_num' => $os->numero_os,
            'celular' => substr($cliente->celular, 0, 4) . '****',
        ]);
    }
}
