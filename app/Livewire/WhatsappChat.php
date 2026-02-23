<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cadastro;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;

class WhatsappChat extends Component
{
    public Cadastro $record; // O Filament injeta isso automaticamente nas Infolists
    public $newMessage = '';

    public function sendMessage()
    {
        $this->validate([
            'newMessage' => 'required|string|max:1000'
        ]);

        // 1. Limpa o telefone do cliente
        $numeroCru = $this->record->celular ?? $this->record->telefone;
        $telefone = preg_replace('/\D/', '', $numeroCru);

        if (strlen($telefone) < 10) {
            $this->addError('newMessage', 'Cliente sem telefone válido cadastrado.');
            return;
        }

        // Se não tiver código do país, injeta o 55 (Brasil)
        if (!str_starts_with($telefone, '55')) {
            $telefone = '55' . $telefone;
        }

        // 2. Parâmetros da Evolution API (.env required)
        $apiUrl = env('EVOLUTION_API_URL', 'http://localhost:8080');
        $instance = env('EVOLUTION_INSTANCE', 'stofgard');
        $apikey = env('EVOLUTION_API_KEY', 'sua-api-key-global');

        try {
            // 3. Dispara POST pra Evolution
            $response = Http::withHeaders(['apikey' => $apikey])
                ->post("{$apiUrl}/message/sendText/{$instance}", [
                    'number' => $telefone,
                    'options' => [
                        'delay' => 1200, // Delay "humano"
                        'presence' => 'composing'
                    ],
                    'textMessage' => [
                        'text' => $this->newMessage
                    ]
                ]);

            if ($response->successful()) {
                // 4. Salva no banco como "enviada"
                WhatsappMessage::create([
                    'cadastro_id' => $this->record->id,
                    'remote_jid' => $telefone . '@s.whatsapp.net',
                    'body' => $this->newMessage,
                    'type' => 'text',
                    'direction' => 'out',
                    'status' => 'sent',
                ]);
                $this->newMessage = ''; // Limpa o input
            } else {
                $this->addError('newMessage', 'A Evolution API recusou o disparo.');
            }
        } catch (\Exception $e) {
            $this->addError('newMessage', 'Falha ao conectar no servidor do WhatsApp.');
        }
    }

    public function render()
    {
        // Puxa mensagens do relacionamento whatsappMessages
        $messages = $this->record->whatsappMessages()->orderBy('created_at', 'asc')->get();
        return view('livewire.whatsapp-chat', compact('messages'));
    }
}
