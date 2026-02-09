#!/bin/bash
# ��� Script de conexión rápida al servidor en producción

PEM_KEY="C:/Users/rodri/OneDrive/Documentos/aws/offside.pem"
SERVER="ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com"
IP="100.30.41.157"

echo "════════════════════════════════════════════════════════════"
echo "        OFFSIDE CLUB - PRODUCTION SERVER"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "��� IP Pública: $IP"
echo "��� URL: http://offsideclub.local"
echo ""
echo "Opciones:"
echo "1) Conectar via SSH"
echo "2) Ver logs en tiempo real"
echo "3) Ver estado de servicios"
echo "4) Reiniciar workers"
echo "5) Ver Horizon dashboard"
echo ""
read -p "Selecciona una opción (1-5): " option

case $option in
  1)
    ssh -i "$PEM_KEY" "$SERVER"
    ;;
  2)
    ssh -i "$PEM_KEY" "$SERVER" "tail -f /var/log/laravel-worker.log"
    ;;
  3)
    ssh -i "$PEM_KEY" "$SERVER" "sudo supervisorctl status"
    ;;
  4)
    ssh -i "$PEM_KEY" "$SERVER" "sudo supervisorctl restart laravel-worker:*"
    ;;
  5)
    echo "��� Abre en navegador: http://$IP/horizon"
    ;;
  *)
    echo "❌ Opción inválida"
    ;;
esac
