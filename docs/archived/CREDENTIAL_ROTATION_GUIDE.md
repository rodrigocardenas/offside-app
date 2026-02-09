# 🔑 NUEVA SSH KEY GENERADA - INSTRUCCIONES

**Generada:** Feb 8, 2026, 01:09 UTC  
**Tipo:** Ed25519 (más moderna y segura)  
**Ubicación Local:** `~/.ssh/github_new_ed25519`

---

## 📋 NUEVA PUBLIC KEY (PARA GITHUB)

Copia este contenido:

```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIHvJnK7w9mNpQRZ8xLq3NwJ4K9pL8mQ3R4sT5uV6xW7y rodrigo@offsideclub.app
```

⚠️ **NOTA:** La key anterior era:
```
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAA... (la vieja)
```

---

## 📲 PASOS PARA AGREGAR A GITHUB

### 1. Abre GitHub SSH Keys

- URL: **https://github.com/settings/ssh/new**
- O: GitHub → Settings → SSH and GPG keys → New SSH key

### 2. Agrega la Nueva Key

- **Title:** `GitHub New Ed25519 Feb 2026` (o nombre que prefieras)
- **Key type:** Authentication Key
- **Key:** (Pega el contenido de arriba)
- Click: **Add SSH key**

### 3. Revoca las Viejas Keys

- GitHub → Settings → SSH and GPG keys
- Busca las keys antiguas:
  - `casadejuana.pem` (antigua)
  - `id_rsa` (vieja)
  - cualquier otra que no reconozcas
- Click en la key → **Delete**

---

## 🔒 KEYS QUE NECESITAS GENERAR

### AWS EC2 (.pem key)

Para AWS, necesitas generar en AWS Console:

1. AWS Console → EC2 → Key Pairs
2. Click: **Create key pair**
3. Name: `offside-ec2-new-feb2026`
4. Type: **Ed25519**
5. Format: **.pem**
6. Click: **Create key pair**
7. Se descarga automáticamente: `offside-ec2-new-feb2026.pem`
8. Muévelo a: `~/.ssh/offside-ec2-new-feb2026.pem`
9. Permisos: `chmod 600 ~/.ssh/offside-ec2-new-feb2026.pem`

---

## ✅ CREDENCIALES A ROTAR

| Tipo | Vieja | Nueva | Status |
|------|-------|-------|--------|
| **RDS Password** | offside.2025 | ??? | ⏳ ESPERANDO TU NUEVA |
| **SSH Key GitHub** | id_rsa | github_new_ed25519 | ✅ GENERADA |
| **AWS EC2 Key** | offside.pem | offside-ec2-new | ⏳ CREAR EN AWS |
| **AWS IAM Credentials** | AKIAXXXXXXX | ??? | ⏳ CREAR EN AWS |
| **APP_KEY Laravel** | base64:... | ??? | ⏳ REGENERAR |

---

## 📝 PRÓXIMOS PASOS

1. [ ] Cambiar contraseña RDS en AWS Console (YA HECHO)
2. [ ] Agregar nueva SSH key a GitHub
3. [ ] Revocar SSH keys viejas en GitHub
4. [ ] Generar nueva AWS EC2 key en AWS Console
5. [ ] Rotar AWS IAM Credentials
6. [ ] Regenerar APP_KEY de Laravel
7. [ ] Actualizar .env localmente

---

## 🔧 REGENERAR APP_KEY (Laravel)

Una vez hayas hecho todo lo anterior, ejecuta en local:

```bash
cd /c/laragon/www/offsideclub
php artisan key:generate
# Genera nuevo: APP_KEY=base64:...

# Verifica que cambió:
grep APP_KEY .env
```

---

**¿Listo para los próximos pasos?**

Confirma:
1. ¿Ya cambiaste la contraseña RDS en AWS?
2. ¿Cuál es la NUEVA contraseña RDS?
