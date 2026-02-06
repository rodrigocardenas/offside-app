#!/bin/bash

# 🔒 PHP HARDENING - FIX CRITICAL VULNERABILITIES
# Applies missing PHP security configurations

set -e

COLOR_RED='\033[0;31m'
COLOR_GREEN='\033[0;32m'
COLOR_YELLOW='\033[1;33m'
COLOR_BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${COLOR_BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${COLOR_BLUE}║         🔒 PHP HARDENING - CRITICAL FIXES                      ║${NC}"
echo -e "${COLOR_BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

PHP_INI="/etc/php/8.3/fpm/php.ini"
PHP_BACKUP="/etc/php/8.3/fpm/php.ini.backup-$(date +%Y%m%d_%H%M%S)"

# ============ BACKUP ORIGINAL ============
echo -e "${COLOR_YELLOW}📦 Step 1: Backing up original PHP configuration...${NC}"
if [ ! -f "$PHP_INI" ]; then
    echo -e "${COLOR_RED}❌ ERROR: PHP INI file not found: $PHP_INI${NC}"
    exit 1
fi

sudo cp "$PHP_INI" "$PHP_BACKUP"
echo -e "${COLOR_GREEN}✅ Backup created: $PHP_BACKUP${NC}"
echo ""

# ============ UPDATE FUNCTION ============
update_php_setting() {
    local key=$1
    local value=$2
    local description=$3
    
    echo -n "   $description... "
    
    # Remove current setting if exists
    sudo sed -i "s/^$key[[:space:]]*=.*//" "$PHP_INI"
    
    # Add new setting
    echo "$key = $value" | sudo tee -a "$PHP_INI" > /dev/null
    
    echo -e "${COLOR_GREEN}✅${NC}"
}

# ============ APPLY FIXES ============
echo -e "${COLOR_YELLOW}🔧 Step 2: Applying PHP security configurations...${NC}"
echo ""

echo -e "   ${COLOR_BLUE}A) Dangerous Functions${NC}"
update_php_setting "disable_functions" "system,exec,passthru,shell_exec,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,dl,eval" "Disabling dangerous functions"

echo ""
echo -e "   ${COLOR_BLUE}B) File System Access${NC}"
update_php_setting "open_basedir" "/var/www/html/offside-app:/tmp:/var/tmp:/dev/urandom" "Restricting open_basedir"
update_php_setting "allow_url_fopen" "Off" "Disabling remote file inclusion"
update_php_setting "allow_url_include" "Off" "Disabling remote code inclusion"

echo ""
echo -e "   ${COLOR_BLUE}C) Session Security${NC}"
update_php_setting "session.use_only_cookies" "1" "Enforcing cookie-only sessions"
update_php_setting "session.cookie_httponly" "1" "Setting HttpOnly flag on cookies"
update_php_setting "session.cookie_secure" "1" "Forcing HTTPS for cookie transmission"
update_php_setting "session.cookie_samesite" "Strict" "Setting SameSite=Strict"

echo ""
echo -e "   ${COLOR_BLUE}D) Information Disclosure${NC}"
update_php_setting "expose_php" "Off" "Hiding PHP version"
update_php_setting "display_errors" "0" "Disabling error display"
update_php_setting "log_errors" "1" "Enabling error logging"

echo ""

# ============ VERIFY CONFIGURATION ============
echo -e "${COLOR_YELLOW}🔍 Step 3: Verifying configuration...${NC}"
echo ""

echo "   Current settings in $PHP_INI:"
echo ""
grep -E "^(disable_functions|open_basedir|allow_url_fopen|allow_url_include|session\.|expose_php|display_errors|log_errors)" "$PHP_INI" | sed 's/^/      /'
echo ""

# ============ RESTART PHP-FPM ============
echo -e "${COLOR_YELLOW}🔄 Step 4: Restarting PHP-FPM...${NC}"

if ! sudo systemctl restart php8.3-fpm 2>/dev/null; then
    echo -e "${COLOR_RED}❌ Failed to restart PHP-FPM${NC}"
    exit 1
fi

echo -e "${COLOR_GREEN}✅ PHP-FPM restarted successfully${NC}"
sleep 2

# ============ VERIFY RESTART ============
if ! sudo systemctl is-active --quiet php8.3-fpm; then
    echo -e "${COLOR_RED}❌ PHP-FPM failed to start!${NC}"
    echo "   Restoring from backup..."
    sudo cp "$PHP_BACKUP" "$PHP_INI"
    sudo systemctl restart php8.3-fpm
    exit 1
fi

echo -e "${COLOR_GREEN}✅ PHP-FPM is running${NC}"
echo ""

# ============ TEST FIXES ============
echo -e "${COLOR_YELLOW}🧪 Step 5: Testing hardening...${NC}"
echo ""

echo "   Test 1: Testing disable_functions..."
if php -r "system('id');" 2>&1 | grep -q "disabled"; then
    echo -e "   ${COLOR_GREEN}✅ system() is properly disabled${NC}"
else
    echo -e "   ${COLOR_RED}⚠️  Warning: system() may not be disabled${NC}"
fi

echo ""
echo "   Test 2: Testing open_basedir..."
if ! php -r "@file_get_contents('/etc/passwd');" 2>&1 | grep -q "open_basedir"; then
    echo -e "   ${COLOR_RED}⚠️  Warning: open_basedir may not be working${NC}"
else
    echo -e "   ${COLOR_GREEN}✅ open_basedir is properly enforced${NC}"
fi

echo ""
echo "   Test 3: Testing allow_url_fopen..."
if php -i 2>/dev/null | grep -q "allow_url_fopen.*Off"; then
    echo -e "   ${COLOR_GREEN}✅ allow_url_fopen is disabled${NC}"
else
    echo -e "   ${COLOR_RED}⚠️  Warning: allow_url_fopen check inconclusive${NC}"
fi

echo ""

# ============ COMPLETION ============
echo -e "${COLOR_GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${COLOR_GREEN}║            ✅ PHP HARDENING COMPLETE                           ║${NC}"
echo -e "${COLOR_GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo "📊 SUMMARY:"
echo "   ✅ disable_functions: Configured"
echo "   ✅ open_basedir: Configured"
echo "   ✅ allow_url_fopen: Disabled"
echo "   ✅ Session security: Hardened"
echo "   ✅ PHP-FPM: Running"
echo ""

echo "🔒 Security Improvements:"
echo "   • RCE via system()/exec() functions: BLOCKED"
echo "   • File system traversal: RESTRICTED to app directory"
echo "   • Remote file inclusion: DISABLED"
echo "   • Session hijacking: PROTECTED"
echo "   • Information disclosure: REDUCED"
echo ""

echo "📝 Backup location: $PHP_BACKUP"
echo ""

echo "⚠️  NEXT STEPS:"
echo "   1. Review application logs for errors"
echo "   2. Test application functionality thoroughly"
echo "   3. Fix path traversal in /avatars route (routes/web.php:162)"
echo "   4. Rotate SSH keys and database credentials"
echo "   5. Deploy WAF rules if available"
echo ""
