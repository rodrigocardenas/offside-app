#!/bin/bash
set -e

# Script para reparar permisos en producción
# Uso: bash scripts/fix-permissions.sh
# Requiere: SSH key configurada para la instancia EC2

SERVER="ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com"

echo "🔧 Reparando permisos en $SERVER..."

ssh -T $SERVER << 'SSH_EOF'
    set -e
    cd /var/www/html
    
    echo "📝 Directorio actual: $(pwd)"
    echo ""
    echo "1️⃣ Asignando www-data como propietario..."
    sudo chown -R www-data:www-data .
    
    echo "2️⃣ Configurando permisos de directorios..."
    sudo find . -type d -exec chmod 755 {} \;
    
    echo "3️⃣ Configurando permisos de archivos..."
    sudo find . -type f -exec chmod 644 {} \;
    
    echo "4️⃣ Configurando directorios especiales..."
    sudo chmod -R 775 storage bootstrap/cache public
    
    echo "5️⃣ Configurando ACL para nuevos archivos..."
    sudo setfacl -R -m u:www-data:rwx storage bootstrap/cache public 2>/dev/null || true
    
    echo ""
    echo "✅ Limpiando caché..."
    sudo -u www-data php artisan cache:clear || true
    sudo -u www-data php artisan config:clear || true
    
    echo ""
    echo "⚙️ Optimizando framework..."
    sudo -u www-data php artisan optimize
    
    echo ""
    echo "📦 Cacheando vistas..."
    sudo -u www-data php artisan view:cache || true
    
    echo ""
    echo "✅ ¡Permisos reparados exitosamente!"
SSH_EOF

echo "🎉 ¡Listo!"
