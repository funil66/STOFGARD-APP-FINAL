# **🔩 Especificação Técnica: Consolidação de Navegação STOFGARD**

**Autor:** Iron Code (p/ Allisson Sousa)

**Data:** 05/02/2026

**Objetivo:** Eliminar rotas órfãs e centralizar 100% da navegação nos 8 módulos do Dashboard.

**Status:** Planejamento de Execução

## **1\. 🎯 Diretriz Primária: "Zero URL Typing"**

O usuário final jamais deve precisar digitar uma URL. Todas as funcionalidades listadas em routes.md devem ser acessíveis através de cliques (Drill-down) a partir dos 8 ícones principais do Dashboard.

## **2\. 🗺️ Mapa de Consolidação (Current vs Target)**

### **Módulo 1: AGENDA 🗓️**

**Situação Atual:** Acesso separado para Lista (/agendas), Calendário Visual (/calendario) e Tarefas (/tarefas).

**Estratégia de Consolidação:**

1. **Resource Pai:** AgendaResource.  
2. **Calendário:** Não deve ser uma página separada. Deve ser uma **View** alternativa dentro de AgendaResource.  
   * *Implementação:* Adicionar um Toggle ou Tab no topo da ListAgendas: \[ Lista | Calendário \].  
3. **Tarefas:** Deve ser subordinada à Agenda.  
   * *Implementação:* Adicionar como RelationManager na visualização da Agenda ou um Action no Header "Minhas Tarefas" que abre um SlideOver.  
4. **Google Sync:**  
   * *Ação:* HeaderAction na listagem principal. "Sincronizar Google".

### **Módulo 2: CADASTRO (CRM) 👥**

**Situação Atual:** Funil de Vendas (/funil-vendas) está órfão fora do cadastro.

**Estratégia de Consolidação:**

1. **Resource Pai:** CadastroResource.  
2. **Funil de Vendas (Kanban):**  
   * *Lógica:* O Funil é apenas uma visualização de status dos cadastros/leads.  
   * *Implementação:* Na ListCadastros, adicionar um botão de ação no topo: "Visualizar Funil (CRM)". Isso redireciona para a rota do funil (agora filha) ou altera a view da tabela para Kanban.  
3. **Busca Universal:**  
   * *Ação:* Integrar ao GlobalSearch do Filament (Cmd+K) ou colocar um widget de busca no topo do Dashboard de Cadastros.

### **Módulo 3: FINANCEIRO 💵**

**Situação Atual:** Relatórios (/relatorios), Extratos e Notas Fiscais soltos.

**Estratégia de Consolidação:**

1. **Resource Pai:** FinanceiroResource (Transações).  
2. **Relatórios (Geral, Avançado, DRE):**  
   * *Implementação:* Criar um Menu Dropdown (ActionGroup) no Header da listagem financeira chamado "Relatórios".  
   * *Itens:* "Fluxo de Caixa", "DRE", "Relatórios Avançados".  
3. **Notas Fiscais & Extratos:**  
   * *Implementação:* Transformar em **Tabs** no topo da página ManageFinanceiros ou sub-páginas acessíveis via botões de navegação no topo da lista.

### **Módulo 4: CONFIGURAÇÕES ⚙️**

**Situação Atual:** Diversos CRUDs auxiliares (Tabela de Preços, Garantias, Categorias) poluindo o menu ou invisíveis.

**Estratégia de Consolidação:**

1. **Página Pai:** Configuracoes (Filament Page).  
2. **Dashboard de Configurações:**  
   * *Lógica:* Esta página não deve ser apenas um formulário. Ela deve ser um **Hub de Navegação**.  
   * *Implementação:* Criar um Grid de Cards (Blade Component) dentro da página Configuracoes.  
   * *Cards:*  
     * 🏷️ **Categorias** (Link para /admin/categorias)  
     * 💲 **Tabela de Preços** (Link para /admin/tabela-precos)  
     * 🛡️ **Garantias** (Link para /admin/garantias)  
     * 🚩 **Feature Flags** (Visível apenas para Dev/Admin)  
3. **Ocultação:** Todos os Resources acima devem ter protected static bool $shouldRegisterNavigation \= false; para não aparecerem na sidebar principal, sendo acessados apenas por este Hub.

### **Módulo 5: ALMOXARIFADO 📦**

**Situação Atual:** Produtos, Estoques, Equipamentos e Lista de Desejos fragmentados.

**Estratégia de Consolidação:**

1. **Estrutura:** Utilizar **Filament Clusters** ou Tabs.  
2. **Resource Principal:** AlmoxarifadoResource (Foco em Produtos).  
3. **Lista de Desejos:** Botão de Ação "Lista de Compras" no Header.  
4. **Equipamentos:** Tab separada "Ativos Imobilizados" dentro da listagem, filtrando o tipo de produto.

