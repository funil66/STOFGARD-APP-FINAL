<?php

namespace App\Services;

use App\Models\Orcamento;

class WhatsAppService
{
    /**
     * Gera um link do WhatsApp com mensagem pré-definida.
     */
    public function getLink(string $phone, string $message): string
    {
        // Limpa o telefone (remove caracteres não numéricos)
        $phone = preg_replace('/\D/', '', $phone);

        // Adiciona DDI 55 se não tiver
        if (strlen($phone) <= 11) {
            $phone = '55'.$phone;
        }

        $encodedMessage = urlencode($message);

        // Retorna link universal (funciona mobile e web)
        return "https://wa.me/{$phone}?text={$encodedMessage}";
    }

    public function getWelcomeLink($cliente): string
    {
        if (! $cliente || ! $cliente->celular) {
            return '';
        }

        $nome = explode(' ', $cliente->nome)[0];
        $msg = "Olá {$nome}, tudo bem? Aqui é da StofGard! Recebemos seu contato e gostaríamos de entender melhor sua necessidade para o serviço de estofados.";

        return $this->getLink($cliente->celular, $msg);
    }

    public function getProposalLink(Orcamento $orcamento): string
    {
        $cliente = $orcamento->cliente;
        if (! $cliente || ! $cliente->celular) {
            return '';
        }

        $nome = explode(' ', $cliente->nome)[0];

        // Gera URL assinada que expira em 30 dias
        $linkPdf = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'orcamento.compartilhar',
            now()->addDays(30),
            ['orcamento' => $orcamento->id]
        );

        $msg = "Olá {$nome}! Tudo bem?\n\n";
        $msg .= "Conforme combinamos, segue o link seguro do seu orçamento detalhado:\n\n";
        $msg .= "{$linkPdf}\n\n";
        $msg .= "📱 *Este link é válido por 30 dias*\n";
        $msg .= "📄 Clique para visualizar ou baixar o PDF\n\n";
        $msg .= 'Ficamos à disposição para qualquer dúvida!';

        return $this->getLink($cliente->celular, $msg);
    }

    public function getFollowUpLink(Orcamento $orcamento): string
    {
        $cliente = $orcamento->cliente;
        if (! $cliente || ! $cliente->celular) {
            return '';
        }

        $nome = explode(' ', $cliente->nome)[0];

        $msg = "Oi {$nome}, bom dia! \n";
        $msg .= 'Conseguiu dar uma olhada na proposta que enviei? Podemos agendar o serviço para esta semana?';

        return $this->getLink($cliente->celular, $msg);
    }

    public function getPaymentLink(Orcamento $orcamento): string
    {
        $cliente = $orcamento->cliente;
        if (! $cliente || ! $cliente->celular) {
            return '';
        }

        $nome = explode(' ', $cliente->nome)[0];
        $pix = $orcamento->pix_copia_cola ?? 'Chave não gerada';

        $msg = "Olá {$nome}! Segue o código PIX para pagamento:\n\n";
        $msg .= "{$pix}\n\n";
        $msg .= 'Assim que realizar, por favor me envie o comprovante para já agendarmos a data!';

        return $this->getLink($cliente->celular, $msg);
    }
}
