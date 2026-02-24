# 🔐 CONFIGURAR SECURITY GROUPS EN AWS - INSTRUCCIONES PASO A PASO

**Instancia EC2:** ec2-54-90-74-219.compute-1.amazonaws.com (IP: 54.90.74.219)  
**Base de Datos RDS:** database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com

---

## ⚠️ POR QUÉ ESTAMOS AQUÍ

La aplicación está instalada y funcionando **PERO**:
1. ❌ No puedes acceder desde tu navegador → `curl http://54.90.74.219` no responde
2. ❌ EC2 no puede conectarse a RDS → `ERROR 1698 (28000)`

**Razón:** Los Security Groups (firewall de AWS) bloquean estas conexiones.

---

## 🔧 PASO 1: Permitir HTTP/HTTPS en EC2

### En AWS Console

1. **Abre AWS Console** → https://console.aws.amazon.com/
2. **Ve a EC2** → "Instances" 
3. **Selecciona** `ec2-54-90-74-219` (búscalo por IP 54.90.74.219)
4. En la pestaña **"Security"**, haz click en el Security Group (ej: `sg-0xxxxx`)

![Paso 1](./docs/sg-step1.jpg)

### Agregar Inbound Rules

5. **Click en "Edit inbound rules"**
6. **Click en "Add rule"** y agrega:

#### Regla 1: HTTP
```
Type:        HTTP
Protocol:    TCP
Port range:  80
Source:      0.0.0.0/0 (anywhere)
Description: Web traffic
```

#### Regla 2: HTTPS (para futuro)
```
Type:        HTTPS
Protocol:    TCP
Port range:  443
Source:      0.0.0.0/0 (anywhere)
Description: Secure web traffic
```

#### Regla 3: SSH (solo tu IP - IMPORTANTE)
```
Type:        SSH
Protocol:    TCP
Port range:  22
Source:      YOUR_IP_ADDRESS/32
Description: SSH access
```

7. **Click "Save rules"**

---

## 🔧 PASO 2: Permitir MySQL en RDS

### Encontrar el Security Group de EC2

1. Sigue los pasos anteriores pero **nota el ID** del Security Group (ej: `sg-0xxxxx`)
2. **O ve a:** EC2 → Network & Security → Security Groups
3. Busca el grupo asignado a la instancia `ec2-54-90-74-219`

### Ir a RDS

1. **AWS Console** → RDS
2. **Databases** → `database-1`
3. En la sección "Security group", **click en el nombre** del grupo (ej: `default` o `rds-xxx`)

### Agregar Regla de Entrada

4. **Click "Edit inbound rules"**
5. **Click "Add rule"**:

```
Type:        MySQL/Aurora
Protocol:    TCP
Port range:  3306
Source:      sg-0xxxxx (el Security Group de EC2)
           O IP: 172.31.20.130/32
Description: MySQL from EC2
```

**OPCIÓN A: Por Security Group** (RECOMENDADO)
```
Source type: Security Group
Security Group: sg-0xxxxx (del EC2)
```

**OPCIÓN B: Por IP** (si lo anterior no funciona)
```
Source type: IP CIDR
CIDR: 172.31.20.130/32
```

6. **Click "Save"**

---

## ✅ VERIFICAR QUE FUNCIONÓ

### Test 1: Acceso HTTP a EC2

En tu terminal local:
```bash
curl -I http://54.90.74.219/

# Debería responder:
# HTTP/1.1 302 Found
# Location: http://54.90.74.219/login
```

O en tu navegador:
```
http://54.90.74.219/
```
Deberías ver un **redirect a `/login`**

### Test 2: Conexión a RDS desde EC2

En la instancia EC2:
```bash
ssh -i "offside.pem" ubuntu@54.90.74.219

# Una vez conectado:
mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com \
  -u offside \
  -p'offside.2025' \
  -e "SELECT VERSION();"

# Debería responder:
# mysql: [Warning] Using a password on the command line interface can be insecure.
# 8.0.45
```

---

## 📋 Checklist de Security Groups

### EC2 Security Group

| Tipo | Protocolo | Puerto | Origen | ¿Configurado? |
|------|-----------|--------|--------|--------------|
| HTTP | TCP | 80 | 0.0.0.0/0 | ☐ |
| HTTPS | TCP | 443 | 0.0.0.0/0 | ☐ |
| SSH | TCP | 22 | YOUR_IP/32 | ☐ |

### RDS Security Group

| Tipo | Protocolo | Puerto | Origen | ¿Configurado? |
|------|-----------|--------|--------|--------------|
| MySQL | TCP | 3306 | sg-xxxxx (EC2) | ☐ |

---

## 🚨 SOLUCIÓN DE PROBLEMAS

### "Timeout" en curl http://54.90.74.219/

**Problema:** No agregaste HTTP en el Security Group de EC2
**Solución:** Ve a Paso 1 y agrega la regla HTTP

### "ERROR 1698 (28000)" en MySQL

**Problema:** No agregaste MySQL en el Security Group de RDS
**Solución:** Ve a Paso 2 y agrega la regla MySQL

### "Permission denied" en SSH

**Problema:** No está tu IP en la regla SSH
**Solución:** Averigua tu IP pública:
```bash
curl https://checkip.amazonaws.com
# O busca "my ip" en Google
```

Luego agrega esa IP en formato `X.X.X.X/32` a la regla SSH

---

## 🎯 DESPUÉS DE CONFIGURAR

1. **Restaurar Base de Datos**
   ```bash
   ssh ubuntu@54.90.74.219
   cd /tmp
   mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com \
     -u offside \
     -p'offside.2025' \
     offside_club < db-backup.sql
   ```

2. **Verificar Datos**
   ```bash
   mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com \
     -u offside \
     -p'offside.2025' \
     offside_club \
     -e "SHOW TABLES; SELECT COUNT(*) FROM users;"
   ```

3. **Acceder a la Aplicación**
   ```
   http://54.90.74.219/login
   ```

---

## ⏱️ TIEMPO ESTIMADO

| Paso | Tiempo |
|------|--------|
| Abrir AWS Console | 1 min |
| Configurar EC2 Security Group | 3 min |
| Configurar RDS Security Group | 3 min |
| Verificar con curl/MySQL | 2 min |
| **Total** | **~9 minutos** |

---

## 📸 REFERENCIAS VISUALES

### Ubicación de Security Group en EC2

```
EC2 → Instances → Select instance (54.90.74.219)
↓
Click en "Security" tab
↓
Click en "Security groups" link (sg-0xxxxx)
↓
Click "Edit inbound rules"
```

### Ubicación de Security Group en RDS

```
RDS → Databases → database-1
↓
Scroll down hasta "Security group"
↓
Click en nombre del grupo
↓
Click "Edit inbound rules"
```

---

**Una vez completados estos pasos, ejecuta:**

```bash
# Test final
curl -I http://54.90.74.219/
ssh -i offside.pem ubuntu@54.90.74.219 "mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u offside -p'offside.2025' -e 'SELECT VERSION();'"
```

Si ambos comandos responden correctamente, ¡**todo está listo!**
