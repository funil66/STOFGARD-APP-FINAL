<?php
$content = file_get_contents('routes/web.php');
$content = str_replace(
'\\\\Livewire\Livewire::setUpdateRoute(function ($handle) {
    $handle->middleware([\Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class]);                                                                             return Route::post(\'/livewire/update\', $handle);
});',
'\Livewire\Livewire::setUpdateRoute(function ($handle) {
    $handle->middleware([\Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class]);
    return Route::post(\'/livewire/update\', $handle);
});', $content);
file_put_contents('routes/web.php', $content);
