# 🛣️ Mapa de Rotas - STOFGARD

## 📊 Resumo
- **Total de Rotas:** 133
- **Painéis:** Admin (Filament), Portal Cliente
- **APIs:** Weather, Webhooks PIX
- **PDFs:** Orçamentos, OS, Cadastros, Financeiros, etc

---

## 🔐 Autenticação & Login

| Método | Rota | Nome | Descrição |
|--------|------|------|-----------|
| GET | `/` | - | Página inicial |
| GET | `/dev-login` | dev.login | Login dev (desenvolvimento) |
| ANY | `/login` | login | Redirect para `/admin/login` |
| GET | `/admin/login` | filament.admin.auth.login | Login Admin |
| POST | `/admin/logout` | filament.admin.auth.logout | Logout Admin |
| GET | `/portal/login` | filament.cliente.auth.login | Login Cliente |
| POST | `/portal/logout` | filament.cliente.auth.logout | Logout Cliente |

---

## 🎯 Painel Admin - Dashboard & Páginas Principais

| Rota | Nome | Descrição |
|------|------|-----------|
| `/admin` | filament.admin.pages.dashboard | Dashboard Principal |
| `/admin/calendario` | filament.admin.pages.calendario | Calendário |
| `/admin/funil-vendas` | filament.admin.pages.funil-vendas | Funil de Vendas |
| `/admin/busca-universal` | filament.admin.pages.busca-universal | Busca Universal |
| `/admin/busca-avancada` | filament.admin.pages.busca-avancada | Busca Avançada |
| `/admin/relatorios` | filament.admin.pages.relatorios | Relatórios |
| `/admin/relatorios-avancados` | filament.admin.pages.relatorios-avancados | Relatórios Avançados |
| `/admin/configuracoes` | filament.admin.pages.configuracoes | Configurações |
| `/admin/google-calendar-settings` | filament.admin.pages.google-calendar-settings | Sincronização Google Calendar |
| `/admin/feature-flags` | filament.admin.pages.feature-flags | Feature Flags |
| `/admin/notifications` | filament.admin.pages.notifications | Notificações |

---

## 📋 Módulos - Cadastros & Vendas

### Cadastros
| Rota | Ação |
|------|------|
| `/admin/cadastros` | Listar |
| `/admin/cadastros/create` | Criar |
| `/admin/cadastros/{record}` | Visualizar |
| `/admin/cadastros/{record}/edit` | Editar |

### Orçamentos
| Rota | Ação |
|------|------|
| `/admin/orcamentos` | Listar |
| `/admin/orcamentos/create` | Criar |
| `/admin/orcamentos/{record}` | Visualizar |
| `/admin/orcamentos/{record}/edit` | Editar |

### Ordens de Serviço
| Rota | Ação |
|------|------|
| `/admin/ordem-servicos` | Listar |
| `/admin/ordem-servicos/create` | Criar |
| `/admin/ordem-servicos/{record}` | Visualizar |
| `/admin/ordem-servicos/{record}/edit` | Editar |

### Agenda & Tarefas
| Rota | Ação |
|------|------|
| `/admin/agendas` | Listar (Calendário) |
| `/admin/agendas/create` | Criar |
| `/admin/agendas/{record}` | Visualizar |
| `/admin/agendas/{record}/edit` | Editar |
| `/admin/agendas/tarefas` | Listar Tarefas |
| `/admin/agendas/tarefas/create` | Criar Tarefa |
| `/admin/agendas/tarefas/{record}` | Visualizar Tarefa |
| `/admin/agendas/tarefas/{record}/edit` | Editar Tarefa |

---

## 💰 Módulo Financeiro

| Rota | Ação | Nome |
|------|------|------|
| `/admin/financeiros/transacoes` | Listar | filament.admin.resources.financeiros.transacoes.index |
| `/admin/financeiros/transacoes/dashboard` | Dashboard | dashboard |
| `/admin/financeiros/transacoes/create` | Criar | create |
| `/admin/financeiros/transacoes/receitas` | Receitas | receitas |
| `/admin/financeiros/transacoes/despesas` | Despesas | despesas |
| `/admin/financeiros/transacoes/pendentes` | Pendentes | pendentes |
| `/admin/financeiros/transacoes/atrasadas` | Atrasadas | atrasadas |
| `/admin/financeiros/transacoes/extratos` | Extratos | extratos |
| `/admin/financeiros/transacoes/{record}` | Visualizar | view |
| `/admin/financeiros/transacoes/{record}/edit` | Editar | edit |

