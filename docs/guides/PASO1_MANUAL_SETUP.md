# PASO 1: Configuración Manual del Entorno

## ✅ Archivos Configurados Automáticamente

1. ✅ `resources/css/components.css` - Creado con estilos personalizados
2. ✅ `tailwind.config.js` - Actualizado con colores y sombras custom

## 📂 Directorios a Crear Manualmente

Debido a limitaciones técnicas, necesitas crear los siguientes directorios manualmente.

### Opción 1: Usando el Explorador de Windows
1. Abre el Explorador de Windows
2. Navega a `c:\laragon\www\offsideclub`
3. Crea las siguientes carpetas:

```
resources/views/components/layout/
resources/views/components/predictions/
resources/views/components/common/
resources/views/components/matches/
resources/views/components/chat/
public/js/groups/
public/js/predictions/
public/js/chat/
public/js/rankings/
public/js/common/
```

### Opción 2: Usando CMD (Símbolo del sistema)
1. Abre CMD (Win + R, escribe `cmd`, Enter)
2. Navega al proyecto: `cd c:\laragon\www\offsideclub`
3. Ejecuta: `create-dirs.bat`

O copia y pega estos comandos uno por uno:

```batch
mkdir resources\views\components\layout
mkdir resources\views\components\predictions
mkdir resources\views\components\common
mkdir resources\views\components\matches
mkdir resources\views\components\chat
mkdir public\js\groups
mkdir public\js\predictions
mkdir public\js\chat
mkdir public\js\rankings
mkdir public\js\common
```

### Opción 3: Usando Git Bash (si lo tienes instalado)
```bash
mkdir -p resources/views/components/{layout,predictions,common,matches,chat}
mkdir -p public/js/{groups,predictions,chat,rankings,common}
```

### Opción 4: Usando Node.js
1. Abre CMD en el directorio del proyecto
2. Ejecuta: `node setup-dirs.js`

## 📝 Próximos Pasos

Una vez creados los directorios, debes:

1. ✅ Importar `components.css` en tu archivo principal CSS
2. ✅ Compilar los assets de Tailwind
3. ✅ Verificar que la estructura esté lista

### Importar components.css

Abre `resources/css/app.css` y agrega al final:

```css
@import 'components.css';
```

### Compilar Assets

Ejecuta en la terminal:

```bash
npm run build
```

O para desarrollo:

```bash
npm run dev
```

## ✅ Verificación

Para verificar que todo esté correctamente configurado:

1. Revisa que existan todos los directorios listados arriba
2. Verifica que `tailwind.config.js` tenga los nuevos colores
3. Confirma que `resources/css/components.css` exista
4. Compila los assets sin errores

## 🎯 Estado Actual del Paso 1

- [x] Archivos de configuración creados
- [x] `tailwind.config.js` actualizado
- [x] `components.css` creado
- [ ] Directorios creados (manual)
- [ ] CSS importado en app.css
- [ ] Assets compilados

## 📞 Siguiente Paso

Una vez completado este paso, estaremos listos para el **PASO 2: Componentes de Layout Comunes**.

---

**Nota:** Los archivos auxiliares `create-dirs.bat` y `setup-dirs.js` ya fueron creados en la raíz del proyecto para facilitar la creación de directorios.
