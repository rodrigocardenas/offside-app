#!/bin/bash
# SECURITY AUDIT - Offside App
# Ejecutar en producción para identificar vulnerabilidades

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

APP_PATH="/var/www/html/offside-app"
REPORT_FILE="/tmp/security-audit-$(date +%s).txt"

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}🔍 SECURITY AUDIT - OFFSIDE APP${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""
echo "Reporte guardado en: $REPORT_FILE"
echo ""

# 1. CHECK COMPOSER VULNERABILITIES
echo -e "${YELLOW}1️⃣ Auditando dependencias PHP (Composer)...${NC}"
{
    echo "=== COMPOSER AUDIT ==="
    cd "$APP_PATH"
    composer audit 2>&1 || echo "Composer audit no disponible o error"
    echo ""
} | tee -a "$REPORT_FILE"

# 2. CHECK NPM VULNERABILITIES
if [ -d "$APP_PATH/node_modules" ]; then
    echo -e "${YELLOW}2️⃣ Auditando dependencias JavaScript (NPM)...${NC}"
    {
        echo "=== NPM AUDIT ==="
        cd "$APP_PATH"
        npm audit 2>&1 || echo "NPM audit no disponible"
        echo ""
    } | tee -a "$REPORT_FILE"
fi

# 3. CHECK SYSTEM SECURITY
echo -e "${YELLOW}3️⃣ Verificando configuración de seguridad del sistema...${NC}"
{
    echo "=== SYSTEM SECURITY CHECK ==="

    echo "Permisos de /etc/cron.d:"
    ls -la /etc/cron.d/ | head -5

    echo ""
    echo "Permisos de /etc/crontab:"
    ls -la /etc/crontab

    echo ""
    echo "Permisos de /etc/init.d:"
    ls -la /etc/init.d | head -3

    echo ""
    echo "Cron jobs activos (root):"
    crontab -l 2>/dev/null || echo "No root crontab"

    echo ""
    echo "Cron jobs (www-data):"
    sudo -u www-data crontab -l 2>/dev/null || echo "No www-data crontab"

    echo ""
} | tee -a "$REPORT_FILE"

# 4. CHECK PHP CONFIGURATION
echo -e "${YELLOW}4️⃣ Verificando configuración de PHP...${NC}"
{
    echo "=== PHP CONFIGURATION CHECK ==="
    echo "Disabled functions:"
    grep -i "disable_functions" /etc/php/8.3/fpm/php.ini || echo "⚠️  disable_functions NO CONFIGURADO"

    echo ""
    echo "open_basedir:"
    grep -i "open_basedir" /etc/php/8.3/fpm/php.ini || echo "⚠️  open_basedir NO CONFIGURADO"

    echo ""
} | tee -a "$REPORT_FILE"

# 5. CHECK FOR SUSPICIOUS PROCESSES
echo -e "${YELLOW}5️⃣ Buscando procesos sospechosos...${NC}"
{
    echo "=== SUSPICIOUS PROCESSES ==="
    ps aux | grep -E "qpAo|miner|crypto|xmr|bitcoin" || echo "✅ No encontrados procesos sospechosos"

    echo ""
    echo "Top procesos por CPU:"
    ps aux --sort=-%cpu | head -6

    echo ""
} | tee -a "$REPORT_FILE"

# 6. CHECK FILE INTEGRITY
echo -e "${YELLOW}6️⃣ Verificando integridad de archivos críticos...${NC}"
{
    echo "=== FILE INTEGRITY CHECK ==="
    echo "Archivos modificados en los últimos 7 días (excluir node_modules y .git):"
    find "$APP_PATH" -type f -mtime -7 \
        ! -path "*/node_modules/*" \
        ! -path "*/.git/*" \
        ! -path "*/storage/*" \
        ! -path "*/bootstrap/cache/*" \
        2>/dev/null | head -20

    echo ""
} | tee -a "$REPORT_FILE"

# 7. CHECK LOGS FOR ATTACKS
echo -e "${YELLOW}7️⃣ Revisando logs para signos de ataque...${NC}"
{
    echo "=== ATTACK SIGNATURES IN LOGS ==="
    echo "SQL Injection attempts (in access logs):"
    grep -i "union\|select\|--\|/*" /var/log/nginx/access.log 2>/dev/null | head -5 || echo "No encontrados"

    echo ""
    echo "Command Injection attempts:"
    grep -i "system\|exec\|shell_exec\|passthru" /var/log/php-fpm/*.log 2>/dev/null | head -5 || echo "No encontrados"

    echo ""
} | tee -a "$REPORT_FILE"

# 8. SUMMARY
{
    echo "=== RESUMEN ==="
    echo ""
    echo "Pasos siguientes recomendados:"
    echo "1. Revisar el reporte completo:"
    echo "   cat $REPORT_FILE"
    echo ""
    echo "2. Si hay vulnerabilidades CRÍTICAS:"
    echo "   composer update"
    echo "   npm update"
    echo ""
    echo "3. Rotar credenciales:"
    echo "   - SSH keys"
    echo "   - RDS password"
    echo "   - API tokens en .env"
    echo ""
    echo "4. Implementar hardening:"
    echo "   bash hardening-security.sh"
    echo ""
} | tee -a "$REPORT_FILE"

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Audit completado. Revisa: $REPORT_FILE${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
