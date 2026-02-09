#!/bin/bash
# Script para extraer backup de BD del servidor actual (aunque esté comprometido)
# Los datos de BD probablemente están OK, es el SO el que está infectado

set -e

echo "📥 EXTRAYENDO BACKUP DE BASE DE DATOS"
echo ""

SERVER_ALIAS="offside-app"
LOCAL_BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="backup_offside_$TIMESTAMP.sql.gz"

# Crear directorio de backups
mkdir -p "$LOCAL_BACKUP_DIR"

echo "🔍 Conectando al servidor: $SERVER_ALIAS"
echo "📦 Creando dump de BD..."
echo "💾 Destino: $LOCAL_BACKUP_DIR/$BACKUP_FILE"
echo ""

# Crear backup remoto y transferir
ssh -T "$SERVER_ALIAS" << 'REMOTE_COMMANDS'
echo "📥 Creando backup remoto..."
mysqldump -u offside -p offside_app 2>/dev/null | gzip > /tmp/offside_backup.sql.gz
echo "✅ Backup creado en servidor"
REMOTE_COMMANDS

# Descargar
echo "📥 Descargando backup..."
scp "${SERVER_ALIAS}:/tmp/offside_backup.sql.gz" "$LOCAL_BACKUP_DIR/$BACKUP_FILE"

# Limpiar remoto
echo "🧹 Limpiando servidor..."
ssh -T "$SERVER_ALIAS" "rm -f /tmp/offside_backup.sql.gz"

# Verificar
FILE_SIZE=$(du -h "$LOCAL_BACKUP_DIR/$BACKUP_FILE" | cut -f1)
echo ""
echo "✅ Backup completado"
echo "   Archivo: $LOCAL_BACKUP_DIR/$BACKUP_FILE"
echo "   Tamaño: $FILE_SIZE"
echo ""
echo "🔐 Para restaurar en nuevo servidor:"
echo "   gunzip < $LOCAL_BACKUP_DIR/$BACKUP_FILE | mysql -u offside -p offside_app"
echo ""
