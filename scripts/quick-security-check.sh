#!/bin/bash

# 🚨 QUICK COMPROMISE DETECTION
# Fast check for common backdoors and indicators

echo "🔍 QUICK SECURITY CHECK"
echo "======================"
echo ""

# 1. Check /etc/cron.d for backdoors
echo "1. Checking /etc/cron.d for backdoors..."
ls -la /etc/cron.d/
echo ""

# 2. Check for suspicious files in common locations
echo "2. Checking /tmp and /var/tmp for suspicious files..."
find /tmp -type f -mtime -1 2>/dev/null | head -10
find /var/tmp -type f -mtime -1 2>/dev/null | head -10
echo ""

# 3. Check system calls hooks
echo "3. Checking for LKM (Loadable Kernel Module) backdoors..."
ls -la /lib/modules/$(uname -r)/kernel/net/netfilter/
echo ""

# 4. Check for modified system binaries
echo "4. Last 10 modified files in /usr/bin and /usr/sbin..."
find /usr/bin /usr/sbin -mtime -1 2>/dev/null | head -10
echo ""

# 5. Check listening ports
echo "5. Listening ports..."
netstat -tlnp 2>/dev/null | grep LISTEN | grep -v '22\|80\|443\|25\|3306\|6379\|8080\|9002'
echo ""

# 6. Check for reverse shells
echo "6. Checking for reverse shell indicators..."
ps aux | grep -E 'nc -l|bash -i|perl.*socket|python.*socket' | grep -v grep
echo ""

# 7. Check sudoers for backdoors
echo "7. Checking /etc/sudoers for modifications..."
find /etc/sudoers* -mtime -1 2>/dev/null
echo ""

# 8. Check recent SSH activity
echo "8. Recent SSH activity (last 20 lines)..."
tail -20 /var/log/auth.log | grep -i ssh
echo ""

# 9. Check for webshells
echo "9. Checking for PHP webshells in /var/www..."
find /var/www -name '*.php' -mtime -1 2>/dev/null | head -10
echo ""

# 10. Check for suspicious Apache modules
echo "10. Checking Apache/Nginx modules..."
if command -v apache2ctl &>/dev/null; then
    apache2ctl -M | grep -v 'cgi\|rewrite\|ssl\|proxy'
fi
echo ""

echo "✅ Quick check completed"
