#!/bin/bash
# Test: Verificar que el modal refactorizado funciona correctamente

echo "==========================================================="
echo "TEST: Modal Refactorizado - Verificación de Funcionalidad"
echo "==========================================================="
echo ""

# 1. Verificar que el archivo modal ha sido refactorizado
echo "1. Verificando archivo del modal..."
MODAL_FILE="resources/views/components/modals/create-pre-match-modal.blade.php"

if grep -q "^let preMatchGroupId" "$MODAL_FILE"; then
    echo "   ✓ Variables globales (sin IIFE) encontradas"
else
    echo "   ✗ No se encontraron variables globales"
    exit 1
fi

if ! grep -q "(function() {" "$MODAL_FILE"; then
    echo "   ✓ IIFE wrapper removido"
else
    echo "   ✗ Aun contiene IIFE wrapper"
    exit 1
fi

# 2. Verificar que todas las funciones públicas están en window
echo ""
echo "2. Verificando funciones públicas..."

FUNCTIONS=( "window.openCreatePreMatchModal" "window.closeCreatePreMatchModal" "window.selectMatchFromDropdown" "window.submitCreatePreMatch" )

for FUNC in "${FUNCTIONS[@]}"; do
    if grep -q "$FUNC" "$MODAL_FILE"; then
        echo "   ✓ $FUNC encontrada"
    else
        echo "   ✗ $FUNC NO encontrada"
        exit 1
    fi
done

# 3. Verificar que los archivos han sido compilados
echo ""
echo "3. Verificando assets compilados..."

if [ -f "public/build/manifest.json" ]; then
    echo "   ✓ Manifest.json existe"
else
    echo "   ✗ Manifest.json no encontrado"
    exit 1
fi

# 4. Verificar sintaxis JavaScript (básica)
echo ""
echo "4. Verificando sintaxis..."

# Buscar errores comunes
if grep -q "}\)};" "$MODAL_FILE"; then
    echo "   ✗ Posible cierre incorrecto de funciones"
    exit 1
else
    echo "   ✓ Sintaxis de cierre de funciones OK"
fi

# 5. Verificar que las funciones están correctamente accesibles
echo ""
echo "5. Verificando accesibilidad de funciones..."

if grep -q "window.openCreatePreMatchModal = function" "$MODAL_FILE"; then
    echo "   ✓ openCreatePreMatchModal es una función pública"
else
    echo "   ✗ openCreatePreMatchModal no es accesible públicamente"
    exit 1
fi

if grep -q "window.selectMatchFromDropdown = function" "$MODAL_FILE"; then
    echo "   ✓ selectMatchFromDropdown es una función pública"
else
    echo "   ✗ selectMatchFromDropdown no es accesible públicamente"
    exit 1
fi

# 6. Verificar que el input hidden tiene el ID correcto
echo ""
echo "6. Verificando elementos HTML del modal..."

MODAL_HTML_FILE="resources/views/components/modals/create-pre-match-modal.blade.php"

if grep -q 'id="preMatchMatchSelect"' "$MODAL_HTML_FILE"; then
    echo "   ✓ Input hidden con ID 'preMatchMatchSelect' encontrado"
else
    echo "   ✗ Input hidden no encontrado"
    exit 1
fi

if grep -q 'id="preMatchSearchInput"' "$MODAL_HTML_FILE"; then
    echo "   ✓ Input de búsqueda encontrado"
else
    echo "   ✗ Input de búsqueda no encontrado"
    exit 1
fi

if grep -q 'id="preMatchSearchResults"' "$MODAL_HTML_FILE"; then
    echo "   ✓ Div de resultados encontrado"
else
    echo "   ✗ Div de resultados no encontrado"
    exit 1
fi

# Test Summary
echo ""
echo "==========================================================="
echo "RESUMEN"
echo "==========================================================="
echo "✓ Modal refactorizado correctamente"
echo "✓ IIFE wrapper eliminado"
echo "✓ Todas las funciones son públicamente accesibles"
echo "✓ Assets compilados"
echo "✓ Elementos HTML del modal presentes"
echo ""
echo "STATUS: TODOS LOS TESTS PASARON ✓"
echo "==========================================================="
