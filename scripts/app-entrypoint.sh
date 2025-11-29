#!/bin/sh
set -e
mkdir -p /var/www/html/uploads /var/www/html/admin/logs
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 777 /var/www/html/uploads /var/www/html/admin/logs
if [ ! -f /var/www/html/config.php ] && [ -f /var/www/html/config-sample.php ]; then
  cp /var/www/html/config-sample.php /var/www/html/config.php
fi
apache2-foreground
