# Production Deployment Checklist - Migration Fixes

## Pre-Deployment Phase (Team Lead)

### ✅ Code Review
- [ ] Review all migration fixes in MIGRATION_FIX_SUMMARY.md
- [ ] Verify git log shows correct commits:
  - [ ] 556c34e: fix: Corregir migración remove_competition_id y agregar limpieza de foreign keys
  - [ ] 76a75d4: docs: Agregar resumen de correcciones de migraciones
  - [ ] 93d6d02: scripts: Agregar scripts de deployment y testing
- [ ] Confirm no unstaged changes remain in repository

### ✅ Safety Checks
- [ ] Database backup procedures in place
- [ ] Backup storage location verified: `/backups/`
- [ ] SSH access to production confirmed
- [ ] Database credentials verified with DBA
- [ ] Deployment window scheduled with team

### ✅ Testing Environment
- [ ] Local testing completed with `test-migrations-local.bat`
- [ ] Migration status verified: all migrations show "Ran"
- [ ] Database integrity verified
- [ ] Language system tested locally
- [ ] Settings/profile page verified locally
- [ ] Market page shows "Coming Soon"

## Pre-Deployment Phase (DevOps)

### ✅ Production Environment Checks
- [ ] Production server status: healthy
- [ ] Database space available: > 500MB free
- [ ] MySQL service running on production
- [ ] No other deployments in progress
- [ ] Database character set verified (UTF-8)
- [ ] Production backups automated and recent

### ✅ Access Verification
- [ ] SSH key/credentials working
- [ ] Can read/write to `/backups/` directory
- [ ] Laravel logs writable
- [ ] Can execute `php artisan` commands

## Deployment Phase

### ✅ Pre-Execution (5 minutes before)
```bash
# SSH to production
ssh offside-app

# Navigate to app
cd /var/www/html/offside-app

# Confirm latest code
git log --oneline -n 3
git status  # Should be clean
```

- [ ] Confirm main branch has 3 latest commits
- [ ] Confirm git status is clean
- [ ] Notify team: "Starting migration deployment"

### ✅ Execute Backup
```bash
# Create timestamped backup
mysqldump -h localhost -u offside_user -p offside_club > /backups/database_$(date +%Y%m%d_%H%M%S).sql

# Verify backup created
ls -lah /backups/database_*.sql | tail -1
```

- [ ] Backup file created successfully
- [ ] Backup file size > 10MB (reasonable database size)
- [ ] Backup timestamp current

### ✅ Pull Latest Code
```bash
git pull origin main
```

- [ ] Output shows "Already up to date" or shows new commits pulled
- [ ] No merge conflicts
- [ ] No errors reported

### ✅ Execute Migrations - OPTION A (Recommended)
```bash
# Check current status
php artisan migrate:status

# Run incremental migrations
php artisan migrate --force
```

**If successful:**
- [ ] All output shows migrations completed
- [ ] No error messages
- [ ] Check status again: `php artisan migrate:status`
- [ ] All migrations should show "Ran"

**If failed:**
- [ ] Note exact error message
- [ ] Check Laravel logs: `tail -n 100 storage/logs/laravel.log`
- [ ] DO NOT proceed - contact DevOps lead
- [ ] Prepare for ROLLBACK procedure

### ✅ Execute Migrations - OPTION B (Fresh - Only If Corrupted)
```bash
# WARNING: This drops everything!
php artisan migrate:rollback --all --force

# Then run fresh
php artisan migrate --force
```

- [ ] Only use if migrations failed in OPTION A
- [ ] Backup already exists (safety net)
- [ ] All migrations show "Ran"
- [ ] No errors reported

## Post-Deployment Verification

### ✅ Database Integrity Checks
```bash
# Check migration status
php artisan migrate:status

# Check specific tables
php artisan db:show --table=users
php artisan db:show --table=teams
php artisan db:show --table=competitions
php artisan db:show --table=questions
php artisan db:show --table=answers
php artisan db:show --table=football_matches
php artisan db:show --table=groups
```

- [ ] All tables exist
- [ ] Expected columns present
- [ ] No errors reported
- [ ] Record counts reasonable

### ✅ Application Functionality Tests
```bash
# SSH into production
# Visit application URL in browser
```

Test these user flows:

- [ ] **Login**: User can login successfully
  - Navigate to login page
  - Enter credentials
  - Verify redirect to groups page
  
- [ ] **Language Setting**: Language selection works
  - Go to Settings > Language
  - Change language (EN ↔ ES)
  - Verify interface text updates
  - Refresh page and verify language persists
  
- [ ] **User Profile**: Settings page loads
  - Go to Settings/Profile
  - Verify all fields visible
  - Verify theme selection works
  - Verify changes persist
  
