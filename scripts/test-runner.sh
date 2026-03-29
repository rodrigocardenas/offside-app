#!/bin/bash

# 🧪 Test Helper Script for Offside Club
# Uso: ./scripts/test-runner.sh [opciones]

set -e

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

show_help() {
    echo -e "${BLUE}🧪 Offside Club Test Runner${NC}\n"
    echo "Uso: ./scripts/test-runner.sh [opciones]\n"
    echo "Opciones:"
    echo "  all          - Ejecutar todos los tests"
    echo "  unit         - Ejecutar solo tests unitarios"
    echo "  feature      - Ejecutar solo tests de Feature (Integration)"
    echo "  auth         - Ejecutar tests de autenticación"
    echo "  predictions  - Ejecutar tests de predicciones"
    echo "  groups       - Ejecutar tests de grupos"
    echo "  coverage     - Ejecutar tests con coverage (min 70%)"
    echo "  parallel     - Ejecutar tests en paralelo (rápido)"
    echo "  watch        - Ejecutar tests en modo watch"
    echo "  lint         - Ejecutar linter (Pint)"
    echo "  clean        - Limpiar cache de tests"
    echo "  ci           - Simular ambiente CI (como GitHub Actions)"
    echo "  help         - Mostrar esta ayuda"
    echo ""
    echo -e "${YELLOW}Ejemplos:${NC}"
    echo "  ./scripts/test-runner.sh all"
    echo "  ./scripts/test-runner.sh auth"
    echo "  ./scripts/test-runner.sh coverage"
}

# Función para ejecutar tests
run_tests() {
    local test_path=$1
    local description=$2
    
    echo -e "${YELLOW}▶${NC} $description"
    php artisan test "$test_path" --no-coverage
    echo -e "${GREEN}✓${NC} $description"
}

# Función para ejecutar con coverage
run_coverage() {
    echo -e "${YELLOW}▶${NC} Ejecutando tests con coverage (mín 70%)"
    php artisan test --coverage --min=70
    echo -e "${GREEN}✓${NC} Tests con coverage completados"
}

# Main
case "${1:-help}" in
    all)
        echo -e "\n${BLUE}==== Ejecutar Todos los Tests ====${NC}\n"
        php artisan test --parallel
        echo -e "${GREEN}✓ Todos los tests exitosos${NC}\n"
        ;;
    
    unit)
        run_tests "tests/Unit" "Tests Unitarios"
        ;;
    
    feature)
        run_tests "tests/Feature" "Tests de Feature"
        ;;
    
    auth)
        run_tests "tests/Feature/Auth" "Tests de Autenticación"
        ;;
    
    predictions)
        run_tests "tests/Feature/Predictions" "Tests de Predicciones"
        ;;
    
    groups)
        run_tests "tests/Feature/Groups" "Tests de Grupos"
        ;;
    
    coverage)
        echo -e "\n${BLUE}==== Tests con Coverage ====${NC}\n"
        run_coverage
        echo ""
        ;;
    
    parallel)
        echo -e "\n${BLUE}==== Tests en Paralelo ====${NC}\n"
        echo -e "${YELLOW}▶${NC} Ejecutando tests en paralelo (rápido)"
        php artisan test --parallel
        echo -e "${GREEN}✓${NC} Tests completados\n"
        ;;
    
    watch)
        echo -e "\n${BLUE}==== Tests en Modo Watch ====${NC}\n"
        echo -e "${YELLOW}Nota:${NC} Requiere Pest instalado globalmente"
        pest --watch
        ;;
    
    lint)
        echo -e "\n${BLUE}==== Ejecutar Linter (Pint) ====${NC}\n"
        if command -v composer &> /dev/null; then
            composer run lint
            echo -e "${GREEN}✓${NC} Linting completado\n"
        else
            echo -e "${RED}✗${NC} Composer no encontrado"
            exit 1
        fi
        ;;
    
    clean)
        echo -e "\n${BLUE}==== Limpiar Cache de Tests ====${NC}\n"
        rm -rf bootstrap/cache/
        php artisan cache:clear
        echo -e "${GREEN}✓${NC} Cache limpiado\n"
        ;;
    
    ci)
        echo -e "\n${BLUE}==== Simular Ambiente CI ====${NC}\n"
        echo -e "${YELLOW}▶${NC} Limpiando cache"
        php artisan cache:clear
        
        echo -e "${YELLOW}▶${NC} Ejecutando migrations"
        php artisan migrate --env=testing --force
        
        echo -e "${YELLOW}▶${NC} Ejecutando tests"
        php artisan test --parallel --no-coverage
        
        echo -e "${YELLOW}▶${NC} Ejecutando linter"
        composer run lint || true
        
        echo -e "${YELLOW}▶${NC} Construyendo assets"
        npm run build
        
        echo -e "${GREEN}✓${NC} CI completado exitosamente\n"
        ;;
    
    help|--help|-h)
        show_help
        ;;
    
    *)
        echo -e "${RED}✗${NC} Opción desconocida: $1\n"
        show_help
        exit 1
        ;;
esac
