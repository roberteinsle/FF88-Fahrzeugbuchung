FROM serversideup/php:8.4-fpm-nginx-alpine

# Install intl extension (required by Filament)
RUN install-php-extensions intl

WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist

# Install Node.js dependencies and build frontend assets
RUN npm ci --silent && npm run build && rm -rf node_modules

# Ensure writable directories are owned by web user
RUN chown -R www-data:www-data storage bootstrap/cache
