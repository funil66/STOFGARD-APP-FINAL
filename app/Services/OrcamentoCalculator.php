<?php

namespace App\Services;

use App\Models\Orcamento;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * OrcamentoCalculator - Centraliza toda a matemática financeira de orçamentos
 *
 * Responsável por:
 * - Cálculo de subtotais e totais
 * - Aplicação de descontos (prestador, PIX, promoções)
 * - Cálculo de comissões (vendedor, loja)
 * - Geração de dados para PIX (integrado com PixMasterService)
 */
class OrcamentoCalculator
{
    protected ?array $configCache = null;

    /**
     * Calcula todos os valores de um orçamento
     */
    public function calcular(Orcamento $orcamento): CalculoOrcamentoDTO
    {
        $config = $this->getConfiguracoes();

        // 1. Cálculo base dos itens
        $subtotal = $this->calcularSubtotal($orcamento);

        // 2. Desconto do Prestador (valor fixo informado)
        $descontoPrestador = $orcamento->desconto_prestador ?? 0;

        // 3. Valor após desconto prestador
        $valorAposDescontoPrestador = $subtotal - $descontoPrestador;

        // 4. Desconto PIX (percentual sobre valor após desconto prestador)
        $descontoPix = 0;
        $percentualPix = $config['financeiro_desconto_avista'] ?? 10;

        if ($orcamento->aplicar_desconto_pix && $percentualPix > 0) {
            // Se valor foi editado manualmente, NÃO aplica desconto PIX
            if (! $orcamento->valor_final_editado) {
                $descontoPix = ($valorAposDescontoPrestador * $percentualPix) / 100;
            }
        }

        // 5. Valor final
        $valorFinal = $orcamento->valor_final_editado
            ?? ($valorAposDescontoPrestador - $descontoPix);

        // 6. Comissões
        $comissoes = $this->calcularComissoes($orcamento, $valorFinal);

        return new CalculoOrcamentoDTO(
            subtotal: $subtotal,
            descontoPrestador: $descontoPrestador,
            valorAposDescontoPrestador: $valorAposDescontoPrestador,
            percentualDescontoPix: $percentualPix,
            descontoPix: $descontoPix,
            valorFinal: $valorFinal,
            comissaoVendedor: $comissoes['vendedor'],
            comissaoLoja: $comissoes['loja'],
            percentualComissaoVendedor: $comissoes['percentual_vendedor'],
            percentualComissaoLoja: $comissoes['percentual_loja'],
        );
    }

    /**
     * Calcula o subtotal dos itens do orçamento
     */
    public function calcularSubtotal(Orcamento $orcamento): float
    {
        // Prioridade: valor_total existente ou soma dos itens
        if ($orcamento->valor_total > 0 && ! $orcamento->relationLoaded('itens')) {
            return (float) $orcamento->valor_total;
        }

        // Soma dos itens
        $itens = $orcamento->itens ?? collect();
        $subtotal = 0;

        foreach ($itens as $item) {
            $quantidade = $item->quantidade ?? 1;
            $valorUnitario = $item->valor_unitario ?? 0;
            $subtotal += ($quantidade * $valorUnitario);
        }

        return $subtotal > 0 ? $subtotal : (float) ($orcamento->valor_total ?? 0);
    }

    /**
     * Calcula as comissões de vendedor e loja
     */
    public function calcularComissoes(Orcamento $orcamento, float $valorBase): array
    {
        $comissaoVendedor = 0;
        $comissaoLoja = 0;
        $percentualVendedor = 0;
        $percentualLoja = 0;

        // Comissão do Vendedor
        if ($orcamento->vendedor_id) {
            $vendedor = $orcamento->vendedor;
            if ($vendedor) {
                $percentualVendedor = $vendedor->comissao_percentual ?? 0;
                $comissaoVendedor = ($valorBase * $percentualVendedor) / 100;
            }
        }

        // Comissão da Loja
        if ($orcamento->loja_id) {
            $loja = $orcamento->loja;
            if ($loja) {
                $percentualLoja = $loja->comissao_percentual ?? 0;
                $comissaoLoja = ($valorBase * $percentualLoja) / 100;
            }
        }

        return [
            'vendedor' => round($comissaoVendedor, 2),
            'loja' => round($comissaoLoja, 2),
            'percentual_vendedor' => $percentualVendedor,
            'percentual_loja' => $percentualLoja,
        ];
    }

