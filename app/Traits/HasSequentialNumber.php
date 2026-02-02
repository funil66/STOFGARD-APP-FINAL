<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Trait HasSequentialNumber
 * 
 * Gera números sequenciais únicos para modelos com padrão YYYY.XXXX
 * Usa tabela de controle de sequências com lock de banco para evitar colisões
 * 
 * Uso:
 * 1. Adicione o trait ao model
 * 2. Defina a propriedade protegida $sequenceType (ex: 'orcamento', 'os', 'nf')
 * 3. Defina a propriedade protegida $sequenceColumn (ex: 'numero', 'numero_os')
 * 
 * Exemplo:
 * use HasSequentialNumber;
 * protected string $sequenceType = 'orcamento';
 * protected string $sequenceColumn = 'numero';
 */
trait HasSequentialNumber
{
    /**
     * Boot do trait - registra evento creating
     */
    protected static function bootHasSequentialNumber(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getSequenceColumn()})) {
                $model->{$model->getSequenceColumn()} = $model->generateSequentialNumber();
            }
        });
    }

    /**
     * Retorna o tipo de sequência do model
     */
    protected function getSequenceType(): string
    {
        return $this->sequenceType ?? 'default';
    }

    /**
     * Retorna a coluna que armazena o número sequencial
     */
    protected function getSequenceColumn(): string
    {
        return $this->sequenceColumn ?? 'numero';
    }

    /**
     * Gera número sequencial único no formato YYYY.XXXX
     * 
     * @return string
     */
    public function generateSequentialNumber(): string
    {
        return DB::transaction(function () {
            $ano = date('Y');
            $tipo = $this->getSequenceType();

            // Busca ou cria registro de sequência com lock FOR UPDATE
            $sequencia = DB::table('sequencias')
                ->where('tipo', $tipo)
                ->where('ano', $ano)
                ->lockForUpdate()
                ->first();

            if (!$sequencia) {
                // Primeira sequência do ano
                DB::table('sequencias')->insert([
                    'tipo' => $tipo,
                    'ano' => $ano,
                    'ultimo_numero' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $proximoNumero = 1;
            } else {
                // Incrementa sequência existente
                $proximoNumero = $sequencia->ultimo_numero + 1;
                DB::table('sequencias')
                    ->where('tipo', $tipo)
                    ->where('ano', $ano)
                    ->update([
                        'ultimo_numero' => $proximoNumero,
                        'updated_at' => now(),
                    ]);
            }

            // Formata: YYYY.XXXX (ex: 2026.0001)
            return sprintf('%s.%04d', $ano, $proximoNumero);
        });
    }

    /**
     * Método estático para geração manual (retrocompatibilidade)
     * 
     * @return string
     */
    public static function gerarNumeroSequencial(): string
    {
        return (new static)->generateSequentialNumber();
    }
}
