# 🚨 RCE SECURITY AUDIT - CRITICAL VULNERABILITIES FOUND

**Date:** Feb 6, 2026  
**Status:** CRITICAL - Immediate action required  
**Conducted by:** Security Audit Team  

---

## Executive Summary

A comprehensive security audit of the Offside App has identified **3 CRITICAL** vulnerabilities that could allow Remote Code Execution (RCE). The hardening deployed previously has NOT been correctly applied to the production server.

**Severity: CRITICAL** 🔴

---

## Vulnerabilities Found

### 1. 🔴 CRITICAL: disable_functions NOT CONFIGURED IN PHP

**Location:** `/etc/php/8.3/fpm/php.ini`  
**Current Value:** Empty  
**Required Value:**
```ini
disable_functions = system,exec,passthru,shell_exec,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,dl,eval
```

**Impact:**
- Attackers can execute system commands via `system()`, `exec()`, `shell_exec()`, etc.
- This is likely HOW the malware was installed

**Fix Required:**
```bash
sudo nano /etc/php/8.3/fpm/php.ini
# Find: disable_functions =
# Replace with: disable_functions = system,exec,passthru,shell_exec,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,dl,eval

# Then restart:
sudo systemctl restart php8.3-fpm
```

---

### 2. 🔴 CRITICAL: open_basedir NOT CONFIGURED

**Location:** `/etc/php/8.3/fpm/php.ini`  
**Current Value:** NOT SET  
**Required Value:**
```ini
open_basedir = /var/www/html/offside-app:/tmp:/var/tmp:/dev/urandom
```

**Impact:**
- PHP can access files ANYWHERE on the system
- Attackers can read sensitive files: `/etc/passwd`, database configs, etc.
- Combined with `allow_url_fopen = On`, can read remote files

**Fix Required:**
```bash
sudo nano /etc/php/8.3/fpm/php.ini
# Add (if not present): 
# open_basedir = /var/www/html/offside-app:/tmp:/var/tmp:/dev/urandom

# Also disable URL file operations:
# allow_url_fopen = Off
# allow_url_include = Off
```

---

### 3. 🔴 CRITICAL: allow_url_fopen = On

**Location:** `/etc/php/8.3/fpm/php.ini`  
**Current Value:** `On`  
**Required Value:** `Off`

**Impact:**
- Applications can download and execute files from internet
- Vector for delivering malware payloads
- Used to fetch `http://abcdefghijklmnopqrst.net/sh` in previous attack

**Fix Required:**
```bash
sudo nano /etc/php/8.3/fpm/php.ini
# Find: allow_url_fopen = On
# Replace with: allow_url_fopen = Off
```

---

### 4. 🟠 HIGH: Path Traversal in /avatars Route

**Location:** `routes/web.php:162-177`  
**Vulnerable Code:**
```php
Route::get('/avatars/{filename}', function ($filename) {
    $path = storage_path('app/public/avatars/' . $filename);
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    $file = file_get_contents($path);
    $type = mime_content_type($path);
    
    return response($file, 200)
        ->header('Content-Type', $type)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where('filename', '.*');  // ⚠️ ALLOWS ANY PATH!
```

**Vulnerability:**
- Regex `.*` allows slashes (`/`)
- No path traversal protection
- Can access: `/avatars/../../../../../etc/passwd`

**Exploit Example:**
```bash
curl http://ec2-52-3-65-135.compute-1.amazonaws.com/avatars/..%2F..%2F..%2Fetc%2Fpasswd
```

**Fix Required:**
```php
Route::get('/avatars/{filename}', function ($filename) {
    // Only allow alphanumeric, dash, underscore, dot
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
        abort(403);
    }
    
    $path = storage_path('app/public/avatars/' . $filename);
    
    // Extra safety: ensure path is within avatars directory
    $basePath = realpath(storage_path('app/public/avatars'));
    $realPath = realpath($path);
    
    if (!$realPath || strpos($realPath, $basePath) !== 0) {
        abort(403);
    }
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    $file = file_get_contents($path);
    $type = mime_content_type($path);
    
    return response($file, 200)
        ->header('Content-Type', $type)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where('filename', '[a-zA-Z0-9._-]+');
```

