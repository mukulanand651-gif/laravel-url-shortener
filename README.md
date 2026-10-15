# Multi-Company Laravel URL Shortener

A Laravel 12 + MySQL assessment project with **multiple companies**, company-scoped **Admin/Member** accounts, a platform **SuperAdmin**, invitation-based registration and **publicly resolving** short links. Blade, HTML and CSS provide the UI.

> This revision replaces an earlier single-company implementation that followed incorrect transcribed requirements. The provided assignment screenshots explicitly require multiple companies, Admin/Member creators and **public** redirection.

## Requirements coverage

| Requirement | Implementation |
| --- | --- |
| Multiple companies, multiple users each | `companies` table; `users.company_id`; unlimited companies |
| User roles | Company roles `Admin`, `Member`, `sale`, `Manager`; global `SuperAdmin` |
| SuperAdmin seeded | `database/seeders/DatabaseSeeder.php` uses parameterized raw SQL |
| Login / logout | Laravel session authentication |
| SuperAdmin invites first Admin into **new company** | `/invite`: new company name + Admin invitation, created atomically |
| Admin invites Admin or Member in own company | `/invite`; company is derived from authenticated Admin |
| Admin/Member create short URL | Authenticated `POST /urls` |
| SuperAdmin cannot create URL | Returns HTTP 403 |
| SuperAdmin lists all companies' URLs | `GET /urls` no company filter |
| Admin lists URLs created in own company | `GET /urls` filtered by company ID |
| Member lists only own URLs | `GET /urls` filtered by company ID and creator ID |
| Anyone can resolve a short URL | Public `GET /s/{code}` returns 302 to original, increments visits |

## Requirements and installation

- PHP 8.2+, Composer, PHP `pdo_mysql`, `mbstring`, `openssl` extensions
- MySQL 8 or a compatible MariaDB server (XAMPP works for local development)
- Git

```bash
git clone https://github.com/mukulanand651-gif/laravel-url-shortener.git
cd laravel-url-shortener
composer install
```

**Package contents:** This ZIP includes the Laravel application source, Laravel skeleton files, composer manifests, migrations, tests and documentation. It excludes `vendor/`, `.git/`, local `.env` files and secrets. Extract it, run `composer install`, and configure your environment as below. The GitHub clone instructions apply after you publish this updated version to the repository.

### Create the MySQL development database

```sql
CREATE DATABASE IF NOT EXISTS url_shortener CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Copy `.env.example` to `.env` and set these values (use the actual local credentials):

```dotenv
APP_NAME="Multi-Company URL Shortener"
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
SEED_SUPERADMIN_PASSWORD=change-this-to-your-own-long-password
```

Do not commit `.env` or use the example credentials in production.

```bash
php artisan key:generate
php artisan config:clear
php artisan migrate
php artisan db:seed
php artisan serve
```

Visit `http://127.0.0.1:8000/login` and sign in using the configured seeded SuperAdmin account. The seeder is idempotent by email.

## Demo workflow

1. Sign in as SuperAdmin. Open **Invite**, enter a new company name, role **Admin**, and the first Admin's email. Submit and copy the link shown. This creates the new company and first Admin invitation in one transaction.
2. Open the invitation in a private browser, complete name/password registration. The invitation expires after 48 hours and can only be used once.
3. Log in as that Admin. Open **Invite** to add an **Admin** or **Member** to **their own company**.
4. Log in as an Admin or Member, open **URLs**, enter `https://example.com` and click **Generate**. The app creates a random code.
5. A Member sees **only their own URLs**. An Admin sees URLs from all creators in **their own company**. SuperAdmin sees URLs from **all companies**.
6. Copy a generated `http://127.0.0.1:8000/s/{code}` link into a private (logged-out) browser window. It must redirect straight to the saved original URL, without asking for login.
7. Repeat steps 1–6 with a second company to demonstrate isolation and global SuperAdmin visibility.

**Note:** Invitation emails are not actually sent. The app generates a copyable invitation link for manual sharing. The assessment does not require an email delivery provider.

## Main routes

| Method | Path | Authentication | Behavior |
| --- | --- | --- | --- |
| GET/POST | `/login` | Guest | Login |
| POST | `/logout` | Signed in | Logout |
| GET | `/dashboard` | Signed in | Role-aware dashboard |
| GET/POST | `/companies` | SuperAdmin | List/create companies |
| GET/POST | `/invite` | SuperAdmin/Admin | Invite first Admin/new company or invite into own company |
| GET/POST | `/invitations/{token}` | Guest | Complete invitation registration |
| GET | `/urls` | Signed in | Role-filtered list |
| POST | `/urls` | Admin/Member | Create short link |
| GET | `/s/{code}` | **Public** | Redirect to original URL |

## Data model

- `companies`: id, name, timestamps. No single-company uniqueness restriction.
- `users`: Laravel columns plus role and nullable company ID. SuperAdmin has a null company; Admin/Member belong to a company.
- `invitations`: company ID, inviter, email, role, hashed token, expiry, accepted timestamp.
- `short_urls`: code (unique), original URL, company ID, creator ID, visit counter.

## Automated feature tests (MySQL)

**Never run tests against your development database.** Tests use `RefreshDatabase` and can clear data.

Create a separate database:

```sql
CREATE DATABASE IF NOT EXISTS url_shortener_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Copy `.env.testing.example` to `.env.testing` and set your local MySQL credentials. Make sure that `phpunit.xml` targets `url_shortener_test`. Verify testing connection using `php artisan migrate:status --env=testing` (the first run may show missing migration table), or Tinker `DB::connection()->getDatabaseName()` under `--env=testing`.

```bash
php artisan test --testsuite=Feature --filter=AssessmentTest
```

The test file covers multiple companies, Admin/Member creation, SuperAdmin create restriction/global visibility, company isolation, Member ownership filtering, publicly resolvable redirects, both invitation workflows, single-use/expiry checks, and login/logout.

**Verification:** PHP syntax checks on the included PHP files were run when packaging. The revised Laravel feature suite has **not** been executed against a live MySQL database here; run it locally and record the results before submission. Results of the former single-company test suite **do not** validate this revised implementation.

## Updating an earlier single-company installation

The ZIP includes `database/migrations/2026_01_01_000005_remove_single_company_constraint.php`. It removes the previous `singleton_key` unique column **if present**. For a fresh database, the new `create_companies_table` migration already omits it. Never use `migrate:fresh` on a database whose records you need to keep. Back up existing data first and run `php artisan migrate`.

Existing `Sales`/`Manager` users from the earlier incorrect implementation are outside the requested role model. Review and migrate their roles to `Admin` or `Member` according to actual business decisions; do not automatically alter them without approval.

## Troubleshooting

- Missing `pdo_mysql`: enable the extension for the PHP CLI.
- Missing migration tables: confirm the MySQL database and migration files.
- Duplicate tables: inspect the migrations tracking table rather than dropping production data.
- 403 for Admin/Member creation: ensure you're running the revised controllers and signed into a company account.
- Public redirects unexpectedly going to login: ensure `/s/{code}` is outside `Route::middleware('auth')` and clear routes using `php artisan optimize:clear`.
- Test runner errors: check `tests/TestCase.php`, `tests/Feature/AssessmentTest.php`, `phpunit.xml` and test DB connectivity.

## AI assistance and author contribution

This project has involved substantial ChatGPT assistance,  feature tests and documentation, as well as debugging.

