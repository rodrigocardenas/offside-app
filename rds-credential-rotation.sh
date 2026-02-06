#!/bin/bash

# 🔐 CREDENTIAL ROTATION FOR RDS - AWS Database
# This script correctly rotates credentials for Amazon RDS MySQL

set -e

COLOR_GREEN='\033[0;32m'
COLOR_YELLOW='\033[1;33m'
COLOR_BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${COLOR_BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${COLOR_BLUE}║        🔐 AWS RDS CREDENTIAL ROTATION                          ║${NC}"
echo -e "${COLOR_BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# ============ CONFIGURATION ============
RDS_ENDPOINT="database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com"
RDS_PORT="3306"
RDS_USER="admin"
RDS_DATABASE="offsideclub"
RDS_CURRENT_PASSWORD="offside.2025"  # CURRENT PASSWORD
ENV_FILE="/var/www/html/offside-app/.env"

echo -e "${COLOR_YELLOW}⚠️  IMPORTANT: RDS password change requires AWS Management Console${NC}"
echo ""
echo "Manual steps to rotate RDS password:"
echo ""
echo "1. Go to AWS Console → RDS → Databases"
echo "2. Select: database-1"
echo "3. Click 'Modify'"
echo "4. Scroll to 'Master password'"
echo "5. Uncheck 'Apply immediately'"
echo "6. Enter NEW password (recommend: $(openssl rand -base64 16))"
echo "7. Click 'Continue' then 'Modify DB Instance'"
echo "8. Wait for password change in maintenance window or apply immediately"
echo ""
echo "Once RDS password is updated, update the .env file below:"
echo ""

# ============ INTERACTIVE SETUP ============
read -p "Enter the NEW RDS password (or press Enter to skip): " NEW_PASSWORD

if [ -z "$NEW_PASSWORD" ]; then
    echo "Skipping password update..."
    exit 0
fi

echo ""
echo -e "${COLOR_YELLOW}Updating .env with new RDS password...${NC}"

# Backup current .env
sudo cp "$ENV_FILE" "$ENV_FILE.backup-rds-$(date +%Y%m%d_%H%M%S)"

# Update password in .env
sudo sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$NEW_PASSWORD/" "$ENV_FILE"

# Ensure proper permissions
sudo chown www-data:www-data "$ENV_FILE"
sudo chmod 640 "$ENV_FILE"

echo -e "${COLOR_GREEN}✅ .env updated${NC}"
echo ""

# ============ CLEAR CACHES ============
echo -e "${COLOR_YELLOW}Clearing application cache...${NC}"

cd /var/www/html/offside-app

php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo -e "${COLOR_GREEN}✅ Cache cleared${NC}"
echo ""

# ============ VERIFY CONNECTION ============
echo -e "${COLOR_YELLOW}Verifying database connection...${NC}"

# Test connection with new password
if mysql -h "$RDS_ENDPOINT" -P "$RDS_PORT" -u "$RDS_USER" -p"$NEW_PASSWORD" "$RDS_DATABASE" -e "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${COLOR_GREEN}✅ Connection successful!${NC}"
else
    echo -e "⚠️  Connection failed. Check password is correct."
    echo "   Reverting .env..."
    sudo cp "$ENV_FILE.backup-rds-$(date +%Y%m%d_%H%M%S)" "$ENV_FILE"
    exit 1
fi

echo ""
echo -e "${COLOR_GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${COLOR_GREEN}║            ✅ RDS PASSWORD ROTATION COMPLETE                    ║${NC}"
echo -e "${COLOR_GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo "Summary:"
echo "   ✅ RDS password updated"
echo "   ✅ .env file updated"
echo "   ✅ Cache cleared"
echo "   ✅ Connection verified"
echo ""
