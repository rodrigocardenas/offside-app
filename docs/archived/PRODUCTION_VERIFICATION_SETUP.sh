#!/bin/bash

echo "╔════════════════════════════════════════════════════════════╗"
echo "║ Pre-deployment Verification Job Setup                      ║"
echo "╚════════════════════════════════════════════════════════════╝"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Run migrations
echo -e "\n${YELLOW}[1/4] Running migrations...${NC}"
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migrations completed${NC}"
else
    echo -e "${RED}✗ Migrations failed${NC}"
    exit 1
fi

# Step 2: Clear cache
echo -e "\n${YELLOW}[2/4] Clearing cache...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo -e "${GREEN}✓ Cache cleared${NC}"

# Step 3: Recover old results (sync historical data)
echo -e "\n${YELLOW}[3/4] Syncing historical match data...${NC}"
php artisan app:recover-old-results --days=30
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Historical data synced${NC}"
else
    echo -e "${RED}✗ Historical data sync failed (non-critical, continuing...)${NC}"
fi

# Step 4: Link questions to matches (if needed)
echo -e "\n${YELLOW}[4/4] Linking questions to finished matches...${NC}"
php artisan app:link-questions-to-matches 2>/dev/null || echo -e "${YELLOW}(Skipped if no unlinked questions)${NC}"

echo -e "\n╔════════════════════════════════════════════════════════════╗"
echo -e "║ Setup Complete - Ready to dispatch verification job        ║"
echo -e "╚════════════════════════════════════════════════════════════╝"
echo -e "\nNow you can run:"
echo -e "  ${GREEN}php artisan app:run-verification-job${NC}\n"
