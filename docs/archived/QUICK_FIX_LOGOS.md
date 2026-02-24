# 🔗 INSTRUCCIONES INMEDIATAS: Reparar Enlaces Rotos de Logos en Producción

## 📋 Resumen del Problema

```
❌ ROTO:  /var/www/html/offside-app/storage/app/public/logos/Arsenal.png
         (Los logos están aquí pero las URLs no los encuentran)

✅ SOLUCIÓN: Crear symlink
           public/storage → ../storage/app/public
```

## 🚀 OPCIÓN RÁPIDA (1 Comando)

Ejecuta **una sola línea** en tu terminal local:

```bash
ssh -i "C:/Users/rodri/OneDrive/Documentos/aws/offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com "cd /var/www/html/offside-app && sudo rm -f public/storage && sudo ln -s ../storage/app/public public/storage && echo '✅ Symlink creado'"
```

## 🔧 OPCIÓN MANUAL (Paso a paso en el servidor)

### Paso 1: Conectar al servidor
```bash
ssh -i "C:/Users/rodri/OneDrive/Documentos/aws/offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com
```

### Paso 2: Navegar a la aplicación
```bash
cd /var/www/html/offside-app
```

### Paso 3: Crear el symlink
```bash
# Remover symlink roto o directorio si existe
sudo rm -f public/storage

# Crear symlink nuevo
sudo ln -s ../storage/app/public public/storage

# Verificar que funciona
if [ -L public/storage ]; then
    echo "✅ Symlink creado exitosamente"
else
    echo "❌ Error al crear symlink"
fi
```

### Paso 4: Limpiar caché (opcional pero recomendado)
```bash
sudo -u www-data php artisan cache:clear
```

## ✅ Cómo Verificar que Funciona

### Verificación 1: Comando local
```bash
ssh -i "C:/Users/rodri/OneDrive/Documentos/aws/offside.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com "ls -la /var/www/html/offside-app/public/storage/logos/ | head -5"
```

**Debería mostrar:**
```
total X
lrwxrwxrwx 1 root root ...   ../storage/app/public -> public/storage
drwxr-xr-x 1 ...                   Arsenal.png
drwxr-xr-x 1 ...                   Chelsea.png
...
```

### Verificación 2: En el navegador
Abre en tu navegador:
```
https://tu-dominio-produccion.com/storage/logos/Arsenal.png
```

**Debería mostrar:** La imagen del escudo de Arsenal

### Verificación 3: En la API
```bash
curl https://tu-dominio-produccion.com/api/matches/calendar | grep "crest_url" | head -3
```

**Debería mostrar:**
```json
"crest_url": "/storage/logos/Arsenal.png"
```

## 📊 Qué Pasará

### Antes (AHORA ❌)
```
Usuario abre /api/matches/calendar
↓
API retorna: "crest_url": "/storage/logos/Arsenal.png"
↓
Navegador intenta: https://dominio.com/storage/logos/Arsenal.png
↓
❌ 404 - NOT FOUND (el archivo está en otro lugar)
```

### Después (CUANDO SE ARREGLE ✅)
```
Usuario abre /api/matches/calendar
↓
API retorna: "crest_url": "/storage/logos/Arsenal.png"
↓
Navegador intenta: https://dominio.com/storage/logos/Arsenal.png
↓
Apache/Nginx sigue el symlink: public/storage → storage/app/public
↓
✅ 200 OK - Se muestra la imagen correctamente
```

## 🎯 Próximos Despliegues

**A partir de ahora**, cada vez que ejecutes:
```bash
bash deploy.sh
```

Se creará el symlink automáticamente, por lo que no habrá que hacerlo manualmente.

---

## 📞 Si aún no funciona después de esto

1. **Verifica permisos:**
   ```bash
   ssh -i "..." ubuntu@ec2-... "stat /var/www/html/offside-app/storage/app/public/"
   ```
   Debería mostrar permisos `755` o similar.

2. **Verifica que los logos existen:**
   ```bash
   ssh -i "..." ubuntu@ec2-... "ls -1 /var/www/html/offside-app/storage/app/public/logos/ | wc -l"
   ```
   Debería mostrar un número > 100

3. **Recarga la caché del navegador:**
   ```
   Ctrl + Shift + R (en Windows/Linux)
   Cmd + Shift + R (en Mac)
   ```

4. **Contacta al soporte si el problema persiste**
