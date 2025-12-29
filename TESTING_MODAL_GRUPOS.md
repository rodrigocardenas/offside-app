# 🧪 Guía de Testing - Modal de Grupos

## Testing Manual

### 1. **Verificar que el modal se abre**
```
✓ Ir a la página principal (dashboard/grupos)
✓ Buscar el "Partido Destacado del Día"
✓ Hacer clic en el partido
✓ El modal debe aparecer con un spinner
```

### 2. **Verificar carga de grupos**
```
✓ Esperar a que el modal cargue
✓ Si hay grupos de la competición:
  - Deben aparecer en una lista
  - Cada grupo muestra nombre y cantidad de miembros
  - Hay botón "Ir" para acceder
```

### 3. **Verificar estado sin grupos**
```
✓ Si no hay grupos de la competición:
  - Aparece mensaje: "No hay grupos en esta competición"
  - Hay botón "Crear Grupo"
  - El botón preestablece la competición del partido
```

### 4. **Verificar cierre de modal**
```
✓ Clic en botón X (esquina superior derecha)
✓ Clic en botón "Cerrar" (pie del modal)
✓ Clic fuera del modal (en el overlay)
✓ El modal debe cerrar en los tres casos
```

### 5. **Verificar indicador visual**
```
✓ El partido destacado debe mostrar:
  - Cursor: pointer
  - Efecto hover: sombra + movimiento hacia arriba
  - Texto: "Haz clic para ver grupos"
```

---

## Testing con Navegador (DevTools)

### Verificar red (Network tab)
```
1. Abrir DevTools (F12)
2. Ir a Network
3. Hacer clic en el partido
4. Buscar solicitud GET a: /api/groups/by-match/{matchId}
5. Response debe ser:
   {
     "groups": [...],
     "competitionId": <id>,
     "competitionName": "<name>"
   }
```

### Verificar console (Console tab)
```
1. Abrir DevTools (F12)
2. Ir a Console
3. Ejecutar: openMatchGroupsModal(1, 'Real Madrid vs Barcelona', 'La Liga')
4. El modal debe aparecer y cargar datos
5. No debe haber errores en console
```

### Verificar estilos
```
1. En DevTools, inspeccionar el modal
2. Verificar que los estilos se aplican correctamente:
   - Background: #2a2a2a
   - Border: #333
   - Accent: #00deb0
   - Texto: #ffffff
```

---

## Testing de Casos Edge

### Caso 1: Partido sin competición asignada
```
✓ La API debe retornar: groups: [], competitionId: null
✓ El modal debe mostrar "Sin competición"
✓ No debe haber errores en console
```

### Caso 2: Error en la API
```
✓ Cambiar URL de la API a una inválida
✓ El modal debe mostrar mensaje de error
✓ Debe permanecer cerrable
```

### Caso 3: Muchos grupos
```
✓ Si hay muchos grupos (>5), el modal debe:
  - Mostrar scroll en el body
  - Mantener header y footer fijos
  - Ser legible en mobile (max-width: 500px)
```

### Caso 4: Nombres largos
```
✓ Si un grupo tiene nombre muy largo:
  - No debe romper el layout
  - El texto debe ser truncado o envuelto
  - Mantener legibilidad
```

---

## Testing Responsivo

### Mobile (375px)
```
✓ El modal debe ocupar ~95% del ancho
✓ Buttons deben ser tocables (min 44px)
✓ Texto debe ser legible (14px+)
✓ Sin horizontal scroll
```

### Tablet (768px)
```
✓ El modal debe verse bien
✓ Máximo ancho: 500px
✓ Espaciado adecuado
```

### Desktop (1920px)
```
✓ El modal centrado en pantalla
✓ Sombra visible
✓ Efecto hover funcionando
```

---

## Checklist de Aceptación

- [ ] El modal se abre al hacer clic
- [ ] Los grupos cargan correctamente
- [ ] Si hay grupos, se muestran en lista
- [ ] Si no hay grupos, aparece botón crear
- [ ] El modal se cierra correctamente (3 formas)
- [ ] No hay errores en console
- [ ] El indicador visual es claro
- [ ] Funciona en mobile
- [ ] La API retorna datos correctos
- [ ] El botón "Crear Grupo" preestablece la competición
- [ ] El botón "Ir" navega al grupo correcto
- [ ] Los estilos están aplicados correctamente
- [ ] Las animaciones son suaves

---

## Troubleshooting

### El modal no se abre
```
✓ Verificar que el elemento tiene onclick
✓ Verificar que no hay errores en console
✓ Verificar que el modal existe en el DOM
```

### Los grupos no cargan
```
✓ Verificar que /api/groups/by-match/{id} existe
✓ Verificar en Network que se realiza la solicitud
✓ Verificar que el match_id es válido
✓ Verificar en database que existen grupos
```

### Los estilos no se aplican
```
✓ Verificar que se compiló con: npm run build
✓ Verificar que no hay cache del navegador (Ctrl+Shift+Del)
✓ Verificar en DevTools que los estilos inline están presentes
```

### El modal se ve roto en mobile
```
✓ Verificar viewport meta tag
✓ Verificar que max-width: 500px está aplicado
✓ Verificar que el padding es suficiente
✓ Probar con Firefox DevTools mobile view
```

---

## Notas

- Compilar después de cada cambio: `npm run build`
- Limpiar cache del navegador si no ve cambios: `Ctrl+Shift+Del`
- En mobile, probar con DevTools en modo responsive
- Los datos del modal vienen de la API, no hardcodeados
- La competición se pasa desde featured-match.blade.php
