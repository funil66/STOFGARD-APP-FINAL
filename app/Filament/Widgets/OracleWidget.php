<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Traits\RestrictsAccessByTier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OracleWidget extends Widget
{
    use RestrictsAccessByTier;

    protected static string $view = 'filament.widgets.oracle-widget';

    // Faz o widget ocupar a largura toda do ecrã
    protected int|string|array $columnSpan = 'full';

    // Variáveis reativas do Livewire
    public string $question = '';
    public string $answer = '';

    // 🔒 A TRANCA DA TESOURA: Apenas o plano mais caro tem acesso à IA
    public static function getAllowedTiers(): array
    {
        return ['elite'];
    }

    public static function canView(): bool
    {
        return static::canAccess();
    }

    public function askOracle()
    {
        // Valida se o gajo não mandou o input vazio
        $this->validate([
            'question' => 'required|string|max:1000',
        ]);

        $this->answer = ''; // Limpa a resposta anterior da vista

        try {
            // Chamar a API da OpenAI (podes usar Gemini, Claude, etc)
            // Tens de adicionar IA_API_KEY no teu ficheiro .env na VPS
            $apiKey = env('IA_API_KEY');

            if (!$apiKey) {
                $this->answer = "⚠️ Erro Tático: A Chave da API não está configurada no servidor (.env).";
                return;
            }

            // O Call Assíncrono para o GPT-4 Mini (Rápido e barato)
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'És o Oráculo, um assistente especialista em gestão de negócios, vendas e suporte técnico para prestadores de serviços e autónomos. Sê direto, prático e focado no lucro e eficiência. Responde sempre em Português de Portugal ou Brasil, dependendo do contexto.'
                        ],
                        ['role' => 'user', 'content' => $this->question],
                    ],
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                // Extrai o texto da resposta
                $this->answer = $response->json('choices.0.message.content');
            } else {
                Log::error("Oráculo API Error: " . $response->body());
                $this->answer = "⚠️ O Oráculo está a sofrer interferências no radar. Tenta novamente mais tarde.";
            }
        } catch (\Exception $e) {
            Log::error("Oráculo Exception: " . $e->getMessage());
            $this->answer = "⚠️ Ocorreu um erro de comunicação com o quartel-general da IA.";
        }

        $this->question = ''; // Limpa a caixa de texto depois de enviar
    }
}
