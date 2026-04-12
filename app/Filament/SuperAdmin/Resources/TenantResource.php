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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
                                    ->unique(
                                        table: 'tenants',
                                        column: 'slug',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) => $rule->whereNull('deleted_at'),
                                    )
                                    ->dehydrateStateUsing(fn (?string $state) => trim(strtolower((string) $state)))
                                    ->notIn(['app', 'admin', 'super-admin', 'sistema', 'suporte', 'api', 'www', 'mail', 'painel'])
                                    ->validationMessages([
                                        'unique' => 'Este slug já está em uso por outro tenant ativo.',
                                        'not_in' => 'Este subdomínio é reservado e não pode ser usado.',
                                        'regex'  => 'O slug deve conter apenas letras, números, traços e pontos.'
                                    ])
                                    ->helperText('Ex: "joao-eletricista" ou "dominioproprio.com.br"')
                                    ->regex('/^[a-zA-Z0-9.\-]+$/')
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

                                Forms\Components\Fieldset::make('Usuário administrador inicial')
                                    ->schema([
                                        Forms\Components\TextInput::make('owner_name')
                                            ->label('Nome do administrador')
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('owner_email')
                                            ->label('E-mail do administrador')
                                            ->email()
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('owner_password')
                                            ->label('Senha do administrador')
                                            ->password()
                                            ->revealable()
                                            ->minLength(8)
                                            ->required(fn (string $operation): bool => $operation === 'create'),
                                    ])
                                    ->visible(fn (string $operation): bool => $operation === 'create')
                                    ->columns(3),
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

                Tables\Columns\TextColumn::make('plan')
                    ->label('Plano')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pro' => 'primary',
                        'elite' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'free' => '🆓 Free',
                        'pro' => '⭐ PRO',
                        'elite' => '💎 Elite',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('status_pagamento')
                    ->label('Status Pagamento')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'ativo' => 'success',
                        'trial' => 'info',
                        'inadimplente' => 'warning',
                        'suspenso', 'cancelado' => 'danger',
                        default => 'gray',
                    })
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
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

                Tables\Actions\Action::make('criar_usuario_admin')
                    ->label('Criar usuário admin')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->required()
                            ->minLength(8),
                    ])
                    ->action(function (Tenant $record, array $data) {
                        try {
                            tenancy()->initialize($record);

                            $exists = \App\Models\User::query()
                                ->where('email', strtolower($data['email']))
                                ->exists();

                            if ($exists) {
                                Notification::make()
                                    ->title('E-mail já existe neste tenant')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            \App\Models\User::query()->create([
                                'name' => $data['name'],
                                'email' => strtolower($data['email']),
                                'password' => Hash::make($data['password']),
                                'is_admin' => true,
                                'role' => 'dono',
                                'acesso_financeiro' => true,
                                'email_verified_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Usuário admin criado com sucesso')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erro ao criar usuário')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        } finally {
                            tenancy()->end();
                        }
                    }),

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

                // Impersonate (Acessar a conta real do Dono)
                Tables\Actions\Action::make('impersonate')
                    ->label('Logar como Inquilino')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Acessar como dono do Inquilino?')
                    ->modalDescription('Você será redirecionado para o painel de administração dele, com total controle. Suas ações serão feitas em nome do Dono da Empresa.')
                    ->action(function (Tenant $record) {
                        try {
                            $superAdminId = Auth::id();

                            // 1. Busca o dono do Tenant usando a conexão central (pgsql)
                            $dono = \App\Models\User::on(config('tenancy.central_connection', config('database.default')))
                                ->where('tenant_id', $record->id)
                                ->where('role', 'dono')
                                ->first();

                            if (!$dono) {
                                $dono = \App\Models\User::on(config('tenancy.central_connection', config('database.default')))
                                    ->where('tenant_id', $record->id)
                                    ->orderBy('id', 'asc')
                                    ->first();
                            }

                            if (!$dono) {
                                Notification::make()
                                    ->title('Nenhum usuário encontrado neste Tenant')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            tenancy()->initialize($record);

                            // 2. Faz o backup do Super Admin e Log da Ação (Audit)
                            Log::warning('[SuperAdmin] Impersonação de Tenant iniciada', [
                                'super_admin_id' => $superAdminId,
                                'target_tenant_id' => $record->id,
                                'target_user_id' => $dono->id,
                                'ip' => request()->ip(),
                            ]);

                            session()->put('impersonating_super_admin_id', $superAdminId);
                            session()->put('impersonated_at', now()->toIso8601String());

                            // 3. Força o login com as credenciais do Dono do Tenant
                            Auth::guard('web')->login($dono);

                            // Gerar um token seguro para login imediato (Fallback para TLDs onde SESSION_DOMAIN não rola, ex .localhost)
                            $token = \Illuminate\Support\Str::random(40);
                            \Illuminate\Support\Facades\Cache::put('central_auth_token_' . $token, $dono->id, now()->addMinutes(5));

                            // Como a URL do tenant está no DB, precisamos apontar para ela e garantir a sessão
                            $domain = $record->domains->first()?->domain ?? env('APP_URL');
                            
                            $protocol = request()->secure() ? 'https://' : 'http://';
                            $port = request()->getPort();
                            $portSuffix = (!in_array($port, [80, 443])) ? ":{$port}" : "";

                            if (str_starts_with($domain, 'http')) {
                                $url = "{$domain}/admin/login?token={$token}";
                            } else {
                                $url = "{$protocol}{$domain}{$portSuffix}/admin/login?token={$token}";
                            }
                            
                            // Dica para reverter: O usuário precisará usar o Sair padrão, o middleware Global não detectará imediatamente o super-admin cross-domain sem session driver unificado
                            return redirect()->away($url);

                        } catch (\Exception $e) {
                            tenancy()->end();
                            Notification::make()
                                ->title('Erro ao impersonar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->tooltip('Acessar a conta do administrador deste tenant.'),

                // Suporte Direto via WhatsApp
                Tables\Actions\Action::make('suporte_whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->color('success')
                    ->url(function (Tenant $record) {
                        try {
                            // Fetch via direct connection to bypass stancl/tenancy bootstrapper lifecycle crashes
                            $dbConfig = config('database.connections.' . config('database.default'));
                            $dbConfig['database'] = $record->tenancy_db_name;
                            config(["database.connections.tmp_{$record->id}" => $dbConfig]);
                            
                            $telefone = \Illuminate\Support\Facades\DB::connection("tmp_{$record->id}")
                                ->table('configuracoes')->first()?->empresa_telefone;
                            
                            \Illuminate\Support\Facades\DB::purge("tmp_{$record->id}");

                            if (!$telefone) {
                                return '#';
                            }
                            $numero = preg_replace('/[^0-9]/', '', $telefone);
                            return "https://wa.me/55{$numero}?text=Ol%C3%A1%2C%20aqui%20%C3%A9%20o%20suporte%20da%20Autonomia%20Ilimitada.%20Como%20podemos%20ajudar%3F";
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\DB::purge("tmp_{$record->id}");
                            return '#';
                        }
                    })
                    ->openUrlInNewTab()
                    ->tooltip('Abrir conversa no WhatsApp com o dono do tenant.'),

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
                            $apiKey = trim((string) config('services.asaas.api_key', ''));
                            if ($apiKey === '') {
                                throw new \RuntimeException('ASAAS_API_KEY não está configurada no ambiente ativo.');
                            }

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

                // 🔴 Botão do Pânico — Limpar Cache do Inquilino
                Tables\Actions\Action::make('limpar_cache')
                    ->label('Limpar Cache')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Limpar Cache do Inquilino')
                    ->modalDescription('Isso vai limpar o cache de views, rotas e config deste tenant. Pode resolver problemas de exibição ou configurações que não atualizam.')
                    ->action(function (Tenant $record) {
                        try {
                            tenancy()->initialize($record);

                            \Illuminate\Support\Facades\Artisan::call('cache:clear');
                            \Illuminate\Support\Facades\Artisan::call('view:clear');
                            \Illuminate\Support\Facades\Artisan::call('route:clear');
                            \Illuminate\Support\Facades\Artisan::call('config:clear');

                            tenancy()->end();

                            Log::info('[SuperAdmin] Cache limpo para tenant', [
                                'tenant_id' => $record->id,
                                'super_admin_id' => Auth::id(),
                            ]);

                            Notification::make()
                                ->title("Cache limpo para \"{$record->name}\"!")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            tenancy()->end();
                            Notification::make()
                                ->title('Erro ao limpar cache')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->tooltip('Limpar cache, views e rotas deste tenant.'),

                // Ação Crítica: Exclusão Definitiva do Tenant
                Tables\Actions\Action::make('deletar_tenant')
                    ->label('Excluir Definitivo')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('EXCLUSÃO DEFINITIVA')
                    ->modalDescription(fn(Tenant $record) => "ATENÇÃO: Você está prestes a APAGAR COMPLETAMENTE a empresa '{$record->name}' e todo o seu banco de dados e arquivos associados. Essa ação é irreversível. Para continuar, digite EXATAMENTE o slug (subdomínio) '{$record->slug}' abaixo.")
                    ->form(fn(Tenant $record) => [
                        Forms\Components\TextInput::make('confirmacao_slug')
                            ->label("Confirme digitando: {$record->slug}")
                            ->required()
                            ->rule("in:{$record->slug}")
                            ->validationMessages([
                                'in' => 'A palavra digitada não confere com o slug exato. Cancelando exclusão.',
                            ])
                    ])
                    ->action(function (Tenant $record, array $data) {
                        try {
                            if ($data['confirmacao_slug'] !== $record->slug) {
                                throw new \Exception('Validação falhou.');
                            }

                            $tenantId = $record->id;
                            
                            // Remove of owner user on central db
                            \App\Models\User::where('tenant_id', $tenantId)->delete();

                            $record->forceDelete();

                            Log::warning('[SuperAdmin] Tenant APAGADO definitivamente', [
                                'tenant_id' => $record->id,
                                'tenant_name' => $record->name,
                                'super_admin_id' => Auth::id(),
                            ]);

                            Notification::make()
                                ->title("Tenant '{$record->name}' excluído com sucesso!")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erro ao excluir tenant')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->tooltip('Excluir completamente este Tenant e seu banco de dados.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                \App\Models\User::where('tenant_id', $record->id)->delete();
                                $record->forceDelete();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\SuperAdmin\Resources\TenantResource\RelationManagers\TenantUsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\SuperAdmin\Resources\TenantResource\Pages\ListTenants::route('/'),
            'create' => \App\Filament\SuperAdmin\Resources\TenantResource\Pages\CreateTenant::route('/create'),
            'edit' => \App\Filament\SuperAdmin\Resources\TenantResource\Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
