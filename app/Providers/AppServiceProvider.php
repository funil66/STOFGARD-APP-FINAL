<?php

namespace App\Providers;

use App\Models\Agenda;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Observers\AgendaObserver;
use App\Observers\OrcamentoObserver;
use App\Observers\OrdemServicoObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Policies\CadastroPolicy;
use App\Policies\FinanceiroPolicy;
use App\Policies\OrcamentoPolicy;
use App\Policies\OrdemServicoPolicy;
use App\Models\Cliente;
use App\Models\Parceiro;
use App\Models\Financeiro;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ensure global helper functions are loaded for environments where composer
        // 'files' autoload may not be available (for example in some test runners).
        if (!function_exists('admin_resource_route')) {
            require_once app_path('helpers.php');
        }

        // Register SettingsHelper as singleton
        $this->app->singleton(\App\Helpers\SettingsHelper::class, function ($app) {
            return new \App\Helpers\SettingsHelper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // O "Modo Deus". Se retornar true aqui, ignora as Policies.
        Gate::before(function (\App\Models\User $user, string $ability) {
            if ($user->is_admin) {
                return true;
            }
        });
        // Allow environment overrides for Node & PDF generator script during tests/CI.
        // Tests call putenv('NODE_BINARY=...') / putenv('PDF_GENERATOR_SCRIPT=...') and
        // some parts of the app read config('app.node_binary') -- ensure the runtime
        // config reflects any env overrides.
        config(['app.node_binary' => env('NODE_BINARY', config('app.node_binary', 'node'))]);
        config(['app.pdf_generator_script' => env('PDF_GENERATOR_SCRIPT', config('app.pdf_generator_script', base_path('scripts/generate-pdf.js')))]);

        Agenda::observe(AgendaObserver::class);
        Orcamento::observe(OrcamentoObserver::class);
        \App\Models\OrdemServico::observe(\App\Observers\OrdemServicoObserver::class);
        \App\Models\ListaDesejo::observe(\App\Observers\ListaDesejoObserver::class);


        // No early redirect middleware registered for /admin/login; allow the
        // dedicated POST route to handle authentication (with CSRF protection).

        // Register policy for cadastro models (cliente and parceiro)
        Gate::policy(\App\Models\Cadastro::class, CadastroPolicy::class);
        Gate::policy(Cliente::class, CadastroPolicy::class);
        Gate::policy(Parceiro::class, CadastroPolicy::class);

        // Register policies for financial security
        Gate::policy(Financeiro::class, FinanceiroPolicy::class);
        Gate::policy(Orcamento::class, OrcamentoPolicy::class);
        Gate::policy(OrdemServico::class, OrdemServicoPolicy::class);

        // Register Agenda Calendar Widget manually for Livewire (since it's not in AdminPanelProvider widgets list)
        \Livewire\Livewire::component('app.filament.widgets.agenda-calendar-widget', \App\Filament\Widgets\AgendaCalendarWidget::class);
        \Livewire\Livewire::component('financeiro-chart', \App\Filament\Widgets\FinanceiroChart::class);
        \Livewire\Livewire::component('despesas-por-categoria-chart', \App\Filament\Widgets\DespesasPorCategoriaChart::class);
        \Livewire\Livewire::component('fluxo-caixa-chart', \App\Filament\Widgets\FluxoCaixaChart::class);
    }
}
