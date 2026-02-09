#!/bin/bash
set -e

# Security Hardening Script for Production
# Usage: sudo bash scripts/harden-server.sh

echo "🛡️ HARDENING PRODUCTION SERVER..."
echo ""

# 1. Fix Composer Vulnerabilities
echo "1️⃣ Updating vulnerable dependencies..."
cd /var/www/html
sudo -u www-data php -d memory_limit=1G composer update --with-all-dependencies 2>&1 | tail -20 || true
sudo -u www-data php -d memory_limit=1G composer install --no-dev 2>&1 | tail -10 || true

# 2. Verify No CVEs Remain
echo "2️⃣ Verifying security..."
sudo -u www-data php -d memory_limit=1G composer audit || true

# 3. Fix Cron Permissions
echo "3️⃣ Fixing cron permissions..."
sudo chmod 644 /etc/cron.d/* 2>/dev/null || true
sudo chmod 644 /etc/cron.daily/* 2>/dev/null || true
sudo chmod 644 /etc/cron.hourly/* 2>/dev/null || true

# 4. Disable Dangerous PHP Functions
echo "4️⃣ Disabling dangerous PHP functions..."
PHP_INI=$(php -r "echo php_ini_loaded_file();")
echo "disable_functions = shell_exec,exec,system,passthru,proc_open,proc_close,show_source" | \
    sudo tee -a "$PHP_INI" > /dev/null

# 5. Restart Services
echo "5️⃣ Restarting services..."
sudo systemctl restart apache2 || sudo systemctl restart nginx || true
sudo systemctl restart php-fpm || true

# 6. Clear Cache
echo "6️⃣ Clearing cache..."
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan optimize

# 7. Run File Integrity Check
echo "7️⃣ Checking file integrity..."
echo "Checking for suspicious files in /tmp..."
sudo find /tmp -type f -executable -ls 2>/dev/null || true

echo "Checking for recent modifications..."
sudo find /var/www/html -type f -mtime -1 ! -path "*/node_modules/*" ! -path "*/vendor/*" -ls 2>/dev/null | head -20 || true

# 8. Verify Permissions
echo "8️⃣ Verifying permissions..."
sudo chown -R www-data:www-data /var/www/html
sudo chmod 755 /var/www/html
sudo chmod 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo ""
echo "✅ Hardening complete!"
echo "🔒 Please verify:"
echo "   - composer audit shows no vulnerabilities"
echo "   - PHP dangerous functions are disabled"
echo "   - No suspicious files in /tmp"
echo "   - Application is functioning normally"
