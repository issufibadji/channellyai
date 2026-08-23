#!/bin/bash
set -e

cd /var/www/html

# Gera APP_KEY automaticamente se não vier definida via env
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY não definida - gerando..."
    php artisan key:generate --force
fi

# Cria o symlink de storage se ainda não existir
if [ ! -L "public/storage" ]; then
    php artisan storage:link || true
fi

# Aguarda o banco de dados responder antes de migrar (evita crash em cold start)
echo "Aguardando banco de dados..."
until php artisan db:show > /dev/null 2>&1; do
    sleep 2
done

php artisan migrate --force

# Cachear configs, rotas e views para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "Entrypoint concluído. Subindo serviços..."

exec "$@"
