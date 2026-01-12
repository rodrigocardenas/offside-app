#!/bin/bash

# SCRIPT DE LIMPIEZA COMPLETA DE MIGRACIONES
# Ejecutar en el servidor de producción
# ⚠️ ADVERTENCIA: Esto hace rollback completo y limpia la BD

set -e

cd /var/www/html/offside-app

echo "════════════════════════════════════════════════════════"
echo "LIMPIEZA Y RESTAURACIÓN COMPLETA DE MIGRACIONES"
echo "Offside Club - 13 Enero 2026"
echo "════════════════════════════════════════════════════════"
echo ""
echo "⚠️  ADVERTENCIA: Este script:"
echo "   • Hará rollback de TODAS las migraciones"
echo "   • Eliminará y recreará la BD"
echo "   • Re-ejecutará todas las migraciones desde cero"
echo ""

read -p "¿Estás seguro de que deseas continuar? (s/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "❌ Operación cancelada"
    exit 1
fi

echo ""
echo "1️⃣  Actualizando código..."
sudo -u www-data git pull origin main 2>&1 | head -5

echo ""
echo "2️⃣  Entrando en modo mantenimiento..."
sudo -u www-data php artisan down --retry=60 2>&1 | head -3

echo ""
echo "3️⃣  Eliminando todas las migraciones de la BD..."
sudo -u www-data php artisan migrate:reset --force 2>&1 | tail -10

echo ""
echo "4️⃣  Limpiando caché..."
sudo -u www-data php artisan cache:clear 2>&1
sudo -u www-data php artisan config:clear 2>&1

echo ""
echo "5️⃣  Re-ejecutando todas las migraciones en orden correcto..."
sudo -u www-data php artisan migrate --force 2>&1 | tail -30

echo ""
echo "6️⃣  Verificando estado de migraciones..."
sudo -u www-data php artisan migrate:status 2>&1 | tail -15

echo ""
echo "7️⃣  Compilando optimizaciones..."
sudo -u www-data php artisan optimize 2>&1 | head -5
sudo -u www-data php artisan view:cache 2>&1 | head -5

echo ""
echo "8️⃣  Saliendo del modo mantenimiento..."
sudo -u www-data php artisan up 2>&1 | head -3

echo ""
echo "════════════════════════════════════════════════════════"
echo "✅ LIMPIEZA Y RESTAURACIÓN COMPLETADAS"
echo "════════════════════════════════════════════════════════"
echo ""
echo "La aplicación está lista para usar."
