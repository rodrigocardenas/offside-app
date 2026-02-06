#!/bin/bash
set -e

echo "🔗 Configurando symlink de storage en producción..."

# Conectar a servidor de producción y crear symlink
ssh -i "$HOME/OneDrive/Documentos/aws/offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com << 'EOF'
cd /var/www/html/offside-app

# Crear symlink si no existe
if [ ! -L public/storage ]; then
    echo "📁 Creando symlink: public/storage -> storage/app/public"
    php artisan storage:link
    echo "✅ Symlink creado"
else
    echo "✅ Symlink ya existe"
fi

# Verificar que el symlink funciona
if [ -L public/storage ]; then
    echo "🔍 Verificando symlink..."
    ls -la public/storage | head -5
    echo "✅ Symlink funciona correctamente"
else
    echo "❌ Error: No se pudo crear el symlink"
    exit 1
fi
EOF

echo "✅ Configuración completada"
