#!/bin/bash

# Script para inspeccionar y reparar Group 129
# Uso: bash fix_group129.sh

echo "═════════════════════════════════════════════════════════════"
echo "HERRAMIENTA DE DIAGNÓSTICO: Group 129"
echo "═════════════════════════════════════════════════════════════"
echo ""

cd /var/www/offsideclub || { echo "Directorio no encontrado"; exit 1; }

# Paso 1: Inspeccionar Match 2003
echo "📊 PASO 1: Inspeccionando Match 2003..."
echo ""
php artisan debug:match-2003

echo ""
echo "═════════════════════════════════════════════════════════════"
echo "❓ ¿Los datos de Match 2003 son correctos?"
echo ""
read -p "Continuar con re-evaluación? (s/n): " continue1

if [[ $continue1 != "s" ]]; then
    echo "Abortado por usuario."
    exit 0
fi

# Paso 2: Re-evaluar con los evaluadores
echo ""
echo "📝 PASO 2: Re-evaluando preguntas con evaluador..."
echo ""
php artisan app:evaluate-match-questions --match-id=2003 --force=true

echo ""
echo "═════════════════════════════════════════════════════════════"
echo "✓ Base de datos actualizada con respuestas correctas"
echo ""
read -p "Continuar con recálculo de puntos? (s/n): " continue2

if [[ $continue2 != "s" ]]; then
    echo "Abortado por usuario."
    exit 0
fi

# Paso 3: Recalcular puntos
echo ""
echo "👤 PASO 3: Recalculando puntos de usuarios..."
echo ""
php artisan answers:reevaluate --group=129 --date=2026-03-10

echo ""
echo "═════════════════════════════════════════════════════════════"
echo "✅ REPARACIÓN COMPLETADA"
echo ""
echo "Próximos pasos:"
echo "1. Verificar que usuarios tengan puntos correctos"
echo "2. Ejecutar: php artisan verify:group-data --group=129"
echo "3. Confirmar en tabla de rankings"
echo ""
