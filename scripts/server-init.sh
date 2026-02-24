#!/bin/bash

# ============================================
# INICIALIZACIÓN SEGURA DEL SERVIDOR
# ============================================
# Ejecutar UNA SOLA VEZ en el servidor EC2
#
# PASO 1: Conectar al servidor
#   ssh -i ~/.ssh/offside-new.pem ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com
#
# PASO 2: Descargar este script
#   curl -O https://raw.githubusercontent.com/.../server-init.sh
#
# PASO 3: Ejecutar como root
#   sudo bash server-init.sh

set -e

echo "🔒 Inicializando servidor para despliegue seguro..."

if [ "$EUID" -ne 0 ]; then
    echo "❌ ERROR: Este script debe ejecutarse como root (sudo)"
    exit 1
fi

# 1. Crear directorio de aplicación
echo "📁 Creando directorios..."
mkdir -p /var/www/html
mkdir -p /var/www/html/storage/logs
mkdir -p /var/log/deployment
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 2. Configurar sudoers para www-data
echo "🔐 Configurando sudoers para www-data..."
cat > /etc/sudoers.d/www-data-deploy << 'EOF'
# Permisos para despliegue automático
www-data ALL=(ALL) NOPASSWD: /bin/mkdir
www-data ALL=(ALL) NOPASSWD: /bin/chown
www-data ALL=(ALL) NOPASSWD: /bin/chmod
www-data ALL=(ALL) NOPASSWD: /bin/rm
www-data ALL=(ALL) NOPASSWD: /bin/tar
www-data ALL=(ALL) NOPASSWD: /bin/ln
www-data ALL=(ALL) NOPASSWD: /bin/mv
www-data ALL=(ALL) NOPASSWD: /usr/bin/git
www-data ALL=(ALL) NOPASSWD: /usr/bin/composer
www-data ALL=(ALL) NOPASSWD: /usr/bin/php
www-data ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl

# No requerir TTY para scripts automatizados
Defaults:www-data !requiretty
EOF

chmod 0440 /etc/sudoers.d/www-data-deploy
visudo -c -f /etc/sudoers.d/www-data-deploy

if [ $? -eq 0 ]; then
    echo "✅ Sudoers configurado correctamente"
else
    echo "❌ ERROR: Los permisos sudoers tienen problemas"
    exit 1
fi

# 3. Configurar Git para www-data
echo "🔧 Configurando Git..."
sudo -u www-data git config --global core.fileMode false

# 4. Crear directorio de logs
echo "📝 Creando directorio de logs..."
mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage/logs
chmod -R 775 /var/www/html/storage/logs

# 5. Verificar servicios
echo "🔍 Verificando servicios..."
systemctl status php-fpm --no-pager || echo "⚠️  PHP-FPM no disponible"
systemctl status nginx --no-pager || echo "⚠️  Nginx no disponible"

# 6. Crear archivo de configuración segura
echo "📋 Creando archivo de configuración de deploy..."
cat > /var/www/html/.deploy-config.sh << 'EOF'
#!/bin/bash
# Configuración de despliegue generada automáticamente
# No editar manualmente - se regenera en cada deploy

DEPLOY_INITIALIZED="true"
DEPLOY_DATE=$(date)
SERVER_HOSTNAME=$(hostname)
PHP_VERSION=$(php -v | grep -oP 'PHP \K[0-9]+\.[0-9]+\.[0-9]+')
NGINX_VERSION=$(nginx -v 2>&1 | grep -oP '\K[0-9]+\.[0-9]+\.[0-9]+')
EOF

chmod 644 /var/www/html/.deploy-config.sh

# 7. Crear archivo de monitoreo de seguridad
echo "🔐 Creando script de monitoreo..."
cat > /usr/local/bin/offside-security-monitor.sh << 'EOF'
#!/bin/bash
# Monitor de seguridad de Offside Club
# Ejecutar cada 5 minutos desde cron

cd /var/www/html

# Verificar permisos de storage
if [ ! -w storage/logs ]; then
    sudo chown -R www-data:www-data storage/logs
    echo "[$(date)] ⚠️  Permisos de storage/logs ajustados" | sudo tee -a /var/log/deployment/perms.log
fi

# Verificar que security.log existe
if [ ! -f storage/logs/security.log ]; then
    sudo touch storage/logs/security.log
    sudo chown www-data:www-data storage/logs/security.log
    sudo chmod 644 storage/logs/security.log
fi

# Alertar si hay muchos errores de seguridad
CRITICAL_COUNT=$(grep -c CRITICAL storage/logs/security.log 2>/dev/null || echo 0)
if [ "$CRITICAL_COUNT" -gt 5 ]; then
    echo "🚨 ALERTA: $CRITICAL_COUNT alertas críticas detectadas" | sudo tee -a /var/log/deployment/security.log
fi
EOF

chmod +x /usr/local/bin/offside-security-monitor.sh

# 8. Agregar cron job para monitoreo (opcional)
echo "⏰ Configurando monitoreo automático (cron)..."
(sudo -u www-data crontab -l 2>/dev/null | grep -v offside-security-monitor; echo "*/5 * * * * /usr/local/bin/offside-security-monitor.sh") | sudo -u www-data crontab - || echo "⚠️  Cron no disponible"

# 9. Resumen final
echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║            ✅ SERVIDOR INICIALIZADO CORRECTAMENTE        ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📊 INFORMACIÓN DEL SERVIDOR:"
echo "   Hostname:          $(hostname)"
echo "   IP:                $(hostname -I | awk '{print $1}')"
echo "   PHP:               $(php -v | head -1)"
echo "   Nginx:             $(nginx -v 2>&1)"
echo "   www-data:          $(id www-data)"
echo ""
echo "📁 DIRECTORIOS CREADOS:"
echo "   /var/www/html          (dirección de app)"
echo "   /var/www/html/storage/logs (logs)"
echo "   /var/log/deployment     (logs de deploy)"
echo ""
echo "🔐 CONFIGURACIÓN DE SEGURIDAD:"
echo "   ✓ Sudoers configurado para www-data"
echo "   ✓ Permisos de directorios ajustados"
echo "   ✓ Git configurado para ignorar cambios de permisos"
echo "   ✓ Monitoreo automático de seguridad (cron)"
echo ""
echo "📝 PRÓXIMOS PASOS:"
echo "   1. Verificar permisos: sudo visudo -c"
echo "   2. Clonar repositorio: sudo -u www-data git clone ..."
echo "   3. Ejecutar primer deploy desde local"
echo ""
echo "🎯 TESTING:"
echo "   sudo -u www-data php artisan --version"
echo "   sudo -u www-data php artisan tinker"
echo ""
