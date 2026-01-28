<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CadastroResource\Pages;
use App\Models\Cadastro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Http;

class CadastroResource extends Resource
{
    protected static ?string $model = Cadastro::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $label = 'Cadastro';
    protected static ?string $pluralLabel = 'Cadastros';

    // --- INFOLIST (VISUALIZAÇÃO PREMIUM) ---
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Identificação')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('nome')->weight('bold')->columnSpan(2),
                        TextEntry::make('tipo')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'cliente' => 'info',
                                'loja' => 'success',
                                'vendedor' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('documento')->label('CPF / CNPJ')->icon('heroicon-m-identification'),
                    ]),
                Section::make('Contato')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('telefone')->label('WhatsApp')->icon('heroicon-m-chat-bubble-left-right'),
                        TextEntry::make('email')->label('E-mail')->icon('heroicon-m-envelope'),
                        TextEntry::make('telefone_fixo')->label('Fixo'),
                    ]),
                Section::make('Endereço')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('logradouro')->label('Rua')->columnSpan(2),
                        TextEntry::make('numero')->label('Nº'),
                        TextEntry::make('bairro'),
                        TextEntry::make('cidade'),
                        TextEntry::make('estado')->label('UF'),
                    ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema(self::getFormSchema());
    }

    // Reutilizável
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Classificação & Vínculos')
                ->schema([
                    Forms\Components\Select::make('tipo')
                        ->options([
                            'cliente' => 'Cliente Final',
                            'loja' => 'Loja (Parceiro)',
                            'vendedor' => 'Vendedor (Parceiro)',
                            'arquiteto' => 'Arquiteto',
                        ])
                        ->required()
                        ->live(),
                    Forms\Components\Select::make('parent_id')
                        ->label('Loja Vinculada')
                        ->relationship('loja', 'nome', fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('tipo', 'loja'))
                        ->visible(fn (Forms\Get $get) => $get('tipo') === 'vendedor')
                        ->searchable(),
                    // CAMPO DE COMISSÃO
                    Forms\Components\TextInput::make('comissao_percentual')
                        ->label('Comissão Padrão (%)')
                        ->numeric()
                        ->suffix('%')
                        ->default(0)
                        ->visible(fn (Forms\Get $get) => in_array($get('tipo'), ['vendedor', 'loja', 'arquiteto']))
                        ->helperText('Porcentagem que será aplicada automaticamente nos orçamentos.'),
                ])->columns(3),
            Forms\Components\Section::make('Dados Principais')
                ->schema([
                    Forms\Components\TextInput::make('nome')->required()->columnSpan(2),
                    Forms\Components\TextInput::make('documento')->label('CPF / CNPJ')
                        ->mask(\Filament\Support\RawJs::make(<<<'JS'
                            $input.length > 14 ? '99.999.999/9999-99' : '999.999.999-99'
                        JS)),
                    Forms\Components\TextInput::make('rg_ie')->label('RG / Inscrição'),
                ])->columns(4),
            Forms\Components\Section::make('Contato & Endereço')
                ->schema([
                    Forms\Components\TextInput::make('email')->email()->columnSpan(2),
                    Forms\Components\TextInput::make('telefone')->mask('(99) 99999-9999')->required(),
                    Forms\Components\TextInput::make('cep')->mask('99999-999')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (strlen(preg_replace('/[^0-9]/', '', $state)) === 8) {
                                $data = \Illuminate\Support\Facades\Http::get("https://viacep.com.br/ws/{$state}/json/")->json();
                                if (!isset($data['erro'])) {
                                    $set('logradouro', $data['logradouro']);
                                    $set('bairro', $data['bairro']);
                                    $set('cidade', $data['localidade']);
                                    $set('estado', $data['uf']);
                                }
                            }
                        }),
                    Forms\Components\TextInput::make('logradouro')->required(),
                    Forms\Components\TextInput::make('numero')->required(),
                    Forms\Components\TextInput::make('bairro')->required(),
                    Forms\Components\TextInput::make('cidade')->required(),
                    Forms\Components\TextInput::make('estado')->maxLength(2)->required(),
                    Forms\Components\TextInput::make('complemento'),
                ])->columns(4),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cliente' => 'info',
                        'loja' => 'success',
                        'vendedor' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('telefone')->label('WhatsApp')->icon('heroicon-m-phone'),
                Tables\Columns\TextColumn::make('cidade')->label('Cidade'),
            ])
            ->actions([
                // 1. PDF (Verde Destaque)
                Tables\Actions\Action::make('pdf')
                    ->label('Ficha')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->button()
                    ->url(fn (Cadastro $record) => route('cadastro.pdf', $record))
                    ->openUrlInNewTab(),
                // 2. VISUALIZAR (Olho)
                Tables\Actions\ViewAction::make()->label('')->tooltip('Ver Detalhes'),
                // 3. EDITAR (Lápis)
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                // 4. EXCLUIR (Lixeira)
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCadastros::route('/'),
            'create' => Pages\CreateCadastro::route('/create'),
            'edit' => Pages\EditCadastro::route('/{record}/edit'),
        ];
    }
}
