# 🚀 DEPLOYMENT STEPS - NEW INSTANCE

**EC2 Instance:** ec2-54-172-59-146.compute-1.amazonaws.com  
**OS:** Ubuntu 24.04 LTS  
**User:** ubuntu  
**SSH Key:** C:\Users\rodri\OneDrive\Documentos\aws\offside.pem

---

## ⏳ PASO 1: Aguardar Setup (En Progreso)

El script `setup-production.sh` está ejecutándose en background.

**Verificar progreso:**
```bash
ssh -i "C:\Users\rodri\OneDrive\Documentos\aws\offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com "tail -f /tmp/setup-production.log"
```

**Tiempo estimado:** 20-25 minutos

---

## 📋 PASO 2: Después que Setup Termine

Cuando el setup se complete:

### 2.1 Verificar que todo está instalado
```bash
ssh -i "C:\Users\rodri\OneDrive\Documentos\aws\offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com << 'EOF'
echo "=== PHP ===" && php -v | head -1
echo "=== Nginx ===" && nginx -v 2>&1
echo "=== MySQL ===" && mysql --version
echo "=== Node ===" && node -v
echo "=== Redis ===" && redis-cli --version
echo "=== Composer ===" && composer --version
EOF
```

### 2.2 Copiar archivos de configuración
```bash
# Copiar .env.production.example
scp -i "C:\Users\rodri\OneDrive\Documentos\aws\offside.pem" \
  /c/laragon/www/offsideclub/.env.production.example \
  ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com:/tmp/

# En la instancia:
ssh -i "C:\Users\rodri\OneDrive\Documentos\aws\offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com << 'EOF'
sudo cp /tmp/.env.production.example /var/www/html/offside-app/.env
sudo nano /var/www/html/offside-app/.env
# EDITAR ESTOS VALORES:
# DB_PASSWORD=<el generado en setup, revisar en /tmp/db-credentials.txt>
# FIREBASE_PRIVATE_KEY=<tu key>
# GEMINI_API_KEY=<tu key>
# OPENAI_API_KEY=<tu key>
# API_FOOTBALL_KEY=<tu key>
EOF
```

### 2.3 Restaurar base de datos (si tienes backup)
```bash
# Copiar backup a la instancia
scp -i "C:\Users\rodri\OneDrive\Documentos\aws\offside.pem" \
  ./backups/backup_offside_*.sql.gz \
  ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com:/tmp/

# En la instancia, restaurar
ssh -i "C:\Users\rodri\OneDrive\Documentos\aws\offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com << 'EOF'
cd /var/www/html/offside-app
sudo bash restore-database.sh /tmp/backup_offside_*.sql.gz
EOF
```

---

## 🔒 PASO 3: Configurar SSL y Nginx

```bash
ssh -i "C:\Users\rodri\OneDrive\Documentos\aws\offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com << 'EOF'
# Editar nginx.conf con tu dominio
sudo nano /etc/nginx/sites-available/offside-app
# Cambiar: server_name app.offsideclub.es;

# Instalar SSL
sudo certbot --nginx -d app.offsideclub.es

# Reiniciar Nginx
sudo systemctl restart nginx
EOF
```

---

## 🔍 PASO 4: Verificar Funcionamiento

```bash
ssh -i "C:\Users\rodri\OneDrive\Documentos\aws\offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com << 'EOF'
# Test web server
curl https://app.offsideclub.es

# Test database
php artisan tinker
> DB::connection()->getPdo();

# Test queue workers
sudo supervisorctl status

# Ver logs
tail -f /var/log/nginx/offside-app_error.log
tail -f /var/www/html/offside-app/storage/logs/laravel.log
EOF
```

---

## 📝 DATOS GENERADOS POR SETUP

Después del setup, revisar:
```bash
ssh -i "C:\Users\rodri\OneDrive\Documentos\aws\offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com "cat /tmp/db-credentials.txt"
```

Esto contiene:
- MySQL root password
- MySQL offside user password
- Database name
- Confirmación de instalación

---

## ✅ Checklist Final

- [ ] Setup script completó sin errores
- [ ] Verificar versiones (PHP, Nginx, MySQL, Node, Redis)
- [ ] .env editado con credenciales reales
- [ ] Base de datos restaurada (si hay backup)
- [ ] SSL certificado instalado
- [ ] Nginx funcionando
- [ ] Queue workers ejecutándose
- [ ] curl https://app.offsideclub.es → 200 OK
- [ ] DNS actualizado a nueva IP (54.172.59.146)

---

## 🆘 Si Algo Falla

Ver QUICK_START_PRODUCTION.md → Sección Troubleshooting

```bash
# Ver logs detallados
ssh -i "C:\Users\rodri\OneDrive\Documentos\aws\offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com << 'EOF'
sudo journalctl -xe | head -50
sudo systemctl status nginx php8.3-fpm mysql redis-server supervisor
EOF
```

---

**Next Step:** Aguardar que setup termine, luego seguir paso 2 en adelante.
