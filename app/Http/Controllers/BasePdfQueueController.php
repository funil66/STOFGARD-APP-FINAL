<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/**
 * Controller base para geração de PDFs via fila
 * Centraliza lógica comum de dispatch para fila e resposta ao usuário
 */
abstract class BasePdfQueueController extends Controller
{
    /**
     * Carrega configurações do sistema com valores padrão
     * Decodifica JSONs conhecidos automaticamente
     */
    protected function loadConfig(): object
    {
        $settingsArray = Setting::all()->pluck('value', 'key')->toArray();
        $companyIdentity = company_pdf_identity();

        // Decodifica JSONs conhecidos
        $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
        foreach ($jsonFields as $k) {
            if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                $settingsArray[$k] = json_decode($settingsArray[$k], true);
            }
        }

        try {
            $tenantConfig = \App\Models\Configuracao::query()
                ->whereNotNull('empresa_nome')
                ->where('empresa_nome', '!=', '')
                ->latest('id')
                ->first();

            if (!$tenantConfig) {
                $tenantConfig = \App\Models\Configuracao::query()->latest('id')->first();
            }

            if ($tenantConfig) {
                $settingsArray['empresa_logo'] = $companyIdentity['empresa_logo'] ?? $settingsArray['empresa_logo'] ?? $tenantConfig->empresa_logo ?? null;
                $settingsArray['empresa_nome'] = $companyIdentity['empresa_nome'] ?? $settingsArray['empresa_nome'] ?? $tenantConfig->empresa_nome ?? null;
                $settingsArray['empresa_cnpj'] = $companyIdentity['empresa_cnpj'] ?? $settingsArray['empresa_cnpj'] ?? $tenantConfig->empresa_cnpj ?? null;
                $settingsArray['empresa_telefone'] = $companyIdentity['empresa_telefone'] ?? $settingsArray['empresa_telefone'] ?? $tenantConfig->empresa_telefone ?? null;
                $settingsArray['empresa_email'] = $companyIdentity['empresa_email'] ?? $settingsArray['empresa_email'] ?? $tenantConfig->empresa_email ?? null;
            }
        } catch (\Throwable) {
            // mantém fallback com settings já carregados
        }

        return (object) $settingsArray;
    }

    /**
     * Renderiza view para HTML e enfileira geração de PDF
     * 
     * @param string $viewName Nome da view (ex: 'pdf.orcamento')
     * @param array $viewData Dados para passar à view
     * @param string $pdfType Tipo de documento (ex: 'orcamento')
     * @param mixed $model Modelo associado (para buscar ID)
     * @param array $relationships Relacionamentos a carregar do modelo
     * 
     * @return RedirectResponse Redireciona para página de status
     */
    protected function enqueuePdf(
        string $viewName,
        array $viewData,
        string $pdfType,
        mixed $model,
        array $relationships = []
    ) {
        try {
            // Carrega relacionamentos se necessário
            if (!empty($relationships)) {
                $model->load($relationships);
            }

            // Renderiza HTML da view
            $htmlContent = view($viewName, $viewData)->render();

            // Extrai orçamento_id se disponível (para PDFs vinculados)
            $orcamentoId = null;
            if (method_exists($model, 'orcamento')) {
                $orcamentoId = $model->orcamento_id ?? null;
            } elseif (method_exists($model, 'ordemServico')) {
                $orcamentoId = $model->ordemServico?->orcamento_id ?? null;
            }

            // Enfileira geração
            \App\Services\PdfQueueService::enqueue(
                $model->id,
                $pdfType,
                auth()->id(),
                $htmlContent,
                $orcamentoId
            );

            // Notificação de sucesso
            Notification::make()
                ->title('🚀 PDF em Processamento')
                ->body('Seu documento foi enfileirado. Você receberá uma notificação quando estiver pronto.')
                ->success()
                ->send();

            if (Route::has('filament.admin.resources.pdf-geracoes.index')) {
                return redirect()
                    ->route('filament.admin.resources.pdf-geracoes.index')
                    ->with('success', 'PDF enfileirado com sucesso!');
            }

            return redirect('/admin')
                ->with('success', 'PDF enfileirado com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Erro ao enfileirar PDF', [
                'model' => get_class($model),
                'model_id' => $model->id,
                'tipo' => $pdfType,
                'erro' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('❌ Erro ao Enfileirar PDF')
                ->body('Não foi possível processar seu documento. Tente novamente em alguns segundos.')
                ->danger()
                ->send();

            return back();
        }
    }
}
