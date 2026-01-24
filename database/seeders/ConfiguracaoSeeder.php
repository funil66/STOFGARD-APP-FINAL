<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ConfiguracaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        \App\Models\Configuracao::firstOrCreate(
            ['id' => 1],
            [
                'empresa_nome' => 'Stofgard',
                'desconto_pix' => 10,
                'repassar_taxas' => true,
                'taxas_parcelamento' => [
                    2 => 1.0459,
                    3 => 1.0549,
                    4 => 1.06,
                    5 => 1.07,
                    6 => 1.08
                ]
            ]
        );
    }
}
