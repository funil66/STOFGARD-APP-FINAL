# Refatoração de PDFs - Sistema de Fila (6 de Abril de 2026)

## Resumo das Mudanças

### ✅ 1. Refatoração de Todos os Controllers de PDF

**Objetivo:** Remover geração síncrona de PDFs (que travava a tela) e implementar sistema de fila para geração assíncrona.

**Controllers Refatorados:**
1. OrcamentoPdfController
2. CadastroPdfController  
3. GarantiaPdfController
4. OrdemServicoPdfController
5. AgendaPdfController
6. FinanceiroPdfController (+ relatório mensal)
7. EstoquePdfController
8. TarefaPdfController
9. ProdutoPdfController
10. EquipamentoPdfController
11. ListaDesejoPdfController
12. TabelaPrecoPdfController
13. CategoriaPdfController
14. NotaFiscalPdfController

**Como Funciona Agora:**

```php
// ANTES: Retornava PDF imediatamente (síncrono)
return app(PdfService::class)->generate(
    'pdf.orcamento',
    ['data' => $data],
    'orcamento.pdf',
    true  // true = download direto
);

// DEPOIS: Enfileira geração (assíncrono)
return $this->enqueuePdf(
    'pdf.orcamento',
    ['orcamento' => $orcamento, 'config' => $config],
    'orcamento',
    $orcamento,
    ['cliente', 'itens']
);
```

**Benefícios:**
- ✅ Interface não congela mais durante geração de PDF
- ✅ PDFs grandes gerados em background
- ✅ VPS não sobrecarrega com múltiplas gerações simultâneas
- ✅ Notificações ao usuário quando PDF fica pronto
- ✅ Histórico de PDFs gerados em PdfGeracaoResource

### ✅ 2. Controller Base para Centralizar Lógica

**Arquivo:** `app/Http/Controllers/BasePdfQueueController.php`

**Métodos principais:**
- `loadConfig()`: Carrega configurações do sistema com JSON decodificação automática
- `enqueuePdf()`: Centraliza lógica de dispatch para fila com tratamento de erros

**Vantagens:**
- Evita duplicação de código entre 14 controllers
- Reduz bugs relacionados a carregamento de config
- Facilita manutenção futura

### ✅ 3. Padronização do Certificado de Garantia

**Arquivo:** `resources/views/pdf/certificado_garantia.blade.php`

**Mudanças:**
- ❌ Removido: Template rascunho com referências a `$orcamento` inexistente
- ✅ Adicionado: Template profissional baseado em padrão Orcamento

**Estrutura Nova:**
```
┌─────────────────────────────────────┐
│ HEADER FIXO                         │
│ (Logo empresa + Número OS + Datas) │
├─────────────────────────────────────┤
│ CLIENTE                             │
│ (Nome, Doc, Tel, Email, Endereço)  │
│                                     │
│ ORDEM DE SERVIÇO                    │
│ (Número OS, Orçamento, Datas)       │
│                                     │
│ CERTIFICADO DE GARANTIA             │
│ (Dias garantia, Validade, Tipo)     │
│                                     │
│ TERMOS E CONDIÇÕES                  │
│ (Cobertura, Exclusões, etc)         │
├─────────────────────────────────────┤
│ FOOTER FIXO                         │
│ (Dados empresa + informações)       │
└─────────────────────────────────────┘
```

**Dados Extraídos da OS:**
- Nome e contato do cliente
- Número de OS e Orçamento relacionado
- Datas (emissão, conclusão)
- Tipo de serviço da garantia
- Dias e período de validade
- Termos customizáveis