---

## 🏪 Almoxarifado

### Produtos
| Rota | Ação |
|------|------|
| `/admin/almoxarifado/produtos` | Listar |
| `/admin/almoxarifado/produtos/create` | Criar |
| `/admin/almoxarifado/produtos/{record}` | Visualizar |
| `/admin/almoxarifado/produtos/{record}/edit` | Editar |

### Estoques
| Rota | Ação |
|------|------|
| `/admin/almoxarifado/estoques` | Listar |
| `/admin/almoxarifado/estoques/create` | Criar |
| `/admin/almoxarifado/estoques/{record}` | Visualizar |
| `/admin/almoxarifado/estoques/{record}/edit` | Editar |

### Equipamentos
| Rota | Ação |
|------|------|
| `/admin/almoxarifado/equipamentos` | Listar |
| `/admin/almoxarifado/equipamentos/create` | Criar |
| `/admin/almoxarifado/equipamentos/{record}` | Visualizar |
| `/admin/almoxarifado/equipamentos/{record}/edit` | Editar |

### Lista de Desejos
| Rota | Ação |
|------|------|
| `/admin/almoxarifado/lista-desejos` | Listar |
| `/admin/almoxarifado/lista-desejos/create` | Criar |
| `/admin/almoxarifado/lista-desejos/{record}` | Visualizar |
| `/admin/almoxarifado/lista-desejos/{record}/edit` | Editar |

---

## ⚙️ Configurações

### Tabela de Preços
| Rota | Ação |
|------|------|
| `/admin/configuracoes/tabela-precos` | Listar |
| `/admin/configuracoes/tabela-precos/create` | Criar |
| `/admin/configuracoes/tabela-precos/{record}` | Visualizar |
| `/admin/configuracoes/tabela-precos/{record}/edit` | Editar |

### Garantias
| Rota | Ação |
|------|------|
| `/admin/configuracoes/garantias` | Listar |
| `/admin/configuracoes/garantias/create` | Criar |
| `/admin/configuracoes/garantias/{record}` | Visualizar |
| `/admin/configuracoes/garantias/{record}/edit` | Editar |

### Categorias
| Rota | Ação |
|------|------|
| `/admin/categorias` | Listar |
| `/admin/categorias/create` | Criar |
| `/admin/categorias/{record}` | Visualizar |
| `/admin/categorias/{record}/edit` | Editar |

### Notas Fiscais
| Rota | Ação |
|------|------|
| `/admin/notas-fiscais` | Listar |
| `/admin/notas-fiscais/create` | Criar |
| `/admin/notas-fiscais/{record}` | Visualizar |
| `/admin/notas-fiscais/{record}/edit` | Editar |

---

## 📄 Geração de PDFs

| Rota | Nome | Descrição |
|------|------|-----------|
| `GET /orcamento/{orcamento}/pdf` | orcamento.pdf | PDF Orçamento |
| `GET /orcamento/{orcamento}/compartilhar` | orcamento.compartilhar | PDF Compartilhado (signed) |
| `GET /orcamento/{orcamento}/publico` | orcamento.public_stream | Link Público |
| `POST /orcamento/{orcamento}/generate-pdf` | orcamento.generate | Gerar e Salvar PDF |
| `GET /os/{record}/pdf` | os.pdf | PDF Ordem de Serviço |
| `GET /cadastro/{cadastro}/pdf` | cadastro.pdf | PDF Cadastro |
| `GET /financeiro/{financeiro}/pdf` | financeiro.pdf | PDF Financeiro |
| `GET /agenda/{agenda}/pdf` | agenda.pdf | PDF Agenda |
| `GET /categoria/{categoria}/pdf` | categoria.pdf | PDF Categoria |
| `GET /extrato/pdf` | extrato.pdf | PDF Extrato |
| `GET /garantia/{garantia}/pdf` | garantia.pdf | PDF Garantia |
| `GET /nota-fiscal/{notaFiscal}/pdf` | nota-fiscal.pdf | PDF Nota Fiscal |
| `GET /produto/{produto}/pdf` | produto.pdf | PDF Produto |
| `GET /tabelapreco/{tabelapreco}/pdf` | tabelapreco.pdf | PDF Tabela Preço |
| `GET /listadesejo/{listadesejo}/pdf` | listadesejo.pdf | PDF Lista Desejo |
| `GET /tarefa/{tarefa}/pdf` | tarefa.pdf | PDF Tarefa |
| `GET /equipamento/{equipamento}/pdf` | equipamento.pdf | PDF Equipamento |
| `GET /estoque/{estoque}/pdf` | estoque.pdf | PDF Estoque |

