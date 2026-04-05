<?php
$osR = file_get_contents('app/Filament/Resources/OrdemServicoResource.php');

$oldAction = <<<'EOD'
                        Tables\Actions\Action::make('certificado_garantia')
                            ->label('Garantia')
                            ->icon('heroicon-s-document-check')
                            ->color('success')
                            ->visible(fn(OrdemServico $record) => $record->status === 'concluida' && $record->perfil_garantia_id)
                            ->url(fn(OrdemServico $record) => route('os.garantia', $record))
                            ->openUrlInNewTab(),
EOD;

$newAction = <<<'EOD'
                        Tables\Actions\Action::make('certificado_garantia')
                            ->label('Garantia (Fila)')
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('success')
                            ->visible(fn(OrdemServico $record) => $record->status === 'concluida')
                            ->requiresConfirmation()
                            ->modalHeading('Gerar Certificado de Garantia')
                            ->modalDescription('O Certificado de Garantia será gerado em segundo plano usando as configurações de layout de PDF (Layout de Orçamento/Garantia). Você receberá uma notificação quando estiver pronto.')
                            ->action(function (OrdemServico $record) {
                                // Pega a primeira garantia associada ou simula uma
                                $garantia = $record->garantias()->first();
                                if (!$garantia) {
                                    $tipoServico = $record->tipo_servico ?? 'servico';
                                    $dias = \App\Services\ServiceTypeManager::getDiasGarantia($tipoServico) ?? 90;
                                    $garantia = \App\Models\Garantia::create([
                                        'ordem_servico_id' => $record->id,
                                        'tipo_servico' => $tipoServico,
                                        'data_inicio' => now(),
                                        'dias_garantia' => $dias,
                                        'data_fim' => now()->addDays($dias),
                                        'status' => 'ativa',
                                        'observacoes' => "Garantia padrão",
                                    ]);
                                }

                                $settingsArray = \App\Models\Setting::pluck('value', 'key')->toArray();
                                $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
                                foreach ($jsonFields as $k) {
                                    if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                                        $decoded = json_decode($settingsArray[$k], true);
                                        $settingsArray[$k] = $decoded !== null ? $decoded : [];
                                    } elseif (!isset($settingsArray[$k])) {
                                        $settingsArray[$k] = [];
                                    }
                                }
                                $config = (object) $settingsArray;
                                $tenantConfig = \App\Models\Configuracao::first();
                                if ($tenantConfig) {
                                    $config->empresa_logo = $tenantConfig->empresa_logo ?? null;
                                    $config->empresa_nome = $tenantConfig->empresa_nome ?? null;
                                    $config->empresa_cnpj = $tenantConfig->empresa_cnpj ?? null;
                                }

                                try {
                                    // Renderiza o HTML com a engine do blade
                                    $htmlContent = view('pdf.certificado_garantia', [
                                        'os' => $record,
                                        'garantia' => $garantia,
                                        'orcamento' => $record->orcamento,
                                        'config' => $config
                                    ])->render();

                                    \App\Jobs\ProcessPdfJob::dispatch(
                                        $record->id,
                                        'garantia',
                                        auth()->id(),
                                        $htmlContent
                                    );

                                    \Filament\Notifications\Notification::make()
                                        ->title('⚙️ Gerando Garantia...')
                                        ->body('O Certificado de Garantia está sendo gerado no servidor. Avisaremos quando estiver pronto.')
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Erro Crítico')
                                        ->body('Falha ao compilar Certificado de Garantia. Erro: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
EOD;

if (strpos($osR, $oldAction) !== false) {
    file_put_contents('app/Filament/Resources/OrdemServicoResource.php', str_replace($oldAction, $newAction, $osR));
    echo "Action replaced successfully\n";
} else {
    echo "Action not found\n";
}

$osO = file_get_contents('app/Observers/OrdemServicoObserver.php');
$target = <<<'EOD'
            Log::info("Garantia de {$dias} dias criada automaticamente para OS {$os->numero_os} ({$tipoServico})");
EOD;

$newTarget = <<<'EOD'
            Log::info("Garantia de {$dias} dias criada automaticamente para OS {$os->numero_os} ({$tipoServico})");

            try {
                $settingsArray = \App\Models\Setting::pluck('value', 'key')->toArray();
                $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
                foreach ($jsonFields as $k) {
                    if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                        $decoded = json_decode($settingsArray[$k], true);
                        $settingsArray[$k] = $decoded !== null ? $decoded : [];
                    } elseif (!isset($settingsArray[$k])) {
                        $settingsArray[$k] = [];
                    }
                }
                $config = (object) $settingsArray;
                $tenantConfig = \App\Models\Configuracao::first();
                if ($tenantConfig) {
                    $config->empresa_logo = $tenantConfig->empresa_logo ?? null;
                    $config->empresa_nome = $tenantConfig->empresa_nome ?? null;
                    $config->empresa_cnpj = $tenantConfig->empresa_cnpj ?? null;
                }
                
                // Get the created garantia
                $garantia = $os->garantias()->latest()->first();

                // Disparar job invisível para gerar PDF
                $htmlContent = view('pdf.certificado_garantia', [
                    'os' => $os,
                    'garantia' => $garantia,
                    'orcamento' => $os->orcamento,
                    'config' => $config
                ])->render();

                \App\Jobs\ProcessPdfJob::dispatch(
                    $os->id,
                    'garantia',
                    $os->criado_por ?? 1,
                    $htmlContent
                );
            } catch (\Exception $e) {
                Log::error("Erro ao despachar job de PDF da garantia para OS {$os->numero_os}: " . $e->getMessage());
            }
EOD;

if (strpos($osO, $target) !== false) {
    file_put_contents('app/Observers/OrdemServicoObserver.php', str_replace($target, $newTarget, $osO));
    echo "Observer updated successfully\n";
} else {
    echo "Observer target not found\n";
}
