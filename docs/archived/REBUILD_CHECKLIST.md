# ✅ REBUILD CHECKLIST - OFFSIDE CLUB

## PRE-REBUILD (COMPLETADO)
- [x] **Feb 6:** Calendar timezone bug fixed
- [x] **Feb 6-7:** RCE security audit (4 vulnerabilities patched)
- [x] **Feb 7:** PHP hardening deployed
- [x] **Feb 7:** CVE-2026-24765 (PHPUnit) patched
- [x] **Feb 7:** Avatar upload fixed (permissions 644)
- [x] **Feb 8:** Max upload size increased to 100MB
- [x] **Feb 8:** Malware process killed (0k1dfZVi)
- [x] **Feb 8:** Backups completed:
  - [x] Storage backup: 600+ MB downloaded
  - [x] Database backup: 27 KB (db-backup.sql)
  - [x] Configuration: .env and composer.lock backed up
- [x] **User Authorization:** "si, te autorizo" ✅ + "continua" ✅

---

## PHASE 2: CREATE NEW EC2 INSTANCE

- [ ] **Step 1:** Open AWS Console
  - URL: https://console.aws.amazon.com
  - Region: **us-east-1** ⚠️ CRITICAL

- [ ] **Step 2:** Navigate to EC2 > Instances

- [ ] **Step 3:** Click "Launch Instances"

- [ ] **Step 4:** Configure Instance
  - [ ] Name: `offside-app-clean-rebuild`
  - [ ] AMI: Ubuntu 24.04 LTS (check for latest)
  - [ ] Instance Type: `t3.medium` (2 vCPU, 4 GB RAM)
  - [ ] Key Pair: **offside** ⚠️ CRITICAL
  - [ ] VPC/Subnet: same as current server
  - [ ] Security Group: **select EXISTING** (allows HTTP/HTTPS)
  - [ ] Storage: 30 GB, gp3
  - [ ] Auto-assign Public IP: ✅ ENABLED

- [ ] **Step 5:** Review and "Launch Instance"

- [ ] **Step 6:** Wait 2-3 minutes for instance to boot

- [ ] **Step 7:** Verify SSH Connection
  ```bash
  ssh -i ~/aws/offside.pem ubuntu@<PUBLIC_IP>
  ```
  **RECORD HERE:**
  ```
  Instance ID: ____________________
  Public IP:   ____________________
  Private IP:  ____________________
  ```

**⏱️ Estimated Time: 5-10 minutes**

---

## PHASE 3: INSTALL CLEAN STACK

**Before:** Instance running, SSH accessible ✅

- [ ] **Step 1:** Update System
  ```bash
  sudo apt-get update && sudo apt-get upgrade -y
  ```

- [ ] **Step 2:** Install PHP 8.3
  ```bash
  sudo apt-get install -y php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-redis php8.3-gd php8.3-curl \
    php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath
  ```

- [ ] **Step 3:** Install Nginx
  ```bash
  sudo apt-get install -y nginx
  ```

- [ ] **Step 4:** Install Redis
  ```bash
  sudo apt-get install -y redis-server
  ```

- [ ] **Step 5:** Install Node.js 20
  ```bash
  curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
  sudo apt-get install -y nodejs
  ```

- [ ] **Step 6:** Install MySQL Client
  ```bash
  sudo apt-get install -y mysql-client-8.0
  ```

- [ ] **Step 7:** Install Composer
  ```bash
  curl -sS https://getcomposer.org/installer | php
  sudo mv composer.phar /usr/local/bin/composer
  ```

- [ ] **Step 8:** Enable Services
  ```bash
  sudo systemctl enable php8.3-fpm nginx redis-server
  sudo systemctl start php8.3-fpm nginx redis-server
  ```

- [ ] **Step 9:** Verify Installation
  ```bash
  php -v
  nginx -v
  redis-cli ping
  node -v
  ```

**⏱️ Estimated Time: 10-15 minutes**

---

## PHASE 4: RESTORE DATA

**Before:** All software installed ✅

### 4A: Restore Database

