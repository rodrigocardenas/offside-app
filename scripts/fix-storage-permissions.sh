#!/bin/bash

# 🔧 Script para corregir permisos de storage en producción
# Este script se ejecuta después de cada deploy para asegurar permisos correctos

set -e

STORAGE_PATH="/var/www/html/storage"
AVATARS_PATH="${STORAGE_PATH}/app/public/avatars"
WEB_USER="www-data"

echo "🔧 Configurando permisos de storage/app/public..."

# Crear directorio de avatars si no existe
if [ ! -d "${AVATARS_PATH}" ]; then
    echo "📁 Creando directorio ${AVATARS_PATH}..."
    mkdir -p "${AVATARS_PATH}"
fi

# Cambiar propietario de todo storage a www-data
echo "👤 Estableciendo propietario www-data:www-data en ${STORAGE_PATH}..."
chown -R ${WEB_USER}:${WEB_USER} ${STORAGE_PATH}

# Permisos para directorios
echo "🔐 Configurando permisos 775 para directorios..."
find ${STORAGE_PATH} -type d -exec chmod 775 {} \;

# Permisos para archivos
echo "🔐 Configurando permisos 664 para archivos..."
find ${STORAGE_PATH} -type f -exec chmod 664 {} \;

# Permisos especiales para directorios críticos
echo "🔐 Configurando permisos especiales..."
chmod 775 ${STORAGE_PATH}/app/public
chmod 775 ${AVATARS_PATH}
chmod 777 ${STORAGE_PATH}/framework
chmod 777 ${STORAGE_PATH}/logs

# Verificar si el symlink de public/storage existe
if [ ! -L "/var/www/html/public/storage" ]; then
    echo "🔗 Creando symlink public/storage..."
    cd /var/www/html
    php artisan storage:link
fi

echo "✅ Permisos de storage configurados correctamente"
echo "📊 Detalles:"
ls -la ${STORAGE_PATH}/app/public | grep avatars
ls -la ${AVATARS_PATH}

