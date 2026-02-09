#!/bin/bash
set -e

# Script para reparar permisos en producción
# Uso: bash scripts/fix-permissions.sh

echo "🔧 Reparando permisos en /var/www/html..."

ssh -i "$HOME/OneDrive/Documentos/aws/offside.pem" ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com << 'SSH_EOF'
    set -e
    cd /var/www/html
    
    echo "📝 Directorio actual: $(pwd)"
    echo ""
    echo "🔐 Corrigiendo propietario..."
    sudo chown -R www-data:www-data . || true
    
    echo "📋 Corrigiendo permisos de directorios..."
    sudo chmod -R 755 . || true
    sudo chmod -R 775 storage bootstrap/cache public || true
    
    echo "✅ Limpiando caché..."
    sudo -u www-data php artisan cache:clear || true
    sudo -u www-data php artisan config:clear || true
    
    echo "⚙️ Optimizando framework..."
    sudo -u www-data php artisan optimize
    
    echo "📦 Cacheando vistas..."
    sudo -u www-data php artisan view:cache || true
    
    echo ""
    echo "✅ ¡Permisos reparados exitosamente!"
SSH_EOF

echo "🎉 ¡Listo!"
