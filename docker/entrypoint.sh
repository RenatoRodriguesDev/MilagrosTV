#!/bin/sh
set -e

# Cria a base de dados se não existir
if [ ! -f /var/www/database/database.sqlite ]; then
    touch /var/www/database/database.sqlite
    chown www-data:www-data /var/www/database/database.sqlite
fi

# Garante pastas de storage com permissões correctas
mkdir -p /var/www/storage/framework/cache/data \
         /var/www/storage/framework/sessions \
         /var/www/storage/framework/views \
         /var/www/storage/logs
chown -R www-data:www-data /var/www/storage
chmod -R 775 /var/www/storage

# Executa migrations
php artisan migrate --force

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
