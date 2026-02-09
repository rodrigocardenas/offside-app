#!/bin/bash

# 🚀 AUTOMATED LARAVEL STACK INSTALLATION
# Para nuevo servidor limpio

set -e

echo "🚀 INSTALLING LARAVEL STACK ON CLEAN SERVER"
echo "============================================="
echo ""

# 1. Update system
echo "1️⃣  Updating system packages..."
sudo apt-get update
sudo apt-get upgrade -y

# 2. Install dependencies
echo "2️⃣  Installing dependencies..."
sudo apt-get install -y \
    curl wget git \
    build-essential \
    libssl-dev libcurl4-openssl-dev \
    supervisor htop iotop

# 3. Install PHP 8.3
echo "3️⃣  Installing PHP 8.3 & extensions..."
sudo apt-get install -y \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-redis \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-gd \
    php8.3-zip \
    php8.3-bcmath

# 4. Install Nginx
echo "4️⃣  Installing Nginx..."
sudo apt-get install -y nginx

# 5. Install MySQL Client (using RDS)
echo "5️⃣  Installing MySQL client..."
sudo apt-get install -y mysql-client

# 6. Install Redis
echo "6️⃣  Installing Redis..."
sudo apt-get install -y redis-server

# 7. Install Node.js & npm
echo "7️⃣  Installing Node.js 20..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# 8. Install Composer
echo "8️⃣  Installing Composer..."
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# 9. Create application directories
echo "9️⃣  Creating application directories..."
sudo mkdir -p /var/www/html/offside-app
sudo mkdir -p /var/www/html/offside-landing
sudo chown -R $USER:www-data /var/www/html

# 10. Configure PHP-FPM
echo "🔟 Configuring PHP-FPM..."
sudo tee /etc/php/8.3/fpm/conf.d/99-custom.conf > /dev/null <<EOF
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 256M
max_execution_time = 300
default_charset = "UTF-8"
EOF

# 11. Start services
echo "1️⃣1️⃣  Starting services..."
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo systemctl restart redis-server

# 12. Enable services on boot
echo "1️⃣2️⃣  Enabling services..."
sudo systemctl enable php8.3-fpm
sudo systemctl enable nginx
sudo systemctl enable redis-server

# 13. Verify installations
echo ""
echo "✅ INSTALLATION COMPLETE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Installed versions:"
echo ""
php --version
echo ""
nginx -v
echo ""
node --version
echo ""
npm --version
echo ""
composer --version
echo ""
redis-cli --version
echo ""
mysql --version
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Next steps:"
echo "1. Clone repo: cd /var/www/html && git clone..."
echo "2. Composer install: composer install"
echo "3. Configure .env with RDS credentials"
echo "4. php artisan migrate"
echo "5. npm install (for offside-landing)"
echo "6. Configure Nginx"
echo "7. Test application"