---

## Root Cause Analysis

The hardening script (`hardening-security.sh`) was supposed to configure these PHP settings, but:

1. **PHP configuration was not applied** or was overwritten
2. **No verification script** was run after deployment
3. **No monitoring** to detect changes to critical configs

**Proof:** The `/etc/php/8.3/fpm/php.ini` file shows these settings are completely missing.

---

## Recommended Actions (Priority Order)

### IMMEDIATE (Next 30 minutes)

1. **Fix PHP hardening** - Run the script below
2. **Rotate all credentials** - SSH keys, database passwords, API tokens
3. **Review web logs** - Check if /avatars path traversal was exploited
4. **Deploy WAF rules** - Block known RCE patterns

### TODAY (Next 4 hours)

5. **Code patch** - Fix path traversal in /avatars route
6. **Test application** - Verify nothing breaks after PHP changes
7. **Full application audit** - Review all user input handling
8. **Set up monitoring** - Detect PHP config changes automatically

### THIS WEEK

9. **Implement WAF** - ModSecurity or AWS WAF
10. **Code security review** - All controllers and routes
11. **Dependency audit** - Check Composer dependencies for known CVEs

---

## Remediation Script

Créé este script para aplicar todas las correcciones:

```bash
#!/bin/bash
# Fix PHP hardening vulnerabilities

set -e

echo "🔒 APPLYING PHP HARDENING FIXES..."
echo ""

PHP_INI="/etc/php/8.3/fpm/php.ini"
PHP_BACKUP="/etc/php/8.3/fpm/php.ini.backup-$(date +%Y%m%d_%H%M%S)"

# Backup original
echo "📦 Backing up PHP configuration..."
sudo cp "$PHP_INI" "$PHP_BACKUP"
echo "   Backup: $PHP_BACKUP"
echo ""

# Function to update or add PHP setting
update_php_setting() {
    local key=$1
    local value=$2
    
    if grep -q "^$key" "$PHP_INI"; then
        # Setting exists, update it
        sudo sed -i "s/^$key.*/$key = $value/" "$PHP_INI"
        echo "   ✅ Updated: $key = $value"
    else
        # Setting doesn't exist, add it
        echo "$key = $value" | sudo tee -a "$PHP_INI" > /dev/null
        echo "   ✅ Added: $key = $value"
    fi
}

echo "🔧 Applying PHP configuration fixes..."
echo ""

echo "1. Setting disable_functions..."
update_php_setting "disable_functions" "system,exec,passthru,shell_exec,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,dl,eval"

echo ""
echo "2. Setting open_basedir..."
update_php_setting "open_basedir" "/var/www/html/offside-app:/tmp:/var/tmp:/dev/urandom"

echo ""
echo "3. Disabling allow_url_fopen..."
update_php_setting "allow_url_fopen" "Off"

echo ""
echo "4. Disabling allow_url_include..."
update_php_setting "allow_url_include" "Off"

echo ""
echo "5. Hardening session configuration..."
update_php_setting "session.use_only_cookies" "1"
update_php_setting "session.cookie_httponly" "1"
update_php_setting "session.cookie_secure" "1"
update_php_setting "session.cookie_samesite" "Strict"

echo ""
echo "🔄 Restarting PHP-FPM..."
sudo systemctl restart php8.3-fpm
echo "   ✅ PHP-FPM restarted"
echo ""

echo "✅ HARDENING COMPLETE"
echo ""
echo "Verification:"
echo "  disable_functions: $(grep '^disable_functions' $PHP_INI | cut -d= -f2)"
echo "  open_basedir: $(grep '^open_basedir' $PHP_INI | cut -d= -f2)"
echo "  allow_url_fopen: $(grep '^allow_url_fopen' $PHP_INI | cut -d= -f2)"
echo ""
```

---

## Testing the Fixes

