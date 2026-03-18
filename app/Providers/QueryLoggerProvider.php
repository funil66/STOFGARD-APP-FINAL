<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryLoggerProvider extends ServiceProvider {
    public function boot() {
        DB::listen(function ($query) {
            if (str_starts_with($query->sql, 'select * from "users" where "email"')) {
                Log::info('LOGIN_QUERY:', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'connection' => $query->connectionName,
                    'db' => DB::connection()->getDatabaseName()
                ]);
            }
        });
    }
}
