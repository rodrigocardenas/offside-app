# ⚠️ ESTADO ACTUAL: Servidor de Producción Inaccesible

## 🔴 Problema Inmediato

```
❌ Servidor: ec2-54-172-59-146.compute-1.amazonaws.com
   Estado: NO RESPONDE A SSH (timeout)
   
✅ Servidor: ec2-52-3-65-135.compute-1.amazonaws.com (landing)
   Estado: OPERATIVO
```

## 📋 Diagnóstico

El servidor **offside-app** está inaccesible. Esto puede ser por:

1. **Instancia detenida/apagada**
   - Verifica en AWS Console: https://console.aws.amazon.com/ec2/v2/home?region=us-east-1#Instances:

2. **Security Group bloqueando SSH**
   - El puerto 22 podría estar cerrado
   - Verifica reglas de entrada en el Security Group

3. **Servidor caído/no responde**
   - La instancia corre pero el servicio SSH no responde
   - Requiere reinicio de la instancia

4. **Problema de red**
   - Problema de conectividad entre tu máquina y AWS

## ✅ Próximos Pasos

### Opción 1: Usar AWS Console (Recomendado)

1. **Accede a AWS Console**
   ```
   https://console.aws.amazon.com/ec2/v2/home?region=us-east-1#Instances:
   ```

2. **Selecciona la instancia:** `ec2-54-172-59-146`

3. **Si está detenida:**
   - Click derecho → Instance State → Start
   - Espera 30-60 segundos

4. **Si está ejecutándose:**
   - Click derecho → Reboot Instance
   - Espera 30-60 segundos

### Opción 2: Usar AWS CLI (Si lo tienes instalado)

```bash
# Listar instancias
aws ec2 describe-instances --region us-east-1

# Reiniciar la instancia
aws ec2 reboot-instances --instance-ids i-xxxxxxxx --region us-east-1

# Esperar a que esté operativa
aws ec2 wait instance-running --instance-ids i-xxxxxxxx --region us-east-1
```

### Opción 3: Cuando el servidor esté de nuevo activo

Una vez que el servidor responda a SSH, ejecuta:

```bash
bash recover-logos-when-up.sh
```

O manualmente:

```bash
ssh -i "C:/Users/rodri/OneDrive/Documentos/aws/offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com << 'SYMLINK'
cd /var/www/html/offside-app
sudo rm -f public/storage
sudo ln -s ../storage/app/public public/storage
echo "✅ Symlink creado"
SYMLINK
```

## 📊 Resumen de Cambios Realizados

Aunque el servidor esté inaccesible ahora, he preparado todo para cuando vuelva a estar operativo:

### ✅ Completado en el repositorio:

1. **Comando PopulateMissingCrests**
   - Vincula logos locales a equipos sin crest_url
   - 212/371 equipos (57.14%) tienen logos asignados

2. **Deploy.sh actualizado**
   - Ahora crea el symlink automáticamente
   - No necesitarás hacerlo manualmente en futuros despliegues

3. **Documentación completa**
   - `QUICK_FIX_LOGOS.md` - Instrucciones rápidas
   - `FIX_BROKEN_LOGOS_PRODUCTION.md` - Documentación técnica
   - `recover-logos-when-up.sh` - Script de recuperación

4. **Comando Artisan**
   - `php artisan storage:link` - Crear symlink via Laravel
   - `php artisan storage:ensure-symlink` - Verificar y crear si es necesario

## 🎯 Lo Que Necesita Hacerse (Cuando el servidor esté activo)

1. **Crear el symlink** (30 segundos)
   ```bash
   ssh ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com "cd /var/www/html/offside-app && sudo rm -f public/storage && sudo ln -s ../storage/app/public public/storage"
   ```

2. **Limpiar caché** (10 segundos)
   ```bash
   ssh ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com "cd /var/www/html/offside-app && php artisan cache:clear"
   ```

3. **Verificar** (10 segundos)
   ```bash
   ssh ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com "ls -la /var/www/html/offside-app/public/storage | head -5"
   ```

## 💡 Información Útil

### ID de Instancia
```
Instancia: ec2-54-172-59-146.compute-1.amazonaws.com
Región: us-east-1
```

Para encontrar el ID de instancia en AWS Console:
1. Selecciona la instancia
2. Busca "Instance ID" (algo como `i-0abc123def456`)

### Estado Anterior
Hace poco ejecutaste este comando:
```bash
ssh -i "C:/Users/rodri/OneDrive/Documentos/aws/offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com ...
```
Y recibiste `exit code 255` (conexión rechazada)

## ⏰ Estimación de Tiempo

- **Reiniciar servidor:** 1-2 minutos
- **Crear symlink:** 30 segundos
- **Verificar que funciona:** 1 minuto
- **Total:** ~4 minutos

## 📞 Contacto AWS Support (Si es necesario)

Si el servidor está en estado "stopped" y no puedes reiniciarlo:
- AWS Support puede ayudarte
- Acceso: https://console.aws.amazon.com/support

---

## 🔄 Próximos Despliegues

**BUENA NOTICIA:** A partir de ahora, cada vez que ejecutes:
```bash
bash deploy.sh
```

Se ejecutará automáticamente:
```bash
sudo -u www-data php artisan storage:link --force
```

Así que no tendrás que hacerlo manualmente nunca más. 🎉
