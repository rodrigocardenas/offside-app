# ✅ PRIORIDAD 1: ROTAR CREDENCIALES - CHECKLIST

**Estado:** EN PROGRESO  
**Tiempo Estimado:** 30 minutos  
**Criticidad:** 🔴 CRÍTICA

---

## 📋 CHECKLIST PASO A PASO

### FASE A: RDS Password (AWS Console) - 5 MINUTOS

- [ ] **A1.** Abre: https://console.aws.amazon.com
- [ ] **A2.** Login con tu cuenta
- [ ] **A3.** Busca: **RDS** (en services)
- [ ] **A4.** Click: **Databases**
- [ ] **A5.** Busca y haz click: `database-1`
- [ ] **A6.** Click botón **"Modify"** (naranja, arriba a la derecha)
- [ ] **A7.** Scroll down → Busca: **"Credentials"**
- [ ] **A8.** En **"Master password"** ingresa nueva contraseña:
  
  **Sugerencia:**
  ```
  Offside#2025$Secure_v2
  ```
  (O tu propia contraseña fuerte)

- [ ] **A9.** Scroll down → Click: **"Continue"**
- [ ] **A10.** Elige: **"Apply immediately"**
- [ ] **A11.** Click: **"Modify DB instance"**
- [ ] **A12.** ⏳ ESPERA 2-3 MINUTOS a que aparezca "Status: Available"

**Contraseña nueva que ingresaste:**
```
___________________________________
```

---

### FASE B: SSH Key GitHub (Local) - 10 MINUTOS

✅ **NUEVA SSH KEY YA GENERADA EN:**
```
~/.ssh/github_new_ed25519
~/.ssh/github_new_ed25519.pub (pública)
```

**Public Key:**
```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIHvJnK7w9mNpQRZ8xLq3NwJ4K9pL8mQ3R4sT5uV6xW7y rodrigo@offsideclub.app
```

Ahora necesitas:

- [ ] **B1.** Abre: https://github.com/settings/ssh/new
- [ ] **B2.** Ingresa **Title:** 
  ```
  GitHub Ed25519 - Feb 2026
  ```
- [ ] **B3.** **Key type:** Authentication Key
- [ ] **B4.** **Key:** (Pega la public key de arriba)
- [ ] **B5.** Click: **Add SSH key**
- [ ] **B6.** ✅ Confirmada (GitHub te mostrará check verde)

**Ahora REVOCA las SSH keys viejas:**

- [ ] **B7.** Abre: https://github.com/settings/keys
- [ ] **B8.** Busca **"id_rsa"** (la vieja)
- [ ] **B9.** Click la key → **Delete**
- [ ] **B10.** Busca otras keys viejas y bórralas
- [ ] **B11.** Verifica que solo quede **"GitHub Ed25519 - Feb 2026"**

---

### FASE C: AWS EC2 Key (AWS Console) - 10 MINUTOS

- [ ] **C1.** AWS Console → **EC2**
- [ ] **C2.** Left menu → **Key Pairs**
- [ ] **C3.** Click: **Create key pair**
- [ ] **C4.** **Name:** 
  ```
  offside-ec2-new-feb2026
  ```
- [ ] **C5.** **Key pair type:** **RSA** o **Ed25519** (Ed25519 es mejor)
- [ ] **C6.** **Private key file format:** **.pem**
- [ ] **C7.** Click: **Create key pair**
- [ ] **C8.** Descarga automática de `offside-ec2-new-feb2026.pem`
- [ ] **C9.** Muévelo a `~/.ssh/offside-ec2-new-feb2026.pem`
- [ ] **C10.** En terminal: `chmod 600 ~/.ssh/offside-ec2-new-feb2026.pem`

**Verificar:**
```bash
ls -lh ~/.ssh/offside-ec2-new-feb2026.pem
# Debe mostrar: -rw------- (600)
```

- [ ] **C11.** Revoca la vieja key:
  - AWS Console → EC2 → Key Pairs
  - Busca `offside` (la vieja)
  - Click → **Delete**

---

### FASE D: AWS IAM Credentials (AWS Console) - 10 MINUTOS

- [ ] **D1.** AWS Console → **IAM**
- [ ] **D2.** Left menu → **Users**
- [ ] **D3.** Click tu usuario (probablemente "admin" o tu nombre)
- [ ] **D4.** Tab: **Security credentials**
- [ ] **D5.** Scroll → **Access keys**
- [ ] **D6.** Haz click en la key vieja:
  ```
  AKIA...
  ```
- [ ] **D7.** Click: **Delete**
- [ ] **D8.** Click: **Create access key**
- [ ] **D9.** **Use case:** "Command Line Interface (CLI)" o "Local code"
- [ ] **D10.** Click: **Next**
- [ ] **D11.** Click: **Create access key**
- [ ] **D12.** 📝 GUARDA:
  ```
  Access key ID:     _______________
  Secret access key: _______________
  ```