- [ ] **Step 1:** Copy Database Backup
  ```bash
  scp -i ~/aws/offside.pem db-backup.sql ubuntu@<PUBLIC_IP>:/tmp/
  ```

- [ ] **Step 2:** Drop and Recreate DB
  ```bash
  ssh -i ~/aws/offside.pem ubuntu@<PUBLIC_IP>
  export MYSQL_PWD="offside.2025"
  mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin << EOF
  DROP DATABASE IF EXISTS offsideclub;
  CREATE DATABASE offsideclub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  EOF
  ```

- [ ] **Step 3:** Import Database
  ```bash
  export MYSQL_PWD="offside.2025"
  mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin \
    offsideclub < /tmp/db-backup.sql
  ```

- [ ] **Step 4:** Verify Database
  ```bash
  export MYSQL_PWD="offside.2025"
  mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin \
    offsideclub -e "SELECT COUNT(*) as user_count FROM users;"
  ```
  **Expected Output:** `user_count | <number>`

### 4B: Restore Storage

- [ ] **Step 1:** Create Storage Directory
  ```bash
  sudo mkdir -p /var/www/html/offside-app/storage/app/public
  sudo chown -R ubuntu:ubuntu /var/www/html/offside-app
  ```

- [ ] **Step 2:** Copy Storage Backup
  ```bash
  scp -i ~/aws/offside.pem -r backup-storage-20260208 \
    ubuntu@<PUBLIC_IP>:/tmp/storage-backup
  ```

- [ ] **Step 3:** Extract Files
  ```bash
  ssh -i ~/aws/offside.pem ubuntu@<PUBLIC_IP>
  cd /var/www/html/offside-app
  cp -r /tmp/storage-backup/* storage/
  ```

- [ ] **Step 4:** Fix Permissions
  ```bash
  chmod -R 755 storage
  chmod 644 storage/app/public/*
  ```

- [ ] **Step 5:** Verify
  ```bash
  ls -la storage/app/public | wc -l
  ```
  **Expected:** Multiple avatar files (JPG/PNG)

**⏱️ Estimated Time: 10-15 minutes**

---

## PHASE 5: DEPLOY APPLICATION CODE

**Before:** Database and storage restored ✅

- [ ] **Step 1:** Clone Repository
  ```bash
  cd /var/www/html
  git clone https://github.com/rodrigocardenas/offside-app.git offside-app
  cd offside-app
  ```

- [ ] **Step 2:** Create .env File
  ```bash
  cat > .env << 'ENVFILE'
  APP_NAME="Offside Club"
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://offsideclub.app
  APP_TIMEZONE="Europe/Madrid"
  
  DB_CONNECTION=mysql
  DB_HOST=database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com
  DB_PORT=3306
  DB_DATABASE=offsideclub
  DB_USERNAME=admin
  DB_PASSWORD=offside.2025
  
  REDIS_HOST=127.0.0.1
  REDIS_PORT=6379
  CACHE_DRIVER=redis
  SESSION_DRIVER=redis
  
  QUEUE_CONNECTION=sync
  MAIL_MAILER=log
  ENVFILE
  ```

- [ ] **Step 3:** Install Composer Dependencies
  ```bash
  composer install --no-dev --optimize-autoloader
  ```

- [ ] **Step 4:** Generate Laravel Key
  ```bash
  php artisan key:generate
  ```

- [ ] **Step 5:** Run Migrations
  ```bash
  php artisan migrate --force
  ```

- [ ] **Step 6:** Clear Cache
  ```bash
  php artisan config:cache
  php artisan cache:clear
  php artisan view:clear
  ```

- [ ] **Step 7:** Build Next.js Landing (if exists)
  ```bash
  cd offside-landing
  npm ci
  npm run build
  cd ..
  ```

- [ ] **Step 8:** Fix Ownership
  ```bash
  sudo chown -R www-data:www-data /var/www/html/offside-app
  chmod -R 755 /var/www/html/offside-app
  chmod -R 755 /var/www/html/offside-app/storage
  ```

**⏱️ Estimated Time: 10-15 minutes**

---

## PHASE 6: CONFIGURE NGINX

