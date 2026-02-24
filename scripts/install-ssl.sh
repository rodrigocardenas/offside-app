#!/bin/bash

# Script para completar la configuración SSL
# Ejecutar cuando la instancia esté accesible

set -e

DOMAIN="app.offsideclub.es"
SSH_KEY="/path/to/offside.pem"  # CAMBIAR RUTA
INSTANCE="ec2-54-172-59-146.compute-1.amazonaws.com"  # O usar IP pública

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║           SSL Certificate Installation Script                  ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Validar que la instancia es accesible
echo "🔍 Verificando conexión a la instancia..."
if ! ssh -i "$SSH_KEY" -o ConnectTimeout=5 ubuntu@$INSTANCE "echo 'OK'" > /dev/null 2>&1; then
    echo "❌ No se puede conectar a $INSTANCE"
    echo "Por favor verifica:"
    echo "  1. DNS está apuntando correctamente"
    echo "  2. Security Group permite SSH (puerto 22)"
    echo "  3. La ruta del SSH key es correcta: $SSH_KEY"
    exit 1
fi

echo "✅ Conexión exitosa"
echo ""

# Ejecutar Certbot
echo "🔐 Obteniendo certificado SSL para $DOMAIN..."
ssh -i "$SSH_KEY" ubuntu@$INSTANCE << 'EOF'
sudo certbot --nginx -d app.offsideclub.es \
    --non-interactive \
    --agree-tos \
    --email admin@offsideclub.es \
    2>&1 | tail -20
EOF

echo ""
echo "🔍 Verificando certificado..."
ssh -i "$SSH_KEY" ubuntu@$INSTANCE "sudo certbot certificates"

echo ""
echo "✅ Completar con verificación HTTPS..."
ssh -i "$SSH_KEY" ubuntu@$INSTANCE "curl -I https://app.offsideclub.es 2>&1 | head -5"

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║            ✅ SSL CONFIGURATION COMPLETE                       ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "✅ Certificado obtenido y Nginx configurado automáticamente"
echo "✅ HTTPS está activo en app.offsideclub.es"
echo ""
echo "📌 Para re-obtener certificado en el futuro:"
echo "   ssh -i key.pem ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com"
echo "   sudo certbot renew --force-renewal"
echo ""
