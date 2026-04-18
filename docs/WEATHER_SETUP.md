# ⚡ Quick Start - Widget de Clima

## 1️⃣ Obter API Key (Grátis)

1. Acesse: https://openweathermap.org/api
2. Clique em **"Get API key"** ou **"Sign Up"**
3. Crie uma conta gratuita
4. Copie sua API key do dashboard

---

## 2️⃣ Configurar no Sistema

### Adicione no arquivo `.env`:
```env
OPENWEATHER_API_KEY=sua_chave_aqui_da_openweather
OPENWEATHER_DEFAULT_CITY="São Paulo"
```

### Limpe o cache:
```bash
php artisan config:clear
```

---

## 3️⃣ Configurar Cidade no Admin

1. Acesse: **`/admin/configuracoes`**
2. Clique na aba: **Dashboard**
3. Preencha: **"Cidade para Previsão do Tempo"**
4. Exemplo: `São Paulo`, `Rio de Janeiro`, `London`, `New York`
5. Clique em **Salvar**

---

## 4️⃣ Testar

Acesse o dashboard e veja o widget carregar automaticamente! 🎉

---

## ❓ Problemas?

### Widget não aparece?
```bash
# Verifique se a API key está configurada
grep OPENWEATHER_API_KEY .env

# Teste a rota diretamente
curl "http://localhost:8000/api/widget/weather?city=London"

# Veja os logs
tail -f storage/logs/laravel.log
```

### Limpar cache de uma cidade
```bash
php artisan weather:clear-cache "São Paulo"
```

---

## 📚 Documentação Completa

Veja: `docs/WEATHER_MODULE_DOCUMENTATION.md`

---

**Pronto! Seu widget de clima está configurado e funcionando! ☀️**
