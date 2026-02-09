<?php
namespace App\Filament\Pages;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;

class Configuracoes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.configuracoes';
    protected static ?string $title = 'Central de Comando Stofgard';
    protected static ?string $slug = 'configuracoes';
    public ?array $data = [];

    /**
     * Restrição de acesso: apenas administradores podem acessar esta página.
     */
    public static function canAccess(): bool
    {
        return settings()->isAdmin(auth()->user());
    }

    public function mount(): void
    {
        // Carrega configurações do banco
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // chaves que são Arrays/Repeaters e precisam ser decodificadas do JSON
        $jsonFields = ['financeiro_pix_keys', 'financeiro_taxas_cartao', 'financeiro_parcelamento', 'system_service_types', 'admin_emails', 'pdf_layout', 'backup_tables'];
        foreach ($jsonFields as $key) {
            if (isset($settings[$key]) && is_string($settings[$key])) {
                $decoded = json_decode($settings[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $settings[$key] = $decoded;
                }
            }
        }

        // AUTO-SEED: Layout PDF Padrão
        if (empty($settings['pdf_layout'])) {
            $settings['pdf_layout'] = $this->getLayoutPadrao();
        }

        // AUTO-SEED: Tipos de Serviço (Carga inicial baseada no Enum)
        if (empty($settings['system_service_types'])) {
            $settings['system_service_types'] = \App\Services\ServiceTypeManager::getAll()->values()->toArray();
        }

        // Ensure default backup tables
        if (empty($settings['backup_tables'])) {
            $settings['backup_tables'] = ['users', 'cadastros', 'financeiros', 'orcamentos', 'ordem_servicos', 'estoques'];
        }

        $this->form->fill($settings);
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Configurações Globais')
                    ->tabs([
                        // 1. IDENTIDADE
                        Tabs\Tab::make('Identidade')
                            ->icon('heroicon-m-finger-print')
                            ->schema([
                                Section::make('Marca')
                                    ->description('Defina a identidade visual do sistema')
                                    ->schema([
                                        TextInput::make('nome_sistema')
                                            ->label('Nome do Sistema')
                                            ->placeholder('Ex: Minha Empresa')
                                            ->helperText('Aparece no header e PDFs')
                                            ->columnSpan(1),
                                        TextInput::make('empresa_nome')
                                            ->label('Nome Fantasia')
                                            ->columnSpan(1),
                                        FileUpload::make('empresa_logo')
                                            ->label('Logo Principal')
                                            ->directory('logos')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->image()
                                            ->imageEditor()
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                                            ->maxSize(2048)
                                            ->helperText('Formato recomendado: PNG transparente, máx 2MB')
                                            ->columnSpanFull(),
                                        TextInput::make('empresa_cnpj')
                                            ->label('CNPJ/CPF')
                                            ->mask('99.999.999/9999-99'),
                                    ])->columns(2),
                                Section::make('Contato')
                                    ->schema([
                                        TextInput::make('empresa_telefone')
                                            ->label('Telefone')
                                            ->mask('(99) 99999-9999'),
                                        TextInput::make('empresa_email')
                                            ->label('E-mail')
                                            ->email(),
                                        Textarea::make('empresa_endereco')
                                            ->label('Endereço Completo')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])->columns(2),
                            ]),

                        // 2. FINANCEIRO & PRÓ-LABORE
                        Tabs\Tab::make('Financeiro & Sócios')
                            ->icon('heroicon-m-banknotes')
                            ->schema([
                                Section::make('Pró-Labore e Sócios')
                                    ->description('Configurações de distribuição de lucros')
                                    ->schema([
                                        TextInput::make('prolabore_dia_pagamento')
                                            ->label('Dia do Pagamento')
                                            ->numeric()
                                            ->default(5)
                                            ->minValue(1)
                                            ->maxValue(31)
                                            ->suffix('de cada mês'),

                                        TextInput::make('prolabore_percentual_reserva')
                                            ->label('Percentual de Reserva (Caixa)')
                                            ->numeric()
                                            ->default(20)
                                            ->suffix('%')
                                            ->helperText('Quanto do lucro líquido fica na empresa antes da distribuição'),
                                    ])->columns(2),
                            ]),

                        // 3. DASHBOARD
                        Tabs\Tab::make('Dashboard')
                            ->icon('heroicon-m-home')
                            ->schema([
                                Section::make('Banner Superior')
                                    ->description('Customize a faixa azul do dashboard - Layout: Esquerda (Saudação) | Centro (Frase) | Direita (Clima)')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('dashboard_saudacao')
                                            ->label('Frase de Boas-Vindas (Esquerda)')
                                            ->placeholder('Ex: Tenha um dia de trabalho produtivo.')
                                            ->helperText('Aparece alinhado à esquerda no banner')
                                            ->default('Tenha um dia de trabalho produtivo.')
                                            ->columnSpan(1),
                                        TextInput::make('dashboard_frase')
                                            ->label('Frase Motivacional (Centro)')
                                            ->placeholder('Ex: DEUS SEJA LOUVADO')
                                            ->helperText('Aparece centralizado no banner (estilo destaque)')
                                            ->default('BORA TRABALHAR!')
                                            ->columnSpan(1),
                                        ColorPicker::make('dashboard_banner_color_start')
                                            ->label('Cor Inicial do Gradiente')
                                            ->default('#1e40af')
                                            ->helperText('Cor da esquerda do gradiente'),
                                        ColorPicker::make('dashboard_banner_color_end')
                                            ->label('Cor Final do Gradiente')
                                            ->default('#60a5fa')
                                            ->helperText('Cor da direita do gradiente'),
                                    ]),
                                Section::make('Widget de Clima')
                                    ->description('Configure o widget de clima exibido à direita do banner')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('dashboard_mostrar_clima')
                                            ->label('Mostrar Widget de Clima')
                                            ->default(true)
                                            ->columnSpan(1),
                                        TextInput::make('dashboard_weather_city')
                                            ->label('Cidade para Previsão do Tempo')
                                            ->placeholder('São Paulo')
                                            ->helperText('Digite o nome da cidade (ex: São Paulo, Rio de Janeiro, London)')
                                            ->default('São Paulo')
                                            ->required()
                                            ->columnSpan(1),
                                    ]),
                                Section::make('Layout dos Ícones')
                                    ->description('Configure o grid de atalhos (8 ícones em formato 4x2 recomendado)')
                                    ->columns(3)
                                    ->schema([
                                        Select::make('dashboard_grid_colunas')
                                            ->label('Colunas no Desktop')
                                            ->options([
                                                '2' => '2 Colunas (4 linhas)',
                                                '3' => '3 Colunas (3 linhas)',
                                                '4' => '4 Colunas (2 linhas) ⭐',
                                                '5' => '5 Colunas (2 linhas)',
                                                '8' => '8 Colunas (1 linha)',
                                            ])
                                            ->default('4')
                                            ->helperText('Layout recomendado: 4 colunas = grid 4x2'),
                                        Select::make('dashboard_grid_colunas_mobile')
                                            ->label('Colunas no Mobile')
                                            ->options([
                                                '1' => '1 Coluna (lista vertical)',
                                                '2' => '2 Colunas (4 linhas) ⭐',
                                                '3' => '3 Colunas (3 linhas)',
                                                '4' => '4 Colunas (2 linhas)',
                                            ])
                                            ->default('2')
                                            ->helperText('Mobile recomendado: 2 colunas'),
                                        TextInput::make('dashboard_grid_gap')
                                            ->label('Espaçamento entre Ícones')
                                            ->placeholder('2rem')
                                            ->default('2rem')
                                            ->helperText('Ex: 1rem, 2rem, 24px'),
                                    ]),
                            ]),

                        // 4. SERVIÇOS E ITENS (UNIFICADO)
                        Tabs\Tab::make('Serviços e Itens')
                            ->icon('heroicon-m-squares-plus')
                            ->schema([
                                Section::make('Tipos de Serviço')
                                    ->description('Personalize os nomes, cores e descrições dos serviços. Os identificadores (slugs) são fixos para manter a lógica do sistema.')
                                    ->collapsible()
                                    ->schema([
                                        Repeater::make('system_service_types')
                                            ->label('Serviços Disponíveis')
                                            ->schema([
                                                TextInput::make('slug')
                                                    ->label('Identificador')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->required()
                                                    ->columnSpan(1),
                                                TextInput::make('label')
                                                    ->label('Nome Exibido')
                                                    ->required()
                                                    ->columnSpan(2),
                                                Select::make('color')
                                                    ->label('Cor')
                                                    ->options([
                                                        'primary' => 'Primary',
                                                        'secondary' => 'Secondary',
                                                        'success' => 'Success',
                                                        'warning' => 'Warning',
                                                        'danger' => 'Danger',
                                                        'gray' => 'Gray',
                                                        'info' => 'Info',
                                                    ])
                                                    ->required()
                                                    ->columnSpan(1),
                                                TextInput::make('icon')
                                                    ->label('Ícone (Heroicon)')
                                                    ->placeholder('heroicon-o-sparkles')
                                                    ->columnSpan(2),
                                                Textarea::make('descricao_pdf')
                                                    ->label('Descrição para PDF')
                                                    ->placeholder('Ex: Limpeza profunda com extração e sanitização...')
                                                    ->helperText('Texto que aparecerá no PDF junto ao nome do serviço')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(6)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(true)
                                            ->collapsible()
                                            ->itemLabel(fn(array $state): ?string => $state['label'] ?? null),
                                    ]),

                                Section::make('Gerenciamento de Itens/Produtos')
                                    ->description('Para gerenciar preços e itens, utilize a Tabela de Preços avançada. Os dados foram migrados do catálogo JSON.')
                                    ->collapsible()
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('link_tabela_precos')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">' .
                                                '<div class="flex items-center space-x-3">' .
                                                '<svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">' .
                                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2m-6 9l2 2 4-4"></path>' .
                                                '</svg>' .
                                                '<div>' .
                                                '<h3 class="font-semibold text-blue-900">Tabela de Preços Unificada</h3>' .
                                                '<p class="text-sm text-blue-700">Gerencie todos os itens, preços e categorias em um local único.</p>' .
                                                '<a href="/admin/configuracoes/tabela-precos" class="inline-flex items-center mt-2 text-sm font-medium text-blue-600 hover:text-blue-800">' .
                                                'Acessar Tabela de Preços →' .
                                                '</a>' .
                                                '</div>' .
                                                '</div>' .
                                                '</div>'
                                            )),
                                    ]),
                            ]),

                        // 5. SISTEMA
                        Tabs\Tab::make('Sistema')
                            ->icon('heroicon-m-cog')
                            ->schema([
                                Section::make('Configurações Gerais')
                                    ->schema([
                                        Toggle::make('sistema_debug')
                                            ->label('Modo Debug'),
                                        TextInput::make('sistema_timezone')
                                            ->label('Timezone')
                                            ->placeholder('America/Sao_Paulo')
                                            ->default('America/Sao_Paulo'),
                                    ]),
                                Section::make('Administradores')
                                    ->description('Emails com acesso total ao sistema (além de is_admin)')
                                    ->schema([
                                        Repeater::make('admin_emails')
                                            ->label('Emails de Administradores')
                                            ->simple(
                                                TextInput::make('email')
                                                    ->email()
                                                    ->required()
                                            )
                                            ->defaultItems(0)
                                            ->addActionLabel('Adicionar Email'),
                                    ]),
                            ]),


                        // 6. CUSTOMIZAÇÃO PDF (BUILDER)
                        Tabs\Tab::make('Personalização de PDF')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Section::make('Identidade Visual (Global)')
                                    ->description('Cores aplicadas em todo o documento')
                                    ->columns(3)
                                    ->schema([
                                        ColorPicker::make('pdf_color_primary')
                                            ->label('Cor Primária')
                                            ->default('#2563eb'),
                                        ColorPicker::make('pdf_color_secondary')
                                            ->label('Cor Secundária')
                                            ->default('#eff6ff'),
                                        ColorPicker::make('pdf_color_text')
                                            ->label('Cor do Texto')
                                            ->default('#1f2937'),
                                        Toggle::make('pdf_mostrar_fotos_global')
                                            ->label('Habilitar Fotos nos PDFs (Global)')
                                            ->default(true)
                                            ->columnSpanFull(),
                                        Toggle::make('pdf_include_pix_global')
                                            ->label('Gerar QR Code PIX (Global)')
                                            ->default(true)
                                            ->columnSpanFull(),
                                        Toggle::make('pdf_aplicar_desconto_global')
                                            ->label('Aplicar Desconto à Vista (Global)')
                                            ->default(true)
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Construtor de Layout')
                                    ->description('Arraste os boxes para alterar a ordem dos elementos no PDF.')
                                    ->schema([
                                        Builder::make('pdf_layout')
                                            ->label('Estrutura do Documento')
                                            ->collapsible()
                                            ->cloneable()
                                            ->reorderableWithButtons()
                                            ->blocks([
                                                // 1. HEADER
                                                Block::make('header')
                                                    ->label('Cabeçalho (Logo)')
                                                    ->icon('heroicon-m-photo')
                                                    ->schema([
                                                        Toggle::make('show_logo')->label('Mostrar Logo')->default(true),
                                                        Toggle::make('show_dates')->label('Mostrar Datas Emissão/Validade')->default(true),
                                                        Select::make('alignment')
                                                            ->label('Alinhamento')
                                                            ->options(['left' => 'Logo à Esquerda', 'center' => 'Centralizado', 'right' => 'Logo à Direita'])
                                                            ->default('left'),
                                                    ]),

                                                // 2. DADOS DO CLIENTE
                                                Block::make('dados_cliente')
                                                    ->label('Dados do Cliente')
                                                    ->icon('heroicon-m-user')
                                                    ->schema([
                                                        TextInput::make('titulo')->label('Título da Seção')->default('DADOS DO CLIENTE'),
                                                        Toggle::make('show_email')->label('Mostrar Email')->default(true),
                                                        Toggle::make('show_phone')->label('Mostrar Telefone')->default(true),
                                                        Toggle::make('show_address')->label('Mostrar Endereço')->default(true),
                                                    ]),

                                                // 3. TABELA ITENS
                                                Block::make('tabela_itens')
                                                    ->label('Tabela de Itens')
                                                    ->icon('heroicon-m-table-cells')
                                                    ->schema([
                                                        TextInput::make('titulo')->label('Título da Seção')->default('ITENS DO ORÇAMENTO'),
                                                        Toggle::make('show_category_colors')->label('Colorir por Categoria (Higienização/Impermeabilização)')->default(true),
                                                    ]),

                                                // 4. CONTAINER DUPLO (PIX / VALORES)
                                                Block::make('container_duplo')
                                                    ->label('Container Duplo (2 Colunas)')
                                                    ->icon('heroicon-m-rectangle-group')
                                                    ->schema([
                                                        Select::make('coluna_esquerda')
                                                            ->label('Coluna Esquerda')
                                                            ->options([
                                                                'totais' => 'Resumo Total',
                                                                'pix' => 'QR Code PIX',
                                                                'texto_garantia' => 'Garantia/Avisos',
                                                                'vazio' => 'Vazio'
                                                            ])->default('totais'),
                                                        Select::make('coluna_direita')
                                                            ->label('Coluna Direita')
                                                            ->options([
                                                                'totais' => 'Resumo Total',
                                                                'pix' => 'QR Code PIX',
                                                                'texto_garantia' => 'Garantia/Avisos',
                                                                'vazio' => 'Vazio'
                                                            ])->default('pix'),
                                                    ])->columns(2),

                                                // 5. TEXTO LIVRE
                                                Block::make('texto_livre')
                                                    ->label('Texto Livre / Termos')
                                                    ->icon('heroicon-m-pencil-square')
                                                    ->schema([
                                                        RichEditor::make('conteudo')
                                                            ->label('Conteúdo')
                                                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link']),
                                                    ]),

                                                // 6. LINHA SEPARADORA
                                                Block::make('linha_separadora')
                                                    ->label('Linha Separadora')
                                                    ->icon('heroicon-m-minus')
                                                    ->schema([
                                                        ColorPicker::make('cor')->label('Cor da Linha')->default('#e5e7eb'),
                                                        Select::make('espessura')->options(['1px' => 'Fina', '2px' => 'Média', '4px' => 'Grossa'])->default('1px'),
                                                    ]),

                                                // 8. GALERIA DE FOTOS
                                                Block::make('galeria_fotos')
                                                    ->label('Galeria de Fotos do Orçamento')
                                                    ->icon('heroicon-m-camera')
                                                    ->schema([
                                                        TextInput::make('titulo')->label('Título da Seção')->default('REGISTROS FOTOGRÁFICOS'),
                                                        Select::make('columns')
                                                            ->label('Colunas por Linha')
                                                            ->options([
                                                                '1' => '1 Foto (Grande)',
                                                                '2' => '2 Fotos (Médio)',
                                                                '3' => '3 Fotos (Pequeno)',
                                                                '4' => '4 Fotos (Mini)'
                                                            ])->default('2'),
                                                        Toggle::make('show_legend')->label('Mostrar Legenda (Nome do Arquivo)')->default(false),
                                                    ]),

                                                // 9. RODAPÉ FIXO
                                                Block::make('rodape_padrao')
                                                    ->label('Rodapé Padrão (Fixo)')
                                                    ->icon('heroicon-m-arrow-down-tray')
                                                    ->schema([
                                                        Textarea::make('texto_legal')->label('Texto Legal Pequeno')->rows(2),
                                                    ]),
                                            ])
                                        // ->minItems(1) // Opcional
                                    ]),
                            ]),

                        // 7. DADOS & BACKUP [NEW TAB]
                        Tabs\Tab::make('Dados & Backup')
                            ->icon('heroicon-m-server-stack')
                            ->schema([
                                Section::make('Exportação de Dados')
                                    ->description('Selecione quais dados deseja incluir no arquivo de backup. O download será iniciado imediatamente.')
                                    ->schema([
                                        Select::make('backup_tables')
                                            ->label('Tabelas para Exportar')
                                            ->multiple()
                                            ->options([
                                                'users' => 'Usuários',
                                                'cadastros' => 'Cadastros (Clientes/Lojas/Vendedores)',
                                                'financeiros' => 'Financeiro',
                                                'orcamentos' => 'Orçamentos',
                                                'ordem_servicos' => 'Ordens de Serviço',
                                                'estoques' => 'Estoque',
                                                'agendas' => 'Agenda',
                                                'tabela_precos' => 'Tabela de Preços',
                                            ])
                                            ->default(['users', 'cadastros', 'financeiros', 'orcamentos', 'ordem_servicos', 'estoques'])
                                            ->required(),

                                        Toggle::make('backup_include_files')
                                            ->label('Incluir Arquivos (Storage)')
                                            ->helperText('Inclui fotos de OS e Orçamentos. Atenção: O arquivo pode ficar muito grande.')
                                            ->default(false),
                                    ]),
                            ]),

                        // 8. FINANCEIRO (Reordenado)
                        Tabs\Tab::make('Financeiro')
                            ->icon('heroicon-m-banknotes')
                            ->schema([
                                Section::make('Chaves PIX')
                                    ->schema([
                                        Repeater::make('financeiro_pix_keys')
                                            ->label('Chaves Disponíveis')
                                            ->schema([
                                                Select::make('tipo')
                                                    ->label('Tipo da Chave')
                                                    ->options([
                                                        'cpf' => 'CPF',
                                                        'cnpj' => 'CNPJ',
                                                        'telefone' => 'Telefone',
                                                        'email' => 'E-mail',
                                                        'aleatoria' => 'Aleatória (EVP)',
                                                    ])
                                                    ->required()
                                                    ->reactive()
                                                    ->afterStateUpdated(fn(callable $set) => $set('validada', false)),

                                                TextInput::make('chave')
                                                    ->label('Chave PIX')
                                                    ->required()
                                                    ->reactive()
                                                    ->afterStateUpdated(fn(callable $set) => $set('validada', false))
                                                    ->rules(function (callable $get) {
                                                        $tipo = $get('tipo');

                                                        switch ($tipo) {
                                                            case 'cpf':
                                                                return ['regex:/^[0-9]{11}$|^[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2}$/'];
                                                            case 'cnpj':
                                                                return ['regex:/^[0-9]{14}$|^[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}-[0-9]{2}$/'];
                                                            case 'telefone':
                                                                return ['regex:/^(\+55)?[1-9][0-9][9][0-9]{8}$|^(\+55)?[1-9][0-9][0-9]{8}$/'];
                                                            case 'email':
                                                                return ['email'];
                                                            case 'aleatoria':
                                                                return ['regex:/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'];
                                                            default:
                                                                return [];
                                                        }
                                                    }),

                                                TextInput::make('titular')
                                                    ->label('Titular')
                                                    ->required()
                                                    ->maxLength(25)
                                                    ->helperText('Máximo 25 caracteres (limitação PIX)'),

                                                TextInput::make('codigo_pais')
                                                    ->label('Código do País')
                                                    ->default('55')
                                                    ->visible(fn(callable $get) => $get('tipo') === 'telefone')
                                                    ->required(fn(callable $get) => $get('tipo') === 'telefone')
                                                    ->numeric()
                                                    ->helperText('Ex: 55 para Brasil'),

                                                Toggle::make('validada')
                                                    ->label('Chave Validada')
                                                    ->disabled()
                                                    ->helperText('Indica se a chave passou pela validação automática'),
                                            ])->columns(2)
                                            ->itemLabel(
                                                fn(array $state): ?string =>
                                                ($state['tipo'] ?? 'Novo') . ': ' . ($state['chave'] ?? 'Não definido')
                                            )
                                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                                if (is_array($state)) {
                                                    foreach ($state as $index => $chaveData) {
                                                        if (isset($chaveData['chave']) && isset($chaveData['tipo']) && !empty($chaveData['chave'])) {
                                                            $validacao = \App\Services\Pix\PixKeyValidatorService::validate(
                                                                $chaveData['chave'],
                                                                $chaveData['tipo'],
                                                                $chaveData['codigo_pais'] ?? '55'
                                                            );

                                                            $state[$index]['validada'] = $validacao['valida'];
                                                            $state[$index]['chave'] = $validacao['chave_formatada'];
                                                        }
                                                    }
                                                    $set('financeiro_pix_keys', $state);
                                                }
                                            }),
                                    ]),
                                Section::make('Regras de Pagamento')
                                    ->schema([
                                        TextInput::make('financeiro_desconto_avista')
                                            ->label('Desconto à Vista (PIX/Dinheiro)')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(10)
                                            ->required(),
                                        Repeater::make('financeiro_parcelamento')
                                            ->label('Taxas de Parcelamento (Cartão)')
                                            ->schema([
                                                TextInput::make('parcelas')
                                                    ->label('Parcelas (x)')
                                                    ->numeric()
                                                    ->required(),
                                                TextInput::make('taxa')
                                                    ->label('Juros Total (%)')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->default(0),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->reorderableWithButtons(),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ])->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            // Se for array, converte para JSON antes de salvar
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            Setting::set($key, $value);
        }

        // Limpar cache de configurações
        settings()->clearCache();

        Notification::make()
            ->title('Configurações salvas com sucesso!')
            ->success()
            ->send();

        // Redirecionar para dashboard após salvar
        $this->redirect(route('filament.admin.pages.dashboard'));
    }

    // --- EXPORT DATA LOGIC ---
    public function exportData()
    {
        $tables = $this->data['backup_tables'] ?? [];
        $includeFiles = $this->data['backup_include_files'] ?? false;

        if (empty($tables)) {
            Notification::make()->title('Selecione pelo menos uma tabela!')->warning()->send();
            return;
        }

        // Ensure settings are saved first? 
        // Optional: $this->save(); 

        $zipFileName = 'backup-' . now()->format('Y-m-d-His') . '.zip';
        // Ensure directory exists
        if (!is_dir(storage_path('app/public/backups'))) {
            mkdir(storage_path('app/public/backups'), 0755, true);
        }
        $zipPath = storage_path('app/public/backups/' . $zipFileName);

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {

            // Export Tables to JSON
            foreach ($tables as $table) {
                $modelClass = 'App\\Models\\' . \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($table));

                // Handle specific table mappings correctly
                if ($table === 'ordem_servicos')
                    $modelClass = \App\Models\OrdemServico::class;
                if ($table === 'users')
                    $modelClass = \App\Models\User::class;
                if ($table === 'cadastros')
                    $modelClass = \App\Models\Cadastro::class;
                if ($table === 'financeiros')
                    $modelClass = \App\Models\Financeiro::class;
                if ($table === 'orcamentos')
                    $modelClass = \App\Models\Orcamento::class;
                if ($table === 'estoques')
                    $modelClass = \App\Models\Estoque::class;
                if ($table === 'agendas')
                    $modelClass = \App\Models\Agenda::class;
                if ($table === 'tabela_precos')
                    $modelClass = \App\Models\TabelaPreco::class;

                if (class_exists($modelClass)) {
                    $data = $modelClass::all(); // Warning: Heavy load for large tables
                } else {
                    // Fallback 
                    if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                        $data = \Illuminate\Support\Facades\DB::table($table)->get();
                    } else {
                        continue;
                    }
                }
                $zip->addFromString("{$table}.json", $data->toJson(JSON_PRETTY_PRINT));
            }

            // Include Files
            if ($includeFiles) {
                $files = \Illuminate\Support\Facades\File::allFiles(storage_path('app/public'));
                foreach ($files as $file) {
                    $relativePath = $file->getRelativePathname();
                    // Exclude backups folder and the zip itself
                    if (strpos($relativePath, 'backups/') === 0 || $relativePath === $zipFileName) {
                        continue;
                    }
                    $zip->addFile($file->getRealPath(), 'storage/' . $relativePath);
                }
            }

            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportData')
                ->label('Baixar Backup')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn() => $this->exportData()),

            Action::make('limpar_cache')
                ->label('Resetar Cache')
                ->color('danger')
                ->icon('heroicon-m-arrow-path')
                ->action(function () {
                    Artisan::call('view:clear');
                    // Artisan::call('filament:optimize-clear'); // Causing crash on icons:clear
                    Artisan::call('route:clear');
                    Artisan::call('config:clear');
                    settings()->clearCache();
                    Notification::make()->title('Sistema Limpo!')->success()->send();
                })->requiresConfirmation(),
        ];
    }
    // --- LAYOUT PADRÃO (BUILDER) ---
    protected function getLayoutPadrao(): array
    {
        return [
            [
                'type' => 'header',
                'data' => [
                    'show_logo' => true,
                    'show_dates' => true,
                    'alignment' => 'left'
                ]
            ],
            [
                'type' => 'dados_cliente',
                'data' => [
                    'titulo' => 'DADOS DO CLIENTE',
                    'show_email' => true,
                    'show_phone' => true,
                    'show_address' => true
                ]
            ],
            [
                'type' => 'tabela_itens',
                'data' => [
                    'titulo' => 'ITENS DO ORÇAMENTO',
                    'show_category_colors' => true
                ]
            ],
            [
                'type' => 'container_duplo',
                'data' => [
                    'coluna_esquerda' => 'totais',
                    'coluna_direita' => 'pix'
                ]
            ],
            [
                'type' => 'texto_livre',
                'data' => [
                    'conteudo' => '<ul><li>Orçamento válido por 7 dias.</li><li>Pagamento 50% na aprovação e 50% na entrega.</li></ul>'
                ]
            ],
            [
                'type' => 'galeria_fotos',
                'data' => [
                    'titulo' => 'REGISTROS FOTOGRÁFICOS',
                    'columns' => '2',
                    'show_legend' => false
                ]
            ],
            [
                'type' => 'rodape_padrao',
                'data' => [
                    'texto_legal' => 'Este documento não é fiscal.'
                ]
            ]
        ];
    }


}
