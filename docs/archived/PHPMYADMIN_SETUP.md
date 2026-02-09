# 🛠️ PHPMYADMIN CONFIGURACIÓN

## Acceso a phpMyAdmin

### URL Segura (SSH Tunnel)
```bash
# Desde tu máquina local (RECOMENDADO - MÁS SEGURO):
ssh -i offside.pem -L 8080:localhost:8080 ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com

# Luego abre en navegador:
http://localhost:8080
```

### Credenciales de Base de Datos
- **Host:** database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com
- **Puerto:** 3306
- **Usuario:** admin
- **Contraseña:** offside.2025
- **Base de datos:** offsideclub

### Configuración Actual
- **Ubicación en servidor:** `/usr/share/phpmyadmin/`
- **Puerto:** 8080 (solo localhost por defecto)
- **Firewall:** Abierto a 0.0.0.0/0 en puerto 8080

---

## ⚠️ SEGURIDAD - IMPORTANTE

### Método RECOMENDADO (SSH Tunnel - Más Seguro)
```bash
# 1. Ejecuta este comando en tu máquina:
ssh -i offside.pem -L 8080:localhost:8080 ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com

# 2. El SSH quedará abierto. En otra terminal:
# (Manteniendo el túnel activo)

# 3. Abre navegador:
http://localhost:8080

# VENTAJAS:
# ✅ Encriptado end-to-end (SSH)
# ✅ Sin exponer phpMyAdmin a internet
# ✅ Acceso solo desde tu máquina
# ✅ No requiere contraseña adicional
```

### Método ALTERNATIVO (No Recomendado)
Si necesitas acceso desde internet (menos seguro):

```bash
# 1. Primero, instalar protección HTTP básica:
ssh -i offside.pem ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com

# 2. En el servidor:
sudo apt-get install -y apache2-utils
sudo htpasswd -c /etc/nginx/.htpasswd_pma admin

# 3. Editar /etc/nginx/conf.d/phpmyadmin-8080.conf y agregar:
auth_basic "Acceso Restringido";
auth_basic_user_file /etc/nginx/.htpasswd_pma;

# 4. Recargar nginx:
sudo nginx -t && sudo systemctl reload nginx

# 5. Acceder desde internet:
https://your-server-ip:8080
# (Te pedirá usuario: admin y contraseña)
```

---

## 🔐 CONFIGURACIÓN ACTUAL DE PHPMYADMIN

**Archivo config:** `/usr/share/phpmyadmin/config.inc.php`

```php
Servidor: database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com
Puerto: 3306
Autenticación: config (sin login screen)
Lenguaje: Español
Charset: UTF-8
AllowUserDropDatabase: false (no puede eliminar BDs)
ShowPhpInfo: false (información ocultada)
```

---

## 📊 ESTADO ACTUAL

✅ phpMyAdmin instalado
✅ Configurado con credenciales de RDS
✅ Puerto 8080 abierto en firewall
✅ Negación de acceso a directorios sensibles
✅ Headers de seguridad configurados

⚠️ **RECOMENDACIÓN:** Usa SSH Tunnel para acceso. No exponer phpMyAdmin directamente a internet.

---

## 🚀 ACCEDER AHORA

### FORMA SEGURA (RECOMENDADA):

```bash
# Terminal 1 (Mantén abierto):
ssh -i C:/Users/rodri/OneDrive/Documentos/aws/offside.pem -L 8080:localhost:8080 ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com

# Terminal 2:
# Abre navegador en: http://localhost:8080
```

Entrarás automáticamente sin necesidad de login (ya tiene credenciales almacenadas).

---

## 🔧 COMANDOS ÚTILES

```bash
# Ver si phpMyAdmin está respondiendo
curl -s http://localhost:8080/ | grep phpMyAdmin

# Ver versión instalada
ls /usr/share/phpmyadmin/version

# Revisar logs de Nginx phpMyAdmin
sudo tail -50 /var/log/nginx/error.log | grep phpmyadmin

# Recargar configuración
sudo systemctl reload nginx

# Ver puerto 8080 en uso
sudo ss -tulpn | grep 8080
```

---

## 📝 NOTAS

1. **Seguridad:** phpMyAdmin tiene credenciales almacenadas en config.inc.php. El archivo está protegido (chmod 600).

2. **Acceso desde internet:** Si necesitas acceso público, debes:
   - Proteger con contraseña HTTP básica
   - Cambiar puerto a algo no obvio
   - Usar HTTPS (recomendado)
   - Limitar por IP en firewall

3. **Mantenimiento:** Revisa regularmente logs:
   ```bash
   sudo tail -f /var/log/nginx/access.log
   ```

4. **Respaldo:** Configura backups automáticos de tu BDD:
   ```bash
   mysqldump -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com \
   -u admin -p'offside.2025' offsideclub > backup.sql
   ```
