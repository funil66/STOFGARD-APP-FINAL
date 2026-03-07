<?php

namespace App\Filament\SuperAdmin\Resources\TicketSuporteResource\Pages;

use App\Filament\SuperAdmin\Resources\TicketSuporteResource;
use Filament\Resources\Pages\ListRecords;

class ListTicketsSuporte extends ListRecords
{
    protected static string $resource = TicketSuporteResource::class;

    protected static ?string $title = 'Tickets de Suporte';
}
