# 🎯 Funil de Vendas CRM - Documentação Técnica

## 📋 Visão Geral

Sistema completo de CRM com visualização Kanban para gestão de leads e pipeline de vendas.

**URL:** `http://127.0.0.1:8000/admin/funil-vendas`

---

## ✅ Correções Aplicadas

### 1. **Problema CSS Crítico Resolvido**

**Problema:** Classe CSS dinâmica não funciona com Tailwind
```blade
❌ border-{{ $statusData['badge_color'] }}
```

**Solução:** Usar match() do PHP para gerar classes estáticas
```blade
✅ @php
    $borderColor = match($statusKey) {
        'novo' => 'border-gray-500',
        'contato_realizado' => 'border-blue-500',
        ...
    };
@endphp
<div class="... {{ $borderColor }}">
```

### 2. **Melhorias no CSS Responsivo**

**Arquivo:** `resources/css/responsive.css`

**Adicionado:**
- Custom scrollbar para Kanban horizontal
- Breakpoint XL (1280px) com colunas de 350px
- Max-height nas colunas: `calc(100vh - 16rem)`
- Melhor visual do scrollbar com transparência

### 3. **Compilação de Assets**

✅ Build concluído com sucesso:
- `app-D17M1P7S.css` (156.23 KB)
- `app-COZLQeAZ.js` (38.26 KB)
- PWA configurado

---

## 🎨 Estrutura de Cores

| Etapa | Cor | Border | Badge |
|-------|-----|--------|-------|
| 🌟 Novo Lead | Cinza | `border-gray-500` | `bg-gray-500` |
| 💬 Contato Feito | Azul | `border-blue-500` | `bg-blue-500` |
| 📅 Visita Agendada | Amarelo | `border-yellow-500` | `bg-yellow-500` |
| ✈️ Proposta Enviada | Roxo | `border-purple-500` | `bg-purple-500` |
| 💰 Em Negociação | Laranja | `border-orange-500` | `bg-orange-500` |
| ✅ Fechado/Ganho | Verde | `border-green-500` | `bg-green-500` |
| ❌ Perdido | Vermelho | `border-red-500` | `bg-red-500` |

---

## 📱 Responsividade

### Breakpoints das Colunas

```css
Mobile (< 640px):   280px por coluna
Tablet (≥ 640px):   300px por coluna
Desktop (≥ 1024px): 320px por coluna
XL (≥ 1280px):      350px por coluna
```

### Comportamento Mobile

- Scroll horizontal com snap
- Touch-friendly (webkit-overflow-scrolling)
- Indicador visual de scroll
- Colunas fixas (flex-shrink: 0)

---

## 🚀 Funcionalidades

### Header Actions

#### 1. **+ Novo Lead** (Verde)
Formulário rápido com:
- Nome do cliente
- Telefone/WhatsApp (máscara automática)
- E-mail
- Cidade/Estado
- Tipo de serviço (4 opções)
- Etapa inicial
- Valor estimado
- Observações

**Comportamento:**
- Busca cliente existente por telefone
- Cria novo se não existir
- Gera número de orçamento automaticamente
- Define origem como 'crm_funil'
- Redireciona para o funil após salvar

#### 2. **Leads Parados** (Amarelo)
- Executa comando `leads:alert-stalled`
- Identifica orçamentos > 7 dias sem movimentação
- Envia notificações

#### 3. **Estatísticas** (Azul)
Modal com:
- 4 KPIs principais
- Distribuição por etapa (gráficos)
- Top 5 leads mais valiosos

### Filtros Dinâmicos

**Busca Global:**
- Por nome do cliente
- Por número do orçamento
- Debounce de 500ms

**Filtro por Vendedor:**
- Lista todos vendedores ativos
- Opção "Todos"

**Filtro por Período:**
- Todos
- Hoje
- Esta Semana
- Este Mês

### Cards do Kanban

**Informações exibidas:**
- Número do orçamento (mono)
- Nome do cliente (truncado)
- Valor com formatação BRL
- Data/hora absoluta
- Tempo relativo ("há 2 horas")
- Tipo de serviço com ícone
- WhatsApp clicável (abre wa.me)

**Ações:**
- Botão "Editar" (link direto)
- Dropdown "Mover para" (todas etapas exceto atual)
- Drag indicator (visual apenas)

---

## 🔧 Tecnologias

### Backend
- **Filament v3** - Page Component
- **Livewire** - Filtros reativos
- **Laravel** - Models & Eloquent

### Frontend
- **Tailwind CSS** - Estilização
- **Alpine.js** - Interatividade (Filament)
- **Blade Components** - Heroicons

### Banco de Dados
- Tabela: `orcamentos`
- Campo chave: `etapa_funil` (string)
- Relacionamento: `belongsTo(Cadastro::class, 'cadastro_id')`

