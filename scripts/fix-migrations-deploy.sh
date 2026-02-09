#!/bin/bash
set -e

cd /var/www/html/offside-app

echo "📥 Actualizando código..."
git pull origin main

echo "🔄 Haciendo rollback de migraciones problemáticas..."
# Hacer rollback de los últimos batches hasta antes del error
php artisan migrate:rollback --step=5 --force 2>&1 | grep -v "^$" || true

echo "🚀 Ejecutando todas las migraciones nuevamente..."
php artisan migrate --force 2>&1 | tail -20

echo "✅ Proceso completado"
