#!/bin/bash
sshpass -p 'Felina66@' ssh -o StrictHostKeyChecking=no root@185.182.185.58 "
echo 'Waiting for containers to be up...'
while ! docker ps | grep autonomia-app > /dev/null; do
  sleep 5
done
echo 'Running post-deploy hooks...'
docker exec autonomia-app php artisan migrate --force
docker exec autonomia-app php artisan tenants:migrate --force
docker exec autonomia-app php artisan optimize:clear
"
