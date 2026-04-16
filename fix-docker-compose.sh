sed -i '/traefik.http.routers.autonomia-app.tls.certresolver=cloudflare/a \      - "traefik.http.routers.autonomia-app.tls.domains[0].main=autonomia.app.br"\n      - "traefik.http.routers.autonomia-app.tls.domains[0].sans=*.autonomia.app.br"' /opt/autonomia/docker-compose.yml
cd /opt/autonomia
docker compose up -d
