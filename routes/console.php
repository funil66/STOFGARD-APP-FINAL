<?php

use App\Jobs\SuspenderTenantInadimplenteJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 💰 A MÁQUINA DE DINHEIRO: Roda todo dia 5 de cada mês, às 09:00 da manhã
Schedule::command('iron:charge-tenants')
    ->monthlyOn(5, '09:00')
    ->timezone('America/Sao_Paulo');

// 🪂 O PARAQUEDAS (BACKUP): Faz o dump do banco toda madrugada às 03:00 e envia pro seu cloud
Schedule::command('backup:run --only-db')
    ->dailyAt('03:00')
    ->timezone('America/Sao_Paulo')
    ->onOneServer();

// Limpa backups muito velhos (mantém só das últimas semanas pra não lotar o disco)
Schedule::command('backup:clean')
    ->dailyAt('04:00')
    ->timezone('America/Sao_Paulo')
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Agendamento de Tarefas (Schedule)
|--------------------------------------------------------------------------
| Configuração dos cron jobs do sistema.
| * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|--------------------------------------------------------------------------
*/

// Backup diário completo (BD + arquivos) às 2h da manhã
Schedule::command('backup:run')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        logger()->info('Backup diário executado com sucesso');
    })
    ->onFailure(function () {
        logger()->error('Falha no backup diário');
    })
    ->description('Backup diário do banco e arquivos');

// Limpeza de backups antigos (manter últimos 7 dias)
Schedule::command('backup:clean')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Limpa backups antigos');

// Verificar integridade do sistema (diário às 6h)
if (class_exists(\App\Console\Commands\IronCheck::class)) {
    Schedule::command('iron:check')
        ->dailyAt('06:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->description('Verificação de integridade do sistema');
}

// 🎯 FASE 3: A MÁQUINA DE RETENÇÃO
// Roda toda manhã para buscar clientes de meses atrás e avisar tenants sobre manutenção
Schedule::command('cofre:retencao-preditiva --meses=6')
    ->dailyAt('09:30')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->description('A Máquina de Retenção (Manutenção Preditiva) - Pro/Elite');

// Confirmação Automática de Agendamentos (Phase 3.2)
Schedule::command('agendas:enviar-confirmacoes')
    ->dailyAt('10:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->description('A Máquina de Vendas - Confirmação Automática');

/*
|--------------------------------------------------------------------------
| 💰 Fase 1 — Billing Engine
|--------------------------------------------------------------------------
*/

// 🚫 "Já pagou o aluguel, Seu Madruga?"
// Bloqueia tenants inadimplentes (carência 5 dias) e expira trials automaticamente.
Schedule::job(new SuspenderTenantInadimplenteJob)
    ->dailyAt('08:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Suspender tenants inadimplentes e expirar trials');

// 💀 O KILLSWITCH: Verifica e bloqueia caloteiros todo dia de madrugada
Schedule::command('iron:lock-caloteiros')->dailyAt('01:00')->timezone('America/Sao_Paulo');
