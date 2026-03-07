<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Models\TicketSuporte;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class TicketSuporteResource extends Resource
{
    protected static ?string $model = TicketSuporte::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Tickets de Suporte';
    protected static ?string $navigationGroup = 'Suporte';
    protected static ?string $label = 'Ticket';
    protected static ?string $pluralLabel = 'Tickets de Suporte';
    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return (string) TicketSuporte::abertos()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return TicketSuporte::abertos()->count() > 0 ? 'danger' : 'gray';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Ticket')
                    ->schema([
                        Forms\Components\TextInput::make('assunto')
                            ->label('Assunto')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('prioridade')
                            ->label('Prioridade')
                            ->options([
                                'baixa' => '🟢 Baixa',
                                'media' => '🟡 Média',
                                'alta' => '🔴 Alta',
                            ])
                            ->default('media')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'aberto' => '📬 Aberto',
                                'em_andamento' => '🔧 Em Andamento',
                                'resolvido' => '✅ Resolvido',
                                'fechado' => '🔒 Fechado',
                            ])
                            ->default('aberto')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Resposta do Admin')
                    ->schema([
                        Forms\Components\Textarea::make('resposta_admin')
                            ->label('Resposta')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable(),

                Tables\Columns\TextColumn::make('assunto')
                    ->label('Assunto')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('prioridade')
                    ->label('Prior.')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'baixa' => 'success',
                        'media' => 'warning',
                        'alta' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'baixa' => '🟢 Baixa',
                        'media' => '🟡 Média',
                        'alta' => '🔴 Alta',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'aberto' => 'danger',
                        'em_andamento' => 'warning',
                        'resolvido' => 'success',
                        'fechado' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aberto em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('respondido_em')
                    ->label('Respondido')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Aguardando')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'aberto' => 'Aberto',
                        'em_andamento' => 'Em Andamento',
                        'resolvido' => 'Resolvido',
                        'fechado' => 'Fechado',
                    ]),

                Tables\Filters\SelectFilter::make('prioridade')
                    ->label('Prioridade')
                    ->options([
                        'baixa' => 'Baixa',
                        'media' => 'Média',
                        'alta' => 'Alta',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('responder')
                    ->label('Responder')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('primary')
                    ->visible(fn(TicketSuporte $record) => $record->isAberto())
                    ->form([
                        Forms\Components\Textarea::make('resposta')
                            ->label('Resposta do Super Admin')
                            ->required()
                            ->rows(5),

                        Forms\Components\Select::make('novo_status')
                            ->label('Marcar como')
                            ->options([
                                'em_andamento' => '🔧 Em Andamento',
                                'resolvido' => '✅ Resolvido',
                            ])
                            ->default('resolvido')
                            ->required(),
                    ])
                    ->action(function (TicketSuporte $record, array $data) {
                        $record->update([
                            'resposta_admin' => $data['resposta'],
                            'status' => $data['novo_status'],
                            'respondido_em' => now(),
                        ]);

                        Notification::make()
                            ->title('Ticket respondido!')
                            ->body("Ticket #{$record->id} atualizado para {$data['novo_status']}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('fechar')
                    ->label('Fechar')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn(TicketSuporte $record) => $record->status === 'resolvido')
                    ->action(fn(TicketSuporte $record) => $record->update(['status' => 'fechado'])),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\SuperAdmin\Resources\TicketSuporteResource\Pages\ListTicketsSuporte::route('/'),
        ];
    }
}
