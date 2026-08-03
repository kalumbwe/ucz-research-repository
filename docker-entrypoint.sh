#!/bin/bash
set -e

: "${PORT:=10000}"

# Fix disk permissions at startup (disk mount replaces the build-time directory)
mkdir -p /var/www/html/storage/uploads
chown -R www-data:www-data /var/www/html/storage/uploads
chmod -R 775 /var/www/html/storage/uploads

sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground