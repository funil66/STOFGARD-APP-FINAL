<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Agendamento de Tarefas (Schedule)
|--------------------------------------------------------------------------
| Configuração dos cron jobs do sistema.
| Para ativar, adicione ao crontab do servidor:
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
