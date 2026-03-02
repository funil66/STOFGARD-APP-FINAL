# 🏗️ Arquitetura do Sistema AUTONOMIA ILIMITADA

Este documento descreve a arquitetura técnica do sistema de gestão AUTONOMIA ILIMITADA.

## 📊 Visão Geral da Arquitetura

```mermaid
graph TB
    subgraph "🌐 Frontend"
        LP[Landing Page<br/>Leads]
        FP[Filament Panel<br/>Admin]
    end

    subgraph "🛣️ Routes"
        WEB[web.php]
        API[api.php]
    end

    subgraph "🎯 Controllers"
        LC[LeadController]
        OPC[OrcamentoPdfController]
        FDC[FileDownloadController]
        PC[PagamentoController]
    end

    subgraph "⚙️ Services"
        LS[LeadService]
        OC[OrcamentoCalculator]
        PS[Pix/PixMasterService]
        GCS[GoogleCalendarService]
        WS[WeatherService]
    end

    subgraph "📦 Models"
        CAD[Cadastro]
        ORC[Orcamento]
        OS[OrdemServico]
        FIN[Financeiro]
        CAT[Categoria]
        AG[Agenda]
    end

    subgraph "🗄️ Database"
        DB[(SQLite/MySQL)]
    end

    LP --> WEB
    FP --> WEB
    WEB --> LC
    WEB --> OPC
    WEB --> FDC
    WEB --> PC
    LC --> LS
    OPC --> OC
    OC --> PS
    LS --> CAD
    LS --> ORC
    CAD --> DB
    ORC --> DB
    OS --> DB
    FIN --> DB
```

## 🔄 Fluxo Principal de Negócio

```mermaid
sequenceDiagram
    participant C as Cliente (Site)
    participant L as LeadService
    participant O as Orçamento
    participant S as OS
    participant F as Financeiro
    participant A as Agenda

    C->>L: Solicita orçamento
    L->>L: Valida dados
    L->>O: Cria orçamento (etapa: novo)
    
    Note over O: Status: rascunho → enviado → aprovado
    
    O->>S: Gera OS (quando aprovado)
    S->>A: Cria agenda do serviço
    S->>F: Gera lançamento financeiro
    
    Note over F: Status: pendente → pago
```

## 📁 Estrutura de Diretórios Relevantes

```
app/
├── Console/Commands/        # Comandos Artisan customizados
├── Filament/
│   └── Resources/           # CRUD Filament (Financeiro, Orcamento, OS)
├── Http/Controllers/        # Controllers tradicionais (PDF, Webhooks)
├── Models/                  # Eloquent Models
├── Services/                # Lógica de negócio isolada
│   ├── LeadService.php      # Captação de leads
│   ├── OrcamentoCalculator.php  # Matemática financeira
│   └── Pix/                 # Integração PIX EFI/Gerencianet
├── Policies/                # Autorização
└── Traits/                  # Traits reutilizáveis

config/
├── backup.php               # Configuração Spatie Backup
├── browsershot.php          # Geração de PDFs
└── services.php             # Chaves de APIs externas

database/
├── migrations/              # Estrutura do banco
└── seeders/                 # Dados iniciais

docs/                        # Documentação
routes/
├── web.php                  # Rotas web (limpo, sem lógica)
└── api.php                  # API (webhooks PIX)
```

## 🔗 Relacionamentos dos Models Principais

```mermaid
erDiagram
    CADASTRO ||--o{ ORCAMENTO : "solicita"
    CADASTRO ||--o{ ORDEM_SERVICO : "recebe"
    CADASTRO ||--o{ FINANCEIRO : "paga/recebe"
    CADASTRO ||--o{ CADASTRO : "parent (loja→vendedor)"
    
    ORCAMENTO ||--o| ORDEM_SERVICO : "gera"
    ORCAMENTO ||--o{ ORCAMENTO_ITEM : "contém"
    
    ORDEM_SERVICO ||--o{ OS_ITEM : "contém"
    ORDEM_SERVICO ||--o| AGENDA : "agenda"
    ORDEM_SERVICO ||--o{ FINANCEIRO : "gera"
    
    CATEGORIA ||--o{ FINANCEIRO : "categoriza"
    
    CADASTRO {
        int id PK
        string tipo "cliente|loja|vendedor|parceiro"
        string nome
        int parent_id FK "hierarquia"
        decimal comissao_percentual
    }
    
    ORCAMENTO {
        int id PK
        int cadastro_id FK
        string numero
        string status "rascunho|enviado|aprovado"
        string etapa_funil "novo|negociando|fechado"
        decimal valor_total
        int vendedor_id FK
        int loja_id FK
    }
    
    ORDEM_SERVICO {
        int id PK
        string numero_os
        int cadastro_id FK
        int orcamento_id FK
        string status "aberta|concluida"
        date data_servico
    }
    
    FINANCEIRO {
        int id PK
        int cadastro_id FK
        int orcamento_id FK
        int ordem_servico_id FK
        int categoria_id FK
        string tipo "entrada|saida"
        decimal valor
        string status "pendente|pago"
        boolean is_comissao
    }
```

