<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Actions\Action;
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
                Tabs::make('Configurações')
                    ->tabs([
                        // ABA 1: IDENTIDADE VISUAL
                        Tabs\Tab::make('Identidade Visual')
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
                                Section::make('Marca e Documentos')
                                    ->schema([
                                        FileUpload::make('empresa_logo')
                                            ->label('Logo Oficial (PDF e Sistema)')
                                            ->directory('logos')
                                            ->image()
                                            ->preserveFilenames(),
                                        TextInput::make('empresa_nome')
                                            ->label('Nome Fantasia')
                                            ->default('Stofgard Higienização'),
                                    ])->columns(2),
                            ]),

                        // ABA 2: FINANCEIRO AVANÇADO
                        Tabs\Tab::make('Financeiro & Taxas')
                            ->icon('heroicon-m-currency-dollar')
                            ->schema([
                                Section::make('Recebimentos PIX')
                                    ->schema([
                                        \Filament\Forms\Components\Repeater::make('financeiro_pix_keys')
                                            ->label('Chaves PIX Disponíveis')
                                            ->schema([
                                                TextInput::make('tipo')->label('Tipo (CPF/CNPJ/Email)')->required(),
                                                TextInput::make('chave')->label('Chave PIX')->required(),
                                            ])->columns(2),
                                    ]),
                                Section::make('Máquina de Cartão')
                                    ->description('Configure as taxas para cálculo automático de repasse.')
                                    ->schema([
                                        \Filament\Forms\Components\Repeater::make('financeiro_taxas_cartao')
                                            ->label('Tabela de Alíquotas')
                                            ->schema([
                                                TextInput::make('descricao')->label('Condição (Ex: 1x Crédito, 12x)')->required(),
                                                TextInput::make('taxa_percentual')
                                                    ->label('Taxa (%)')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->required(),
                                            ])->columns(2)->grid(2),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        foreach ($state as $key => $value) {
            if (is_array($value)) {
                Setting::set($key, $value, 'geral', 'json');
            } else {
                Setting::set($key, $value);
            }
        }

        Notification::make()
            ->title('Parâmetros Atualizados')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Salvar Alterações')
                ->action('save')
                ->color('primary'),

            Action::make('limpar_cache')
                ->label('Limpar Cache')
                ->color('danger')
                ->action(function() {
                    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                    Notification::make()->title('Cache do Sistema Limpo!')->success()->send();
                })
                ->requiresConfirmation(),
        ];
    }
}

