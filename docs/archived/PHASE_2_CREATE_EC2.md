# FASE 2: CREAR NUEVA INSTANCIA EC2

## Estado Actual
- ✅ Backups descargados localmente:
  - `db-backup.sql` (27 KB)
  - `backup-storage-20260208/` (600+ MB con avatares)
  - `.env.backup` (configuración)
  - `composer.lock.backup` (dependencias)
- ✅ Script de instalación listo: `install-clean-stack.sh`
- 🔴 Servidor comprometido aún en uso: `ec2-52-3-65-135.compute-1.amazonaws.com`

## Pasos para Crear Nueva Instancia EC2

### Paso 1: Acceder a AWS Console
- URL: https://console.aws.amazon.com
- Region: **us-east-1**
- Service: **EC2**

### Paso 2: Lanzar Instancia
1. Click en "Launch Instances"
2. Nombre: `offside-app-clean-rebuild`
3. **AMI Selection:**
   - Search: "Ubuntu 24.04 LTS"
   - Select: `ami-xxxxxxxxx` (Ubuntu 24.04 LTS, 64-bit, x86)
4. **Instance Type:** `t3.medium` (2 vCPU, 4 GB RAM) - IGUAL AL ACTUAL
5. **Key pair:** Seleccionar `offside` (la que ya tienes)
6. **Network Settings:**
   - VPC: (default o la del servidor actual)
   - Subnet: (same as current server)
   - Auto-assign public IP: YES
   - Security Group: **Seleccionar la EXISTENTE del servidor actual** (que ya permite HTTP/HTTPS)
7. **Storage:**
   - Size: 30 GB (o igual al actual)
   - Type: gp3
   - Encrypted: No (para velocidad de rebuild)
8. Click "Launch Instance"

### Paso 3: Anotar Información
Una vez creada la instancia, ANOTA:
```
Instancia ID: i-xxxxxxxxxx
Public IP: xxx.xxx.xxx.xxx (Asignada automáticamente si auto-assign está ON)
Private IP: 10.x.x.x
Availability Zone: us-east-1a/b/c
Security Group: sg-xxxxxxxxx
```

### Paso 4: Esperar 2-3 minutos
La instancia tarda unos minutos en estar lista para conexión SSH.

### Paso 5: Verificar Conectividad
```bash
ssh -i "C:/Users/rodri/OneDrive/Documentos/aws/offside.pem" ubuntu@<PUBLIC_IP>
echo "✅ Conectado!" && exit
```

## Información para Siguiente Fase

Una vez conectado a la NUEVA instancia, el siguiente comando iniciará la instalación:

```bash
# Descargar script
curl -O https://raw.githubusercontent.com/rodrigocardenas/offside-app/main/install-clean-stack.sh

# O copiar desde local si no está en GitHub:
scp -i offside.pem install-clean-stack.sh ubuntu@<NEW_IP>:/tmp/

# Ejecutar
bash /tmp/install-clean-stack.sh
```

## Timeline Estimado
- Crear instancia: 2 minutos
- Esperar a estar lista: 2-3 minutos
- Total: 5 minutos

## Siguiente Paso Después de Esta Fase
**FASE 3: Instalar Stack Limpio**
- SSH a la nueva instancia
- Ejecutar script de instalación
- Verificar: `php -v`, `nginx -v`, `redis-cli ping`
