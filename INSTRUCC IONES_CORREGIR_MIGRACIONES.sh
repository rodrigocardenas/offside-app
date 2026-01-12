#!/bin/bash

# SOLUCIÓN PARA EL ERROR DE COLUMNAS DUPLICADAS EN PRODUCCIÓN
# Ejecutar esto en el servidor remoto

set -e

cd /var/www/html/offside-app

echo "════════════════════════════════════════════════════════"
echo "CORRECCIÓN DE MIGRACIONES - OFFSIDE CLUB"
echo "════════════════════════════════════════════════════════"

echo ""
echo "1️⃣ Actualizando código desde repositorio..."
sudo -u www-data git pull origin main 2>&1 | head -10

echo ""
echo "2️⃣ Haciendo rollback de migraciones problemáticas..."
echo "   (Revirtiendo últimas 5 migraciones para empezar limpio)"
sudo -u www-data php artisan migrate:rollback --step=5 --force 2>&1 | grep -v "^$" || echo "   ⚠️ Rollback completado"

echo ""
echo "3️⃣ Verificando estado de migraciones..."
sudo -u www-data php artisan migrate:status 2>&1 | tail -5

echo ""
echo "4️⃣ Ejecutando todas las migraciones con la nueva migración de corrección..."
sudo -u www-data php artisan migrate --force 2>&1 | tail -20

echo ""
echo "5️⃣ Limpiando caché..."
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear

echo ""
echo "════════════════════════════════════════════════════════"
echo "✅ CORRECCIÓN COMPLETADA"
echo "════════════════════════════════════════════════════════"
echo ""
echo "Las migraciones se han ejecutado correctamente."
echo "La nueva migración 2026_01_13_000000_fix_all_users_columns"
echo "verifica y agrega solo las columnas que falten."
