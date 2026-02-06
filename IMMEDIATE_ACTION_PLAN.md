# 🚨 IMMEDIATE ACTION PLAN - Security Incident Response

**Status:** Post-Incident Remediation  
**Incident:** Cryptocurrency Mining Attack (qpAopmVd)  
**Timeline:** 2024-12-19  

---

## CRITICAL: Do This NOW (Next 2 hours)

### 1. Run Security Audit
```bash
# SSH a producción:
ssh ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com

# Ejecutar audit:
sudo bash /var/www/html/offside-app/security-audit.sh

# Ver resultados:
tail -100 /tmp/security-audit-*.txt
```

**What we're looking for:**
- Vulnerable Composer packages (composer audit)
- Vulnerable NPM packages (npm audit)
- Any CRITICAL or HIGH severity issues

**If found:**
```bash
cd /var/www/html/offside-app
composer audit  # see details
composer update vulnerable-package  # if needed
git add composer.lock
git commit -m "Security: Update vulnerable packages"
git push
./deploy.sh
```

---

### 2. Rotate SSH Keys (CRITICAL)
**Reason:** www-data user had code execution. Could have accessed SSH keys.

```bash
# Local machine:
ssh-keygen -t ed25519 -f ~/.ssh/offside-prod-new -C "offside-prod-$(date +%s)"

# Upload new key:
ssh-copy-id -i ~/.ssh/offside-prod-new.pub ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com

# Disable old key:
# AWS Console → EC2 → Instances → offside-app instance
# → Security tab → Key pairs
# → Create snapshot or remove old key

# Update local config:
# ~/.ssh/config
Host ec2-52-3-65-135.compute-1.amazonaws.com
    HostName ec2-52-3-65-135.compute-1.amazonaws.com
    User ubuntu
    IdentityFile ~/.ssh/offside-prod-new
    IdentitiesOnly yes
```

---

### 3. Rotate RDS Password (CRITICAL)
**Reason:** If www-data compiled credentials, attacker has DB access.

```bash
# AWS Console → RDS → Databases → offside-db
# → Modify → Master password → Generate new password
# → Apply immediately

# Update in .env:
# SSH to prod:
ssh ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com

nano /var/www/html/offside-app/.env
# DB_PASSWORD=new-generated-password

# Restart Laravel (reloader PHP-FPM):
sudo systemctl reload php8.3-fpm
sudo systemctl restart nginx
```

---

### 4. Review Application Logs (HIGH)
**Reason:** Find when/how malware was injected.

```bash
# SSH to prod:
ssh ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com

# Check Laravel logs:
sudo tail -200 /var/www/html/offside-app/storage/logs/laravel.log | grep -i "error\|exception\|warning"

# Check access logs:
sudo tail -500 /var/log/nginx/access.log | grep -E "union|select|--|--execute"

# Check PHP-FPM logs:
sudo tail -100 /var/log/php-fpm/*.log | grep -i "fatal\|error"

# Check system logs:
sudo journalctl -u nginx -n 50
sudo journalctl -u php8.3-fpm -n 50
```

**What to look for:**
- 404 on unusual routes
- POST requests to weird paths
- Errors mentioning file_put_contents, system, exec
- Errors mentioning /etc/cron.d

---

### 5. Verify Hardening is Applied (MEDIUM)

```bash
# SSH to prod:
ssh ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com

# Check cron permissions:
ls -la /etc/cron.d/.placeholder
# Should be: -rw-r--r-- (644)
# NOT: -rw-rw-rw- (666)

# Check PHP hardening:
grep "disable_functions" /etc/php/8.3/fpm/php.ini
# Should show: disable_functions = system,exec,passthru,shell_exec...

# Check open_basedir:
grep "open_basedir" /etc/php/8.3/fpm/php.ini
# Should show: open_basedir = /var/www/html/offside-app:/tmp...

# If any missing, run:
sudo bash /var/www/html/offside-app/hardening-security.sh
```

---

## HIGH PRIORITY: Do This Today

### 6. Implement PHP Hardening
If not already done by hardening script:

```bash
# SSH to prod:
sudo nano /etc/php/8.3/fpm/php.ini

# Add these lines:
disable_functions = system,exec,passthru,shell_exec,proc_open,popen,curl_exec,curl_multi_exec,dl,eval,create_function

open_basedir = /var/www/html/offside-app:/tmp:/var/tmp:/dev/urandom

allow_url_fopen = Off
allow_url_include = Off

# Session security:
session.use_only_cookies = On
session.cookie_httponly = On
session.cookie_secure = On
session.cookie_samesite = Strict

# Restart PHP-FPM:
sudo systemctl reload php8.3-fpm
```

---

### 7. Set Up Monitoring & Alerting

