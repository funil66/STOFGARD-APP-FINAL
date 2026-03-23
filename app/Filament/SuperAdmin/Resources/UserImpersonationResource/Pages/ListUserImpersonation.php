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
            Actions\Action::make('criar_usuario_tenant_page')
                ->label('Criar usuário do tenant')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->url(fn (): string => route('super-admin.tenant-users.create')),
        ];
    }
}
