<?php

namespace App\Filament\SuperAdmin\Resources\UserImpersonationResource\Pages;

use App\Filament\SuperAdmin\Resources\UserImpersonationResource;
use Filament\Resources\Pages\EditRecord;

class EditUserImpersonation extends EditRecord
{
    protected static string $resource = UserImpersonationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
