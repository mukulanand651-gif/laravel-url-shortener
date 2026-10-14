# Laravel URL Shortener — Role-Based, Single-Company Service

A Laravel 12 and MySQL web application for creating private short URLs, with session authentication, company-scoped invitations, role-based permissions, and automated feature tests. The interface uses Laravel Blade, HTML, and CSS without a JavaScript frontend framework.

> **Assessment interpretation:** Some sentences in the brief are contradictory. This implementation follows the literal restrictions listed under Authentication, Invitation, and URL Shortener. See [Assumptions and trade-offs](#assumptions-and-trade-offs) before evaluating the role behavior.

## Tech stack

- PHP 8.2+, Laravel 12, Composer
- MySQL (local development can use XAMPP's MySQL-compatible MariaDB server)
- Laravel Blade, HTML, CSS
- Laravel session authentication, Eloquent ORM, form validation, migrations, seeders, and feature tests
- No external URL shortening API or third-party role library required

## Key features

- Login and logout with session authentication
- Platform SuperAdmin created using a database seeder executing **parameterized raw SQL**
- Database-level and application-level limit of **one company**; one company has multiple users
- Five roles: `SuperAdmin`, `Admin`, `Member`, `Sales`, `Manager`
- Role-restricted invitations with hashed, one-time, 48-hour-expiring tokens; users register with an invitation link
- Short URL creation by Sales and Manager only; 10-character random codes unique in the database
- Role-based URL listings; private `/s/{code}` routes redirect authorized users with HTTP 302
- Visit counter and Laravel feature tests

## Permission matrix

| Action | SuperAdmin | Admin | Member | Sales | Manager |
| --- | --- | --- | --- | --- | --- |
| Sign in/out | Yes | Yes | Yes | Yes | Yes |
| Create company | First company only | No | No | No | No |
| Invite Admin to an **existing** company | Yes | No | No | No | No |
| Invite Member | Yes | No | No | No | No |
| Invite Sales / Manager | Yes | Yes (own company) | No | No | No |
| Create short URL | **No** | **No** | **No** | Yes | Yes |
| List short URLs | **403** | Only outside own company | Other creators in own company | Own-company URLs | Own-company URLs |
| Resolve private link | No | Yes, own company | Yes, own company | Yes, own company | Yes, own company |

**Consequences:** With only one company permitted, Admin's list is intentionally **empty** because there cannot be URLs outside their company. The Member list excludes URLs created by that Member; test fixtures can insert member-owned URLs even though the normal UI forbids Members from generating them.

## Requirements

Install:

1. PHP 8.2 or later with `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, and required Laravel extensions
2. Composer
3. MySQL server and a MySQL user with permissions on the project database
4. Git for cloning and contributing

Check:

```bash
php -v
composer --version
php -m
```

## Local setup from GitHub

### 1. Clone and install

```bash
git clone https://github.com/mukulanand651-gif/laravel-url-shortener.git
cd laravel-url-shortener
composer install
```


### 2. Create the database

In phpMyAdmin or the MySQL CLI, execute:

```sql
CREATE DATABASE IF NOT EXISTS url_shortener
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Configure the environment

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

On macOS/Linux:

```bash
cp .env.example .env
```

Set these values in `.env`:

```dotenv
APP_NAME="Laravel URL Shortener"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=url_shortener
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

SEED_SUPERADMIN_EMAIL=superadmin@example.test
SEED_SUPERADMIN_PASSWORD=replace-with-a-unique-strong-password
```

**Security:** The empty root password is a common *local XAMPP default*, not a production recommendation. Use a dedicated least-privileged MySQL user in other environments. Never commit your real `.env`, secrets, SQL dumps, or private invitation tokens.

### 4. Generate key, migrate, seed

```bash
php artisan key:generate
php artisan config:clear
php artisan migrate
php artisan db:seed
```

The seeder hashes the configured password and inserts the SuperAdmin with parameterized raw SQL. It is designed not to duplicate the account on subsequent runs. **Do not use `migrate:fresh` on a database with data you need to keep.**

### 5. Launch

```bash
php artisan serve
```

Open `http://127.0.0.1:8000/login`, authenticate with the seeded SuperAdmin credentials, and visit `/dashboard`.

If you use XAMPP, start **MySQL** in its control panel. Apache is not required for `php artisan serve`.

## First-time end-to-end workflow

1. **SuperAdmin signs in** with credentials set at seeding time. Open `/companies`, create the single company, then verify a second company is rejected.
2. **SuperAdmin invites an Admin** to that *existing* company at `/invite`. Copy the generated invitation URL. The app displays a link to share; it **does not send an invitation email**.
3. **Admin registers** by opening the invitation in a private browser window, entering their name/password, and accepting. Reusing the invitation should fail.
4. **Admin signs in**, opens `/invite`, and invites Sales and Manager users. Attempts to invite another Admin or a Member are forbidden.
5. **Sales (or Manager) signs in**, opens `/urls`, enters `https://example.com`, and generates a short code. The record appears in that role's URL listing.
6. **Logged-out visitor tests the link:** `/s/{code}` redirects to login, not to the external URL.
7. **Authorized company user tests the link:** `/s/{code}` responds with HTTP 302 and redirects to its stored original URL; visit count increases.
8. **Member account** is invited by SuperAdmin. A Member can list other creators' URLs, but submitting a URL creation request is forbidden.
9. **SuperAdmin visits `/urls`:** HTTP 403 is expected and correct.

## Main routes

| Method | URI | Description |
| --- | --- | --- |
| GET, POST | `/login` | Login form / authenticate |
| POST | `/logout` | End session |
| GET | `/dashboard` | Authenticated dashboard |
| GET, POST | `/companies` | SuperAdmin company view / single creation |
| GET, POST | `/invite` | Invitation form / issue invitation |
| GET, POST | `/invitations/{token}` | Accept invitation / register |
| GET | `/urls` | Role-filtered list |
| POST | `/urls` | Create URL (Sales, Manager only) |
| GET | `/s/{code}` | Authenticated company-scoped redirect |

All company, invitation, and short URL operations enforce authorization **on the server**, not just by hiding controls in the UI.

## Data model

- **companies:** `id`, `name`, unique `singleton_key`, timestamps. `singleton_key = 1` ensures no second company can be inserted even if simultaneous requests race.
- **users:** base Laravel columns plus `role`, nullable `company_id` foreign key. Platform SuperAdmin has no company.
- **invitations:** `company_id`, `invited_by`, `email`, `role`, unique SHA-256 `token_hash`, `expires_at`, nullable `accepted_at`, timestamps.
- **short_urls:** `company_id`, `user_id` (creator), unique `code`, `original_url`, `visits`, timestamps.
- **Laravel tables:** migrations, sessions, cache, queue-related tables as configured by the Laravel skeleton.

Relationships: Company has many Users, Invitations, and ShortUrls; User belongs to Company and creates ShortUrls; Invitation belongs to Company and records its inviter.

## Project structure

```text
app/
  Http/Controllers/
    AuthController.php
    CompanyController.php
    InvitationController.php
    ShortUrlController.php
  Models/
    Company.php
    Invitation.php
    ShortUrl.php
    User.php
database/
  migrations/
    0001_01_01_000000_create_users_table.php
    0001_01_01_000001_create_cache_table.php
    0001_01_01_000002_create_jobs_table.php
    2026_01_01_000001_create_companies_table.php
    2026_01_01_000002_add_company_role_to_users.php
    2026_01_01_000003_create_invitations_table.php
    2026_01_01_000004_create_short_urls_table.php
  seeders/DatabaseSeeder.php
resources/views/
  auth/ companies/ invitations/ layouts/ urls/
  dashboard.blade.php
public/style.css
routes/web.php
tests/Feature/AssessmentTest.php
```

Directory contents reflect the provided application implementation; a complete repository additionally contains the normal Laravel framework skeleton.

## Automated testing with MySQL

> **Warning:** Laravel tests using `RefreshDatabase` can change their configured database. Never run them against your regular `url_shortener` database containing valuable data.



For manual acceptance testing, check forbidden actions using both the UI and direct authenticated HTTP requests; a hidden button does not establish authorization.

## Assumptions and trade-offs

1. The brief says the service must **not have multiple companies**, yet refers to URL listings outside an Admin's company. This implementation strictly enforces one company; therefore the Admin outside-company list is empty. If the intended requirement was multi-tenancy, this design needs changing.
2. The line “each user will neither be an Admin nor a Member” conflicts with the explicit five-role specification. This implementation includes both Admin and Member.
3. The brief simultaneously calls for redirects and says short URLs should not be *publicly* resolvable. This implementation requires login for `/s/{code}`, then redirects authorized company users.
4. Sales and Manager were chosen as URL creators because SuperAdmin, Admin, and Member are explicitly prohibited from creating URLs.
5. Invitations generate copyable one-time links; email delivery is out of scope.
6. This is an assessment implementation, not a production-hardened URL redirect platform. For production, review destination safety/SSRF-adjacent concerns, abuse prevention, rate limiting, audit logging, centralized authorization policies, and deployment security.

## Troubleshooting

| Issue | Check |
| --- | --- |
| `could not find driver` | Enable PHP `pdo_mysql` |
| `Connection refused` | Start MySQL, check host and port |
| `Table ... doesn't exist` | Run/inspect migrations against the configured MySQL DB |
| `Table ... already exists` | Inspect migration history and duplicates; do **not** blindly run `migrate:fresh` |
| Login works but `/urls` is 403 for SuperAdmin | Expected by design |
| Admin URL listing empty | Expected in one-company interpretation |
| Invitation link expired/reused | Issue a fresh invite |
| Test database warning | Verify `.env.testing`, `phpunit.xml`, and `db:show --env=testing` |

## AI use and authorship disclosure

This assessment was developed with assistance from **ChatGPT** for debugging Laravel/MySQL errors, proposing and drafting README documentation.

