#!/bin/bash
set -e

# --- CONFIGURACIÓN ---
SERVER_ALIAS="${DEPLOY_SERVER:-ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com}"
REMOTE_PATH="/var/www/html"
# SSH_KEY_PATH debe estar en variable de entorno para no exponer ruta en Git
SSH_KEY_PATH="${SSH_KEY_PATH:-}"
REQUIRED_BRANCH="main"
DEPLOY_INITIATOR=$(whoami)
COMMIT_SHA=$(git rev-parse --short HEAD)
COMMIT_MESSAGE=$(git log -1 --pretty=%s | sed 's/"/\"/g')
DEPLOY_TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
CLEAN_DUPLICATES="${CLEAN_DUPLICATES:-false}"


echo "🔍 Validando entorno de despliegue..."

# 0. Validar que existe SSH_KEY_PATH
if [ -z "$SSH_KEY_PATH" ] || [ ! -f "$SSH_KEY_PATH" ]; then
    echo "❌ ERROR: No se encontró SSH_KEY_PATH"
    echo "   Configura: export SSH_KEY_PATH=/ruta/a/offside-new.pem"
    echo "   O establece en archivo: ~/.offside-deploy.env"
    exit 1
fi

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
    sudo chown -R ubuntu:ubuntu /var/www/html 2>/dev/null || true
    # Evitar chmod -R recursivo que consume mucho - usar chmod selectivo
    sudo chmod 755 /var/www/html 2>/dev/null || true
    sudo bash -c 'find /var/www/html -maxdepth 1 -type d -exec chmod 755 {} \; 2>/dev/null || true'
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
    # Permisos selectivos para evitar timeouts
    sudo bash -c 'chmod 755 . bootstrap 2>/dev/null || true && find storage bootstrap/cache public -maxdepth 2 -type d -exec chmod 775 {} \; 2>/dev/null || true' || true

    # Configurar git para ignorar cambios de permisos
    sudo -u www-data git config core.fileMode false

    echo "🔄 Limpiando estado de git y actualizando..."
    sudo -u www-data git reset --hard HEAD || true
    sudo -u www-data git clean -fd || true
    sudo -u www-data git pull origin $REQUIRED_BRANCH || { echo "❌ Error en git pull"; exit 1; }

    echo "🔄 Reseteando directorios con cambios..."
    sudo -u www-data git checkout -- public/ storage/ 2>/dev/null || true
    sudo -u www-data git reset --hard HEAD || true

    echo "📦 Verificando dependencias de Composer..."
    # Verificar si hay cambios en composer.json o composer.lock
    if git diff HEAD~1 HEAD --name-only | grep -qE 'composer\.(json|lock)'; then
        echo "🔄 Cambios detectados en composer.json/lock. Instalando dependencias..."
        sudo -u www-data composer install --no-interaction --optimize-autoloader --no-dev || { echo "❌ Error en composer install"; exit 1; }
    else
        echo "✓ Sin cambios en dependencias. Skipping composer install."
    fi

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
    # Permisos selectivos para evitar timeouts
    sudo bash -c 'chmod 755 . bootstrap 2>/dev/null || true && find storage bootstrap/cache public -maxdepth 2 -type d -exec chmod 775 {} \; 2>/dev/null || true' || true

    echo "📦 Ejecutando comandos de optimización..."
    sudo -u www-data php artisan config:clear || true
    sudo -u www-data php artisan cache:clear || true
    sudo -u www-data php artisan optimize
    sudo -u www-data php artisan view:cache

    echo "🗄️ Aplicando migraciones..."
    sudo -u www-data php artisan migrate --force || true

    echo "� Ejecutando comandos de seguridad..."
    # Limpiar logs de seguridad antiguos (>30 días)
    sudo -u www-data php artisan tinker --execute "
      \$logPath = storage_path('logs/security.log');
      if (file_exists(\$logPath) && time() - filemtime(\$logPath) > 2592000) {
        file_put_contents(\$logPath, '');
        echo 'Logs de seguridad limpiados.' . PHP_EOL;
      }
    " || true

    # Limpiar usuarios duplicados (si CLEAN_DUPLICATES=true)
    if [ "$CLEAN_DUPLICATES" = "true" ]; then
        echo "🧹 Eliminando usuarios duplicados..."
        sudo -u www-data php artisan users:clean-duplicates --delete || {
            echo "⚠️  Aviso: No se lograron limpiar todos los duplicados"
        }
    fi

    echo "�🔗 Verificando symlink de storage..."
    sudo -u www-data php artisan storage:link --force || {
        echo "⚠️  Creando symlink manualmente..."
        sudo rm -f $REMOTE_PATH/public/storage
        sudo ln -s ../storage/app/public $REMOTE_PATH/public/storage
    }

    echo "✨ Saliendo del modo mantenimiento..."
    sudo -u www-data php artisan up

    echo "� Reiniciando Horizon..."
    sudo -u www-data php artisan horizon:terminate || true
    sleep 3
    sudo -u www-data php artisan horizon > /dev/null 2>&1 &

    echo "�📣 Notificando despliegue exitoso..."
    sudo -u www-data php artisan deployment:notify success --branch=$REQUIRED_BRANCH --env=production --channel=deployments --initiator="$DEPLOY_INITIATOR" --commit="$COMMIT_SHA" --summary="$COMMIT_MESSAGE"

    echo "✅ Servidor actualizado exitosamente."
EOF

# 7. Limpieza local
rm build.tar.gz

# 8. Información de despliegue
echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                  ✅ DESPLIEGUE COMPLETADO                 ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📊 INFORMACIÓN DE DESPLIEGUE:"
echo "   Servidor:     $SERVER_ALIAS"
echo "   Rama:         $REQUIRED_BRANCH"
echo "   Commit:       $COMMIT_SHA - $COMMIT_MESSAGE"
echo "   Usuario:      $DEPLOY_INITIATOR"
echo "   Timestamp:    $DEPLOY_TIMESTAMP"
echo ""
echo "🔗 Logs disponibles:"
echo "   SSH:          ssh -i \$SSH_KEY_PATH $SERVER_ALIAS 'tail -f /var/log/nginx/error.log'"
echo "   App:          ssh -i \$SSH_KEY_PATH $SERVER_ALIAS 'tail -f /var/www/html/storage/logs/laravel.log'"
echo "   Seguridad:    ssh -i \$SSH_KEY_PATH $SERVER_ALIAS 'tail -f /var/www/html/storage/logs/security.log'"
echo ""
echo "🎉 ¡Todo listo!"
