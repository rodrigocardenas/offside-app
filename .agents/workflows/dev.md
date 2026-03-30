---
description: Iniciar el entorno de desarrollo local y watchers (Vite)
---

Arranca los servicios necesarios para el desarrollo local en Capacitor. 

// Observación: Dado que `npm run dev` levantará un proceso en Watcher, no se usará `// turbo` para que no se quede atascada la terminal si lo lanzo en la misma.

1. Correr el servidor Watcher de assets.
`npm run dev`

2. Levantar la API local 
`php artisan serve`

// turbo
3. Sincronizar cambios a Capacitor
`npx cap sync`
