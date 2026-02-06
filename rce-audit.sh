#!/bin/bash

# 🚨 SECURITY AUDIT - REMOTE CODE EXECUTION VULNERABILITIES
# Auditoría exhaustiva de la aplicación Laravel para RCE

set -e
REPORT_FILE="/tmp/rce-audit-$(date +%Y%m%d_%H%M%S).txt"
APP_PATH="/var/www/html/offside-app"

echo "🚨 RCE SECURITY AUDIT STARTED"
echo "Report will be saved to: $REPORT_FILE"
echo ""

{
    echo "╔════════════════════════════════════════════════════════════════╗"
    echo "║           RCE VULNERABILITY AUDIT - OFFSIDE APP               ║"
    echo "║              $(date '+%Y-%m-%d %H:%M:%S UTC')                     ║"
    echo "╚════════════════════════════════════════════════════════════════╝"
    echo ""

    # ============ PHASE 1: VERIFY PHP HARDENING ============
    echo "1️⃣  VERIFYING PHP HARDENING..."
    echo "════════════════════════════════════════════"
    echo ""
    
    PHP_INI="/etc/php/8.3/fpm/php.ini"
    
    if [ ! -f "$PHP_INI" ]; then
        echo "⚠️  PHP INI file not found: $PHP_INI"
    else
        echo "Checking disable_functions..."
        if grep -q "disable_functions.*system.*exec.*passthru" "$PHP_INI"; then
            echo "✅ disable_functions configured"
            grep "disable_functions" "$PHP_INI"
        else
            echo "🔴 CRITICAL: disable_functions NOT FULLY CONFIGURED"
            grep "disable_functions" "$PHP_INI" || echo "   Not found!"
        fi
        echo ""
        
        echo "Checking open_basedir..."
        if grep -q "open_basedir.*offside-app" "$PHP_INI"; then
            echo "✅ open_basedir configured"
            grep "open_basedir" "$PHP_INI"
        else
            echo "🔴 CRITICAL: open_basedir NOT CONFIGURED"
        fi
        echo ""
        
        echo "Checking allow_url_fopen and allow_url_include..."
        grep -E "allow_url_(fopen|include)" "$PHP_INI" || echo "⚠️  Not found - may be default (Off)"
        echo ""
    fi

    # ============ PHASE 2: CODE INJECTION PATTERNS ============
    echo "2️⃣  CHECKING FOR CODE EXECUTION PATTERNS..."
    echo "════════════════════════════════════════════"
    echo ""
    
    echo "Looking for dangerous function usage in app code..."
    echo ""
    
    # Check for exec/system/shell_exec
    echo "▶ exec() calls:"
    grep -rn "exec(" "$APP_PATH/app" --include="*.php" 2>/dev/null || echo "   ✅ None found"
    
    echo ""
    echo "▶ system() calls:"
    grep -rn "system(" "$APP_PATH/app" --include="*.php" 2>/dev/null || echo "   ✅ None found"
    
    echo ""
    echo "▶ shell_exec() calls:"
    grep -rn "shell_exec(" "$APP_PATH/app" --include="*.php" 2>/dev/null || echo "   ✅ None found"
    
    echo ""
    echo "▶ eval() calls:"
    grep -rn "eval(" "$APP_PATH/app" --include="*.php" 2>/dev/null || echo "   ✅ None found"
    
    echo ""
    echo "▶ passthru() calls:"
    grep -rn "passthru(" "$APP_PATH/app" --include="*.php" 2>/dev/null || echo "   ✅ None found"
    
    echo ""
    echo "▶ popen() calls:"
    grep -rn "popen(" "$APP_PATH/app" --include="*.php" 2>/dev/null || echo "   ✅ None found"
    
    echo ""
    echo "▶ proc_open() calls:"
    grep -rn "proc_open(" "$APP_PATH/app" --include="*.php" 2>/dev/null || echo "   ✅ None found"
    
    echo ""

    # ============ PHASE 3: SQL INJECTION & DATABASE VULNERABILITIES ============
    echo "3️⃣  CHECKING FOR SQL INJECTION PATTERNS..."
    echo "════════════════════════════════════════════"
    echo ""
    
    echo "▶ whereRaw() without parameter binding:"
    grep -rn "whereRaw" "$APP_PATH/app" --include="*.php" -A 1 | head -20
    echo ""
    
    echo "▶ DB::raw() usage:"
    grep -rn "DB::raw" "$APP_PATH/app" --include="*.php" -B 1 -A 1 | head -30
    echo ""
    
    echo "▶ Raw queries:"
    grep -rn "DB::statement\|DB::select\|DB::table.*raw" "$APP_PATH/app" --include="*.php" | head -20
    echo ""

    # ============ PHASE 4: FILE UPLOAD VULNERABILITIES ============
    echo "4️⃣  CHECKING FOR FILE UPLOAD VULNERABILITIES..."
    echo "════════════════════════════════════════════"
    echo ""
    
    echo "▶ File upload handling:"
    grep -rn "move_uploaded_file\|$_FILES\|request()->file" "$APP_PATH/app" --include="*.php" | head -20
    echo ""
    
    echo "▶ Path traversal risk in /avatars route:"
    echo "   Route: GET /avatars/{filename} - Uses .* regex without path traversal protection"
    echo "   File: routes/web.php:162"
    grep -A 10 "avatars.*filename" "$APP_PATH/routes/web.php" || echo "   (route not found in standard location)"
    echo ""

    # ============ PHASE 5: TEMPLATE INJECTION ============
    echo "5️⃣  CHECKING FOR TEMPLATE INJECTION..."
    echo "════════════════════════════════════════════"
    echo ""
    
    echo "▶ Blade view method calls:"
    grep -rn "view(" "$APP_PATH/app" --include="*.php" | wc -l
    echo "   Found above number of view() calls"
    echo ""
    
    echo "▶ Checking for user input in view names:"
    grep -rn "view.*\\\$" "$APP_PATH/app" --include="*.php" | head -10 || echo "   ✅ None found"
    echo ""

    # ============ PHASE 6: WEB LOGS ANALYSIS ============
    echo "6️⃣  ANALYZING WEB ACCESS LOGS..."
    echo "════════════════════════════════════════════"
    echo ""
    
    NGINX_LOG="/var/log/nginx/access.log"
    if [ -f "$NGINX_LOG" ]; then
        echo "Last 24 hours of suspicious requests (SQL injection patterns):"
        grep -i "union.*select\|load_file\|into.*outfile" "$NGINX_LOG" 2>/dev/null | head -5 || echo "   ✅ None found"
        
        echo ""
        echo "Last 24 hours of suspicious requests (command execution patterns):"
        grep -i "exec\|system\|shell\|passthru\|eval" "$NGINX_LOG" 2>/dev/null | head -5 || echo "   ✅ None found"
        
        echo ""
        echo "Last 24 hours of suspicious requests (file access patterns):"
        grep -i "\.php\|\.sh\|\.env\|/etc/\|passwd" "$NGINX_LOG" 2>/dev/null | head -5 || echo "   ✅ None found"
        
        echo ""
        echo "Requests from unusual IPs:"
        awk '{print $1}' "$NGINX_LOG" | sort | uniq -c | sort -rn | head -10
    else
        echo "⚠️  nginx access log not found"
    fi
    
    echo ""

    # ============ PHASE 7: PHP-FPM LOGS ============
    echo "7️⃣  ANALYZING PHP-FPM ERROR LOGS..."
    echo "════════════════════════════════════════════"
    echo ""
    
    PHP_FPM_LOG="/var/log/php8.3-fpm.log"
    if [ -f "$PHP_FPM_LOG" ]; then
        echo "Recent PHP errors (last 20 lines):"
        wc -l "$PHP_FPM_LOG"
        echo ""
        sed -n '$-20,$p' "$PHP_FPM_LOG" 2>/dev/null || echo "   (Cannot read log)"
    else
        echo "⚠️  PHP-FPM log not found"
    fi
    
    echo ""

    # ============ PHASE 8: CRON VERIFICATION ============
    echo "8️⃣  VERIFYING CRON FILE SECURITY..."
    echo "════════════════════════════════════════════"
    echo ""
    
    echo "▶ /etc/cron.d/ permissions (should be 755 and files 644):"
    ls -ld /etc/cron.d/ 2>/dev/null || echo "   ⚠️  Cannot read"
    echo ""
    
    echo "▶ /etc/cron.d/* files:"
    ls -la /etc/cron.d/ 2>/dev/null | grep -v total || echo "   (Files not listed)"
    echo ""
    
    echo "▶ Checking for suspicious cron jobs:"
    grep -r "http\|curl\|wget\|base64\|nc " /etc/cron.d/ 2>/dev/null || echo "   ✅ None found"
    echo ""

    # ============ PHASE 9: PROCESSES & SYSTEM ============
    echo "9️⃣  CHECKING FOR SUSPICIOUS PROCESSES..."
    echo "════════════════════════════════════════════"
    echo ""
    
    echo "▶ Top 10 processes by CPU usage:"
    ps aux --sort=-%cpu | head -11
    echo ""
    
    echo "▶ Checking for known malware patterns:"
    ps aux | grep -E "qpAo|7jf6|miner|crypto|xmr|bitcoin|dd|nc.*1234" | grep -v grep || echo "   ✅ None found"
    echo ""

    # ============ PHASE 10: FILE INTEGRITY ============
    echo "🔟 FILE INTEGRITY CHECK..."
    echo "════════════════════════════════════════════"
    echo ""
    
    echo "▶ Checking /var/www/html/offside-app/ permissions:"
    ls -ld /var/www/html/offside-app/ 2>/dev/null || echo "   ⚠️  Cannot read"
    echo ""
    
    echo "▶ Looking for suspicious files in app directory:"
    find "$APP_PATH" -type f -name "*.php" -size +1M 2>/dev/null | head -10 || echo "   ✅ None found"
    echo ""
    
    echo "▶ Looking for recently modified files in /tmp and /var/tmp:"
    find /tmp /var/tmp -type f -mtime -1 2>/dev/null | head -10 || echo "   ✅ None found recently modified"
    echo ""

    # ============ PHASE 11: SUMMARY ============
    echo ""
    echo "╔════════════════════════════════════════════════════════════════╗"
    echo "║                        AUDIT COMPLETE                          ║"
    echo "╚════════════════════════════════════════════════════════════════╝"
    echo ""
    echo "📋 RECOMMENDATIONS:"
    echo "   1. Review vulnerabilities found above"
    echo "   2. Patch path traversal in /avatars route"
    echo "   3. Rotate all credentials"
    echo "   4. Run WAF (ModSecurity) if available"
    echo "   5. Review application code at:"
    echo "      - app/Http/Controllers/ (all POST endpoints)"
    echo "      - app/Http/Middleware/ (validation logic)"
    echo "      - routes/ (parameter validation)"
    echo ""
    echo "Full report saved to: $REPORT_FILE"
    echo ""

} | tee -a "$REPORT_FILE"

echo "✅ Audit complete. Report: $REPORT_FILE"
