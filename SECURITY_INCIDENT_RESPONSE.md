# 🚨 SECURITY INCIDENT RESPONSE - CRITICAL

## Status: COMPROMISED SERVER DETECTED

**Threat:** Malicious node processes executing remote code injection
**Source IP:** 91.92.243.113:235
**Script:** logic.sh (unknown malware)
**Impact:** Memory exhaustion → 502 errors, potential data breach

---

## IMMEDIATE ACTIONS (PRIORITY 1)

### 1. Kill all malicious processes
```bash
# SSH into production server
ssh ubuntu@your-server-ip

# Find all node processes downloading from suspicious IPs
ps aux | grep -i "http.get\|91.92.243.113"

# Kill ALL node processes (if they're all malicious)
pkill -9 node

# Verify no malicious processes remain
ps aux | grep -v grep | grep node
```

### 2. Find and remove backdoors
```bash
# Search for suspicious files modified in the last 24 hours
find /home/ubuntu -type f -name "*.sh" -mtime -1
find /home/ubuntu -type f -name "*.js" -mtime -1

# Check if there are cron jobs running malicious scripts
crontab -l
sudo crontab -l

# Check /tmp for scripts (common malware location)
ls -la /tmp/ | grep -E "\.sh|\.js"

# Find files with suspicious names
find /home/ubuntu -type f \( -name "logic*" -o -name "*miner*" -o -name "*bot*" -o -name "*crypto*" \)
```

### 3. Check entry points
```bash
# Review recent SSH logins
last -20

# Check web server access logs for suspicious requests
tail -1000 /var/log/nginx/access.log | grep -E "\.php|shell|exec|system"

# Find uploaded files in web directory
find /home/ubuntu/offsideclub -type f -name "*.php" -mtime -7

# Check Laravel storage for suspicious files
ls -la /home/ubuntu/offsideclub/storage/

# Search for eval() or system() calls in recent uploads
grep -r "eval\|system\|exec\|passthru\|shell_exec" /home/ubuntu/offsideclub/app/ --include="*.php"
```

---

## PRIORITY 2: ISOLATION & CONTAINMENT

### 1. Stop the application
```bash
# Put app in maintenance mode
cd /home/ubuntu/offsideclub
php artisan down

# Stop queue workers
sudo systemctl stop offsideclub-queue-worker || pkill -f "artisan queue:work"

# Stop supervisor if running
sudo systemctl stop supervisor || sudo service supervisor stop
```

### 2. Preserve evidence (for forensics)
```bash
# Make a backup of logs BEFORE cleaning
mkdir -p /home/ubuntu/forensics
cp -r /var/log /home/ubuntu/forensics/logs-$(date +%Y%m%d-%H%M%S)
cp -r ~/.bash_history /home/ubuntu/forensics/
```

### 3. Check database integrity
```bash
# Login to MySQL
mysql -u root -p

# Check for suspicious user accounts
SELECT user, host FROM mysql.user;

# Check if database was accessed/modified
SHOW PROCESSLIST;
```

---

## PRIORITY 3: REMEDIATION

### 1. Change ALL credentials
```bash
# 1. MySQL root password
mysql -u root -p
ALTER USER 'root'@'localhost' IDENTIFIED BY 'new-secure-password';
FLUSH PRIVILEGES;

# 2. Laravel .env database credentials
# Edit /home/ubuntu/offsideclub/.env
# Change DB_PASSWORD, DB_HOST, all API keys

# 3. SSH keys (rotate)
ssh-keygen -t ed25519 -f ~/.ssh/id_ed25519_new
# Add new key to authorized_keys, remove old one

# 4. GitHub deploy key
# Regenerate in GitHub settings, add to server

# 5. Firebase credentials
# Check if firebase-adminsdk json was exposed
```

### 2. Clean application files
```bash
# Remove all suspicious PHP files
find /home/ubuntu/offsideclub -name "*.php" -type f -exec ls -la {} \; | grep -E "2025-02-0[345]"

# Check for shell.php, admin.php, etc
find /home/ubuntu/offsideclub -name "*shell*" -o -name "*admin*" -o -name "*bypass*"

# Clear storage caches
rm -rf /home/ubuntu/offsideclub/storage/logs/*
rm -rf /home/ubuntu/offsideclub/bootstrap/cache/*
```

### 3. Disable dangerous PHP functions
```bash
# Edit /etc/php/8.3/fpm/php.ini
sudo nano /etc/php/8.3/fpm/php.ini

# Add/uncomment this line:
disable_functions = "exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,eval"

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

### 4. Firewall rules
```bash
# Block the malicious IP
sudo ufw insert 1 deny from 91.92.243.113

# Only allow specific IPs to SSH (your dev machine)
sudo ufw insert 1 allow from YOUR_IP to any port 22

# Block suspicious outbound connections
sudo iptables -A OUTPUT -d 91.92.243.113 -j DROP
```

---

## PRIORITY 4: VERIFICATION & HARDENING

### 1. Verify clean state
```bash
# No node processes should exist
ps aux | grep node

# No suspicious cron jobs
crontab -l
sudo crontab -l

# File integrity check
find /home/ubuntu/offsideclub -type f -mtime -7 -ls
```

### 2. Enable monitoring
```bash
# Install fail2ban (prevent brute force)
sudo apt-get install fail2ban

# Monitor for suspicious processes
sudo apt-get install aide
sudo aideinit
sudo aide --check

# Real-time file monitoring
sudo apt-get install auditd
sudo auditctl -w /home/ubuntu/offsideclub -p wa -k offsideclub_changes
```

### 3. Harden Laravel
```bash
# Add to .env
APP_DEBUG=false
APP_ENV=production

# Update Laravel
composer update
php artisan config:cache
php artisan route:cache

# Remove dangerous routes/files
```

---

## QUESTIONS FOR YOU

1. **When did this start?** (Check when first 502 error occurred)
2. **Did you notice any unusual activity?** (Suspicious logins, file uploads, etc.)
3. **Do you have automated deployments?** (GitHub Actions, GitLab CI?) → Could be compromised
4. **Are there development branches deployed anywhere?** (Staging server with weaker credentials?)
5. **Did you recently install any new packages?** (Check composer.lock for suspicious repos)

---

## NEXT STEPS

1. ✅ Kill malicious processes
2. ✅ Find and remove backdoors
3. ✅ Change all credentials
4. ✅ Disable dangerous PHP functions
5. ✅ Block malicious IPs
6. ✅ Scan for rootkits (if needed: rkhunter, chkrootkit)
7. ✅ Full system update: `sudo apt update && sudo apt upgrade -y`
8. ✅ Reboot server to ensure clean state
9. ✅ Restore from backup if available
10. ✅ Monitor for 48 hours for re-infection

---

## ADDITIONAL RESOURCES

- Check `/var/log/auth.log` for unauthorized access attempts
- Review `/var/log/syslog` for unusual process spawning
- Check `~/.ssh/authorized_keys` for unauthorized keys
- Review GitHub access logs (Settings → Security → Access logs)

**⏰ Time is critical. Do this ASAP before attacker escalates.**
