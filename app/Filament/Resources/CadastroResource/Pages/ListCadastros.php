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
                        ->acceptedFileTypes(['text/vcard', 'text/x-vcard', '.vcf', 'text/csv', '.csv', 'application/octet-stream']) // octet-stream as vezes vem do upload
                        ->storeFiles(true)
                        ->disk('local')
                        ->directory('imports')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // RESOLUÇÃO DE CAMINHO CORRETA USANDO DISK
                    $storage = \Illuminate\Support\Facades\Storage::disk('local');
                    $pathRelativo = $data['arquivo'];

                    if (!$storage->exists($pathRelativo)) {
                        \Filament\Notifications\Notification::make()->title('Erro ao ler arquivo')->body("Arquivo não encontrado: $pathRelativo")->danger()->send();
                        return;
                    }

                    $caminhoAbsoluto = $storage->path($pathRelativo);
                    $extensao = strtolower(pathinfo($caminhoAbsoluto, PATHINFO_EXTENSION));

                    $importados = 0;

                    set_time_limit(0); // Para arquivos grandes
        
                    if ($extensao === 'csv') {
                        // --- IMPORTAÇÃO CSV ---
                        // Verifica encoding para evitar caracteres quebrados (comum em Excel)
                        // Se não for UTF-8 válido, tenta Win-1252
                        $content = file_get_contents($caminhoAbsoluto);
                        if (!mb_check_encoding($content, 'UTF-8')) {
                            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
                            file_put_contents($caminhoAbsoluto, $content);
                        }
                        unset($content); // Libera memória
        
                        $handle = fopen($caminhoAbsoluto, 'r');
                        if ($handle !== false) {
                            $header = fgetcsv($handle, 1000, ',');

                            // Tenta detectar delimitador se falhou (ex: ponto e virgula)
                            if (!$header || count($header) < 2) {
                                rewind($handle);
                                $header = fgetcsv($handle, 1000, ';');
                                $delimiter = ';';
                            } else {
                                $delimiter = ',';
                            }

                            // Normaliza header para lower case para facilitar busca
                            $header = array_map(fn($h) => strtolower(trim($h)), $header ?? []);

                            // --- LÓGICA DE PONTUAÇÃO PARA COLUNAS CSV ---
                            // Função auxiliar definida inline ou lógica direta
                            $getBestColumn = function ($header, $keywords, $excludeKeywords = []) {
                                $bestIdx = -1;
                                $bestScore = -1;
                                foreach ($header as $i => $col) {
                                    $score = 0;
                                    $colLower = strtolower($col);
                                    foreach ($excludeKeywords as $exclude) {
                                        if (str_contains($colLower, $exclude))
                                            continue 2;
                                    }
                                    foreach ($keywords as $keyword => $weight) {
                                        if (str_contains($colLower, $keyword))
                                            $score += $weight;
                                    }
                                    if (str_contains($colLower, 'value'))
                                        $score += 2;
                                    if (str_contains($colLower, 'label'))
                                        $score -= 5;
                                    // Exact match bonus
                                    if (in_array($colLower, ['name', 'nome', 'fn', 'display name']))
                                        $score += 5;

                                    if ($score > $bestScore && $score > 0) {
                                        $bestScore = $score;
                                        $bestIdx = $i;
                                    }
                                }
                                return $bestIdx;
                            };

                            // Busca melhores índices
                            $idxNome = $getBestColumn($header, ['name' => 1, 'nome' => 1, 'fn' => 1, 'first' => 2, 'cliente' => 1], ['organization', 'phonetic', 'file as', 'prefix', 'suffix', 'nickname']);
                            $idxTel = $getBestColumn($header, ['tel' => 1, 'cel' => 2, 'phone' => 1, 'mobile' => 2, 'whatsapp' => 2], ['label']);
                            $idxEmail = $getBestColumn($header, ['email' => 1, 'mail' => 1, 'e-mail' => 1], ['label']);

                            // Fallback de ordem padrão se nada for detectado
                            if ($idxNome === -1 && count($header) >= 1)
                                $idxNome = 0;
                            if ($idxTel === -1 && count($header) >= 2)
                                $idxTel = 1;

                            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                                if (count($row) < 1)
                                    continue;

                                $nome = ($idxNome >= 0 && isset($row[$idxNome])) ? trim($row[$idxNome]) : null;
                                $telefone = ($idxTel >= 0 && isset($row[$idxTel])) ? trim($row[$idxTel]) : null;
                                $email = ($idxEmail >= 0 && isset($row[$idxEmail])) ? trim($row[$idxEmail]) : null;

                                $telefoneLimpo = $telefone ? preg_replace('/[^0-9]/', '', $telefone) : null;

                                if (!$nome && !$telefoneLimpo && !$email)
                                    continue;

                                // Se não tem nome mas tem email, usa parte do email
                                if (!$nome && $email) {
                                    $parts = explode('@', $email);
                                    $nome = ucfirst($parts[0]);
                                }

                                // Tenta encontrar existente para ATUALIZAR
                                $cadastroExistente = null;

                                if ($email) {
                                    $cadastroExistente = \App\Models\Cadastro::where('email', $email)->first();
                                }

                                if (!$cadastroExistente && $nome) {
                                    $cadastroExistente = \App\Models\Cadastro::where('nome', $nome)->first();
                                }

                                // Se encontrou, atualiza dados faltantes
                                if ($cadastroExistente) {
                                    $adicionou = false;
                                    if (empty($cadastroExistente->celular) && $telefoneLimpo) {
                                        $cadastroExistente->celular = $telefoneLimpo;
                                        $cadastroExistente->telefone = $telefoneLimpo; // Sync visual
                                        $adicionou = true;
                                    }
                                    if (empty($cadastroExistente->email) && $email) {
                                        $cadastroExistente->email = $email;
                                        $adicionou = true;
                                    }
                                    if ($adicionou) {
                                        $cadastroExistente->save();
                                        $importados++;
                                    }
                                } else {
                                    // Se não encontrou, cria novo
                                    // Evita criar duplicado APENAS pelo telefone se já existir (casos raros)
                                    $existeTel = $telefoneLimpo ? \App\Models\Cadastro::where('celular', 'LIKE', "%$telefoneLimpo%")->exists() : false;

                                    if (!$existeTel) {
                                        \App\Models\Cadastro::create([
                                            'nome' => $nome ?? 'Sem Nome',
                                            'celular' => $telefoneLimpo,
                                            'telefone' => $telefoneLimpo, // Sync visual
                                            'email' => $email,
                                            'tipo' => 'cliente',
                                            'observacoes' => 'Importado via CSV em ' . date('d/m/Y'),
                                        ]);
                                        $importados++;
                                    }
                                }
                            }
                            fclose($handle);
                        }

                    } else {
                        // --- IMPORTAÇÃO VCARD (Sabre\VObject) ---
                        try {
                            // Use Splitter to handle multiple VCards in the same file
                            $splitter = new \Sabre\VObject\Splitter\VCard(fopen($caminhoAbsoluto, 'r'));
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro ao processar arquivo')
                                ->body('O arquivo não parece ser um VCard válido.')
                                ->danger()
                                ->send();
                            return;
                        }

                        while ($card = $splitter->getNext()) {
                            // Extrai Nome (FN) - Prioriza FN, depois N (formatado)
                            $nome = isset($card->FN) ? (string) $card->FN : null;
                            if (!$nome && isset($card->N)) {
                                $nome = (string) $card->N;
                                // Se N for apenas ";;;;" ou vazio
                                if (trim(str_replace(';', '', $nome)) === '')
                                    $nome = null;
                            }

                            // Extrai Email
                            $email = null;
                            if (isset($card->EMAIL)) {
                                foreach ($card->EMAIL as $e) {
                                    $email = (string) $e;
                                    break; // Pega o primeiro
                                }
                            }

                            // Extrai Telefone (TEL)
                            $telefone = null;
                            if (isset($card->TEL)) {
                                // Itera sobre os telefones para achar celular ou pegar o primeiro que servir
                                foreach ($card->TEL as $telProperty) {
                                    $telValue = (string) $telProperty;

                                    // Determina tipos (CELL, HOME, WORK, etc.)
                                    $types = $telProperty['TYPE'] ?? [];
                                    if (!is_array($types))
                                        $types = [$types];
                                    $types = array_map('strtoupper', $types);

                                    // Verifica labels customizados (Google Contacts usa itemX.X-ABLabel)
                                    // Ex: item1.TEL e item1.X-ABLabel:Celular
                                    $group = $telProperty->group;
                                    if ($group) {
                                        foreach ($card->select("X-ABLabel") as $labelProp) {
                                            if ($labelProp->group === $group) {
                                                $labelVal = strtoupper((string) $labelProp);
                                                if (str_contains($labelVal, 'CEL') || str_contains($labelVal, 'MOB')) {
                                                    $types[] = 'CELL';
                                                }
                                            }
                                        }
                                    }

                                    // Prioridade: CELL
                                    if (in_array('CELL', $types)) {
                                        $telefone = $telValue; // Overwrite previous candidates
                                        break; // Encontrou o melhor possível
                                    }

                                    // Se ainda não temos nenhum telefone, aceita qualquer um
                                    if (!$telefone) {
                                        $telefone = $telValue;
                                    }
                                }
                            }

                            // Limpa telefone
                            $telefoneLimpo = null;
                            if ($telefone) {
                                $telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);
                            }

                            if (!$nome && !$telefoneLimpo && !$email) {
                                continue; // Nada útil
                            }

                            // Se não tem nome mas tem email, usa parte do email
                            if (!$nome && $email) {
                                $parts = explode('@', $email);
                                $nome = ucfirst($parts[0]);
                            }

                            // Tenta encontrar existente para ATUALIZAR
                            $cadastroExistente = null;
                            if ($email) {
                                $cadastroExistente = \App\Models\Cadastro::where('email', $email)->first();
                            }
                            if (!$cadastroExistente && $nome) {
                                $cadastroExistente = \App\Models\Cadastro::where('nome', $nome)->first();
                            }

                            // Se encontrou, atualiza dados faltantes
                            if ($cadastroExistente) {
                                $adicionou = false;
                                if (empty($cadastroExistente->celular) && $telefoneLimpo) {
                                    $cadastroExistente->celular = $telefoneLimpo;
                                    $cadastroExistente->telefone = $telefoneLimpo;
                                    $adicionou = true;
                                }
                                if (empty($cadastroExistente->email) && $email) {
                                    $cadastroExistente->email = $email;
                                    $adicionou = true;
                                }
                                if ($adicionou) {
                                    $cadastroExistente->save();
                                    $importados++;
                                }
                            } else {
                                // Cria novo se não existir
                                $existeTel = $telefoneLimpo ? \App\Models\Cadastro::where('celular', 'LIKE', "%$telefoneLimpo%")->exists() : false;

                                if (!$existeTel) {
                                    \App\Models\Cadastro::create([
                                        'nome' => $nome ?? 'Sem Nome',
                                        'celular' => $telefoneLimpo,
                                        'telefone' => $telefoneLimpo,
                                        'email' => $email,
                                        'tipo' => 'cliente',
                                        'observacoes' => 'Importado via VCF em ' . date('d/m/Y'),
                                    ]);
                                    $importados++;
                                }
                            }
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
