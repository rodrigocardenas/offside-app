#!/bin/bash
set -e

echo "📥 Haciendo pull..."
cd /var/www/html/offside-app
git pull origin main

echo "🔄 Ejecutando migraciones..."
sudo -u www-data php artisan migrate --force

echo "✅ Migraciones ejecutadas exitosamente"