**Before:** Application code deployed ✅

- [ ] **Step 1:** Create Nginx Config
  ```bash
  sudo tee /etc/nginx/sites-available/offside-app > /dev/null << 'NGINX'
  server {
      listen 80;
      server_name _;
      root /var/www/html/offside-app/public;
      
      index index.php;
      
      location / {
          try_files $uri $uri/ /index.php?$query_string;
      }
      
      location ~ \.php$ {
          include snippets/fastcgi-php.conf;
          fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
      }
      
      location ~ /\.ht {
          deny all;
      }
  }
  NGINX
  ```

- [ ] **Step 2:** Enable Site
  ```bash
  sudo ln -sf /etc/nginx/sites-available/offside-app /etc/nginx/sites-enabled/
  sudo rm -f /etc/nginx/sites-enabled/default
  ```

- [ ] **Step 3:** Test Configuration
  ```bash
  sudo nginx -t
  ```
  **Expected Output:** `syntax is ok` ✅

- [ ] **Step 4:** Reload Nginx
  ```bash
  sudo systemctl reload nginx
  ```

**⏱️ Estimated Time: 5 minutes**

---

## PHASE 7: TESTING

**Before:** Nginx configured ✅

- [ ] **Step 1:** Test PHP
  ```bash
  php -v
  ```
  **Expected:** PHP 8.3.x

- [ ] **Step 2:** Test Database
  ```bash
  export MYSQL_PWD="offside.2025"
  mysql -h database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com -u admin \
    offsideclub -e "SELECT 1;"
  ```
  **Expected:** `1`

- [ ] **Step 3:** Test Redis
  ```bash
  redis-cli ping
  ```
  **Expected:** `PONG`

- [ ] **Step 4:** Test HTTP (from local machine)
  ```bash
  curl -I http://<NEW_PUBLIC_IP>
  ```
  **Expected:** `HTTP/1.1 200 OK` or `HTTP/1.1 500 OK` (app loading)

- [ ] **Step 5:** Test Application
  - Open browser: `http://<NEW_PUBLIC_IP>`
  - Check if page loads
  - Verify no errors in console

- [ ] **Step 6:** Test Login
  - Use credentials from production users table
  - Verify session/Redis working

- [ ] **Step 7:** Test Avatar Display
  - Navigate to profile
  - Verify avatars display correctly
  - Check browser console for no 404 errors

- [ ] **Step 8:** Test Match Calendar
  - Navigate to calendar
  - Verify dates display in Madrid timezone
  - Verify matches show correctly

- [ ] **Step 9:** Monitor Logs
  ```bash
  tail -50 /var/www/html/offside-app/storage/logs/laravel.log
  ```
  **Expected:** No ERROR entries (INFO/DEBUG OK)

**✅ If all tests pass, proceed to migration**

**⏱️ Estimated Time: 15-30 minutes**

---

## PHASE 8: DNS MIGRATION

**Before:** All tests passing ✅, Application stable ✅

- [ ] **Option A: AWS Elastic IP** (fastest)
  - [ ] Go to AWS Console > EC2 > Elastic IPs
  - [ ] Disassociate old IP from compromised instance
  - [ ] Associate with new instance
  - [ ] Wait 1-2 minutes for DNS to update

- [ ] **Option B: Route53 DNS Change**
  - [ ] Go to AWS Console > Route53 > Hosted Zones
  - [ ] Find A record pointing to old IP
  - [ ] Click Edit
  - [ ] Change to new public IP
  - [ ] Save
  - [ ] Wait up to 5 minutes for propagation

- [ ] **Option C: Application Load Balancer** (if used)
  - [ ] Go to AWS Console > EC2 > Load Balancers
  - [ ] Select balancer
  - [ ] Edit Target Group
  - [ ] Remove old instance
  - [ ] Add new instance
  - [ ] Wait for health checks to pass

- [ ] **Step 1:** Monitor Transition
  - [ ] Watch server logs for new connections
  - [ ] Monitor AWS CloudWatch metrics
  - [ ] Check application health

