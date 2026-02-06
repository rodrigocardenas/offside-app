#!/bin/bash
# Script para eliminar proceso de CPU que consume 100%
# Ejecutar cuando el servidor responda: ssh ubuntu@ec2-... "bash /tmp/kill-cpu-hog.sh"

set -e

echo "🔍 Buscando procesos que consumen CPU..."
echo ""

# Buscar procesos sospechosos
echo "⚠️  Procesos con alto consumo de CPU:"
ps aux --sort=-%cpu | head -6

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Buscar el proceso qpAopmVd específicamente
if pgrep -f "qpAopmVd" > /dev/null; then
    echo "❌ Encontrado proceso sospechoso: qpAopmVd"
    PID=$(pgrep -f "qpAopmVd")
    echo "   PID: $PID"

    # Mostrar información del proceso
    echo "   Comando: $(ps -p $PID -o cmd= || true)"
    echo "   Ruta: $(ls -l /proc/$PID/cwd 2>/dev/null || echo 'N/A')"
    echo "   Tiempo ejecutando: $(ps -p $PID -o etime= 2>/dev/null || true)"

    echo ""
    echo "🚨 Terminando proceso..."
    kill -9 $PID

    if ! pgrep -f "qpAopmVd" > /dev/null; then
        echo "✅ Proceso eliminado exitosamente"
    else
        echo "❌ Error: Proceso aún está corriendo"
    fi
else
    echo "✅ Proceso qpAopmVd NO encontrado"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Buscar cron jobs sospechosos
echo "🔍 Verificando cron jobs..."
if [ -f /etc/cron.d/* ]; then
    echo "Cron jobs del sistema:"
    grep -r "qpAo\|\./" /etc/cron.d/ 2>/dev/null || echo "  Ninguno encontrado"
fi

# Verificar crontab de ubuntu
echo ""
echo "Cron jobs del usuario ubuntu:"
crontab -u ubuntu -l 2>/dev/null || echo "  Ninguno encontrado"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Limpieza completada"
