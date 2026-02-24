#!/bin/bash

# Test Script - Rate Limiting Tests
# Verifica que las limitaciones de rate funcionan correctamente

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

BASE_URL="${1:-http://localhost:8000}"
USERNAME="${2:-testuser}"

echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   🧪 RATE LIMITING TEST SUITE                      ║${NC}"
echo -e "${BLUE}║   Base URL: ${BASE_URL}                 ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

# Función para hacer requests
test_rate_limit() {
    local test_name="$1"
    local attempts="$2"
    local expected_fail_at="$3"
    local username="$4"

    echo -e "${YELLOW}🧪 TEST: $test_name${NC}"
    echo "Intentos a realizar: $attempts"
    echo "Se espera fallo en intento: $expected_fail_at"
    echo "---"

    success_count=0
    fail_count=0

    for i in $(seq 1 $attempts); do
        response=$(curl -s -X POST "$BASE_URL/login" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -d "name=$username" \
            -w "\n%{http_code}")

        http_code=$(echo "$response" | tail -1)
        body=$(echo "$response" | sed '$d')

        if [ "$http_code" == "429" ]; then
            echo -e "${RED}✗ Intento $i: BLOQUEADO (429)${NC}"
            ((fail_count++))
        elif [ "$http_code" == "200" ] || [ "$http_code" == "201" ]; then
            echo -e "${GREEN}✓ Intento $i: OK ($http_code)${NC}"
            ((success_count++))
        else
            echo -e "${YELLOW}? Intento $i: Status $http_code${NC}"
        fi

        sleep 0.5  # Pequeña pausa entre intentos
    done

    echo ""
    echo -e "Resultados: ${GREEN}$success_count exitosos${NC}, ${RED}$fail_count bloqueados${NC}"
    echo ""
}

echo -e "${YELLOW}📊 TEST 1: Límite por minuto (10/min)${NC}"
echo "Enviando 12 requests rápidamente a POST /login"
echo "Se espera: 10 OK → 429 en intentos 11-12"
echo ""
test_rate_limit "10 attempts per minute" 12 11 "test_$RANDOM"

echo ""
echo -e "${YELLOW}📊 TEST 2: Duplicados del mismo usuario (3/5min)${NC}"
echo "Enviando 5 requests con MISMO username"
echo "Se espera: 3 OK → 429 en intentos 4-5"
echo ""
test_rate_limit "Same username 5 times" 5 4 "duplicate_test_$RANDOM"

echo ""
echo -e "${YELLOW}📊 TEST 3: Total por IP (20/hora)${NC}"
echo "Intentando crear 22 usuarios diferentes"
echo "Se espera: 20 OK → 429 en intentos 21-22"
echo ""
for i in $(seq 1 22); do
    response=$(curl -s -X POST "$BASE_URL/login" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -d "name=user_${i}_$RANDOM" \
        -w "\n%{http_code}")

    http_code=$(echo "$response" | tail -1)

    if [ "$http_code" == "429" ]; then
        echo -e "${RED}✗ Intento $i: BLOQUEADO (429)${NC}"
    else
        echo -e "${GREEN}✓ Intento $i: OK${NC}"
    fi
done

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   ✅ SUITE DE TESTS COMPLETADA                    ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"

echo ""
echo -e "${YELLOW}📋 PRÓXIMOS PASOS:${NC}"
echo "1. Verificar logs:"
echo "   ${BLUE}tail -f storage/logs/security.log${NC}"
echo ""
echo "2. Verificar monitoreo en vivo:"
echo "   ${BLUE}php artisan security:monitor${NC}"
echo ""
echo "3. Limpiar duplicados:"
echo "   ${BLUE}php artisan users:clean-duplicates${NC}"
echo ""
