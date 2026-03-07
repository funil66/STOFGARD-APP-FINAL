<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Tenant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Number;

/**
 * Widget: Guardião do Disco
 *
 * Mostra o uso de storage de cada tenant no dashboard do Super Admin.
 * Varre storage/tenant{id}/ e calcula o tamanho total.
 */
class TenantStorageWidget extends BaseWidget
{
    protected static ?string $heading = '💾 Guardião do Disco — Storage por Tenant';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(Tenant::query()->orderBy('name'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan')
                    ->label('Plano')
                    ->badge()
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'elite' => 'success',
                        'pro' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('storage_usage')
                    ->label('Uso de Disco')
                    ->getStateUsing(function (Tenant $record): string {
                        $path = storage_path("tenant{$record->id}");

                        if (!File::isDirectory($path)) {
                            return '0 B';
                        }

                        $size = $this->getDirectorySize($path);
                        return Number::fileSize($size, precision: 2);
                    })
                    ->badge()
                    ->color(function (Tenant $record): string {
                        $path = storage_path("tenant{$record->id}");

                        if (!File::isDirectory($path)) {
                            return 'gray';
                        }

                        $sizeBytes = $this->getDirectorySize($path);
                        $sizeGB = $sizeBytes / (1024 * 1024 * 1024);

                        if ($sizeGB >= 5)
                            return 'danger';
                        if ($sizeGB >= 2)
                            return 'warning';
                        return 'success';
                    })
                    ->sortable(query: function ($query, string $direction) {
                        // Sort não nativo — mantém a query original
                        return $query;
                    }),
                Tables\Columns\TextColumn::make('file_count')
                    ->label('Arquivos')
                    ->getStateUsing(function (Tenant $record): string {
                        $path = storage_path("tenant{$record->id}");

                        if (!File::isDirectory($path)) {
                            return '0';
                        }

                        return number_format(count(File::allFiles($path)));
                    }),
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => $state ? 'Ativo' : 'Inativo')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),
            ])
            ->defaultSort('name')
            ->paginated([5, 10, 25]);
    }

    /**
     * Calcula o tamanho total de um diretório recursivamente.
     */
    protected function getDirectorySize(string $path): int
    {
        $size = 0;

        try {
            $files = File::allFiles($path);
            foreach ($files as $file) {
                $size += $file->getSize();
            }
        } catch (\Throwable $e) {
            // Ignora erros de permissão
        }

        return $size;
    }
}
