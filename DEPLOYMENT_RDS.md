# 📋 DEPLOYMENT CON AWS RDS

**Status:** ✅ Setup script actualizado para usar RDS (sin MySQL local)

---

## 🎯 Cambios Realizados

### setup-production.sh
- ❌ Removido: Instalación de MySQL Server local
- ❌ Removido: Creación manual de BD y usuario
- ✅ Agregado: MySQL Client (solo para CLI tools)
- ✅ Agregado: Confirmación de RDS antes de migraciones

### .env.production.example
- ✅ Actualizado `DB_HOST` a RDS endpoint
- ✅ Actualizado `DB_USERNAME` a admin (default RDS)
- ✅ Actualizado instrucciones para RDS

---

## 📝 CONFIGURACIÓN RDS

**Tu instancia RDS:**
```
Host: offside-db.c2j8xr6wq0qp.us-east-1.rds.amazonaws.com
Port: 3306
Database: offside_app
User: admin
Password: <buscar en AWS Secrets Manager o RDS console>
```

---

## 🚀 PASOS PARA DEPLOYMENT

### 1. El setup-production.sh ya está ejecutándose
- Instalará TODO excepto MySQL Server (ya lo tienes en RDS)
- Tiempo: ~15-20 min más

### 2. Cuando termine el setup, SSH a la instancia:
```bash
ssh -i "C:\Users\rodri\OneDrive\Documentos\aws\offside.pem" \
  ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com
```

### 3. Obtener credenciales RDS de AWS
```bash
# En AWS Console:
# RDS > Databases > offside-db
# Copiar: Endpoint, Master username, Master password
```

### 4. Editar .env
```bash
sudo nano /var/www/html/offside-app/.env

# Reemplazar:
DB_HOST=offside-db.c2j8xr6wq0qp.us-east-1.rds.amazonaws.com
DB_USERNAME=admin
DB_PASSWORD=<TU_PASSWORD_RDS>

# Otros valores:
GEMINI_API_KEY=...
FIREBASE_PRIVATE_KEY=...
API_FOOTBALL_KEY=...
OPENAI_API_KEY=...
```

### 5. Ejecutar migraciones
```bash
cd /var/www/html/offside-app
php artisan migrate --force
```

### 6. Verificar BD
```bash
# Test conexión a RDS
mysql -h offside-db.c2j8xr6wq0qp.us-east-1.rds.amazonaws.com \
      -u admin -p \
      -e "SHOW TABLES FROM offside_app;"

# Test desde Laravel
php artisan tinker
> DB::connection()->getPdo()
> DB::table('users')->count()
```

### 7. Configurar Nginx
```bash
# Copiar template
sudo cp nginx.conf.example /etc/nginx/sites-available/offside-app

# Editar dominio
sudo nano /etc/nginx/sites-available/offside-app
# Cambiar: server_name app.offsideclub.es;

# Enable y test
sudo ln -s /etc/nginx/sites-available/offside-app /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 8. SSL Certificate
```bash
sudo certbot --nginx -d app.offsideclub.es
```

### 9. Queue Workers
```bash
# Copiar config
sudo cp supervisor.conf.example /etc/supervisor/conf.d/offside-queue.conf

# Actualizar
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

### 10. Verificar todo
```bash
# Web
curl https://app.offsideclub.es

# DB
php artisan tinker
> DB::connection()->getPdo()

# Queue
sudo supervisorctl status

# Logs
tail -f /var/log/nginx/offside-app_error.log
tail -f /var/www/html/offside-app/storage/logs/laravel.log
```

---

## ✅ Checklist Final

- [ ] Setup script completó
- [ ] .env editado con RDS credentials
- [ ] RDS accessible desde EC2 (security group rules)
- [ ] Migraciones ejecutadas
- [ ] Nginx configurado
- [ ] SSL instalado
- [ ] Queue workers corriendo
- [ ] curl https://app.offsideclub.es → 200 OK
- [ ] BD datos correctos

---

## 🔐 Security Group RDS

**Importante:** Asegúrate que en AWS:

```
RDS Security Group:
- Inbound: Port 3306 from EC2 instance (o 0.0.0.0/0 si confías)
- Outbound: Allow all

EC2 Security Group:
- Inbound: 22 (SSH), 80 (HTTP), 443 (HTTPS)
- Outbound: Allow all
```

---

## 📊 Ventajas de usar RDS

✅ Backups automáticos  
✅ Multi-AZ failover  
✅ Patching automático  
✅ Monitoring y alertas  
✅ Encryption at rest  
✅ No necesitas administrar MySQL  
✅ Escalabilidad fácil  

---

**Status:** Listo para usar RDS 🚀
