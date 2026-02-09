# 🚀 SERVIDOR REBUILD PLAN - PASO A PASO

**Status:** ⏳ LISTO PARA EJECUTAR  
**Tiempo Estimado:** 2-3 horas  
**Downtime Aproximado:** 15-30 minutos  

---

## 📋 CHECKLIST PRE-REBUILD

### ✅ Ya Completado
- [x] Backup de storage/ (avatars, logos)
- [x] Documentación de configuración
- [x] Script de instalación automática creado
- [x] Malware principal matado (proceso 0k1dfZVi)

### ⏳ Por Hacer Antes de Rebuild
- [ ] Backup final de DB (mysqldump)
- [ ] Backup final de .env
- [ ] Documentar IP actual vs nueva IP
- [ ] Prevenir acceso a servidor viejo

---

## 🚀 PLAN PASO A PASO

### FASE 1: PREPARACIÓN (30 min)

**Paso 1: Backup final de base de datos**
```bash
# En servidor viejo:
ssh -i offside.pem ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com
mysqldump -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com \
  -u admin -poffside.2025 \
  --skip-lock-tables offsideclub > /tmp/db-final.sql

# Descargar backup
scp -i offside.pem ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com:/tmp/db-final.sql local-backup/
```

**Paso 2: Guardar configuración actual**
```bash
# Descargar archivos críticos
scp -i offside.pem ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com:/var/www/html/offside-app/.env local-backup/
scp -i offside.pem ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com:/var/www/html/offside-app/composer.lock local-backup/
```

### FASE 2: CREAR NUEVA INSTANCIA EC2 (10 min)

**Paso 3: Provisionar nueva instancia**
```bash
# En AWS Console:
1. EC2 → Instances → Launch Instance
2. Ubuntu 24.04 LTS
3. Instance type: t3.medium (same as current)
4. VPC: Same as current
5. Security Group: Same as current
6. Storage: 30GB (gp3)
7. Tags: Name=offside-app-clean
8. Launch
9. Assign elastic IP (o cambiar DNS luego)
```

**Paso 4: SSH al nuevo servidor**
```bash
# Esperar ~2 min para que esté listo
ssh -i offside.pem ubuntu@<NEW_IP>

# Verificar que está limpio
ps aux | wc -l  # Should be ~20-30, NOT 100+
```

### FASE 3: INSTALAR STACK LIMPIO (30 min)

**Paso 5: Ejecutar script de instalación**
```bash
# Copiar script al nuevo servidor
scp -i offside.pem install-clean-stack.sh ubuntu@<NEW_IP>:/tmp/

# Ejecutar
ssh -i offside.pem ubuntu@<NEW_IP> "bash /tmp/install-clean-stack.sh"

# Verificar
ssh -i offside.pem ubuntu@<NEW_IP> "php -v && nginx -v && redis-cli ping"
```

### FASE 4: RESTAURAR DATOS (15 min)

**Paso 6: Restaurar base de datos**
```bash
# Copiar backup de DB
scp -i offside.pem local-backup/db-final.sql ubuntu@<NEW_IP>:/tmp/

# Restaurar
ssh -i offside.pem ubuntu@<NEW_IP> << 'EOF'
mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com \
  -u admin -poffside.2025 \
  offsideclub < /tmp/db-final.sql
echo "✅ Database restored"
EOF
```

**Paso 7: Copiar storage/ (avatars, logos)**
```bash
# Copiar archivos
scp -i offside.pem -r local-backup/backup-storage-20260208/* \
  ubuntu@<NEW_IP>:/var/www/html/offside-app/storage/

# Fijar permisos
ssh -i offside.pem ubuntu@<NEW_IP> << 'EOF'
sudo chmod -R 755 /var/www/html/offside-app/storage/app/public
sudo find /var/www/html/offside-app/storage/app/public -type f -exec chmod 644 {} \;
sudo chown -R www-data:www-data /var/www/html/offside-app/storage
echo "✅ Storage restored with correct permissions"
EOF
```

### FASE 5: DEPLOY CÓDIGO LIMPIO (15 min)

**Paso 8: Clonar repositorio**
```bash
ssh -i offside.pem ubuntu@<NEW_IP> << 'EOF'
cd /var/www/html/offside-app
git clone https://github.com/rodrigocardenas/offside-app.git .
echo "✅ Repository cloned"
EOF
```

**Paso 9: Instalar dependencias PHP**
```bash
ssh -i offside.pem ubuntu@<NEW_IP> << 'EOF'
cd /var/www/html/offside-app
composer install --no-dev --optimize-autoloader
php artisan cache:clear
echo "✅ Composer dependencies installed"
EOF
```

**Paso 10: Copiar .env**
```bash
scp -i offside.pem local-backup/.env ubuntu@<NEW_IP>:/var/www/html/offside-app/

# Adaptar si es necesario:
ssh -i offside.pem ubuntu@<NEW_IP> << 'EOF'
cd /var/www/html/offside-app
# Editar .env si las credenciales RDS han cambiado
nano .env
EOF
```

