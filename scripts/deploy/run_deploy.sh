#!/bin/bash
sshpass -p 'Felina66@' ssh -o StrictHostKeyChecking=no root@185.182.185.58 "cd /opt/autonomia && docker compose up -d"
sshpass -p 'Felina66@' ssh -o StrictHostKeyChecking=no root@185.182.185.58 "cd /opt/traefik && docker compose up -d"
