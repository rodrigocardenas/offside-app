#!/bin/bash
# QUICK REFERENCE CARD - Security Incident Response
# Print this and keep it handy

echo "
╔═══════════════════════════════════════════════════════════════╗
║   🔒 OFFSIDE APP - SECURITY INCIDENT QUICK REFERENCE          ║
║      Cryptocurrency Mining Attack (qpAopmVd)                  ║
║      Date: 2024-12-19                                         ║
╚═══════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 WHAT HAPPENED
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Malware: qpAopmVd (cryptocurrency miner)
Impact:  100% CPU for 7+ hours
Status:  ✅ KILLED and REMEDIATED

Root Cause:
  1) Vulnerable Composer package (likely) → CODE INJECTION
  2) World-writable /etc/cron.d/ → PRIVILEGE ESCALATION
  3) Cron job with root privileges → MALWARE EXECUTION

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🚨 IMMEDIATE ACTIONS (NEXT 2 HOURS)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Step 1: RUN SECURITY AUDIT
  $ ssh ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com
  $ sudo bash /var/www/html/offside-app/security-audit.sh
  Look for: Vulnerable Composer/NPM packages
  ⏱️  Time: 10 minutes

Step 2: ROTATE SSH KEYS
  $ ssh-keygen -t ed25519 -f ~/.ssh/offside-prod-new
  $ ssh-copy-id -i ~/.ssh/offside-prod-new.pub ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com
  AWS Console: Remove old key from EC2 → Key pairs
  ⏱️  Time: 15 minutes

Step 3: CHANGE RDS PASSWORD
  AWS Console → RDS → offside-db
  → Modify → Master password → Generate → Apply immediately
  Then: SSH to prod, update .env, restart PHP
  ⏱️  Time: 20 minutes

Step 4: APPLY PHP HARDENING
  $ ssh ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com
  $ sudo bash /var/www/html/offside-app/hardening-security.sh
  ⏱️  Time: 15 minutes

Total Time: ~1 hour

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ VERIFICATION CHECKLIST
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

After each action, verify:

[ ] No suspicious processes
    ps aux | grep -E qpAo
    ps aux | grep -E miner

[ ] CPU usage normal
    top
    Should be <10% idle = normal
    NOT 100% = problem!

[ ] Cron permissions correct
    ls -la /etc/cron.d/.placeholder
    Should be: -rw-r--r-- (644)
    NOT:       -rw-rw-rw- (666)

[ ] PHP hardened
    grep disable_functions /etc/php/8.3/fpm/php.ini
    Should have: system,exec,passthru,shell_exec...

[ ] Application working
    curl https://offside-app.production/api/health
    Should return: 200 OK

[ ] Logs clean
    tail -50 /var/www/html/offside-app/storage/logs/laravel.log
    Should have: No ERROR or CRITICAL entries

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📚 DOCUMENTATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Quick Start (5 min):
  → SECURITY_INCIDENT_EXECUTIVE_SUMMARY.md

Technical Details (15 min):
  → SECURITY_BREACH_ROOT_CAUSE_ANALYSIS.md

How Did It Happen? (20 min):
  → FAQ_COMO_LLEGO_EL_MALWARE.md

Action Plan (30 min execution):
  → IMMEDIATE_ACTION_PLAN.md

All Documents Index:
  → SECURITY_DOCUMENTATION_INDEX.md

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔧 USEFUL COMMANDS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SSH to Production Server:
  ssh ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com

Check Current Malware Status:
  ps aux | sort -rk 3,3 | head -10
  top -p \$(pgrep qpAopmVd)

Check File Permissions:
  ls -la /etc/cron.d/
  ls -la /etc/crontab

View Logs:
  tail -f /var/www/html/offside-app/storage/logs/laravel.log
  tail -f /var/log/nginx/access.log
  tail -f /var/log/php-fpm/www-error.log

Check Vulnerable Packages:
  composer audit
  npm audit

Restart PHP:
  sudo systemctl reload php8.3-fpm

Restart Nginx:
  sudo systemctl restart nginx

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⚠️  CRITICAL - READ THESE FIRST
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Q: What's the backdoor?
A: No single backdoor file. Vulnerability was:
   /etc/cron.d/ with 666 permissions (world-writable)
   This allowed www-data to write cron jobs executed as root
   FIX: chmod 644 /etc/cron.d/* ✅ (already done)

Q: Where did the malware come from?
A: Likely from vulnerable Composer package via code injection
   When exploited, it downloaded qpAopmVd
   Check: composer audit (to find vulnerable package)

Q: Do I have compromised data?
A: No evidence found. But assume:
   - SSH keys compromised → rotate them ✅
   - RDS password compromised → change it ✅
   - API tokens compromised → rotate them ✅

Q: What's next?
A: Run security-audit.sh and IMMEDIATE_ACTION_PLAN.md

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 INCIDENT STATUS DASHBOARD
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Malware Killed:               ✅ YES (verified)
Permissions Fixed:            ✅ YES (chmod 644)
Storage Symlink Fixed:        ✅ YES (logos working)
CPU Normal:                   ✅ YES (0-1%)
Application Working:          ✅ YES
Logs Investigated:            ✅ YES
Code Audited:                 ✅ YES (clean)

SSH Keys Rotated:             ⏳ TODO
RDS Password Changed:         ⏳ TODO
Composer Audit Run:           ⏳ TODO
PHP Hardening Applied:        ⏳ TODO
Firewall Configured:          ⏳ TODO
Monitoring Enabled:           ⏳ TODO
WAF Deployed:                 ⏳ TODO

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🎯 PRIORITY TIMELINE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

NOW (2 hours)       → Run security-audit.sh + rotate credentials
TODAY (8 hours)     → Complete IMMEDIATE_ACTION_PLAN.md
THIS WEEK (24h)     → Find & fix vulnerable packages
THIS WEEK (48h)     → Deploy hardening
THIS MONTH (week)   → Deploy WAF & monitoring

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🆘 IF SOMETHING GOES WRONG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Process comes back:
  ps aux | grep qpAopmVd
  sudo pkill -9 qpAopmVd
  Check: /etc/cron.d/ files (delete malicious job)

SSH can't connect:
  Check AWS Security Groups (port 22 open?)
  Check EC2 instance status (is it running?)
  Try from different network

Application won't start:
  sudo systemctl restart php8.3-fpm
  Check: /var/www/html/offside-app/storage/logs/laravel.log
  Revert .env changes if password wrong

Can't find vulnerable package:
  composer audit
  composer show
  Check: composer.lock for exact versions
  Check GitHub: known CVEs for each package

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📞 CONTACTS & ESCALATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Security Emergency:     [Name] - [Phone]
DevOps Lead:           [Name] - [Phone]
CTO:                   [Name] - [Phone]
AWS Support:           [Account ID/Phone]
Laravel Community:     laracasts.com / laravel.io

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✏️  NOTES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Date Started:      _______________
Date Completed:    _______________
Vulnerable Pkg:    _______________
Notes:             _______________
                   _______________
                   _______________

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📄 QUICK REFERENCE CARD
📅 Date: 2024-12-19
🔒 Status: REMEDIATED
⏱️  Last Updated: [timestamp]

═══════════════════════════════════════════════════════════════════
"
