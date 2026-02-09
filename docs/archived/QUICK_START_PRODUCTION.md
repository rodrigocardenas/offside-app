# 🚀 QUICK REFERENCE - NUEVA INSTANCIA OFFSIDE CLUB

## Configuración Rápida de Servidor Limpio

### Paso 1: Crear Instancia EC2
```bash
# Ubuntu 24.04 LTS
# t3.medium o superior (2GB RAM mínimo)
# Security Group: Allow 22 (SSH), 80 (HTTP), 443 (HTTPS)
```

### Paso 2: Conectar por SSH
```bash
ssh -i your-key.pem ubuntu@your-ip
sudo su
```

### Paso 3: Ejecutar Setup Script
```bash
cd /tmp
wget https://github.com/rodrigocardenas/offside-app/raw/main/setup-production.sh
bash setup-production.sh
```

**El script instala automáticamente:**
- ✅ PHP 8.3 + PHP-FPM
- ✅ Nginx
- ✅ MySQL 8.0
- ✅ Node.js 20
- ✅ Composer
- ✅ Redis
- ✅ Supervisor
- ✅ Fail2Ban + Firewall
- ✅ Clona repositorio
- ✅ Instala dependencias

### Paso 4: Configurar Variables de Entorno
```bash
nano /var/www/html/offside-app/.env
```

**Valores CRÍTICOS a cambiar:**
```bash
DB_PASSWORD=CONTRASEÑA_FUERTE
APP_KEY=base64:GENERATED_BY_SCRIPT
GEMINI_API_KEY=xxx
FIREBASE_PRIVATE_KEY=xxx
OPENAI_API_KEY=xxx
API_FOOTBALL_KEY=xxx
```

### Paso 5: Restaurar Base de Datos (si existe backup)
```bash
# Copiar archivo backup
scp backup.sql ubuntu@server:/home/ubuntu/

# En el servidor:
sudo bash /var/www/html/offside-app/restore-database.sh backup.sql
```

### Paso 6: Configurar Nginx
```bash
# Copiar template
sudo cp /var/www/html/offside-app/nginx.conf.example /etc/nginx/sites-available/offside-app

# Editar
sudo nano /etc/nginx/sites-available/offside-app

# Habilitar
sudo ln -s /etc/nginx/sites-available/offside-app /etc/nginx/sites-enabled/

# Validar
sudo nginx -t

# Reiniciar
sudo systemctl restart nginx
```

### Paso 7: Configurar SSL
```bash
sudo certbot --nginx -d app.offsideclub.es
```

### Paso 8: Configurar Queue Workers
```bash
# Copiar template
sudo cp /var/www/html/offside-app/supervisor.conf.example /etc/supervisor/conf.d/offside-queue.conf

# Editar si es necesario
sudo nano /etc/supervisor/conf.d/offside-queue.conf

# Habilitar
sudo supervisorctl reread
sudo supervisorctl update
```

### Paso 9: Regenerar SSH Keys
```bash
# NO reutilizar keys viejas (servidor estaba comprometido)
ssh-keygen -t ed25519 -f ~/.ssh/id_ed25519

# Copiar a GitHub
cat ~/.ssh/id_ed25519.pub
# Settings → Deploy Keys → Add New
```

### Paso 10: Verificar Funcionamiento
```bash
# Test web
curl https://app.offsideclub.es

# Test BD
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit()

# Test Queue
sudo supervisorctl status

# Ver logs
tail -f /var/log/supervisor/offside-queue-worker.log
tail -f /var/log/nginx/offside-app_error.log
```

---

## Archivos de Configuración Incluidos

| Archivo | Propósito |
|---------|-----------|
| `setup-production.sh` | Script automatizado de setup (1️⃣ USAR PRIMERO) |
| `.env.production.example` | Template de variables de entorno |
| `nginx.conf.example` | Template de configuración Nginx |
| `supervisor.conf.example` | Template para queue workers |
| `restore-database.sh` | Script para restaurar BD desde backup |

