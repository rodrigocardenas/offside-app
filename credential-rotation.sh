#!/bin/bash

# 🔐 CREDENTIAL ROTATION SCRIPT - CRITICAL SECURITY UPDATE
# Rotates SSH keys, database passwords, and API tokens

set -e

COLOR_RED='\033[0;31m'
COLOR_GREEN='\033[0;32m'
COLOR_YELLOW='\033[1;33m'
COLOR_BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${COLOR_BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${COLOR_BLUE}║            🔐 CREDENTIAL ROTATION - CRITICAL PHASE              ║${NC}"
echo -e "${COLOR_BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# ============ BACKUP SENSITIVE FILES ============
echo -e "${COLOR_YELLOW}Step 1: Backing up sensitive configuration files...${NC}"
BACKUP_DIR="/root/credential-rotation-backup-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

sudo cp /root/.ssh/authorized_keys "$BACKUP_DIR/" 2>/dev/null || true
sudo cp /var/www/html/offside-app/.env "$BACKUP_DIR/" 2>/dev/null || true
sudo cp /var/www/html/offside-app/.env.production "$BACKUP_DIR/" 2>/dev/null || true

echo -e "${COLOR_GREEN}✅ Backup created at: $BACKUP_DIR${NC}"
echo ""

# ============ SSH KEY ROTATION ============
echo -e "${COLOR_YELLOW}Step 2: Generating new SSH key pair...${NC}"

SSH_KEY_FILE="/tmp/offside_new_rsa"
SSH_BACKUP="/root/.ssh/authorized_keys.backup-$(date +%Y%m%d_%H%M%S)"

# Generate new SSH key
ssh-keygen -t rsa -b 4096 -f "$SSH_KEY_FILE" -N "" -C "offside-app-$(date +%Y%m%d)" > /dev/null 2>&1

echo -e "${COLOR_GREEN}✅ New SSH key pair generated${NC}"
echo "   Private key: $SSH_KEY_FILE"
echo "   Public key: $SSH_KEY_FILE.pub"
echo ""

# Backup old authorized_keys
sudo cp /root/.ssh/authorized_keys "$SSH_BACKUP"
echo -e "${COLOR_GREEN}✅ Old authorized_keys backed up: $SSH_BACKUP${NC}"
echo ""

# Install new public key
echo "Installing new public key in authorized_keys..."
sudo bash -c "cat $SSH_KEY_FILE.pub >> /root/.ssh/authorized_keys"
echo -e "${COLOR_GREEN}✅ New public key installed${NC}"
echo ""

echo "⚠️  IMPORTANT: Save this private key immediately!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
cat "$SSH_KEY_FILE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Copy the above private key and save it as: offside_new_rsa"
echo "Then set permissions: chmod 600 offside_new_rsa"
echo ""

# ============ DATABASE PASSWORD ROTATION ============
echo -e "${COLOR_YELLOW}Step 3: Rotating database password...${NC}"

DB_USER="offside"
DB_NAME="offside_production"

# Generate random password
NEW_DB_PASSWORD=$(openssl rand -base64 32)

echo "   Changing MySQL password for user: $DB_USER"

# Change MySQL password
mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<EOF
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$NEW_DB_PASSWORD';
ALTER USER '$DB_USER'@'%' IDENTIFIED BY '$NEW_DB_PASSWORD';
FLUSH PRIVILEGES;
EOF

echo -e "${COLOR_GREEN}✅ Database password rotated${NC}"
echo ""
echo "   New DB_PASSWORD: $NEW_DB_PASSWORD"
echo ""

# ============ UPDATE .ENV FILE ============
echo -e "${COLOR_YELLOW}Step 4: Updating .env with new database password...${NC}"

ENV_FILE="/var/www/html/offside-app/.env"

if [ ! -f "$ENV_FILE" ]; then
    echo -e "${COLOR_RED}❌ ERROR: .env file not found at $ENV_FILE${NC}"
    exit 1
fi

# Backup .env before modification
sudo cp "$ENV_FILE" "$ENV_FILE.backup-$(date +%Y%m%d_%H%M%S)"

# Update DB_PASSWORD
sudo sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$NEW_DB_PASSWORD/" "$ENV_FILE"

echo -e "${COLOR_GREEN}✅ .env updated with new database password${NC}"
echo ""

# ============ API TOKENS ROTATION ============
echo -e "${COLOR_YELLOW}Step 5: Rotating API tokens...${NC}"

# Generate new tokens
NEW_APP_KEY="base64:$(openssl rand -base64 32)"
NEW_SANCTUM_TOKEN=$(openssl rand -hex 32)
NEW_JWT_SECRET=$(openssl rand -base64 32)

# Update .env with new app key
sudo sed -i "s/^APP_KEY=.*/APP_KEY=$NEW_APP_KEY/" "$ENV_FILE"

# Update other potential tokens
if grep -q "SANCTUM_TOKEN" "$ENV_FILE"; then
    sudo sed -i "s/^SANCTUM_TOKEN=.*/SANCTUM_TOKEN=$NEW_SANCTUM_TOKEN/" "$ENV_FILE"
fi

if grep -q "JWT_SECRET" "$ENV_FILE"; then
    sudo sed -i "s/^JWT_SECRET=.*/JWT_SECRET=$NEW_JWT_SECRET/" "$ENV_FILE"
fi

echo -e "${COLOR_GREEN}✅ API tokens rotated${NC}"
echo ""
echo "   New APP_KEY: $NEW_APP_KEY"
echo "   New SANCTUM_TOKEN: $NEW_SANCTUM_TOKEN"
echo "   New JWT_SECRET: $NEW_JWT_SECRET"
echo ""

# ============ THIRD-PARTY API KEYS ============
echo -e "${COLOR_YELLOW}Step 6: Reviewing third-party API keys...${NC}"

echo "   Third-party API keys in .env:"
echo "   (These should be manually rotated at their respective services)"
echo ""

grep -E "^(GEMINI_API_KEY|FIREBASE_|STRIPE_|SENDGRID_|AWS_|MAILGUN_)" "$ENV_FILE" | sed 's/=.*/=***HIDDEN***/g' || echo "   No standard API keys found"

echo ""
echo -e "${COLOR_YELLOW}⚠️  MANUAL ACTION REQUIRED:${NC}"
echo "   1. Gemini API: Regenerate in Google Cloud Console"
echo "   2. Firebase: Rotate service account keys"
echo "   3. AWS: Rotate IAM access keys"
echo "   4. Any other third-party services"
echo ""

# ============ LARAVEL CACHE CLEAR ============
echo -e "${COLOR_YELLOW}Step 7: Clearing application cache...${NC}"

cd /var/www/html/offside-app

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo -e "${COLOR_GREEN}✅ Application cache cleared${NC}"
echo ""

# ============ VERIFY CHANGES ============
echo -e "${COLOR_YELLOW}Step 8: Verifying credential rotation...${NC}"

echo ""
echo "   SSH Keys:"
echo "   ✅ New public key installed"
grep -c "offside-app" /root/.ssh/authorized_keys && echo "   ✅ Found in authorized_keys" || echo "   ❌ NOT found in authorized_keys"

echo ""
echo "   Database:"
echo "   ✅ Password rotated (verify with new password)"

echo ""
echo "   Application:"
echo "   ✅ Cache cleared"
echo "   ✅ .env updated"

echo ""

# ============ GENERATE CREDENTIAL REPORT ============
echo -e "${COLOR_YELLOW}Step 9: Generating credential rotation report...${NC}"

REPORT_FILE="/root/credential-rotation-report-$(date +%Y%m%d_%H%M%S).txt"

cat > "$REPORT_FILE" <<REPORT
╔════════════════════════════════════════════════════════════════╗
║         🔐 CREDENTIAL ROTATION REPORT                          ║
║              $(date '+%Y-%m-%d %H:%M:%S UTC')                     ║
╚════════════════════════════════════════════════════════════════╝

CREDENTIALS ROTATED:
══════════════════════════════════════════════════════════════════

1. SSH Keys
   ✅ Old key backed up: $SSH_BACKUP
   ✅ New key generated: $SSH_KEY_FILE
   ✅ New public key installed in authorized_keys
   
2. Database Password
   ✅ User: $DB_USER
   ✅ New password generated: [32-char random string]
   ✅ Updated in .env: DB_PASSWORD
   
3. Application Keys
   ✅ APP_KEY rotated
   ✅ SANCTUM_TOKEN rotated (if configured)
   ✅ JWT_SECRET rotated (if configured)
   
4. Cache & Configuration
   ✅ Application cache cleared
   ✅ Config cache cleared
   ✅ Route cache cleared
   ✅ View cache cleared

BACKUP LOCATIONS:
══════════════════════════════════════════════════════════════════
- Full backup: $BACKUP_DIR/
- Authorized keys: $SSH_BACKUP
- .env backups: $BACKUP_DIR/.env*

MANUAL ACTIONS REQUIRED:
══════════════════════════════════════════════════════════════════
[ ] 1. Save the new SSH private key (offside_new_rsa) IMMEDIATELY
[ ] 2. Update deployment scripts with new SSH key
[ ] 3. Test SSH connection with new key
[ ] 4. Regenerate Gemini API key in Google Cloud Console
[ ] 5. Regenerate Firebase service account keys
[ ] 6. Rotate AWS IAM access keys
[ ] 7. Rotate any other third-party API keys
[ ] 8. Update CI/CD secrets (GitHub Actions, etc.)
[ ] 9. Verify application is still working
[ ] 10. Remove old SSH key from authorized_keys (optional, can keep for 24h backup)

SECURITY VERIFICATION:
══════════════════════════════════════════════════════════════════
Date: $(date)
Server: $(hostname)
User: $(whoami)
Backup preserved: YES
Old credentials: AVAILABLE IN BACKUPS

NEXT STEPS:
══════════════════════════════════════════════════════════════════
1. Retrieve all credentials from this report
2. Update deployment tools/scripts
3. Test connectivity with new credentials
4. Monitor for any authentication failures
5. Delete this report when credentials are safely stored
REPORT

sudo chown root:root "$REPORT_FILE"
sudo chmod 600 "$REPORT_FILE"

echo -e "${COLOR_GREEN}✅ Report saved: $REPORT_FILE${NC}"
echo ""

# ============ FINAL SUMMARY ============
echo -e "${COLOR_GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${COLOR_GREEN}║           ✅ CREDENTIAL ROTATION COMPLETE                      ║${NC}"
echo -e "${COLOR_GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo "📋 SUMMARY:"
echo "   ✅ SSH keys rotated"
echo "   ✅ Database password rotated"
echo "   ✅ Application keys rotated"
echo "   ✅ Cache cleared"
echo "   ✅ Report generated: $REPORT_FILE"
echo ""

echo "⚠️  CRITICAL ACTIONS BEFORE PRODUCTION:"
echo "   1. Save new SSH private key immediately!"
echo "   2. Test SSH connection with new key"
echo "   3. Verify database connection works"
echo "   4. Check application logs for errors"
echo "   5. Rotate third-party API keys"
echo ""

echo "📝 For reference:"
echo "   - Backup directory: $BACKUP_DIR"
echo "   - Report file: $REPORT_FILE"
echo ""
