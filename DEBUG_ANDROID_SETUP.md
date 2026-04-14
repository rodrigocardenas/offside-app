# 🔧 Guía: Depuración Remota en Android con Chrome DevTools

## Paso 1: Habilitar USB Debugging en Android

1. **En tu teléfono:**
   - Abre **Configuración** → **Acerca de teléfono**
   - Toca **Número de compilación** 7 veces seguidas
   - Aparecerá: "Ya eres desarrollador"
   - Vuelve a **Configuración** → **Opciones de desarrollador** (nueva opción)
   - Habilita **Depuración USB**
   - Habilita **Depuración de aplicaciones web** (si existe)

2. **Conecta el teléfono a la computadora por USB**

3. **En el teléfono:**
   - Se pedirá confirmación: "¿Permitir depuración USB?"
   - Marca **Siempre permitir desde este dispositivo**
   - Toca **Permitir**

---

## Paso 2: Configurar Chrome para Inspección Remota

### En Windows/Notebook:

1. **Abre Chrome en tu notebook**

2. **Escribe en la clave de dirección:**
   ```
   chrome://inspect
   ```

3. **Verifica que esté habilitado:**
   - En la esquina superior izquierda, debe haber un checkbox con **"Discover USB devices"** ✓

4. **Verás tu dispositivo:**
   - Ejemplo: `Galaxy S21 (or your device name)`
   - Debajo: `Offside Club` o tu página web/app

5. **Haz click en "inspect":**
   - Se abrirá Chrome DevTools remoto (igual que F12 normal)

---

## Paso 3: Usar DevTools Remoto

### Pestaña Console:
- Verás todos los `console.log()` en tiempo real
- Errores de red (fetch fallidos)
- Errores de JavaScript

### Pestaña Network:
- Mira los headers de cada request
- Verifica que se envíe `credentials` en cookies
- Revisa response status (200, 401, 403, etc)

### Pestaña Application:
- **Cookies**: Busca `LARAVEL_SESSION` o `XSRF-TOKEN`
- **LocalStorage**: Verifica `prematch_*_lastEventId`

---

## Paso 4: Testear la Sincronización

### Mientras observas DevTools:

1. **En la pestana Console:**
   - Crea una propuesta
   - Mira si hay errores en rojo
   - Verifica que se muestre: `fetch POST /api/pre-matches/...`

2. **En la pestaña Network:**
   - Filtra por `XHR` (XMLHttpRequest)
   - Haz clic en el request POST
   - Mira:
     - **Headers** → Request Headers
     - **Response** → ¿Qué devuelve? ¿201? ¿401? ¿500?

3. **En Application:**
   - Verifica que exista la cookie `LARAVEL_SESSION`
   - Si no existe → Problema de autenticación

---

## ¿Qué Buscar?

### Si falla la creación de propuesta:
- ❌ **Error 401**: Sin autenticación (NO se envían cookies)
- ❌ **Error 403**: Sin permiso (usuario no autenticado)
- ❌ **Error 500**: Error del servidor (revisar logs)
- ✅ **201/200**: OK (debería funcionar)

### Si no se actualizan en tiempo real:
- Mira Console
- Busca errores del polling (GET /api/pre-matches/.../events-poll)
- Verifica que la cookie esté presente en cada request

---

## Troubleshooting

### "No veo el dispositivo en chrome://inspect"

1. **Desconecta y reconecta el USB**
2. **Confirma nuevamente en el teléfono:**
   - "Siempre permitir" + "Permitir"
3. **Recarga chrome://inspect**

### "La consola está vacía"

- La app no está corriendo
- Recarga la app en Android
- Vuelve a la pestaña de inspect

### "Veo cookies pero siguen sin funcionar"

- Revisa que `X-CSRF-TOKEN` se envíe correcto en el header
- Verifica que `credentials: 'include'` esté en el fetch
- Mira el Response de la request fallida

