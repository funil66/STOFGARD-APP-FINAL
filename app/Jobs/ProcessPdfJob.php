<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\PdfGeneration;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProcessPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 120;

    protected $modeloId;
    protected $tipo;
    protected $userId;
    protected $htmlContent;
    protected $recordId;

    public function __construct($modeloId, $tipo, $userId, $htmlContent, $recordId = null)
    {
        $this->modeloId = $modeloId;
        $this->tipo = $tipo;
        $this->userId = $userId;
        $this->htmlContent = $htmlContent;
        $this->recordId = $recordId;

        $this->onConnection('redis');
        $this->onQueue('high');
    }

    public function handle()
    {
        Log::info("Iniciando geração de PDF para {$this->tipo} ID: {$this->modeloId}");

        $fileName = "{$this->tipo}_{$this->modeloId}_" . time() . ".pdf";
        $pathPdf = "pdfs/{$fileName}";
        $fullPath = storage_path("app/public/{$pathPdf}");

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $pdfRecord = $this->recordId ? PdfGeneration::find($this->recordId) : null;

        try {
            $pdf = app(\App\Services\PdfService::class)->generateFromHtml(
                $this->htmlContent,
                $fileName,
                'a4',
                'portrait'
            );

            $content = base64_decode($pdf->base64());
            Storage::disk('public')->put($pathPdf, $content);

            $urlToPdf = asset("storage/{$pathPdf}");
            if (function_exists('tenancy') && tenancy()->initialized) {
                // Obtem o domínio associado ao tenant atual
                $domain = tenancy()->tenant->domains->first();
                if ($domain) {
                    $protocol = str_starts_with(env('APP_URL', 'http://'), 'https://') ? 'https://' : 'http://';
                    // Garante que a porta correta seja adicionada, caso nao seja padrao
                    $parsedUrl = parse_url(env('APP_URL'));
                    $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
                    $urlToPdf = "{$protocol}{$domain->domain}{$port}/storage/{$pathPdf}";
                }
            }

            // Atualiza registro com sucesso
            if ($pdfRecord) {
                try {
                    $pdfRecord->update([
                        'status'    => 'done',
                        'file_path' => $pathPdf,
                        'url'       => $urlToPdf,
                    ]);
                } catch (\Throwable) {}
            }

            // Notifica o usuário via banco
            $user = User::find($this->userId);
            if ($user) {
                try {
                    if (Schema::hasTable('notifications')) {
                        Notification::make()
                            ->title("📄 PDF do " . ucfirst($this->tipo) . " Gerado!")
                            ->success()
                            ->body("O arquivo já está pronto para download.")
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('download')
                                    ->button()
                                    ->url($urlToPdf, shouldOpenInNewTab: true),
                            ])
                            ->sendToDatabase($user);
                    }
                } catch (\Throwable $e) {
                    Log::warning("Notificação DB falhou: " . $e->getMessage());
                }
            }

            Log::info("PDF {$fileName} finalizado com sucesso.");

        } catch (\Throwable $e) {
            Log::error("Erro ao gerar PDF: " . $e->getMessage());

            // Atualiza registro com erro
            if ($pdfRecord) {
                try {
                    $pdfRecord->update([
                        'status'        => 'failed',
                        'error_message' => substr($e->getMessage(), 0, 500),
                    ]);
                } catch (\Throwable) {}
            }

            $user = User::find($this->userId);
            if ($user) {
                try {
                    if (Schema::hasTable('notifications')) {
                        Notification::make()
                            ->title("❌ Erro na Geração do PDF")
                            ->danger()
                            ->body("Falha ao gerar o documento.")
                            ->sendToDatabase($user);
                    }
                } catch (\Throwable) {}
            }

            throw $e;
        }
    }
}
