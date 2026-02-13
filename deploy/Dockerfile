FROM php:8.4-fpm

# Arguments defined in docker-compose.yml
ARG user=www
ARG uid=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    supervisor \
    cron \
    gnupg \
    libnss3 \
    libnspr4 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libxkbcommon0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libasound2 \
    libpango-1.0-0 \
    libcairo2 \
    libglib2.0-0 \
    # Fontes para PDF
    fonts-liberation \
    fontconfig \
    mariadb-client \
    # Clean up
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure MySQL client to skip SSL (Fixes backup issues)
RUN mkdir -p /etc/mysql/conf.d && \
    echo "[client]\nssl=0" > /etc/mysql/conf.d/no-ssl.cnf

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js & NPM (for frontend build & puppeteer)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
RUN apt-get install -y nodejs

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
# Add www-data to the www group so PHP-FPM can write to files owned by www
RUN usermod -aG $user www-data

RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Set working directory
WORKDIR /var/www

# Copy custom configurations
COPY deploy/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY deploy/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application code
COPY . /var/www

# Set permissions
RUN chown -R $user:$user /var/www

# Switch to user
USER $user

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Switch back to root for supervisor
USER root

# Expose port 9000 and start supervisor (which runs php-fpm)
EXPOSE 9000
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
