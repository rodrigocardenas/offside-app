#!/bin/bash
# Script para ejecutar en el servidor EC2 via SSH

cd /var/www/html/offside-app

echo "🔧 Verificando symlink de storage..."

# Si existe y es un symlink roto, eliminarlo
if [ -L public/storage ] && [ ! -e public/storage ]; then
    echo "⚠️  Symlink roto detectado, eliminando..."
    rm -f public/storage
fi

# Si existe pero es un directorio normal (no symlink), moverlo
if [ -d public/storage ] && [ ! -L public/storage ]; then
    echo "⚠️  Directorio storage normal detectado, moviendo..."
    mv public/storage public/storage.bak
fi

# Crear symlink
if [ ! -L public/storage ]; then
    echo "📁 Creando symlink: public/storage → ../storage/app/public"
    ln -s ../storage/app/public public/storage
fi

# Verificar
if [ -L public/storage ]; then
    echo "✅ Symlink OK"
    echo "   Apunta a: $(readlink public/storage)"
fi

# Verificar acceso a logos
if [ -f storage/app/public/logos/Arsenal.png ]; then
    echo "✅ Logos accesibles"
else
    echo "⚠️  No hay logos en storage/app/public/logos/"
fi

echo "✅ Configuración completada"