## **3\. 👨‍💻 Instruções Técnicas para o Agente (Implementation Guide)**

Siga estas instruções passo-a-passo para refatorar o código Laravel Filament.

### **Passo 1: Limpeza da Sidebar (Navigation Hiding)**

Para os seguintes Resources, defina a propriedade de navegação como falsa, mas mantenha as rotas ativas (slug):

* TabelaPrecoResource  
* GarantiaResource  
* CategoriaResource  
* FeatureFlagResource  
* CalendarioPage (Se for página isolada)

// Exemplo em TabelaPrecoResource.php  
protected static bool $shouldRegisterNavigation \= false;  
protected static ?string $slug \= 'configuracoes/tabela-precos'; // Slug aninhado semanticamente

### **Passo 2: Construção do Hub de Configurações**

Edite Filament/Pages/Configuracoes.php e sua view correspondente.

**View (resources/views/filament/pages/configuracoes.blade.php):**

Crie um grid layout com links diretos para os resources ocultos.

\<x-filament::page\>  
    \<div class="grid grid-cols-1 md:grid-cols-3 gap-4"\>  
        \<\!-- Card Categorias \--\>  
        \<a href="{{ \\App\\Filament\\Resources\\CategoriaResource::getUrl() }}" class="block p-6 bg-white rounded-lg border hover:shadow-lg transition"\>  
            \<h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900"\>Categorias\</h5\>  
            \<p class="font-normal text-gray-700"\>Gerenciar taxonomias do sistema.\</p\>  
        \</a\>  
          
        \<\!-- Card Tabela de Preços \--\>  
        \<a href="{{ \\App\\Filament\\Resources\\TabelaPrecoResource::getUrl() }}" class="block p-6 bg-white rounded-lg border hover:shadow-lg transition"\>  
            \<h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900"\>Tabela de Preços\</h5\>  
            \<p class="font-normal text-gray-700"\>Ajustar valores e precificação.\</p\>  
        \</a\>  
          
        \<\!-- Adicionar Cards para Garantias, Logs, etc \--\>  
    \</div\>  
\</x-filament::page\>

### **Passo 3: Fusão Agenda \+ Calendário**

No AgendaResource/Pages/ListAgendas.php:

1. Use o método getHeaderActions() para adicionar o botão de alternância.  
2. Se usar o plugin FullCalendar, considere usar View::share ou Tabs.

protected function getHeaderActions(): array  
{  
    return \[  
        Actions\\Action::make('visualizarCalendario')  
            \-\>label('Modo Calendário')  
            \-\>icon('heroicon-o-calendar')  
            \-\>url(route('filament.admin.pages.calendario')), // Link interno direto  
            // OU melhor: Renderizar o widget de calendário em um Modal ou Tab  
        Actions\\CreateAction::make(),  
    \];  
}

### **Passo 4: Integração CRM no Cadastro**

No CadastroResource/Pages/ListCadastros.php:

protected function getHeaderActions(): array  
{  
    return \[  
        Actions\\Action::make('funilVendas')  
            \-\>label('Funil de Vendas')  
            \-\>icon('heroicon-o-funnel')  
            \-\>color('warning')  
            \-\>url(fn () \=\> \\App\\Filament\\Pages\\FunilVendas::getUrl()), // Redireciona para a página órfã  
    \];  
}

### **Passo 5: Centralização de Relatórios (Financeiro)**

No FinanceiroResource/Pages/ListFinanceiros.php:

protected function getHeaderActions(): array  
{  
    return \[  
        Actions\\ActionGroup::make(\[  
            Actions\\Action::make('relatorioSimples')  
                \-\>label('Extrato Simples')  
                \-\>url(route('filament.admin.pages.relatorios')),  
            Actions\\Action::make('relatorioAvancado')  
                \-\>label('Análise Avançada')  
                \-\>url(route('filament.admin.pages.relatorios-avancados')),  
        \])  
        \-\>label('Relatórios')  
        \-\>icon('heroicon-m-document-chart')  
        \-\>button(),  
          
        Actions\\CreateAction::make(),  
    \];  
}

## **4\. ✅ Checklist de Validação**

Após a implementação, verificar:

1. \[ \] O Dashboard exibe apenas os 8 ícones principais?  
2. \[ \] Ao clicar em "Agenda", consigo ver a lista E acessar o calendário visual sem digitar URL?  
3. \[ \] Ao clicar em "Cadastro", consigo acessar o "Funil de Vendas" com 1 clique?  
4. \[ \] Ao clicar em "Configurações", vejo o grid com Tabela de Preços, Garantias e Categorias?  
5. \[ \] Nenhuma rota retorna 404 ou 403 devido às mudanças de slug.  
6. \[ \] O Breadcrumb (trilha de navegação) faz sentido? Ex: Configurações \> Tabela de Preços.

**Assinado:** Iron Code 🤘