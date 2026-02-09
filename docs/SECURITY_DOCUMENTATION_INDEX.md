# 🔒 SECURITY INCIDENT DOCUMENTATION INDEX

**Incident:** Cryptocurrency Mining Attack (qpAopmVd)  
**Date:** 2024-12-19  
**Status:** REMEDIATED  

---

## 📋 Document Guide

### For Management / Leadership

**→ START HERE:** [SECURITY_INCIDENT_EXECUTIVE_SUMMARY.md](SECURITY_INCIDENT_EXECUTIVE_SUMMARY.md)
- High-level overview of incident
- Business impact analysis
- Current risk status
- What happens next

---

### For Security Team / DevOps

**→ START HERE:** [SECURITY_BREACH_ROOT_CAUSE_ANALYSIS.md](SECURITY_BREACH_ROOT_CAUSE_ANALYSIS.md)
- Complete forensic investigation
- Attack chain visualization
- Evidence collected
- Root cause confirmed: world-writable cron files

**→ THEN READ:** [FAQ_COMO_LLEGO_EL_MALWARE.md](FAQ_COMO_LLEGO_EL_MALWARE.md)
- Detailed Q&A on attack vector
- How exploitation happened
- Timeline of events
- Prevention strategies

**→ THEN DO:** [IMMEDIATE_ACTION_PLAN.md](IMMEDIATE_ACTION_PLAN.md)
- Step-by-step remediation checklist
- Priority order for fixes
- Credential rotation guide
- Verification procedures

---

### For Implementation / Execution

**RUN THIS FIRST:**
```bash
ssh ubuntu@ec2-52-3-65-135.compute-1.amazonaws.com
sudo bash /var/www/html/offside-app/security-audit.sh
# Finds vulnerable packages and configuration issues
# Output: /tmp/security-audit-*.txt
```

**RUN THIS SECOND:**
```bash
sudo bash /var/www/html/offside-app/hardening-security.sh
# Applies security hardening
# Fixes permissions
# Enables monitoring
# Configures PHP restrictions
```

---

## 📁 All Documents

```
SECURITY_INCIDENT_EXECUTIVE_SUMMARY.md
├─ Purpose: High-level overview for leadership
├─ Audience: Management, CTO, Security leads
├─ Length: ~5 min read
└─ Action: Understand situation and next steps

SECURITY_BREACH_ROOT_CAUSE_ANALYSIS.md
├─ Purpose: Complete technical forensics
├─ Audience: Security engineers, DevOps
├─ Length: ~15 min read
└─ Action: Understand technical details

FAQ_COMO_LLEGO_EL_MALWARE.md
├─ Purpose: Detailed explanation of attack
├─ Audience: Technical staff, auditors
├─ Length: ~20 min read
└─ Action: Learn how to prevent similar attacks

IMMEDIATE_ACTION_PLAN.md
├─ Purpose: Step-by-step remediation guide
├─ Audience: DevOps, system administrators
├─ Length: ~30 min to execute
└─ Action: Follow checklist to implement fixes

hardening-security.sh
├─ Purpose: Automated security hardening script
├─ Audience: System administrators
├─ Execution: sudo bash hardening-security.sh
└─ What it does:
    ├─ Fix file permissions
    ├─ Disable dangerous PHP functions
    ├─ Disable unnecessary services
    ├─ Enable firewall (UFW)
    ├─ Install security tools (aide, auditd)
    ├─ Scan for suspicious files
    └─ Configure monitoring

security-audit.sh
├─ Purpose: Vulnerability and configuration audit
├─ Audience: System administrators
├─ Execution: sudo bash security-audit.sh
└─ What it checks:
    ├─ Composer vulnerabilities (composer audit)
    ├─ NPM vulnerabilities (npm audit)
    ├─ System security configuration
    ├─ PHP configuration
    ├─ Suspicious processes
    ├─ File integrity
    └─ Attack signatures in logs

[Other Related Docs]
├─ QUICK_FIX_LOGOS.md (logos displaying issue)
├─ FIX_BROKEN_LOGOS_PRODUCTION.md (symlink fix)
├─ DEPLOYMENT_CHECKLIST.md (deployment steps)
└─ [Various other incident response docs]
```

---

## 🚨 Priority Actions

### IMMEDIATE (Next 2 hours) 🔴

- [ ] Run `security-audit.sh` to find vulnerable packages
- [ ] Rotate SSH keys (attacker may have access)
- [ ] Change RDS password (attacker may have DB access)
- [ ] Apply PHP hardening (disable dangerous functions)

**Verification:**
```bash
✅ No suspicious processes (ps aux)
✅ CPU usage normal (top)
✅ Cron permissions secure (ls -la /etc/cron.d/)
✅ Application working (curl /api/health)
```

### TODAY (This business day) 🟠

- [ ] Complete `IMMEDIATE_ACTION_PLAN.md` checklist
- [ ] Review application logs for injection signs
- [ ] Update vulnerable Composer packages
- [ ] Document all changes made

### THIS WEEK 🟡

- [ ] Full dependency audit (composer show --outdated)
- [ ] Security code review of vulnerable packages
- [ ] Implement monitoring/alerting
- [ ] Schedule security training

### THIS MONTH 🟢

- [ ] Deploy WAF (AWS WAF or ModSecurity)
- [ ] Implement CI/CD security scanning
- [ ] Create incident response procedure
- [ ] Security assessment with external firm

---

## 🔍 Key Findings Summary