    /**
     * Prepara dados do PIX para o PDF
     */
    public function prepararDadosPix(Orcamento $orcamento, float $valorFinal): ?array
    {
        if (! $orcamento->pdf_incluir_pix || ! $orcamento->pix_chave_selecionada) {
            return null;
        }

        try {
            $config = $this->getConfiguracoes();
            $chavesPix = $config['financeiro_pix_keys'] ?? [];

            // Encontrar titular da chave
            $titular = $config['nome_sistema'] ?? 'Stofgard';
            $cidade = 'Ribeirao Preto';

            if (is_array($chavesPix)) {
                foreach ($chavesPix as $keyItem) {
                    if (($keyItem['chave'] ?? '') === $orcamento->pix_chave_selecionada) {
                        $titular = $keyItem['titular'] ?? $titular;
                        break;
                    }
                }
            }

            // Gerar QR Code
            $pixService = new Pix\PixMasterService;
            $pixData = $pixService->gerarQrCode(
                $orcamento->pix_chave_selecionada,
                $titular,
                $cidade,
                $orcamento->numero ?? 'ORC',
                $valorFinal
            );

            return [
                'qr_code_base64' => $pixData['qr_code_img'] ?? null,
                'copia_cola' => $pixData['payload_pix'] ?? null,
                'chave' => $orcamento->pix_chave_selecionada,
                'titular' => $titular,
                'valor' => $valorFinal,
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao gerar dados PIX: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Aplica os cálculos no objeto orçamento (para exibição/PDF)
     */
    public function aplicarNoOrcamento(Orcamento $orcamento): Orcamento
    {
        $calculo = $this->calcular($orcamento);

        // Atributos temporários para renderização
        $orcamento->calculated_subtotal = $calculo->subtotal;
        $orcamento->calculated_desconto_pix = $calculo->descontoPix;
        $orcamento->calculated_valor_final = $calculo->valorFinal;
        $orcamento->calculated_comissao_vendedor = $calculo->comissaoVendedor;
        $orcamento->calculated_comissao_loja = $calculo->comissaoLoja;

        // Dados PIX
        $pixData = $this->prepararDadosPix($orcamento, $calculo->valorFinal);
        if ($pixData) {
            $orcamento->pix_qrcode_base64 = $pixData['qr_code_base64'];
            $orcamento->pix_copia_cola = $pixData['copia_cola'];
        }

        return $orcamento;
    }

    /**
     * Carrega configurações do sistema (com cache)
     */
    protected function getConfiguracoes(): array
    {
        if ($this->configCache !== null) {
            return $this->configCache;
        }

        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // Decodifica campos JSON
        $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
        foreach ($jsonFields as $field) {
            if (isset($settings[$field]) && is_string($settings[$field])) {
                $settings[$field] = json_decode($settings[$field], true);
            }
        }

        $this->configCache = $settings;

        return $settings;
    }

    /**
     * Formata valor para exibição em Real brasileiro
     */
    public static function formatarMoeda(float $valor): string
    {
        return 'R$ '.number_format($valor, 2, ',', '.');
    }
}

/**
 * DTO para resultado do cálculo de orçamento
 */
class CalculoOrcamentoDTO
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $descontoPrestador,
        public readonly float $valorAposDescontoPrestador,
        public readonly float $percentualDescontoPix,
        public readonly float $descontoPix,
        public readonly float $valorFinal,
        public readonly float $comissaoVendedor,
        public readonly float $comissaoLoja,
        public readonly float $percentualComissaoVendedor,
        public readonly float $percentualComissaoLoja,
    ) {}

    /**
     * Total de descontos aplicados
     */
    public function totalDescontos(): float
    {
        return $this->descontoPrestador + $this->descontoPix;
    }

    /**
     * Total de comissões
     */
    public function totalComissoes(): float
    {
        return $this->comissaoVendedor + $this->comissaoLoja;
    }

    /**
     * Lucro líquido estimado (valor final - comissões)
     */
    public function lucroLiquido(): float
    {
        return $this->valorFinal - $this->totalComissoes();
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'desconto_prestador' => $this->descontoPrestador,
            'valor_apos_desconto_prestador' => $this->valorAposDescontoPrestador,
            'percentual_desconto_pix' => $this->percentualDescontoPix,
            'desconto_pix' => $this->descontoPix,
            'valor_final' => $this->valorFinal,
            'comissao_vendedor' => $this->comissaoVendedor,
            'comissao_loja' => $this->comissaoLoja,
            'percentual_comissao_vendedor' => $this->percentualComissaoVendedor,
            'percentual_comissao_loja' => $this->percentualComissaoLoja,
            'total_descontos' => $this->totalDescontos(),
            'total_comissoes' => $this->totalComissoes(),
            'lucro_liquido' => $this->lucroLiquido(),
        ];
    }
}
