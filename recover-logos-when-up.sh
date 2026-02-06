#!/bin/bash
# Script de recuperación para enlace roto de logos
# Ejecutar cuando el servidor ec2-54-172-59-146 esté de nuevo activo

set -e

SERVER="ec2-54-172-59-146.compute-1.amazonaws.com"
KEY="C:/Users/rodri/OneDrive/Documentos/aws/offside.pem"
APP_PATH="/var/www/html/offside-app"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔧 Reparando enlaces rotos de logos"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Intentar conexión
echo "🔍 Verificando conexión al servidor..."
if ! timeout 5 ssh -o ConnectTimeout=3 -i "$KEY" "ubuntu@$SERVER" "echo 'OK'" > /dev/null 2>&1; then
    echo "❌ ERROR: Servidor $SERVER no responde"
    echo ""
    echo "Posibles causas:"
    echo "  1. Instancia EC2 está apagada"
    echo "  2. Security group no permite SSH (puerto 22)"
    echo "  3. Servidor está caído"
    echo ""
    echo "📞 Acciones:"
    echo "  1. Verifica AWS Console: https://console.aws.amazon.com/ec2"
    echo "  2. Reinicia la instancia si está detenida"
    echo "  3. Intenta de nuevo"
    exit 1
fi

echo "✅ Servidor respondiendo"
echo ""

# Crear symlink
echo "🔗 Creando symlink de storage..."
ssh -i "$KEY" "ubuntu@$SERVER" << SSHCOMMAND
    set -e
    cd $APP_PATH

    # Mostrar estado actual
    echo "Estado actual:"
    if [ -L public/storage ]; then
        echo "  ✅ Symlink ya existe"
        readlink public/storage
    elif [ -d public/storage ]; then
        echo "  ⚠️  Directorio común encontrado"
    else
        echo "  ❌ No existe symlink"
    fi

    echo ""
    echo "Creando symlink..."

    # Remover si existe
    sudo rm -f public/storage 2>/dev/null || true

    # Crear symlink
    sudo ln -s ../storage/app/public public/storage

    # Verificar
    if [ -L public/storage ]; then
        echo "✅ Symlink creado exitosamente"
        echo "   Target: $(readlink public/storage)"
    else
        echo "❌ Error al crear symlink"
        exit 1
    fi

    # Mostrar logos
    echo ""
    echo "📸 Verificando logos..."
    LOGO_COUNT=\$(ls $APP_PATH/storage/app/public/logos/ 2>/dev/null | wc -l)
    echo "   Logos encontrados: \$LOGO_COUNT"

    # Limpiar caché
    echo ""
    echo "🧹 Limpiando caché..."
    sudo -u www-data php artisan cache:clear 2>/dev/null || true

    echo "✅ Completado"
SSHCOMMAND

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✨ Reparación completada"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Para verificar que funciona:"
echo "  1. Abre: https://tu-dominio.com/storage/logos/Arsenal.png"
echo "  2. Debería mostrar la imagen correctamente"
echo ""
