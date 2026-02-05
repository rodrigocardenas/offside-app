# 🔒 Análisis de Seguridad - Hardening Offsideclub

## 1. PROBABLE VECTOR DE ATAQUE EN INSTANCIA ANTERIOR

### Vulnerabilidades Típicas que Llevan a Rootkit:

**A) Acceso SSH Comprometido**
- ❌ SSH root login habilitado sin restricciones
- ❌ Contraseñas débiles en SSH
- ❌ Port 22 expuesto sin restricciones en SG

**B) Aplicaciones Web Vulnerables**
- ❌ Laravel en debug mode expuesto (`APP_DEBUG=true`)
- ❌ Rutas administrativas sin protección (Telescope, Debugbar)
- ❌ APIs sin rate limiting
- ❌ SQL Injection o RCE no parcheado

**C) Permisos de Archivos Incorrectos**
- ❌ /var/www con permisos 777
- ❌ .env accesible públicamente
- ❌ Archivos de configuración legibles

**D) Falta de Monitoreo**
- ❌ Sin auditoría de logs
- ❌ Sin IDS/detección de intrusiones
- ❌ Sin alertas de acceso root

---

## 2. ESTADO ACTUAL DE LA NUEVA INSTANCIA

### ✅ BIEN CONFIGURADO:
- SSH: `permitrootlogin without-password` ✅
- SSH: `passwordauthentication no` ✅ (solo keys)
- SSH: `pubkeyauthentication yes` ✅
- APP_DEBUG: `false` ✅
- Usuarios: solo root y ubuntu ✅

### ⚠️ ÁREAS DE RIESGO ACTUAL:
1. **No hay Firewall (UFW)** - Todos los puertos abiertos
2. **Port 22 accesible desde cualquier IP** - Sin restricción
3. **Puertos 80, 443, 9002, 6379 expuestos**
4. **Redis sin contraseña** - `REDIS_PASSWORD=null`
5. **Logs no monitoreados**
6. **No hay fail2ban** - Sin protección contra brute force

---

## 3. PASOS PARA HARDENING

### PASO 1: Instalar y Configurar Firewall (UFW)
```bash
# En producción
sudo apt-get update
sudo apt-get install -y ufw

# Habilitar firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Permitir solo SSH, HTTP, HTTPS
sudo ufw allow 22/tcp     # SSH
sudo ufw allow 80/tcp     # HTTP
sudo ufw allow 443/tcp    # HTTPS

# DENEGAR estos puertos (no públicos):
sudo ufw deny 6379/tcp    # Redis
sudo ufw deny 9002/tcp    # Next.js (solo localhost)
sudo ufw deny 3306/tcp    # MySQL (solo RDS)
sudo ufw deny 9000/tcp    # PHP-FPM
sudo ufw deny 25/tcp      # SMTP

# Activar firewall
sudo ufw enable
sudo ufw status verbose
```

### PASO 2: Configurar Redis con Contraseña
```bash
# En /etc/redis/redis.conf:
requirepass TU_CONTRASEÑA_FUERTE_AQUI

# Reiniciar Redis
sudo systemctl restart redis-server

# Actualizar .env en Laravel:
REDIS_PASSWORD=TU_CONTRASEÑA_AQUI
```

### PASO 3: Instalar Fail2Ban
```bash
sudo apt-get install -y fail2ban

# Crear config local
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Editar /etc/fail2ban/jail.local:
[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 5
findtime = 600
bantime = 3600

sudo systemctl restart fail2ban
```

### PASO 4: Auditoría de Logs
```bash
# Habilitar auditoría de comandos root
echo 'session required pam_exec.so /usr/local/bin/audit_commands.sh' | sudo tee -a /etc/pam.d/sudo

# Crear script de auditoría
cat > /tmp/audit_commands.sh << 'EOF'
#!/bin/bash
echo "[$(date)] User: $SUDO_USER | Command: $SUDO_COMMAND" >> /var/log/sudo-audit.log
EOF

sudo install -m 755 /tmp/audit_commands.sh /usr/local/bin/
```

### PASO 5: Deshabilitar Root Login por SSH
```bash
# En /etc/ssh/sshd_config cambiar:
PermitRootLogin without-password  ← ESTÁ BIEN
# O mejor aún:
PermitRootLogin no
# (Ubuntu ya requiere keys públicas)

sudo systemctl restart ssh
```

### PASO 6: Proteger .env
```bash
# En Laravel
sudo chown www-data:www-data /var/www/html/offside-app/.env
sudo chmod 600 /var/www/html/offside-app/.env  # Solo lectura/escritura www-data

# En Next.js (si hay .env)
sudo chown ubuntu:ubuntu /var/www/offside-landing/.env
sudo chmod 600 /var/www/offside-landing/.env
```

### PASO 7: Escanear Vulnerabilidades
```bash
# Instalar herramientas
sudo apt-get install -y lynis aide

# Ejecutar auditoría
sudo lynis audit system

# Crear baseline de archivos
sudo aideinit
```

### PASO 8: Monitoreo Activo
```bash
# Instalar osquery (monitoring avanzado)
sudo apt-get install -y osquery

# Ver procesos sospechosos
sudo osqueryi "SELECT * FROM processes WHERE name NOT IN (SELECT name FROM processes_baseline);"
```

---

## 4. SEGURIDAD DE AWS (GRUPO DE SEGURIDAD)

### Configuración Recomendada en Security Group:

| Puerto | Protocolo | Origen | Descripción |
|--------|-----------|--------|-------------|
| 22 | TCP | IP_TU_OFICINA/32 | SSH solo desde tu IP |
| 80 | TCP | 0.0.0.0/0 | HTTP público |
| 443 | TCP | 0.0.0.0/0 | HTTPS público |
| 6379 | TCP | DENY | Redis privado |
| 9002 | TCP | DENY | Next.js privado |

### En AWS Console:
```
Security Group > Inbound Rules:
- SSH (22): Only MY IP/32
- HTTP (80): Anywhere
- HTTPS (443): Anywhere
- Deny all other ports
```

---

## 5. CAMBIOS EN .env SEGURIDAD

```env
# ✅ Actualizar en producción:
APP_DEBUG=false                          ✅ Ya está
APP_ENV=production                       ✅ Ya está
APP_URL=https://app.offsideclub.es       ⚠️ Cambiar a HTTPS

# Seguridad de sesiones
SESSION_DOMAIN=.offsideclub.es           ✅ Ok
SESSION_SECURE=true                      ⚠️ Agregar (solo HTTPS)
SESSION_HTTP_ONLY=true                   ⚠️ Agregar (no JS access)
SESSION_SAME_SITE=Strict                 ⚠️ Agregar (CSRF protection)

# Rate limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60
```

---

## 6. COMO EVITAR QUE PASE DE NUEVO

### Checklist de Seguridad Mensual:
- [ ] Revisar logs de SSH: `sudo tail -f /var/log/auth.log`
- [ ] Revisar logs de Laravel: `tail -f storage/logs/laravel.log`
- [ ] Revisar usuarios activos: `w` y `last`
- [ ] Escanear rootkits: `sudo rkhunter --check --skip-keypress`
- [ ] Actualizar patches: `sudo apt-get update && apt-get upgrade`
- [ ] Revisar permisos de archivos críticos
- [ ] Revisar fail2ban bans: `sudo fail2ban-client status sshd`
- [ ] Monitorear conexiones abiertas: `sudo netstat -tulpn | grep LISTEN`

### Monitoreo en Tiempo Real:
```bash
# Ver intentos fallidos de SSH
sudo grep "Failed password" /var/log/auth.log | tail -20

# Ver accesos root
sudo grep "COMMAND=" /var/log/auth.log | grep sudo

# Ver conexiones establecidas
sudo ss -tulpn | grep ESTABLISHED
```

---

## 7. SCRIPT DE HARDENING COMPLETO

Crear `/tmp/hardening.sh`:

```bash
#!/bin/bash
set -e

echo "🔒 Iniciando hardening de seguridad..."

# 1. Firewall
echo "1️⃣  Instalando UFW..."
sudo apt-get install -y ufw
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw deny 6379/tcp
sudo ufw deny 9002/tcp
sudo ufw --force enable

# 2. Fail2Ban
echo "2️⃣  Instalando Fail2Ban..."
sudo apt-get install -y fail2ban
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo systemctl restart fail2ban

# 3. Auditoría
echo "3️⃣  Instalando Lynis..."
sudo apt-get install -y lynis

# 4. Permisos .env
echo "4️⃣  Asegurando .env..."
sudo chown www-data:www-data /var/www/html/offside-app/.env
sudo chmod 600 /var/www/html/offside-app/.env

# 5. Deshabilitar IPv6 si no lo usas
echo "5️⃣  Asegurando red..."
echo "net.ipv6.conf.all.disable_ipv6 = 1" | sudo tee -a /etc/sysctl.conf
sudo sysctl -p

# 6. Escaneo inicial
echo "6️⃣  Ejecutando auditoría Lynis..."
sudo lynis audit system --quick

echo "✅ Hardening completado!"
echo "⚠️  IMPORTANTE: Revisar logs en /var/log/auth.log"
```

---

## 8. RESUMEN: PROBABLE CAUSA DEL HACKEO ANTERIOR

Basándome en un rootkit a nivel kernel, probablemente:

1. **Acceso SSH comprometido** → Contraseña débil o root login abierto
2. **Exploit web no parcheado** → Vulnerable en Laravel/PHP
3. **Privilegios escalados** → Usuario comprometido corrió código malicioso
4. **Backdoor instalado** → Rootkit para persistencia

**Punto de entrada más probable:** 
- Laravel en debug mode expuesto
- Ruta /telescope o /horizon sin protección
- O credenciales SSH débiles

---

## 9. COMANDOS PARA EJECUTAR AHORA

```bash
# Detectar rootkits existentes
sudo apt-get install -y rkhunter chkrootkit
sudo rkhunter --check --skip-keypress
sudo chkrootkit

# Revisar procesos sospechosos
ps auxf
lsmod  # módulos del kernel cargados

# Revisar puertos abiertos
sudo netstat -tulpn
sudo ss -tulpn
```

---

## ✅ RECOMENDACIÓN FINAL

**EJECUTA ESTOS COMANDOS AHORA EN PRODUCCIÓN:**

```bash
ssh -i offside.pem ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com << 'EOF'
# Instalar y activar firewall
sudo apt-get install -y ufw
sudo ufw default deny incoming
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

# Instalar fail2ban y rkhunter
sudo apt-get install -y fail2ban rkhunter
sudo rkhunter --check --skip-keypress

# Proteger .env
sudo chown www-data:www-data /var/www/html/offside-app/.env
sudo chmod 600 /var/www/html/offside-app/.env

echo "✅ Sistema hardeneado básico completado"
EOF
```

Esto te dará protección inmediata contra los vectores de ataque más comunes.