## 🔧 Comandos Artisan Customizados

### `php artisan iron:check`
Verifica a integridade do ambiente:
- Conexão com banco de dados
- Configurações de PIX
- Chaves de API (Google, Weather)
- Permissões de diretórios

### `php artisan backup:run`
Executa backup completo:
- Banco de dados
- Arquivos de storage
- Envio para disco externo (se configurado)

### `php artisan queue:work`
Processa filas em background:
- Envio de emails
- Geração de PDFs
- Sincronização Google Calendar

## 🔐 Variáveis de Ambiente Importantes

```env
# ===========================================
# BANCO DE DADOS
# ===========================================
DB_CONNECTION=sqlite          # ou mysql em produção
DB_DATABASE=/path/to/database.sqlite

# ===========================================
# INTEGRAÇÃO PIX (EFI/Gerencianet)
# ===========================================
EFI_CLIENT_ID=seu_client_id
EFI_CLIENT_SECRET=seu_client_secret
EFI_SANDBOX=true              # false em produção
EFI_PIX_KEY=sua_chave_pix
EFI_CERTIFICATE_PATH=/path/to/certificado.pem

# ===========================================
# GOOGLE CALENDAR
# ===========================================
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxx
GOOGLE_REDIRECT_URI=https://seudominio.com/google/callback
GOOGLE_CALENDAR_ID=primary

# ===========================================
# CLIMA (OpenWeatherMap)
# ===========================================
WEATHER_API_KEY=sua_api_key
WEATHER_CITY_ID=3451190       # Ribeirão Preto
WEATHER_UNITS=metric

# ===========================================
# PDF GERAÇÃO (Browsershot)
# ===========================================
CHROME_PATH=/usr/bin/chromium
NODE_PATH=/usr/bin/node
NPM_PATH=/usr/bin/npm

# ===========================================
# BACKUP
# ===========================================
BACKUP_DISK=google            # ou s3
BACKUP_NOTIFICATION_MAIL=admin@empresa.com
```

## 🚀 Serviços Principais

### LeadService
Responsável pela captação de leads do site público:
- Validação robusta de dados
- Busca/criação de cliente
- Criação de orçamento inicial no funil

### OrcamentoCalculator
Centraliza toda matemática financeira:
- Cálculo de subtotais
- Aplicação de descontos (prestador, PIX)
- Cálculo de comissões (vendedor, loja)
- Geração de dados PIX para PDF

### PixMasterService
Integração com EFI/Gerencianet:
- Geração de QR Code PIX
- Verificação de pagamentos
- Webhook para confirmação automática

### GoogleCalendarService
Sincronização com agenda Google:
- Criação de eventos
- Atualização automática
- OAuth2 refresh token

## 📱 Responsividade Mobile

Os Resources Filament seguem a regra:
- **Mobile (< md)**: 2-3 colunas essenciais (Nome, Valor, Status)
- **Tablet (md)**: + Cliente, Tipo
- **Desktop (lg+)**: Todas as colunas

Colunas configuradas com:
```php
->visibleFrom('md')  // Oculta no mobile
->hiddenFrom('md')   // Visível apenas no mobile
```

## 🔒 Segurança

### FileDownloadController
- Whitelist de extensões (.pdf, .jpg, .png, etc.)
- Verificação de Path Traversal
- Validação de disco permitido
- Limite de tamanho (100MB)
- Logging de tentativas suspeitas

### Rotas Assinadas
```php
Route::get('/orcamento/{orcamento}/publico', ...)
    ->middleware('signed');
```

### Autenticação
- Filament com middleware `auth`
- Proteção CSRF padrão Laravel
- Rate limiting em rotas públicas

---

**Última atualização:** Fevereiro 2026