**Cores Dinâmicas:**
- Primária: `$config->pdf_color_primary` (#2563eb padrão)
- Secundária: `$config->pdf_color_secondary` (#eff6ff padrão)
- Texto: `$config->pdf_color_text` (#1f2937 padrão)

### ✅ 4. Integração com Sistema de Fila Existente

**Queue Path:**
```
User clica "Gerar PDF"
    ↓
Controller enfileira (PdfQueueService::enqueue)
    ↓
Redis recebe job na fila 'high' (prioridade)
    ↓
Worker Horizon processa (ProcessPdfJob)
    ↓
Notificação enviada ao usuário
    ↓
PDF disponível em PdfGeracaoResource
```

**Configurações:**
- **Fila:** Redis
- **Prioridade:** `high` (processado antes de default/low)
- **Timeout:** 120 segundos (ProcessPdfJob)
- **Retentativas:** 2
- **Sleep:** 1 segundo (workers polling rápido)

## Impacto nos Usuários

### Antes
```
1. Clica "Baixar PDF"
2. ⏳ Espera 30+ segundos (tela congela)
3. ✅ PDF baixa automaticamente
```

### Depois
```
1. Clica "Gerar PDF"
2. ⚡ Aviso: "PDF enfileirado!"
3. 🔄 Redirecionado para listagem de PDFs
4. 📧 Recebe notificação quando pronto
5. ✅ Clica para baixar PDF gerado
```

## Fluxo Técnico Completo

### 1. Dispatch (Imediato)
```php
// app/Http/Controllers/OrcamentoPdfController
public function gerarPdf(Orcamento $orcamento)
{
    $config = $this->loadConfig();
    
    return $this->enqueuePdf(
        'pdf.orcamento',
        ['orcamento' => $orcamento, 'config' => $config],
        'orcamento',
        $orcamento,
        ['cliente', 'itens']  // Carrega relacionamentos
    );
}
```

### 2. Enfileiramento (BasePdfQueueController::enqueuePdf)
```php
// 1. Renderiza HTML da view
$htmlContent = view($viewName, $viewData)->render();

// 2. Extrai orcamento_id se disponível
$orcamentoId = null;
if (method_exists($model, 'orcamento')) {
    $orcamentoId = $model->orcamento_id ?? null;
}

// 3. Enfileira em PdfQueueService
PdfQueueService::enqueue(
    $model->id,
    $type,
    auth()->id(),
    $htmlContent,
    $orcamentoId
);

// 4. Notifica usuário
Notification::make()
    ->title('🚀 PDF em Processamento')
    ->body('Você receberá uma notificação quando estiver pronto.')
    ->success()
    ->send();

// 5. Redireciona
return redirect()->route('filament.admin.resources.pdf-geracoes.index');
```

### 3. Processamento (Worker)
```php
// app/Jobs/ProcessPdfJob (via Horizon)
public function handle()
{
    // 1. Recupera HTML enfileirado
    $html = $this->htmlContent;
    
    // 2. Gera PDF via Browsershot
    $pdf = PdfService::generateFromHtml($html);
    
    // 3. Salva no storage
    Storage::put($filePath, $pdf);
    
    // 4. Atualiza tracking (PdfGeneration)
    $pdfRecord->update(['status' => 'done', 'file_path' => $filePath]);
    
    // 5. Envia notificação
    Notification::sendToUser('PDF pronto!');
}
```

### 4. Download (Usuário)
```
Acessa PdfGeracaoResource
    ↓
Vê lista de PDFs gerados (com status)
    ↓
Clica botão "Baixar"
    ↓
FileDownloadController faz stream seguro
    ↓
✅ PDF baixa normalmente
```

## Arquivos Modificados

### Controllers (14 arquivos)
- `app/Http/Controllers/OrcamentoPdfController.php`
- `app/Http/Controllers/CadastroPdfController.php`
- `app/Http/Controllers/GarantiaPdfController.php`
- `app/Http/Controllers/OrdemServicoPdfController.php`
- `app/Http/Controllers/AgendaPdfController.php`
- `app/Http/Controllers/FinanceiroPdfController.php`
- `app/Http/Controllers/EstoquePdfController.php`
- `app/Http/Controllers/TarefaPdfController.php`
- `app/Http/Controllers/ProdutoPdfController.php`
- `app/Http/Controllers/EquipamentoPdfController.php`
- `app/Http/Controllers/ListaDesejoPdfController.php`
- `app/Http/Controllers/TabelaPrecoPdfController.php`
- `app/Http/Controllers/CategoriaPdfController.php`
- `app/Http/Controllers/NotaFiscalPdfController.php`

### Novos Arquivos
- `app/Http/Controllers/BasePdfQueueController.php` (Nova classe base)

### Views (1 arquivo)
- `resources/views/pdf/certificado_garantia.blade.php` (Redesenhado)

## Testes Realizados

✅ **PdfQueueGarantiaTest** - Valida dispatch de garantia com parent_orcamento_id
✅ **Pdf Test Suite** - 6 testes validam subsistema PDF
✅ **Smoke Test** - PDF real gerado com 16KB+ de dados válidos
✅ **Job Handle Test** - ProcessPdfJob executa sem erros

## Próximos Passos (Opcional)

1. **Notificações Email** - Enviar email quando PDF ficar pronto
2. **Preview Web** - Mostrar PDF no navegador antes de baixar
3. **Agendamento** - Gerar PDFs em horários off-peak
4. **Compressão** - Comprimir PDFs grandes antes de armazenar
5. **Limpeza** - Remover PDFs com mais de 90 dias

## Configurações Importante

### config/queue.php
```php
'default' => env('QUEUE_CONNECTION', 'redis'),
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
],
```

### config/horizon.php
```php
'environments' => [
    'production' => [
        'supervisor' => [
            [
                'name' => 'pdf-high',
                'queue' => 'high',
                'balance' => 'auto',
                'processes' => 3,
                'timeout' => 120,
            ],
            // ... default, low queues
        ],
    ],
],
```

### stofgard-worker.conf (Supervisor)
```ini
command=php /var/www/html/artisan queue:work redis --queue=high,default,low --sleep=1
```

## Suporte

Para problemas:
1. Verificar logs: `storage/logs/laravel.log`
2. Verificar fila Redis: `redis-cli LLEN queue:high`
3. Verificar workers: `ps aux | grep queue:work`
4. Reiniciar Horizon: `php artisan horizon:terminate`
