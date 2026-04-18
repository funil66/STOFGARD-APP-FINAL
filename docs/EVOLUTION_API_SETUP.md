# 🚀 Integração WhatsApp CRM via Evolution API

A Autonomia utiliza a Evolution API para automatizar fluxos de disparo de mensagens do Funil de Vendas e cobranças financeiras.

## 1. Configuração do .env
Adicione as credenciais da Evolution na raiz do `.env` ou `.env.prod`:

```env
EVOLUTION_API_URL=https://api.suaevolution.com.br
EVOLUTION_API_KEY=sua_apikey_global_secreta
EVOLUTION_INSTANCE_NAME=autonomia_padrao
EVOLUTION_INSTANCE_TOKEN=token_da_instancia_especifica
```

## 2. Webhooks
Para receber confirmações de leitura ou respostas, configure o Webhook no painel da Evolution apontando para:

`https://seusistema.com.br/api/evolution/webhook`

## 3. Disparo via Código (Exemplo Interno)
Para disparar de qualquer lugar do código, injete o serviço:

```php
use App\Services\EvolutionWhatsAppService;

$zap = new EvolutionWhatsAppService();
$zap->sendMessage('5511999999999', 'Seu certificado de garantia foi gerado!');
```