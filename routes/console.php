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

// 🪂 BACKUP: Full backup (DB + arquivos) — madrugada às 02:00
Schedule::command('backup:run')
    ->dailyAt('02:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer()
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
    ->dailyAt('04:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer()
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

// 🤖 BOT DE VENDAS: Dispara as confirmações de agenda para o dia seguinte todos os dias às 18:00
Schedule::command('iron:agenda-confirmations')
    ->dailyAt('18:00')
    ->timezone('America/Sao_Paulo');

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

// 🔄 CONTRATOS RECORRENTES: Processa contratos de serviço recorrente toda manhã
Schedule::job(new \App\Jobs\ProcessarContratosRecorrentes)
    ->dailyAt('07:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Processar contratos de serviço recorrente');

// ⏰ SLA ALERTS: Verifica OS próximas do vencimento SLA
Schedule::command('os:verificar-sla')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Verificar ordens de serviço com SLA próximo do vencimento');
