# 🚀 Guia de Deploy Manual

Como não tenho acesso direto ao terminal da VPS, preparei este guia e um script para facilitar o deploy.

## Passo 1: Atualizar o Código na VPS

No terminal da VPS (que você já tem aberto), navegue até a pasta do projeto e atualize o código:

```bash
cd /caminho/para/seu/projeto
git pull origin main
```

## Passo 2: Executar o Script de Deploy

Dê permissão de execução ao script e rode-o:

```bash
chmod +x deploy/deploy.sh
./deploy/deploy.sh
```

## O que o script faz?

1. Verifica se o Docker está instalado.
2. Cria o arquivo `.env` a partir de `.env.prod` (já configurado com Mysql/Redis) se não existir.
3. Sobe os containers (App, Nginx, Redis, MySQL) usando auto-detecção de compose (`docker-compose.prod.yml`, `docker-compose.standalone.yml`, `compose.yaml` ou `docker-compose.yml`).
4. Executa comandos finais:
   - `composer install`
   - `artisan migrate`
   - `artisan storage:link`
   - `artisan optimize`

### Regras de segurança no deploy

- O script **não** regenera `APP_KEY` a cada deploy.
- `APP_KEY` só é gerada quando estiver ausente no ambiente.
- Worker de fila usa conexão configurável por `QUEUE_CONNECTION` (sem fixar `redis`).

## Verificação

Após o script rodar, verifique se os serviços estão de pé:

```bash
docker ps
```

## Billing / PIX (importante)

- O fluxo legado web (`/pagamento/{hash}` e `/webhook/pix`) está desativado por padrão.
- Use o webhook canônico: `POST /api/webhooks/pix/{webhookToken}`.
- No `.env`, mantenha:

```dotenv
LEGACY_PIX_FLOW_ENABLED=false
LEGACY_PIX_WEBHOOK_ENABLED=false
```

## Smoke test pós-deploy

- Checklist rápido: [docs/SMOKE_TEST_POS_DEPLOY.md](docs/SMOKE_TEST_POS_DEPLOY.md)
- Execução automatizada:

```bash
chmod +x scripts/post_deploy_smoke.sh
./scripts/post_deploy_smoke.sh
```
