# 🔍 Guia de Debug do Widget de Clima

## Problema
Widget fica travado em "Carregando clima..." e não exibe os dados.

## Correções Aplicadas

### 1. Wire:ignore adicionado
- Adicionado `wire:ignore` no container do widget para evitar que Livewire reescreva o HTML

### 2. JavaScript robusto com múltiplas estratégias
- IIFE para evitar conflitos de escopo
- Listener para `DOMContentLoaded`
- Listener para `livewire:navigated` (SPA navigation)
- Fallback após 1 segundo caso ainda esteja em loading
- Logs detalhados com prefixo `[Weather]`

### 3. Remoção do @script do Filament
- Removido `@script/@endscript` que pode causar problemas
- Usando `<script>` puro com IIFE

---

## 📋 Passos para Testar no Navegador

### Teste 1: Página de Teste Minimalista
```
http://localhost:8000/test-api-minimal.html
```

**O que deve acontecer:**
- ✅ Deve mostrar "✅ SUCESSO! Temp: XX°C em Ribeirão Preto"
- ✅ Deve exibir o JSON completo da resposta

**Se falhar:**
- ❌ Problema na API ou conectividade
- Verifique se o Docker está rodando
- Teste: `curl http://localhost:8000/api/widget/weather?city=London`

---

### Teste 2: Página de Debug Completa
```
http://localhost:8000/debug-widget.html
```

**O que deve acontecer:**
- ✅ Widget deve carregar e mostrar temperatura
- ✅ Console de logs na parte inferior deve mostrar todos os passos
- ✅ Botões de teste devem funcionar (Londres, São Paulo)

**Se falhar:**
- Veja os logs na parte inferior da página
- Se aparecer erro 404: API não está carregando
- Se aparecer erro 500: Problema no servidor
- Se timeout: API lenta ou API key inválida

---

### Teste 3: Dashboard Real do Filament
```
http://localhost:8000/admin
```

**Login:** seu usuário admin

**Console do Navegador (F12):**

1. Abra o console ANTES de carregar a página
2. Pressione `Ctrl + Shift + R` (hard refresh)
3. Procure logs que começam com `[Weather]`

**Logs esperados:**
```
[Weather] 🚀 Script iniciado
[Weather] DOM já pronto, executando imediatamente
[Weather] ✅ Widget encontrado!
[Weather] 🌤️ Cidade configurada: Ribeirão Preto
[Weather] 📡 Requisitando: /api/widget/weather?city=...
[Weather] 📥 Status HTTP: 200
[Weather] ✅ Dados recebidos: {success: true, ...}
[Weather] ✅ Widget carregado com sucesso!
```

**Possíveis problemas e soluções:**

| Log | Significado | Solução |
|-----|-------------|---------|
| `❌ Widget #weather-widget não encontrado` | HTML não renderizou | View cache ou erro no Blade |
| `❌ Elementos internos não encontrados` | Estrutura HTML incompleta | Verificar blade file |
| `📥 Status HTTP: 404` | Rota API não encontrada | `php artisan route:list \| grep weather` |
| `📥 Status HTTP: 500` | Erro no servidor | Ver `storage/logs/laravel.log` |
| `📥 Status HTTP: 503` | API externa indisponível | OpenWeather fora do ar |
| `❌ Erro HTTP: XXX` | Falha na requisição | Ver network tab no F12 |
| Nenhum log aparece | Script não executou | Problema com Livewire/Alpine |

---

## 🧪 Comandos de Debug

### Verificar se API está funcionando
```bash
curl "http://localhost:8000/api/widget/weather?city=London"
```

Resposta esperada:
```json
{
  "success": true,
  "data": {
    "city": "London",
    "temperature": 7.2,
    "description": "Nublado",
    ...
  }
}
```

### Limpar todos os caches
```bash
docker compose exec laravel.test php artisan view:clear
docker compose exec laravel.test php artisan cache:clear
docker compose exec laravel.test php artisan config:clear
```

