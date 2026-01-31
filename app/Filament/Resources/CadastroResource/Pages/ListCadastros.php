<?php

namespace App\Filament\Resources\CadastroResource\Pages;

use App\Filament\Resources\CadastroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCadastros extends ListRecords
{
    protected static string $resource = CadastroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('importar')
                ->label('Importar Contatos')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('arquivo')
                        ->label('Arquivo de Contatos (.vcf)')
                        ->helperText('Suporta arquivos exportados do Google, Android ou iPhone')
                        ->acceptedFileTypes(['text/vcard', 'text/x-vcard', '.vcf', 'application/octet-stream']) // octet-stream as vezes vem do upload
                        ->storeFiles(true)
                        ->disk('local')
                        ->directory('imports')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $caminho = storage_path('app/' . $data['arquivo']);
                    if (!file_exists($caminho)) {
                        \Filament\Notifications\Notification::make()->title('Erro ao ler arquivo')->danger()->send();
                        return;
                    }

                    $conteudo = file_get_contents($caminho);

                    // Regex simples para separar VCARDS
                    // PADRÃO: BEGIN:VCARD ... END:VCARD
                    preg_match_all('/BEGIN:VCARD(.*?)END:VCARD/s', $conteudo, $matches);

                    $contatos = $matches[1] ?? [];
                    $importados = 0;

                    foreach ($contatos as $bloco) {
                        // Extrai Nome (FN)
                        if (preg_match('/FN:(.*?)\r?\n/', $bloco, $fn)) {
                            $nome = trim($fn[1]);
                        } else {
                            continue; // Sem nome, ignora
                        }

                        // Extrai Telefone (TEL) - pega o primeiro
                        if (preg_match('/TEL.*:(.*?)\r?\n/', $bloco, $tel)) {
                            $telefone = trim($tel[1]);
                            // Limpa caracteres especiais do telefone
                            $telefone = preg_replace('/[^0-9]/', '', $telefone);
                        } else {
                            $telefone = null;
                        }

                        // Verifica duplicidade pelo telefone (se existir) ou nome
                        if ($telefone) {
                            $existe = \App\Models\Cadastro::where('celular', 'LIKE', "%$telefone%")->exists();
                        } else {
                            $existe = \App\Models\Cadastro::where('nome', $nome)->exists();
                        }

                        if (!$existe) {
                            \App\Models\Cadastro::create([
                                'nome' => $nome,
                                'celular' => $telefone,
                                'tipo' => 'cliente',
                                'observacoes' => 'Importado via VCF em ' . date('d/m/Y'),
                            ]);
                            $importados++;
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->title("Importação Concluída")
                        ->body("$importados novos contatos importados com sucesso!")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'clientes' => \Filament\Resources\Components\Tab::make('Clientes')
                ->modifyQueryUsing(fn($query) => $query->where('tipo', 'cliente'))
                ->icon('heroicon-m-user'),
            'parceiros' => \Filament\Resources\Components\Tab::make('Parceiros e Lojas')
                ->modifyQueryUsing(fn($query) => $query->whereIn('tipo', ['loja', 'vendedor']))
                ->icon('heroicon-m-briefcase'),
            'todos' => \Filament\Resources\Components\Tab::make('Todos'),
        ];
    }
}
