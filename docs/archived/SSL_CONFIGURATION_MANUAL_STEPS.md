# 🔐 Configuración SSL - Pasos Manuales Requeridos

## ⚠️ IMPORTANTE - Prerequisito: Configurar DNS

Antes de obtener certificados SSL, **DEBES apuntar los dominios a la IP del servidor** en tu proveedor de DNS.

### 1. Configurar los Records DNS

En tu proveedor DNS (ej: Godaddy, CloudFlare, etc), crea o actualiza estos records:

```
Dominio: offsideclub.es
Tipo: A
Valor: 100.30.41.157
TTL: 3600

Dominio: www.offsideclub.es
Tipo: A
Valor: 100.30.41.157
TTL: 3600

Dominio: app.offsideclub.es
Tipo: A
Valor: 100.30.41.157
TTL: 3600
```

### 2. Verificar que DNS Propaga (5-15 minutos)

```bash
# En tu máquina local, espera un poco y verifica:
nslookup offsideclub.es
nslookup app.offsideclub.es

# Deberían resolver a: 100.30.41.157
```

---

## ✅ Una Vez DNS Esté Configurado

Una vez hayas completado la configuración DNS y verificado que propaga correctamente, ejecuta este comando en la instancia:

```bash
ssh -i "offside.pem" ubuntu@100.30.41.157 << 'SSL_FINAL'
echo "════════════════════════════════════════════════════════════"
echo "           OBTENER CERTIFICADOS SSL CON CERTBOT"
echo "════════════════════════════════════════════════════════════"

echo ""
echo "=== PASO 1: OBTENER CERTIFICADO PARA LANDING PAGE ==="
sudo certbot certonly --standalone \
  -d offsideclub.es \
  -d www.offsideclub.es \
  --non-interactive \
  --agree-tos \
  -m admin@offsideclub.es

echo ""
echo "=== PASO 2: OBTENER CERTIFICADO PARA LARAVEL APP ==="
sudo certbot certonly --standalone \
  -d app.offsideclub.es \
  --non-interactive \
  --agree-tos \
  -m admin@offsideclub.es

echo ""
echo "=== PASO 3: VERIFICAR CERTIFICADOS ==="
sudo certbot certificates

SSL_FINAL
```

---

## 📋 Configuraciones Nginx Finales

Una vez obtenidos los certificados, las configuraciones quedarán así:

### `/etc/nginx/sites-available/offsideclub.es` (Landing Page)

```nginx
# Redirigir HTTP a HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name offsideclub.es www.offsideclub.es;
    return 301 https://$host$request_uri;
}

# HTTPS con SSL
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name offsideclub.es www.offsideclub.es;

    ssl_certificate /etc/letsencrypt/live/offsideclub.es/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/offsideclub.es/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location / {
        proxy_pass http://localhost:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

### `/etc/nginx/sites-available/app.offsideclub.es` (Laravel App)

```nginx
# Redirigir HTTP a HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name app.offsideclub.es;
    return 301 https://$host$request_uri;
}

# HTTPS con SSL
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name app.offsideclub.es;

    root /var/www/html/public;
    index index.php index.html index.htm;
    client_max_body_size 128M;

    ssl_certificate /etc/letsencrypt/live/app.offsideclub.es/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.offsideclub.es/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

## 🔄 Completar la Configuración SSL

Una vez que Certbot te confirme que los certificados fueron obtenidos exitosamente, ejecuta este comando para aplicar las configuraciones finales con SSL:

```bash
ssh -i "offside.pem" ubuntu@100.30.41.157 << 'NGINX_SSL'
echo "=== CONFIGURAR NGINX CON SSL ==="

# Landing page con SSL
sudo tee /etc/nginx/sites-available/offsideclub.es > /dev/null <<'LANDING'
server {
    listen 80;
    listen [::]:80;
    server_name offsideclub.es www.offsideclub.es;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name offsideclub.es www.offsideclub.es;

    ssl_certificate /etc/letsencrypt/live/offsideclub.es/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/offsideclub.es/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location / {
        proxy_pass http://localhost:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
LANDING

# App con SSL
sudo tee /etc/nginx/sites-available/app.offsideclub.es > /dev/null <<'APP'
server {
    listen 80;
    listen [::]:80;
    server_name app.offsideclub.es;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name app.offsideclub.es;

    root /var/www/html/public;
    index index.php index.html index.htm;
    client_max_body_size 128M;

    ssl_certificate /etc/letsencrypt/live/app.offsideclub.es/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.offsideclub.es/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
APP

# Probar y reinicar nginx
sudo nginx -t && echo "✓ Config con SSL OK" || echo "✗ Error en SSL config"
sudo systemctl restart nginx

# Configurar auto-renovación
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

echo ""
echo "════════════════════════════════════════════════════════════"
echo "✅ SSL CONFIGURADO EXITOSAMENTE"
echo "════════════════════════════════════════════════════════════"

# Mostrar certificados
sudo certbot certificates

NGINX_SSL
```

---

## ✅ Verificaciones Finales

Una vez completado, verifica que todo funcione:

```bash
# En tu máquina local:

# 1. Verificar landing page con SSL
curl -I https://offsideclub.es/

# 2. Verificar app con SSL
curl -I https://app.offsideclub.es/

# 3. Verificar redirección HTTP → HTTPS
curl -I http://offsideclub.es/

# 4. Acceder desde el navegador:
# https://offsideclub.es/
# https://app.offsideclub.es/
```

---

## 📊 Estado Actual de la Instancia

| Componente | Estado | IP/Puerto |
|---|---|---|
| Nginx (HTTP) | ✅ ACTIVO | 80, 3000 |
| PHP 8.3-FPM | ✅ ACTIVO | socket |
| Redis | ✅ ACTIVO | 6379 |
| Supervisor | ✅ ACTIVO | — |
| Laravel Horizon | ✅ ACTIVO | — |
| Queue Workers (4x) | ✅ ACTIVO | — |
| Landing Page (Express) | ✅ ACTIVO | 3001 |
| RDS Database | ✅ CONECTADO | 172.31.16.43:3306 |

---

## 🔐 Auto-renovación de Certificados

Los certificados Let's Encrypt durán 90 días. Certbot renovará automáticamente:

```bash
# Ver estado del timer de renovación:
sudo systemctl status certbot.timer

# Logs de renovación:
sudo journalctl -u certbot -n 50
```

---

## ⚠️ Notas Importantes

1. **DNS Primero**: Sin DNS configurado, Certbot no puede validar los dominios
2. **TTL**: Puede tardar 5-15 minutos en propagar
3. **Puertos**: Asegúrate que Security Groups de AWS permitan puertos 80 y 443
4. **Email**: Los emails de Certbot irán a admin@offsideclub.es (cambia si necesario)

---

**Próximos pasos después de SSL:**
- [ ] Reemplazar landing page Express con versión Next.js real
- [ ] Cambiar contraseña de RDS (offside.2025 está comprometida)
- [ ] Rotar SSH keys
- [ ] Rotar API keys
- [ ] Terminar instancias comprometidas anteriores
