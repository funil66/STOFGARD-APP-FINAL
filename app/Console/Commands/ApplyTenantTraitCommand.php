<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ApplyTenantTraitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iron:apply-tenant';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fuzila a trait BelongsToTenant em todos os models (Gambiarra de luxo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🚁 Iniciando a Operação: Chuva de Traits...");

        $modelsPath = app_path('Models');

        // Models que NÃO DEVEM ter o escopo de Tenant (Bancos centrais ou de sistema)
        $ignoredModels = [
            'User.php',
            'Tenant.php',
            'Cliente.php',
            'GoogleToken.php',
            'CadastroView.php'
        ];

        if (!File::exists($modelsPath)) {
            $this->error("Cadê a pasta Models, porra? Caminho não encontrado.");
            return;
        }

        $files = File::files($modelsPath);
        $count = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();

            if (in_array($filename, $ignoredModels)) {
                $this->warn("🛡️ Pulando: {$filename} (Alvo Blindado)");
                continue;
            }

            $content = File::get($file);

            // Verifica se a Trait já foi importada antes
            if (str_contains($content, 'use App\Traits\BelongsToTenant;')) {
                $this->line("✅ Já estava fuzilado: {$filename}");
                continue;
            }

            // 1. Injeta o 'use' da namespace lá no topo, logo abaixo da declaração da classe
            $content = preg_replace(
                '/namespace App\\\\Models;/',
                "namespace App\\Models;\n\nuse App\\Traits\\BelongsToTenant;",
                $content
            );

            // 2. Injeta a trait 'use BelongsToTenant;' logo na primeira linha de dentro da classe
            // Regex ninja para pegar a abertura da classe, independente de ter "implements" ou não
            $content = preg_replace(
                '/(class\s+[a-zA-Z0-9_]+\s+extends\s+[^{]+)\{/s',
                "$1{\n    use BelongsToTenant;\n",
                $content,
                1
            );

            File::put($file, $content);
            $this->info("🎯 Headshot: {$filename} agora pertence a um Tenant!");
            $count++;
        }

        $this->info("\n🔥 Operação concluída! {$count} Models infectados com sucesso.");
    }
}
