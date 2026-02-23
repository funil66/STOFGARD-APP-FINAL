<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cadastro;
use App\Models\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Loga o evento para debug
        Log::info('Webhook Evolution Recebido', [
            'event' => $request->input('event')
        ]);

        $event = $request->input('event');

        // 2. Só nos importamos com novas mensagens ('messages.upsert')
        if ($event !== 'messages.upsert') {
            return response()->json(['status' => 'ignored', 'reason' => 'Not a messages.upsert event']);
        }

        $data = $request->input('data');

        if (!isset($data['key']) || !isset($data['message'])) {
            return response()->json(['status' => 'error', 'reason' => 'Invalid payload']);
        }

        $remoteJid = $data['key']['remoteJid'];
        $messageId = $data['key']['id'];
        $fromMe = $data['key']['fromMe'] ?? false;

        // 3. Extrai o texto (A Evolution manda em campos diferentes se for texto puro ou resposta)
        $body = $data['message']['conversation']
            ?? $data['message']['extendedTextMessage']['text']
            ?? '[Mensagem de mídia/áudio não suportada na visualização beta]';

        // 4. Inteligência: Limpa o número tirando o "@s.whatsapp.net" e foca nos últimos 8 dígitos
        $numeroLimpo = preg_replace('/\D/', '', explode('@', $remoteJid)[0]);
        $oitoDigitos = substr($numeroLimpo, -8); // Estratégia para ignorar o "+55" e o nono dígito (que muda de DDD para DDD)

        // 5. Tenta achar o Cadastro (Cliente) pelo telefone (BUSCA GLOBAL - Single Tenant)
        $cliente = Cadastro::where(function ($query) use ($oitoDigitos) {
            $query->where('telefone', 'like', '%' . $oitoDigitos . '%')
                ->orWhere('celular', 'like', '%' . $oitoDigitos . '%');
        })->first();

        // 6. Guarda no banco de dados
        WhatsappMessage::create([
            'cadastro_id' => $cliente ? $cliente->id : null,
            'remote_message_id' => $messageId,
            'remote_jid' => $remoteJid,
            'body' => $body,
            'type' => 'text',
            'direction' => $fromMe ? 'out' : 'in',
            'status' => 'delivered',
        ]);

        // A API de Webhook exige que você retorne um 200 OK rápido
        return response()->json(['status' => 'success']);
    }
}
