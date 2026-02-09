# REBUILD SERVIDOR - INSTRUCCIONES PASO A PASO

## ESTADO ACTUAL (Feb 8, 00:55 UTC)
- ✅ Backups completados y descargados:
  - `db-backup.sql` (27 KB) - Base de datos completa
  - `backup-storage-20260208/` (600+ MB) - Avatares y archivos
  - `.env.backup` - Configuración
  - `composer.lock.backup` - Dependencias
- ✅ Servidor comprometido eliminará backups temporales y se mantendrá como backup
- 🔄 Nueva instancia lista para crear

---

## FASE 2: CREAR NUEVA INSTANCIA EC2

### Opción A: Creación Manual en AWS Console (RECOMENDADO para este caso)

1. **Abre AWS Console:**
   - URL: https://console.aws.amazon.com
   - Region: **us-east-1** (esquina arriba a la derecha)

2. **Navega a EC2:**
   - Services > EC2 > Instances

3. **Haz click "Launch Instances"**

4. **Nombre de la instancia:**
   ```
   offside-app-clean-rebuild
   ```

5. **Seleccionar AMI:**
   - Busca: "Ubuntu 24.04 LTS"
   - Selecciona: Ubuntu 24.04 LTS (HVM) 64-bit (x86)
   - Nota el AMI ID

6. **Tipo de instancia:**
   - Instance Type: **t3.medium**
   - (2 vCPU, 4 GB RAM - igual al actual)

7. **Key Pair:**
   - Selecciona: **offside**

8. **Network Settings:**
   - VPC: default o la existente
   - Subnet: same as current (check current: ec2-52-3-65-135)
   - Auto-assign public IP: ✅ ENABLE
   - Selecciona el **Security Group EXISTENTE** (el que permite HTTP/HTTPS)

9. **Storage:**
   - Size: **30 GB** (como el actual)
   - Volume Type: **gp3**
   - Encryption: ❌ Desactivado (más rápido para rebuild)

10. **Tags:**
    ```
    Name: offside-app-clean-rebuild
    Environment: production-rebuild
    ```

11. **Haz click "Launch Instance"**

12. **ESPERA 2-3 MINUTOS** a que esté lista

### Opción B: Creación con AWS CLI (más rápido)

```bash
# 1. Obtén el security group ID del servidor actual
SG_ID=$(aws ec2 describe-instances \
  --region us-east-1 \
  --query 'Reservations[].Instances[?PublicIpAddress==`52.3.65.135`].SecurityGroups[0].GroupId' \
  --output text)

echo "Security Group: $SG_ID"

# 2. Obtén el AMI ID más reciente de Ubuntu 24.04
AMI_ID=$(aws ec2 describe-images \
  --region us-east-1 \
  --owners 099720109477 \
  --filters "Name=name,Values=ubuntu/images/hvm-ssd/ubuntu-noble-24.04-amd64-server-*" \
  --query 'sort_by(Images, &CreationDate)[-1].ImageId' \
  --output text)

echo "AMI: $AMI_ID"

# 3. Lanza la instancia
RESULT=$(aws ec2 run-instances \
  --region us-east-1 \
  --image-id "$AMI_ID" \
  --instance-type t3.medium \
  --key-name offside \
  --security-group-ids "$SG_ID" \
  --block-device-mappings 'DeviceName=/dev/sda1,Ebs={VolumeSize=30,VolumeType=gp3}' \
  --tag-specifications 'ResourceType=instance,Tags=[{Key=Name,Value=offside-app-clean-rebuild}]' \
  --query 'Instances[0].[InstanceId,PublicIpAddress]' \
  --output text)

INSTANCE_ID=$(echo $RESULT | awk '{print $1}')
PUBLIC_IP=$(echo $RESULT | awk '{print $2}')

echo "✅ Instance: $INSTANCE_ID"
echo "✅ Public IP: $PUBLIC_IP"
```

### Anota la Información:

```
NUEVA INSTANCIA EC2
===================
Instance ID: _____________ (ejemplo: i-0abc1234defgh5678)
Public IP:   _____________ (ejemplo: 54.123.45.67)
Private IP:  _____________ (ejemplo: 10.0.1.50)
Region:      us-east-1
Created:     _____________ (fecha/hora)

SSH Command:
ssh -i offside.pem ubuntu@[PUBLIC_IP]
```

---

## FASE 3: INSTALAR STACK LIMPIO

### 3.1 Esperar a que la instancia esté lista

```bash
# En terminal local:
PUBLIC_IP="54.123.45.67"  # Reemplaza con la IP real

# Probar conexión (puede fallar 1-2 veces)
for i in {1..10}; do
  if ssh -i ~/aws/offside.pem -o StrictHostKeyChecking=no ubuntu@$PUBLIC_IP "echo OK"; then
    echo "✅ Instancia lista!"
    break
  fi
  echo "⏳ Esperando... intento $i"
  sleep 10
done
```

### 3.2 Ejecutar script de instalación

