<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cadastro;

class CadastroSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CLIENTES FINAIS
        Cadastro::firstOrCreate(
            ['nome' => 'João da Silva', 'tipo' => 'cliente'],
            [
                'documento' => '123.456.789-00',
                'telefone' => '(16) 99111-2222',
                'email' => 'joao@email.com',
                'cidade' => 'Ribeirão Preto',
                'estado' => 'SP',
                'logradouro' => 'Av. Independência',
                'numero' => '1000',
                'bairro' => 'Alto da Boa Vista'
            ]
        );

        Cadastro::firstOrCreate(
            ['nome' => 'Maria Oliveira', 'tipo' => 'cliente'],
            [
                'documento' => '987.654.321-11',
                'telefone' => '(16) 98888-7777',
                'email' => 'maria@email.com',
                'cidade' => 'Bonfim Paulista',
                'estado' => 'SP',
                'logradouro' => 'Rua do Professor',
                'numero' => '50',
                'bairro' => 'Centro'
            ]
        );

        // 2. PARCEIROS (LOJAS)
        $loja = Cadastro::firstOrCreate(
            ['nome' => 'Decor House', 'tipo' => 'loja'],
            [
                'documento' => '11.222.333/0001-99',
                'telefone' => '(16) 3636-1010',
                'email' => 'contato@decorhouse.com.br',
                'cidade' => 'Ribeirão Preto',
                'estado' => 'SP',
                'logradouro' => 'Av. Presidente Vargas',
                'numero' => '2000',
                'bairro' => 'Jardim Sumaré'
            ]
        );

        // 3. VENDEDORES (VINCULADOS À LOJA)
        Cadastro::firstOrCreate(
            ['nome' => 'Carlos Vendedor', 'tipo' => 'vendedor'],
            [
                'parent_id' => $loja->id,
                'telefone' => '(16) 99777-6666',
                'email' => 'carlos@decorhouse.com.br',
                'cidade' => 'Ribeirão Preto',
                'estado' => 'SP',
                'logradouro' => 'Rua Vendedor',
                'numero' => '10',
                'bairro' => 'Irajá'
            ]
        );

        // 4. ARQUITETOS
        Cadastro::firstOrCreate(
            ['nome' => 'Arq. Ana Souza', 'tipo' => 'arquiteto'],
            [
                'telefone' => '(16) 99999-8888',
                'email' => 'ana.arq@projeto.com',
                'cidade' => 'Ribeirão Preto',
                'estado' => 'SP',
                'logradouro' => 'Rua dos Arquitetos',
                'numero' => '100',
                'bairro' => 'Fiusa'
            ]
        );
    }
}
