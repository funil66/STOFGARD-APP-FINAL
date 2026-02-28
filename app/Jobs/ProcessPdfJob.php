<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Filament\Notifications\Notification;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Log;

class ProcessPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2; // PDF trava CPU, se der merda, não insista infinitamente.
    public $timeout = 120; // Se o Chrome enlouquecer, derruba em 2 minutos.

    protected $modeloId;
    protected $tipo; // ex: 'orcamento', 'os'
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
        Log::info("🚁 Iron Code: Iniciando geração de PDF pesada para o {$this->tipo} ID: {$this->modeloId}");

        $fileName = "{$this->tipo}_{$this->modeloId}_" . time() . ".pdf";
        $path = "pdfs/{$fileName}";
        $fullPath = storage_path("app/public/{$path}");

        // Garante que a pasta existe antes de salvar
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        try {
            // Renderização monstra do Browsershot
            Browsershot::html($this->htmlContent)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->setCustomTempPath(storage_path('app/temp')) // Adicionado para compatibilidade com o browserless
                ->save($fullPath);

            // Notifica o infeliz que pediu o PDF (Sino do Filament)
            $user = User::find($this->userId);
            if ($user) {
                Notification::make()
                    ->title("📄 PDF do " . ucfirst($this->tipo) . " Gerado com Sucesso!")
                    ->success()
                    ->body("O arquivo já está pronto para download.")
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('download')
                            ->button()
                            ->url(asset("storage/{$path}"), shouldOpenInNewTab: true),
                    ])
                    ->sendToDatabase($user);
            }

            Log::info("🎯 Headshot: PDF {$fileName} finalizado com sucesso.");

        } catch (\Exception $e) {
            Log::error("💣 C4 Explodiu ao gerar PDF: " . $e->getMessage());

            $user = User::find($this->userId);
            if ($user) {
                Notification::make()
                    ->title("❌ Erro na Geração do PDF")
                    ->danger()
                    ->body("Falha ao gerar o documento. O suporte já foi notificado.")
                    ->sendToDatabase($user);
            }

            throw $e; // Joga a exceção pra cima pro Laravel marcar o Job como "Failed"
        }
    }
}
