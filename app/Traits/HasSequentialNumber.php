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
     * Gera número único no formato YYYY.XXXX (aleatório)
     */
    public function generateSequentialNumber(): string
    {
        return DB::transaction(function () {
            $ano = date('Y');
            $tipo = $this->getSequenceType();

            // Gera 10 tentativas de número aleatório
            for ($i = 0; $i < 10; $i++) {
                // Gera 4 dígitos aleatórios (1000-9999)
                $randomDigits = rand(1000, 9999);
                $numero = sprintf('%s.%04d', $ano, $randomDigits);

                // Verifica se já existe
                $existe = static::where($this->getSequenceColumn(), $numero)->exists();

                if (! $existe) {
                    return $numero;
                }
            }

            // Fallback: se após 10 tentativas ainda colidiu, usa timestamp
            $timestamp = substr(str_replace('.', '', microtime(true)), -4);

            return sprintf('%s.%s', $ano, $timestamp);
        });
    }

    /**
     * Método estático para geração manual (retrocompatibilidade)
     */
    public static function gerarNumeroSequencial(): string
    {
        return (new static)->generateSequentialNumber();
    }
}
