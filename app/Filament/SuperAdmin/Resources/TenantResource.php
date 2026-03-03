<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Models\Tenant;
use App\Services\AsaasService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * TenantResource — Gerenciamento completo de tenants/empresas do SaaS.
 * Fase 1: Inclui billing, status de pagamento e controle de planos.
 */
class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tenants / Empresas';

    protected static ?string $modelLabel = 'Tenant';

    protected static ?string $pluralModelLabel = 'Tenants';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tenant')
                    ->tabs([
                        // ======================================================
                        Forms\Components\Tabs\Tab::make('🏢 Dados da Empresa')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome da Empresa')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug (subdomínio)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Ex: "joao-eletricista" → joao-eletricista.stofgard.com.br')
                                    ->alphaDash()
                                    ->maxLength(100),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Tenant Ativo')
                                    ->helperText('Se desativado, nenhum usuário do tenant consegue fazer login.')
                                    ->required(),

                                Forms\Components\Select::make('plan')
                                    ->label('Plano')
                                    ->options([
                                        'free' => '🆓 Free',
                                        'pro' => '⭐ PRO',
                                        'elite' => '💎 Elite',
                                    ])
                                    ->required()
                                    ->default('free')
                                    ->live(),
                            ])
                            ->columns(2),

                        // ======================================================
                        Forms\Components\Tabs\Tab::make('💰 Billing & Assinatura')
                            ->badge(fn($record) => $record?->status_pagamento)
                            ->badgeColor(fn($record) => match ($record?->status_pagamento) {
                                'ativo' => 'success',
                                'trial' => 'info',
                                'inadimplente' => 'warning',
                                'suspenso' => 'danger',
                                'cancelado' => 'gray',
                                default => null,
                            })
                            ->schema([
                                Forms\Components\Select::make('status_pagamento')
                                    ->label('Status do Pagamento')
                                    ->options([
                                        'trial' => '⏳ Trial (período gratuito)',
                                        'ativo' => '✅ Ativo (pagamento em dia)',
                                        'inadimplente' => '⚠️ Inadimplente',
                                        'suspenso' => '🚫 Suspenso',
                                        'cancelado' => '❌ Cancelado',
                                    ])
                                    ->required()
                                    ->default('trial'),

                                Forms\Components\DatePicker::make('data_vencimento')
                                    ->label('Vencimento da Próxima Fatura')
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Data da próxima cobrança mensal.'),

                                Forms\Components\DatePicker::make('trial_termina_em')
                                    ->label('Trial Encerra em')
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Deixe em branco se não estiver em trial.'),

                                Forms\Components\TextInput::make('gateway_customer_id')
                                    ->label('ID do Cliente no Asaas')
                                    ->placeholder('cus_...')
                                    ->readOnly()
                                    ->helperText('Preenchido automaticamente ao iniciar assinatura.'),

                                Forms\Components\TextInput::make('gateway_subscription_id')
                                    ->label('ID da Assinatura no Asaas')
                                    ->placeholder('sub_...')
                                    ->readOnly()
                                    ->helperText('Preenchido automaticamente ao criar assinatura.'),
                            ])
                            ->columns(2),

                        // ======================================================
                        Forms\Components\Tabs\Tab::make('📊 Limites do Plano')
                            ->schema([
                                Forms\Components\TextInput::make('max_users')
                                    ->label('Máx. Usuários')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->helperText('Número máximo de usuários que podem ser criados neste tenant.'),

                                Forms\Components\TextInput::make('max_orcamentos_mes')
                                    ->label('Máx. Orçamentos/mês')
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(0)
                                    ->helperText('0 = ilimitado'),

                                Forms\Components\TextInput::make('limite_os_mes')
                                    ->label('Máx. Ordens de Serviço/mês')
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(0)
                                    ->helperText('0 = ilimitado. Reinicia todo dia 1º do mês.'),

                                Forms\Components\Placeholder::make('os_criadas_mes_atual')
                                    ->label('OS Abertas Este Mês')
                                    ->content(fn($record) => $record?->os_criadas_mes_atual ?? 0),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Tenant $record) => $record->slug),

                Tables\Columns\BadgeColumn::make('plan')
                    ->label('Plano')
                    ->colors([
                        'gray' => 'free',
                        'primary' => 'pro',
                        'warning' => 'elite',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'free' => '🆓 Free',
                        'pro' => '⭐ PRO',
                        'elite' => '💎 Elite',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('status_pagamento')
                    ->label('Status Pagamento')
                    ->colors([
                        'success' => 'ativo',
                        'info' => 'trial',
                        'warning' => 'inadimplente',
                        'danger' => ['suspenso', 'cancelado'],
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'ativo' => '✅ Ativo',
                        'trial' => '⏳ Trial',
                        'inadimplente' => '⚠️ Inadimplente',
                        'suspenso' => '🚫 Suspenso',
                        'cancelado' => '❌ Cancelado',
                        default => $state ?? 'N/A',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Online')
                    ->boolean(),

                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('trial_termina_em')
                    ->label('Trial até')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->label('Plano')
                    ->options([
                        'free' => 'Free',
                        'pro' => 'PRO',
                        'elite' => 'Elite',
                    ]),

                Tables\Filters\SelectFilter::make('status_pagamento')
                    ->label('Status Pagamento')
                    ->options([
                        'trial' => 'Trial',
                        'ativo' => 'Ativo',
                        'inadimplente' => 'Inadimplente',
                        'suspenso' => 'Suspenso',
                        'cancelado' => 'Cancelado',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Online')
                    ->trueLabel('Ativos')
                    ->falseLabel('Suspensos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),

                // Ativar tenant suspenso
                Tables\Actions\Action::make('ativar')
                    ->label('Ativar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Tenant $record) => !$record->is_active)
                    ->action(function (Tenant $record) {
                        $record->update([
                            'is_active' => true,
                            'status_pagamento' => 'ativo',
                        ]);
                        Notification::make()->title('Tenant ativado!')->success()->send();
                    }),

                // Suspender manualmente
                Tables\Actions\Action::make('suspender')
                    ->label('Suspender')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Tenant $record) => $record->is_active)
                    ->action(function (Tenant $record) {
                        $record->update([
                            'is_active' => false,
                            'status_pagamento' => 'suspenso',
                        ]);
                        Notification::make()->title('Tenant suspenso.')->warning()->send();
                    }),

                // Impersonate (Acessar a conta)
                Tables\Actions\Action::make('acessar_conta')
                    ->label('Login Mágico')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Acessar Painel do Inquilino')
                    ->modalDescription('Você fará login automaticamente como o proprietário deste tenant e será redirecionado para o painel de administração.')
                    ->action(function (Tenant $record) {
                        $user = \App\Models\User::where('tenant_id', $record->id)->first();
                        if ($user) {
                            \Illuminate\Support\Facades\Auth::login($user);
                            return redirect('/admin');
                        } else {
                            Notification::make()->title('Este inquilino não possui nenhum usuário cadastrado.')->danger()->send();
                        }
                    }),

                // Iniciar assinatura no Asaas
                Tables\Actions\Action::make('iniciar_assinatura')
                    ->label('Iniciar Assinatura (Asaas)')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Criar Assinatura no Asaas')
                    ->modalDescription('Isso cria a assinatura mensal do tenant no Asaas. Verifique o valor do plano antes de confirmar.')
                    ->visible(fn(Tenant $record) => empty($record->gateway_subscription_id))
                    ->form([
                        Forms\Components\TextInput::make('email_cliente')
                            ->label('E-mail do cliente (para o Asaas)')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('cpf_cnpj')
                            ->label('CPF ou CNPJ')
                            ->required(),
                    ])
                    ->action(function (Tenant $record, array $data) {
                        try {
                            $asaas = app(AsaasService::class);

                            // 1. Cria cliente no Asaas
                            $cliente = $asaas->criarCliente([
                                'name' => $record->name,
                                'email' => $data['email_cliente'],
                                'cpf_cnpj' => $data['cpf_cnpj'],
                                'tenant_id' => $record->id,
                            ]);

                            // 2. Cria assinatura
                            $valorPlano = match ($record->plan) {
                                'pro' => (float) env('PLAN_PRO_PRICE', 97),
                                'elite' => (float) env('PLAN_ELITE_PRICE', 197),
                                default => 0,
                            };

                            $assinatura = $asaas->criarAssinatura(
                                $cliente['id'],
                                $valorPlano,
                                strtoupper($record->plan)
                            );

                            // 3. Salva IDs no tenant
                            $record->update([
                                'gateway_customer_id' => $cliente['id'],
                                'gateway_subscription_id' => $assinatura['id'],
                                'status_pagamento' => 'ativo',
                                'data_vencimento' => now()->addMonth()->format('Y-m-d'),
                            ]);

                            Notification::make()
                                ->title('✅ Assinatura criada com sucesso!')
                                ->body("ID: {$assinatura['id']}")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Erro ao criar assinatura')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Cancelar assinatura
                Tables\Actions\Action::make('cancelar_assinatura')
                    ->label('Cancelar Assinatura')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar assinatura no Asaas?')
                    ->modalDescription('Esta ação é irreversível. O tenant ficará inadimplente e será suspenso após 5 dias.')
                    ->visible(fn(Tenant $record) => !empty($record->gateway_subscription_id))
                    ->action(function (Tenant $record) {
                        try {
                            app(AsaasService::class)->cancelarAssinatura($record->gateway_subscription_id);

                            $record->update([
                                'gateway_subscription_id' => null,
                                'status_pagamento' => 'cancelado',
                            ]);

                            Notification::make()->title('Assinatura cancelada.')->warning()->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erro ao cancelar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\SuperAdmin\Resources\TenantResource\Pages\ListTenants::route('/'),
            'edit' => \App\Filament\SuperAdmin\Resources\TenantResource\Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
