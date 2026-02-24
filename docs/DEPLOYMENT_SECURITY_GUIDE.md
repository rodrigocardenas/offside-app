# 🚀 Guía de Despliegue Seguro - Offside Club

## Descripción General

Este documento describe cómo desplegar la aplicación de forma segura sin exponer credenciales en el código.

---

## 📋 Requisitos Previos

### Local (Tu computadora)
- Git configurado
- SSH key generada (`offside-new.pem`)
- Acceso a la máquina EC2
- npm/nodejs instalado
- PHP y Composer para testing

### En el Servidor (EC2)
- Ubuntu 24.04 LTS
- PHP-FPM, Nginx, MySQL, Redis
- Git, Composer, npm
- Usuario `www-data` con permisos configurados

---

## 🔐 Configuración de Seguridad

### PASO 1: Configurar Variables de Entorno Locales

**En tu máquina (NO en Git):**

1. Copiar el archivo de ejemplo:
   ```bash
   cp .env.deploy.example .env.deploy
   ```

2. Editar con tus valores:
   ```bash
   nano .env.deploy
   ```

3. Configurar la ruta SSH key:
   ```bash
   export SSH_KEY_PATH="/ruta/completa/a/offside-new.pem"
   export DEPLOY_SERVER="ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com"
   export CLEAN_DUPLICATES="false"  # true solo si necesitas limpiar duplicados
   ```

4. **Guardar con permisos seguros** (solo tú puedes leer):
   ```bash
   chmod 600 .env.deploy
   ```

5. **NUNCA** hacer commit:
   ```bash
   # Ya está en .gitignore, pero verificar:
   grep ".env.deploy" .gitignore
   ```

### PASO 2: Configurar Variables de Entorno en Bash

**Opción A: Cargarlo cada vez que haces deploy**
```bash
source .env.deploy
bash scripts/deploy.sh
```

**Opción B: Agregarlo a tu perfil de shell (permanente)**
```bash
# En ~/.bashrc o ~/.zshrc
if [ -f ~/path/to/offsideclub/.env.deploy ]; then
    source ~/path/to/offsideclub/.env.deploy
fi
```

**Opción C: Agregarlo al PATH de SSH (recomendado)**
```bash
# En ~/.bashrc
export SSH_KEY_PATH="$HOME/OneDrive/Documentos/aws/offside-new.pem"
export DEPLOY_SERVER="ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com"
```

### PASO 3: Inicializar el Servidor (UNA SOLA VEZ)

**En el servidor:**

1. Conectar:
   ```bash
   ssh -i ~/.ssh/offside-new.pem ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com
   ```

2. Descargar script de inicialización:
   ```bash
   # Opción A: Desde URL (si tienes acceso)
   curl -O https://raw.githubusercontent.com/tuorg/offsideclub/main/scripts/server-init.sh
   
   # Opción B: Copiar desde local
   scp -i ~/.ssh/offside-new.pem scripts/server-init.sh ubuntu@ec2-...:~/
   ```

3. Ejecutar como root:
   ```bash
   sudo bash ~/server-init.sh
   ```

   Output esperado:
   ```
   ✅ SERVIDOR INICIALIZADO CORRECTAMENTE
   ✓ Sudoers configurado para www-data
   ✓ Permisos de directorios ajustados
   ✓ Git configurado para ignorar cambios de permisos
   ✓ Monitoreo automático de seguridad (cron)
   ```

---

## 📤 Desplegar la Aplicación

### PASO 1: Preparar cambios locales

```bash
# Verificar rama actual
git branch

# Crear rama feature
git checkout -b feature/mi-cambio

# Hacer cambios, commit y push
git add -A
git commit -m "feat: Nuevo cambio importante"
git push origin feature/mi-cambio

# Abrir Pull Request en GitHub/GitLab
```

### PASO 2: Fusionar a main (main branch)

```bash
# Cambiar a main
git checkout main

# Asegurarse de estar actualizado
git pull origin main

# Fusionar feature
git merge feature/mi-cambio

# Push a main
git push origin main
```

### PASO 3: Ejecutar Deploy

```bash
# Cargar variables de entorno
source .env.deploy

# Ejecutar deploy (debe estar en rama main)
bash scripts/deploy.sh
```

**Output esperado:**
```
🔍 Validando entorno de despliegue...
✓ Rama validada. Iniciando despliegue...
📦 Compilando assets...
✓ Build completado
🔄 Desplegando en servidor remoto...
🔧 Ajustando permisos previos...
🔄 Limpiando estado de git...
📦 Verificando dependencias de Composer...
🧹 Limpiando y extrayendo...
🔐 Ejecutando comandos de seguridad...
📊 INFORMACIÓN DE DESPLIEGUE:
   Servidor:     ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com
   Rama:         main
   Commit:       a1b2c3d - feat: Nuevo cambio
   Usuario:      rodri
   Timestamp:    2025-02-20 15:30:45

✅ DESPLIEGUE COMPLETADO
```

---

## 🔐 Permisos Sudoers

### ¿Qué comandos necesita `www-data` con sudo?

El script de deploy necesita ejecutar:
- `mkdir`, `chown`, `chmod` - Para permisos
- `rm`, `tar`, `ln` - Para archivos
- `git` - Para actualizaciones
- `composer` - Para dependencias PHP
- `php artisan` - Para comandos de artisan
- `supervisorctl` - Para reiniciar servicios

