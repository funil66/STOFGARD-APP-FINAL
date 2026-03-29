<?php

namespace App\Filament\SuperAdmin\Resources\UserImpersonationResource\Pages;

use App\Filament\SuperAdmin\Resources\UserImpersonationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserImpersonation extends ListRecords
{
    protected static string $resource = UserImpersonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Usuário')
                ->icon('heroicon-o-user-plus'),
        ];
    }
}
