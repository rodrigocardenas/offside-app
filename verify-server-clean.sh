#!/bin/bash
# Security Post-Reboot Verification & Hardening Script
# Run this IMMEDIATELY after server comes back online

set -e

echo "🔐 VERIFICACIÓN POST-REBOOT & HARDENING"
echo "════════════════════════════════════════════════"
echo ""

# 1. Verify malware is gone
echo "1️⃣ Verificando que malware fue eliminado..."
MALWARE_PROCS=$(ps aux | grep -E "wget|logic|91.92|x86_64.kok" | grep -v grep | wc -l)
if [ "$MALWARE_PROCS" -eq 0 ]; then
    echo "   ✅ LIMPIO - No hay procesos maliciosos"
else
    echo "   ❌ ADVERTENCIA - Encontrados $MALWARE_PROCS procesos sospechosos"
    ps aux | grep -E "wget|logic|91.92|x86_64" | grep -v grep
fi
echo ""

# 2. Check memory usage
echo "2️⃣ Uso de memoria (debería ser normal ahora):"
free -h
echo ""

# 3. Verify Laravel is running
echo "3️⃣ Verificando que Laravel está online..."
if curl -s http://localhost:80 > /dev/null 2>&1; then
    echo "   ✅ Aplicación respondiendo en puerto 80"
else
    echo "   ⚠️  No se puede acceder a la aplicación. Revisar logs:"
    echo "   tail -50 /var/www/html/offside-app/storage/logs/laravel.log"
fi
echo ""

# 4. Check database connection
echo "4️⃣ Verificando conexión a base de datos..."
cd /var/www/html/offside-app
if php artisan tinker << 'PHP_CHECK'
DB::connection()->getPdo();
echo "\n✅ Base de datos OK\n";
exit();
PHP_CHECK
then
    echo "   ✅ Base de datos conectada"
else
    echo "   ❌ Error de conexión a BD - revisar .env"
fi
echo ""

# 5. Check cron jobs are clean
echo "5️⃣ Verificando cron jobs (solo Laravel):"
sudo crontab -l 2>/dev/null | grep -v "^#" | grep -v "^$"
echo ""

# 6. Verify firewall is blocking malicious IP
echo "6️⃣ Verificando que IP 91.92.243.113 está bloqueada:"
sudo ufw status | grep "91.92.243.113" && echo "   ✅ Bloqueada" || echo "   ⚠️  No está en rules (pero blocked en kernel)"
echo ""

# 7. Check disk space
echo "7️⃣ Espacio en disco:"
df -h / | tail -1
echo ""

# 8. Show systemd unit status
echo "8️⃣ Status de servicios críticos:"
echo "   PHP-FPM:"
sudo systemctl status php8.3-fpm --no-pager 2>/dev/null | grep "Active:" || echo "   ℹ️  Revisar manualmente"
echo ""
echo "   Nginx:"
sudo systemctl status nginx --no-pager 2>/dev/null | grep "Active:" || echo "   ℹ️  Revisar manualmente"
echo ""

echo "════════════════════════════════════════════════"
echo "✅ VERIFICACIÓN COMPLETADA"
echo ""
echo "🔐 PRÓXIMOS PASOS CRÍTICOS:"
echo "1. Cambiar contraseña MySQL root"
echo "2. Actualizar .env con nuevas credenciales"
echo "3. Regenerar deploy key en GitHub"
echo "4. Revisar logs de acceso"
echo "5. Instalar herramientas de monitoreo"
echo ""
echo "Ver: SECURITY_CLEANUP_SUMMARY.md para detalles"
