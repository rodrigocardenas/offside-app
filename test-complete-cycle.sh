#!/bin/bash

###############################################################################
# SCRIPT DE PRUEBA: CICLO COMPLETO DE LA APLICACIÓN
#
# Este script realiza un ciclo completo de la aplicación:
# 1. Obtiene partidos próximos de las APIs (datos reales)
# 2. Los guarda en la base de datos
# 3. Crea un grupo
# 4. Genera preguntas predictivas para ese grupo
# 5. Responde las preguntas con un usuario de prueba
# 6. Obtiene los resultados de los partidos
# 7. Verifica las respuestas y asigna puntos
# 8. Genera un reporte del ciclo
#
# Uso: ./test-complete-cycle.sh
###############################################################################

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Directorio del proyecto
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR" || exit

# Funciones de utilidad
print_section() {
    echo ""
    echo -e "${CYAN}=== $1 ===${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Verificar que estamos en el directorio correcto
if [ ! -f "$PROJECT_DIR/artisan" ]; then
    print_error "No se encontró el archivo artisan. Asegúrate de estar en el directorio raíz del proyecto."
    exit 1
fi

print_section "INICIANDO CICLO COMPLETO DE PRUEBA"
print_info "Directorio del proyecto: $PROJECT_DIR"

# Verificar que la aplicación está configurada
if [ ! -f "$PROJECT_DIR/.env" ]; then
    print_error "No se encontró el archivo .env"
    exit 1
fi

print_success "Archivo .env encontrado"

# Ejecutar el script PHP
print_section "Ejecutando script PHP"
print_info "Ejecutando: php scripts/test-complete-cycle.php"

php scripts/test-complete-cycle.php

if [ $? -eq 0 ]; then
    print_success "Script completado exitosamente"
    print_info "Revisa los logs en storage/logs/ para más detalles"
else
    print_error "El script falló. Revisa los errores arriba."
    exit 1
fi

print_section "CICLO COMPLETO FINALIZADO"
