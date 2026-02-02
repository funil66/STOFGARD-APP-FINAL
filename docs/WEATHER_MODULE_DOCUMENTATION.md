# Módulo de Clima Dinâmico - Documentação Técnica

## 📋 Visão Geral

Sistema de widget meteorológico com **cache inteligente por cidade**, **carregamento assíncrono** e **proteção contra rate limit**.

---

## 🏗️ Arquitetura

### 1. **Rota API** (`/api/widget/weather`)
- **Arquivo**: `routes/api.php`
- **Throttle**: 60 requisições/minuto por IP
- **Método**: GET
- **Parâmetro**: `city` (string, obrigatório)

```php
GET /api/widget/weather?city=São%20Paulo
```

---

### 2. **Validação de Request** (`WeatherRequest`)
- **Arquivo**: `app/Http/Requests/WeatherRequest.php`
- **Sanitização**: Remove caracteres especiais
- **Regex**: `/^[a-zA-ZÀ-ÿ\s\-]+$/u` (apenas letras e hífen)
- **Limites**: 2-100 caracteres

---

### 3. **Service Layer** (`WeatherService`)
- **Arquivo**: `app/Services/WeatherService.php`

#### Cache Dinâmico por Cidade
```php
Cache Key: weather_data_{slug-da-cidade}
TTL: 1800 segundos (30 minutos)
```

#### Fluxo de Dados
```
1. Request recebido → Valida cidade
2. Gera cache key: weather_data_sao-paulo
3. Cache HIT? → Retorna dados (< 1ms)
4. Cache MISS? → Chama OpenWeather API
5. API Success? → Cacheia + Retorna
6. API 404? → Cacheia erro (5min) + Retorna null
7. API Timeout? → Log + Retorna null
```

---

### 4. **Controller** (`WeatherController`)
- **Arquivo**: `app/Http/Controllers/WeatherController.php`
- **Resposta Padronizada**:

**Sucesso (200)**:
```json
{
  "success": true,
  "data": {
    "city": "São Paulo",
    "country": "BR",
    "temperature": 25.3,
    "feels_like": 26.1,
    "description": "Céu limpo",
    "humidity": 65,
    "icon": "01d",
    "icon_url": "https://openweathermap.org/img/wn/01d@2x.png",
    "timestamp": "2026-02-02T10:30:00-03:00"
  },
  "cached": true
}
```

**Erro (503)**:
```json
{
  "success": false,
  "message": "Não foi possível obter dados meteorológicos...",
  "error_code": "WEATHER_UNAVAILABLE"
}
```

---

### 5. **Frontend (JavaScript Assíncrono)**
- **Arquivo**: `resources/views/filament/widgets/dashboard-shortcuts-widget.blade.php`

#### Comportamento
1. **Skeleton Loading**: Widget inicia com animação de loading
2. **Fetch Assíncrono**: Não bloqueia o render da página
3. **Tratamento de Erro**: 
   - Exibe mensagem amigável por 5 segundos
   - Remove widget automaticamente se falhar
4. **Exibição Suave**: Transição fade-in após carregar

---

## 🔐 Segurança

### 1. Throttling (Rate Limit)
```php
Route::middleware(['throttle:60,1']) // 60 req/min
```

### 2. Sanitização de Input
```php
preg_replace('/[^a-zA-ZÀ-ÿ\s\-]/u', '', $city)
```

### 3. Timeout de Conexão
```php
Http::timeout(5) // 5 segundos
```

### 4. Try-Catch em Cascata
```php
Service → Controller → Frontend
// Nunca lança Exception 500 para o usuário
```

---

## ⚙️ Configuração

### 1. API Key do OpenWeather

**Obter gratuitamente**: https://openweathermap.org/api

**Adicionar no `.env`**:
```env
OPENWEATHER_API_KEY=sua_api_key_aqui
OPENWEATHER_DEFAULT_CITY="São Paulo"
```

### 2. Configurar Cidade no Admin

1. Acesse: `/admin/configuracoes`
2. Aba: **Dashboard**
3. Campo: **Cidade para Previsão do Tempo**
4. Salvar

---

## 🚀 Performance

### Cache Strategy
| Cenário | Cache Hit | Latência |
|---------|-----------|----------|
| 1ª requisição | ❌ Miss | ~500ms (API) |
| 2ª+ requisição (30min) | ✅ Hit | < 1ms |
| Erro 404 | ✅ Hit | < 1ms (5min) |

### Limites da API Free
- **60 req/min** por IP (protegido via throttle)
- **1.000.000 req/mês**
- Com cache de 30min, uso estimado: **< 1.000 req/mês**

---

## 🛠️ Comandos Artisan

### Limpar Cache de Cidade Específica
```bash
php artisan weather:clear-cache "São Paulo"
```

### Limpar Todo Cache do Sistema
```bash
php artisan cache:clear
```

---

## 📊 Monitoramento

### Logs
```bash
# Ver logs de erro do serviço
tail -f storage/logs/laravel.log | grep "Weather"
```

### Eventos Logados
- ✅ API key não configurada
- ✅ Cidade não encontrada (404)
- ✅ Timeout de conexão
- ✅ Erros inesperados

---

## 🧪 Testes

### 1. Testar Endpoint Diretamente
```bash
curl "http://localhost:8000/api/widget/weather?city=São%20Paulo"
```

### 2. Testar Cidade Inválida
```bash
curl "http://localhost:8000/api/widget/weather?city=XXXINVALIDO"
# Deve retornar 503 com mensagem amigável
```

### 3. Testar Rate Limit
```bash
# Fazer 61 requisições em 1 minuto
for i in {1..61}; do curl "http://localhost:8000/api/widget/weather?city=London"; done
# A 61ª deve retornar 429 Too Many Requests
```

---

## 🐛 Troubleshooting

### Widget não aparece
1. Verificar se `OPENWEATHER_API_KEY` está configurada no `.env`
2. Verificar logs: `tail -f storage/logs/laravel.log`
3. Testar endpoint manualmente (curl)

### Cidade não encontrada
- API do OpenWeather retorna 404 para nomes inválidos
- Testar no site oficial: https://openweathermap.org/city

### Dados desatualizados
```bash
php artisan weather:clear-cache "NomeDaCidade"
```

---

## 📦 Arquivos Criados

```
app/
├── Console/Commands/
│   └── ClearWeatherCache.php
├── Http/
│   ├── Controllers/
│   │   └── WeatherController.php
│   └── Requests/
│       └── WeatherRequest.php
└── Services/
    └── WeatherService.php

routes/
└── api.php

config/
└── services.php (atualizado)

resources/views/filament/widgets/
└── dashboard-shortcuts-widget.blade.php (atualizado)

app/Filament/
├── Pages/
│   └── Configuracoes.php (atualizado)
└── Widgets/
    └── DashboardShortcutsWidget.php (atualizado)

.env (atualizado)
.env.weather.example (novo)
```

---

## 🎯 Checklist de Implementação

- ✅ Rota API com throttle criada
- ✅ Request Validation implementada
- ✅ Service com cache dinâmico
- ✅ Controller com tratamento de erro
- ✅ Frontend assíncrono com skeleton
- ✅ Configuração no admin
- ✅ Variáveis de ambiente
- ✅ Comando artisan para cache
- ✅ Documentação completa

---

## 🔄 Próximos Passos

1. Configurar `OPENWEATHER_API_KEY` no arquivo `.env`
2. Acessar `/admin/configuracoes` e definir a cidade
3. Testar o widget no dashboard
4. Monitorar logs por 24h

---

**Desenvolvido com ❤️ para o Sistema Stofgard**
