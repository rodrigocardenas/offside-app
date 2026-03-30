📝 INSTRUCCIONES PARA CONFIGURAR HOSTS LOCAL

═══════════════════════════════════════════════════════════════════

🔧 PASO 1: ABRIR EDITOR DE HOSTS (Como Administrador)

OPCIÓN A - Bloc de notas:
────────────────────────
1. Presiona: Win + R
2. Escribe: notepad C:\Windows\System32\drivers\etc\hosts
3. Haz clic en "Ejecutar"
4. Si pide permiso de administrador, haz clic en "Sí"

OPCIÓN B - PowerShell:
────────────────────
1. Abre PowerShell como administrador
2. Ejecuta: notepad C:\Windows\System32\drivers\etc\hosts

═══════════════════════════════════════════════════════════════════

🔧 PASO 2: AGREGAR LAS SIGUIENTES LÍNEAS AL FINAL DEL ARCHIVO

Copiar y pegar estas líneas al final (después de las líneas existentes):

───────────────────────────────────────────────────────────────
# Offside Club - Production Server (Feb 2026)
100.30.41.157 offsideclub.local
100.30.41.157 offsideclub.test
100.30.41.157 app.offsideclub.local
100.30.41.157 landing.offsideclub.local
───────────────────────────────────────────────────────────────

═══════════════════════════════════════════════════════════════════

🔧 PASO 3: GUARDAR Y CERRAR

1. Presiona: Ctrl + S
2. Cierra el Bloc de notas
3. ✅ Listo

═══════════════════════════════════════════════════════════════════

🔗 ACCESO DESPUÉS DE CONFIGURAR:

App Principal:      http://offsideclub.local
Landing Page:       http://offsideclub.local:3000
Horizon Dashboard:  http://offsideclub.local/horizon

O usar IP directamente:
App:                http://100.30.41.157
Landing:            http://100.30.41.157:3000

═══════════════════════════════════════════════════════════════════

🧪 VERIFICAR QUE FUNCIONA:

1. Abre navegador
2. Escribe: http://offsideclub.local
3. Deberías ver la página de login de Offside Club
4. En otra pestaña: http://offsideclub.local:3000 (Landing)

═══════════════════════════════════════════════════════════════════

⚠️  SOLUCIONAR SI NO FUNCIONA:

Si no funciona, intenta:

1. Limpiar caché DNS:
   ipconfig /flushdns

2. Reiniciar navegador

3. Verificar que el servidor esté activo:
   ping 100.30.41.157

4. Si recibiste un timeout en SSH, el servidor podría estar 
   reiniciándose. Espera 30 segundos y vuelve a intentar.

═══════════════════════════════════════════════════════════════════
