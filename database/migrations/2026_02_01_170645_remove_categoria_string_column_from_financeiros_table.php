<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove a coluna 'categoria' (string) da tabela financeiros para evitar conflito 
     * com o relacionamento categoria() que usa categoria_id.
     */
    public function up(): void
    {
        if (Schema::hasTable('financeiros') && Schema::hasColumn('financeiros', 'categoria')) {
            // Para SQLite, vamos tentar uma abordagem mais simples primeiro
            try {
                if (DB::getDriverName() === 'sqlite') {
                    // No SQLite, vamos criar nova tabela sem a coluna conflitante
                    Schema::create('financeiros_new', function (Blueprint $table) {
                        $table->id();
                        $table->foreignId('cadastro_id')->nullable()->constrained('cadastros')->nullOnDelete();
                        $table->foreignId('orcamento_id')->nullable()->constrained('orcamentos')->nullOnDelete();
                        $table->foreignId('ordem_servico_id')->nullable()->constrained('ordem_servicos')->nullOnDelete();
                        $table->enum('tipo', ['entrada', 'saida']);
                        $table->string('descricao');
                        $table->text('observacoes')->nullable();
                        $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
                        $table->decimal('valor', 10, 2);
                        $table->decimal('valor_pago', 10, 2)->default(0);
                        $table->decimal('desconto', 10, 2)->default(0);
                        $table->decimal('juros', 10, 2)->default(0);
                        $table->decimal('multa', 10, 2)->default(0);
                        $table->date('data');
                        $table->date('data_vencimento')->nullable();
                        $table->date('data_pagamento')->nullable();
                        $table->enum('status', ['pendente', 'pago', 'vencido', 'cancelado'])->default('pendente');
                        $table->string('forma_pagamento')->nullable();
                        $table->string('comprovante')->nullable();
                        $table->string('pix_txid')->nullable();
                        $table->text('pix_qrcode_base64')->nullable();
                        $table->text('pix_copia_cola')->nullable();
                        $table->string('pix_location')->nullable();
                        $table->timestamp('pix_expiracao')->nullable();
                        $table->string('pix_status')->nullable();
                        $table->text('pix_response')->nullable();
                        $table->timestamp('pix_data_pagamento')->nullable();
                        $table->decimal('pix_valor_pago', 10, 2)->nullable();
                        $table->string('link_pagamento_hash')->nullable();
                        $table->json('extra_attributes')->nullable();
                        $table->timestamps();
                    });

                    // Copiar dados (exceto coluna categoria)
                    DB::statement('INSERT INTO financeiros_new (
                        id, cadastro_id, orcamento_id, ordem_servico_id, tipo, descricao, observacoes, 
                        categoria_id, valor, valor_pago, desconto, juros, multa, data, data_vencimento, 
                        data_pagamento, status, forma_pagamento, comprovante, pix_txid, pix_qrcode_base64, 
                        pix_copia_cola, pix_location, pix_expiracao, pix_status, pix_response, 
                        pix_data_pagamento, pix_valor_pago, link_pagamento_hash, extra_attributes, created_at, updated_at
                    ) SELECT 
                        id, cadastro_id, orcamento_id, ordem_servico_id, tipo, descricao, observacoes, 
                        categoria_id, valor, valor_pago, desconto, juros, multa, data, data_vencimento, 
                        data_pagamento, status, forma_pagamento, comprovante, pix_txid, pix_qrcode_base64, 
                        pix_copia_cola, pix_location, pix_expiracao, pix_status, pix_response, 
                        pix_data_pagamento, pix_valor_pago, link_pagamento_hash, extra_attributes, created_at, updated_at
                    FROM financeiros');

                    // Substituir tabela
                    Schema::dropIfExists('financeiros');
                    Schema::rename('financeiros_new', 'financeiros');
                } else {
                    // Para outros DBs, remover coluna diretamente
                    Schema::table('financeiros', function (Blueprint $table) {
                        $table->dropColumn('categoria');
                    });
                }
                
                echo "✅ Coluna 'categoria' (string) removida com sucesso!\n";
                
            } catch (\Exception $e) {
                echo "⚠️  Erro ao remover coluna categoria: " . $e->getMessage() . "\n";
                echo "ℹ️  O conflito será tratado no nível da aplicação.\n";
            }
        } else {
            echo "ℹ️  Coluna 'categoria' não encontrada.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('financeiros') && !Schema::hasColumn('financeiros', 'categoria')) {
            Schema::table('financeiros', function (Blueprint $table) {
                $table->string('categoria')->nullable()->after('descricao');
            });
            
            echo "⚠️  Coluna 'categoria' (string) adicionada de volta.\n";
        }
    }
};
