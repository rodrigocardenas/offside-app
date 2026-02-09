# 🔐 SSL Configuration - Manual Steps

**Estado:** Esperando acceso a la instancia  
**Problema:** Timeout de SSH a ec2-54-172-59-146.compute-1.amazonaws.com

---

## ⏳ Causa Probable

La DNS se está propagando. Puede tardar hasta **15-20 minutos** después de actualizar los registros DNS.

## ✅ Solución - Opción 1: Esperar y Ejecutar Script

```bash
# Esperar 10-15 minutos, luego ejecutar:
bash install-ssl.sh
```

---

## ✅ Solución - Opción 2: Conexión Manual

Si el script falla, ejecuta manualmente:

```bash
# 1. Obtener IP pública de la instancia desde AWS Console
# 2. Conectarse vía SSH
ssh -i "C:/Users/rodri/OneDrive/Documentos/aws/offside.pem" ubuntu@IP_PUBLICA

# 3. En la instancia remota:
sudo certbot --nginx \
    -d app.offsideclub.es \
    --non-interactive \
    --agree-tos \
    --email admin@offsideclub.es

# 4. Verificar certificado
sudo certbot certificates

# 5. Verificar HTTPS
curl -I https://app.offsideclub.es
```

---

## ✅ Solución - Opción 3: Usar IP Pública Directamente

Si DNS aún no propaga, usa la IP pública de AWS:

```bash
# En AWS Console → EC2 → Instances
# Copiar "Public IPv4 address"
# Reemplazar en el comando:

ssh -i "C:/Users/rodri/OneDrive/Documentos/aws/offside.pem" ubuntu@PUBLIC_IP

# Luego ejecutar certbot
sudo certbot --nginx -d app.offsideclub.es ...
```

---

## 📋 Checklist después de SSL

- [ ] `sudo certbot certificates` muestra el certificado
- [ ] `curl https://app.offsideclub.es` retorna 200
- [ ] Nginx reinicia sin errores
- [ ] Acceso via navegador a https://app.offsideclub.es funciona
- [ ] Certificado es válido (no auto-firmado)

---

## 🔄 Auto-Renovación SSL

Let's Encrypt emite certificados de 90 días. Certbot automáticamente renueva 30 días antes del vencimiento.

Verificar que la renovación automática está configurada:

```bash
sudo systemctl status certbot.timer
sudo systemctl enable certbot.timer
```

---

## 🆘 Si Algo Falla

1. **"Certificate not found"** → DNS aún no propagó, espera más
2. **"Connection refused"** → Security group no permite puerto 443
3. **"Timeout"** → Instancia no responde, verifica AWS Console

---

**Próximo paso:** Ejecuta `bash install-ssl.sh` cuando la instancia sea accesible
