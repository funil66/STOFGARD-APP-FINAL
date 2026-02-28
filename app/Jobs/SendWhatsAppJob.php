<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    // Retentativas exponenciais: tenta em 30s, se falhar de novo em 2 min, depois 5 min.
    public function backoff()
    {
        return [30, 120, 300];
    }

    protected $telefone;
    protected $mensagem;
    protected $instancia;

    public function __construct(string $telefone, string $mensagem, string $instancia = 'default')
    {
        $this->telefone = $telefone;
        $this->mensagem = $mensagem;
        $this->instancia = $instancia;
    }

    public function handle()
    {
        $apiUrl = env('EVOLUTION_API_URL');
        $apiKey = env('EVOLUTION_API_KEY');

        if (!$apiUrl || !$apiKey) {
            Log::error("🔴 Iron Code: As credenciais da Evolution API sumiram do .env, porra!");
            return;
        }

        $response = Http::withHeaders([
            'apikey' => $apiKey,
            'Content-Type' => 'application/json'
        ])->post("{$apiUrl}/message/sendText/{$this->instancia}", [
                    'number' => $this->telefone,
                    'options' => [
                        'delay' => 1500, // Dá uma fingida que tá digitando
                        'presence' => 'composing',
                        'linkPreview' => true
                    ],
                    'textMessage' => [
                        'text' => $this->mensagem
                    ]
                ]);

        if ($response->failed()) {
            Log::error("🔥 Falha de comunicação no Zap pro número {$this->telefone}. Resposta: " . $response->body());
            $response->throw(); // Força o falecimento do Job pra ele ir pro Retry
        }

        Log::info("✅ Rádio limpo: WhatsApp disparado com sucesso para {$this->telefone}!");
    }
}
