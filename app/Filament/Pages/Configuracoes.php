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
    protected static ?string $title = 'Configurações do Sistema';
    protected static ?string $slug = 'configuracoes';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all()->pluck('value','key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Configurações')
                    ->tabs([
                        Tabs\Tab::make('Institucional')
                            ->icon('heroicon-m-building-office')
                            ->schema([
                                Section::make('Dados da Empresa')
                                    ->description('Informações usadas em cabeçalhos e rodapés.')
                                    ->schema([
                                        TextInput::make('empresa_nome')->label('Razão Social / Nome')->required(),
                                        TextInput::make('empresa_cnpj')->label('CNPJ / CPF'),
                                        TextInput::make('empresa_telefone')->label('Telefone de Contato'),
                                        TextInput::make('empresa_email')->label('E-mail Principal'),
                                        Textarea::make('empresa_endereco')->label('Endereço Completo')->rows(2),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('Personalização & PDF')
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
                                Section::make('Identidade Visual')
                                    ->schema([
                                        FileUpload::make('empresa_logo')
                                            ->label('Logo do Sistema (PDF)')
                                            ->directory('logos')
                                            ->image()
                                            ->imageEditor(),
                                        ColorPicker::make('cor_primaria')
                                            ->label('Cor Principal dos Documentos')
                                            ->default('#2563EB'),
                                    ])->columns(2),

                                Section::make('Textos Padrão')
                                    ->schema([
                                        Textarea::make('pdf_obs_padrao')
                                            ->label('Observações Padrão (Orçamentos)')
                                            ->helperText('Texto que aparece automaticamente no rodapé dos orçamentos.')
                                            ->rows(3),
                                    ]),
                            ]),

                        Tabs\Tab::make('Parâmetros Financeiros')
                            ->icon('heroicon-m-banknotes')
                            ->schema([
                                TextInput::make('financeiro_validade_orcamento')
                                    ->label('Validade do Orçamento (Dias)')
                                    ->numeric()
                                    ->default(15),
                                TextInput::make('financeiro_chave_pix')
                                    ->label('Chave PIX Padrão'),
                            ])->columns(2),

                        Tabs\Tab::make('Sistema & Segurança')
                            ->icon('heroicon-m-shield-check')
                            ->schema([
                                Section::make('Manutenção')
                                    ->schema([
                                        TextInput::make('sistema_versao')
                                            ->label('Versão do Sistema')
                                            ->disabled()
                                            ->default('Stofgard v2.0 Iron'),
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
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Configurações salvas com sucesso!')
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

