#!/bin/bash

# 🔒 PERMANENT CRON SECURITY FIX
# Mantiene /etc/cron.d/ protegido contra backdoors

# Fijar permisos correctos
echo "🔒 Fijando permisos de /etc/cron.d/..."
sudo chmod 755 /etc/cron.d
sudo chmod 644 /etc/cron.d/*
sudo chown root:root /etc/cron.d
sudo chown root:root /etc/cron.d/*

# Remover permisos de grupo/otros que puedan escribir
sudo chmod o-w /etc/cron.d
sudo chmod g-w /etc/cron.d

# Verificar
echo ""
echo "✅ Permisos actualizados:"
ls -la /etc/cron.d/

# Crear systemd timer para monitorear cambios
echo ""
echo "🔄 Creando monitor de cambios..."

cat > /tmp/monitor-cron-permissions.sh << 'EOF'
#!/bin/bash
# Monitor para permisos de /etc/cron.d

while true; do
    PERMS=$(stat -c "%a" /etc/cron.d)
    if [ "$PERMS" != "755" ]; then
        echo "⚠️ ALERT: /etc/cron.d permissions changed to $PERMS"
        logger -t cron-monitor "ALERT: /etc/cron.d permissions are $PERMS - fixing"
        chmod 755 /etc/cron.d
    fi
    
    # Check file permissions
    for file in /etc/cron.d/*; do
        PERM=$(stat -c "%a" "$file")
        if [ "$PERM" != "644" ]; then
            echo "⚠️ ALERT: $file permissions are $PERM"
            logger -t cron-monitor "ALERT: $file permissions are $PERM - fixing"
            chmod 644 "$file"
        fi
    done
    
    sleep 300  # Check every 5 minutes
done
EOF

sudo chmod +x /tmp/monitor-cron-permissions.sh

# Crear cron job que corre cada 5 minutos
echo "*/5 * * * * root chmod 755 /etc/cron.d && chmod 644 /etc/cron.d/* 2>/dev/null" | sudo tee /etc/cron.d/fix-cron-permissions > /dev/null

echo "✅ Monitor instalado"
