# SiteTrack

Job management, invoicing and bookkeeping for construction companies.

**Stack:** Laravel 12 · PostgreSQL · Vue 3 SPA · Tailwind CSS · Vite · Sanctum token auth

---

## Features

**Operations**
- **Clients:** customer records with a statement (opening balance, transactions with running balance) for any date range
- **Jobs:** auto-numbered (`JOB-2026-0001`), with a status workflow
- **Employees (casuals):** worker directory, Excel import, assignment to jobs by work date

**Finance**
- **Invoices:** labour/material line items, VAT, auto-numbered (`INV-2026-0001`), PDF download, email sending
- **Payments:** recording a payment updates the invoice status (unpaid → partial → paid)
- **Expenses:** categories, departments, cost centres, and an approval status
- **Reports:** aging, revenue, profit & loss, jobs summary, employee jobs
- **Exports:** Excel/CSV for lists and reports; PDF for invoices and statements

**Platform**
- **Multi-company:** a user can belong to several companies and switch between them; all data is scoped to the current company
- **Audit log** (owner/admin): records who created, changed, deleted or restored customers, jobs, employees, invoices, payments, expenses, company settings and team members, with before/after values. A deleted record can be restored from its log entry.
- **Dark mode:** toggle in the sidebar, remembered per browser
- **Roles** (per company):

  | Role | Access |
  |---|---|
  | owner | Everything, including company settings |
  | admin | Users, clients, jobs, employees, and all finance |
  | finance | Invoices, payments, expenses, reports, exports; read-only clients and jobs |
  | member | Own profile only |

---

## Local setup

**Requirements:** PHP 8.2+ (with `pdo_pgsql`, `mbstring`, `xml`, `gd`, `zip`, `intl`), Composer, Node 20+, PostgreSQL.

1. Create a database and user:
   ```bash
   sudo -u postgres psql -c "CREATE USER sitetrack WITH PASSWORD 'secret';" -c "CREATE DATABASE sitetrack OWNER sitetrack;"
   ```
2. Configure the app:
   ```bash
   cp .env.example .env
   ```
   Set `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://localhost:8000`, and the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` values.
3. Install and migrate:
   ```bash
   composer install
   php artisan key:generate
   php artisan migrate
   npm install
   ```
4. (Optional) Seed one user per role, all with password `password`:
   ```bash
   php artisan db:seed
   ```
   Creates `owner@`, `admin@`, `finance@` and `member@sitetrack.test`.
5. Start everything (Laravel server, queue, logs, Vite):
   ```bash
   composer dev
   ```
   Open http://127.0.0.1:8000.

---

## Tests

Tests run against PostgreSQL in a separate `testing` schema of the same database, so they never touch the app's data. Create the schema once:

```bash
psql -h 127.0.0.1 -U sitetrack -d sitetrack -c "CREATE SCHEMA IF NOT EXISTS testing;"
```

Then:

```bash
php artisan test
```

The base `TestCase` refuses to reset any schema other than `testing`.

Coverage: company data isolation, role permissions, every report's figures, and every export.

---

## Deployment

Deployed on Railway with Nixpacks (`railway.json`, `nixpacks.toml`). The start command runs `php artisan migrate --force` before serving. Railway's `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER` and `PGPASSWORD` variables are picked up automatically.

---

## Notes for developers

- **API:** every endpoint is under `/api`, returns JSON, and authenticates with a Bearer token (stored in `localStorage` by the SPA).
- **Downloads:** files must be fetched through `api.download(url)` in `resources/js/api.js`, so the token is sent. A plain `<a href>` to an `/api` URL gets a 401.
- **Audit trail:** to track a model, add the `Auditable` trait and an entry in `AuditLog::TYPES`. For changes that aren't model events, such as role changes in `company_user`, call `AuditLog::record()` directly.
- **Company scoping:** tenant models use the `BelongsToCompany` trait, which applies `CompanyScope` and fills `company_id` on create. Raw `DB::table()` queries bypass this. To validate a reference to another record, use `company_exists:table,id`, not `exists:`.
- **Styling** (`resources/css/app.css`):
  - Colours come from the tokens at the top of `app.css` (`var(--primary-600)`, `var(--danger-100)`, …). Don't put hex values in pages.
  - Shared building blocks (page header, cards, tables, forms, modals, loaders, empty states, buttons) are defined once in `app.css`. Pages use those classes, and only add `<style scoped>` rules for layout unique to that page.
- **PDF templates** (`resources/views/pdf`): use `DejaVu Sans` with `font-weight` of `normal` or `bold` only. dompdf renders any other weight in a serif fallback font.