**Opción A: Desde GitHub** (si ya subiste el script)
```bash
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'EOF'
cd /tmp

# Descargar script
curl -O https://raw.githubusercontent.com/rodrigocardenas/offside-app/main/install-clean-stack.sh

# O si no está en GitHub, lo haremos manual
bash install-clean-stack.sh
EOF
```

**Opción B: Manual paso a paso** (más seguro)

```bash
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'EOF'

echo "🔄 Actualizando sistema..."
sudo apt-get update
sudo apt-get upgrade -y

echo "📦 Instalando PHP 8.3 FPM..."
sudo apt-get install -y \
  php8.3-fpm php8.3-cli php8.3-common \
  php8.3-mysql php8.3-redis php8.3-gd \
  php8.3-curl php8.3-mbstring php8.3-xml \
  php8.3-zip php8.3-bcmath php8.3-intl

echo "📦 Instalando Nginx..."
sudo apt-get install -y nginx

echo "📦 Instalando Redis..."
sudo apt-get install -y redis-server

echo "📦 Instalando MySQL Client..."
sudo apt-get install -y mysql-client-8.0

echo "📦 Instalando Node.js 20..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

echo "📦 Instalando Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

echo "🔧 Iniciando servicios..."
sudo systemctl start php8.3-fpm
sudo systemctl start nginx
sudo systemctl start redis-server
sudo systemctl enable php8.3-fpm nginx redis-server

echo "✅ Stack instalado!"
php -v
nginx -v
redis-cli ping

EOF
```

### 3.3 Verificar instalación

```bash
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'EOF'
echo "=== PHP ==="
php -v | head -2

echo "=== Nginx ==="
nginx -v

echo "=== Redis ==="
redis-cli ping

echo "=== MySQL Client ==="
mysql --version

echo "=== Node.js ==="
node -v
npm -v

echo "=== Composer ==="
composer --version
EOF
```

---

## FASE 4: RESTAURAR DATOS

### 4.1 Restaurar Base de Datos

```bash
PUBLIC_IP="54.123.45.67"

echo "🔄 Restaurando base de datos..."

# Descargar backup a servidor remoto
scp -i ~/aws/offside.pem db-backup.sql ubuntu@$PUBLIC_IP:/tmp/

# Restaurar en RDS
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'RESTORE'
export MYSQL_PWD="offside.2025"

echo "Recreando base de datos..."
mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin << SQL
DROP DATABASE IF EXISTS offsideclub;
CREATE DATABASE offsideclub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL

echo "Importando datos..."
mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin offsideclub < /tmp/db-backup.sql

echo "✅ Base de datos restaurada"
mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin offsideclub -e "SELECT COUNT(*) as user_count FROM users;"

RESTORE
```

### 4.2 Restaurar Storage (Avatares, Logos, etc)

```bash
echo "📤 Copiando storage backup..."
scp -i ~/aws/offside.pem -r backup-storage-20260208 ubuntu@$PUBLIC_IP:/tmp/

ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'EOF'
# Crear directorio de aplicación
sudo mkdir -p /var/www/html/offside-app
sudo chown ubuntu:ubuntu /var/www/html/offside-app

# Crear estructura de storage
cd /var/www/html/offside-app
mkdir -p storage/app/public
mkdir -p storage/logs
mkdir -p storage/cache

# Copiar archivos desde backup
cp -r /tmp/backup-storage-20260208/* storage/

# Permisos correctos
chmod -R 755 storage
chmod -R 644 storage/app/public/*
chmod -R 755 storage/app/public/*/

# Verificar
ls -la storage/app/public | head -10
EOF
```

---

## FASE 5: DESPLEGAR APLICACIÓN

### 5.1 Clonar Repositorio

```bash
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'EOF'
cd /var/www/html

# Clonar repositorio
git clone https://github.com/rodrigocardenas/offside-app.git offside-app-new

# Si no es público, necesitas SSH key configurada en GitHub
# Para testing, puedes usar credentials o HTTPS

# Copiar a ubicación correcta si necesitas
mv offside-app-new offside-app
cd offside-app

EOF
```

### 5.2 Configurar .env

```bash
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'EOF'
cd /var/www/html/offside-app

# Copiar .env.example
cp .env.example .env

# Editar variables críticas
cat > .env << ENVFILE
APP_NAME="Offside Club"
APP_ENV=production
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=false
APP_URL=https://offsideclub.app
APP_TIMEZONE="Europe/Madrid"

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=offsideclub
DB_USERNAME=admin
DB_PASSWORD=offside.2025

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
QUEUE_CONNECTION=sync
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=localhost
MAIL_PORT=1025

ENVFILE

echo "✅ .env configurado"
EOF
```

### 5.3 Instalar Dependencias Laravel

