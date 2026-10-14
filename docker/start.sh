#!/bin/sh
set -e

# Render tells the app which port to use; Apache must listen on it
PORT="${PORT:-10000}"
sed -i "s/Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Aiven requires SSL: the CA certificate is pasted into DB_CA_CERT on Render
if [ -n "$DB_CA_CERT" ]; then
    printf '%s\n' "$DB_CA_CERT" > /var/www/html/storage/aiven-ca.pem
    export MYSQL_ATTR_SSL_CA=/var/www/html/storage/aiven-ca.pem
fi

php artisan package:discover --ansi
php artisan migrate --force

# Creates the Operational Manager account once (does nothing if an admin exists)
php artisan db:seed --class=ProductionSeeder --force || true

php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground