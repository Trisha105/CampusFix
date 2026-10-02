#!/bin/sh
set -eu

port="${PORT:-10000}"
case "$port" in
    ''|*[!0-9]*) echo 'PORT must be a number between 1 and 65535.' >&2; exit 1 ;;
esac
if [ "$port" -lt 1 ] || [ "$port" -gt 65535 ]; then
    echo 'PORT must be a number between 1 and 65535.' >&2
    exit 1
fi

# Render routes traffic to this port; Apache must listen on all interfaces.
sed -ri "s/^Listen [0-9]+$/Listen 0.0.0.0:${port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

# Apply pending additive migrations and provision an optional first administrator.
php /var/www/html/database/bootstrap.php
exec apache2-foreground
