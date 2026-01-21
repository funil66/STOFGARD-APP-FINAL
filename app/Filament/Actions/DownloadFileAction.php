<?php

namespace App\Filament\Actions;

use Filament\Tables;

class DownloadFileAction
{
    /**
     * Create a standard Download action for a file attribute.
     * Usage in a Resource's actions():
     *  ->actions([
     *      DownloadFileAction::make('arquivos', 'public')->label('Download')->icon('heroicon-m-arrow-down'),
     *  ])
     */
    public static function make(string $attribute, string $disk = 'public') : Tables\Actions\Action
    {
        return Tables\Actions\Action::make('download')
            ->label('Download')
            ->icon('heroicon-m-arrow-down')
            ->url(function ($record) use ($attribute, $disk) {
                $value = data_get($record, $attribute);

                // if array, take first
                if (is_array($value)) {
                    $value = $value[0] ?? null;
                }

                if (! $value) {
                    return null;
                }

                return route('file.download', ['disk' => $disk, 'encodedPath' => base64_encode((string) $value)]);
            })
            ->openUrlInNewTab()
            ->visible(fn ($record) => (bool) (is_array(data_get($record, $attribute)) ? (data_get($record, $attribute)[0] ?? null) : data_get($record, $attribute)));
    }
}
