<?php

namespace App\Services;

use App\Models\Financeiro;
use Efi\EfiPay;
use Efi\Exception\EfiException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PixService
{
    private ?EfiPay $client = null;

    private bool $sandbox;

    public function __construct()
    {
        // Verificar se está ativo
        if (! ConfiguracaoService::financeiro('pix_ativo', false)) {
            return;
        }

        $this->sandbox = ConfiguracaoService::financeiro('gateway_sandbox', true);

        try {
            $options = [
                'client_id' => ConfiguracaoService::financeiro('gateway_client_id'),
                'client_secret' => ConfiguracaoService::financeiro('gateway_client_secret'),
                'sandbox' => $this->sandbox,
                'debug' => config('app.debug', false),
                'timeout' => 30,
            ];

            // Certificado PIX (necessário apenas se não for sandbox)
            if (! $this->sandbox) {
                $certPath = storage_path('app/certificates/producao.p12');
                if (file_exists($certPath)) {
                    $options['certificate'] = $certPath;
                }
            }

            $this->client = new EfiPay($options);
        } catch (\Exception $e) {
            Log::error('Erro ao inicializar EFI Pay', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cria uma cobrança PIX imediata
     */
    public function criarCobranca(Financeiro $financeiro): array
    {
        if (! $this->client) {
            throw new \Exception('PIX não está configurado. Configure em Configurações → Financeiro');
        }

        try {
            // Gerar TXID único
            $txid = Str::upper(Str::random(32));

            // Preparar dados do cliente
            $clienteData = [];
            if ($financeiro->cliente) {
                $cpfCnpj = preg_replace('/\D/', '', $financeiro->cliente->cpf_cnpj);

                $clienteData = [
                    'nome' => $financeiro->cliente->nome,
                ];

                if (strlen($cpfCnpj) === 11) {
                    $clienteData['cpf'] = $cpfCnpj;
                } elseif (strlen($cpfCnpj) === 14) {
                    $clienteData['cnpj'] = $cpfCnpj;
                }
            }

            // Calcular validade (em segundos)
            $expiracao = ConfiguracaoService::financeiro('pix_expiracao_horas', 24) * 3600;

            // Corpo da requisição
            $body = [
                'calendario' => [
                    'expiracao' => $expiracao, // 24 horas padrão
                ],
                'valor' => [
                    'original' => number_format($financeiro->valor_total, 2, '.', ''),
                ],
                'chave' => ConfiguracaoService::financeiro('pix_chave'),
                'solicitacaoPagador' => $financeiro->descricao,
            ];

            // Adicionar devedor se houver dados
            if (! empty($clienteData)) {
                $body['devedor'] = $clienteData;
            }

            // Criar cobrança imediata
            $response = $this->client->pixCreateImmediateCharge(
                ['txid' => $txid],
                $body
            );

            // Gerar QR Code
            $qrcode = $this->client->pixGenerateQRCode([
                'id' => $response['loc']['id'],
            ]);

            // Atualizar financeiro
            $financeiro->update([
                'pix_txid' => $txid,
                'pix_qrcode_base64' => $qrcode['imagemQrcode'],
                'pix_copia_cola' => $qrcode['qrcode'],
                'pix_location' => $response['loc']['location'],
                'pix_expiracao' => now()->addSeconds($expiracao),
                'pix_status' => 'pendente',
                'pix_response' => json_encode($response),
                'link_pagamento_hash' => Str::random(40),
            ]);

            Log::info('PIX criado com sucesso', [
                'financeiro_id' => $financeiro->id,
                'txid' => $txid,
                'valor' => $financeiro->valor_total,
            ]);

            return [
                'success' => true,
                'txid' => $txid,
                'qrcode' => $qrcode['imagemQrcode'],
                'copia_cola' => $qrcode['qrcode'],
                'expiracao' => $financeiro->pix_expiracao,
            ];

        } catch (EfiException $e) {
            Log::error('Erro EFI ao criar PIX', [
                'error' => $e->getMessage(),
                'code' => $e->code,
                'financeiro_id' => $financeiro->id,
            ]);

            throw new \Exception('Erro ao gerar PIX: '.$e->error_description ?? $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro geral ao criar PIX', [
                'error' => $e->getMessage(),
                'financeiro_id' => $financeiro->id,
            ]);

            throw $e;
        }
    }

    /**
     * Consulta uma cobrança PIX
     */
    public function consultarCobranca(string $txid): array
    {
        if (! $this->client) {
            throw new \Exception('PIX não está configurado');
        }

        try {
            $response = $this->client->pixDetailCharge(['txid' => $txid]);

            return $response;
        } catch (EfiException $e) {
            Log::error('Erro ao consultar PIX', [
                'txid' => $txid,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Processa webhook de pagamento recebido
     */
    public function processarWebhook(array $payload): bool
    {
        try {
            // O webhook da EFI envia informações sobre o PIX recebido
            if (! isset($payload['pix']) || empty($payload['pix'])) {
                Log::warning('Webhook PIX sem dados de PIX', $payload);

                return false;
            }

            foreach ($payload['pix'] as $pixData) {
                $txid = $pixData['txid'] ?? null;

                if (! $txid) {
                    continue;
                }

                // Buscar financeiro pelo txid
                $financeiro = Financeiro::where('pix_txid', $txid)->first();

                if (! $financeiro) {
                    Log::warning('Financeiro não encontrado para TXID', ['txid' => $txid]);

                    continue;
                }

                // Verificar se já está pago
                if ($financeiro->status === 'pago') {
                    Log::info('Financeiro já marcado como pago', ['txid' => $txid]);

                    continue;
                }

                // Atualizar financeiro
                $financeiro->update([
                    'status' => 'pago',
                    'pix_status' => 'pago',
                    'pix_data_pagamento' => now(),
                    'pix_valor_pago' => $pixData['valor'] ?? $financeiro->valor_total,
                    'data_pagamento' => now(),
                    'valor_pago' => $pixData['valor'] ?? $financeiro->valor_total,
                    'forma_pagamento' => 'pix',
                ]);

                Log::info('Pagamento PIX confirmado via webhook', [
                    'financeiro_id' => $financeiro->id,
                    'txid' => $txid,
                    'valor' => $pixData['valor'] ?? $financeiro->valor_total,
                ]);

                // Aqui você pode adicionar notificações
                // Exemplo: enviar WhatsApp, email, etc.
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook PIX', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return false;
        }
    }

    /**
     * Cancela uma cobrança PIX
     */
    public function cancelarCobranca(Financeiro $financeiro): bool
    {
        if (! $this->client || ! $financeiro->pix_txid) {
            return false;
        }

        try {
            // A EFI não tem endpoint específico para cancelar, mas podemos marcar como cancelado
            $financeiro->update([
                'pix_status' => 'cancelado',
            ]);

            Log::info('PIX cancelado', [
                'financeiro_id' => $financeiro->id,
                'txid' => $financeiro->pix_txid,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erro ao cancelar PIX', [
                'error' => $e->getMessage(),
                'financeiro_id' => $financeiro->id,
            ]);

            return false;
        }
    }

    /**
     * Verifica cobranças expiradas e atualiza status
     */
    public function verificarExpirados(): int
    {
        $expirados = Financeiro::where('pix_status', 'pendente')
            ->whereNotNull('pix_expiracao')
            ->where('pix_expiracao', '<', now())
            ->get();

        foreach ($expirados as $financeiro) {
            $financeiro->update(['pix_status' => 'expirado']);

            Log::info('PIX expirado', [
                'financeiro_id' => $financeiro->id,
                'txid' => $financeiro->pix_txid,
            ]);
        }

        return $expirados->count();
    }

    /**
     * Gera link público de pagamento
     */
    public function gerarLinkPagamento(Financeiro $financeiro): string
    {
        if (! $financeiro->link_pagamento_hash) {
            $financeiro->update([
                'link_pagamento_hash' => Str::random(40),
            ]);
        }

        return route('pagamento.pix', $financeiro->link_pagamento_hash);
    }

    /**
     * Gera a string Payload (CRC16) - Mantenha sua lógica atual aqui ou use esta padronizada
     */
    public function gerarPayloadPix($chave, $valor, $beneficiario, $cidade, $txtId = '')
    {
        $payload = '';

        // 00 - Payload Format Indicator
        $payload .= '000201';

        // 26 - Merchant Account Information
        $payload .= '26';
        $mai = '0014br.gov.bcb.pix01';
        $mai .= str_pad(strlen($chave), 2, '0', STR_PAD_LEFT).$chave;
        $payload .= str_pad(strlen($mai), 2, '0', STR_PAD_LEFT).$mai;

        // 52 - Merchant Category Code
        $payload .= '52040000';

        // 53 - Transaction Currency
        $payload .= '5303986';

        // 54 - Transaction Amount
        if ($valor > 0) {
            $valorStr = number_format($valor, 2, '.', '');
            $payload .= '54'.str_pad(strlen($valorStr), 2, '0', STR_PAD_LEFT).$valorStr;
        }

        // 58 - Country Code
        $payload .= '5802BR';

        // 59 - Merchant Name
        $payload .= '59'.str_pad(strlen($beneficiario), 2, '0', STR_PAD_LEFT).$beneficiario;

        // 60 - Merchant City
        $payload .= '60'.str_pad(strlen($cidade), 2, '0', STR_PAD_LEFT).$cidade;

        // 62 - Additional Data Field Template
        $payload .= '62';
        $adt = '05'.str_pad(strlen($txtId), 2, '0', STR_PAD_LEFT).$txtId;
        $payload .= str_pad(strlen($adt), 2, '0', STR_PAD_LEFT).$adt;

        // 63 - CRC16
        $payload .= '6304';
        $crc = $this->crc16($payload);
        $payload .= $crc;

        return $payload;
    }

    /**
     * NOVO MÉTODO: Gera a imagem Base64 pronta para o PDF
     */
    public function gerarQrCodeBase64($payloadPix)
    {
        // Tenta gerar PNG primeiro (melhor compatibilidade visual), se falhar tenta SVG como fallback
        try {
            $imagem = QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->generate($payloadPix);
            return 'data:image/png;base64,'.base64_encode($imagem);
        } catch (\Throwable $e) {
            // Fallback para SVG
            try {
                $svg = QrCode::format('svg')->size(300)->margin(1)->generate($payloadPix);
                return 'data:image/svg+xml;base64,'.base64_encode($svg);
            } catch (\Throwable $e2) {
                // Em último caso, retorna null para ser tratado pelo chamador
                \Illuminate\Support\Facades\Log::warning('QrCode generation failed: '.$e->getMessage().' / '.$e2->getMessage());
                return null;
            }
        }
    }

    private function crc16($data)
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc <<= 1;
                }
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