### Ver logs do Laravel
```bash
docker compose exec laravel.test tail -f storage/logs/laravel.log
```

### Verificar rotas registradas
```bash
docker compose exec laravel.test php artisan route:list | grep weather
```

Deve mostrar:
```
GET|HEAD  api/widget/weather  api.weather.get › WeatherController@getWeather
```

---

## 🔧 Troubleshooting Avançado

### Problema: Script não executa no Filament

**Solução 1: Verificar se wire:ignore está presente**
```bash
grep -n "wire:ignore" resources/views/filament/widgets/dashboard-shortcuts-widget.blade.php
```

Deve mostrar linha com `<div class="banner-weather" wire:ignore>`

**Solução 2: Inspecionar elemento no navegador**
1. F12 → Elements
2. Procurar por `id="weather-widget"`
3. Verificar se possui `data-city` attribute
4. Verificar se elementos internos existem (#weather-loading, #weather-content, #weather-error)

**Solução 3: Testar fetch manualmente no console**
```javascript
fetch('/api/widget/weather?city=London')
  .then(r => r.json())
  .then(data => console.log(data))
```

---

### Problema: CORS ou CSP bloqueando requisição

**Verificar headers:**
```bash
curl -I "http://localhost:8000/api/widget/weather?city=London"
```

Deve conter:
```
Content-Type: application/json
Access-Control-Allow-Origin: *
```

**Se não tiver CORS:**
Adicionar em `app/Http/Controllers/WeatherController.php`:
```php
return response()->json($data)->header('Access-Control-Allow-Origin', '*');
```

---

### Problema: Livewire reescrevendo o HTML

**Sintomas:**
- Widget desaparece após alguns segundos
- Console mostra "Widget não encontrado" após reload

**Solução:**
Garantir que `wire:ignore` está presente no container do widget

---

### Problema: Cache antigo

**Limpar TUDO:**
```bash
docker compose exec laravel.test php artisan optimize:clear
docker compose exec laravel.test php artisan view:clear
docker compose exec laravel.test php artisan config:clear
docker compose exec laravel.test php artisan route:clear
docker compose exec laravel.test php artisan cache:clear
```

---

## 📊 Checklist Final

Teste na ordem:

- [ ] 1. `test-api-minimal.html` funciona?
  - **Sim:** API OK, problema no widget
  - **Não:** API com problema

- [ ] 2. `debug-widget.html` funciona?
  - **Sim:** JavaScript OK, problema no Filament
  - **Não:** JavaScript com problema

- [ ] 3. Console do admin mostra logs `[Weather]`?
  - **Sim:** Script executando
  - **Não:** Script não carrega

- [ ] 4. Console mostra erro 404/500?
  - **404:** Rota não registrada
  - **500:** Erro no controller/service

- [ ] 5. Inspecionar elemento mostra `#weather-widget`?
  - **Sim:** HTML renderizado
  - **Não:** Blade não compilou

---

## 💡 Próximos Passos

1. Acesse `http://localhost:8000/test-api-minimal.html`
   - Se funcionar: API OK ✅
   - Se não funcionar: API com problema ❌

2. Acesse `http://localhost:8000/debug-widget.html`
   - Se funcionar: JavaScript OK ✅
   - Se não funcionar: JavaScript com problema ❌

3. Acesse `http://localhost:8000/admin`
   - Abra console (F12)
   - Faça hard refresh (Ctrl + Shift + R)
   - Me envie os logs que aparecem
   - Me envie uma screenshot da aba Network (filtro: weather)

---

**Arquivo atualizado:** `resources/views/filament/widgets/dashboard-shortcuts-widget.blade.php`
- ✅ wire:ignore adicionado
- ✅ Script robusto com múltiplas estratégias
- ✅ Logs detalhados
- ✅ Fallback após 1 segundo
