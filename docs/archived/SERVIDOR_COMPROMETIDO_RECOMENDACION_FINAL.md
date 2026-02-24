# 🚨 SERVIDOR COMPROMETIDO - RECOMENDACIÓN FINAL

**Análisis:** Feb 8, 2026  
**Status:** ⚠️ SERVIDOR ESTÁ COMPROMETIDO - ACCIÓN INMEDIATA REQUERIDA

---

## 📋 Evidencia de Compromiso Continuo

### 1. Permisos Resetean a 666 Automáticamente
```
/etc/cron.d/*.  ← Volvieron a 666 después de fijarlos
```

**Esto significa:**
- ❌ El ataque original NO fue completamente eliminado
- ❌ Algo sigue ACTIVAMENTE cambiando los permisos
- ❌ Posible segundo atacante o backdoor persistente

### 2. Puerto SMTP (25) Abierto
```
tcp 0.0.0.0:25  ← Postfix running (puede ser usado para spam/malware)
```

### 3. Posible RCE via PHPUnit (Ya Parcheado)
```
CVE-2026-24765 → Parcheado a 10.5.63
Pero: ¿Qué otro acceso dejó el atacante?
```

---

## ⏰ Timeline Crítico

```
Feb 6, 13:00 UTC
└─ Hardening incompleto - permisos NO persistieron

Feb 6, 22:11 UTC
└─ Ataque explotó permisos inseguros (666)
└─ Malware instalado: minería de crypto

Feb 6, 23:01 UTC
└─ Malware eliminado pero fix NO FUE PERMANENTE
└─ Permisos se "fijaron" MANUALMENTE pero sin persistencia

Feb 8, 00:00 UTC
└─ DESCUBIERTO: Permisos volvieron a 666
└─ Indicador: Servidor sigue activamente comprometido
```

---

## 🎯 RECOMENDACIÓN: REBUILD DESDE CERO

### Opción 1: Rebuild Rápido (RECOMENDADO)

**Tiempo:** 2-3 horas  
**Costo:** Mínimo (misma instancia)  
**Seguridad:** MÁXIMA

```bash
# 1. Backup datos
mysqldump -u admin -p offsideclub > /home/ubuntu/backup.sql
scp -r /var/www/html/offside-app/storage /local/backup/

# 2. Terminar instancia EC2 comprometida

# 3. Crear nueva instancia desde AMI limpia

# 4. Instalar stack limpio
- Ubuntu 24.04 LTS
- PHP 8.3 (FPM)
- Nginx
- MySQL (o usar RDS existente)
- Redis
- Node.js

# 5. Restaurar datos
- Restaurar DB from backup
- Copiar SOLO storage/ (sin código malicioso)
- Redeploy código limpio de git

# 6. Aplicar hardening desde el inicio
```

### Opción 2: Deep Forensics (Riesgoso)

```bash
# Investigar qué está:
1. Cambiando permisos a 666
2. Abriendo puerto 25 (SMTP)
3. Manteniendo acceso

# Requiere:
- Experto en seguridad
- Días de análisis
- Aún con riesgo de contaminación
```

---

## 🔧 Acciones Inmediatas (Mientras Decides)

**Ya Implementadas:**
- ✅ Permisos fijados a 755 (ahora)
- ✅ Cron job de monitoring cada 5 min
- ✅ PHPUnit CVE parcheado
- ✅ PHP hardening aplicado

**Monitorear:**
```bash
# Cada 5 minutos, los permisos se auto-corrigen
# Esto da tiempo para preparar rebuild

# Ver si el sistema intenta cambiarlos de nuevo
tail -f /var/log/cron.log  # Ver si el cron job se ejecuta
```

---

## ✅ Plan Recomendado

### Semana 1: Limpieza Temporal
1. ✅ Aplicar monitoring permanente (HECHO)
2. ✅ Mantener permisos asegurados (HECHO)
3. Mantener backups limpios

### Semana 2: Rebuild Limpio
1. Provisionar nueva instancia EC2
2. Instalar stack desde cero
3. Restaurar datos seguros
4. Redeploy código desde Git
5. Testing exhaustivo
6. Migración de tráfico
7. Terminar instancia comprometida

### Semana 3: Post-Rebuild
1. Hardening inicial (NO incremental)
2. Monitoreo de seguridad
3. WAF + IDS instalados
4. Logs centralizados
5. Alertas configuradas

---

## 💡 Por Qué Rebuild es Mejor

### Comparación

| Aspecto | Deep Forensics | Rebuild |
|---------|---|---|
| Tiempo | 5+ días | 2-3 horas |
| Costo | Bajo | Bajo |
| Seguridad | 70% (riesgo residual) | 99% (clean slate) |
| Confiabilidad | Dudosa | Alta |
| Riesgo de Reinfección | Alto (desconocido qué falta) | Bajo (código limpio) |

**Recomendación:** **REBUILD** 

---

## 📞 Acción Requerida del Usuario

**Pregunta Critical:**
> "¿Autorizas un rebuild del servidor con downtime mínimo (1-2 horas)?"

**Si SÍ:**
1. Dime cuándo hacer el rebuild
2. Preparo todo en paralelo
3. Ejecuto en ventana de mantenimiento

**Si NO (mantener servidor actual):**
1. Continuar con monitoreo permanente
2. Revisar logs diariamente
3. Estar alerta a anomalías

---

**Estado Actual:** ⚠️ Servidor asegurado temporalmente pero comprometido  
**Recomendación:** REBUILD desde cero en próximos días

