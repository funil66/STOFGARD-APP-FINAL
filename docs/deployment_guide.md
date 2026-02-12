# Stofgard Deployment Guide

## Docker Infrastructure
The application has been containerized using `php:8.2-fpm` and `nginx`.

### Files Created
- **Dockerfile**: `deploy/Dockerfile`
- **Compose File**: `docker-compose.prod.yml`
- **Supervisor**: `deploy/supervisord.conf` (Queues & Scheduler)
- **PHP Config**: `deploy/php.ini`
- **Nginx Config**: `deploy/nginx/conf.d/app.conf`

## How to Deploy

### 1. Environment Setup
Create a `.env` file based on `.env.example` but with production values:
```bash
cp .env.example .env
```
**Critical Changes for Production:**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `QUEUE_CONNECTION=redis`
- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`

### 2. Build and Run
Run the following command to build the images and start the containers:
```bash
docker compose -f docker-compose.prod.yml up -d --build
```

### 3. Post-Deployment Steps
After the containers are running, execute these commands inside the `stofgard-app` container:

```bash
# Enter the container
docker exec -it stofgard-app bash

# Run migrations
php artisan migrate --force

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create symbolic link for storage
php artisan storage:link
```

## Maintenance
- **Logs**: `docker compose -f docker-compose.prod.yml logs -f`
- **Update**: `git pull && docker compose -f docker-compose.prod.yml up -d --build`
- **Queue Status**: The supervisor process inside the container manages the queue workers automatically.
