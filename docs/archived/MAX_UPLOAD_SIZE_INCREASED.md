# 📤 MAX UPLOAD SIZE - AUMENTADO A 100MB

**Fecha:** Feb 7, 2026  
**Cambio:** Aumentar límite de upload de 4MB a 100MB  
**Status:** ✅ APLICADO  

---

## 🔧 Cambios Realizados

### 1. ProfileUpdateRequest.php ✅
```php
// ANTES
'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:4096'],

// DESPUÉS (max en KB, 102400 = 100MB)
'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:102400'],
```

**Archivo:** [app/Http/Requests/ProfileUpdateRequest.php](app/Http/Requests/ProfileUpdateRequest.php#L30)

### 2. PHP.ini en Producción ✅
```bash
# ANTES
upload_max_filesize = 2M
post_max_size = 8M

# DESPUÉS
upload_max_filesize = 100M
post_max_size = 100M
```

**Archivo:** `/etc/php/8.3/fpm/php.ini`  
**Ubicación en Servidor:** ec2-52-3-65-135

### 3. PHP-FPM Reiniciado ✅
```bash
sudo systemctl restart php8.3-fpm
```

---

## 📊 Detalles de los Cambios

| Parámetro | Antes | Después | Unidad |
|-----------|-------|---------|--------|
| Laravel max | 4096 | 102400 | KB |
| Laravel max | 4 | 100 | MB |
| PHP upload_max_filesize | 2M | 100M | MB |
| PHP post_max_size | 8M | 100M | MB |

---

## ✅ Validaciones

✅ ProfileUpdateRequest.php actualizado en local  
✅ ProfileUpdateRequest.php deployado a producción  
✅ PHP.ini actualizado a 100M en ambas directivas  
✅ PHP-FPM reiniciado (cambios aplicados)  
✅ Cache de Laravel limpiado  

---

## 🚀 Próximas Acciones

1. Testear upload de avatar con archivo > 4MB
2. Verificar que se acepta correctamente
3. Monitorear uso de disk en storage/

---

## 📝 Nginx Configuration (Opcional)

Si el cliente devuelve error 413 (Entity Too Large), también revisar:

```nginx
# /etc/nginx/nginx.conf
client_max_body_size 100m;
```

---

**Status:** ✅ COMPLETADO  
**Próximo paso:** Testear upload de archivo grande desde la app
