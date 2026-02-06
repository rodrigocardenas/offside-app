#!/bin/bash
# Security Hardening Script para Offside App
# Ejecutar después de detectar breaches
# sudo bash hardening-security.sh

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}🔒 SECURITY HARDENING - OFFSIDE APP${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""

# 1. FIX FILE PERMISSIONS
echo -e "${YELLOW}1️⃣ Corrigiendo permisos de archivos críticos...${NC}"
chmod 755 /etc/cron.d
chmod 644 /etc/cron.d/*
chmod 644 /etc/crontab
chmod 755 /etc/init.d
find /etc -type f -perm /002 -exec chmod o-w {} \; 2>/dev/null || true
find /etc -type f -perm /020 -exec chmod g-w {} \; 2>/dev/null || true
echo -e "${GREEN}✅ Permisos corregidos${NC}"
echo ""

# 2. DISABLE DANGEROUS PHP FUNCTIONS
echo -e "${YELLOW}2️⃣ Deshabilitando funciones PHP peligrosas...${NC}"
PHP_INI="/etc/php/8.3/fpm/php.ini"
if [ -f "$PHP_INI" ]; then
    # Backup
    cp "$PHP_INI" "$PHP_INI.backup.$(date +%s)"

    # Disable functions
    if ! grep -q "disable_functions" "$PHP_INI"; then
        echo "disable_functions = system,exec,passthru,shell_exec,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,dl,eval" >> "$PHP_INI"
    fi

    # Restrict file access
    if ! grep -q "open_basedir" "$PHP_INI"; then
        echo "open_basedir = /var/www/html/offside-app:/tmp:/var/tmp:/dev/urandom" >> "$PHP_INI"
    fi

    systemctl reload php8.3-fpm
    echo -e "${GREEN}✅ PHP hardening aplicado${NC}"
else
    echo -e "${RED}⚠️ PHP no encontrado en $PHP_INI${NC}"
fi
echo ""

# 3. REMOVE UNNECESSARY SERVICES
echo -e "${YELLOW}3️⃣ Deshabilitando servicios innecesarios...${NC}"
for service in telnet rsh rlogin; do
    systemctl disable $service 2>/dev/null || true
done
echo -e "${GREEN}✅ Servicios innecesarios deshabilitados${NC}"
echo ""

# 4. ENABLE FIREWALL
echo -e "${YELLOW}4️⃣ Configurando Firewall (UFW)...${NC}"
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw --force enable
echo -e "${GREEN}✅ Firewall habilitado${NC}"
echo ""

# 5. INSTALL SECURITY TOOLS
echo -e "${YELLOW}5️⃣ Instalando herramientas de seguridad...${NC}"
apt-get update
apt-get install -y aide aide-common auditd apparmor apparmor-utils
echo -e "${GREEN}✅ Herramientas instaladas${NC}"
echo ""

# 6. ENABLE AUDIT
echo -e "${YELLOW}6️⃣ Habilitando auditd...${NC}"
systemctl enable auditd
systemctl start auditd

# Audit cron
auditctl -w /etc/cron.d/ -p wa -k cron_changes 2>/dev/null || true
auditctl -w /etc/crontab -p wa -k crontab_changes 2>/dev/null || true

echo -e "${GREEN}✅ Auditd configurado${NC}"
echo ""

# 7. SCAN FOR SUSPICIOUS FILES
echo -e "${YELLOW}7️⃣ Escaneando archivos sospechosos...${NC}"
echo "Archivos creados en el último día:"
find / -type f -mtime -1 -not -path "/proc/*" -not -path "/sys/*" 2>/dev/null | grep -E "\.sh$|\.py$|cron" | head -20
echo -e "${GREEN}✅ Escaneo completado${NC}"
echo ""

# 8. SHOW SUSPICIOUS CRON JOBS
echo -e "${YELLOW}8️⃣ Verificando cron jobs...${NC}"
echo "System crontab:"
cat /etc/crontab | grep -v "^#" | grep -v "^$"
echo ""
echo "Cron.d jobs:"
cat /etc/cron.d/* | grep -v "^#" | grep -v "^$"
echo -e "${GREEN}✅ Verificación completada${NC}"
echo ""

# 9. SUMMARY
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}🔒 HARDENING COMPLETADO${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""
echo "Acciones realizadas:"
echo "  ✅ Permisos de archivos corregidos"
echo "  ✅ Funciones PHP peligrosas deshabilitadas"
echo "  ✅ Servicios innecesarios deshabilitados"
echo "  ✅ Firewall habilitado"
echo "  ✅ Auditd configurado"
echo ""
echo "Próximos pasos recomendados:"
echo "  1. Revisar: sudo auditctl -l"
echo "  2. Ver logs: sudo tail -f /var/log/audit/audit.log"
echo "  3. Cambiar credenciales SSH"
echo "  4. Auditar dependencies: composer audit && npm audit"
echo "  5. Revisar access logs de Apache/Nginx"
echo ""