---

## Variables de Entorno (Valores Sensibles)

Obtener de:
- **FIREBASE_PRIVATE_KEY**: Firebase Console → Project Settings → Service Account
- **GEMINI_API_KEY**: Google Cloud Console → API Keys
- **OPENAI_API_KEY**: OpenAI Dashboard
- **API_FOOTBALL_KEY**: api-football.com → Profile

**IMPORTANTE:** No versionar en Git. Guardar en gestor de secretos (AWS Secrets Manager, 1Password, Bitwarden, etc.)

---

## Database Backup

### Crear Backup
```bash
mysqldump -u offside -p offside_app > backup-$(date +%Y%m%d).sql
```

### Restaurar
```bash
sudo bash /var/www/html/offside-app/restore-database.sh backup.sql
```

---

## Monitoring & Logs

```bash
# Nginx errors
tail -f /var/log/nginx/offside-app_error.log

# Application logs
tail -f /var/www/html/offside-app/storage/logs/laravel.log

# Queue workers
sudo supervisorctl status
tail -f /var/log/supervisor/offside-queue-worker.log

# System resources
htop

# Disk usage
df -h

# MySQL
sudo systemctl status mysql
```

---

## Security Hardening (Post-Setup)

```bash
# SSH: Deshabilitar password auth
sudo nano /etc/ssh/sshd_config
# PasswordAuthentication no
# PubkeyAuthentication yes

sudo systemctl restart ssh

# Firewall: Whitelist IPs
sudo ufw insert 1 allow from YOUR_IP to any port 22

# Fail2Ban: Monitor intentos fallidos
sudo fail2ban-client status sshd

# Automatic updates
sudo dpkg-reconfigure -plow unattended-upgrades
```

---

## Troubleshooting

### 502 Bad Gateway
```bash
# Revisar PHP-FPM
sudo systemctl status php8.3-fpm
sudo systemctl restart php8.3-fpm

# Revisar logs
tail -50 /var/log/nginx/offside-app_error.log
tail -50 /var/www/html/offside-app/storage/logs/laravel.log
```

### Database Connection Error
```bash
# Verificar MySQL
sudo systemctl status mysql

# Test conexión
mysql -u offside -p -e "SELECT 1;"

# Revisar .env: DB_HOST, DB_USERNAME, DB_PASSWORD
```

### Queue Workers Not Processing
```bash
# Verificar supervisor
sudo supervisorctl status

# Reiniciar
sudo supervisorctl restart offside-queue-worker:*

# Revisar logs
tail -f /var/log/supervisor/offside-queue-worker.log
```

### Memory/Disk Issues
```bash
# Memoria
free -h

# Disk
df -h

# Logs muy grandes
sudo truncate -s 0 /var/log/nginx/*.log
sudo truncate -s 0 /var/log/supervisor/*.log
```

---

## Timeframe Estimado

- **Setup automatizado:** 15-20 minutos
- **Configuración manual (Nginx, SSL, etc):** 10-15 minutos
- **Restauración BD:** Depende del tamaño (10-30 min para BD normal)
- **Verificación y testing:** 10 minutos

**Total:** ~1 hora para una instancia completamente funcional

---

## Checklist Final

- [ ] Instancia creada y SSH funcional
- [ ] Script setup-production.sh ejecutado exitosamente
- [ ] .env configurado con valores correctos
- [ ] Base de datos restaurada
- [ ] Nginx configurado y validado
- [ ] SSL certificado instalado
- [ ] Queue workers en ejecución
- [ ] Test web: curl https://app.offsideclub.es
- [ ] Test BD: php artisan tinker
- [ ] Logs revisados (sin errores críticos)
- [ ] Firewall habilitado
- [ ] SSH keys regeneradas (no reutilizar)
- [ ] Backups automatizados configurados

---

**Versión:** 2.0  
**Última actualización:** 2026-02-05  
**Estado:** PRODUCCIÓN LISTA PARA DEPLOY
