<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Orcamento;
use GuzzleHttp\Client;

class LlmService
{
    // Returns a model key chosen for the provided cliente.
    // Currently only the default provider is configured.
    public function chooseModelForCliente(?Cliente $cliente = null): string
    {
        return 'default';
    }

    // Gera um texto de venda/pitch baseado nos itens do orçamento
    public function generateSalesCopy(Orcamento $orcamento): string
    {
        $items = $orcamento->itens ?? [];
        $summaryItems = [];

        foreach ($items as $it) {
            $nome = $it['item'] ?? ($it['descricao'] ?? 'Serviço');
            $qtd = $it['quantidade'] ?? 1;
            $val = isset($it['valor_unitario']) ? number_format($it['valor_unitario'], 2) : null;
            $summaryItems[] = trim("{$nome} (x{$qtd}" . ($val ? " - R$ {$val}" : '') . ")");
        }

        $itemList = implode('; ', array_slice($summaryItems, 0, 6));

        // If OpenAI key is configured, try to call the API, otherwise return a templated copy
        $apiKey = env('OPENAI_API_KEY');
        $model = env('OPENAI_MODEL', 'gpt-4o-mini');

        if ($apiKey) {
            try {
                $client = new Client([
                    'base_uri' => 'https://api.openai.com/',
                    'timeout' => 10,
                ]);

                $prompt = "Escreva um texto curto e persuasivo de até 120 palavras para o cliente, com base no orçamento abaixo. Destaque benefícios, prazo estimado e um call-to-action amigável. Items: " . $itemList;

                $response = $client->post('v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => 'Você é um assistente que escreve textos comerciais curtos e persuasivos em Português do Brasil.'],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'max_tokens' => 200,
                        'temperature' => 0.8,
                    ],
                ]);

                $body = json_decode((string) $response->getBody(), true);
                if (! empty($body['choices'][0]['message']['content'])) {
                    return trim($body['choices'][0]['message']['content']);
                }
            } catch (\Throwable $e) {
                // Silencioso: fallback abaixo
            }
        }

        // Fallback template
        $empresa = config('app.name', 'Stofgard');
        $total = number_format($orcamento->valor_total ?? 0, 2);
        $copy = "Olá, obrigado por considerar {$empresa}. Este orçamento inclui: {$itemList}. Valor estimado: R$ {$total}. Entre em contato para agendarmos a visita e formalizar o serviço. Estamos à disposição para esclarecer dúvidas e ajustar o escopo conforme necessário.";

        return $copy;
    }

    // Helper para checar se um modelo está disponível para um cliente
    public function isModelAvailableForCliente(string $modelKey, ?Cliente $cliente = null): bool
    {
        if (! config("llm.models.{$modelKey}", false)) {
            return false;
        }

        $globallyEnabled = config("llm.models.{$modelKey}.enabled", false);

        if (! $globallyEnabled) {
            return false;
        }

        if ($cliente) {
            return $cliente->hasFeature($modelKey);
        }

        return $globallyEnabled;
    }
}