After applying hardening, verify with:

```bash
# Test 1: Verify disable_functions
php -r "echo 'Testing system() call...'; system('whoami');"
# Should output: Warning: system() has been disabled

# Test 2: Verify open_basedir
php -r "file_get_contents('/etc/passwd');"
# Should output: Warning: file_get_contents(/etc/passwd): Failed to open stream

# Test 3: Test path traversal fix
curl -v "http://ec2-52-3-65-135.compute-1.amazonaws.com/avatars/../../../etc/passwd"
# Should return: 403 Forbidden
```

---

## Attack Timeline

```
Feb 4, 2026
├─ Initial vulnerability exists (missing PHP hardening)
└─ Possible: Attacker discovered RCE vector

Feb 6, 13:00 UTC
├─ hardening-security.sh was supposed to fix this
├─ But PHP configuration was NOT actually applied
└─ Server remains vulnerable

Feb 6, 22:11 UTC
├─ Attacker exploited RCE via:
│  ├─ Option A: SQL Injection → file_put_contents
│  ├─ Option B: Path traversal → write to /etc/cron.d/
│  └─ Option C: Vulnerable Composer package
├─ Creates /etc/cron.d/auto-upgrade with malware payload
└─ Cron executes as root every day

Feb 6, 22:58 UTC
├─ User detected malware (100% CPU, process 7jf6tJ76B)
└─ We eliminated malware and fixed /etc/cron.d/ permissions

Feb 6, 23:07 UTC
├─ Discovered: PHP hardening was NEVER applied
├─ disable_functions: EMPTY ❌
├─ open_basedir: NOT SET ❌
└─ allow_url_fopen: ON ❌
```

---

## How the Attacker Likely Got In

Given the PHP hardening was missing, the attack likely followed this chain:

```
1. APPLICATION VULNERABILITY
   ↓
   Option A: SQL Injection
   ├─ LOAD_FILE('/etc/passwd')
   └─ INTO OUTFILE '/tmp/shell.php'
   
   Option B: File Upload + Path Traversal
   ├─ Upload: shell.php
   └─ Move to: ../../etc/cron.d/malware
   
   Option C: Vulnerable Composer Package
   └─ RCE via third-party dependency

2. EXECUTION IN PHP CONTEXT
   ├─ www-data user
   ├─ disable_functions = EMPTY (can use system())
   └─ open_basedir = NOT SET (can access /etc/)

3. WRITE TO /etc/cron.d/
   ├─ Permissions were 666 (world-writable)
   └─ Created: /etc/cron.d/auto-upgrade

4. PRIVILEGE ESCALATION
   ├─ Cron daemon executes as root
   └─ Downloads malware from C2 server

5. IMPACT
   ├─ Process running as root
   ├─ Cryptocurrency mining
   └─ 100% CPU usage
```

---

## Recommendations Summary

| Priority | Action | Timeline | Owner |
|----------|--------|----------|-------|
| 🔴 CRITICAL | Apply PHP hardening fixes | NOW | DevOps |
| 🔴 CRITICAL | Rotate SSH credentials | TODAY | DevOps |
| 🔴 CRITICAL | Review application access logs | TODAY | Security |
| 🟠 HIGH | Fix path traversal in /avatars | TODAY | Developer |
| 🟠 HIGH | Deploy and test WAF rules | TODAY | DevOps |
| 🟠 HIGH | Implement config change monitoring | TODAY | DevOps |
| 🟡 MEDIUM | Full code security review | WEEK | Developer |
| 🟡 MEDIUM | Update Composer dependencies | WEEK | Developer |

---

## Next Steps

1. **Execute the remediation script** immediately
2. **Verify fixes** with the testing commands above
3. **Document all changes** in change management
4. **Monitor for any issues** after applying fixes
5. **Schedule follow-up audit** in 7 days

The server must NOT go back online until ALL hardening is verified.

---

**Report Generated:** Feb 6, 2026 23:07 UTC  
**Next Audit:** Feb 13, 2026
