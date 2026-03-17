#!/bin/bash
# Ejecutar en el VPS desde: /var/www/html/mp-admin-vps
#
# ANTES ejecuta una sola vez (con tu contraseña de sudo y de MySQL):
#   sudo apt update && sudo apt install -y php8.1-mysql
#   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS mp_admin_vps CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# Y en .env pon DB_PASSWORD=tu_password_mysql si root tiene contraseña.
#
set -e
cd "$(dirname "$0")"

echo "=== Migraciones ==="
php artisan migrate --no-interaction

echo "=== Seeders ==="
php artisan db:seed --no-interaction

echo "=== Listo. Accede con: admin@mpadmin.local / password ==="
