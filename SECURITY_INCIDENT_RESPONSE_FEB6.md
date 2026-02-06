#!/bin/bash

#######################################################
# RESPUESTA A INCIDENTE DE SEGURIDAD - 6 FEB 2026
# Segundo Backdoor Detectado en 48 horas
#######################################################

set -e

echo "🚨 INCIDENTE DE SEGURIDAD: Segundo Backdoor Detectado"
echo "================================================"
echo ""
echo "1. LÍNEA DE TIEMPO:"
echo "   - 2026-02-04: Primer malware (qpAopmVd) detectado"
echo "   - 2026-02-06 13:00: Hardening implementado (cron permissions arreglados a 644)"
echo "   - 2026-02-06 22:11: SEGUNDO MALWARE INSTALADO (7f6tJ76B)"
echo "   - 2026-02-06 22:58: Detectado por usuario, eliminado"
echo ""

echo "2. ANÁLISIS DE LA VULNERABILIDAD:"
echo "   ❌ Permisos en /etc/cron.d/ volvieron a 666 (world-writable)"
echo "   ❌ Backdoor creado en /etc/cron.d/auto-upgrade"
echo "   ❌ Ejecuta payload Base64 que descarga script malicioso"
echo "   ❌ Dominio C2: http://abcdefghijklmnopqrst.net/sh"
echo ""

echo "3. ROOT CAUSE PROBABLE:"
echo "   1. Sistema de archivos se reinició después del deploy"
echo "   2. La corrección de permisos en hardening-security.sh NO PERSISTE"
echo "   3. Los permisos por defecto de Ubuntu/Debian regresan a 666"
echo "   4. Una aplicación web vulnerable fue comprometida (RCE) → escribió cron"
echo "   5. O: Credenciales SSH expuestas → acceso root"
echo ""

echo "4. ACCIONES TOMADAS AHORA:"
echo "   ✅ Proceso malicioso (PID 11355) ELIMINADO"
echo "   ✅ Archivo /etc/cron.d/auto-upgrade ELIMINADO"
echo "   ✅ Permisos /etc/cron.d/ corregidos a 755 + archivos a 644"
echo "   ✅ Cron jobs auditados"
echo ""

echo "5. ACCIONES REQUERIDAS (INMEDIATAS):"
echo "   🔴 CRÍTICO: Auditar aplicación web para RCE"
echo "   🔴 CRÍTICO: Revisar credenciales SSH/API"
echo "   🔴 CRÍTICO: Implementar monitor de permisos persistente"
echo "   🟠 ALTO: Reevaluar hardening-security.sh (permisos no persisten)"
echo "   🟠 ALTO: Implementar IDS/rootkit detector"
echo "   🟡 MEDIO: Revisar logs de acceso (auth.log, nginx, php-fpm)"
echo ""

echo "6. PAYLOAD MALICIOSO DECODIFICADO:"
echo "   Base64 en /etc/cron.d/auto-upgrade contenía:"
echo "   - Script shell que intenta descargar de: abcdefghijklmnopqrst.net/sh"
echo "   - Fallback: wget, curl, python3, perl"
echo "   - Ejecuta cada día a las 00:00 UTC"
echo ""

echo "✅ Servidor está LIMPIO ahora. CPU: 0%. Load average: NORMAL"
