#!/bin/bash

# Script de Testing - Medidas de Seguridad Offside Club
# Autor: Security Team
# Fecha: 2025-02-20

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables
BASE_URL="http://localhost:8000"
LOG_DIR="storage/logs"
SECURITY_LOG="storage/logs/security.log"

echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   🔐 SECURITY IMPLEMENTATION TEST SUITE             ║${NC}"
echo -e "${BLUE}║   Offside Club - Rate Limiting & Anomaly Detection  ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

# Verificaciones previas
echo -e "${YELLOW}📋 [VERIFICACIÓN PREVIA]${NC}"
echo "Verificando archivos..."

files=(
    "app/Services/AnomalyDetectionService.php"
    "app/Http/Middleware/RateLimitUserCreation.php"
    "app/Console/Commands/CleanDuplicateUsers.php"
    "app/Console/Commands/MonitorSecurityLogs.php"
    "config/logging.php"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗ FALTANTE:${NC} $file"
    fi
done

echo ""
echo -e "${YELLOW}🔍 [VALIDACIÓN SINTAXIS PHP]${NC}"

php -l app/Services/AnomalyDetectionService.php > /dev/null 2>&1 && echo -e "${GREEN}✓${NC} AnomalyDetectionService - Sintaxis OK" || echo -e "${RED}✗ Error en AnomalyDetectionService${NC}"
php -l app/Http/Middleware/RateLimitUserCreation.php > /dev/null 2>&1 && echo -e "${GREEN}✓${NC} RateLimitUserCreation - Sintaxis OK" || echo -e "${RED}✗ Error en RateLimitUserCreation${NC}"
php -l app/Console/Commands/CleanDuplicateUsers.php > /dev/null 2>&1 && echo -e "${GREEN}✓${NC} CleanDuplicateUsers - Sintaxis OK" || echo -e "${RED}✗ Error en CleanDuplicateUsers${NC}"
php -l app/Console/Commands/MonitorSecurityLogs.php > /dev/null 2>&1 && echo -e "${GREEN}✓${NC} MonitorSecurityLogs - Sintaxis OK" || echo -e "${RED}✗ Error en MonitorSecurityLogs${NC}"

echo ""
echo -e "${YELLOW}⚙️  [CONFIGURACIÓN REGISTRADA]${NC}"

grep -q "rate-limit-users" app/Http/Kernel.php && echo -e "${GREEN}✓${NC} Middleware registrado en Kernel" || echo -e "${RED}✗ Middleware NO registrado${NC}"
grep -q "middleware('rate-limit-users')" routes/web.php && echo -e "${GREEN}✓${NC} Middleware aplicado a POST /login" || echo -e "${RED}✗ Middleware NO aplicado${NC}"
grep -q "'security'" config/logging.php && echo -e "${GREEN}✓${NC} Canal de logging 'security' configurado" || echo -e "${RED}✗ Canal NOT configurado${NC}"

echo ""
echo -e "${YELLOW}📁 [DIRECTORIO DE LOGS]${NC}"

if [ -d "$LOG_DIR" ]; then
    echo -e "${GREEN}✓${NC} Directorio storage/logs existe"
    echo "  Contenido:"
    ls -lh "$LOG_DIR" 2>/dev/null | tail -5
else
    echo -e "${YELLOW}⚠️  Directorio storage/logs NO existe (se creará en primera ejecución)${NC}"
fi

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   ✅ VERIFICACIÓN COMPLETADA                      ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"

echo ""
echo -e "${GREEN}PRÓXIMOS PASOS:${NC}"
echo "1. Iniciar servidor Laravel: ${BLUE}php artisan serve${NC}"
echo "2. En otra terminal, ejecutar: ${BLUE}php artisan security:monitor${NC}"
echo "3. Probar rate limiting: ${BLUE}bash tests/test-rate-limiting.sh${NC}"
echo ""
echo -e "${YELLOW}MONITOREO EN TIEMPO REAL:${NC}"
echo "tail -f $SECURITY_LOG"
echo ""
