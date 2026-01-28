#!/bin/bash

# 🌍 Test Script para Timezone Sync

echo "=========================================="
echo "🌍 TIMEZONE SYNC - Test Checklist"
echo "=========================================="
echo ""

echo "✅ Paso 1: Verificar que el archivo existe"
if [ -f "public/js/timezone-sync.js" ]; then
    echo "   ✓ public/js/timezone-sync.js existe"
else
    echo "   ✗ FALTA public/js/timezone-sync.js"
    exit 1
fi

echo ""
echo "✅ Paso 2: Verificar que el script está incluido en layout"
if grep -q "timezone-sync.js" resources/views/layouts/app.blade.php; then
    echo "   ✓ Script incluido en resources/views/layouts/app.blade.php"
else
    echo "   ✗ Script NO está incluido en layout"
    exit 1
fi

echo ""
echo "✅ Paso 3: Verificar que DEBUG está en true"
if grep -q "const DEBUG = true" public/js/timezone-sync.js; then
    echo "   ✓ DEBUG = true (logs activos)"
else
    echo "   ⚠ DEBUG podría estar desactivado"
fi

echo ""
echo "✅ Paso 4: Verificar endpoints API"
if grep -q "/api/set-timezone" routes/api.php; then
    echo "   ✓ Endpoint POST /api/set-timezone existe"
else
    echo "   ✗ FALTA endpoint /api/set-timezone"
    exit 1
fi

if grep -q "/api/timezone-status" routes/api.php; then
    echo "   ✓ Endpoint GET /api/timezone-status existe"
else
    echo "   ⚠ Endpoint /api/timezone-status no encontrado"
fi

echo ""
echo "=========================================="
echo "📋 PRÓXIMOS PASOS EN EL NAVEGADOR:"
echo "=========================================="
echo ""
echo "1. Abre http://localhost/ o tu app"
echo "2. Abre DevTools (F12) → Console"
echo "3. Deberías ver logs en VERDE como:"
echo "   [TZ-SYNC] === INICIALIZANDO TIMEZONE SYNC ==="
echo "   [TZ-SYNC] ✅ Timezone del dispositivo detectado: ..."
echo ""
echo "4. Para forzar sincronización manual, ejecuta en consola:"
echo "   window.forceTimezoneSync()"
echo ""
echo "5. Verifica en Network que se haga POST /api/set-timezone"
echo ""
echo "=========================================="
echo "✅ TESTS COMPLETADOS"
echo "=========================================="
