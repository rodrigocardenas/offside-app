#!/bin/bash

# 🚀 Deploy Storage Permissions Fix
# Se ejecuta después de composer install/update

APP_PATH="/var/www/html/offside-app"
STORAGE_PATH="$APP_PATH/storage/app/public"

echo "================================"
echo "🔧 Fijando permisos de storage"
echo "================================"

# Verificar que existe la carpeta
if [ ! -d "$STORAGE_PATH" ]; then
    echo "❌ Carpeta $STORAGE_PATH no existe"
    exit 1
fi

# Fijar permisos de directorios a 755
echo "📁 Fijando permisos de directorios a 755..."
sudo find "$STORAGE_PATH" -type d -exec chmod 755 {} \;

# Fijar permisos de archivos a 644
echo "📄 Fijando permisos de archivos a 644..."
sudo find "$STORAGE_PATH" -type f -exec chmod 644 {} \;

# Asegurar que www-data es propietario
echo "👤 Asignando propietario a www-data:www-data..."
sudo chown -R www-data:www-data "$STORAGE_PATH"

# Verificar resultados
echo ""
echo "✅ Verificación de permisos:"
echo ""
echo "📊 Directorios:"
sudo find "$STORAGE_PATH" -type d -printf "%m %p\n" | head -5

echo ""
echo "📊 Archivos (últimos 5):"
sudo find "$STORAGE_PATH" -type f -printf "%m %p\n" | tail -5

echo ""
echo "✅ Deploy storage permissions fix completado"
