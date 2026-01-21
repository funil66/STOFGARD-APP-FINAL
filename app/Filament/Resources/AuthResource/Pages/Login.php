<?php

namespace App\Filament\Resources\AuthResource\Pages;

use App\Filament\Resources\AuthResource;
use Filament\Resources\Pages\Page;

class Login extends Page
{
    protected static string $resource = AuthResource::class;

    protected static string $view = 'filament.resources.auth-resource.pages.login';
}
