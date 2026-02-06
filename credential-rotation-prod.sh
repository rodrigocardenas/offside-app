#!/bin/bash

# 🔐 SIMPLIFIED CREDENTIAL ROTATION - For Production
# This is a simplified version that rotates only essential credentials

set -e

COLOR_GREEN='\033[0;32m'
COLOR_YELLOW='\033[1;33m'
NC='\033[0m'

APP_PATH="/var/www/html/offside-app"
ENV_FILE="$APP_PATH/.env"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-}"

echo -e "${COLOR_YELLOW}🔐 Starting credential rotation...${NC}"
echo ""

# ============ BACKUP .env ============
echo "1. Backing up .env..."
sudo cp "$ENV_FILE" "$ENV_FILE.backup-$(date +%Y%m%d_%H%M%S)"
echo -e "${COLOR_GREEN}✅ Backup created${NC}"
echo ""

# ============ DATABASE PASSWORD ============
echo "2. Generating new database password..."
NEW_DB_PASSWORD=$(openssl rand -base64 32)

echo "3. Updating database password..."
if [ ! -z "$MYSQL_ROOT_PASSWORD" ]; then
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e \
        "ALTER USER 'offside'@'localhost' IDENTIFIED BY '$NEW_DB_PASSWORD'; FLUSH PRIVILEGES;" 2>/dev/null || \
        echo "   ⚠️  Could not update MySQL (may need manual update)"
else
    echo "   ⚠️  MYSQL_ROOT_PASSWORD not set. Skipping MySQL update."
    echo "   Manual update required:"
    echo "   mysql -u root -p"
    echo "   ALTER USER 'offside'@'localhost' IDENTIFIED BY '$NEW_DB_PASSWORD';"
fi

echo "4. Updating .env with new database password..."
sudo sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$NEW_DB_PASSWORD/" "$ENV_FILE"
echo -e "${COLOR_GREEN}✅ Database credentials rotated${NC}"
echo ""

# ============ APP KEY ============
echo "5. Rotating application key..."
NEW_APP_KEY="base64:$(openssl rand -base64 32)"
sudo sed -i "s/^APP_KEY=.*/APP_KEY=$NEW_APP_KEY/" "$ENV_FILE"
echo -e "${COLOR_GREEN}✅ APP_KEY rotated${NC}"
echo ""

# ============ CLEAR CACHE ============
echo "6. Clearing application cache..."
cd "$APP_PATH"
php artisan cache:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
echo -e "${COLOR_GREEN}✅ Cache cleared${NC}"
echo ""

# ============ SHOW IMPORTANT CREDENTIALS ============
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 NEW CREDENTIALS (Save these!):"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "DB_PASSWORD: $NEW_DB_PASSWORD"
echo ""
echo "APP_KEY: $NEW_APP_KEY"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo -e "${COLOR_GREEN}✅ CREDENTIAL ROTATION COMPLETE${NC}"
echo ""
echo "⚠️  IMPORTANT:"
echo "   1. Update your local .env with these credentials"
echo "   2. Update CI/CD secrets with new APP_KEY"
echo "   3. Test database connection"
echo "   4. Monitor logs for any issues"
echo ""
