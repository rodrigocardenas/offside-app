# GitHub Actions Workflows

## Notify on Main Branch Commit

Este workflow envía notificaciones automáticas cuando se hace un commit en la rama `main`.

### 📋 Funcionalidad Básica

Por defecto, el workflow:
- ✅ Se ejecuta automáticamente en cada push a `main`
- ✅ Muestra la información del commit en los logs de GitHub Actions
- ✅ Extrae información del autor, mensaje y SHA del commit

### 🔔 Opciones de Notificación

El archivo `notify-on-main-commit.yml` incluye configuraciones comentadas para varios servicios de notificación. Puedes habilitar cualquiera de ellos:

#### 1. GitHub Notifications (Por defecto - Ya activo)
No requiere configuración adicional. Las notificaciones aparecen en los logs del workflow.

#### 2. Slack
Para habilitar notificaciones en Slack:

1. Crea un webhook en Slack:
   - Ve a https://api.slack.com/apps
   - Crea una nueva app o selecciona una existente
   - Activa "Incoming Webhooks"
   - Crea un nuevo webhook para tu canal

2. Agrega el webhook como secret en GitHub:
   - Ve a Settings > Secrets and variables > Actions
   - Crea un nuevo secret llamado `SLACK_WEBHOOK_URL`
   - Pega la URL del webhook

3. Descomenta la sección de Slack en el workflow

#### 3. Discord
Para habilitar notificaciones en Discord:

1. Crea un webhook en Discord:
   - Ve a la configuración del canal
   - Integraciones > Webhooks > Nuevo Webhook
   - Copia la URL del webhook

2. Agrega el webhook como secret en GitHub:
   - Crea un secret llamado `DISCORD_WEBHOOK`
   - Pega la URL del webhook

3. Descomenta la sección de Discord en el workflow

#### 4. Email
Para habilitar notificaciones por email:

1. Configura los siguientes secrets en GitHub:
   - `MAIL_SERVER`: servidor SMTP (ej: smtp.gmail.com)
   - `MAIL_PORT`: puerto SMTP (ej: 587)
   - `MAIL_USERNAME`: tu email
   - `MAIL_PASSWORD`: contraseña de aplicación
   - `NOTIFICATION_EMAIL`: email de destino

2. Descomenta la sección de email en el workflow

#### 5. Telegram
Para habilitar notificaciones en Telegram:

1. Crea un bot con @BotFather en Telegram
2. Obtén el token del bot
3. Obtén tu chat ID (puedes usar @userinfobot)
4. Agrega estos secrets en GitHub:
   - `TELEGRAM_TOKEN`: token del bot
   - `TELEGRAM_CHAT_ID`: tu chat ID

5. Descomenta la sección de Telegram en el workflow

#### 6. Microsoft Teams
Para habilitar notificaciones en Teams:

1. Crea un webhook en Teams:
   - Ve al canal de Teams
   - Configuración > Conectores > Webhook entrante
   - Copia la URL del webhook

2. Agrega el webhook como secret en GitHub:
   - Crea un secret llamado `TEAMS_WEBHOOK_URL`
   - Pega la URL del webhook

3. Descomenta la sección de Teams en el workflow

### 🔐 Gestión de Secrets

Para agregar secrets en GitHub:

1. Ve al repositorio en GitHub
2. Settings > Secrets and variables > Actions
3. Click en "New repository secret"
4. Ingresa el nombre y valor del secret
5. Guarda

### 📝 Personalización

Puedes modificar el workflow para:
- Cambiar el formato de los mensajes
- Agregar más información del commit
- Filtrar notificaciones por autor o tipo de commit
- Agregar condiciones para enviar notificaciones

### 🧪 Pruebas

Para probar el workflow:

1. Haz un commit en la rama `main`
2. Ve a la pestaña "Actions" en GitHub
3. Verifica que el workflow se ejecutó correctamente
4. Revisa los logs para ver la información del commit

### 📖 Recursos Adicionales

- [GitHub Actions Documentation](https://docs.github.com/es/actions)
- [Workflow Syntax](https://docs.github.com/es/actions/using-workflows/workflow-syntax-for-github-actions)
- [Encrypted Secrets](https://docs.github.com/es/actions/security-guides/encrypted-secrets)
