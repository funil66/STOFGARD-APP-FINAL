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
                                        // Substituído FileUpload por TextInput para evitar crash de ícone
                                        TextInput::make('empresa_logo_path')
                                            ->label('Nome do Arquivo da Logo')
                                            ->helperText('Ex: logo_stofgard.png (Deve estar na pasta public/images)')
                                            ->default('logo_stofgard.png'),
                                        ColorPicker::make('cor_primaria')->label('Cor do Sistema')->default('#2563EB'),
                                    ])->columns(2),
                                Section::make('Contato')
                                    ->schema([
                                        TextInput::make('empresa_telefone')->label('WhatsApp')->mask('(99) 99999-9999'),
                                        TextInput::make('empresa_email')->label('E-mail'),
                                        Textarea::make('empresa_endereco')->label('Endereço')->rows(2)->columnSpanFull(),
                                    ])->columns(2),
                            ]),
                        // 2. FINANCEIRO (Ícone Banknotes Seguro)
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
            Setting::set($key, $value);
        }

        Notification::make()->title('Configurações Salvas')->success()->send();
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
}

