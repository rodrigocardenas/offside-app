# Plan: Boton discreto para compartir prediccion (solo grupo Mundial)

## Objetivo
Agregar un boton pequeno de compartir (solo icono) en la card de prediccion dentro de la vista de grupo, para redirigir a la pantalla existente de resultado de Mundial donde se genera la imagen para RRSS.

## Alcance
- Solo para grupos Mundial (`$group->is_world_cup === true`).
- Solo mostrar cuando el usuario ya voto esa pregunta.
- El boton debe ser discreto (icono) y no romper el layout actual del carrusel.
- El boton debe abrir la ruta existente de resultado:
  - `route('wc.resultado', $matchId)`
  - Definida en `routes/web.php` bajo prefijo `wc`.

## Ruta y dependencias confirmadas
- Ruta resultado Mundial:
  - `GET /wc/{match}/resultado`
  - Nombre: `wc.resultado`
  - Controlador: `WorldCupSocialController@resultado`
- Componente donde se agrega:
  - `resources/views/components/groups/group-match-questions.blade.php`
- Vista destino ya funcional:
  - `resources/views/mundial/resultado.blade.php`

## Criterios funcionales
1. Si el grupo NO es Mundial, no aparece el boton.
2. Si el usuario no ha respondido la pregunta, no aparece el boton.
3. Si la pregunta no tiene `football_match` valido, no aparece el boton (evitar enlaces rotos).
4. Al hacer click, redirige a `wc.resultado` del partido de esa pregunta.
5. El boton se mantiene visible tanto en estado de pregunta activa/respondida como en estado de resultados, siempre bajo las reglas 1-3.

## Propuesta tecnica
### 1) Preparar bandera y URL por card
En cada card de pregunta del componente:
- Calcular:
  - `$isWorldCupGroup = (bool) ($group->is_world_cup ?? false)`
  - `$hasUserAnswered = !is_null($userHasAnswered)`
  - `$matchId = $question->football_match?->id`
  - `$canSharePrediction = $isWorldCupGroup && $hasUserAnswered && !empty($matchId)`
  - `$sharePredictionUrl = $canSharePrediction ? route('wc.resultado', $matchId) : null`

### 2) Render del boton discreto
- Insertar un `a` pequeno con icono (por ejemplo `fa-share-alt`) en zona superior derecha de la card o en el bloque inferior de acciones secundarias.
- Estilo recomendado:
  - tamano compacto
  - fondo semitransparente alineado al tema actual
  - `title="Compartir prediccion"`
  - `aria-label="Compartir prediccion"`

### 3) No afectar UX existente
- Mantener sin cambios el flujo de respuesta/modificacion.
- No modificar logica de puntuacion ni estados de opciones.
- Sin JS adicional (solo link server-side).

## Archivos a tocar (implementacion)
- `resources/views/components/groups/group-match-questions.blade.php`

## Checklist de validacion manual
1. Grupo Mundial + usuario YA voto -> boton visible.
2. Grupo Mundial + usuario NO voto -> boton oculto.
3. Grupo no Mundial + usuario voto -> boton oculto.
4. Click en boton -> abre pantalla `wc.resultado` correcta del partido.
5. Desde `wc.resultado`, flujo de compartir/descargar imagen sigue OK.
6. Verificar responsive en mobile (no superponer con avatar/opciones).

## Riesgos y mitigacion
- Riesgo: `football_match` nulo en alguna pregunta.
  - Mitigacion: condicionar render por `!empty($matchId)`.
- Riesgo: ruido visual en card.
  - Mitigacion: boton icon-only, tamano pequeno, opacidad moderada.
- Riesgo: accesibilidad.
  - Mitigacion: agregar `title` y `aria-label`.

## Definicion de terminado
- Boton implementado y visible solo bajo reglas de negocio.
- Enlace funcional hacia `wc.resultado`.
- Sin regresiones visuales ni errores Blade.
