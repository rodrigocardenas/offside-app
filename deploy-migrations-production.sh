#!/bin/bash

# ============================================================================
# Production Migration Deployment Script
# ============================================================================
# This script handles safe deployment of migration fixes to production
# 
# Usage:
#   ./deploy-migrations-production.sh [option]
# 
# Options:
#   fresh     - Rollback all and run fresh (for corrupted databases)
#   incremental - Run only new migrations (for mostly intact databases)
#   status    - Check migration status without making changes
#   verify    - Verify database integrity after deployment
#   rollback  - Rollback last batch of migrations
#
# ============================================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/html/offside-app"
BACKUP_DIR="/backups"
DB_USER="${DB_USER:=default_user}"
DB_NAME="${DB_NAME:=offside_club}"
DB_HOST="${DB_HOST:=localhost}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/database_${TIMESTAMP}.sql"

# Functions
print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

backup_database() {
    print_header "Backup Database"
    
    if ! mkdir -p "$BACKUP_DIR"; then
        print_error "Failed to create backup directory"
        return 1
    fi
    
    print_info "Backing up to: $BACKUP_FILE"
    if mysqldump -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null; then
        print_success "Database backup created"
        return 0
    else
        print_error "Failed to backup database"
        return 1
    fi
}

check_git_status() {
    print_header "Check Git Status"
    
    cd "$APP_DIR" || return 1
    
    if [ -z "$(git status --porcelain)" ]; then
        print_success "Git working directory is clean"
        return 0
    else
        print_warning "Git working directory has changes"
        git status
        return 1
    fi
}

pull_latest() {
    print_header "Pull Latest Code"
    
    cd "$APP_DIR" || return 1
    
    if git pull origin main 2>&1 | grep -q "Already up to date"; then
        print_success "Already up to date with main branch"
        return 0
    elif git pull origin main; then
        print_success "Successfully pulled latest code from main"
        return 0
    else
        print_error "Failed to pull from main branch"
        return 1
    fi
}

check_migration_status() {
    print_header "Check Migration Status"
    
    cd "$APP_DIR" || return 1
    
    php artisan migrate:status
}

migrate_fresh() {
    print_header "Fresh Migration (Rollback All + Migrate)"
    
    cd "$APP_DIR" || return 1
    
    print_warning "This will rollback ALL migrations and run them fresh"
    print_warning "Only use this if database schema is corrupted"
    read -p "Continue? (yes/no): " confirm
    
    if [ "$confirm" != "yes" ]; then
        print_info "Rollback cancelled"
        return 1
    fi
    
    print_info "Rolling back all migrations..."
    if php artisan migrate:rollback --all --force; then
        print_success "All migrations rolled back"
    else
        print_error "Failed to rollback migrations"
        return 1
    fi
    
    print_info "Running fresh migrations..."
    if php artisan migrate --force; then
        print_success "Fresh migrations completed"
        return 0
    else
        print_error "Fresh migrations failed"
        return 1
    fi
}

migrate_incremental() {
    print_header "Incremental Migration"
    
    cd "$APP_DIR" || return 1
    
    print_info "Running pending migrations..."
    if php artisan migrate --force; then
        print_success "Incremental migrations completed"
        return 0
    else
        print_error "Incremental migrations failed"
        return 1
    fi
}

verify_migrations() {
    print_header "Verify Migrations"
    
    cd "$APP_DIR" || return 1
    
    print_info "Checking migration status..."
    
    # Count total migrations
    TOTAL=$(php artisan migrate:status | grep "Run" | wc -l)
    
    if [ "$TOTAL" -gt 0 ]; then
        print_success "$TOTAL migrations have been run"
        return 0
    else
        print_warning "No migrations have been run"
        return 1
    fi
}

verify_database() {
    print_header "Verify Database Integrity"
    
    cd "$APP_DIR" || return 1
    
    # Check if key tables exist
    TABLES=("users" "teams" "competitions" "questions" "answers" "football_matches" "groups")
    
    for table in "${TABLES[@]}"; do
        php artisan db:show --table="$table" 2>/dev/null && print_success "Table '$table' verified" || print_warning "Table '$table' not found"
    done
}

rollback_migrations() {
    print_header "Rollback Last Migration Batch"
    
    cd "$APP_DIR" || return 1
    
    print_warning "This will rollback the last batch of migrations"
    read -p "Continue? (yes/no): " confirm
    
    if [ "$confirm" != "yes" ]; then
        print_info "Rollback cancelled"
        return 1
    fi
    
    if php artisan migrate:rollback --force; then
        print_success "Migrations rolled back successfully"
        return 0
    else
        print_error "Failed to rollback migrations"
        return 1
    fi
}

show_help() {
    cat << EOF
Production Migration Deployment Script

Usage:
    $0 [option]

Options:
    fresh       - Rollback ALL migrations and run fresh (for corrupted databases)
    incremental - Run only pending migrations (for mostly intact databases)
    status      - Check migration status without making changes
    verify      - Verify database integrity after deployment
    rollback    - Rollback last batch of migrations
    help        - Show this help message

Examples:
    $0 incremental      # Recommended for most cases
    $0 fresh            # Use if database is corrupted
    $0 status           # Check current state
    $0 verify           # Verify after deployment

Important:
    - Database backup is automatically created before migrations
    - Backup file location: $BACKUP_DIR/database_YYYYMMDD_HHMMSS.sql
    - Ensure you have proper permissions and credentials
    - Test in staging environment first

EOF
}

# Main execution
main() {
    local option="${1:-help}"
    
    case "$option" in
        fresh)
            backup_database && check_git_status && pull_latest && migrate_fresh && verify_migrations && verify_database
            ;;
        incremental)
            backup_database && check_git_status && pull_latest && migrate_incremental && verify_migrations && verify_database
            ;;
        status)
            check_migration_status
            ;;
        verify)
            verify_migrations && verify_database
            ;;
        rollback)
            rollback_migrations && verify_migrations
            ;;
        help)
            show_help
            ;;
        *)
            print_error "Unknown option: $option"
            show_help
            exit 1
            ;;
    esac
}

# Run main function
main "$@"