---

## 🌐 Portal Cliente

| Rota | Nome | Descrição |
|------|------|-----------|
| `GET /portal` | filament.cliente.pages.dashboard | Dashboard Cliente |
| `GET /portal/login` | filament.cliente.auth.login | Login |
| `GET /portal/register` | filament.cliente.auth.register | Registro |
| `GET /portal/meus-servicos` | filament.cliente.resources.meus-servicos.index | Meus Serviços |
| `GET /portal/meus-servicos/{record}` | filament.cliente.resources.meus-servicos.view | Visualizar Serviço |
| `GET /portal/profile` | filament.cliente.auth.profile | Perfil |
| `GET /portal/password-reset/request` | filament.cliente.auth.password-reset.request | Recuperar Senha |
| `GET /portal/password-reset/reset` | filament.cliente.auth.password-reset.reset | Resetar Senha |

---

## 🌐 Público - Captação de Leads

| Rota | Nome | Método | Descrição |
|------|------|--------|-----------|
| `/solicitar-orcamento` | solicitar.orcamento | GET | Formulário de solicitação |
| `/solicitar-orcamento` | solicitar.orcamento.post | POST | Processar solicitação |
| `/pagamento/{hash}` | pagamento.pix | GET | Página PIX |
| `/pagamento/{hash}/verificar` | pagamento.verificar | GET | Verificar status PIX |

---

## 🔗 Utilitários & Integração

| Rota | Nome | Método | Descrição |
|------|------|--------|-----------|
| `/google/auth` | google.auth | GET | Autenticação Google Calendar |
| `/google/callback` | google.callback | GET | Callback Google OAuth |
| `/webhook/pix` | webhook.pix | POST | Webhook PIX (EFI/Gerencianet) |
| `/webhook/pix/status` | webhook.pix.status | GET | Status Webhook |
| `/download/{disk}/{encodedPath}` | file.download | GET | Download Seguro de Arquivos |
| `/admin/files/download/{model}/{record}/{path}` | admin.files.download | GET | Download Admin |
| `/admin/files/delete/{model}/{record}/{path}` | admin.files.delete | GET | Deletar Arquivo |

---

## 📡 APIs

| Rota | Nome | Método | Descrição |
|------|------|--------|-----------|
| `/api/user` | - | GET | Usuário autenticado |
| `/api/widget/weather` | api.weather.get | GET | Widget de Clima |
| `/financeiro/grafico/categoria` | financeiro.grafico.categoria | GET | Gráfico Financeiro |

---

## 🎭 Livewire & Assets

| Rota | Nome | Descrição |
|------|------|-----------|
| `/livewire/update` | livewire.update | POST - Update Livewire |
| `/livewire/upload-file` | livewire.upload-file | POST - Upload de Arquivo |
| `/livewire/preview-file/{filename}` | livewire.preview-file | GET - Preview de Arquivo |
| `/livewire/livewire.js` | - | GET - JavaScript Livewire |
| `/livewire/livewire.min.js.map` | - | GET - Source Map |
| `/storage/{path}` | storage.local | GET - Arquivos de Storage |
| `/filament/exports/{export}/download` | filament.exports.download | GET - Download Export |
| `/filament/imports/{import}/failed-rows/download` | filament.imports.failed-rows.download | GET - Download Falhos |

---

## 🧪 Teste & Desenvolvimento

| Rota | Nome | Descrição |
|------|------|-----------|
| `/_dusk/login/{userId}/{guard?}` | dusk.login | Laravel Dusk - Login |
| `/_dusk/logout/{guard?}` | dusk.logout | Laravel Dusk - Logout |
| `/_dusk/user/{guard?}` | dusk.user | Laravel Dusk - Usuário |
| `/up` | - | Health Check |

---

## 📊 Grupos de Rotas por Função

### 🔐 Segurança
- Auth (Login/Logout)
- Protected (Autenticado)
- Signed URLs (PDF compartilhados)

### 💼 Negócio
- Cadastros, Orçamentos, Ordens
- Financeiro, Agenda
- Almoxarifado

### 📤 Exportação
- PDFs (17 tipos)
- Relatórios
- Extratos

### 🔌 Integração
- Google Calendar
- PIX Webhook
- Weather API

### 🌐 Público
- Landing page
- Formulário de leads
- Portal cliente

---

**Última atualização:** 5 de fevereiro de 2026  
**Total de Rotas:** 133
