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

# 4. Comprimir - incluye build assets Y storage publicos (avatares, logos)
tar -czf build.tar.gz public/build storage/app/public

# 5. Subir el archivo directamente al servidor
echo "Subiendo assets al servidor..."
scp -i "$SSH_KEY_PATH" build.tar.gz $SERVER_ALIAS:$REMOTE_PATH/ || { echo "Error al subir build.tar.gz"; exit 1; }

# 6. Operaciones en servidor
ssh -T -i "$SSH_KEY_PATH" $SERVER_ALIAS << EOF
    echo "🔄 Desplegando en servidor remoto..."
    set -e

    cd $REMOTE_PATH

    echo "🔧 Asegurando directorio de bootstrap/cache..."
    mkdir -p bootstrap/cache

    # Configurar git para ignorar cambios de permisos
    git config core.fileMode false

    echo "🔄 Limpiando estado de git y actualizando..."
    git reset --hard HEAD || true
    git clean -fd || true
    git pull origin $REQUIRED_BRANCH || { echo "❌ Error en git pull"; exit 1; }

    echo "🔄 Reseteando directorios con cambios..."
    git checkout -- public/ storage/ 2>/dev/null || true
    git reset --hard HEAD || true

    echo "📦 Verificando dependencias de Composer..."
    # Verificar si hay cambios en composer.json o composer.lock
    if git diff HEAD~1 HEAD --name-only | grep -qE 'composer\.(json|lock)'; then
        echo "🔄 Cambios detectados en composer.json/lock. Instalando dependencias..."
        composer install --no-interaction --optimize-autoloader --no-dev || { echo "❌ Error en composer install"; exit 1; }
    else
        echo "✓ Sin cambios en dependencias. Skipping composer install."
    fi

    echo "Entrando en modo mantenimiento..."
    php artisan down --retry=60

    echo "🧹 Limpiando y extrayendo..."
    rm -rf public/build
    tar -xzf build.tar.gz
    rm build.tar.gz

    echo "🔧 Preparando directorios..."
    mkdir -p bootstrap/cache
    # Asegurar permisos correctos en storage (para avatares y logos de usuarios)
    chmod -R 777 storage/app/public 2>/dev/null || true
    chown -R www-data:www-data storage/app/public 2>/dev/null || true

    echo "📦 Ejecutando comandos de optimización..."
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan optimize
    php artisan view:cache

    echo "🗄️ Aplicando migraciones..."
    php artisan migrate --force || true

    echo "� Ejecutando comandos de seguridad..."
    # Limpiar logs de seguridad antiguos (>30 días)
    php artisan tinker --execute "
      \$logPath = storage_path('logs/security.log');
      if (file_exists(\$logPath) && time() - filemtime(\$logPath) > 2592000) {
        file_put_contents(\$logPath, '');
        echo 'Logs de seguridad limpiados.' . PHP_EOL;
      }
    " || true

    # Limpiar usuarios duplicados (si CLEAN_DUPLICATES=true)
    if [ "$CLEAN_DUPLICATES" = "true" ]; then
        echo "🧹 Eliminando usuarios duplicados..."
        php artisan users:clean-duplicates --delete || {
            echo "⚠️  Aviso: No se lograron limpiar todos los duplicados"
        }
    fi

    echo "�🔗 Verificando symlink de storage..."
    php artisan storage:link --force || {
        echo "⚠️  Creando symlink manualmente..."
        rm -f $REMOTE_PATH/public/storage
        ln -s ../storage/app/public $REMOTE_PATH/public/storage
    }

    echo "✨ Saliendo del modo mantenimiento..."
    php artisan up

    echo "🔄 Reiniciando Horizon..."
    php artisan horizon:terminate || true
    sleep 3
    php artisan horizon > /dev/null 2>&1 &

    echo "📣 Notificando despliegue exitoso..."
    php artisan deployment:notify success --branch=$REQUIRED_BRANCH --env=production --channel=deployments --initiator="$DEPLOY_INITIATOR" --commit="$COMMIT_SHA" --summary="$COMMIT_MESSAGE"

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
