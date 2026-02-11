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
     * Marca uma transação como paga (com dados do formulário).
     */
    public static function baixarPagamento(Financeiro $record, ?array $dados = null): void
    {
        $valorPago = floatval($dados['valor_pago'] ?? $record->valor);
        $valorTotal = floatval($record->valor);

        // Se o valor pago é menor que o total, faz pagamento parcial
        if ($valorPago > 0 && $valorPago < $valorTotal) {
            self::baixarParcial($record, $dados);
            return;
        }

        $record->update([
            'status' => 'pago',
            'data_pagamento' => $dados['data_pagamento'] ?? now(),
            'valor_pago' => $valorPago > 0 ? $valorPago : $valorTotal,
            'forma_pagamento' => $dados['forma_pagamento'] ?? $record->forma_pagamento,
        ]);

        Notification::make()
            ->title('Pagamento confirmado!')
            ->body('R$ ' . number_format(floatval($record->valor_pago), 2, ',', '.') . ' registrado com sucesso.')
            ->success()
            ->send();
    }

    /**
     * Pagamento parcial: paga parte do valor e gera novo registro com o saldo restante.
     */
    public static function baixarParcial(Financeiro $record, array $dados): void
    {
        $valorPago = floatval($dados['valor_pago']);
        $valorOriginal = floatval($record->valor);
        $saldoRestante = $valorOriginal - $valorPago;

        // 1. Marca o registro atual como pago (parcial)
        $record->update([
            'status' => 'pago',
            'data_pagamento' => $dados['data_pagamento'] ?? now(),
            'valor_pago' => $valorPago,
            'forma_pagamento' => $dados['forma_pagamento'] ?? $record->forma_pagamento,
            'observacoes' => trim(
                ($record->observacoes ? $record->observacoes . "\n" : '') .
                '📌 Pagamento parcial: R$ ' . number_format($valorPago, 2, ',', '.') .
                ' de R$ ' . number_format($valorOriginal, 2, ',', '.')
            ),
        ]);

        // 2. Cria novo registro com o saldo restante
        $novoRegistro = Financeiro::create([
            'cadastro_id' => $record->cadastro_id,
            'orcamento_id' => $record->orcamento_id,
            'ordem_servico_id' => $record->ordem_servico_id,
            'id_parceiro' => $record->id_parceiro,
            'tipo' => $record->tipo,
            'is_comissao' => $record->is_comissao,
            'descricao' => $record->descricao . ' (Saldo restante)',
            'observacoes' => '📌 Saldo restante de pagamento parcial (Ref. #' . $record->id . '). Original: R$ ' . number_format($valorOriginal, 2, ',', '.') . ', Pago: R$ ' . number_format($valorPago, 2, ',', '.'),
            'categoria_id' => $record->categoria_id,
            'valor' => $saldoRestante,
            'data' => $record->data ?? now(),
            'data_vencimento' => $record->data_vencimento ?? now()->addDays(30),
            'status' => 'pendente',
            'forma_pagamento' => null,
        ]);

        Notification::make()
            ->title('Pagamento parcial registrado!')
            ->body(
                'Pago: R$ ' . number_format($valorPago, 2, ',', '.') .
                ' | Novo registro #' . $novoRegistro->id .
                ' criado com saldo de R$ ' . number_format($saldoRestante, 2, ',', '.')
            )
            ->warning()
            ->send();
    }

    public static function estornarPagamento(Financeiro $record): void
    {
        $record->update([
            'status' => 'pendente',
            'data_pagamento' => null,
            'valor_pago' => null,
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
                'forma_pagamento' => 'transferencia',
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
        $count = 0;
        $records->each(function ($record) use (&$count) {
            if ($record->status === 'pendente' || $record->status === 'atrasado') {
                $record->update([
                    'status' => 'pago',
                    'data_pagamento' => now(),
                    'valor_pago' => $record->valor,
                ]);
                $count++;
            }
        });

        Notification::make()
            ->title("Pagamentos confirmados em lote! ({$count} registros)")
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
