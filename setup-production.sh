#!/bin/bash
# OFFSIDE CLUB - PRODUCTION SERVER SETUP SCRIPT
# Automated setup for new clean Ubuntu 24.04 instance
# Usage: sudo bash setup-production.sh

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  🚀 OFFSIDE CLUB - PRODUCTION SERVER SETUP${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""

# Configuration
APP_USER="www-data"
APP_GROUP="www-data"
APP_DIR="/var/www/html/offside-app"
REPO_URL="https://github.com/rodrigocardenas/offside-app.git"

echo -e "${YELLOW}📋 Configuración:${NC}"
echo "   App User: $APP_USER"
echo "   App Group: $APP_GROUP"
echo "   App Dir: $APP_DIR"
echo "   Repo: $REPO_URL"
echo ""

# 1. UPDATE SYSTEM
echo -e "${BLUE}1️⃣ Actualizando sistema...${NC}"
apt-get update
apt-get upgrade -y
apt-get install -y curl wget git vim nano htop net-tools ufw fail2ban unattended-upgrades

# 2. INSTALL PHP 8.3
echo -e "${BLUE}2️⃣ Instalando PHP 8.3...${NC}"
apt-get install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-mbstring php8.3-redis
systemctl enable php8.3-fpm
systemctl start php8.3-fpm

# 3. INSTALL NGINX
echo -e "${BLUE}3️⃣ Instalando Nginx...${NC}"
apt-get install -y nginx
systemctl enable nginx
systemctl start nginx

# 4. INSTALL MySQL 8.0
echo -e "${BLUE}4️⃣ Instalando MySQL 8.0...${NC}"
apt-get install -y mysql-server mysql-client
systemctl enable mysql
systemctl start mysql

# 5. INSTALL NODE & NPM
echo -e "${BLUE}5️⃣ Instalando Node.js...${NC}"
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
apt-get install -y nodejs

# 6. INSTALL COMPOSER
echo -e "${BLUE}6️⃣ Instalando Composer...${NC}"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
chmod +x /usr/local/bin/composer

# 7. INSTALL REDIS
echo -e "${BLUE}7️⃣ Instalando Redis...${NC}"
apt-get install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# 8. INSTALL SUPERVISOR
echo -e "${BLUE}8️⃣ Instalando Supervisor...${NC}"
apt-get install -y supervisor
systemctl enable supervisor
systemctl start supervisor

# 9. CLONE REPOSITORY
echo -e "${BLUE}9️⃣ Clonando repositorio...${NC}"
mkdir -p /var/www/html
cd /var/www/html
git clone $REPO_URL
cd $APP_DIR

# 10. INSTALL DEPENDENCIES
echo -e "${BLUE}🔟 Instalando dependencias PHP...${NC}"
composer install --optimize-autoloader --no-dev

echo -e "${BLUE}1️⃣1️⃣ Instalando dependencias Node...${NC}"
npm install

# 11. SETUP ENV
echo -e "${BLUE}1️⃣2️⃣ Creando archivo .env (NECESITA EDICIÓN MANUAL)...${NC}"
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${YELLOW}⚠️  IMPORTANTE: Edita .env con tus valores:${NC}"
    echo "   - DB_HOST, DB_USERNAME, DB_PASSWORD"
    echo "   - APP_KEY (php artisan key:generate)"
    echo "   - GEMINI_API_KEY"
    echo "   - FIREBASE_PROJECT_ID"
    echo "   - Otros valores..."
fi

# 12. GENERATE APP KEY
echo -e "${BLUE}1️⃣3️⃣ Generando APP_KEY...${NC}"
php artisan key:generate

# 13. CREATE DATABASE
echo -e "${BLUE}1️⃣4️⃣ Creando base de datos...${NC}"
echo "⚠️  Ingresa contraseña de MySQL root:"
mysql -u root -p << MYSQL_COMMANDS
CREATE DATABASE IF NOT EXISTS offside_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'offside'@'localhost' IDENTIFIED BY 'SET_A_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON offside_app.* TO 'offside'@'localhost';
FLUSH PRIVILEGES;
EXIT;
MYSQL_COMMANDS

# 14. RUN MIGRATIONS
echo -e "${BLUE}1️⃣5️⃣ Ejecutando migraciones...${NC}"
php artisan migrate --force

# 15. SETUP FILE PERMISSIONS
echo -e "${BLUE}1️⃣6️⃣ Ajustando permisos...${NC}"
chown -R $APP_USER:$APP_GROUP $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage $APP_DIR/bootstrap/cache

# 16. BUILD ASSETS
echo -e "${BLUE}1️⃣7️⃣ Compilando assets...${NC}"
npm run build

# 17. CACHE OPTIMIZATION
echo -e "${BLUE}1️⃣8️⃣ Optimizando aplicación...${NC}"
sudo -u $APP_USER php artisan config:cache
sudo -u $APP_USER php artisan route:cache
sudo -u $APP_USER php artisan view:cache
sudo -u $APP_USER php artisan optimize

# 18. SETUP SSL
echo -e "${BLUE}1️⃣9️⃣ Instalando Let's Encrypt Certbot...${NC}"
apt-get install -y certbot python3-certbot-nginx
echo -e "${YELLOW}⚠️  Configura SSL manualmente después con:${NC}"
echo "   certbot --nginx -d app.offsideclub.es"

# 20. CONFIGURE FIREWALL
echo -e "${BLUE}2️⃣0️⃣ Configurando Firewall...${NC}"
ufw --force enable
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp

# 21. SETUP FAIL2BAN
echo -e "${BLUE}2️⃣1️⃣ Configurando Fail2Ban...${NC}"
systemctl enable fail2ban
systemctl start fail2ban

# 22. SETUP UNATTENDED UPGRADES
echo -e "${BLUE}2️⃣2️⃣ Configurando actualizaciones automáticas...${NC}"
dpkg-reconfigure -plow unattended-upgrades

echo ""
echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ SETUP COMPLETADO${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}📋 PRÓXIMOS PASOS:${NC}"
echo ""
echo "1. EDITAR CONFIGURACIÓN:"
echo "   nano /var/www/html/offside-app/.env"
echo "   - DB_PASSWORD (debe ser la que creaste)"
echo "   - APP_KEY (ya se generó)"
echo "   - GEMINI_API_KEY"
echo "   - FIREBASE credenciales"
echo "   - Otros values..."
echo ""
echo "2. RESTAURAR BASE DE DATOS (si tienes backup):"
echo "   mysql -u offside -p offside_app < backup.sql"
echo ""
echo "3. CONFIGURAR NGINX (ver nginx.conf.example):"
echo "   nano /etc/nginx/sites-available/offside-app"
echo "   ln -s /etc/nginx/sites-available/offside-app /etc/nginx/sites-enabled/"
echo "   nginx -t"
echo "   systemctl restart nginx"
echo ""
echo "4. CONFIGURAR SSL:"
echo "   certbot --nginx -d app.offsideclub.es"
echo ""
echo "5. SETUP SSH KEYS (Regenerar, no usar viejas):"
echo "   ssh-keygen -t ed25519 -f ~/.ssh/id_ed25519"
echo ""
echo "6. SETUP QUEUE WORKER (Supervisor):"
echo "   nano /etc/supervisor/conf.d/offside-queue.conf (ver template)"
echo "   supervisorctl reread"
echo "   supervisorctl update"
echo ""
echo "7. TEST LA APLICACIÓN:"
echo "   curl http://localhost"
echo "   php artisan tinker"
echo "   DB::connection()->getPdo();"
echo ""
echo -e "${GREEN}Server está listo en: /var/www/html/offside-app${NC}"
echo ""
