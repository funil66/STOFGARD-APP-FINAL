<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfiguracaoResource\Pages;
use App\Models\Configuracao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\ConfiguracaoResource\RelationManagers\TabelaPrecosRelationManager;

use Filament\Forms\Get;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class ConfiguracaoResource extends Resource
{
    protected static ?string $model = Configuracao::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configurações';

    protected static ?string $modelLabel = 'Configuração';

    protected static ?string $pluralModelLabel = 'Configurações';

    protected static ?string $navigationGroup = 'Administração';

    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Configurações')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('🏢 Identidade Visual')
                            ->schema([
                                Forms\Components\FileUpload::make('empresa_logo')
                                    ->label('Logo da Empresa')
                                    ->image()
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory('logos')
                                    ->visibility('public')
                                    ->helperText('Upload da logo que aparecerá no cabeçalho do PDF'),

                                Forms\Components\TextInput::make('empresa_nome')->required(),
                                Forms\Components\TextInput::make('empresa_cnpj')->mask('99.999.999/9999-99'),
                                Forms\Components\ColorPicker::make('cores_pdf.primaria')
                                    ->label('Cor Principal do PDF'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('💰 Motor Financeiro')
                            ->schema([
                                Forms\Components\TextInput::make('desconto_pix')
                                    ->label('Desconto Pix (%)')->numeric(),
                                Forms\Components\KeyValue::make('taxas_parcelamento')
                                    ->label('Taxas da Maquininha (Coeficientes)')
                                    ->keyLabel('Parcelas (ex: 2)')
                                    ->valueLabel('Coeficiente (ex: 1.0459)')
                                    ->helperText('Defina os multiplicadores para 2x até 6x.'),
                                Forms\Components\KeyValue::make('formas_pagamento_personalizado')
                                    ->label('Gerenciar Formas de Pagamento Aceitas')
                                    ->keyLabel('Slug (ex: crypto)')
                                    ->valueLabel('Nome (ex: Criptomoeda)'),
                            ]),

                        Forms\Components\Tabs\Tab::make('🔄 Workflow & Status')
                            ->schema([
                                Forms\Components\KeyValue::make('status_orcamento_personalizado')
                                    ->label('Personalizar Status do Orçamento')
                                    ->keyLabel('Slug (ex: aguardando_peca)')
                                    ->valueLabel('Nome (ex: Aguardando Peça)'),
                            ]),

                        Forms\Components\Tabs\Tab::make('📄 Textos Legais')
                            ->schema([
                                Forms\Components\RichEditor::make('pdf_header')->label('Cabeçalho'),
                                Forms\Components\RichEditor::make('termos_garantia')->label('Termos de Garantia'),
                            ]),
                    ])->columnSpanFull()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TabelaPrecosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'edit' => Pages\EditConfiguracao::route('/{record}/edit'),
        ];
    }

    // Restrição de acesso: apenas admin (`is_admin`) ou usuário principal `allisson@stofgard.com.br`
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return ($user->is_admin === true) || ($user->email === 'allisson@stofgard.com.br');
    }
}