---

## 📊 Estatísticas Calculadas

```php
foreach ($this->statuses as $key => $data) {
    $items = $orcamentos->where('etapa_funil', $key);
    $estatisticas[$key] = [
        'count' => $items->count(),        // Quantidade
        'total' => $items->sum('valor_total'), // Valor total
    ];
}
```

**Métricas globais:**
- Total de leads
- Valor total em R$
- Taxa de conversão (aprovados/total)
- Ticket médio

---

## 🎯 Sincronização de Status

Quando um lead é movido:

```php
if ($status === 'aprovado') {
    $orcamento->update(['status' => 'aprovado']);
} elseif ($status === 'perdido') {
    $orcamento->update(['status' => 'rejeitado']);
}
```

Mantém consistência entre:
- `etapa_funil` (CRM Kanban)
- `status` (Sistema principal)

---

## 🔄 Fluxo de Dados

1. **Captura:** Lead entra via site → etapa `novo`
2. **Qualificação:** Movimentação manual pelas etapas
3. **Negociação:** Acompanhamento em tempo real
4. **Fechamento:** Move para `aprovado` ou `perdido`
5. **Trigger:** Status aprovado → gera OS e financeiro

---

## 🐛 Debug & Troubleshooting

### Problema: Cards não aparecem
```bash
# Verificar se há orçamentos
docker compose exec laravel.test php artisan tinker
>>> \App\Models\Orcamento::count()
```

### Problema: Filtros não funcionam
```bash
# Verificar propriedades públicas Livewire
# Em FunilVendas.php devem estar:
public ?string $busca = '';
public ?string $filtroVendedor = null;
public ?string $filtroPeriodo = 'todos';
```

### Problema: CSS não carrega
```bash
# Recompilar
npm run build

# Limpar cache
docker compose exec laravel.test php artisan view:clear
docker compose exec laravel.test php artisan filament:clear-cached-components
```

### Problema: Dropdown não funciona
```bash
# Verificar Alpine.js (Filament)
# Deve estar carregado no layout
```

---

## 📈 Performance

### Otimizações aplicadas:

1. **Eager Loading:** `with(['cliente'])`
2. **Filtro temporal:** últimos 6 meses apenas
3. **Debounce:** busca com 500ms
4. **Scroll virtual:** apenas cards visíveis
5. **CSS otimizado:** classes estáticas (não dinâmicas)

### Limites recomendados:

- Máximo 500 leads simultâneos no funil
- Arquivar leads > 6 meses
- Índices no DB: `etapa_funil`, `cadastro_id`, `created_at`

---

## 🎨 Customização

### Adicionar nova etapa:

1. Atualizar array `$statuses` em `FunilVendas.php`
2. Adicionar cor no match() da view
3. Adicionar migração se necessário

### Alterar cores:

Editar em `FunilVendas.php`:
```php
'badge_color' => 'bg-SEU-COR-500',
'color' => 'bg-gradient-to-br from-SEU-COR-50 to-SEU-COR-100',
```

E na view (match):
```php
'sua_etapa' => 'border-SEU-COR-500',
```

---

## 📝 Changelog

**v2.0.0** (05/02/2026)
- ✅ Corrigido CSS dinâmico (border-{{}})
- ✅ Adicionado botão "Novo Lead"
- ✅ Implementado filtros reativos
- ✅ Adicionado estatísticas
- ✅ Melhorado responsividade (XL)
- ✅ Custom scrollbar
- ✅ WhatsApp clicável nos cards
- ✅ Tempo relativo (diffForHumans)

**v1.0.0** (31/01/2026)
- ✅ Kanban básico implementado
- ✅ 6 etapas do funil
- ✅ Drag visual indicator

---

## 🔗 Arquivos Relacionados

```
app/
  Filament/
    Pages/
      FunilVendas.php                          # Controller

resources/
  views/
    filament/
      pages/
        funil-vendas.blade.php                 # View principal
        components/
          funil-stats.blade.php                # Modal estatísticas
  css/
    responsive.css                             # Estilos Kanban

database/
  migrations/
    2026_01_31_063032_add_etapa_funil.php      # Campo etapa_funil
```

---

## 📚 Referências

- [Filament Pages](https://filamentphp.com/docs/3.x/panels/pages)
- [Livewire Properties](https://livewire.laravel.com/docs/properties)
- [Tailwind Dynamic Classes](https://tailwindcss.com/docs/content-configuration#dynamic-class-names)
- [Blade Templates](https://laravel.com/docs/11.x/blade)

---

**Desenvolvido para:** STOFGARD  
**Versão:** 2.0.0  
**Data:** 05 de Fevereiro de 2026  
**Status:** ✅ Produção
