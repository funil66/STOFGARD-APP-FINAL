<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            // Lista de colunas antigas identificadas no log
            $columns = [
                'criado_por', 'atualizado_por', 'parceiro_id', 'ordem_servico_id', 'area_m2', 'valor_m2', 'valor_desconto', 'forma_pagamento',
                'pix_chave_tipo', 'pix_chave_valor', 'pix_txid', 'pix_qrcode_base64', 'pix_copia_cola', 'link_pagamento_hash',
                'aprovado_em', 'reprovado_em', 'motivo_reprovacao', 'data_servico_agendada', 'numero_pedido_parceiro', 'observacoes_internas', 'documentos'
            ];

            // Tratar colunas de relacionamento (dropar FK, ajustar tipo, readicionar FK)
            foreach (['parceiro_id', 'ordem_servico_id'] as $fkColumn) {
                if (Schema::hasColumn('orcamentos', $fkColumn)) {
                    try {
                        // Remove FK se existir
                        $table->dropForeign([$fkColumn]);
                    } catch (\Throwable $e) {
                        // ignore se não existir
                    }
                    try {
                        // Define como unsignedBigInteger nullable
                        $table->unsignedBigInteger($fkColumn)->nullable()->change();
                    } catch (\Throwable $e) {
                        // ignore mudanças de tipo se falhar
                    }
                    // Re-adiciona FK somente para colunas conhecidas
                    try {
                        if ($fkColumn === 'parceiro_id') {
                            $table->foreign('parceiro_id')->references('id')->on('parceiros')->nullOnDelete();
                        } elseif ($fkColumn === 'ordem_servico_id') {
                            $table->foreign('ordem_servico_id')->references('id')->on('ordens_servico')->nullOnDelete();
                        }
                    } catch (\Throwable $e) {
                        // ignore se não puder re-criar FK
                    }
                }
            }

            // Agora processa as demais colunas genéricas
            foreach ($columns as $column) {
                if (in_array($column, ['parceiro_id', 'ordem_servico_id'])) {
                    // já tratadas acima
                    continue;
                }
                if (Schema::hasColumn('orcamentos', $column)) {
                    try {
                        // Colunas que precisam ser text (ex: base64 longo ou JSON)
                        if (in_array($column, ['pix_qrcode_base64', 'documentos', 'observacoes_internas'])) {
                            $table->text($column)->nullable()->change();
                        } else {
                            // Tentativa genérica: converte para string nullable se aplicável
                            $table->string($column)->nullable()->change();
                        }
                    } catch (\Throwable $e) {
                        // Se falhar (tipo numérico/decimal/etc), tentamos mudanças específicas para alguns campos conhecidos
                        try {
                            if (in_array($column, ['valor_desconto', 'valor_m2', 'area_m2'])) {
                                $table->decimal($column, 10, 2)->nullable()->change();
                            } else {
                                // fallback: ignorar
                            }
                        } catch (\Throwable $e) {
                            // ignorar falhas de change() por tipo
                        }
                    }
                }
            }

            // Correções específicas de tipo para garantir
            if (Schema::hasColumn('orcamentos', 'criado_por')) $table->string('criado_por')->nullable()->change();
            if (Schema::hasColumn('orcamentos', 'valor_desconto')) $table->decimal('valor_desconto', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Irreversível
    }
};
