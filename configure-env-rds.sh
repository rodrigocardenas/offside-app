#!/bin/bash

# Script para configurar .env en la instancia EC2 con datos de RDS
# Uso: bash configure-env-rds.sh

set -e

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║     Configurador de Variables de Entorno - Offside Club        ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Variables necesarias
read -p "RDS Host (ej: offside-db.c2j8xr6wq0qp.us-east-1.rds.amazonaws.com): " DB_HOST
read -p "RDS Port [3306]: " DB_PORT
DB_PORT=${DB_PORT:-3306}
read -p "RDS Database Name [offside_app]: " DB_DATABASE
DB_DATABASE=${DB_DATABASE:-offside_app}
read -p "RDS Username [admin]: " DB_USERNAME
DB_USERNAME=${DB_USERNAME:-admin}
read -sp "RDS Password: " DB_PASSWORD
echo ""

read -p "FIREBASE_PRIVATE_KEY (pegarlo completo): " FIREBASE_KEY
read -p "GEMINI_API_KEY: " GEMINI_KEY
read -p "OPENAI_API_KEY: " OPENAI_KEY
read -p "API_FOOTBALL_KEY: " FOOTBALL_KEY

read -p "APP_URL [https://app.offsideclub.es]: " APP_URL
APP_URL=${APP_URL:-https://app.offsideclub.es}

read -p "REDIS_HOST [localhost]: " REDIS_HOST
REDIS_HOST=${REDIS_HOST:-localhost}
read -p "REDIS_PORT [6379]: " REDIS_PORT
REDIS_PORT=${REDIS_PORT:-6379}

echo ""
echo "📝 Copiando .env a /var/www/html/offside-app/.env..."
sudo cp /tmp/.env /var/www/html/offside-app/.env

echo "⚙️  Actualizando variables..."

# Actualizar valores en .env
sudo sed -i "s|DB_HOST=.*|DB_HOST=$DB_HOST|" /var/www/html/offside-app/.env
sudo sed -i "s|DB_PORT=.*|DB_PORT=$DB_PORT|" /var/www/html/offside-app/.env
sudo sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" /var/www/html/offside-app/.env
sudo sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" /var/www/html/offside-app/.env
sudo sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" /var/www/html/offside-app/.env
sudo sed -i "s|APP_URL=.*|APP_URL=$APP_URL|" /var/www/html/offside-app/.env
sudo sed -i "s|REDIS_HOST=.*|REDIS_HOST=$REDIS_HOST|" /var/www/html/offside-app/.env
sudo sed -i "s|REDIS_PORT=.*|REDIS_PORT=$REDIS_PORT|" /var/www/html/offside-app/.env

# Actualizar API keys (escape special characters)
FIREBASE_KEY_ESCAPED=$(printf '%s\n' "$FIREBASE_KEY" | sed -e 's/[\/&]/\\&/g')
sudo sed -i "s|FIREBASE_PRIVATE_KEY=.*|FIREBASE_PRIVATE_KEY=$FIREBASE_KEY_ESCAPED|" /var/www/html/offside-app/.env
sudo sed -i "s|GEMINI_API_KEY=.*|GEMINI_API_KEY=$GEMINI_KEY|" /var/www/html/offside-app/.env
sudo sed -i "s|OPENAI_API_KEY=.*|OPENAI_API_KEY=$OPENAI_KEY|" /var/www/html/offside-app/.env
sudo sed -i "s|API_FOOTBALL_KEY=.*|API_FOOTBALL_KEY=$FOOTBALL_KEY|" /var/www/html/offside-app/.env

echo "✅ Variables actualizadas"
echo ""
echo "📋 Contenido de .env (primeras 25 líneas):"
sudo head -25 /var/www/html/offside-app/.env
echo ""
echo "✅ Configuración completada"
echo ""
echo "📌 Próximos pasos:"
echo "   1. Testear conexión a RDS:"
echo "      php artisan tinker"
echo "      DB::connection()->getPdo();"
echo ""
echo "   2. Ver logs si hay error:"
echo "      tail -f /var/www/html/offside-app/storage/logs/laravel.log"
echo ""
