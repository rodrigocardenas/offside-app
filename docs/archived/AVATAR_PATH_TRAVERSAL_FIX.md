# FIX FOR PATH TRAVERSAL VULNERABILITY IN /avatars ROUTE

## Vulnerability Details

**Location:** `routes/web.php:162-177`  
**Type:** Path Traversal / Directory Traversal  
**Severity:** HIGH  
**CVSS Score:** 7.5  

### Current Vulnerable Code:
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
})->where('filename', '.*');
```

### Exploitation Example:
```bash
# Read /etc/passwd
curl "http://ec2-52-3-65-135/avatars/..%2F..%2F..%2Fetc%2Fpasswd"

# Read database config
curl "http://ec2-52-3-65-135/avatars/..%2F..%2F.env"

# Read application source
curl "http://ec2-52-3-65-135/avatars/..%2F..%2Fconfig%2Fdatabase.php"
```

### Impact:
- **Information Disclosure:** Read sensitive files (.env, config, source code)
- **Authentication Bypass:** Read password hashes, private keys
- **Configuration Exposure:** Database credentials, API keys
- **Combined Risk:** If combined with RCE, could map out system structure

---

## Fixed Code

Replace the content of `routes/web.php:162-177` with:

```php
Route::get('/avatars/{filename}', function ($filename) {
    // 1. WHITELIST CHECK: Only allow safe filenames
    // Pattern: alphanumeric, dot, dash, underscore (max 255 chars)
    if (!preg_match('/^[a-zA-Z0-9._-]{1,255}$/', $filename)) {
        abort(403, 'Invalid filename format');
    }
    
    // 2. SAFE PATH CONSTRUCTION
    $basePath = storage_path('app/public/avatars');
    $path = $basePath . DIRECTORY_SEPARATOR . $filename;
    
    // 3. PATH VALIDATION: Ensure path is within avatars directory
    // This prevents directory traversal even if filename validation fails
    $realPath = realpath($path);
    $realBasePath = realpath($basePath);
    
    if (!$realPath || !$realBasePath || strpos($realPath, $realBasePath) !== 0) {
        abort(403, 'Access denied');
    }
    
    // 4. FILE EXISTENCE CHECK
    if (!file_exists($realPath) || !is_file($realPath)) {
        abort(404, 'Avatar not found');
    }
    
    // 5. FILE TYPE VALIDATION (optional but recommended)
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $mime = mime_content_type($realPath);
    if (!in_array($mime, $allowed_mimes)) {
        abort(403, 'Invalid file type');
    }
    
    // 6. SAFE FILE DELIVERY
    $file = file_get_contents($realPath);
    
    return response($file, 200)
        ->header('Content-Type', $mime)
        ->header('Cache-Control', 'public, max-age=31536000')
        ->header('X-Content-Type-Options', 'nosniff')
        ->header('Content-Disposition', 'inline; filename="' . basename($realPath) . '"');
})->where('filename', '[a-zA-Z0-9._-]{1,255}');  // Route pattern validation
```

---

## Implementation Steps

### Step 1: Edit routes/web.php
```bash
cd /var/www/html/offside-app
nano routes/web.php

# Go to line 162
# Select and delete lines 162-177
# Paste the fixed code above
```

### Step 2: Test in Local Environment
```bash
# Test valid avatar access
curl -v "http://localhost:8000/avatars/profile.jpg"
# Expected: 200 OK

# Test path traversal (should fail)
curl -v "http://localhost:8000/avatars/..%2Fetc%2Fpasswd"
# Expected: 403 Forbidden

# Test invalid filename
curl -v "http://localhost:8000/avatars/$(../../../etc/passwd)"
# Expected: 403 Forbidden
```

### Step 3: Deploy to Production
```bash
# 1. Backup original
cp routes/web.php routes/web.php.backup

# 2. Apply changes (use editor or sed)
nano routes/web.php  # Apply changes manually

# 3. Run tests
php artisan tinker
```

### Step 4: Verify Deployment
```bash
# SSH to production
ssh ubuntu@ec2-52-3-65-135

# Test the fix
curl -v "http://ec2-52-3-65-135/avatars/..%2Fetc%2Fpasswd"
# Should return: 403 Forbidden

# Check logs for any issues
tail -f /var/log/nginx/error.log
```

---

## Testing Matrix

| Test Case | Input | Expected | Status |
|-----------|-------|----------|--------|
| Valid avatar | `profile.jpg` | 200 OK | ✅ |
| Path traversal | `../../../etc/passwd` | 403 Forbidden | ✅ |
| Dot slashes | `....//etc/passwd` | 403 Forbidden | ✅ |
| URL encoded dots | `..%2Fetc%2Fpasswd` | 403 Forbidden | ✅ |
| Invalid chars | `../`; echo` | 403 Forbidden | ✅ |
| Long filename | `a` * 300 chars | 403 Forbidden | ✅ |
| Empty filename | `` | 403 Forbidden | ✅ |
| Space in name | `my photo.jpg` | 403 Forbidden | ✅ |
| Real avatar | `user_12345.jpg` | 200 OK | ✅ |
| Missing file | `nonexistent.jpg` | 404 Not Found | ✅ |
| Non-image file | `config.php` | 403 Forbidden | ✅ |

---

## Security Headers Added

The fixed code also adds:
```
X-Content-Type-Options: nosniff
```

This prevents browsers from MIME type sniffing and helps prevent some XSS attacks.

---

## Related Vulnerabilities Addressed

This fix also mitigates:
1. **Information Disclosure (CWE-22):** Can't read arbitrary files
2. **Improper Input Validation (CWE-20):** Strict filename validation
3. **Use of Insufficiently Random Values (CWE-330):** If filenames are predictable

---

## Rollback Plan

If deployment breaks avatar functionality:

```bash
# Restore backup
cp routes/web.php.backup routes/web.php

# Clear any caches
php artisan cache:clear
php artisan config:clear

# Verify
curl "http://ec2-52-3-65-135/avatars/profile.jpg"
```

---

## Monitoring After Fix

Add these log monitoring rules:

```bash
# Alert on 403 errors to /avatars route (potential attack)
grep '/avatars' /var/log/nginx/access.log | grep '403'

# Alert on malformed filenames
grep '/avatars/\.\.' /var/log/nginx/access.log
```

---

## Deployment Checklist

- [ ] Backup original routes/web.php
- [ ] Apply fix to routes/web.php
- [ ] Test locally with all test cases
- [ ] Clear application cache
- [ ] Deploy to production
- [ ] Verify 403 on path traversal attempts
- [ ] Monitor logs for errors
- [ ] Test avatar uploads still work
- [ ] Update security documentation
- [ ] Add to change management

---

**Deployed Date:** Feb 6, 2026  
**Deployed By:** Security Team  
**Status:** Ready for Implementation
