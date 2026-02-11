<?php

namespace App\Services;

use App\Models\Financeiro;
use App\Models\OrdemServico;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceiroService
{
    /**
     * Gera a previsão de receita (Conta a Receber) baseada em uma OS recém-criada.
     * Usa o modelo Financeiro com cadastro_id (Cadastro Unificado).
     */
    public function gerarPreviaReceita(OrdemServico $os): Financeiro
    {
        return Financeiro::create([
            'cadastro_id' => $os->cadastro_id,
            'ordem_servico_id' => $os->id,
            'tipo' => 'entrada',
            'categoria' => 'servico',
            'descricao' => "Receita ref. OS #{$os->numero_os} - " . ($os->cliente->nome ?? 'Cliente'),
            'valor' => $os->valor_total,
            'data_vencimento' => now()->addDays(30),
            'status' => 'pendente',
            'forma_pagamento' => null, // Define quando cliente pagar
        ]);
    }

    /**
     * Gera despesa/saída financeira.
     */
    public function gerarDespesa(array $dados): Financeiro
    {
        return Financeiro::create(array_merge([
            'tipo' => 'saida',
            'status' => 'pendente',
        ], $dados));
    }

    /**
     * Marca uma transação como paga.
     */
    public function baixar(Financeiro $financeiro, ?string $formaPagamento = null): Financeiro
    {
        $financeiro->update([
            'status' => 'pago',
            'data_pagamento' => now(),
            'forma_pagamento' => $formaPagamento ?? $financeiro->forma_pagamento,
            'valor_pago' => $financeiro->valor,
        ]);

        return $financeiro->fresh();
    }

    /**
     * Marca uma transação como paga (Static Wrapper com Notificação).
     */
    public static function baixarPagamento(Financeiro $record): void
    {
        // Reutiliza a lógica existente, criando uma instância temporária se necessário, ou apenas chama o update direto.
        // Como o método 'baixar' é de instância e pode ser usado via injeção, aqui fazemos a implementação estática para o Resource.
        $record->update([
            'status' => 'pago',
            'data_pagamento' => now(),
        ]);

        Notification::make()
            ->title('Pago!')
            ->success()
            ->send();
    }

    public static function estornarPagamento(Financeiro $record): void
    {
        $record->update([
            'status' => 'pendente',
            'data_pagamento' => null,
        ]);

        Notification::make()
            ->title('Estornado!')
            ->body('O lançamento voltou para pendente.')
            ->warning()
            ->send();
    }

    public static function pagarComissao(Financeiro $record, ?array $dados = null): void
    {
        $record->update([
            'comissao_paga' => true,
            'comissao_data_pagamento' => now(),
            'status' => 'pago',
            'data_pagamento' => now(),
            'valor_pago' => $record->valor,
        ]);

        // Se dados adicionais foram passados, cria a DESPESA correspondente
        if ($dados) {
            // Busca ou cria categoria 'Comissões'
            $categoria = \App\Models\Categoria::firstOrCreate(
                ['nome' => 'Comissões', 'tipo' => 'financeiro_despesa'],
                ['cor' => '#f59e0b', 'icone' => '💼']
            );

            Financeiro::create([
                'tipo' => 'saida',
                'status' => 'pago',
                'data' => now(),
                'data_vencimento' => now(),
                'data_pagamento' => $dados['data_pagamento'] ?? now(),
                'valor' => $dados['valor'] ?? $record->valor,
                'valor_pago' => $dados['valor'] ?? $record->valor,
                'descricao' => 'Comissão ref. ' . ($record->descricao ?? 'Venda'),
                'categoria_id' => $categoria->id,
                'cadastro_id' => $dados['beneficiario_id'] ?? null,
                'observacoes' => 'Pagamento de comissão gerado automaticamente.',
                'forma_pagamento' => 'transferencia', // Default
            ]);
        }

        Notification::make()
            ->title('Comissão paga com sucesso!')
            ->body('A comissão foi marcada como paga e a despesa financeira gerada.')
            ->success()
            ->send();
    }

    public static function baixarEmLote(Collection $records): void
    {
        $records->each(function ($record) {
            if ($record->status === 'pendente') {
                $record->update([
                    'status' => 'pago',
                    'data_pagamento' => now(),
                ]);
            }
        });

        Notification::make()
            ->title('Pagamentos confirmados em lote!')
            ->success()
            ->send();
    }

    public static function gerarCsvExportacao(Collection $records): StreamedResponse
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=relatorio_financeiro.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Data', 'Tipo', 'Descricao', 'Categoria', 'Cliente', 'Valor', 'Status', 'Forma Pagamento']);

            foreach ($records as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->data->format('d/m/Y'),
                    $row->tipo,
                    $row->descricao,
                    $row->categoria?->nome ?? '-',
                    $row->cadastro?->nome ?? '-',
                    number_format($row->valor, 2, ',', '.'),
                    $row->status,
                    $row->forma_pagamento,
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'relatorio_financeiro.csv', $headers);
    }
}
