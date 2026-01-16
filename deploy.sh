#!/bin/bash
set -e

# --- CONFIGURACIÓN ---
SERVER_ALIAS="offside-app"
REMOTE_PATH="/var/www/html/offside-app"
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
if [ -n "$(git status --porcelain)" ]; then
    echo "⚠️ ADVERTENCIA: Tienes cambios locales sin guardar en Git. Haz commit antes de desplegar."
    exit 1
fi

echo "🚀 Rama validada. Iniciando despliegue de '$REQUIRED_BRANCH'..."

# 3. Compilar localmente
echo "📦 Compilando assets..."
npm run build

# 4. Comprimir
tar -czf build.tar.gz public/build

# 5. Subir
scp build.tar.gz $SERVER_ALIAS:$REMOTE_PATH

# 6. Operaciones en servidor
ssh -T $SERVER_ALIAS << EOF
    echo "🔄 Desplegando en servidor remoto..."
    set -e
    cd $REMOTE_PATH
    sudo git checkout -- .
    echo "� Actualizando código desde Git..."
    sudo git pull origin $REQUIRED_BRANCH

    echo "�🚧 Entrando en modo mantenimiento..."
    sudo -u www-data php artisan down --retry=60

    echo "🧹 Limpiando y extrayendo..."
    sudo rm -rf public/build
    sudo tar -xzf build.tar.gz
    sudo rm build.tar.gz

    echo "🔧 Ajustando permisos y caché..."
    sudo chown -R www-data:www-data public/build storage bootstrap/cache
    sudo -u www-data php artisan optimize
    sudo -u www-data php artisan view:cache
    sudo -u www-data php artisan migrate

    echo "✨ Saliendo del modo mantenimiento..."
    sudo -u www-data php artisan up

    echo "📣 Notificando despliegue exitoso..."
    sudo -u www-data php artisan deployment:notify success --branch=$REQUIRED_BRANCH --env=production --channel=deployments --initiator="$DEPLOY_INITIATOR" --commit="$COMMIT_SHA" --summary="$COMMIT_MESSAGE"

    echo "✅ Servidor actualizado exitosamente."
EOF

# 7. Limpieza local
rm build.tar.gz
echo "🎉 ¡Todo listo!"
