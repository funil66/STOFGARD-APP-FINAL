<?php
$yaml = file_get_contents('/opt/autonomia/docker-compose.yml');
$yaml = preg_replace('/traefik.http.routers.autonomia-app.rule=.*/', 'traefik.http.routers.autonomia-app.rule=Host(`autonomia.app.br`) || HostRegexp(`{subdomain:[a-zA-Z0-9-]+}.autonomia.app.br`)"', $yaml);
file_put_contents('/opt/autonomia/docker-compose.yml', $yaml);
