---
description: Setup inicial del proyecto (composer, npm, base de datos)
---

Ejecuta el setup inicial del repositorio instalando todas las dependencias y montando la bd.

// turbo
1. Instalar dependencias de servidor:
`composer install`

// turbo
2. Instalar paquetes de Node:
`npm install`

3. Ejecutar las migraciones y seeders de la base de datos (Atención: esto podría alterar los datos existentes en desarrollo):
`php artisan migrate --seed`
