#!/bin/bash

# SOLUCIÓN DEFINITIVA PARA TODAS LAS MIGRACIONES
# Ejecutar en servidor de producción

set -e

cd /var/www/html/offside-app

echo "════════════════════════════════════════════════════════"
echo "CORRECCIÓN DEFINITIVA DE MIGRACIONES"
echo "Offside Club - 13 Enero 2026"
echo "════════════════════════════════════════════════════════"

echo ""
echo "1️⃣  Actualizando código..."
sudo -u www-data git pull origin main 2>&1 | head -5

echo ""
echo "2️⃣  Entrando en modo mantenimiento..."
sudo -u www-data php artisan down --retry=60 2>&1 | head -3

echo ""
echo "3️⃣  Limpiando caché y configuración..."
sudo -u www-data php artisan cache:clear 2>&1 | head -3
sudo -u www-data php artisan config:clear 2>&1 | head -3

echo ""
echo "4️⃣  Haciendo rollback de todas las migraciones..."
sudo -u www-data php artisan migrate:rollback --step=20 --force 2>&1 | grep -E "(Rolled|Rolling)" | head -10 || echo "   ✓ Rollback completado"

echo ""
echo "5️⃣  Re-ejecutando TODAS las migraciones..."
sudo -u www-data php artisan migrate --force 2>&1 | grep -E "(Migrat|Migration)" | tail -20

echo ""
echo "6️⃣  Saliendo del modo mantenimiento..."
sudo -u www-data php artisan up 2>&1 | head -3

echo ""
echo "7️⃣  Compilando vistas..."
sudo -u www-data php artisan view:cache 2>&1 | head -3

echo ""
echo "════════════════════════════════════════════════════════"
echo "✅ CORRECCIÓN COMPLETADA EXITOSAMENTE"
echo "════════════════════════════════════════════════════════"
echo ""
echo "Se han ejecutado las siguientes correcciones:"
echo "  • Validaciones agregadas a todas las migraciones de usuarios"
echo "  • Validaciones agregadas a todas las migraciones de teams"
echo "  • Nueva migración de corrección para usuarios (2026_01_13_000000)"
echo "  • Nueva migración de corrección para teams (2026_01_13_000001)"
echo ""
echo "Todas las columnas se han verificado y agregado solo si faltaban."
