# 🔒 Análisis de Seguridad - Cómo Entró el Malware

## 🔴 Vector de Ataque Identificado

**Culpable: Permisos Inseguros en `/etc/cron.d/`**

El proceso `qpAopmVd` (minero de criptomonedas) entró a través de permisos writable en cron.

### Evidencia:

```bash
# ANTES (❌ INSEGURO)
-rw-rw-rw- 1 root root  102 /etc/cron.d/.placeholder
```

Esto significa que **cualquier usuario** (incluyendo www-data que ejecuta PHP/Laravel) podía escribir en `/etc/cron.d/`.

## ⚠️ Cómo Funcionaba el Ataque

```
1. Vulnerable web application o dependency
   ↓
2. Código inyectado en PHP/Laravel que escribe en /etc/cron.d/
   ↓
3. Crea archivo .malware o modifica .placeholder
   ↓
4. cron ejecuta el script como root
   ↓
5. Descarga y ejecuta qpAopmVd (minero de crypto)
   ↓
6. Consume 100% CPU minando criptomonedas
```

## 📋 Posibles Orígenes del Exploit

### 1. **Dependency Package Vulnerable** (Más probable)
- composer.json con algún package comprometido
- npm dependencies con código malicioso
- Typosquatting en nombres de paquetes

### 2. **SQL Injection**
- Si hay query sin sanitizar que ejecuta comandos

### 3. **Arbitrary File Upload**
- Si hay función de upload sin validación

### 4. **Código Inyectado en Git**
- Un developer con acceso al repo subió código malicioso

## 🛡️ Correcciones Implementadas

### 1. ✅ Permisos Arreglados
```bash
# ANTES (❌)
-rw-rw-rw- 1 root root  102 /etc/cron.d/.placeholder

# DESPUÉS (✅)
-rw-r--r-- 1 root root  102 /etc/cron.d/.placeholder
```

### 2. ✅ Procesos Maliciosos Eliminados
```bash
pkill -9 qpAopmVd  # Detenido
```

### 3. ✅ Servidor Reiniciado
- Limpieza de procesos en memoria
- Cache limpio

## 🔍 Verificaciones que Realizamos

✅ **Crontab del root:** Limpio, sin qpAo
✅ **Crontab del usuario:** Ninguno
✅ **Systemd services:** Ninguno sospechoso
✅ **Git history:** Commits limpios
✅ **Scripts en /tmp:** Ninguno
✅ **Procesos actuales:** CPU en 0% (normal)

## 📊 Timeline del Incidente

```
Fecha desconocida: Exploit entra al sistema
   ├─ Código malicioso se ejecuta con permisos de www-data
   ├─ Escribe en /etc/cron.d/ (permisos inseguros lo permitieron)
   └─ cron ejecuta la descarga del minero

Hace ~7-8 horas (aproximadamente 05:xx del 6 de Feb):
   └─ qpAopmVd inicia y consume 100% CPU

Hoy 12:46: Reinicio de servidor
   └─ Proceso eliminado, limpieza de memoria
```

## 🔐 Próximos Pasos de Seguridad

### 1. **Auditar Dependencias**
```bash
# Revisar composer.json
grep -r "require" composer.json | sort

# Verificar packages conocidos como comprometidos
composer audit
npm audit
```

### 2. **Revisar Logs de PHP/Apache**
```bash
# Buscar ejecuciones sospechosas de system()
sudo grep -i "system\|exec\|passthru" /var/log/php-fpm/*.log

# Ver acceso a /etc/cron.d/
sudo grep "/etc/cron" /var/log/apache2/access.log
```

### 3. **Hardening de Permisos**
```bash
# Arreglar permisos globales
sudo find /etc -type f -perm /002 -exec chmod o-w {} \;
sudo find /etc -type f -perm /020 -exec chmod g-w {} \;

# Especialmente cron
sudo chmod 755 /etc/cron.d
sudo chmod 644 /etc/cron.d/*
sudo chmod 644 /etc/crontab
```

### 4. **Restricciones en PHP**
En `/etc/php/8.3/fpm/php.ini`:
```ini
# Deshabilitar funciones peligrosas
disable_functions = system,exec,passthru,shell_exec,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

# Restringir acceso a directorios
open_basedir = /var/www/html/offside-app:/tmp:/var/tmp
```

### 5. **SELinux o AppArmor**
```bash
# Ubuntu usa AppArmor, configurar perfiles restrictivos
sudo aa-enforce /etc/apparmor.d/usr.sbin.apache2
```

## ✅ Estado Actual

```
✅ CPU: Normal (0-1%)
✅ Procesos: Limpios
✅ Crontab: Seguro
✅ Permisos: Arreglados
✅ Symlinks: Activos
✅ Logos: Funcionando
```

## 📌 Recomendaciones Finales

1. **Cambiar todas las credenciales:**
   - SSH keys
   - Database passwords
   - API keys en .env

2. **Auditar archivos recientes:**
   ```bash
   sudo find / -type f -mtime -30 2>/dev/null | grep -v proc | grep -v sys
   ```

3. **Implementar WAF (Web Application Firewall)**
   - ModSecurity en Apache/Nginx
   - OWASP Core Rule Set

4. **Monitoreo continuo:**
   - Prometheus + Grafana para CPU/memoria
   - Osquery para integridad de archivos
   - CloudFlare WAF si es posible

5. **Backups seguros:**
   - Versión limpia de la BD antes del exploit
   - Backups encriptados en S3
