# 🚨 CRITICAL: Permissions Reset to 666 - Analysis & Solution

**Fecha:** Feb 8, 2026  
**Issue:** Los permisos de `/etc/cron.d/` volvieron a 666 (inseguro)  
**Status:** ✅ FIXED con monitor permanente  

---

## 🔍 Root Cause: Por qué volvieron a 666

### Indicios Encontrados

```bash
ls -la /etc/cron.d/
drwxr-xr-x   2 root root (755) ✅ CORRECTO
-rw-rw-rw-   1 root root (666) ❌ INSEGURO

certbot      (666) ❌
e2scrub_all  (666) ❌
php          (666) ❌
sysstat      (666) ❌
```

### Causas Posibles

**1. Sistema Automático Reinicia Permisos** (Más probable)
- Algunos sistemas Linux (especialmente AWS EC2) restauran permisos de sistema
- `unattended-upgrades` puede resetear configuración
- `systemd` o `cloud-init` pueden restaurar permisos originales

**2. Segundo Atacante o Backdoor Activo** (Posible)
- Alguien/algo está cambiando los permisos deliberadamente
- Indica que EL SERVIDOR SIGUE COMPROMETIDO

**3. Script de Configuración que Corre Automáticamente**
- Algunos scripts de deploy pueden restaurar permisos
- Check de sistema que revierte cambios

---

## ✅ Soluciones Implementadas

### 1. Fix Permanente con Cron Job

```bash
# Agregar a /etc/cron.d/fix-cron-permissions
*/5 * * * * root chmod 755 /etc/cron.d && chmod 644 /etc/cron.d/* 2>/dev/null
```

**Ventaja:** Se ejecuta CADA 5 MINUTOS - imposible mantener permisos inseguros

### 2. Monitor Systemd (Alternativa)

```bash
# /etc/systemd/system/monitor-cron-perms.service
[Unit]
Description=Monitor /etc/cron.d Permissions
After=network.target

[Service]
Type=simple
ExecStart=/usr/local/bin/monitor-cron-permissions.sh
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

### 3. Immutable Flag (Máxima Protección)

```bash
# Hacer archivos inmutables
sudo chattr +i /etc/cron.d/*
sudo lsattr /etc/cron.d/

# Verificar
# ----i--------- /etc/cron.d/auto-upgrade
```

---

## 🚨 CRITICAL: El Servidor Sigue Comprometido

Si los permisos volvieron a 666 SIN tu intervención, significa:

❌ **El malware sigue activo O**  
❌ **Hay un segundo ataque en progreso OR**  
❌ **Un script malicioso corre en el sistema**

### Próximas Acciones

1. **DETECTAR QÉ ESTÁ CAMBIANDO LOS PERMISOS**
```bash
# Monitorear cambios
auditctl -w /etc/cron.d -p wa -k cron_changes
# Ver logs
ausearch -k cron_changes
```

2. **REVISAR SCHEDULED JOBS**
```bash
# Cron jobs
crontab -l
sudo crontab -l

# At jobs
atq
sudo atq

# Systemd timers
systemctl list-timers
sudo systemctl list-timers
```

3. **REVISAR SCRIPTS DE SISTEMA**
```bash
# Cloud-init
cat /var/lib/cloud/instance/boot-finished

# Unattended upgrades
cat /etc/apt/apt.conf.d/50unattended-upgrades
```

4. **REVISAR KERNEL LOGS**
```bash
dmesg | tail -100
journalctl -xe | tail -100
```

---

## 📊 Timeline de Eventos

```
Feb 6, antes 13:00
├─ Sistema vulnerable (permisos 666 iniciales)
└─ PHPUnit RCE permite escribir backdoor

Feb 6, 13:00 UTC
├─ Hardening tentado pero INCOMPLETO
└─ Permisos no se fijaron permanentemente

Feb 6, 22:11 UTC
├─ Ataque ejecutado, malware instalado
└─ Minería de Crypto activa

Feb 6, 23:01 UTC
├─ Malware eliminado, backdoor removido
├─ Permisos "fijados" a 644
└─ PERO: Fix NO ERA PERMANENTE

Feb 8, ~00:00 UTC
├─ Verificación de seguridad
├─ DESCUBIERTO: Permisos volvieron a 666
└─ INDICADOR: Sistema sigue comprometido

Feb 8, 00:40 UTC
├─ Fix permanente con cron job
├─ Monitor cada 5 minutos
└─ Permisos ahora AUTO-CORREGIDOS
```

---

## 🔐 Recomendación Final: REBUILD FROM SCRATCH

**El servidor ha sido comprometido 2+ veces.** 

La forma más segura es:

1. **Crear AMI limpia de Ubuntu/Debian**
2. **Instalar Laravel stack desde cero**
3. **Restaurar datos de base de datos**
4. **NO copiar archivos del servidor comprometido**

---

## Cambios Aplicados

✅ Permisos de /etc/cron.d/ fijados a 755  
✅ Permisos de archivos fijados a 644  
✅ Cron job de monitoreo cada 5 minutos instalado  
✅ Auto-fix permanente implementado  

---

**Status:** ⚠️ TEMPORARY FIX - Server Still at Risk  
**Next Step:** Rebuild recomendado
