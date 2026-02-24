# 📑 REBUILD DOCUMENTATION INDEX

**Generated:** Feb 8, 2026, 00:55 UTC  
**Status:** ✅ READY FOR PHASE 2  
**Authorization:** ✅ User Confirmed

---

## 🎯 START HERE

### If you're starting the rebuild RIGHT NOW:

1. **First Read:**
   - [REBUILD_STATUS_READY.md](REBUILD_STATUS_READY.md) - 5 min overview

2. **Then Decide:**
   - Manual? → Read [REBUILD_STEP_BY_STEP.md](REBUILD_STEP_BY_STEP.md)
   - Quick? → Use [install-and-restore.sh](install-and-restore.sh)
   - Verify? → Use [REBUILD_CHECKLIST.md](REBUILD_CHECKLIST.md)

3. **Execute Phase 2:**
   - [PHASE_2_CREATE_EC2.md](PHASE_2_CREATE_EC2.md)

---

## 📚 COMPLETE DOCUMENTATION

### Primary Guides

| Document | Purpose | Read Time | When to Use |
|----------|---------|-----------|------------|
| **REBUILD_STATUS_READY.md** | Executive summary | 5 min | Before starting |
| **REBUILD_STEP_BY_STEP.md** | Detailed manual | 30 min | Following manually |
| **REBUILD_CHECKLIST.md** | Visual checklist | 20 min | While executing |
| **PHASE_2_CREATE_EC2.md** | EC2 creation | 10 min | Creating instance |

### Automation Scripts

| Script | Purpose | Input | Output |
|--------|---------|-------|--------|
| **create-new-instance.sh** | Auto-create EC2 | AWS CLI OR console | Instance ID + IP |
| **install-and-restore.sh** | Install + Restore | New EC2 IP | Full stack + data |
| **install-clean-stack.sh** | Stack only | Manual | PHP, Nginx, Redis, Node.js |

### Backup Files