| Item | Status | Details |
|------|--------|---------|
| **Malware** | ✅ Killed | qpAopmVd cryptocurrency miner (100% CPU, 7h55m) |
| **Attack Entry** | ❓ Unknown | Likely vulnerable Composer package (composer audit needed) |
| **Privilege Escalation** | ✅ Fixed | World-writable cron files (now chmod 644) |
| **Data Breach** | ✅ No | No evidence of data exfiltration |
| **Code Compromise** | ✅ No | No backdoors in application code |
| **Database Compromise** | ❓ Risky | Assume compromise if www-data had DB access |
| **Credentials Compromised** | ❓ Risky | SSH keys, passwords should be rotated |

---

## 📊 Timeline

```
[Unknown date]  │ Vulnerable Composer package in use
                │ Attacker identifies and exploits
                │
[T-8 hours]     │ 🚨 CODE INJECTION + PRIVILEGE ESCALATION
                │    qpAopmVd downloaded and started
                │    100% CPU consumption begins
                │
[T-0 hours]     │ ✅ DETECTED: User notices high CPU
                │    Process killed manually
                │
[T+0.5 hours]   │ ✅ REMEDIATED: Permissions fixed
                │    chmod 644 /etc/cron.d/*
                │
[T+1 hour]      │ 🔍 ROOT CAUSE: Identified
                │    World-writable cron = privilege escalation vector
                │
[T+2 hours]     │ 📋 INVESTIGATION: Complete
                │    Forensics documented
                │    Remediation plan ready
                │
[T+24 hours]    │ 📊 AUDIT: Run composer audit
                │    Identify vulnerable packages
                │
[T+48 hours]    │ 🔧 FIX: Update packages
                │    Deploy to production
                │
[T+1 week]      │ 🛡️ HARDENING: WAF + monitoring
                │    Incident response plan
                │    Security training
```

---

## 🎯 Success Criteria

**Incident is fully resolved when:**

- ✅ All vulnerable Composer packages updated
- ✅ PHP hardening fully implemented (disable_functions, open_basedir)
- ✅ Firewall configured (UFW or AWS Security Groups)
- ✅ Monitoring in place (CPU alerts, file integrity, auditd)
- ✅ Credentials rotated (SSH, RDS, API tokens)
- ✅ No suspicious processes for 7 days
- ✅ WAF deployed (AWS WAF or ModSecurity)
- ✅ Security training completed
- ✅ Incident response plan documented

---

## 📞 Support & References

### Internal Resources
- SECURITY_INCIDENT_EXECUTIVE_SUMMARY.md (management)
- IMMEDIATE_ACTION_PLAN.md (technical checklist)
- hardening-security.sh (automation)
- security-audit.sh (vulnerability scanning)

### External Resources
- **Laravel Security:** https://laravel.com/docs/security
- **OWASP Top 10:** https://owasp.org/www-project-top-ten/
- **Composer Audit:** https://getcomposer.org/doc/03-cli.md#audit
- **PHP Security:** https://www.php.net/manual/en/security.php
- **AWS Security Best Practices:** https://aws.amazon.com/architecture/security-identity-compliance/

### Incident Response Contacts
- **Security Lead:** [Name/Contact]
- **DevOps Lead:** [Name/Contact]
- **CTO:** [Name/Contact]
- **AWS Support:** [Account ID]

---

## 💾 Log Locations (For Reference)

**Application Logs:**
```
/var/www/html/offside-app/storage/logs/laravel.log
```

**Web Server Logs:**
```
/var/log/nginx/access.log
/var/log/nginx/error.log
```

**PHP-FPM Logs:**
```
/var/log/php-fpm/www-error.log
```

**Audit Logs:**
```
/var/log/audit/audit.log
```

**System Logs:**
```
journalctl -u nginx
journalctl -u php8.3-fpm
journalctl -u cron
```

---

## 🔐 Password Change Log

After rotating credentials, track them here:

```
[ ] SSH Key:
    Old: ~/.ssh/offside-prod
    New: ~/.ssh/offside-prod-new
    Date Rotated: ___________
    Verified: ___________

[ ] RDS Password:
    Date Changed: ___________
    Updated in: .env (DB_PASSWORD)
    Verified: ___________

[ ] API Tokens:
    List: ___________
    Date Changed: ___________
    Verified: ___________
```

---

## ✅ Final Verification Checklist

Run these after completing remediation:

```bash
# 1. No malware running
ps aux | grep -i qpAo  # Should be empty
ps aux | grep -i miner # Should be empty

# 2. CPU normal
top -b -n 1 | head -3  # Should show <5% usage

# 3. Cron secure
ls -la /etc/cron.d/    # Should show 644 permissions

# 4. PHP hardened
grep disable_functions /etc/php/8.3/fpm/php.ini  # Should have entries

# 5. App working
curl https://offside-app.production/api/health  # Should return 200 OK

# 6. Logs clean
tail -50 /var/www/html/offside-app/storage/logs/laravel.log  # No errors

# 7. No world-writable files
find /var/www/html/offside-app -type f -perm /002 2>/dev/null  # Should be empty
```

---

**Document Created:** 2024-12-19  
**Last Updated:** 2024-12-19  
**Status:** INCIDENT REMEDIATED  
**Next Review:** 2024-12-20

---

**START WITH:** SECURITY_INCIDENT_EXECUTIVE_SUMMARY.md (5 min read)  
**THEN DO:** IMMEDIATE_ACTION_PLAN.md (2 hour execution)  
**THEN VERIFY:** Success criteria above  

