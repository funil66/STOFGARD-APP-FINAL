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
3. Sobe os containers (App, Nginx, Redis, MySQL) usando `docker-compose.prod.yml`.
4. Executa comandos finais:
   - `composer install`
   - `artisan migrate`
   - `artisan storage:link`
   - `artisan optimize`

## Verificação

Após o script rodar, verifique se os serviços estão de pé:

```bash
docker ps
```
