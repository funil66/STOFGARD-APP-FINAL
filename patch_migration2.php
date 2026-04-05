<?php
$file = 'database/migrations/tenant/2026_02_05_025818_change_unidade_to_string_in_estoques_table.php';
$content = file_get_contents($file);
$old = <<<OLD
    public function down(): void
    {
        \$temObservacoes = Schema::hasColumn('estoques', 'observacoes');
        \$temTipo = Schema::hasColumn('estoques', 'tipo');

        // Recria com enum se precisar reverter
        Schema::create('estoques_new', function (Blueprint \$table) use (\$temObservacoes, \$temTipo) {
            \$table->id();
            \$table->timestamps();
            \$table->string('item'); // Nome do produto
            \$table->decimal('quantidade', 10, 2)->default(0); // Quantidade atual
            \$table->enum('unidade', ['unidade', 'litros', 'caixa', 'metro'])->default('unidade'); // Unidade de medida
            \$table->decimal('minimo_alerta', 10, 2)->default(5); // Quantidade mínima para alerta
            if (\$temTipo) \$table->string('tipo')->default('geral'); 
            if (\$temObservacoes) \$table->text('observacoes')->nullable();
        });

        \$colunasArr = ['id', 'created_at', 'updated_at', 'item', 'quantidade', 'unidade', 'minimo_alerta'];
        if (\$temTipo) \$colunasArr[] = 'tipo';
        if (\$temObservacoes) \$colunasArr[] = 'observacoes';
        
        \$colunasStr = implode(',', \$colunasArr);

        \Illuminate\Support\Facades\DB::statement("INSERT INTO estoques_new (\$colunasStr) SELECT \$colunasStr FROM estoques WHERE unidade IN ('unidade', 'litros', 'caixa', 'metro')");

        Schema::drop('estoques');
        Schema::rename('estoques_new', 'estoques');
    }
OLD;

$new = <<<NEW
    public function down(): void
    {
        Schema::dropIfExists('estoques_new');
        
        // As SQLite can't alter column type directly easily or rollback complex states,
        // and we really only care about re-creating the table structure cleanly on rollback,
        // we'll try to flush Doctrine schema cache to avoid hasColumn returning true incorrectly
        \Illuminate\Support\Facades\Schema::connection(null)->flush();

        \$temObservacoes = \Illuminate\Support\Facades\Schema::hasColumn('estoques', 'observacoes');
        \$temTipo = \Illuminate\Support\Facades\Schema::hasColumn('estoques', 'tipo');

        Schema::create('estoques_new', function (Blueprint \$table) use (\$temObservacoes, \$temTipo) {
            \$table->id();
            \$table->timestamps();
            \$table->string('item'); 
            \$table->decimal('quantidade', 10, 2)->default(0); 
            \$table->enum('unidade', ['unidade', 'litros', 'caixa', 'metro'])->default('unidade'); 
            \$table->decimal('minimo_alerta', 10, 2)->default(5); 
            if (\$temTipo) \$table->string('tipo')->default('geral'); 
            if (\$temObservacoes) \$table->text('observacoes')->nullable();
        });

        \$colunasArr = ['id', 'created_at', 'updated_at', 'item', 'quantidade', 'unidade', 'minimo_alerta'];
        if (\$temTipo) \$colunasArr[] = 'tipo';
        if (\$temObservacoes) \$colunasArr[] = 'observacoes';
        
        \$colunasStr = implode(',', \$colunasArr);

        \Illuminate\Support\Facades\DB::statement("INSERT INTO estoques_new (\$colunasStr) SELECT \$colunasStr FROM estoques WHERE unidade IN ('unidade', 'litros', 'caixa', 'metro')");

        Schema::drop('estoques');
        Schema::rename('estoques_new', 'estoques');
    }
NEW;

$content = str_replace($old, $new, $content);
file_put_contents($file, $content);
