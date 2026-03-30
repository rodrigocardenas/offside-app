# 🚀 DEPLOYMENT PRODUCCIÓN - OFFSIDE CLUB

## 📍 Información del Servidor

**IP Pública:** `100.30.41.157`  
**IP Interna:** `172.31.20.130`  
**Región:** AWS us-east-1  
**Instancia:** EC2 Ubuntu 24.04 LTS  
**Hostname:** `ec2-100-30-41-157.compute-1.amazonaws.com`

---

## 🔗 Configuración de Hosts Local

Agregar a `C:\Windows\System32\drivers\etc\hosts`:

```
100.30.41.157 offsideclub.local
100.30.41.157 offsideclub.test
100.30.41.157 app.offsideclub.local
```

---

## 📱 Servicios Activos

| Servicio | Status | Puerto | URL | Descripción |
|----------|--------|--------|-----|-------------|
| **Nginx** | ✅ RUNNING | 80/443 | http://100.30.41.157 | Web server |
| **PHP-FPM** | ✅ RUNNING | 9000 | - | PHP processor |
| **Laravel App** | ✅ RUNNING | 80 | http://100.30.41.157 | Main app |
| **Landing Page** | ✅ RUNNING | 3000 | http://100.30.41.157:3000 | Homepage |
| **Redis** | ✅ RUNNING | 6379 | - | Cache & queue |
| **Horizon** | ✅ RUNNING | 80 | http://100.30.41.157/horizon | Job monitoring |
| **Queue Workers** | ✅ RUNNING (4x) | - | - | Background jobs |
| **Supervisor** | ✅ ACTIVE | - | - | Process manager |

---

## 🗄️ Base de Datos

- **Endpoint:** `database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com`
- **Database:** `offside_club`
- **Usuario:** `offside` (⚠️ Cambiar contraseña)
- **Tablas:** 113
- **Status:** Conectada ✅

---

## 📂 Estructura de Carpetas

```
/var/www/
├── html/                      # App Laravel
│   ├── public/                # Web root
│   ├── app/
│   ├── routes/
│   ├── resources/
│   ├── storage/
│   ├── .env                   # Configuración
│   └── artisan
└── landing-page/              # Landing page (Express)
    ├── server.js
    ├── package.json
    └── node_modules/
```

---

## 🔄 Procesamiento de Trabajos

### 1. Horizon (Monitoring de Jobs)
```bash
# Acceder al dashboard
http://100.30.41.157/horizon

# Ver logs en tiempo real
ssh ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com
tail -f /var/log/horizon.log
```

### 2. Queue Workers (4 procesos)
```bash
# Ver estado
sudo supervisorctl status laravel-worker:*

# Reiniciar
sudo supervisorctl restart laravel-worker:*

# Ver logs
tail -f /var/log/laravel-worker.log
```

### 3. Redis (Cache)
```bash
# Conectar a Redis
redis-cli -p 6379

# Listar keys
KEYS *

# Limpiar todo
FLUSHALL
```

---

## 🔐 Credenciales

⚠️ **ACCIÓN REQUERIDA: Cambiar estas credenciales**

- **RDS Password:** `offside.2025` (COMPROMETIDA)
- **API Keys:** Verificar en `.env`
- **SSH Key:** `offside.pem` (guardar seguro)

### Cambiar Contraseña RDS

```bash
# Desde EC2
mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com \
  -u admin -p'offside.2025' \
  -e "ALTER USER 'offside'@'%' IDENTIFIED BY 'NEW_PASSWORD';"

# Actualizar .env en /var/www/html/.env
DB_PASSWORD=NEW_PASSWORD
```

---

## 📝 Configuración SSL (Let's Encrypt)

```bash
# Conectar a servidor
ssh -i "offside.pem" ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com

# Instalar Certbot
sudo apt update
sudo apt install -y certbot python3-certbot-nginx

# Obtener certificado (reemplazar dominio)
sudo certbot --nginx -d offsideclub.com

# Auto-renovación (ya configurado con cron)
```

---

## 📊 Monitoreo

### Ver Logs en Tiempo Real

```bash
# APP Laravel
tail -f /var/log/nginx/app-access.log
tail -f /var/log/nginx/app-error.log

# Queue Workers
tail -f /var/log/laravel-worker.log

# Horizon
tail -f /var/log/horizon.log

# Landing Page
tail -f /var/log/landing-page.log
```

### Verificar Recursos

```bash
# CPU y Memory
free -h
df -h

# Procesos
ps aux | grep php
ps aux | grep node
ps aux | grep redis

# Network
netstat -tulpn
```

---

## 🔧 Comandos Útiles

### Reiniciar Servicios

```bash
# Nginx
sudo systemctl restart nginx

# PHP-FPM
sudo systemctl restart php8.3-fpm

# Redis
sudo systemctl restart redis-server

# Supervisor (todos)
sudo supervisorctl restart all

# Supervisor (worker específico)
sudo supervisorctl restart laravel-worker:laravel-worker_00
```

### Laravel Artisan

```bash
cd /var/www/html

# Migraciones
php artisan migrate

# Cache
php artisan cache:clear
php artisan config:clear

# Jobs
php artisan queue:retry all
php artisan queue:flush
```

---

## 🧪 Pruebas Rápidas

```bash
# Test HTTP
curl -I http://100.30.41.157/
curl -I http://100.30.41.157:3000

# Test Base de datos
mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com \
  -u offside -p'offside.2025' \
  -e "SELECT VERSION();"

# Test Redis
redis-cli ping

# Test Queue
curl http://100.30.41.157/horizon
```

---

## 🚨 Troubleshooting

### 502 Bad Gateway
```bash
# Verificar PHP-FPM
sudo systemctl status php8.3-fpm
tail -f /var/log/php8.3-fpm.log

# Verificar socket
ls -la /run/php/php8.3-fpm.sock
```

### Workers No Procesando
```bash
# Verificar estado
sudo supervisorctl status laravel-worker:*

# Revisar logs
tail -f /var/log/laravel-worker.log

# Reiniciar
sudo supervisorctl restart laravel-worker:*

# Ver Redis
redis-cli LLEN queues:default
```

### Landing Page No Carga
```bash
# Verificar servicio
sudo supervisorctl status landing-page

# Ver logs
tail -f /var/log/landing-page.log

# Verificar puerto 3001
sudo ss -tulpn | grep 3001
```

---

## 📋 Checklist de Mantenimiento

- [ ] Cambiar contraseña RDS
- [ ] Configurar SSL (Let's Encrypt)
- [ ] Hacer repositorio GitHub PRIVATE
- [ ] Rotar SSH keys
- [ ] Verificar backups de BD
- [ ] Revisar logs regularmente
- [ ] Monitorear uso de disco
- [ ] Actualizar dependencias

---

## 🆘 Soporte

Para más información:
- GitHub: https://github.com/rodrigocardenas/offside-app
- Docs: `/var/www/html/docs/`
- Logs: `/var/log/`