### Verificar Sudoers en el Servidor

```bash
# En el servidor, verificar que está correctamente configurado
sudo visudo -c

# Resultado esperado:
# /etc/sudoers.d/www-data-deploy: syntax OK

# Ver qué puede ejecutar www-data
sudo -u www-data -l
```

---

## 📊 Monitoreo Post-Deploy

### Ver logs en el servidor

```bash
# Logs de aplicación
ssh -i $SSH_KEY_PATH $DEPLOY_SERVER 'tail -f /var/www/html/storage/logs/laravel.log'

# Logs de seguridad
ssh -i $SSH_KEY_PATH $DEPLOY_SERVER 'tail -f /var/www/html/storage/logs/security.log'

# Logs de Nginx
ssh -i $SSH_KEY_PATH $DEPLOY_SERVER 'tail -f /var/log/nginx/error.log'
```

### Verificar que la aplicación está corriendo

```bash
# En el servidor
sudo systemctl status php-fpm
sudo systemctl status nginx
sudo systemctl status redis-server

# O desde local
ssh -i $SSH_KEY_PATH $DEPLOY_SERVER 'curl http://localhost'
```

---

## ⚙️ Opciones Avanzadas

### Limpiar Usuarios Duplicados en Deploy

Si tiene usuarios duplicados del incidente:

```bash
# Establecer variable
export CLEAN_DUPLICATES="true"

# Ejecutar deploy (limpiará duplicados)
bash scripts/deploy.sh

# Volver a false después
export CLEAN_DUPLICATES="false"
```

### Deploy sin Compilar Assets

Si solo cambios de backend:

```bash
# Editar deploy.sh y comentar:
# npm run build
# tar -czf build.tar.gz public/build
```

### Deploy en Staging Primer

```bash
export DEPLOY_SERVER="ubuntu@ec2-staging.compute-1.amazonaws.com"
bash scripts/deploy.sh

# Luego en producción
export DEPLOY_SERVER="ubuntu@ec2-100-30-41-157.compute-1.amazonaws.com"
bash scripts/deploy.sh
```

---

## 🚨 Troubleshooting

### Error: "SSH_KEY_PATH not found"

```bash
# Solución: Configurar variable de entorno
export SSH_KEY_PATH="/ruta/correcta/offside-new.pem"

# O verificar que archivo existe
ls -la ~/OneDrive/Documentos/aws/offside-new.pem
```

### Error: "Permission denied (publickey)"

```bash
# Verificar permisos del .pem
chmod 600 ~/.ssh/offside-new.pem

# Verificar que es la clave correcta
ssh-keygen -y -f ~/.ssh/offside-new.pem | ssh ubuntu@ec2-... "cat >> ~/.ssh/authorized_keys"
```

### Error: "Cambios sin commitear"

```bash
# Hacer commit de cambios
git add -A
git commit -m "WIP: Cambios en desarrollo"

# O revertir
git checkout .
```

### Error: "Sudoers syntax error"

```bash
# En el servidor, reconfigurar:
sudo visudo -c -f /etc/sudoers.d/www-data-deploy

# Si hay error, ejecutar script de inicialización de nuevo:
sudo bash ~/server-init.sh
```

---

## 📋 Checklist de Seguridad

- [ ] SSH key está en `~/.ssh/` con permisos 600
- [ ] `.env.deploy` NO está en Git (verificar .gitignore)
- [ ] `.env.deploy` local tiene permisos 600
- [ ] `SSH_KEY_PATH` está configurado como variable de entorno
- [ ] Servidor iniciado con `scripts/server-init.sh`
- [ ] Sudoers verificado: `sudo visudo -c`
- [ ] Primer deploy exitoso
- [ ] Logs de seguridad visibles
- [ ] Monitoreo automático activo (cron)

---

## 🔄 Flujo Típico de Despliegue

```
1. Trabajo local
   ├─ git checkout -b feature/mi-cambio
   ├─ Editar archivos
   ├─ git add / git commit
   └─ git push origin feature/mi-cambio

2. Code Review (Pull Request)
   ├─ CI/CD tests
   ├─ Code review
   └─ Approve

3. Merge a main
   ├─ git checkout main
   ├─ git pull origin main
   ├─ git merge feature/mi-cambio
   └─ git push origin main

4. Deploy automático (CI/CD)
   O desplegar manualmente:
   ├─ source .env.deploy
   ├─ bash scripts/deploy.sh
   └─ Verificar logs

5. Post-deploy
   ├─ Verificar aplicación en vivo
   ├─ Revisar logs de seguridad
   └─ Alertar si hay anomalías
```

---

## 🔗 Recursos Relacionados

- [SUDOERS_DEPLOY_CONFIG.md](SUDOERS_DEPLOY_CONFIG.md) - Configuración de permisos
- [SECURITY_IMPLEMENTATION_SUMMARY.txt](../SECURITY_IMPLEMENTATION_SUMMARY.txt) - Medidas de seguridad
- [MEDIDAS_SEGURIDAD_IMPLEMENTADAS.md](MEDIDAS_SEGURIDAD_IMPLEMENTADAS.md) - Documentación de seguridad

---

## 📞 Contacto

**Equipo de DevOps:** devops@offside.club  
**Reportar issues:** GitHub Issues  
**Emergencias:** Slack #deployments

---

**Última actualización:** 2025-02-20  
**Versión:** 1.0  
**Status:** Production-Ready ✅
