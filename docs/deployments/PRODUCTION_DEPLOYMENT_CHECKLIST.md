# 🚀 Checklist: Pasar a Producción (11 Feb 2026)

## 📱 PARTE 1: Publicar App en Play Store

### ✅ App Bundle está listo
```
✅ android/app/build/outputs/bundle/release/app-release.aab (4.5 MB)
✅ android/app/build/outputs/mapping/release/mapping.txt (15 MB)
```

### Pasos para publicar:
1. **Abre Play Console:** https://play.google.com/console
2. **Selecciona:** Offside Club
3. **Ve a:** Producción → Crear nueva versión
4. **Sube:** app-release.aab
5. **Sube Mapping:** mapping.txt en "Archivos de símbolos"
6. **Responde:** "¿Tu app usa ID de publicidad?" → **SÍ**
7. **Revisa:** Compatibilidad (warning de 687 dispositivos es normal)
8. **Publica:** Click en "Publicar"

### Información de versión:
- **Version Code:** 9 → versionCode incrementado ✅
- **Version Name:** 1.081 ✅
- **API Level:** 35 ✅
- **Minificación:** R8 habilitada ✅

---

## 🖥️ PARTE 2: Deploy del Backend en AWS

### Verificación pre-deploy:

```bash
# 1. Validar que estás en rama main
git branch
# Debes estar en: * main

# 2. Verificar que no hay cambios sin commitear
git status
# Output: "On branch main. working tree clean"

# 3. Ver último commit
git log -1 --oneline
```

### Ejecutar deploy:
```bash
bash scripts/deploy.sh
```

**Esto hará:**
- ✅ Compilar frontend (npm run build)
- ✅ Comprimir assets
- ✅ Subir a servidor AWS vía SSH
- ✅ Extraer files
- ✅ Ejecutar migraciones
- ✅ Limpiar caché
- ✅ Optimizar
- ✅ Notificar despliegue

---

## 🔄 Orden recomendado:

### Opción A: Solo Play Store (rápido)
1. Sube App Bundle a Play Store
2. Publica
3. Listo - Los usuarios reciben la app

### Opción B: Solo Backend (rápido)
1. Ejecuta: `bash scripts/deploy.sh`
2. Espera a que termine
3. Listo - El servidor se actualiza

### Opción C: Ambas (RECOMENDADO)
1. **Primero:** Deploy del backend
2. **Luego:** Publicar app en Play Store
3. **Razón:** El backend está listo cuando los usuarios descarguen la app nueva

---

## ⚠️ Pre-requisitos para deploy backend:

- [ ] SSH Key configurada: `~/OneDrive/Documentos/aws/offside-new.pem`
- [ ] Acceso al servidor AWS: `ec2-100-30-41-157.compute-1.amazonaws.com`
- [ ] Todos los cambios en Git estan commitados
- [ ] Estás en rama `main`

---

## 📊 Estado actual (11 Feb 2026):

| Componente | Estado | Versión |
|------------|--------|---------|
| **App Android** | ✅ Compilada | 1.081 (versionCode 9) |
| **App Bundle** | ✅ Listo | 4.5 MB |
| **Mapping** | ✅ Listo | 15 MB |
| **Backend** | ✅ Listo | Último commit en main |
| **Firebase Messaging** | ✅ Configurado | Endpoint público |

---

## 🎯 ¿Qué quieres hacer?

**Responde:**
1. ¿Publicar app en Play Store ahora?
2. ¿Hacer deploy del backend ahora?
3. ¿Ambas?

Si respondes, te ayudaré a ejecutar los pasos exactos.
