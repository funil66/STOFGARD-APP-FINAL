<?php

namespace App\Filament\SuperAdmin\Resources\GlobalAnnouncementResource\Pages;

use App\Filament\SuperAdmin\Resources\GlobalAnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageGlobalAnnouncements extends ManageRecords
{
    protected static string $resource = GlobalAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
