<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfiguracaoResource\Pages;
use App\Filament\Resources\ConfiguracaoResource\RelationManagers\TabelaPrecosRelationManager;
use App\Models\Configuracao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

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

                        Forms\Components\Tabs\Tab::make('🛡️ Garantias')
                            ->schema([
                                Forms\Components\Repeater::make('config_prazo_garantia')
                                    ->label('Prazos de Garantia por Tipo de Serviço')
                                    ->schema([
                                        Forms\Components\Select::make('tipo_servico')
                                            ->label('Tipo de Serviço')
                                            ->options(\App\Services\ServiceTypeManager::getOptions())
                                            ->required()
                                            ->searchable()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('nome')
                                                    ->label('Nome do Serviço')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('icone')
                                                    ->label('Ícone (Emoji)')
                                                    ->placeholder('🧹')
                                                    ->maxLength(255),
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                $categoria = \App\Models\Categoria::create([
                                                    'nome' => $data['nome'],
                                                    'tipo' => 'servico_tipo',
                                                    'slug' => \Illuminate\Support\Str::slug($data['nome']),
                                                    'icone' => $data['icone'] ?? '🛠️',
                                                    'ativo' => true,
                                                ]);
                                                return $categoria->slug;
                                            })
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('dias')
                                            ->label('Prazo')
                                            ->numeric()
                                            ->suffix('dias')
                                            ->required()
                                            ->default(90)
                                            ->minValue(1)
                                            ->maxValue(3650)
                                            ->columnSpan(1),

                                        Forms\Components\Textarea::make('descricao')
                                            ->label('Descrição da Garantia')
                                            ->rows(2)
                                            ->placeholder('Ex: Garantia contra manchas e odores')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->addActionLabel('Adicionar Tipo de Serviço')
                                    ->collapsible()
                                    ->helperText('Configure o prazo de garantia para cada tipo de serviço. Garantias são geradas automaticamente ao concluir uma OS.'),
                            ]),

                        Forms\Components\Tabs\Tab::make('📄 Textos Legais')
                            ->schema([
                                Forms\Components\RichEditor::make('pdf_header')->label('Cabeçalho (PDF)'),
                                Forms\Components\RichEditor::make('termos_garantia')->label('Termos de Garantia (Geral)'),
                                Forms\Components\RichEditor::make('texto_contrato_padrao')
                                    ->label('Modelo de Contrato de Serviço (Avançado)')
                                    ->helperText('Contrato gerado automaticamente nos Orçamentos e OS aprovadas. Variáveis suportadas: {cliente_nome}, {cliente_doc}, {valor_total}, {itens}.'),
                            ]),

                        Forms\Components\Tabs\Tab::make('💳 Gateway de Pagamento')
                            ->schema([
                                Forms\Components\Section::make('Provedor de Pagamento')
                                    ->description('Configure para enviar PIX/Boleto automático para clientes ao aprovar orçamentos.')
                                    ->schema([
                                        Forms\Components\Select::make('gateway_provider')
                                            ->label('Provedor')
                                            ->options([
                                                'asaas' => '🏦 Asaas (Recomendado)',
                                                'efipay' => '🏦 EFI / Gerencianet',
                                                'mercadopago' => '🏦 Mercado Pago',
                                            ])
                                            ->placeholder('Não configurado (PIX manual)')
                                            ->helperText('Selecione o banco/fintech onde você tem conta como autônomo/empresa.')
                                            ->live(),

                                        Forms\Components\TextInput::make('gateway_token_encrypted')
                                            ->label('API Key / Token de Acesso')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Cole aqui o token da sua conta no provedor escolhido. Ele é armazenado de forma segura (criptografado).')
                                            ->dehydrateStateUsing(fn($state) => filled($state) ? encrypt($state) : null)
                                            ->afterStateHydrated(function ($component, $state) {
                                                try {
                                                    $component->state(filled($state) ? decrypt($state) : '');
                                                } catch (\Exception) {
                                                    $component->state('');
                                                }
                                            })
                                            ->visible(fn($get) => filled($get('gateway_provider'))),

                                        Forms\Components\Placeholder::make('gateway_webhook_token_display')
                                            ->label('Seu Link de Webhook PIX')
                                            ->content(function ($record) {
                                                if (!$record || !$record->gateway_webhook_token) {
                                                    return 'Salve as configurações para gerar o link de webhook.';
                                                }
                                                $url = url("/api/webhooks/pix/{$record->gateway_webhook_token}");
                                                return "Configure no painel do seu banco: {$url}";
                                            })
                                            ->helperText('Cole esta URL no campo de webhook do seu provedor de pagamento para receber confirmações automáticas.')
                                            ->visible(fn($get) => filled($get('gateway_provider'))),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('PIX Manual (Fallback)')
                                    ->description('Se não usar gateway, cliente recebe apenas a chave PIX para pagar manualmente.')
                                    ->schema([
                                        Forms\Components\Select::make('pix_tipo_chave')
                                            ->label('Tipo da Chave PIX')
                                            ->options([
                                                'cpf' => 'CPF',
                                                'cnpj' => 'CNPJ',
                                                'email' => 'E-mail',
                                                'telefone' => 'Telefone',
                                                'aleatoria' => 'Chave Aleatória (EVP)',
                                            ]),

                                        Forms\Components\TextInput::make('pix_chave')
                                            ->label('Chave PIX')
                                            ->placeholder('Ex: joao@exemplo.com ou 123.456.789-00')
                                            ->helperText('Esta chave é enviada por WhatsApp quando não há gateway configurado.'),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('📣 Marketing')
                            ->schema([
                                Forms\Components\Section::make('⭐ Máquina de Avaliações Google')
                                    ->description('24h após uma OS ser concluída e paga, o cliente recebe um WhatsApp pedindo avaliação no Google.')
                                    ->schema([
                                        Forms\Components\Toggle::make('habilitar_avaliacao_automatica')
                                            ->label('Habilitar envio automático de solicitação de avaliação')
                                            ->helperText('Liga/desliga o envio automático. Quando ligado, funciona 24h por dia sem intervenção.')
                                            ->columnSpanFull()
                                            ->live(),

                                        Forms\Components\TextInput::make('gmb_link')
                                            ->label('Link do Google Meu Negócio (Avaliação Direta)')
                                            ->placeholder('https://g.page/r/..../review')
                                            ->url()
                                            ->helperText('Para obter: Google Maps → sua empresa → Compartilhar → Escrever uma avaliação → copiar link.')
                                            ->columnSpanFull()
                                            ->visible(fn($get) => $get('habilitar_avaliacao_automatica')),

                                        Forms\Components\Textarea::make('mensagem_avaliacao')
                                            ->label('Mensagem Personalizada (WhatsApp)')
                                            ->rows(6)
                                            ->placeholder("Olá, {nome_cliente}! 😊\n\nO serviço de *{nome_empresa}* atendeu suas expectativas?\n\nDeixe sua avaliação ⭐⭐⭐⭐⭐:\n{link_gmb}")
                                            ->helperText('Variáveis disponíveis: {nome_cliente}, {nome_empresa}, {link_gmb}, {numero_os}')
                                            ->columnSpanFull()
                                            ->visible(fn($get) => $get('habilitar_avaliacao_automatica')),
                                    ])
                                    ->columns(1),
                            ]),
                    ])->columnSpanFull(),
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

    /**
     * Restrição de acesso: apenas administradores
     */
    public static function canAccess(): bool
    {
        return settings()->isAdmin(auth()->user());
    }
}
