<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Jobs\SendWhatsAppJob;
use Illuminate\Support\Facades\Log;

class ChargeTenantsCommand extends Command
{
    protected $signature = 'iron:charge-tenants';
    protected $description = 'Gera cobrança e manda o Seu Barriga cobrar o aluguel dos Tenants no WhatsApp';

    public function handle()
    {
        $this->info("💰 Iniciando a Máquina de Fazer Dinheiro (Cobrança de Tenants)...");

        // Busca todos os inquilinos que estão com o sistema ativo
        $tenants = Tenant::where('is_active', true)->get();

        foreach ($tenants as $tenant) {
            // Aqui entraria a API da EFI/Asaas no futuro para gerar o PIX Copia e Cola dinâmico
            // Por enquanto, vamos mandar o texto persuasivo e seu PIX fixo ou CNPJ.

            // TODO: No futuro, criar um campo de telefone_admin no Tenant. Vamos usar um placeholder.
            $telefoneDono = "5511999999999";

            $mensagem = "Fala mestre! Aqui é do sistema STOFGARD. 🚀\n\n";
            $mensagem .= "Sua mensalidade vence hoje. Pra não ter o sistema bloqueado e a galera ficar sem emitir Orçamento, faz o PIX da mensalidade:\n\n";
            $mensagem .= "Valor: R$ 150,00\n";
            $mensagem .= "Chave PIX (CNPJ): 12.345.678/0001-99\n\n";
            $mensagem .= "Mandou o PIX? Só ignorar essa mensagem. Valeu por usar nosso sistema! 🎸";

            // Joga pra fila! A Evolution API que lute no background
            // Atenção: A classe SendWhatsAppJob deve existir para disparar.
            if (class_exists(\App\Jobs\SendWhatsAppJob::class)) {
                \App\Jobs\SendWhatsAppJob::dispatch($telefoneDono, $mensagem);
            } else {
                Log::warning("Iron Code: SendWhatsAppJob não encontrado, simulei disparo para o Tenant {$tenant->id}");
            }

            Log::info("💸 Iron Code: Cobrança disparada no Zap para o Tenant: {$tenant->id}");
            $this->line("✅ Tiro dado no inquilino: {$tenant->id}");
        }

        $this->info("\n🔥 Operação concluída! O Seu Barriga terminou a ronda.");
    }
}
