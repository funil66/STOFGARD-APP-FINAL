<?php

namespace App\Observers;

use App\Models\Agenda;
use App\Models\Estoque;
use App\Models\OrdemServico;
use App\Jobs\EnviarSolicitacaoAvaliacaoJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdemServicoObserver
{
    /**
     * Handle the OrdemServico "saving" event.
     */
    public function saving(OrdemServico $ordemServico): void
    {
        // Se a latitude foi preenchida agora e o IP ainda está vazio
        if ($ordemServico->isDirty('checkin_latitude') && empty($ordemServico->checkin_ip)) {
            $ordemServico->checkin_ip = request()->ip();
        }
    }
    /**
     * Handle the OrdemServico "created" event.
     */
    public function created(OrdemServico $os): void
    {
        // Verifica se já existe uma agenda para esta OS
        // (evita duplicação quando criada via aprovação de orçamento que já lida com a criação de agenda e financeiro no StofgardSystem)
        if ($os->orcamento_id) {
            return; // Veio de um orçamento: StofgardSystem/OrdemServicoService já cuidam disso.
        }

        DB::transaction(function () use ($os) {
            // 1. Cria a Receita no Financeiro automaticamente (Lançamento Pendente)
            // Calcula total a partir dos itens + extra_attributes (fallback caso valor_total esteja incorreto)
            $itensTotal = collect($os->itens ?? [])->sum(function ($i) { return floatval($i->subtotal ?? 0); });
            $extrasTotal = 0;
            if (is_array($os->extra_attributes) || $os->extra_attributes instanceof \Illuminate\Support\Collection) {
                foreach ($os->extra_attributes as $k => $v) {
                    if (is_numeric($v)) {
                        $extrasTotal += floatval($v);
                        continue;
                    }
                    if (is_string($v) && strlen(trim((string) $v)) > 0) {
                        $normalized = str_replace(['R$', ' ', '\\u00A0', '\\xc2\\xa0'], ['', '', '', ''], $v);
                        $normalized = str_replace('.', '', $normalized);
                        $normalized = str_replace(',', '.', $normalized);
                        $extrasTotal += floatval($normalized);
                    }
                }
            }

            $computedTotal = $itensTotal + $extrasTotal;
            $valorParaFinanceiro = ($computedTotal > 0) ? $computedTotal : ($os->valor_total ?? 0);

            \App\Models\Financeiro::create([
                'cadastro_id' => $os->cadastro_id,
                'ordem_servico_id' => $os->id,
                'id_parceiro' => $os->id_parceiro,
                'tipo' => 'entrada',
                'descricao' => "Receita ref. OS #{$os->numero_os} - " . ($os->cliente->nome ?? 'Cliente'),
                'valor' => $valorParaFinanceiro,
                'data_vencimento' => $os->data_prevista ?? now()->addDays(2),
                'data' => now(),
                'status' => 'pendente',
            ]);

            // 2. Cria a Agenda com cores e descrições aprimoradas
            $tipoServico = \App\Services\ServiceTypeManager::getLabel($os->tipo_servico ?? 'servico');

            $agendaExistente = Agenda::where('ordem_servico_id', $os->id)->exists();

            if (!$agendaExistente) {
                Agenda::create([
                    'titulo' => "Serviço - OS #{$os->numero_os}",
                    'descricao' => "{$tipoServico}\nCliente: " . ($os->cliente->nome ?? 'N/A') . "\n" . ($os->descricao_servico ?? ''),
                    'cadastro_id' => $os->cadastro_id,
                    'ordem_servico_id' => $os->id,
                    'tipo' => 'servico',
                    'data_hora_inicio' => $os->data_prevista ? \Carbon\Carbon::parse($os->data_prevista)->setTime(8, 0) : now()->addDays(1)->setTime(8, 0),
                    'data_hora_fim' => $os->data_prevista ? \Carbon\Carbon::parse($os->data_prevista)->setTime(18, 0) : now()->addDays(1)->setTime(10, 0),
                    'status' => 'agendado',
                    'local' => $os->cliente->cidade ?? ($os->cliente->endereco ?? 'A definir'),
                    'endereco_completo' => $os->cliente->endereco_completo ?? null,
                    'cor' => '#22c55e', // Verde para serviços
                    'criado_por' => Auth::id() ?? 1,
                ]);
            }
        });
    }

    /**
     * Handle the OrdemServico "updated" event.
     * Cria garantia automaticamente ao concluir a OS.
     * Fase 4: Dispara solicitação de avaliação GMB 24h após conclusão + pagamento.
     */
    public function updated(OrdemServico $os): void
    {
        // Só cria garantia na primeira conclusão
        if ($os->status === 'concluida' && $os->wasChanged('status')) {
            $this->criarGarantiaAutomatica($os);

            // === FASE 4: Solicitação de Avaliação GMB ===
            // Se já está pago, agenda o ZAP de avaliação para 24h depois.
            // Se ainda não está pago, o FinanceiroObserver/webhook cuida disso.
            $financeiro = $os->financeiro;
            if ($financeiro && $financeiro->status === 'pago') {
                EnviarSolicitacaoAvaliacaoJob::dispatch($os->id)
                    ->delay(now()->addHours(24));

                Log::info('[OrdemServicoObserver] Job de avaliação GMB agendado para 24h', [
                    'os_id' => $os->id,
                    'os_numero' => $os->numero_os,
                ]);
            }
        }
    }

    /**
     * Cria garantia automaticamente baseada nas configurações do sistema.
     */
    protected function criarGarantiaAutomatica(OrdemServico $os): void
    {
        try {
            $tiposServico = collect($os->itens ?? [])
                ->map(fn ($item) => $item->servico_tipo)
                ->filter(fn ($tipo) => filled($tipo))
                ->unique()
                ->values();

            if ($tiposServico->isEmpty()) {
                $tiposServico = collect([$os->tipo_servico ?? 'servico']);
            }

            $garantiasCriadas = 0;
            $diasResumo = [];
            $perfilResumoId = null;

            foreach ($tiposServico as $tipoServico) {
                $jaExiste = $os->garantias()->where('tipo_servico', $tipoServico)->exists();
                if ($jaExiste) {
                    continue;
                }

                $perfil = null;
                $perfilId = \App\Services\ServiceTypeManager::getPerfilGarantiaId((string) $tipoServico);
                if ($perfilId) {
                    $perfil = \App\Models\PerfilGarantia::find($perfilId);
                }

                if (!$perfil && $os->perfilGarantia && $os->tipo_servico === $tipoServico) {
                    $perfil = $os->perfilGarantia;
                }

                if (!$perfil) {
                    Log::info("Perfil de garantia não configurado para serviço '{$tipoServico}', pulando criação da garantia.");
                    continue;
                }

                $dias = (int) ($perfil->dias_garantia ?? 0);
                if (empty($dias)) {
                    Log::info("Perfil '{$perfil->nome}' sem dias de garantia para serviço '{$tipoServico}', pulando criação.");
                    continue;
                }

                $labelServico = \App\Services\ServiceTypeManager::getLabel((string) $tipoServico);
                $descricao = "Perfil {$perfil->nome} aplicado para {$labelServico}";

                \App\Models\Garantia::create([
                    'ordem_servico_id' => $os->id,
                    'tipo_servico' => $tipoServico,
                    'data_inicio' => now(),
                    'dias_garantia' => $dias,
                    'data_fim' => now()->addDays($dias),
                    'status' => 'ativa',
                    'observacoes' => $descricao,
                ]);

                $garantiasCriadas++;
                $diasResumo[] = $dias;
                if (!$perfilResumoId) {
                    $perfilResumoId = $perfil->id;
                }

                Log::info("Garantia de {$dias} dias criada automaticamente para OS {$os->numero_os} ({$tipoServico})");
            }

            if ($garantiasCriadas > 0) {
                $os->forceFill([
                    'perfil_garantia_id' => $os->perfil_garantia_id ?: $perfilResumoId,
                    'dias_garantia' => !empty($diasResumo) ? max($diasResumo) : $os->dias_garantia,
                ])->saveQuietly();
            }

            try {
                $settingsArray = \App\Models\Setting::pluck('value', 'key')->toArray();
                $companyIdentity = company_pdf_identity();
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
                $tenantConfig = \App\Models\Configuracao::query()
                    ->whereNotNull('empresa_nome')
                    ->where('empresa_nome', '!=', '')
                    ->latest('id')
                    ->first();

                if (!$tenantConfig) {
                    $tenantConfig = \App\Models\Configuracao::query()->latest('id')->first();
                }

                if ($tenantConfig) {
                    $config->empresa_logo = $companyIdentity['empresa_logo'] ?? $config->empresa_logo ?? $tenantConfig->empresa_logo ?? null;
                    $config->empresa_nome = $companyIdentity['empresa_nome'] ?? $config->empresa_nome ?? $tenantConfig->empresa_nome ?? null;
                    $config->empresa_cnpj = $companyIdentity['empresa_cnpj'] ?? $config->empresa_cnpj ?? $tenantConfig->empresa_cnpj ?? null;
                    $config->empresa_telefone = $companyIdentity['empresa_telefone'] ?? $config->empresa_telefone ?? $tenantConfig->empresa_telefone ?? null;
                    $config->empresa_email = $companyIdentity['empresa_email'] ?? $config->empresa_email ?? $tenantConfig->empresa_email ?? null;
                }
                
                // Get one garantia as referência para o PDF
                $garantia = $os->garantias()->latest()->first();

                // Disparar job invisível para gerar PDF
                $htmlContent = view('pdf.certificado_garantia', [
                    'os' => $os,
                    'garantia' => $garantia,
                    'orcamento' => $os->orcamento,
                    'config' => $config
                ])->render();

                \App\Services\PdfQueueService::enqueue(
                    $os->id,
                    'garantia',
                    $os->criado_por ?? 1,
                    $htmlContent,
                    $os->orcamento_id
                );
            } catch (\Exception $e) {
                Log::error("Erro ao despachar job de PDF da garantia para OS {$os->numero_os}: " . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error("Erro ao criar garantia automática para OS {$os->numero_os}: " . $e->getMessage());
        }
    }

    /**
     * Handle the OrdemServico "saved" event (after create or update).
     * Gerencia baixas e estornos de produtos do estoque.
     */
    public function saved(OrdemServico $ordemServico): void
    {
        // Verifica se há dados de produtos no request
        if (!request()->has('produtosUtilizados') && !request()->has('data.produtosUtilizados')) {
            return;
        }

        try {
            DB::transaction(function () use ($ordemServico) {
                // Recarrega produtos atuais do banco
                $produtosAtuais = $ordemServico->produtosUtilizados()
                    ->withPivot('quantidade_utilizada')
                    ->get()
                    ->keyBy('id');

                // Obtém produtos do request (suporta ambos formatos: direto ou dentro de 'data')
                $produtosRequest = request('produtosUtilizados', request('data.produtosUtilizados', []));
                $produtosNovos = collect($produtosRequest)->keyBy('estoque_id');

                // ESTORNAR produtos removidos
                foreach ($produtosAtuais as $produtoAtual) {
                    if (!$produtosNovos->has($produtoAtual->id)) {
                        Log::info("Estornando produto {$produtoAtual->item}: {$produtoAtual->pivot->quantidade_utilizada}");
                        $produtoAtual->estornar($produtoAtual->pivot->quantidade_utilizada);
                    }
                }

                // PROCESSAR produtos novos ou alterados
                foreach ($produtosNovos as $estoqueId => $dados) {
                    $estoque = Estoque::findOrFail($estoqueId);
                    $quantidadeNova = (float) $dados['quantidade_utilizada'];

                    if ($produtosAtuais->has($estoqueId)) {
                        // Produto já existia - ajustar diferença
                        $quantidadeAntiga = $produtosAtuais[$estoqueId]->pivot->quantidade_utilizada;
                        $diferenca = $quantidadeNova - $quantidadeAntiga;

                        if ($diferenca > 0) {
                            Log::info("Aumentando baixa do produto {$estoque->item}: diferença de {$diferenca}");
                            $estoque->darBaixa($diferenca);
                        } elseif ($diferenca < 0) {
                            Log::info("Estornando parcialmente produto {$estoque->item}: {" . abs($diferenca) . '}');
                            $estoque->estornar(abs($diferenca));
                        }
                    } else {
                        // Produto novo - dar baixa total
                        Log::info("Dando baixa em novo produto {$estoque->item}: {$quantidadeNova}");
                        $estoque->darBaixa($quantidadeNova);
                    }
                }
            });
        } catch (\Exception $e) {
            // Log do erro mas não interrompe o salvamento da OS
            Log::error("Erro ao processar estoque da OS {$ordemServico->numero_os}: " . $e->getMessage());

            // Re-lança a exception para que o usuário saiba do problema
            throw $e;
        }
    }

    /**
     * Handle the OrdemServico "deleting" event.
     * Estorna todos os produtos antes de deletar a OS.
     */
    public function deleting(OrdemServico $ordemServico): void
    {
        try {
            DB::transaction(function () use ($ordemServico) {
                // Carrega produtos antes de deletar
                $produtos = $ordemServico->produtosUtilizados()
                    ->withPivot('quantidade_utilizada')
                    ->get();

                foreach ($produtos as $produto) {
                    Log::info("Estornando produto {$produto->item} da OS deletada: {$produto->pivot->quantidade_utilizada}");
                    $produto->estornar($produto->pivot->quantidade_utilizada);
                }
            });
        } catch (\Exception $e) {
            Log::error("Erro ao estornar produtos da OS {$ordemServico->numero_os} ao deletar: " . $e->getMessage());
            throw $e;
        }
    }
}