```bash
# Install auditd (if not already installed):
sudo apt-get install auditd

# Monitor cron changes:
sudo auditctl -w /etc/cron.d/ -p wa -k cron_changes
sudo auditctl -w /etc/crontab -p wa -k crontab_changes

# Monitor app directory:
sudo auditctl -w /var/www/html/offside-app -p wa -k app_changes

# View audit logs:
sudo tail -f /var/log/audit/audit.log

# Search for events:
sudo ausearch -k cron_changes
```

---

### 8. Check Outbound Network Connections

```bash
# SSH to prod:
ssh ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com

# Show established connections:
sudo netstat -tlnp | grep ESTABLISHED

# Show listening ports:
sudo netstat -tlnp | grep LISTEN

# Look for suspicious IPs:
# Check AWS Security Groups → offside-app instance → Outbound rules
# Should restrict to specific destinations
```

---

## MEDIUM PRIORITY: This Week

### 9. Full Dependency Audit
```bash
# SSH to prod:
cd /var/www/html/offside-app

# Check all packages:
composer show --outdated
npm list --outdated

# Create detailed report:
composer audit --format=json > /tmp/composer-audit.json
npm audit --json > /tmp/npm-audit.json
```

**For each CRITICAL vulnerability:**
1. Update the package: `composer require package:^newversion`
2. Test locally
3. Push to git
4. Deploy to prod
5. Verify application works

---

### 10. Historical Breach Investigation

```bash
# SSH to prod:

# Find all cron jobs that were created recently:
sudo find /etc/cron.d/ -type f -mtime -30 2>/dev/null | xargs -I {} sh -c 'echo "=== {} ===" && cat {}'

# Find all scripts modified recently:
sudo find /var/www/html/offside-app -type f -name "*.php" -mtime -30 2>/dev/null

# Check if there are shell scripts that shouldn't be there:
sudo find /var/www/html/offside-app -type f -name "*.sh" -mtime -30 2>/dev/null

# Review the malware signature:
sudo grep -r "qpAopmVd\|xmr\|cryptonight" /var/log/ 2>/dev/null || echo "Not found in logs"
```

---

### 11. Implement Web Application Firewall (WAF)

**Option A: ModSecurity (On-server)**
```bash
sudo apt-get install nginx-module-modsecurity
# Complex configuration, see docs

# Enable OWASP Core Rule Set
```

**Option B: AWS WAF (Easier)**
```
AWS Console → WAF & Shield → Create web ACL
→ Add rules for:
   - SQL Injection
   - XSS
   - Path Traversal
   - Rate limiting
→ Associate with CloudFront distribution
```

**Option C: Cloudflare (Easiest for DNS)**
```
Switch DNS to Cloudflare
→ Enable Web Application Firewall
→ Enable bot protection
→ Enable DDoS protection
```

---

## LONGER TERM: Next 2 Weeks

### 12. Security Infrastructure

```bash
# Install file integrity monitoring:
sudo apt-get install aide

# Initialize baseline:
sudo aide --init

# Create daily check:
echo "0 2 * * * root aide --check" | sudo tee -a /etc/cron.d/aide

# Container security (if moving to Docker):
# - Scan images for vulnerabilities
# - Use minimal base images
# - Run as non-root user
```

---

### 13. Credential Rotation Schedule

Create a policy:
```
Every 90 days:
  ☐ Rotate SSH keys
  ☐ Rotate RDS password
  ☐ Rotate API tokens
  ☐ Review access logs
  ☐ Update dependencies
```

---

### 14. Security Training

- Code review process for security
- OWASP Top 10 awareness
- Secure dependency management
- Incident response procedures

---

## VERIFICATION CHECKLIST

After each action, verify:

```bash
✅ No suspicious processes running
   ps aux | grep -E "qpAo|miner|crypto"

✅ CPU usage normal
   top -b -n 1 | head -15

✅ Cron permissions secure
   ls -la /etc/cron.d/.placeholder

✅ PHP hardening applied
   grep "disable_functions" /etc/php/8.3/fpm/php.ini

✅ Application working
   curl https://offside-app.production/api/health

✅ Logs clean
   tail -50 /var/www/html/offside-app/storage/logs/laravel.log

✅ No world-writable sensitive files
   find /var/www/html/offside-app -type f -perm /002 2>/dev/null
```

---

## ESCALATION CONTACTS

If you need help:

**Security Issues:** Contact AWS Support (Professional Support)
**Laravel Issues:** Laravel Community (laracasts.com, laravel.io)
**PHP Issues:** PHP.net Documentation
**Your Team Lead:** [Contact info]

---

## REFERENCE DOCUMENTS

- SECURITY_BREACH_ROOT_CAUSE_ANALYSIS.md (root cause)
- hardening-security.sh (automation)
- security-audit.sh (checking)
- QUICK_FIX_LOGOS.md (production guide)

---

**Last Updated:** 2024-12-19  
**Next Review:** 2024-12-20 (24 hours)  
**Incident Status:** REMEDIATED, POST-INCIDENT REVIEW ONGOING

