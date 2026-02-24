#!/bin/bash
# Script para restaurar base de datos desde backup
# Usage: sudo bash restore-database.sh <backup.sql>

if [ -z "$1" ]; then
    echo "❌ Error: Proporciona el archivo de backup"
    echo "Usage: sudo bash restore-database.sh backup.sql"
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ Archivo no encontrado: $BACKUP_FILE"
    exit 1
fi

echo "📥 Restaurando base de datos desde: $BACKUP_FILE"
echo ""
echo "⚠️  Ingresa contraseña de usuario MySQL 'offside':"

mysql -u offside -p offside_app < "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Base de datos restaurada exitosamente"
else
    echo ""
    echo "❌ Error durante la restauración"
    exit 1
fi

# Verificar integridad
echo ""
echo "🔍 Verificando integridad de BD..."
mysql -u offside -p offside_app -e "SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema='offside_app';"

echo "✅ Restauración completada"
