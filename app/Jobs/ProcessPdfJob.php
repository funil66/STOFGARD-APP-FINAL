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

    public function __construct($modeloId, $tipo, $userId, $htmlContent)
    {
        $this->modeloId = $modeloId;
        $this->tipo = $tipo;
        $this->userId = $userId;
        $this->htmlContent = $htmlContent;
    }

    public function handle()
    {
        Log::info("Iniciando geração de PDF para {$this->tipo} ID: {$this->modeloId}");

        $fileName = "{$this->tipo}_{$this->modeloId}_" . time() . ".pdf";
        $path = "pdfs/{$fileName}";
        $fullPath = storage_path("app/public/{$path}");

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Registra na tabela de acompanhamento (se existir)
        $pdfRecord = null;
        try {
            if (Schema::hasTable('pdf_generations')) {
                $pdfRecord = PdfGeneration::create([
                    'tipo'      => $this->tipo,
                    'modelo_id' => (string) $this->modeloId,
                    'user_id'   => $this->userId,
                    'status'    => 'processing',
                    'orcamento_id' => $this->tipo === 'orcamento' ? $this->modeloId : null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Não foi possível registrar PDF em pdf_generations: " . $e->getMessage());
        }

        try {
            $pdf = app(\App\Services\PdfService::class)->generateFromHtml(
                $this->htmlContent,
                $fileName,
                'a4',
                'portrait'
            );

            $content = base64_decode($pdf->base64());
            Storage::disk('public')->put($path, $content);

            // Atualiza registro com sucesso
            if ($pdfRecord) {
                try {
                    $pdfRecord->update([
                        'status'    => 'done',
                        'file_path' => $path,
                        'url'       => asset("storage/{$path}"),
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
                                    ->url(asset("storage/{$path}"), shouldOpenInNewTab: true),
                            ])
                            ->sendToDatabase($user);
                    }
                } catch (\Throwable $e) {
                    Log::warning("Notificação DB falhou: " . $e->getMessage());
                }
            }

            Log::info("PDF {$fileName} finalizado com sucesso.");

        } catch (\Exception $e) {
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
