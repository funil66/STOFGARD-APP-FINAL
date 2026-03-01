<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class LgpdEncryptDataCommand extends Command
{
    protected $signature = 'iron:lgpd-encrypt';
    protected $description = 'Criptografa CPFs e Telefones retroativos para adequação à LGPD';

    public function handle()
    {
        $this->info("🛡️ Iniciando Protocolo LGPD: Criptografia de Dados Sensíveis...");

        // Ajuste o nome da sua tabela e colunas conforme sua modelagem real
        $tabela = 'cadastros'; // ou 'clientes', dependendo do seu schema
        $colunasSensiveis = ['documento', 'telefone', 'celular', 'email'];

        $registros = DB::table($tabela)->get();
        $bar = $this->output->createProgressBar(count($registros));
        $bar->start();

        foreach ($registros as $registro) {
            $updateData = [];

            foreach ($colunasSensiveis as $coluna) {
                if (!empty($registro->$coluna)) {
                    // Evita criptografar algo que já foi criptografado (começa com eyJpdi...)
                    if (!str_starts_with($registro->$coluna, 'eyJpdi')) {
                        $updateData[$coluna] = Crypt::encryptString($registro->$coluna);
                    }
                }
            }

            if (!empty($updateData)) {
                DB::table($tabela)->where('id', $registro->id)->update($updateData);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n✅ Criptografia concluída com sucesso! Agora você pode adicionar o Cast 'encrypted' nos Models.");
    }
}
