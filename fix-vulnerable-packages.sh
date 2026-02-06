#!/bin/bash
# Fix Vulnerable Composer Packages
# Actualizar paquetes vulnerables identificados en composer audit

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

APP_PATH="/var/www/html/offside-app"

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}🔧 UPDATING VULNERABLE COMPOSER PACKAGES${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""

# List of vulnerable packages that need updating
VULNERABLE_PACKAGES=(
    "symfony/http-foundation"
    "symfony/process"
    "league/commonmark"
    "paragonie/sodium_compat"
    "psy/psysh"
)

cd "$APP_PATH"

echo -e "${YELLOW}Current vulnerable packages:${NC}"
composer audit 2>&1 | grep -E "Package|Severity|CVE" | head -20
echo ""

echo -e "${YELLOW}Updating vulnerable packages...${NC}"
for package in "${VULNERABLE_PACKAGES[@]}"; do
    echo -e "${YELLOW}Updating: $package${NC}"
    composer update "$package" --with-dependencies -n || echo "⚠️  Could not update $package"
done

echo ""
echo -e "${YELLOW}Running composer audit again...${NC}"
composer audit 2>&1 | tail -20

echo ""
echo -e "${GREEN}✅ Vulnerable packages updated${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "  1. Test the application thoroughly"
echo "  2. Commit changes: git add composer.lock"
echo "  3. Push to repository: git push"
echo "  4. Deploy to production"
echo ""
