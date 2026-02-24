#!/bin/bash

# Conectar al servidor y obtener info de migraciones
ssh offside-app << 'EOF'
cd /var/www/html/offside-app

echo "=== MIGRACIONES PENDIENTES ==="
php artisan migrate:status --pending 2>&1

echo ""
echo "=== MIGRACIONES FALLIDAS (buscando en tabla migrations) ==="
mysql -u root -proot -e "SELECT migration FROM offside_production.migrations WHERE migration LIKE '%favorite_teams%' OR migration LIKE '%add_is_admin%' OR migration LIKE '%add_avatar%' OR migration LIKE '%add_theme%' OR migration LIKE '%add_language%';"

echo ""
echo "=== COLUMNAS EN TABLA USERS ==="
mysql -u root -proot -e "DESCRIBE offside_production.users;" | grep -E "(language|favorite|avatar|is_admin|theme)"
EOF
