---
description: Cacheado, optimización y script de despliegue para producción
---

Realiza la compilación enfocada a producción de los assets y corre el bash script de deploy.

// turbo
1. Construir e hiper-optimizar vistas y dependencias
`npm run prod`

// turbo
2. Sincronizar en el entorno de Android en producción
`npx cap sync android`

3. Ejecutar el script oficial de Deploy (se requerirá probablemente revisión manual, por eso no es turbo)
`bash scripts/deploy.sh`
