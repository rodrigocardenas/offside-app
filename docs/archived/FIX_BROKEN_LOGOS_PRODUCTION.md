# 🔗 Solución: Enlaces Rotos de Logos en Producción

## 🔴 Problema
Los logos en producción tienen enlaces rotos porque están guardados en:
```
/var/www/html/offside-app/storage/app/public/logos/
```

Pero la aplicación está buscando:
```
/var/www/html/offside-app/public/storage/logos/
```

## ✅ Solución: Crear Symlink

Laravel requiere un symlink de `public/storage` → `storage/app/public` para servir los archivos.

### Opción 1: Ejecutar comando de Laravel (recomendado)

```bash
ssh -i "tu-clave.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com << 'SYMLINK'
cd /var/www/html/offside-app
sudo php artisan storage:link --force
echo "✅ Symlink creado"
SYMLINK
```

### Opción 2: Crear symlink manualmente

```bash
ssh -i "tu-clave.pem" ubuntu@ec2-54-172-59-146.compute-1.amazonaws.com << 'SYMLINK'
cd /var/www/html/offside-app

# Remover symlink roto si existe
sudo rm -f public/storage

# Crear symlink nuevo
sudo ln -s ../storage/app/public public/storage

# Verificar
if [ -L public/storage ]; then
    echo "✅ Symlink creado exitosamente"
    ls -la public/storage | head -10
else
    echo "❌ Error al crear symlink"
fi
SYMLINK
```

### Opción 3: Ejecutar directamente en el servidor

Conéctate al servidor y ejecuta:

```bash
cd /var/www/html/offside-app
sudo rm -f public/storage
sudo ln -s ../storage/app/public public/storage
php artisan cache:clear
```

## 🔍 Verificación

Para verificar que funciona:

1. **Desde el servidor:**
   ```bash
   ls -la /var/www/html/offside-app/public/storage
   # Debería mostrar archivos del directorio storage/app/public
   ```

2. **Desde el navegador:**
   ```
   https://tu-dominio.com/storage/logos/Arsenal.png
   # Debería mostrar la imagen correctamente
   ```

3. **En la base de datos:**
   ```bash
   sqlite3 /var/www/html/offside-app/database/offside.db
   SELECT COUNT(*) FROM teams WHERE crest_url IS NOT NULL;
   # Debería mostrar 212
   ```

## 📝 Detalles Técnicos

### Estructura de directorios correcta:
```
/var/www/html/offside-app/
├── public/
│   ├── index.php
│   └── storage → ../storage/app/public (SYMLINK)
├── storage/
│   └── app/
│       └── public/
│           └── logos/
│               ├── Arsenal.png
│               ├── Chelsea.png
│               └── ...
└── ...
```

### Cómo funciona:
1. Archivo está en: `storage/app/public/logos/Arsenal.png`
2. Symlink mapea: `public/storage` → `storage/app/public`
3. URL pública: `/storage/logos/Arsenal.png`
4. Ruta real: `public/storage/logos/Arsenal.png` → `storage/app/public/logos/Arsenal.png`

## 🔧 Integración con Deploy

He actualizado `deploy.sh` para crear el symlink automáticamente en cada despliegue.

El siguiente comando de deploy creará el symlink automáticamente:
```bash
bash deploy.sh
```

## ⚠️ Notas importantes

- **Permisos:** El servidor web (www-data) necesita poder leer los archivos en `storage/`
- **Directorios:** Verifica que exista `/var/www/html/offside-app/storage/app/public/logos/`
- **Symlink:** Si el symlink ya existe pero está roto, se eliminará y se recreará

## 📞 Si aún no funciona

Si después de crear el symlink los logos aún no se ven:

1. Verifica permisos:
   ```bash
   ls -la /var/www/html/offside-app/storage/app/public/logos/
   # Debería mostrar los archivos PNG
   ```

2. Verifica configuración de Nginx/Apache:
   ```bash
   grep -A 10 "location /storage" /etc/nginx/sites-enabled/offside-app
   ```

3. Limpia cache:
   ```bash
   cd /var/www/html/offside-app
   php artisan cache:clear
   php artisan config:clear
   ```

4. Recarga la página en el navegador (Ctrl+Shift+R para forzar recarga)
