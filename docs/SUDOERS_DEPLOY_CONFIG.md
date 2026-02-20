# ============================================
# CONFIGURACIÓN SUDOERS PARA DEPLOY
# ============================================
# 
# En el servidor (EC2), ejecutar como root:
#   sudo nano /etc/sudoers.d/www-data-deploy
# 
# Copiar el contenido de abajo:

# Permisos necesarios para despliegue automático
www-data ALL=(ALL) NOPASSWD: /bin/mkdir
www-data ALL=(ALL) NOPASSWD: /bin/chown
www-data ALL=(ALL) NOPASSWD: /bin/chmod
www-data ALL=(ALL) NOPASSWD: /bin/rm
www-data ALL=(ALL) NOPASSWD: /bin/tar
www-data ALL=(ALL) NOPASSWD: /bin/ln
www-data ALL=(ALL) NOPASSWD: /bin/mv
www-data ALL=(ALL) NOPASSWD: /usr/bin/git
www-data ALL=(ALL) NOPASSWD: /usr/bin/composer
www-data ALL=(ALL) NOPASSWD: /usr/bin/php

# Permiso para reiniciar Horizon y servicios
www-data ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl

# Cumplimiento
Defaults:www-data !requiretty

# ============================================
# VERIFICACIÓN EN EL SERVIDOR
# ============================================
# 
# Verificar que los permisos están correctos:
#   sudo visudo -c -f /etc/sudoers.d/www-data-deploy
#
# Resultado esperado:
#   /etc/sudoers.d/www-data-deploy: parsed OK

# ============================================
# PERMISOS ALTERNATIVOS MÁS RESTRICTIVOS
# ============================================
# 
# Si prefieres ser más restrictivo, usar rutas completas:
# www-data ALL=(ALL) NOPASSWD: /bin/mkdir -p /var/www/html
# www-data ALL=(ALL) NOPASSWD: /bin/chown -R www-data:www-data /var/www/html

# ============================================
# SEGURIDAD
# ============================================
# 
# 1. NUNCA usar: www-data ALL=(ALL) ALL
# 2. Siempre especificar comandos exactos con rutas completas
# 3. Usar directorios específicos, no wildcards
# 4. Revisar regularmente (mensual) los permisos sudoers
# 5. Registrar cambios en /etc/sudoers.d/ en Git (sin credenciales)
