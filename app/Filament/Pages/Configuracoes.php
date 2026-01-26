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
use App\Models\Setting;

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

        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Configurações Globais')
                    ->tabs([
                        // 1. INSTITUCIONAL (Identidade Visual)
                        Tabs\Tab::make('Identidade & Marca')
                            ->icon('heroicon-m-identification')
                            ->schema([
                                Section::make('Branding')
                                    ->description('Define a cara do sistema e dos PDFs.')
                                    ->schema([
                                        FileUpload::make('empresa_logo')
                                            ->label('Logo Oficial (Alta Resolução)')
                                            ->directory('logos')
                                            ->image()
                                            ->imageEditor()
                                            ->columnSpanFull(),
                                        TextInput::make('empresa_nome')->label('Razão Social')->required(),
                                        TextInput::make('empresa_cnpj')->label('CNPJ/CPF')->mask('99.999.999/9999-99'),
                                        TextInput::make('empresa_site')->label('Website / Instagram')->prefix('https://'),
                                        ColorPicker::make('cor_primaria')->label('Cor Principal do Sistema')->default('#2563EB'),
                                    ])->columns(2),

                                Section::make('Contato Oficial')
                                    ->schema([
                                        TextInput::make('empresa_telefone')->label('WhatsApp Comercial')->mask('(99) 99999-9999'),
                                        TextInput::make('empresa_email')->label('E-mail Financeiro'),
                                        Textarea::make('empresa_endereco')->label('Endereço Completo (Matriz)')->rows(2)->columnSpanFull(),
                                    ])->columns(2),
                            ]),

                        // 2. FINANCEIRO (PIX e Taxas)
                        Tabs\Tab::make('Engenharia Financeira')
                            ->icon('heroicon-m-currency-dollar')
                            ->schema([
                                Section::make('Tesouraria Digital (PIX)')
                                    ->schema([
                                        Repeater::make('financeiro_pix_keys')
                                            ->label('Chaves PIX Ativas')
                                            ->schema([
                                                Select::make('banco')
                                                    ->options(['Inter' => 'Inter', 'Nubank' => 'Nubank', 'Itau' => 'Itaú', 'Santander' => 'Santander'])
                                                    ->required(),
                                                Select::make('tipo_chave')
                                                    ->options(['cpf' => 'CPF/CNPJ', 'email' => 'E-mail', 'celular' => 'Celular', 'aleatoria' => 'Chave Aleatória'])
                                                    ->required(),
                                                TextInput::make('chave')->label('Chave')->required(),
                                            ])->columns(3)->grid(1),
                                    ]),

                                Section::make('Taxas de Cartão (Simulação)')
                                    ->description('Configuração das alíquotas para cálculo de lucro real.')
                                    ->schema([
                                        Repeater::make('financeiro_taxas_cartao')
                                            ->label('Tabela de Alíquotas (Maquininha)')
                                            ->schema([
                                                TextInput::make('parcelas')->label('Qtd Parcelas (Ex: 1x, 12x)')->required(),
                                                TextInput::make('taxa')->label('Taxa da Operadora (%)')->numeric()->suffix('%')->required(),
                                                Toggle::make('repassar_juros')->label('Repassar ao Cliente?')->default(false),
                                            ])->columns(3)->grid(2),
                                    ]),
                            ]),

                        // 3. OPERACIONAL (Regras de Serviço)
                        Tabs\Tab::make('Regras Operacionais')
                            ->icon('heroicon-m-clipboard-document-check')
                            ->schema([
                                Section::make('Parâmetros de Orçamento')
                                    ->schema([
                                        TextInput::make('orcamento_validade')
                                            ->label('Validade da Proposta (Dias)')
                                            ->numeric()
                                            ->default(15)
                                            ->suffix('dias'),
                                        TextInput::make('taxa_deslocamento_minima')
                                            ->label('Taxa Mínima de Deslocamento')
                                            ->numeric()
                                            ->prefix('R$'),
                                        TextInput::make('pedido_minimo')
                                            ->label('Valor Mínimo de Pedido')
                                            ->numeric()
                                            ->prefix('R$')
                                            ->helperText('O sistema alertará se o orçamento for menor que isso.'),
                                    ])->columns(3),
                            ]),

                        // 4. JURÍDICO (Termos e Garantia)
                        Tabs\Tab::make('Jurídico & Garantia')
                            ->icon('heroicon-m-shield-check')
                            ->schema([
                                Section::make('Certificado de Garantia')
                                    ->schema([
                                        TextInput::make('garantia_prazo_padrao')
                                            ->label('Prazo de Garantia (Meses)')
                                            ->numeric()
                                            ->default(12),
                                        RichEditor::make('texto_garantia')
                                            ->label('Texto Legal do Certificado')
                                            ->default('A Stofgard garante a impermeabilização contra líquidos à base de água...')
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Termos do Orçamento')
                                    ->schema([
                                        Textarea::make('obs_orcamento_padrao')
                                            ->label('Observações Padrão no PDF')
                                            ->rows(4)
                                            ->default('Serviço realizado no local. Necessário ponto de energia e água.'),
                                    ]),
                            ]),

                        // 5. (Placeholder for future expansion) - kept empty for now
                        Tabs\Tab::make('Infra & Integrations')
                            ->icon('heroicon-m-cloud-upload')
                            ->schema([
                                Section::make('Integrações')
                                    ->schema([
                                        TextInput::make('integracao_ga')->label('Google Analytics ID'),
                                        TextInput::make('integracao_pagarme')->label('Pagar.me Public Key'),
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

        Notification::make()->title('Sistema Atualizado com Sucesso')->success()->send();
    }
}