- [ ] **Marketplace**: Shows "Coming Soon"
  - Navigate to Marketplace
  - Verify "Coming Soon" message displays
  - Verify no errors in browser console
  
- [ ] **Groups**: Can view groups
  - Navigate to Groups
  - Verify groups load
  - Verify group members visible
  - Verify questions visible

### ✅ Error Log Checks
```bash
# Check for errors
grep -i "error\|exception\|failed" storage/logs/laravel.log | tail -20

# Check last 100 lines of log
tail -100 storage/logs/laravel.log
```

- [ ] No foreign key errors
- [ ] No migration errors
- [ ] No "column not found" errors
- [ ] Only normal INFO/DEBUG messages

### ✅ Performance Checks
```bash
# Check database size
mysql -u offside_user -p offside_club -e "SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb FROM information_schema.tables WHERE table_schema = 'offside_club' ORDER BY size_mb DESC;" 

# Check query performance
mysql -u offside_user -p offside_club -e "SHOW STATUS LIKE 'Questions';"
```

- [ ] Database size reasonable (< 500MB)
- [ ] No slow queries
- [ ] All table sizes expected

## Rollback Procedure (If Needed)

### ✅ Decision to Rollback
- [ ] Critical errors preventing user access
- [ ] Foreign key constraint violations
- [ ] Data corruption detected
- [ ] Performance degradation

### ✅ Execute Rollback
```bash
# Option 1: Rollback last migration batch
php artisan migrate:rollback --force

# Option 2: Restore from backup (Nuclear option)
mysql -u offside_user -p offside_club < /backups/database_YYYYMMDD_HHMMSS.sql
```

- [ ] Backup file confirmed before restore
- [ ] Application returned to pre-deployment state
- [ ] All user-facing functionality verified
- [ ] Error logs checked for issues

## Post-Deployment Communication

### ✅ Notify Team
- [ ] Send message: "✅ Migration deployment completed successfully"
- [ ] Include: Timestamp, migration count, any issues
- [ ] Include: Link to MIGRATION_FIX_SUMMARY.md for details

### ✅ Documentation
- [ ] Update deployment log with date/time
- [ ] Document any issues encountered and resolved
- [ ] Update runbook with lessons learned
- [ ] Archive backup file reference in deployment log

## Cleanup

### ✅ Local Environment
- [ ] Restore local .env file (if modified)
- [ ] Restart local server if needed
- [ ] Run local migrations: `php artisan migrate`
- [ ] Verify local environment working

### ✅ Repository
- [ ] Verify all commits pushed to main
- [ ] Confirm no uncommitted changes remain
- [ ] Tag release: `git tag -a v1.0.0-migrations -m "Migration fixes deployment"`
- [ ] Push tags: `git push origin --tags`

## Success Criteria

### ✅ Deployment Successful If:
- [✅] All migrations completed without errors
- [✅] Database integrity verified
- [✅] Application accessible and functional
- [✅] All user flows tested and working
- [✅] Error logs clean (no migration/FK errors)
- [✅] Language system working correctly
- [✅] Settings/profile page accessible
- [✅] Marketplace shows "Coming Soon"
- [✅] Team notified of successful deployment

### ✅ Deployment Failed If:
- [✅] Migrations fail with foreign key errors
- [✅] Application returns database errors
- [✅] Tables missing or corrupted
- [✅] User cannot login
- [✅] Critical functionality broken

## Quick Reference - Commands

```bash
# Check status
php artisan migrate:status

# Run migrations
php artisan migrate --force

# Rollback last batch
php artisan migrate:rollback --force

# Rollback all (NUCLEAR)
php artisan migrate:rollback --all --force

# Check database
php artisan db:show

# View logs
tail -f storage/logs/laravel.log

# Test specific table
php artisan db:show --table=users
```

## Important Files

- **MIGRATION_FIX_SUMMARY.md** - Comprehensive overview of all fixes
- **deploy-migrations-production.sh** - Automated deployment script (Linux/Mac)
- **test-migrations-local.bat** - Local testing script (Windows)
- **database/migrations/2026_01_13_000003_cleanup_all_foreign_keys.php** - Key migration

## Contact Information

| Role | Name | Contact |
|------|------|---------|
| DevOps Lead | [Name] | [Email/Phone] |
| Database Admin | [Name] | [Email/Phone] |
| Team Lead | [Name] | [Email/Phone] |
| On-Call | [Name] | [Email/Phone] |

---

**Deployment Date**: ________________
**Started By**: ________________
**Completed By**: ________________
**Duration**: ________________ minutes
**Issues**: ☐ None ☐ Minor ☐ Major (describe below)

Notes/Issues:
```
[Space for notes]
```

**Sign-off**: ________________ (DevOps Lead)

---

Last Updated: 2026-01-13
Version: 1.0
Status: Ready for Production
