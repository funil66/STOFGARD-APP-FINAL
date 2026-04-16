sed -i 's/HostRegexp(`{subdomain:\[a-z0-9-\]\+}.autonomia.app.br`)/HostRegexp(`{subdomain:[a-z0-9-]+}.autonomia.app.br`)/' /opt/autonomia/docker-compose.yml
cd /opt/autonomia
docker compose up -d
