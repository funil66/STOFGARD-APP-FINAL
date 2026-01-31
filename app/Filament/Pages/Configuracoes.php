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
    public function mount(): void
    {
        // Carrega configurações do banco
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // chaves que são Arrays/Repeaters e precisam ser decodificadas do JSON
        $jsonFields = ['catalogo_servicos_v2', 'financeiro_pix_keys', 'financeiro_taxas_cartao', 'financeiro_parcelamento'];
        foreach ($jsonFields as $key) {
            if (isset($settings[$key]) && is_string($settings[$key])) {
                $decoded = json_decode($settings[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $settings[$key] = $decoded;
                }
            }
        }

        // AUTO-SEED: Se a lista estiver vazia, injeta o catálogo massivo
        if (empty($settings['catalogo_servicos_v2'])) {
            $settings['catalogo_servicos_v2'] = $this->getCatalogoMassivo();
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
                                            ->required()
                                            ->columnSpan(1),
                                        TextInput::make('empresa_nome')
                                            ->label('Nome Fantasia')
                                            ->required()
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

                        // 2. DASHBOARD
                        Tabs\Tab::make('Dashboard')
                            ->icon('heroicon-m-home')
                            ->schema([
                                Section::make('Personalização do Dashboard')
                                    ->description('Customize a aparência da tela inicial')
                                    ->schema([
                                        TextInput::make('dashboard_frase')
                                            ->label('Frase Central')
                                            ->placeholder('Ex: Bem-vindo ao Sistema')
                                            ->helperText('Aparece no banner do dashboard')
                                            ->columnSpanFull(),
                                        Toggle::make('dashboard_mostrar_clima')
                                            ->label('Mostrar Widget de Clima')
                                            ->default(true),
                                        TextInput::make('url_clima')
                                            ->label('URL do Widget de Clima')
                                            ->placeholder('https://wttr.in/SuaCidade?0&Q&T&lang=pt')
                                            ->helperText('Use wttr.in ou weatherwidget.io')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // 3. CATÁLOGO INTELIGENTE
                        Tabs\Tab::make('Catálogo de Itens')
                            ->icon('heroicon-m-tag')
                            ->schema([
                                Section::make('Base de Precificação')
                                    ->description('Defina os valores base. No orçamento, o sistema calculará automaticamente.')
                                    ->schema([
                                        Repeater::make('catalogo_servicos_v2')
                                            ->label('Itens Cadastrados')
                                            ->schema([
                                                TextInput::make('nome')
                                                    ->label('Item')
                                                    ->required()
                                                    ->columnSpan(3),
                                                Select::make('unidade')
                                                    ->options(['un' => 'Unidade', 'm2' => 'm²', 'ml' => 'Metro Linear'])
                                                    ->default('un')
                                                    ->required(),
                                                TextInput::make('preco_higi')
                                                    ->label('R$ Higienização')
                                                    ->numeric()->prefix('R$')->default(0),
                                                TextInput::make('preco_imper')
                                                    ->label('R$ Impermeab.')
                                                    ->numeric()->prefix('R$')->default(0),
                                            ])
                                            ->columns(6)
                                            ->cloneable()
                                            ->collapsible()
                                            ->collapsed(true)
                                            ->itemLabel(fn(array $state): ?string => $state['nome'] ?? null),
                                    ]),
                            ]),

                        // 4. SISTEMA
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

                        // 5. FINANCEIRO
                        Tabs\Tab::make('Financeiro')
                            ->icon('heroicon-m-banknotes')
                            ->schema([
                                Section::make('Chaves PIX')
                                    ->schema([
                                        Repeater::make('financeiro_pix_keys')
                                            ->label('Chaves Disponíveis')
                                            ->schema([
                                                TextInput::make('chave')->label('Chave PIX')->required(),
                                                TextInput::make('titular')->label('Titular'),
                                            ])->columns(2),
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('limpar_cache')
                ->label('Resetar Cache')
                ->color('danger')
                ->icon('heroicon-m-arrow-path')
                ->action(function () {
                    Artisan::call('view:clear');
                    Artisan::call('filament:optimize-clear');
                    settings()->clearCache();
                    Notification::make()->title('Sistema Limpo!')->success()->send();
                })->requiresConfirmation(),
        ];
    }
    // --- LISTA MASSIVA (CORRIGIDA E INCLUSA) ---
    protected function getCatalogoMassivo(): array
    {
        return [
            // CADEIRAS
            ['nome' => 'Cadeira de Jantar (Somente Assento)', 'unidade' => 'un', 'preco_higi' => 35.00, 'preco_imper' => 55.00],
            ['nome' => 'Cadeira de Jantar (Assento + Encosto)', 'unidade' => 'un', 'preco_higi' => 50.00, 'preco_imper' => 65.00],
            ['nome' => 'Cadeira de Jantar (Tecido Completo)', 'unidade' => 'un', 'preco_higi' => 60.00, 'preco_imper' => 85.00],
            ['nome' => 'Cadeira de Escritório (Simples)', 'unidade' => 'un', 'preco_higi' => 45.00, 'preco_imper' => 0.00],
            ['nome' => 'Cadeira de Escritório (Presidente)', 'unidade' => 'un', 'preco_higi' => 65.00, 'preco_imper' => 0.00],
            ['nome' => 'Cadeira Gamer', 'unidade' => 'un', 'preco_higi' => 80.00, 'preco_imper' => 0.00],
            ['nome' => 'Cadeira Boneca / Decorativa', 'unidade' => 'un', 'preco_higi' => 70.00, 'preco_imper' => 110.00],
            ['nome' => 'Banqueta Alta (Com Encosto)', 'unidade' => 'un', 'preco_higi' => 40.00, 'preco_imper' => 55.00],

            // POLTRONAS
            ['nome' => 'Poltrona Fixa Pequena', 'unidade' => 'un', 'preco_higi' => 120.00, 'preco_imper' => 150.00],
            ['nome' => 'Poltrona do Papai (Reclinável)', 'unidade' => 'un', 'preco_higi' => 200.00, 'preco_imper' => 250.00],
            ['nome' => 'Poltrona Berger (Clássica)', 'unidade' => 'un', 'preco_higi' => 220.00, 'preco_imper' => 280.00],
            ['nome' => 'Poltrona Egg / Swan', 'unidade' => 'un', 'preco_higi' => 150.00, 'preco_imper' => 180.00],
            ['nome' => 'Puff Pequeno', 'unidade' => 'un', 'preco_higi' => 40.00, 'preco_imper' => 60.00],
            ['nome' => 'Puff Baú / Grande', 'unidade' => 'un', 'preco_higi' => 60.00, 'preco_imper' => 90.00],
            // SOFÁS (FIXOS)
            ['nome' => 'Sofá 2 Lugares (Fixo)', 'unidade' => 'un', 'preco_higi' => 160.00, 'preco_imper' => 350.00],
            ['nome' => 'Sofá 3 Lugares (Fixo)', 'unidade' => 'un', 'preco_higi' => 200.00, 'preco_imper' => 450.00],
            ['nome' => 'Sofá 4 Lugares (Fixo)', 'unidade' => 'un', 'preco_higi' => 250.00, 'preco_imper' => 550.00],
            // SOFÁS (RETRÁTEIS/RECLINÁVEIS)
            ['nome' => 'Sofá 2 Lugares (Retrátil)', 'unidade' => 'un', 'preco_higi' => 220.00, 'preco_imper' => 380.00],
            ['nome' => 'Sofá 3 Lugares (Retrátil)', 'unidade' => 'un', 'preco_higi' => 280.00, 'preco_imper' => 515.00],
            ['nome' => 'Sofá 4 Lugares (Retrátil)', 'unidade' => 'un', 'preco_higi' => 350.00, 'preco_imper' => 630.00],
            ['nome' => 'Sofá de Canto (5 Lugares)', 'unidade' => 'un', 'preco_higi' => 350.00, 'preco_imper' => 650.00],
            ['nome' => 'Sofá de Canto (6 Lugares)', 'unidade' => 'un', 'preco_higi' => 400.00, 'preco_imper' => 750.00],
            // SOFÁS POR MEDIDA (ALTA PRECISÃO)
            ['nome' => 'Sofá Retrátil (Até 2,00m)', 'unidade' => 'un', 'preco_higi' => 220.00, 'preco_imper' => 350.00],
            ['nome' => 'Sofá Retrátil (2,10m a 2,40m)', 'unidade' => 'un', 'preco_higi' => 260.00, 'preco_imper' => 450.00],
            ['nome' => 'Sofá Retrátil (2,50m a 2,90m)', 'unidade' => 'un', 'preco_higi' => 320.00, 'preco_imper' => 670.00],
            ['nome' => 'Sofá Retrátil (Acima de 3,00m)', 'unidade' => 'un', 'preco_higi' => 380.00, 'preco_imper' => 710.00],
            ['nome' => 'Sofá Living (Design - Por Metro Linear)', 'unidade' => 'ml', 'preco_higi' => 90.00, 'preco_imper' => 150.00],
            // COLCHÕES
            ['nome' => 'Colchão Berço', 'unidade' => 'un', 'preco_higi' => 100.00, 'preco_imper' => 150.00],
            ['nome' => 'Colchão Solteiro', 'unidade' => 'un', 'preco_higi' => 180.00, 'preco_imper' => 340.00],
            ['nome' => 'Colchão Casal Padrão', 'unidade' => 'un', 'preco_higi' => 240.00, 'preco_imper' => 465.00],
            ['nome' => 'Colchão Queen Size', 'unidade' => 'un', 'preco_higi' => 280.00, 'preco_imper' => 550.00],
            ['nome' => 'Colchão King Size', 'unidade' => 'un', 'preco_higi' => 350.00, 'preco_imper' => 650.00],
            ['nome' => 'Cama Box (Base) Solteiro', 'unidade' => 'un', 'preco_higi' => 60.00, 'preco_imper' => 0.00],
            ['nome' => 'Cama Box (Base) Casal', 'unidade' => 'un', 'preco_higi' => 80.00, 'preco_imper' => 0.00],
            ['nome' => 'Cabeceira Cama (Solteiro)', 'unidade' => 'un', 'preco_higi' => 80.00, 'preco_imper' => 150.00],
            ['nome' => 'Cabeceira Cama (Casal)', 'unidade' => 'un', 'preco_higi' => 120.00, 'preco_imper' => 250.00],
            // AUTOMOTIVO
            ['nome' => 'Carro Hatch (Bancos)', 'unidade' => 'un', 'preco_higi' => 180.00, 'preco_imper' => 0.00],
            ['nome' => 'Carro Hatch (Completa: Teto+Carpete)', 'unidade' => 'un', 'preco_higi' => 350.00, 'preco_imper' => 0.00],
            ['nome' => 'Carro Sedan (Bancos)', 'unidade' => 'un', 'preco_higi' => 200.00, 'preco_imper' => 0.00],
            ['nome' => 'Carro Sedan (Completa)', 'unidade' => 'un', 'preco_higi' => 400.00, 'preco_imper' => 0.00],
            ['nome' => 'SUV / Caminhonete (Bancos)', 'unidade' => 'un', 'preco_higi' => 250.00, 'preco_imper' => 0.00],
            ['nome' => 'SUV / Caminhonete (Completa)', 'unidade' => 'un', 'preco_higi' => 480.00, 'preco_imper' => 0.00],
            ['nome' => 'Teto Veicular (Avulso)', 'unidade' => 'un', 'preco_higi' => 100.00, 'preco_imper' => 0.00],
            ['nome' => 'Caminhão (Cabine Simples)', 'unidade' => 'un', 'preco_higi' => 300.00, 'preco_imper' => 0.00],
            ['nome' => 'Caminhão (Cabine Dupla)', 'unidade' => 'un', 'preco_higi' => 450.00, 'preco_imper' => 0.00],
            // BEBÊ E CRIANÇA
            ['nome' => 'Bebê Conforto', 'unidade' => 'un', 'preco_higi' => 80.00, 'preco_imper' => 0.00],
            ['nome' => 'Carrinho de Bebê (Simples)', 'unidade' => 'un', 'preco_higi' => 100.00, 'preco_imper' => 0.00],
            ['nome' => 'Carrinho de Bebê (Com Moises)', 'unidade' => 'un', 'preco_higi' => 150.00, 'preco_imper' => 0.00],
            ['nome' => 'Urso de Pelúcia P', 'unidade' => 'un', 'preco_higi' => 20.00, 'preco_imper' => 0.00],
            ['nome' => 'Urso de Pelúcia M', 'unidade' => 'un', 'preco_higi' => 40.00, 'preco_imper' => 0.00],
            ['nome' => 'Urso de Pelúcia G', 'unidade' => 'un', 'preco_higi' => 60.00, 'preco_imper' => 0.00],
            // TAPETES E CORTINAS (M2)
            ['nome' => 'Tapete Pelo Curto/Sintético', 'unidade' => 'm2', 'preco_higi' => 25.00, 'preco_imper' => 0.00],
            ['nome' => 'Tapete Pelo Alto/Shaggy', 'unidade' => 'm2', 'preco_higi' => 35.00, 'preco_imper' => 0.00],
            ['nome' => 'Tapete Importado/Lã/Sisal', 'unidade' => 'm2', 'preco_higi' => 45.00, 'preco_imper' => 0.00],
            ['nome' => 'Cortina Tecido Leve (Voil)', 'unidade' => 'm2', 'preco_higi' => 25.00, 'preco_imper' => 49.00],
            ['nome' => 'Cortina Tecido Pesado (Linho/Blackout)', 'unidade' => 'm2', 'preco_higi' => 35.00, 'preco_imper' => 60.00],
            ['nome' => 'Persianas', 'unidade' => 'm2', 'preco_higi' => 45.00, 'preco_imper' => 0.00],
        ];
    }
}

