# GitHub Copilot Instructions - OffsideClub

## Project Overview

OffsideClub is a Laravel-based web application for football predictions, social group management, and interactive competitions. Users can create groups, make predictions about football matches, participate in social questions, and compete in rankings based on points.

## Tech Stack

### Backend
- **Framework**: Laravel 10.x (PHP 8.1+)
- **ORM**: Eloquent ORM with Doctrine DBAL
- **Authentication**: Laravel Sanctum (API tokens)
- **Queue System**: Laravel Horizon with Redis
- **Broadcasting**: Laravel Broadcasting for real-time notifications
- **Cache/Queue**: Redis
- **Database**: MySQL (primary), PostgreSQL compatible
- **Testing**: PHPUnit 10.x with RefreshDatabase trait

### Frontend
- **Templating**: Blade templates
- **CSS Framework**: Tailwind CSS 3.x
- **JavaScript**: AlpineJS 3.x, Vanilla JavaScript
- **Build Tool**: Vite 5.x
- **Icons**: Font Awesome 6.x

### External Services
- **AI Services**: Google Gemini API, OpenAI API
- **Football Data**: Football-Data.org API, web scraping with Goutte
- **Push Notifications**: Firebase Cloud Messaging (FCM), Laravel WebPush
- **Image Processing**: Intervention Image

## Naming Conventions

### PHP/Laravel
- **Classes**: PascalCase (e.g., `UserController`, `FootballMatch`)
- **Methods**: camelCase (e.g., `createQuestion()`, `getFixtures()`)
- **Properties**: camelCase for class properties, snake_case for database columns
- **Constants**: SCREAMING_SNAKE_CASE (e.g., `MAX_RETRIES`)
- **Namespaces**: Follow PSR-4 autoloading standard (e.g., `App\Services\GeminiService`)

### Database
- **Tables**: Plural snake_case (e.g., `users`, `football_matches`, `question_options`)
- **Columns**: snake_case (e.g., `created_at`, `home_team_score`)
- **Foreign Keys**: `{model}_id` format (e.g., `user_id`, `group_id`)
- **Pivot Tables**: Alphabetically ordered model names (e.g., `group_user`)

### Frontend
- **CSS Classes**: Tailwind utility classes, kebab-case for custom classes
- **JavaScript Variables**: camelCase (e.g., `userId`, `matchData`)
- **Blade Components**: kebab-case (e.g., `<x-card-component>`)
- **Alpine.js**: Use `x-data`, `x-bind`, `@click` directive syntax

## Coding Standards

### Laravel Specific
- Use **Laravel Collections** instead of plain PHP arrays when working with data
- Leverage **Eloquent relationships** (hasMany, belongsTo, belongsToMany) instead of manual queries
- Use **Service classes** for business logic (in `app/Services/`)
- Use **Jobs** for asynchronous tasks (in `app/Jobs/`)
- Use **Traits** for reusable functionality (in `app/Traits/`)
- Always use **dependency injection** in controllers and services
- Use **type hints** for method parameters and return types
- Add **PHPDoc comments** for complex methods and class properties

### Database
- Always use **migrations** for database changes, never modify the database directly
- Use **factories** for test data generation (in `database/factories/`)
- Use **seeders** for initial/demo data (in `database/seeders/`)
- Wrap related operations in **database transactions** using `DB::transaction()`
- Use **soft deletes** where appropriate with `SoftDeletes` trait

### Security
- **Never** store sensitive data (API keys, passwords) in code - use `.env` configuration
- Always **validate and sanitize** user input using Laravel Request validation
- Use **Laravel's built-in CSRF protection** for forms
- Use **Sanctum tokens** for API authentication
- Use **Gate and Policy classes** for authorization logic
- Be careful with **mass assignment** - define `$fillable` or `$guarded` in models

### Error Handling
- Use **Laravel's exception handling** system
- Log errors with **Log facade** using appropriate levels (debug, info, warning, error)
- Use **try-catch blocks** for external API calls
- Return appropriate **HTTP status codes** in API responses
- Provide **meaningful error messages** in both Spanish and English where applicable

### Testing
- Write **feature tests** for user-facing functionality (in `tests/Feature/`)
- Write **unit tests** for isolated service/model logic (in `tests/Unit/`)
- Use **RefreshDatabase** trait to reset database between tests
- Use **factories** to create test data, not manual creation
- Mock external API calls in tests to avoid real API requests
- Test both **success and failure scenarios**

