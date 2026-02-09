# 🔧 Guía para Resolver Problemas de Base de Datos

## 📍 Acceso Rápido a phpMyAdmin

**URL:** https://phpmyadmin.offsideclub.es  
**Usuario:** offside  
**Contraseña:** offside.2025

---

## 🔍 Checklist de Verificación

### 1. Revisar Estructura de Tabla `users`

En phpMyAdmin → Base de datos `offside_club` → Tabla `users`:

- [ ] Columnas presentes:
  - [ ] `id` (INT, PK, AI)
  - [ ] `unique_id` (VARCHAR 50) ← NUEVA
  - [ ] `name` (VARCHAR)
  - [ ] `email` (VARCHAR, UNIQUE)
  - [ ] `email_verified_at` (TIMESTAMP)
  - [ ] `password` (VARCHAR)
  - [ ] `remember_token` (VARCHAR)
  - [ ] `is_admin` (TINYINT) ← Verificar
  - [ ] `created_at` (TIMESTAMP)
  - [ ] `updated_at` (TIMESTAMP)

**Acción si falta alguna:**
```sql
-- Ejemplo para agregar is_admin si falta:
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER email;
```

### 2. Revisar Tabla `answers`

- [ ] Columnas presentes:
  - [ ] `id`
  - [ ] `user_id` (FK a users)
  - [ ] `question_id` (FK a questions)
  - [ ] `option_id` (FK a options) ← Crítica
  - [ ] `is_correct` (TINYINT)
  - [ ] `points_earned` (INT)
  - [ ] `category` (VARCHAR) ← Verificar
  - [ ] `created_at`
  - [ ] `updated_at`

### 3. Revisar Relaciones (Foreign Keys)

En phpMyAdmin → Tabla → Pestaña "Relaciones":

- [ ] `answers.user_id` → `users.id`
- [ ] `answers.question_id` → `questions.id`
- [ ] `answers.option_id` → `options.id` (o `question_options.id`)

---

## 🛠️ Comandos SQL para Diagnosticar

### Columnas Faltantes en `users`

Ejecuta en phpMyAdmin → SQL:

```sql
DESCRIBE users;
```

Esto mostrará todas las columnas de la tabla.

### Columnas Faltantes en `answers`

```sql
DESCRIBE answers;
```

### Contar Registros

```sql
SELECT 
  (SELECT COUNT(*) FROM users) as 'Usuarios',
  (SELECT COUNT(*) FROM answers) as 'Respuestas',
  (SELECT COUNT(*) FROM questions) as 'Preguntas',
  (SELECT COUNT(*) FROM options) as 'Opciones';
```

### Ver Integridad de Datos

```sql
-- Respuestas huérfanas (sin usuario)
SELECT * FROM answers WHERE user_id NOT IN (SELECT id FROM users);

-- Respuestas sin pregunta
SELECT * FROM answers WHERE question_id NOT IN (SELECT id FROM questions);

-- Usuarios sin respuestas
SELECT u.id, u.name FROM users u 
LEFT JOIN answers a ON u.id = a.user_id 
WHERE a.id IS NULL;
```

---

## ⚠️ Errores Comunes y Soluciones

### Error: "Unknown column 'unique_id'"
**Solución:**
```sql
ALTER TABLE users ADD COLUMN unique_id VARCHAR(50) NULL AFTER id;

-- Generar IDs únicos para usuarios existentes:
UPDATE users SET unique_id = CONCAT('USER_', UPPER(SUBSTRING(name, 1, 3)), '_', LPAD(FLOOR(RAND()*999999), 6, '0')) 
WHERE unique_id IS NULL;
```

### Error: "Column not found: is_admin"
**Solución:**
```sql
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER email;
```

### Error: "Foreign key constraint fails"
**Solución:** Desactiva temporalmente las restricciones:
```sql
SET FOREIGN_KEY_CHECKS=0;
-- Haz tus cambios
SET FOREIGN_KEY_CHECKS=1;
```

---

## 📋 Script de Restauración Completa

Si la BD está muy dañada, ejecuta esto en phpMyAdmin:

```sql
-- Crear tabla users correctamente
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  unique_id VARCHAR(50) NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  email_verified_at TIMESTAMP NULL,
  password VARCHAR(255) NOT NULL,
  remember_token VARCHAR(100) NULL,
  is_admin TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_email (email),
  INDEX idx_unique_id (unique_id)
);

-- Crear tabla answers correctamente
DROP TABLE IF EXISTS answers CASCADE;

CREATE TABLE answers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL,
  option_id BIGINT UNSIGNED NOT NULL,
  is_correct TINYINT(1) NULL,
  points_earned INT NOT NULL DEFAULT 0,
  category VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_question_id (question_id)
);
```

---

## 📞 Pasos para Reportar Problemas

1. Abre phpMyAdmin: https://phpmyadmin.offsideclub.es
2. Navega a la tabla problemática
3. Ejecuta: `DESCRIBE [nombre_tabla];`
4. Copia el resultado y envíamelo
5. Especifica qué error obtienes al intentar login

---

## 🚀 Test de Funcionamiento Post-Fix

Una vez arreglada la BD, prueba:

```bash
# SSH a la instancia
ssh -i "offside.pem" ubuntu@100.30.41.157

# Desde Laravel, ejecuta migraciones pendientes
cd /var/www/html
php artisan migrate --force

# Limpia cache
php artisan config:cache
php artisan route:cache

# Reinicia PHP
sudo systemctl restart php8.3-fpm
```

Luego intenta login en: https://app.offsideclub.es

---

**Próximo paso:** Revisa phpMyAdmin y reporta qué columnas/datos faltan 👇

