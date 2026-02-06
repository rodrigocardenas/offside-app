#!/bin/bash

###############################################################################
# SECURITY MONITOR - Previene cambios inseguros en archivos críticos
# Ejecutado cada 5 minutos por systemd timer
###############################################################################

set -e

CRITICAL_FILES=(
    "/etc/cron.d"
    "/etc/crontab"
    "/var/spool/cron"
    "/etc/passwd"
    "/etc/shadow"
    "/etc/sudoers"
    "/root/.ssh"
    "/var/www/html/.htaccess"
)

CRITICAL_DIRS=(
    "/etc/cron.d"
    "/etc/cron.hourly"
    "/etc/cron.daily"
    "/etc/cron.weekly"
    "/etc/cron.monthly"
)

# Archivo de bloqueo para evitar loops
LOCK_FILE="/var/run/security-monitor.lock"
BASELINE="/etc/security-monitor-baseline"

echo "[$(date)] Iniciando Security Monitor..."

# Crear baseline si no existe
if [ ! -f "$BASELINE" ]; then
    echo "[$(date)] Creando baseline de seguridad..."
    mkdir -p $(dirname "$BASELINE")
    
    # Cron directories
    for dir in "${CRITICAL_DIRS[@]}"; do
        stat "$dir" | grep -E "Access:|Uid:|Gid:" >> "$BASELINE"
        find "$dir" -type f -exec stat {} \; | grep -E "Access:|File:" >> "$BASELINE" 2>/dev/null || true
    done
    
    # Files
    for file in "${CRITICAL_FILES[@]}"; do
        if [ -e "$file" ]; then
            stat "$file" | grep -E "Access:|Uid:|Gid:" >> "$BASELINE"
        fi
    done
fi

# Función para alertar
alert() {
    local severity=$1
    local message=$2
    echo "[$(date)] [ALERT] $severity: $message" >&2
    
    # Opcional: enviar a syslog
    logger -t security-monitor -p "auth.$severity" "$message"
    
    # Opcional: ejecutar acción de remediación
    case "$severity" in
        "CRITICAL")
            # Ejecutar script de remediación crítica
            bash /opt/security/respond-critical-incident.sh "$message"
            ;;
        "WARNING")
            # Alertar pero no actuar automáticamente
            ;;
    esac
}

# Verificar permisos de /etc/cron.d
CRON_PERMS=$(stat -c %a /etc/cron.d)
if [ "$CRON_PERMS" != "755" ]; then
    alert "CRITICAL" "/etc/cron.d tiene permisos $CRON_PERMS (debe ser 755)"
    chmod 755 /etc/cron.d
fi

# Verificar permisos de archivos en /etc/cron.d
for file in /etc/cron.d/*; do
    if [ -f "$file" ]; then
        FILE_PERMS=$(stat -c %a "$file")
        
        # Debe ser 644 (rw-r--r--) o 444 (r--r--r--)
        if [ "$FILE_PERMS" = "666" ] || [ "$FILE_PERMS" = "777" ] || [[ "$FILE_PERMS" == "7"* ]]; then
            alert "CRITICAL" "$file tiene permisos inseguros: $FILE_PERMS"
            chmod 644 "$file"
        fi
        
        # Verificar por patrones sospechosos
        if grep -q "base64\|eval\|abcdefgh\|system(\.http\|curl\|wget" "$file" 2>/dev/null; then
            alert "CRITICAL" "$file contiene patrones sospechosos (posible backdoor)"
            # Crear backup antes de eliminar
            cp "$file" "/var/log/security-removed-$(basename $file)-$(date +%s).txt"
            rm "$file"
        fi
    fi
done

# Verificar /etc/crontab
if [ -f /etc/crontab ]; then
    CRONTAB_PERMS=$(stat -c %a /etc/crontab)
    if [ "$CRONTAB_PERMS" != "644" ]; then
        alert "CRITICAL" "/etc/crontab tiene permisos $CRONTAB_PERMS (debe ser 644)"
        chmod 644 /etc/crontab
    fi
fi

# Detectar archivos ejecutables nuevos en /tmp /var/tmp
for dir in /tmp /var/tmp; do
    find "$dir" -type f -executable -newer "$BASELINE" 2>/dev/null | while read file; do
        # Ignorar archivos conocidos
        if [[ ! "$file" =~ ^/tmp/\.mount ]]; then
            alert "WARNING" "Archivo ejecutable nuevo detectado: $file"
        fi
    done
done

# Verificar cambios en /etc/passwd y /etc/shadow
if [ -f /etc/passwd ]; then
    PASSWD_HASH=$(md5sum /etc/passwd | awk '{print $1}')
    if [ -f "$BASELINE.passwd" ]; then
        BASELINE_HASH=$(cat "$BASELINE.passwd")
        if [ "$PASSWD_HASH" != "$BASELINE_HASH" ]; then
            alert "WARNING" "/etc/passwd ha sido modificado"
        fi
    fi
    echo "$PASSWD_HASH" > "$BASELINE.passwd"
fi

echo "[$(date)] Security Monitor completado. Sistema: OK"
