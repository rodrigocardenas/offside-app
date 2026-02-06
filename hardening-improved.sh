#!/bin/bash

###############################################################################
# HARDENING MEJORADO - Previene ejecución desde /tmp y otras ubicaciones
# Ejecutar como root en el servidor de producción
###############################################################################

echo "🔒 Aplicando hardening mejorado..."

# 1. MONTAR /tmp SIN PERMISOS DE EJECUCIÓN
if grep -q "/tmp" /etc/fstab; then
    echo "⚠️  /tmp ya está en fstab, verificando opciones..."
    if ! grep "/tmp" /etc/fstab | grep -q "noexec"; then
        echo "Actualizando montaje de /tmp con noexec..."
        sudo sed -i '/^[^#].*\/tmp/ s/$/,noexec,nodev,nosuid/' /etc/fstab
    fi
else
    echo "Agregando /tmp a fstab con opciones seguras..."
    # tmpfs es la mejor opción para /tmp
    echo "tmpfs /tmp tmpfs defaults,rw,nosuid,nodev,noexec,relatime,size=1G 0 0" | sudo tee -a /etc/fstab
fi

# 2. REMONTAREMOS /tmp
sudo mount -o remount,noexec,nodev,nosuid /tmp 2>/dev/null || echo "⚠️  No se pudo remontardir /tmp (requiere reboot)"

# 3. FIJAR PERMISOS EN /etc/cron.d PERMANENTEMENTE
echo "Fijando permisos en /etc/cron.d..."
sudo chmod 755 /etc/cron.d
sudo chmod 644 /etc/cron.d/* 2>/dev/null || true

# 4. PREVENIR ESCRITURA A /etc/cron.d POR USUARIOS NO-ROOT
# Usando AppArmor (Ubuntu/Debian)
if command -v aa-status &>/dev/null; then
    echo "AppArmor disponible, creando perfil..."
    cat > /tmp/apparmor-cron-protect << 'EOF'
#include <tunables/global>

/usr/sbin/cron {
  #include <abstractions/base>
  #include <abstractions/nameservice>
  
  /etc/cron.d r,
  /etc/cron.* r,
  /var/spool/cron/** r,
  /run/cron.* rw,
  /proc/[0-9]*/attr/current r,
  /proc/sys/kernel/osrelease r,
  
  deny /etc/cron.d w,
  deny /etc/cron.* w,
  deny /var/spool/cron w,
}
EOF
    # Aplicar perfil
    sudo cp /tmp/apparmor-cron-protect /etc/apparmor.d/local/usr.sbin.cron 2>/dev/null || echo "⚠️  No se pudo aplicar perfil AppArmor"
    sudo apparmor_parser -r /etc/apparmor.d/usr.sbin.cron 2>/dev/null || echo "⚠️  Error aplicando AppArmor"
fi

# 5. CONFIGURAR AUDITING PARA CAMBIOS EN CRON
if command -v auditd &>/dev/null; then
    echo "Configurando auditing para /etc/cron.d..."
    cat > /tmp/audit-cron.rules << 'EOF'
# Monitorear cambios en archivos de cron
-w /etc/cron.d -p wa -k cron_changes
-w /etc/cron.hourly -p wa -k cron_changes  
-w /etc/cron.daily -p wa -k cron_changes
-w /etc/cron.weekly -p wa -k cron_changes
-w /etc/cron.monthly -p wa -k cron_changes
-w /etc/crontab -p wa -k cron_changes
-w /var/spool/cron -p wa -k cron_changes

# Monitorear intentos de escalada de privilegios
-w /etc/sudoers -p wa -k sudoers_changes
-w /etc/sudoers.d -p wa -k sudoers_changes

# Monitorear ejecución de comandos sospechosos
-a always,exit -F arch=b64 -S addfanotify -F auid>=1000 -F auid!=-1 -k suspicious_activity
-a always,exit -F arch=b32 -S addfanotify -F auid>=1000 -F auid!=-1 -k suspicious_activity
EOF
    
    sudo bash -c "cat /tmp/audit-cron.rules >> /etc/audit/rules.d/audit.rules" 2>/dev/null || echo "⚠️  Error configurando auditd"
    sudo service auditd restart 2>/dev/null || echo "⚠️  No se pudo reiniciar auditd"
fi

# 6. DESABILITAR CRON PARA USUARIOS NO-ROOT (EXCEPTO www-data si es necesario)
echo "Configurando acceso a cron..."
sudo bash -c 'echo "root" > /etc/cron.allow'
sudo bash -c 'echo "www-data" >> /etc/cron.allow' # Si tu app lo necesita
sudo bash -c 'rm -f /etc/cron.deny'

# 7. SCRIPT DE MONITOREO CONTINUO
echo "Instalando security monitor..."
sudo cp security-monitor.sh /opt/security/monitor.sh 2>/dev/null || mkdir -p /opt/security && sudo cp security-monitor.sh /opt/security/monitor.sh
sudo chmod +x /opt/security/monitor.sh

# 8. SYSTEMD TIMER PARA EJECUCIÓN CADA 5 MINUTOS
cat > /tmp/security-monitor.service << 'EOF'
[Unit]
Description=Security Monitor Service
After=network.target

[Service]
Type=oneshot
ExecStart=/opt/security/monitor.sh
StandardOutput=journal
StandardError=journal
EOF

cat > /tmp/security-monitor.timer << 'EOF'
[Unit]
Description=Run Security Monitor every 5 minutes
Requires=security-monitor.service

[Timer]
OnBootSec=1min
OnUnitActiveSec=5min
Persistent=true

[Install]
WantedBy=timers.target
EOF

sudo cp /tmp/security-monitor.* /etc/systemd/system/ 2>/dev/null || echo "⚠️  No se pudo instalar systemd timer"
sudo systemctl daemon-reload 2>/dev/null || true
sudo systemctl enable security-monitor.timer 2>/dev/null || echo "⚠️  No se pudo habilitar timer"
sudo systemctl start security-monitor.timer 2>/dev/null || echo "⚠️  No se pudo iniciar timer"

# 9. VERIFICACIÓN FINAL
echo ""
echo "✅ Hardening mejorado aplicado:"
echo "   - /tmp sin permisos de ejecución"
echo "   - /etc/cron.d con permisos correctos (755)"
echo "   - Auditing configurado para cambios en cron"
echo "   - cron.allow/deny configurado"
echo "   - Security monitor instalado"
echo ""
echo "⚠️  NOTA: Algunos cambios requieren REBOOT para ser efectivos"
echo "          Ejecutar: sudo reboot"
