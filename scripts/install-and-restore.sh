#!/bin/bash

# SCRIPT COMPLETO: INSTALAR STACK LIMPIO + RESTAURAR DATOS
# USO: bash install-and-restore.sh <NEW_EC2_PUBLIC_IP>

set -e

NEW_IP="${1:-}"

if [ -z "$NEW_IP" ]; then
    echo "❌ USO: bash install-and-restore.sh <IP>"
    echo "Ejemplo: bash install-and-restore.sh 54.123.45.67"
    exit 1
fi

SSH_KEY="C:/Users/rodri/OneDrive/Documentos/aws/offside.pem"
SSH_CMD="ssh -i '$SSH_KEY' -o StrictHostKeyChecking=no ubuntu@$NEW_IP"

echo "========================================="
echo "REBUILD AUTOMÁTICO - NUEVA INSTANCIA"
echo "========================================="
echo "Target IP: $NEW_IP"
echo ""

# ============================================
# FASE 3: INSTALAR STACK LIMPIO
# ============================================

echo "FASE 3: INSTALAR STACK LIMPIO"
echo "=============================="

# Crear script de instalación remota
cat > /tmp/install-stack.sh << 'INSTALL_SCRIPT'
#!/bin/bash
set -e

echo "🔄 Actualizando sistema..."
sudo apt-get update -qq
sudo apt-get upgrade -y -qq

echo "📦 Instalando PHP 8.3..."
sudo apt-get install -y -qq php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-redis php8.3-gd php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath

echo "📦 Instalando Nginx..."
sudo apt-get install -y -qq nginx

echo "📦 Instalando Redis..."
sudo apt-get install -y -qq redis-server

echo "📦 Instalando Node.js 20..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y -qq nodejs

echo "📦 Instalando MySQL Client..."
sudo apt-get install -y -qq mysql-client-8.0

echo "📦 Instalando Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "🔧 Habilitando servicios..."
sudo systemctl enable php8.3-fpm nginx redis-server

echo "✅ Stack instalado correctamente!"
php -v
nginx -v
redis-cli ping
INSTALL_SCRIPT

# Copiar y ejecutar script de instalación
echo "📤 Copiando script de instalación..."
scp -i "$SSH_KEY" -o StrictHostKeyChecking=no /tmp/install-stack.sh "ubuntu@$NEW_IP:/tmp/"

echo "🚀 Ejecutando instalación remota..."
$SSH_CMD "bash /tmp/install-stack.sh"

echo "✅ FASE 3 COMPLETADA"
echo ""

# ============================================
# FASE 4: RESTAURAR DATOS
# ============================================

echo "FASE 4: RESTAURAR DATOS"
echo "======================"

echo "📤 Copiando backup de DB..."
scp -i "$SSH_KEY" -o StrictHostKeyChecking=no db-backup.sql "ubuntu@$NEW_IP:/tmp/"

echo "📤 Copiando storage..."
scp -i "$SSH_KEY" -o StrictHostKeyChecking=no -r backup-storage-20260208 "ubuntu@$NEW_IP:/tmp/storage-backup"

echo "🔄 Restaurando base de datos..."
$SSH_CMD << 'RESTORE_DB'
MYSQL_PWD='offside.2025' mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin << EOF
DROP DATABASE IF EXISTS offsideclub;
CREATE DATABASE offsideclub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF

MYSQL_PWD='offside.2025' mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin offsideclub < /tmp/db-backup.sql
echo "✅ Base de datos restaurada"
RESTORE_DB

echo "✅ FASE 4 COMPLETADA"
echo ""

# ============================================
# FASE 5: DESPLEGAR CÓDIGO
# ============================================

echo "FASE 5: DESPLEGAR CÓDIGO"
echo "======================="

$SSH_CMD << 'DEPLOY'
cd /var/www/html

# Clonar repositorio
echo "📥 Clonando repositorio..."
git clone https://github.com/rodrigocardenas/offside-app.git offside-app-new
cd offside-app-new

# Copiar .env
echo "🔧 Configurando .env..."
cat > .env << ENV_FILE
APP_NAME="Offside Club"
APP_ENV=production
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=false
APP_URL=https://offsideclub.app

DB_CONNECTION=mysql
DB_HOST=database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=offsideclub
DB_USERNAME=admin
DB_PASSWORD=offside.2025

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SESSION_DRIVER=redis
CACHE_DRIVER=redis

QUEUE_CONNECTION=sync

APP_TIMEZONE="Europe/Madrid"
ENV_FILE

# Instalar dependencias
echo "📦 Instalando Composer..."
composer install --no-dev --optimize-autoloader

# Generar key Laravel
php artisan key:generate

# Ejecutar migraciones
echo "🔄 Ejecutando migraciones..."
php artisan migrate --force

# Copiar storage
echo "📤 Restaurando storage..."
rm -rf storage
cp -r /tmp/storage-backup storage
chmod -R 755 storage
chmod 644 storage/app/public/*

# Instalar landing (Next.js)
if [ -d "offside-landing" ]; then
    echo "📦 Instalando Next.js landing..."
    cd offside-landing
    npm ci
    npm run build
    cd ..
fi

echo "✅ Código desplegado"
DEPLOY

echo "✅ FASE 5 COMPLETADA"
echo ""

# ============================================
# FASE 6: TESTING
# ============================================

echo "FASE 6: TESTING"
echo "==============="

echo "🧪 Probando base de datos..."
$SSH_CMD "MYSQL_PWD='offside.2025' mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin offsideclub -e 'SELECT COUNT(*) as users FROM users;'"

echo "🧪 Probando Redis..."
$SSH_CMD "redis-cli ping"

echo "🧪 Probando PHP..."
$SSH_CMD "php -v"

echo "🧪 Probando Nginx..."
$SSH_CMD "sudo nginx -t"

echo "✅ FASE 6 COMPLETADA"
echo ""

# ============================================
# RESUMEN FINAL
# ============================================

echo "========================================="
echo "✅ REBUILD COMPLETADO EXITOSAMENTE"
echo "========================================="
echo ""
echo "Nueva instancia: $NEW_IP"
echo ""
echo "Próximos pasos:"
echo "1. Probar aplicación: http://$NEW_IP"
echo "2. Verificar login y funcionalidades"
echo "3. Actualizar DNS o Elastic IP"
echo "4. Terminar instancia antigua"
echo ""