| File | Size | Content | Location |
|------|------|---------|----------|
| **db-backup.sql** | 27 KB | Complete database | Root directory |
| **backup-storage-20260208/** | 600+ MB | Avatars, logos, cache | backup-storage-20260208/ |
| **.env.backup** | 2.3 KB | Configuration | Root directory |
| **composer.lock.backup** | 419 KB | PHP dependencies | Root directory |

---

## 🔄 REBUILD PHASES (with estimated time)

```
┌─────────────────────────────────────────────────────────┐
│ PHASE 2: CREATE EC2                      ~5-10 min       │
│ ─────────────────────────────────────────────────────────│
│ Action: Launch new t3.medium instance (Ubuntu 24.04)    │
│ When: Now!                                              │
│ Guide: PHASE_2_CREATE_EC2.md                            │
└─────────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────┐
│ PHASE 3: INSTALL STACK                   ~10-15 min      │
│ ─────────────────────────────────────────────────────────│
│ Action: PHP, Nginx, Redis, Node.js, MySQL              │
│ When: After instance ready (2-3 min)                   │
│ Guide: REBUILD_STEP_BY_STEP.md § Phase 3              │
└─────────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────┐
│ PHASE 4: RESTORE DATA                    ~10-15 min      │
│ ─────────────────────────────────────────────────────────│
│ Action: Import DB + Copy storage files                 │
│ When: After stack installed                            │
│ Guide: REBUILD_STEP_BY_STEP.md § Phase 4              │
└─────────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────┐
│ PHASE 5: DEPLOY CODE                     ~10-15 min      │
│ ─────────────────────────────────────────────────────────│
│ Action: Git clone + Composer + Laravel setup           │
│ When: After data restored                              │
│ Guide: REBUILD_STEP_BY_STEP.md § Phase 5              │
└─────────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────┐
│ PHASE 6: CONFIGURE NGINX                 ~5 min          │
│ ─────────────────────────────────────────────────────────│
│ Action: Create virtual host + reload                   │
│ When: After code deployed                              │
│ Guide: REBUILD_STEP_BY_STEP.md § Phase 6              │
└─────────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────┐
│ PHASE 7: TESTING                         ~15-30 min      │
│ ─────────────────────────────────────────────────────────│
│ Action: PHP, MySQL, Redis, HTTP, App tests             │
│ When: After Nginx configured                           │
│ Guide: REBUILD_STEP_BY_STEP.md § Phase 7              │
└─────────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────┐
│ PHASE 8: DNS MIGRATION                   ~5-10 min       │
│ ─────────────────────────────────────────────────────────│
│ Action: Update Elastic IP or Route53                   │
│ When: After all tests pass ✅                          │
│ Guide: REBUILD_STEP_BY_STEP.md § Phase 8              │
└─────────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────┐
│ PHASE 9: CLEANUP & HARDENING             ~30-45 min      │
│ ─────────────────────────────────────────────────────────│
│ Action: Apply security fixes + HTTPS + terminate old   │
│ When: After 1-2 hour stability verification            │
│ Guide: REBUILD_STEP_BY_STEP.md § Phase 9              │
└─────────────────────────────────────────────────────────┘

TOTAL TIME: 1.5 - 2.5 hours
```

---

## 📋 QUICK REFERENCE

### Command to Start Rebuild (3 options)

**Option A: Full Manual**
```bash
# Read and execute REBUILD_STEP_BY_STEP.md
# Expected time: 2-3 hours (learning-friendly)
```

**Option B: Semi-Automated**
```bash
bash install-and-restore.sh 54.123.45.67
# Expected time: 1.5 hours (instance creation is manual)
```

**Option C: Full Automated** (CLI only)
```bash
bash create-new-instance.sh
# Expected time: 1.5 hours
```

---

## 🔐 SECURITY INFORMATION

### Critical Credentials (RDS)
```
Host: database-1.cli4u22ycgzu.us-east-1.rds.amazonaws.com
User: admin
Password: offside.2025
Database: offsideclub
Port: 3306
```
⚠️ **MUST rotate after rebuild!**

### Infrastructure Details
- **Region:** us-east-1
- **Key Pair:** offside.pem
- **Instance Type:** t3.medium
- **AMI:** Ubuntu 24.04 LTS

---

## 🆘 TROUBLESHOOTING

### If something goes wrong:

| Problem | Solution |
|---------|----------|
| SSH timeout | Wait 2-3 min, instance still booting |
| DB connection error | Check RDS security group allows EC2 |
| App returns 500 | Check logs: `tail -50 storage/logs/laravel.log` |
| Avatars not showing | Verify `storage/app/public/` permissions |
| Nginx not starting | Check config: `sudo nginx -t` |

**Full troubleshooting:**
- See REBUILD_STEP_BY_STEP.md § Troubleshooting
- Check REBUILD_CHECKLIST.md § Verification Steps

---

## ⚡ QUICK START (TL;DR)

1. **Now:** Read REBUILD_STATUS_READY.md
2. **5 min:** Create EC2 in AWS Console (PHASE_2_CREATE_EC2.md)
3. **3 min:** Note new Instance ID + Public IP
4. **45 min:** Run `bash install-and-restore.sh <IP>`
5. **30 min:** Test application
6. **10 min:** Migrate DNS
7. **1 hour:** Monitor for stability
8. **30 min:** Apply security hardening

**Total: ~2 hours**

---

## 📞 SUPPORT

If you have questions while rebuilding:

1. Check REBUILD_CHECKLIST.md for what step you're on
2. Read the Phase section in REBUILD_STEP_BY_STEP.md
3. Look at Troubleshooting section
4. Review script output for error messages

---

## ✅ PRE-REBUILD VERIFICATION

Before you start, verify:

- [ ] Have AWS credentials configured
- [ ] Have offside.pem key available
- [ ] All backup files present locally:
  - [ ] db-backup.sql (27 KB)
  - [ ] backup-storage-20260208/ (600+ MB)
  - [ ] .env.backup
  - [ ] composer.lock.backup
- [ ] Have read REBUILD_STATUS_READY.md
- [ ] Understand the 9 phases
- [ ] Have ~2 hours available
- [ ] Can SSH to AWS instances
- [ ] Have RDS credentials memorized

---

## 📅 REBUILD TIMELINE

| Time | Action | Status |
|------|--------|--------|
| NOW | Start Phase 2 | ⏳ Waiting for you |
| +5 min | EC2 instance created | ⏳ |
| +10 min | Instance ready for SSH | ⏳ |
| +25 min | Stack installed | ⏳ |
| +40 min | Data restored | ⏳ |
| +55 min | Code deployed | ⏳ |
| +60 min | Nginx ready | ⏳ |
| +90 min | Tests pass ✅ | ⏳ |
| +100 min | DNS migrated | ⏳ |
| +130 min | Security hardened | ⏳ |

---

## 🎯 SUCCESS CRITERIA

Rebuild is complete when:

- [x] New EC2 instance created
- [x] All software installed
- [x] Database restored with all data
- [x] Storage files accessible
- [x] Application code deployed
- [x] Nginx serving requests
- [x] Users can login
- [x] Avatars display correctly
- [x] Calendar shows Madrid timezone
- [x] No errors in logs
- [x] DNS pointing to new server
- [x] Old instance terminated
- [x] Security hardening applied
- [x] No malware processes detected

---

## 📌 FINAL NOTES

- **Backups are safe** - 600+ MB of storage downloaded locally
- **Database is backed up** - 27 KB SQL file ready to restore
- **Old server stays up** - Keep as backup for 24 hours
- **This is reversible** - If new server fails, revert DNS

---

## 🚀 READY TO BEGIN?

### Next Step: Create EC2 Instance

**Open:** https://console.aws.amazon.com  
**Navigate:** EC2 > Instances > Launch Instances  
**Follow:** PHASE_2_CREATE_EC2.md  

**You've got this! 💪**

---

**Version:** Feb 8, 2026  
**Status:** READY FOR EXECUTION  
**Last Update:** 00:55 UTC
