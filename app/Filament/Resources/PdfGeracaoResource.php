<?php

namespace App\Filament\Resources;

use App\Models\PdfGeneration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PdfGeracaoResource extends Resource
{
    protected static ?string $model = PdfGeneration::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?string $navigationLabel = 'PDFs Gerados';

    protected static ?string $modelLabel = 'PDF';

    protected static ?string $pluralModelLabel = 'PDFs Gerados';

    protected static ?string $navigationGroup = 'Gestão & Configurações';

    protected static ?int $navigationSort = 90;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('10s')
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'orcamento' => 'primary',
                        'contrato'  => 'info',
                        'os'        => 'warning',
                        'garantia'  => 'success',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'orcamento' => '📄 Orçamento',
                        'contrato'  => '⚖️ Contrato',
                        'os'        => '🔧 OS',
                        'garantia'  => '🛡️ Garantia',
                        default     => ucfirst($state ?? 'N/A'),
                    }),

                Tables\Columns\TextColumn::make('modelo_id')
                    ->label('Ref. ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'done'       => 'success',
                        'processing' => 'warning',
                        'failed'     => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'done'       => '✅ Pronto',
                        'processing' => '⏳ Processando',
                        'failed'     => '❌ Falhou',
                        default      => $state ?? 'N/A',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Solicitado por')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Erro')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'done'       => 'Pronto',
                        'processing' => 'Processando',
                        'failed'     => 'Falhou',
                    ]),

                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'orcamento' => 'Orçamento',
                        'contrato'  => 'Contrato',
                        'os'        => 'Ordem de Serviço',
                        'garantia'  => 'Garantia',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Baixar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(PdfGeneration $record) => $record->url ?? '#', shouldOpenInNewTab: true)
                    ->visible(fn(PdfGeneration $record) => $record->status === 'done' && $record->url),

                Tables\Actions\Action::make('retry')
                    ->label('Retentar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn(PdfGeneration $record) => $record->status === 'failed')
                    ->action(function (PdfGeneration $record) {
                        $record->update(['status' => 'processing', 'error_message' => null]);

                        \Filament\Notifications\Notification::make()
                            ->title('Documento reenfileirado para geração.')
                            ->info()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()->label('Remover'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Nenhum PDF gerado')
            ->emptyStateDescription('Os PDFs gerados via botão "Gerar PDF" aparecerão aqui com status e link de download.')
            ->emptyStateIcon('heroicon-o-document');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\PdfGeracaoResource\Pages\ListPdfGeracoes::route('/'),
        ];
    }
}