```bash
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'EOF'
cd /var/www/html/offside-app

echo "📦 Composer install..."
composer install --no-dev --optimize-autoloader

echo "🔑 Generar APP_KEY..."
php artisan key:generate

echo "🔄 Running migrations..."
php artisan migrate --force

echo "🗑️  Clearing cache..."
php artisan config:cache
php artisan cache:clear
php artisan view:clear

echo "✅ Laravel configurado"
EOF
```

### 5.4 Instalar Landing (Next.js)

```bash
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'EOF'
cd /var/www/html/offside-app/offside-landing

echo "📦 npm ci..."
npm ci

echo "🏗️  npm run build..."
npm run build

echo "✅ Landing compilado"
EOF
```

---

## FASE 6: CONFIGURAR NGINX

### 6.1 Crear Virtual Host

```bash
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'EOF'
sudo tee /etc/nginx/sites-available/offside-app > /dev/null << 'NGINX'
server {
    listen 80;
    server_name _;
    root /var/www/html/offside-app/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }

    # Static files cache
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
NGINX

# Habilitar sitio
sudo ln -sf /etc/nginx/sites-available/offside-app /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Probar configuración
sudo nginx -t

# Recargar
sudo systemctl reload nginx

echo "✅ Nginx configurado"
EOF
```

---

## FASE 7: TESTING

```bash
PUBLIC_IP="54.123.45.67"

echo "🧪 Testing..."

# Test PHP
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP "php -v | head -2"

# Test MySQL
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'EOF'
export MYSQL_PWD="offside.2025"
mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin offsideclub -e "SELECT COUNT(*) as users FROM users;"
EOF

# Test Redis
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP "redis-cli ping"

# Test HTTP
echo "Probando HTTP..."
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" "http://$PUBLIC_IP"

# Test Laravel health check
curl -s "http://$PUBLIC_IP/api/health" | jq .

echo "✅ Testing completado"
```

---

## FASE 8: DNS / IP MIGRATION

### Una vez verificado que todo funciona:

**Opción A: Cambiar Elastic IP** (más rápido)
```bash
# En AWS Console > EC2 > Elastic IPs
# Asociar la IP pública del servidor actual a la nueva instancia
```

**Opción B: Cambiar DNS**
```bash
# En tu proveedor DNS (Route53, etc):
# Cambiar A record a la nueva IP pública
```

**Opción C: Actualizar Application Load Balancer** (si lo tienes)
```bash
# En AWS Console > EC2 > Load Balancers
# Cambiar target group de instancia antigua a nueva
```

---

## RESUMEN RÁPIDO (Copy & Paste)

```bash
# Variables
PUBLIC_IP="54.123.45.67"  # REEMPLAZA CON TU IP
SSH_KEY="~/aws/offside.pem"

# 1. Esperar instancia lista
ssh -i $SSH_KEY ubuntu@$PUBLIC_IP "echo OK"

# 2. Instalar stack
bash install-clean-stack.sh $PUBLIC_IP

# 3. Restaurar datos
scp -i $SSH_KEY db-backup.sql ubuntu@$PUBLIC_IP:/tmp/
scp -i $SSH_KEY -r backup-storage-20260208 ubuntu@$PUBLIC_IP:/tmp/

# 4. Deploy
bash deploy-app.sh $PUBLIC_IP

# 5. Testing
curl http://$PUBLIC_IP
```

---

## TROUBLESHOOTING

**SSH Connection Refused:**
```bash
# Espera 1-2 minutos más, instancia aún bootea
sleep 30
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP "echo OK"
```

**MySQL Connection Error:**
```bash
# Verifica credenciales RDS
export MYSQL_PWD="offside.2025"
mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin -e "SELECT 1;"

# Si falla, verifica security group de RDS permite acceso desde EC2 security group
```

**PHP Not Found:**
```bash
# Verifica php-fpm corre
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP "sudo systemctl status php8.3-fpm"
```

**Application Returns 500:**
```bash
# Ver logs
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP "tail -50 /var/www/html/offside-app/storage/logs/laravel.log"
```

---

## IMPORTANTE: SEGURIDAD EN NUEVA INSTANCIA

**Una vez online:**

1. Instalar PHP hardening (del documento anterior)
2. Cambiar credenciales de DB en AWS RDS
3. Generar nuevos APP_KEYS en Laravel
4. Habilitar HTTPS con Let's Encrypt
5. Configurar firewall/Security Group
6. Instalar fail2ban
7. Monitorear con CloudWatch

```bash
# Script de hardening rápido
ssh -i ~/aws/offside.pem ubuntu@$PUBLIC_IP << 'HARDEN'
# Descargar y ejecutar hardening
curl -O https://raw.githubusercontent.com/rodrigocardenas/offside-app/main/php-hardening-fix.sh
bash php-hardening-fix.sh
HARDEN
```

---

**¡Estás listo para comenzar el rebuild! 🚀**

Ahora ejecuta:
1. Crea la instancia EC2 (Fase 2)
2. Espera a que esté lista
3. Ejecuta los comandos de instalación (Fases 3-5)
4. Prueba y migra DNS (Fases 7-8)