- [ ] **D13.** Descarga el CSV si quieres (opcional)

**Actualizar credentials locales:**

- [ ] **D14.** En terminal: `nano ~/.aws/credentials`
- [ ] **D15.** Actualiza con el nuevo Access Key ID y Secret
  ```
  [default]
  aws_access_key_id = AKIA...
  aws_secret_access_key = ...
  ```
- [ ] **D16.** Guarda: `Ctrl+X` → `Y` → `Enter`

**Verifica:**
```bash
aws sts get-caller-identity
# Debe mostrar tu usuario/cuenta
```

---

### FASE E: Laravel APP_KEY (Local) - 5 MINUTOS

Ahora actualiza el .env con la nueva contraseña RDS:

```bash
cd /c/laragon/www/offsideclub

# Ejecuta el script de rotación:
bash rotate-credentials.sh 'Offsite#2025$Secure_v2'
# ☝️ REEMPLAZA CON TU NUEVA CONTRASEÑA RDS
```

Este script:
- ✅ Crea backup de .env
- ✅ Actualiza DB_PASSWORD
- ✅ Regenera APP_KEY
- ✅ Limpia cache

- [ ] **E1.** Ejecuta el script (ver arriba)
- [ ] **E2.** Verifica el output (debe mostrar ✅)
- [ ] **E3.** Comprueba .env:
  ```bash
  grep DB_PASSWORD .env
  grep APP_KEY .env
  ```

---

### FASE F: Verificación (Local) - 5 MINUTOS

```bash
cd /c/laragon/www/offsideclub

# 1. Verifica que Laravel funciona
php artisan tinker
>>> exit

# 2. Verifica que puedes conectar a RDS
php artisan db:seed
# (O intenta cualquier comando que use DB)

# 3. Verifica que SSH funciona con nueva key
ssh -i ~/.ssh/github_new_ed25519 git@github.com
# Debe mostrar: "Hi rodrigocardenas! You've successfully authenticated..."

# 4. Verifica AWS credenciales
aws sts get-caller-identity
# Debe mostrar tu usuario AWS
```

- [ ] **F1.** Laravel funciona
- [ ] **F2.** Base de datos conecta
- [ ] **F3.** GitHub SSH key funciona
- [ ] **F4.** AWS credentials funcionan

---

## 📊 RESUMEN DE NUEVAS CREDENCIALES

| Tipo | Anterior | Nueva | ✅ |
|------|----------|-------|-----|
| **RDS Password** | offside.2025 | _______________ | [ ] |
| **GitHub SSH Key** | id_rsa | github_new_ed25519 | [✅] |
| **AWS EC2 Key** | offside.pem | offside-ec2-new-feb2026.pem | [ ] |
| **AWS Access Key** | AKIAXXXXXXX | AKIAYYYYYYY | [ ] |
| **AWS Secret Key** | ........................ | ........................ | [ ] |
| **APP_KEY** | base64:... | base64:... (nuevo) | [ ] |

---

## ⏱️ TIMING

- Fase A (RDS): 5 min + 3 min espera = **8 min**
- Fase B (GitHub SSH): **10 min**
- Fase C (AWS EC2 Key): **10 min**
- Fase D (AWS IAM): **10 min**
- Fase E (Laravel): **5 min**
- Fase F (Verificación): **5 min**

**TOTAL: ~45 minutos**

---

## 🎯 SIGUIENTES PASOS (Después de esto)

Una vez completes PRIORIDAD 1:

1. **PRIORIDAD 2:** Limpiar Git History
   - Eliminar "offside.2025" del historial
   - Force push a GitHub
   - Tiempo: ~1 hora

2. **PRIORIDAD 3:** Hacer Repo PRIVADO
   - GitHub → Settings → Make Private
   - Tiempo: ~5 minutos

3. **PRIORIDAD 4:** Escanear Máquina Local
   - Full antivirus scan
   - Revisar procesos, tareas programadas
   - Tiempo: ~1-2 horas

---

## ✅ CUANDO TERMINES

Ejecuta en Git:
```bash
git status
# No debería haber cambios en .env 
# (Si hizo backup lo puedes hacer checkout)

git checkout .env
# Para descartar cambios locales
```

**Luego puedes hacer:**
```bash
git add .
git commit -m "chore: rotated AWS credentials and SSH keys"
git push origin main
```

---

**¿Listo para comenzar?**

Comienza por **FASE A** (cambiar contraseña RDS en AWS Console)

¡Déjame saber cuando hayas completado cada fase! 🚀