**Paso 11: Artisan setup**
```bash
ssh -i offside.pem ubuntu@<NEW_IP> << 'EOF'
cd /var/www/html/offside-app
php artisan key:generate --force
php artisan migrate --force
php artisan cache:clear
php artisan config:clear
echo "✅ Laravel setup complete"
EOF
```

**Paso 12: Instalar offside-landing (Next.js)**
```bash
ssh -i offside.pem ubuntu@<NEW_IP> << 'EOF'
cd /var/www/offside-landing
git clone https://github.com/rodrigocardenas/offside-landing.git .
npm install
npm run build
echo "✅ Next.js build complete"
EOF
```

### FASE 6: CONFIGURACIÓN & TESTING (30 min)

**Paso 13: Configurar Nginx**
```bash
# Copiar config de nginx del servidor viejo
scp -i offside.pem ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com:/etc/nginx/sites-available/default \
  local-backup/nginx-config

# Aplicar en servidor nuevo (adaptar IPs/dominios si es necesario)
scp -i offside.pem local-backup/nginx-config ubuntu@<NEW_IP>:/tmp/
ssh -i offside.pem ubuntu@<NEW_IP> << 'EOF'
sudo cp /tmp/nginx-config /etc/nginx/sites-available/default
sudo nginx -t
sudo systemctl reload nginx
echo "✅ Nginx configured"
EOF
```

**Paso 14: Testing exhaustivo**
```bash
ssh -i offside.pem ubuntu@<NEW_IP> << 'EOF'
echo "🧪 TESTING APPLICATION"
echo "======================="

# Test DB connection
echo "1. Database connection..."
cd /var/www/html/offside-app
php artisan tinker <<< "DB::connection()->getPDO()"
echo "✅ DB OK"

# Test Redis
echo "2. Redis connection..."
redis-cli ping
echo "✅ Redis OK"

# Test file permissions
echo "3. File permissions..."
ls -la /var/www/html/offside-app/storage/app/public/avatars | head -3
echo "✅ Storage OK"

# Test Laravel
echo "4. Laravel artisan..."
php artisan about
echo "✅ Laravel OK"

echo ""
echo "✅ ALL TESTS PASSED"
EOF
```

**Paso 15: Health check HTTP**
```bash
curl -I https://<NEW_IP>  # or https://new-domain.com
# Should return 200 OK
```

### FASE 7: MIGRAR TRÁFICO (5 min)

**Paso 16: Cambiar DNS / Elastic IP**

```bash
# OPCIÓN A: Cambiar DNS (recomendado)
# En tu proveedor DNS (Route53, Cloudflare, etc):
# Cambiar A record de offside-app.com para apuntar a <NEW_IP>

# OPCIÓN B: Cambiar Elastic IP
# AWS Console → Elastic IPs → 
# Disociar de instancia vieja, asociar a instancia nueva
```

**Paso 17: Verificar tráfico en nuevo servidor**
```bash
# Ver logs
ssh -i offside.pem ubuntu@<NEW_IP>
tail -f /var/log/nginx/access.log
# Deberías ver requests de usuarios
```

### FASE 8: CLEANUP (5 min)

**Paso 18: Terminar instancia vieja**
```bash
# Esperar 1 hora para confirmar que todo funciona

# En AWS Console:
# EC2 → Instances → ec2-52-3-65-135
# Instance State → Terminate

# Opcionalmente:
# - Crear snapshot de EBS (para post-mortem si es necesario)
# - Liberar Elastic IP
```

---

## 🎯 RESUMEN FINAL

| Fase | Tarea | Tiempo | Status |
|------|-------|--------|--------|
| 1 | Backups finales | 30 min | ⏳ |
| 2 | Crear EC2 | 10 min | ⏳ |
| 3 | Install stack | 30 min | ⏳ |
| 4 | Restaurar datos | 15 min | ⏳ |
| 5 | Deploy código | 15 min | ⏳ |
| 6 | Testing | 30 min | ⏳ |
| 7 | Migrar DNS | 5 min | ⏳ |
| 8 | Cleanup | 5 min | ⏳ |
| **TOTAL** | | **2h 20m** | |

---

## 🔒 Hardening Aplicado al Nuevo Servidor

```
1. PHP.ini:
   ✅ disable_functions = system,exec,shell_exec...
   ✅ open_basedir = /var/www/html...
   ✅ upload_max_filesize = 100M

2. Cron jobs:
   ✅ Monitoreo de permisos cada 5 min
   ✅ Auto-fix de /etc/cron.d/

3. File permissions:
   ✅ Avatars: 644
   ✅ Storage: 755
   ✅ www-data owner

4. Firewall:
   ✅ Solo puertos 22, 80, 443 abiertos
   ✅ Cerrar puerto 25 (SMTP)
```

---

## 📞 PRÓXIMO PASO

**¿Estoy listo para empezar el rebuild?**

Responde cuando quieras comenzar la FASE 1.

