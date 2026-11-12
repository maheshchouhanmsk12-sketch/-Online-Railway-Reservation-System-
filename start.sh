#!/bin/bash
set -e

# Default to port 80 if $PORT is not set
LISTEN_PORT="${PORT:-80}"

echo "[STARTUP] Configuring Apache to listen on port: ${LISTEN_PORT}..."

# Update ports.conf
sed -i "s/Listen 80/Listen ${LISTEN_PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${LISTEN_PORT}>/" /etc/apache2/sites-available/000-default.conf

# Configure Reverse Proxy Fixes in conf-available
cat << 'EOF' > /etc/apache2/conf-available/reverse-proxy.conf
ServerName localhost
UseCanonicalName Off
UseCanonicalPhysicalPort Off

<Directory /var/www/html>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
EOF

a2enconf reverse-proxy

# Ensure AllowOverride All in apache2.conf for /var/www/
sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Ensure upload directory permissions
mkdir -p /var/www/html/uploads
chown -R www-data:www-data /var/www/html/uploads
chmod -R 777 /var/www/html/uploads

echo "[STARTUP] Starting Apache foreground on port ${LISTEN_PORT}..."
exec apache2-foreground
