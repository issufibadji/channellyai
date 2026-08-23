# syntax=docker/dockerfile:1

# ---------- Stage 1: build de assets (Node/Vite) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---------- Stage 2: dependências PHP (Composer) ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY database/ database/
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---------- Stage 3: imagem final (PHP-FPM + Nginx) ----------
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        mysql-client \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
        zip \
        gd \
        opcache \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html

# Copia a aplicação com vendor/ já instalado (stage vendor)
COPY --from=vendor /app /var/www/html

# Copia os assets buildados (stage assets) por cima
COPY --from=assets /app/public/build /var/www/html/public/build

# Configs do Nginx, PHP-FPM e Supervisor
COPY deploy/docker/nginx.conf /etc/nginx/nginx.conf
COPY deploy/docker/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY deploy/docker/supervisord.conf /etc/supervisord.conf
COPY deploy/docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Permissões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
