<?php
$file = 'app/Livewire/RegistroEmpresa.php';
$content = file_get_contents($file);

$cacheLogic = <<<PHP
        // Salva dados do owner no cache para o job em background (CreateTenantOwnerJob)
        \$pendingOwner = [
            'name'     => \$this->admin_nome,
            'email'    => strtolower(trim(\$this->admin_email)),
            'password' => \$this->admin_password,
        ];
        \Illuminate\Support\Facades\Cache::put('pending_owner_' . trim(strtolower(\$slug)), \$pendingOwner, now()->addMinutes(60));

        // Create tenant (triggers CreateDatabase
PHP;

$content = str_replace('        // Create tenant (triggers CreateDatabase', $cacheLogic, $content);

$syncLogicToRemove = <<<PHP
        // Garante baseline visual/funcional idêntico ao tenant referência (STOFGARD)
        app(TenantTemplateProvisioner::class)->apply(\$tenant);

        // Create admin user inside tenant context
        \$tenant->run(function () {
            User::create([
                'name' => \$this->admin_nome,
                'email' => \$this->admin_email,
                'password' => Hash::make(\$this->admin_password),
                'is_admin' => true,
                'tenant_id' => tenant('id'),
            ]);
        });
PHP;

$replacement = <<<PHP
        // Template provisionado e Usuario criado via Fila (JobPipeline TenancyServiceProvider)
PHP;

$content = str_replace($syncLogicToRemove, $replacement, $content);

file_put_contents($file, $content);
echo "Patched RegistroEmpresa.php!\n";
