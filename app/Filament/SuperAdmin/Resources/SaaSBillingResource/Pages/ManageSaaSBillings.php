<?php

namespace App\Filament\SuperAdmin\Resources\SaaSBillingResource\Pages;

use App\Filament\SuperAdmin\Resources\SaaSBillingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSaaSBillings extends ManageRecords
{
    protected static string $resource = SaaSBillingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
