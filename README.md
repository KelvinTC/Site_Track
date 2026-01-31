# SiteTrack - Job Management System

**Built for Mount Sinai Construction**

A complete job tracking, invoicing, and payment management system for construction companies.

---

## 🚀 What's Built

### ✅ Backend (Laravel 12 + PostgreSQL)

**Database Tables:**
- `customers` - Customer information with soft deletes
- `site_jobs` - Jobs with auto-generated job numbers (JOB-2026-0001)
- `invoices` - Invoices with auto-generated numbers (INV-2026-0001)
- `invoice_items` - Line items for labor/materials
- `payments` - Payment tracking with auto invoice status updates

**API Endpoints** (`/api/`)
```
Customers:  GET, POST, PUT, DELETE /api/customers
Jobs:       GET, POST, PUT, DELETE /api/site-jobs
            PATCH /api/site-jobs/{id}/status
Invoices:   GET, POST, PUT, DELETE /api/invoices
Payments:   GET, POST, PUT, DELETE /api/payments
```

**Features:**
- Auto job number generation
- Auto invoice number generation
- Payment auto-updates invoice status (unpaid/partial/paid)
- Balance calculation on invoices
- Soft deletes on all models

---

### ✅ Frontend (Vue.js 3 SPA)

**Pages:**
1. **Dashboard** - Stats cards + recent jobs table
2. **Customers** - Full CRUD with search
3. **Jobs** - Full CRUD with status filter & inline status updates
4. **Invoices** - (Pending)
5. **Payments** - (Pending)

**Technology:**
- Vue 3 Composition API
- Vue Router for navigation
- Axios for API calls
- Tailwind CSS for styling

---

## 🔧 Setup Instructions

### Prerequisites
- PHP 8.3+
- PostgreSQL
- Node.js 20+ (required for Vite)
- Composer

### Installation

1. **Install PHP dependencies:**
```bash
composer install
```

2. **Configure database:**
Edit `.env`:
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=mydb
DB_USERNAME=myuser
DB_PASSWORD=password
```

3. **Run migrations:**
```bash
php artisan migrate
```

4. **Install Node dependencies:**
```bash
npm install
```

5. **Start servers:**
```bash
# Terminal 1 - Laravel
php artisan serve

# Terminal 2 - Vite (requires Node 20+)
npm run dev
```

6. **Visit:** `http://127.0.0.1:8000`

---

## ⚠️ Current Issue

**Node.js version too old for Vite**

Current: v18.19.1
Required: v20.19+ or v22.12+

**Fix:**
```bash
# Using NVM
nvm install 20
nvm use 20
npm run dev

# Or install Node 20 directly
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

---

## 🎯 Workflow

1. **Create Customer** → Customers page
2. **Create Job** → Select customer, auto-generates job number
3. **Create Invoice** → Link to job, add line items
4. **Record Payment** → Invoice status auto-updates
5. **View Statement** → All customer transactions

---

## 📊 Features Completed

✅ Customer management (CRUD)
✅ Job tracking with status workflow
✅ Auto job numbering
✅ Auto invoice numbering
✅ Payment tracking
✅ Invoice status auto-update
✅ Dashboard with stats
✅ Search & filters

---

## 🚧 Features Pending

- Invoice CRUD with line items
- Payment CRUD page
- Customer statements (date range, balance)
- PDF invoice generation
- Email notifications
- User authentication
- Role-based permissions

---

## 🗄️ Database Schema

### Customers
`id, name, email, phone, address, city, state, zip, notes, is_active`

### Site Jobs
`id, job_number, customer_id, title, description, status, start_date, end_date, estimated_cost, actual_cost, site_address, notes`

### Invoices
`id, invoice_number, site_job_id, customer_id, issue_date, due_date, subtotal, tax, total, status, notes`

### Invoice Items
`id, invoice_id, description, type (labor/material/other), quantity, unit_price, total`

### Payments
`id, invoice_id, amount, payment_date, payment_method, reference_number, notes`

---

## 🔌 API Testing

```bash
# Get all customers
curl http://127.0.0.1:8000/api/customers

# Create customer
curl -X POST http://127.0.0.1:8000/api/customers \
  -H "Content-Type: application/json" \
  -d '{"name": "John Doe", "email": "john@example.com", "phone": "555-1234"}'

# Create job
curl -X POST http://127.0.0.1:8000/api/site-jobs \
  -H "Content-Type: application/json" \
  -d '{"customer_id": 1, "title": "Kitchen Renovation", "status": "new"}'
```

---

## 📝 Development Notes

- Laravel server: `http://127.0.0.1:8000`
- Vite dev server: `http://127.0.0.1:5173` (proxied by Laravel)
- Database: PostgreSQL (`mydb` database)
- All API responses are JSON
- Frontend makes API calls to `/api/*` endpoints

---

## 👨‍💻 Built With

- **Laravel 12** - Backend framework
- **Vue.js 3** - Frontend framework
- **PostgreSQL** - Database
- **Tailwind CSS** - Styling
- **Vite** - Build tool
- **Axios** - HTTP client

---

**Next Steps:**
1. Upgrade Node.js to v20+
2. Run `npm run dev`
3. Visit `http://127.0.0.1:8000` to see the app with full CSS styling! 🚀