- [ ] **Step 2:** Verify Traffic Flowing to New Instance
  ```bash
  # From any machine
  curl -H "User-Agent: Mozilla" http://offsideclub.app/api/health | jq .
  ```

- [ ] **Step 3:** Monitor Old Instance (keep for 1-2 hours as backup)
  - [ ] Watch CPU/Memory metrics
  - [ ] Should be near 0% after DNS switches

**⏱️ Estimated Time: 5-10 minutes**

---

## PHASE 9: CLEANUP & SECURITY HARDENING

**Before:** New instance stable, users accessing normally ✅

- [ ] **Step 1:** Verify New Instance Stability (1-2 hours)
  - [ ] Monitor error logs
  - [ ] Check database queries
  - [ ] Verify user sessions
  - [ ] Test all main features

- [ ] **Step 2:** Apply PHP Hardening** (CRITICAL)
  ```bash
  ssh -i ~/aws/offside.pem ubuntu@<NEW_IP>
  # Copy hardening script from old server or create new
  bash php-hardening-fix.sh
  ```

- [ ] **Step 3:** Install Security Tools
  ```bash
  # Fail2ban
  sudo apt-get install -y fail2ban
  sudo systemctl enable fail2ban
  
  # Certbot for HTTPS
  sudo apt-get install -y certbot python3-certbot-nginx
  ```

- [ ] **Step 4:** Set Up HTTPS with Let's Encrypt**
  ```bash
  sudo certbot certonly --nginx -d offsideclub.app -d www.offsideclub.app
  ```

- [ ] **Step 5:** Rotate RDS Credentials**
  - [ ] Go to AWS RDS Console
  - [ ] Modify offsideclub database
  - [ ] Change master password
  - [ ] Update .env on new instance
  - [ ] Restart PHP-FPM

- [ ] **Step 6:** Create New APP_KEY**
  ```bash
  cd /var/www/html/offside-app
  php artisan key:generate
  ```

- [ ] **Step 7:** Terminate Old Instance**
  - [ ] Go to AWS Console > EC2 > Instances
  - [ ] Select old instance (52.3.65.135)
  - [ ] Right-click > Terminate Instance
  - [ ] Confirm
  - ⚠️ **AFTER 5-10 MINUTES OF VERIFICATION ONLY!**

- [ ] **Step 8:** Optional: Create AMI Snapshot**
  ```bash
  # For faster rebuilds in future
  # AWS Console > EC2 > Instances > right-click > Image and templates > Create image
  # Name: offside-app-clean-base-20260208
  ```

**⏱️ Estimated Time: 30-45 minutes (including verification period)**

---

## FINAL VERIFICATION

After all phases complete:

- [ ] **Users can login** ✅
- [ ] **Avatars display correctly** ✅
- [ ] **Calendar shows Madrid timezone** ✅
- [ ] **Database queries fast** ✅
- [ ] **Redis caching works** ✅
- [ ] **No malware processes** (`ps aux` clean) ✅
- [ ] **Permissions stable** (not resetting) ✅
- [ ] **Logs show no errors** ✅
- [ ] **Application responsive** ✅
- [ ] **HTTPS working** (if configured) ✅

---

## ROLLBACK PLAN (if needed)

If new instance has issues:
1. Keep old instance running for 24 hours
2. Point DNS back to old IP
3. Investigate issue on new instance
4. Do not terminate old instance yet

---

## TOTAL ESTIMATED TIME
- Phase 2: 5-10 minutes
- Phase 3: 10-15 minutes
- Phase 4: 10-15 minutes
- Phase 5: 10-15 minutes
- Phase 6: 5 minutes
- Phase 7: 15-30 minutes
- Phase 8: 5-10 minutes
- Phase 9: 30-45 minutes (includes verification)

**⏱️ TOTAL: 1 hour 30 minutes to 2 hours 30 minutes**

---

## 🚀 YOU'RE READY TO START!

Next steps:
1. Create new EC2 instance (Phase 2)
2. Follow checklist for each phase
3. Verify thoroughly before moving to next phase
4. Contact support if any step fails

**Good luck! 🎉**
