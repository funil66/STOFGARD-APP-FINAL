<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CadastroViewResource\Pages;
use App\Models\CadastroView;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class CadastroViewResource extends Resource
{
    protected static ?string $model = CadastroView::class;

    // Use concise label and group icon per product request
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Cadastro';

    protected static ?string $navigationGroup = 'Gestão';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Use loose/bool casting to handle DB-stored integers like 1 for `is_admin`
        return ((bool) $user->is_admin === true) || ($user->email === 'allisson@stofgard.com.br');
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) \DB::table('cadastros_view')->count();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Primary identifying column (name)
                TextColumn::make('nome')->searchable()->weight('bold')->limit(40),

                // Show loja (when applicable) near the name as requested
                TextColumn::make('loja')
                    ->label('Loja')
                    ->getStateUsing(fn (\App\Models\CadastroView $record) => ($record->model === 'parceiro' && ($model = $record->underlying_model) && ($model->tipo === 'vendedor')) ? ($model->loja?->nome ?? null) : null)
                    ->toggleable(),

                // Contact
                TextColumn::make('telefone')->label('Telefone')->searchable()->toggleable(),

                // Arquivos with download links (HTML)
                TextColumn::make('arquivos')
                    ->label('Arquivos')
                    ->html()
                    ->formatStateUsing(function ($state, $record) {
                        $model = $record->underlying_model;

                        if (! $model || empty($model->arquivos)) {
                            return '';
                        }

                        $entries = [];

                        foreach ($model->arquivos as $path) {
                            $name = basename($path);

                            $downloadUrl = route('admin.files.download', [
                                'model' => base64_encode(get_class($model)),
                                'record' => $record->model_id,
                                'path' => base64_encode($path),
                            ]);

                            $deleteUrl = \Illuminate\Support\Facades\URL::signedRoute('admin.files.delete', [
                                'model' => base64_encode(get_class($model)),
                                'record' => $record->model_id,
                                'path' => base64_encode($path),
                            ], now()->addHour());

                            $entries[] = "<div class='inline-flex items-center gap-2'><a href='{$downloadUrl}' target='_blank' class='text-xs text-blue-600 underline'>{$name}</a> <a href='{$downloadUrl}?download=1' class='text-xs ml-2' title='Baixar'>⤓</a> <a href='{$deleteUrl}' class='text-xs text-red-600 ml-2' onclick=\"return confirm('Excluir arquivo?')\">✖</a></div>";
                        }

                        return implode('', $entries);
                    }),

                TextColumn::make('cidade')->label('Cidade')->toggleable(),
                TextColumn::make('created_at')->label('Cadastrado em')->dateTime('d/m/Y H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')->options([
                    'cliente' => 'Cliente',
                    'loja' => 'Loja',
                    'vendedor' => 'Vendedor',
                ])->label('Tipo'),
            ])
            ->emptyStateHeading('Nenhum cadastro encontrado')
            ->emptyStateDescription('Cadastre um novo cliente, loja ou vendedor para começar.')
            ->emptyStateActions([
                Tables\Actions\Action::make('novo_cadastro')
                    ->label('Novo Cadastro')
                    ->url(\App\Filament\Resources\CadastroResource::getUrl('create')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('Ver')
                        ->icon('heroicon-o-eye')
                        ->url(fn (CadastroView $record) => (
                            $record->model === 'cliente'
                                ? route('cadastros.show', ['uuid' => \App\Models\Cliente::find($record->model_id)?->uuid ?? $record->model_id])
                                : route('cadastros.show', ['uuid' => \App\Models\Parceiro::find($record->model_id)?->uuid ?? $record->model_id])
                        ))
                        ->openUrlInNewTab(false),

                    Tables\Actions\Action::make('edit')
                        ->label('Editar')
                        ->icon('heroicon-o-pencil')
                        ->url(fn (CadastroView $record) => (
                            $record->model === 'cliente'
                                ? \App\Filament\Resources\ClienteResource::getUrl('edit', ['record' => $record->model_id])
                                : \App\Filament\Resources\ParceiroResource::getUrl('edit', ['record' => $record->model_id])
                        )),

                    Tables\Actions\Action::make('restore')
                        ->label('Restaurar')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->visible(function (CadastroView $record) {
                            if ($record->model === 'cliente') {
                                $m = \App\Models\Cliente::withTrashed()->find($record->model_id);
                            } else {
                                $m = \App\Models\Parceiro::withTrashed()->find($record->model_id);
                            }
                            return $m && method_exists($m, 'trashed') && $m->trashed();
                        })
                        ->requiresConfirmation()
                        ->color('success')
                        ->action(function (CadastroView $record) {
                            if ($record->model === 'cliente') {
                                $m = \App\Models\Cliente::withTrashed()->find($record->model_id);
                            } else {
                                $m = \App\Models\Parceiro::withTrashed()->find($record->model_id);
                            }
                            if ($m && method_exists($m, 'restore')) {
                                $m->restore();
                            }
                        }),

                    Tables\Actions\Action::make('delete')
                        ->label('Excluir')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->action(function (CadastroView $record) {
                            if ($record->model === 'cliente') {
                                $model = \App\Models\Cliente::find($record->model_id);
                            } else {
                                $model = \App\Models\Parceiro::find($record->model_id);
                            }
                            if ($model) {
                                $model->delete();
                            }
                        }),

                    \App\Filament\Actions\DownloadFileAction::make('arquivos')->label('Download'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('restore_selected')
                    ->label('Restaurar selecionados')
                    ->requiresConfirmation()
                    ->color('success')
                    ->action(function (\Illuminate\Support\Collection $records) {
                        foreach ($records as $record) {
                            if ($record->model === 'cliente') {
                                $m = \App\Models\Cliente::withTrashed()->find($record->model_id);
                            } else {
                                $m = \App\Models\Parceiro::withTrashed()->find($record->model_id);
                            }
                            if ($m && method_exists($m, 'restore')) {
                                $m->restore();
                            }
                        }
                    }),

                Tables\Actions\BulkAction::make('delete_selected')
                    ->label('Excluir selecionados')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (\Illuminate\Support\Collection $records) {
                        foreach ($records as $record) {
                            if ($record->model === 'cliente') {
                                $m = \App\Models\Cliente::find($record->model_id);
                            } else {
                                $m = \App\Models\Parceiro::find($record->model_id);
                            }
                            if ($m) {
                                $m->delete();
                            }
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCadastros::route('/'),
        ];
    }
}
