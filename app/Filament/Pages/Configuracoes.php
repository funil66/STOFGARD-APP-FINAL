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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
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
        $settings = [];
        foreach (Setting::all() as $s) {
            $val = $s->value;
            if (is_string($val) && (str_starts_with($val, '{') || str_starts_with($val, '['))) {
                $decoded = json_decode($val, true);
                $settings[$s->key] = $decoded !== null ? $decoded : $val;
            } else {
                $settings[$s->key] = $val;
            }
        }

        // Auto-seed default services if not present
        if (empty($settings['tabela_precos_padrao'])) {
            $settings['tabela_precos_padrao'] = $this->getServicosPadrao();
        }

        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Configurações Globais')
                    ->tabs([
                        // 1. INSTITUCIONAL (Ícone Home Seguro)
                        Tabs\Tab::make('Identidade')
                            ->icon('heroicon-m-home')
                            ->schema([
                                Section::make('Dados da Empresa')
                                    ->schema([
                                        TextInput::make('empresa_nome')->label('Razão Social')->required(),
                                        TextInput::make('empresa_cnpj')->label('CNPJ/CPF')->mask('99.999.999/9999-99'),
                                        FileUpload::make('empresa_logo')
                                            ->label('Logo do Sistema')
                                            ->image()
                                            ->directory('logos')
                                            ->preserveFilenames()
                                            ->imageEditor()
                                            ->columnSpanFull(),
                                        ColorPicker::make('cor_primaria')->label('Cor do Sistema')->default('#2563EB'),
                                    ])->columns(2),
                                Section::make('Contato')
                                    ->schema([
                                        TextInput::make('empresa_telefone')->label('WhatsApp')->mask('(99) 99999-9999'),
                                        TextInput::make('empresa_email')->label('E-mail'),
                                        Textarea::make('empresa_endereco')->label('Endereço')->rows(2)->columnSpanFull(),
                                    ])->columns(2),
                            ]),
                        // 2. CATÁLOGO DE SERVIÇOS (NOVO)
                        Tabs\Tab::make('Catálogo de Serviços')
                            ->icon('heroicon-m-tag')
                            ->schema([
                                Section::make('Tabela de Preços')
                                    ->description('Gerencie todos os seus serviços e preços aqui. Eles aparecerão nos Orçamentos.')
                                    ->schema([
                                        Repeater::make('tabela_precos_padrao')
                                            ->label('Lista de Serviços')
                                            ->schema([
                                                TextInput::make('nome')->label('Nome do Serviço')->required()->columnSpan(3),
                                                Select::make('categoria')
                                                    ->options([
                                                        'Higienização' => 'Higienização',
                                                        'Impermeabilização' => 'Impermeabilização',
                                                        'Automotivo' => 'Automotivo',
                                                        'Tapetes' => 'Tapetes',
                                                    ])->required(),
                                                TextInput::make('preco')
                                                    ->label('Preço Venda (R$)')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->required(),
                                            ])
                                            ->columns(5)
                                            ->cloneable()
                                            ->collapsible()
                                            ->collapsed(true)
                                            ->itemLabel(fn (array $state): ?string => $state['nome'] ?? null),
                                    ]),
                            ]),
                        // 3. FINANCEIRO (Ícone Banknotes Seguro)
                        Tabs\Tab::make('Financeiro')
                            ->icon('heroicon-m-banknotes')
                            ->schema([
                                Section::make('PIX')
                                    ->schema([
                                        Repeater::make('financeiro_pix_keys')
                                            ->label('Chaves PIX')
                                            ->schema([
                                                Select::make('banco')->options(['Inter'=>'Inter', 'Nubank'=>'Nubank', 'Itaú'=>'Itaú'])->required(),
                                                TextInput::make('chave')->required(),
                                            ])->columns(2)->grid(2),
                                    ]),
                                Section::make('Cartão')
                                    ->schema([
                                        Repeater::make('financeiro_taxas_cartao')
                                            ->label('Taxas')
                                            ->schema([
                                                TextInput::make('descricao')->label('Condição')->required(),
                                                TextInput::make('taxa')->label('%')->numeric()->required(),
                                            ])->columns(2)->grid(2),
                                    ]),
                            ]),
                        // 3. REGRAS (Ícone Cog Seguro)
                        Tabs\Tab::make('Regras')
                            ->icon('heroicon-m-cog-6-tooth')
                            ->schema([
                                TextInput::make('orcamento_validade')->label('Validade (Dias)')->numeric()->default(15),
                                TextInput::make('pedido_minimo')->label('Pedido Mínimo')->numeric()->prefix('R$'),
                            ]),
                        // 4. SISTEMA (Ícone Server Seguro)
                        Tabs\Tab::make('Sistema')
                            ->icon('heroicon-m-server')
                            ->schema([
                                Section::make('Manutenção')
                                    ->schema([
                                        Toggle::make('sistema_debug')->label('Modo Debug'),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            if (is_array($value)) {
                Setting::set($key, $value, 'geral', 'json');
            } else {
                Setting::set($key, $value);
            }
        }

        Notification::make()->title('Configurações e Tabela de Preços Salvas!')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('limpar_cache')
                ->label('Limpar Cache e Otimizar')
                ->color('danger')
                ->icon('heroicon-m-arrow-path')
                ->action(function() {
                    Artisan::call('optimize:clear');
                    Artisan::call('view:clear');
                    Notification::make()->title('Sistema Otimizado!')->success()->send();
                })->requiresConfirmation(),
        ];
    }

    // --- O SEED MANUAL PROTEGIDO - LISTA PADRÃO ---
    protected function getServicosPadrao(): array
    {
        return [
            ['nome' => 'Higienização Sofá Retrátil 2 Lugares', 'categoria' => 'Higienização', 'preco' => '180.00'],
            ['nome' => 'Higienização Sofá Retrátil 3 Lugares', 'categoria' => 'Higienização', 'preco' => '220.00'],
            ['nome' => 'Higienização Sofá Retrátil 4 Lugares', 'categoria' => 'Higienização', 'preco' => '280.00'],
            ['nome' => 'Higienização Sofá de Canto (5 Lugares)', 'categoria' => 'Higienização', 'preco' => '300.00'],
            ['nome' => 'Higienização Sofá de Canto (6 Lugares)', 'categoria' => 'Higienização', 'preco' => '350.00'],
            ['nome' => 'Higienização Poltrona Simples', 'categoria' => 'Higienização', 'preco' => '90.00'],
            ['nome' => 'Higienização Poltrona do Papai', 'categoria' => 'Higienização', 'preco' => '120.00'],
            ['nome' => 'Higienização Cadeira de Jantar (Assento)', 'categoria' => 'Higienização', 'preco' => '35.00'],
            ['nome' => 'Higienização Cadeira de Jantar (Completa)', 'categoria' => 'Higienização', 'preco' => '50.00'],
            ['nome' => 'Higienização Puff', 'categoria' => 'Higienização', 'preco' => '40.00'],
            ['nome' => 'Impermeabilização Sofá 2 Lugares', 'categoria' => 'Impermeabilização', 'preco' => '350.00'],
            ['nome' => 'Impermeabilização Sofá 3 Lugares', 'categoria' => 'Impermeabilização', 'preco' => '450.00'],
            ['nome' => 'Impermeabilização Sofá 4 Lugares', 'categoria' => 'Impermeabilização', 'preco' => '550.00'],
            ['nome' => 'Impermeabilização Cadeira', 'categoria' => 'Impermeabilização', 'preco' => '60.00'],
            ['nome' => 'Higienização Colchão Solteiro', 'categoria' => 'Higienização', 'preco' => '120.00'],
            ['nome' => 'Higienização Colchão Casal', 'categoria' => 'Higienização', 'preco' => '160.00'],
            ['nome' => 'Higienização Colchão Queen', 'categoria' => 'Higienização', 'preco' => '200.00'],
            ['nome' => 'Higienização Colchão King', 'categoria' => 'Higienização', 'preco' => '250.00'],
            ['nome' => 'Higienização Interna Carro P', 'categoria' => 'Automotivo', 'preco' => '250.00'],
            ['nome' => 'Higienização Interna SUV', 'categoria' => 'Automotivo', 'preco' => '350.00'],
            ['nome' => 'Lavagem Tapete Pelo Curto (m2)', 'categoria' => 'Tapetes', 'preco' => '25.00'],
            ['nome' => 'Lavagem Tapete Pelo Longo (m2)', 'categoria' => 'Tapetes', 'preco' => '35.00'],
        ];
    }
}

