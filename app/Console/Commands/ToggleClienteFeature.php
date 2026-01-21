<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;

class ToggleClienteFeature extends Command
{
    protected $signature = 'cliente:feature
        {feature : Feature key (e.g. beta_feature_x)}
        {--client= : Cliente ID to target}
        {--all : Apply to all clients}
        {--enable : Enable the feature}
        {--disable : Disable the feature}
        {--force : Do not ask for confirmation}';

    protected $description = 'Enable or disable a feature flag for a specific cliente or all clients.';

    public function handle()
    {
        $feature = $this->argument('feature');
        $clientId = $this->option('client');
        $applyAll = $this->option('all');
        $enable = $this->option('enable');
        $disable = $this->option('disable');

        if (! $enable && ! $disable) {
            $this->error('Specify --enable or --disable.');
            return 1;
        }

        if (! $applyAll && ! $clientId) {
            $this->error('Specify --client=ID or --all.');
            return 1;
        }

        if ($applyAll) {
            $count = Cliente::count();
            $this->info("Applying to all {$count} clients...");
            if (! $this->option('force') && ! $this->confirm('Proceed?')) {
                $this->info('Aborted.');
                return 0;
            }

            Cliente::cursor()->each(function (Cliente $cliente) use ($feature, $enable) {
                if ($enable) {
                    $cliente->enableFeature($feature);
                } else {
                    $cliente->disableFeature($feature);
                }
            });

            $this->info('Done.');
            return 0;
        }

        $cliente = Cliente::find($clientId);
        if (! $cliente) {
            $this->error("Cliente not found: {$clientId}");
            return 1;
        }

        if (! $this->option('force') && ! $this->confirm("Proceed to " . ($enable ? 'enable' : 'disable') . " '{$feature}' for cliente {$cliente->nome} (ID: {$cliente->id})?")) {
            $this->info('Aborted.');
            return 0;
        }

        if ($enable) {
            $cliente->enableFeature($feature);
            $this->info("Feature '{$feature}' enabled for cliente: {$cliente->nome} ({$cliente->id})");
        } else {
            $cliente->disableFeature($feature);
            $this->info("Feature '{$feature}' disabled for cliente: {$cliente->nome} ({$cliente->id})");
        }

        return 0;
    }
}