## Build and Development Commands

### Setup
```bash
composer install              # Install PHP dependencies
npm install                   # Install Node.js dependencies
cp .env.example .env          # Create environment file
php artisan key:generate      # Generate application key
php artisan migrate           # Run database migrations
php artisan db:seed           # Seed database with demo data
```

### Development
```bash
npm run dev                   # Start Vite development server
php artisan serve             # Start Laravel development server
php artisan queue:work        # Start queue worker
php artisan horizon           # Start Horizon dashboard (for Redis queues)
```

### Building
```bash
npm run build                 # Build frontend assets for production
npm run prod                  # Build and optimize for production (includes caching)
```

### Testing
```bash
php artisan test              # Run all PHPUnit tests
php artisan test --filter=TestName  # Run specific test
npm run test                  # Run frontend tests (if configured)
```

### Code Quality
```bash
./vendor/bin/pint             # Format PHP code (Laravel Pint)
php artisan optimize          # Optimize application (cache config, routes, views)
php artisan clear             # Clear all caches (npm script: npm run clear)
```

### Database
```bash
php artisan migrate           # Run pending migrations
php artisan migrate:fresh     # Drop all tables and re-run migrations
php artisan migrate:refresh   # Rollback and re-run migrations
php artisan db:seed           # Run database seeders
```

## Project-Specific Patterns

### Multi-language Support
- The application supports **Spanish** and **English**
- Use Laravel's `__()` helper for translations
- Store translations in `resources/lang/` directory
- Most user-facing content is in Spanish by default

### Timezone Handling
- Store dates in **UTC** in the database
- Convert to user's timezone for display using Carbon
- Users can set their preferred timezone in settings

### Football Match Data
- Use **Football-Data.org API** as primary source for match data
- Use **Gemini API** for AI-powered analysis and question generation
- Cache match data to reduce API calls (use Laravel Cache facade)
- Handle API rate limits with retry logic and exponential backoff

### Question System
- Questions can be **predictive** (match-related) or **social** (group-related)
- Questions have **options** that users select as answers
- Points are awarded based on correct answers
- Results are verified automatically after matches finish
- Use `VerifyQuestionResultsJob` for automated verification

### Group Management
- Groups have **roles** (admin, moderator, member) managed via `GroupRoleService`
- Each group can be associated with a specific **competition**
- Groups have unique **invite codes** for joining
- Use **GroupAccessException** for authorization failures

### Push Notifications
- Use **FCM (Firebase Cloud Messaging)** for mobile push notifications
- Use **Laravel WebPush** for browser push notifications
- Store push subscriptions in `push_subscriptions` table
- Queue notification jobs to avoid blocking requests

## Configuration Files

- `.env` - Environment configuration (never commit this file)
- `.env.example` - Template for environment variables
- `.env.testing` - Testing environment configuration
- `config/` - Laravel configuration files
- `phpunit.xml` - PHPUnit test configuration
- `tailwind.config.js` - Tailwind CSS configuration
- `vite.config.js` - Vite build tool configuration

## Common Pitfalls to Avoid

- **Don't** use `tail` command in bash (it blocks execution) - use alternatives like `sed`, `awk`, or `cat`
- **Don't** commit `.env` file or API keys to version control
- **Don't** use `any` type hints in PHP - always specify concrete types
- **Don't** write raw SQL queries - use Eloquent or Query Builder
- **Don't** forget to queue long-running tasks (API calls, image processing)
- **Don't** cache sensitive data or user-specific data globally
- **Don't** forget to validate user input in controllers
- **Don't** expose internal error details to users in production

## Terminal and Command Rules

- Use exclusively **Bash** syntax for all terminal commands (no PowerShell or CMD)
- **Strict prohibition**: Never use the `tail` command (alone or at the end of a pipeline) as it blocks execution flow
- If you need to read the end of a file, use alternatives like `sed`, `awk`, or simply `cat` for small files

## Additional Resources

- Laravel Documentation: https://laravel.com/docs/10.x
- Tailwind CSS: https://tailwindcss.com/docs
- AlpineJS: https://alpinejs.dev/
- PHPUnit: https://phpunit.de/documentation.html
- Technical Documentation: See `TECHNICAL_DOCUMENTATION.md` in the repository root
