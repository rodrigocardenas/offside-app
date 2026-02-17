#!/bin/bash
set -e

# --- CONFIGURACIÓN ---
SERVER_ALIAS="ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com"
REMOTE_PATH="/var/www/html"
SSH_KEY_PATH="$HOME/OneDrive/Documentos/aws/offside-new.pem"
REQUIRED_BRANCH="main"
DEPLOY_INITIATOR=$(whoami)
COMMIT_SHA=$(git rev-parse --short HEAD)
COMMIT_MESSAGE=$(git log -1 --pretty=%s | sed 's/"/\"/g')


echo "🔍 Validando entorno de despliegue..."

# 1. Validar que estamos en la rama correcta
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

if [ "$CURRENT_BRANCH" != "$REQUIRED_BRANCH" ]; then
    echo "❌ ERROR: Estás en la rama '$CURRENT_BRANCH'. Solo se permite desplegar desde '$REQUIRED_BRANCH'."
    exit 1
fi

# 2. Validar que no hay cambios sin commitear
if [ -n "$(git status --porcelain | grep -v 'build.tar.gz')" ]; then
    echo "⚠️ ADVERTENCIA: Tienes cambios locales sin guardar en Git. Haz commit antes de desplegar."
    exit 1
fi

echo "🚀 Rama validada. Iniciando despliegue de '$REQUIRED_BRANCH'..."

# 3. Compilar localmente
echo "📦 Compilando assets..."
npm run build

# 4. Comprimir
tar -czf build.tar.gz public/build

# 5. Preparar servidor y subir
ssh -T -i "$SSH_KEY_PATH" $SERVER_ALIAS << 'PRE_EOF'
    set -e
    # Asegurar que el directorio existe y tiene permisos correctos
    sudo mkdir -p /var/www/html
    sudo chown -R $USER:$USER /var/www/html || sudo chown -R ubuntu:ubuntu /var/www/html
    sudo chmod -R 755 /var/www/html
PRE_EOF

# Subir el archivo
scp -i "$SSH_KEY_PATH" build.tar.gz $SERVER_ALIAS:/tmp/

# 6. Operaciones en servidor
ssh -T -i "$SSH_KEY_PATH" $SERVER_ALIAS << EOF
    echo "🔄 Desplegando en servidor remoto..."
    set -e

    cd $REMOTE_PATH
    
    echo "🔧 Ajustando permisos previos..."
    sudo chown -R www-data:www-data . || true
    sudo chmod -R 775 storage bootstrap/cache public || true
    sudo chmod 755 bootstrap || true

    echo "🔄 Limpiando estado de git y actualizando..."
    sudo -u www-data git reset --hard HEAD || true
    sudo -u www-data git clean -fd || true
    sudo -u www-data git pull origin $REQUIRED_BRANCH || { echo "❌ Error en git pull"; exit 1; }
    
    echo "🔄 Reseteando directorios con cambios..."
    sudo -u www-data git checkout -- public/ storage/ 2>/dev/null || true
    sudo -u www-data git reset --hard HEAD || true

    echo "📦 Instalando dependencias de Composer..."
    sudo -u www-data composer install --no-interaction --optimize-autoloader --no-dev || { echo "❌ Error en composer install"; exit 1; }

    # Mover archivo después de limpiar git
    echo "📦 Preparando assets..."
    sudo mv /tmp/build.tar.gz $REMOTE_PATH/

    echo "🚧 Entrando en modo mantenimiento..."
    sudo -u www-data php artisan down --retry=60

    echo "🧹 Limpiando y extrayendo..."
    sudo rm -rf public/build
    sudo tar -xzf build.tar.gz
    sudo rm build.tar.gz

    echo "🔧 Ajustando permisos y caché..."
    sudo mkdir -p bootstrap/cache
    sudo chown -R www-data:www-data . || true
    sudo chmod -R 775 storage bootstrap/cache public || true
    sudo chmod 755 bootstrap || true

    echo "📦 Ejecutando comandos de optimización..."
    sudo -u www-data php artisan config:clear || true
    sudo -u www-data php artisan cache:clear || true
    sudo -u www-data php artisan optimize
    sudo -u www-data php artisan view:cache

    echo "🗄️ Aplicando migraciones..."
    sudo -u www-data php artisan migrate --force || true

    echo "🔗 Verificando symlink de storage..."
    sudo -u www-data php artisan storage:link --force || {
        echo "⚠️  Creando symlink manualmente..."
        sudo rm -f $REMOTE_PATH/public/storage
        sudo ln -s ../storage/app/public $REMOTE_PATH/public/storage
    }

    echo "✨ Saliendo del modo mantenimiento..."
    sudo -u www-data php artisan up

    echo "📣 Notificando despliegue exitoso..."
    sudo -u www-data php artisan deployment:notify success --branch=$REQUIRED_BRANCH --env=production --channel=deployments --initiator="$DEPLOY_INITIATOR" --commit="$COMMIT_SHA" --summary="$COMMIT_MESSAGE"

    echo "✅ Servidor actualizado exitosamente."
EOF

# 7. Limpieza local
rm build.tar.gz
echo "🎉 ¡Todo listo!"
