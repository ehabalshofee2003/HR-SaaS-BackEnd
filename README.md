# HR-SaaS — Multi-Tenant HR & Payroll Management Platform

A multi-tenant SaaS platform for HR and payroll management, built for small and medium businesses that need a unified digital replacement for spreadsheets, paper trails, and messaging-app workflows. Companies subscribe to the platform and manage their branches, departments, employees, attendance, leave, payroll, and performance — all through role-scoped interfaces that mirror a real organizational hierarchy.

---

## Table of Contents

- [Overview](#overview)
- [Role Hierarchy](#role-hierarchy)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Environment Variables](#environment-variables)
- [Authentication](#authentication)
- [Permission System](#permission-system)
- [API Overview](#api-overview)
- [Project Status](#project-status)
- [Roadmap](#roadmap)
- [Contributing](#contributing)

---

## Overview

HR-SaaS solves a common problem for growing companies: HR processes scattered across spreadsheets, paper, and chat apps. The platform centralizes attendance tracking, leave management, payroll processing, task assignment, performance evaluation, and internal communication into a single system — with a permission model flexible enough to match how authority actually flows in a real company, rather than forcing rigid, one-size-fits-all roles.

## Role Hierarchy

```
Super Admin   → owns and operates the platform itself, across all subscribing companies
    Owner     → owns a subscribed company, manages all of its branches
        Branch Manager → manages a single branch end-to-end
            Supervisor  → manages a subset of employees within a branch
                Employee → self-service: attendance, leave requests, tasks, payslips
```

Each role operates strictly within its own scope. Permissions are **delegated down the chain, not hardcoded**: a Branch Manager can grant a Supervisor exactly the permissions they need, no more — see [Permission System](#permission-system).

## Key Features

- **Dynamic QR attendance** — check-in/check-out codes regenerate continuously and are single-use, preventing replay or sharing
- **Delegated permission system** — permissions flow down the role hierarchy on a per-user basis, not via fixed roles
- **Multi-stage payroll** — periods move through `Draft → Calculated → Approved → Paid`, with full per-employee breakdowns (base, bonuses, deductions, overtime)
- **Bilingual-ready reporting** — attendance, payroll, financial, and performance reports, exportable to PDF and Excel with correct Arabic/RTL rendering
- **Passwordless authentication** — OTP-based login for every role except Super Admin, who uses email + password for operational reliability
- **True multi-tenancy** — every company's data is strictly isolated at the row level; cross-tenant access attempts return `404`, not `403`, so a resource's existence is never leaked to another tenant

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11 (PHP), MySQL 8 |
| Auth | Laravel Sanctum (token-based) + Spatie Permission (direct grants, no fixed roles) |
| Mobile | Flutter (Employee + Supervisor shared app, plus a separate Owner Portal app) |
| Web | React 18 (separate apps for Branch Manager, Owner, and Super Admin) |
| PDF export | mpdf (chosen over dompdf for correct Arabic/RTL rendering) |
| Excel export | maatwebsite/excel |
| SMS delivery | HTTP-based gateway integration for OTP delivery |

## Architecture

The backend follows a strict **Repository + Service Layer** pattern:

```
Controller (thin — request handling only)
    → Service (all business logic and validation)
        → Repository (query builder via DB::table(), manual soft-delete handling)
```

- Controllers never contain business logic; they call a Service and shape the response.
- Repositories use `DB::table()` rather than Eloquent ORM throughout, giving explicit control over complex joins across the organizational hierarchy.
- Every Repository is bound to an Interface for testability and swappability.

### Data Model

```
Company → Branch → Department → Employee (User + EmployeeDetail)
                                → Supervisor (User, direct branch_id)
              → Manager (User, direct branch_id + company_id)
```

Supervisors and Branch Managers are `users` rows **without** an `employee_details` row — a deliberate distinction that several parts of the codebase must account for when resolving a user's branch (always via `users.branch_id` directly, never through the `employee_details → department → branch` chain).

### Multi-Tenancy

Tenancy is enforced via row-level `company_id` scoping in every query, rather than a database-per-tenant model — the right tradeoff for the platform's current scale, with a clear migration path (partitioning or hybrid database-per-large-tenant) if growth demands it later.

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/V1/{Actor}/     # thin controllers, one namespace per role
│   └── Requests/{Actor}/{Epic}/        # form request validation
├── Services/{Epic}/                    # business logic
├── Repositories/{Epic}/                # DB::table() query layer
└── Models/{Domain}/                    # Identity, Organization, Hr, Payroll, Support

routes/
└── api/{actor}.php                     # one route file per role

resources/views/exports/                # PDF export blade templates
```

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 8
- Node.js + npm (for frontend apps)
- Flutter SDK (for mobile apps)

### Backend Setup

```bash
git clone <repository-url>
cd hr-saas

composer install
cp .env.example .env
php artisan key:generate

# configure your database and SMS gateway credentials in .env

php artisan migrate --seed
php artisan serve
```

### Frontend Setup (Branch Manager / Owner / Super Admin — React)

```bash
cd frontend/<app-name>
npm install
cp .env.example .env
# set VITE_API_BASE_URL to point at your backend

npm run dev
```

### Mobile Setup (Flutter)

```bash
cd mobile/<app-name>
flutter pub get
flutter run
```

## Environment Variables

Key variables to configure in `.env`:

```env
DB_DATABASE=hr_saas
DB_USERNAME=
DB_PASSWORD=

SMS_ENABLED=true
SMS_GATEWAY_API_KEY=

OTP_EXPIRY_MINUTES=5
OTP_RETRY_COOLDOWN_SECONDS=60
```

> **Never commit real SMS gateway keys or `.env` files to version control.** Use `.env.example` as a template with placeholder values only.

## Authentication

All roles except Super Admin use a phone-number + OTP flow — no passwords:

```
POST /auth/send-otp     { "phone": "0912345678" }
POST /auth/verify-otp   { "phone": "0912345678", "otp": "123456" }
```

A successful verification returns a Sanctum bearer token and the user's granted permission list, used by every frontend to drive UI visibility.

Super Admin uses traditional email + password login, a deliberate exception — tying platform-administration access to SMS delivery reliability was judged too risky a single point of failure.

## Permission System

Rather than fixed roles, permissions are **delegated directly, user to user**, down the hierarchy:

- A new Branch Manager receives all manager-level permissions automatically; an Owner can revoke specific ones.
- A new Supervisor starts with zero permissions; their Branch Manager grants exactly what's needed.
- No user can delegate a permission they don't themselves hold — enforced server-side on every grant.

This means the UI for managing a subordinate's access is the same pattern everywhere: fetch the grantor's own permission set, fetch the target's currently-granted subset, render as a checklist, save the full selection back as a single `PUT`.

## API Overview

The API is versioned and namespaced by role:

```
/api/v1/employee/...
/api/v1/supervisor/...
/api/v1/branch-manager/...
/api/v1/owner/...
/api/v1/super-admin/...
/api/auth/...            (shared across all roles)
```

Each role's routes live in their own file under `routes/api/`, and each endpoint enforces its own permission middleware (e.g. `permission:employees.view`) where applicable.

## Project Status

| Component | Status |
|---|---|
| Employee mobile backend | ✅ Complete |
| Branch Manager backend | ✅ Complete |
| Branch Manager frontend (React) | ✅ Complete |
| Super Admin backend (core) | ✅ Complete |
| Supervisor mobile backend | 🟡 Mostly complete |
| Owner backend | 🟡 In progress |
| Owner frontend | ⬜ Not started |
| Super Admin frontend | 🟡 Login screen only |
| Employee/Supervisor mobile app | 🟡 In progress |
| Owner Portal mobile app | 🟡 Early stage |

## Roadmap

- [ ] Complete remaining Owner backend epics (Complaints, Resignations, Announcements, Notifications, Support Tickets)
- [ ] Build the Owner React frontend
- [ ] Complete Super Admin frontend (Users, Billing, Analytics, Audit Logs, Support Tickets)
- [ ] Finish Supervisor backend (Evaluations, Leave review)
- [ ] Unify mobile app authentication/session storage
- [ ] Expand automated test coverage

## Contributing

This is currently a closed, actively developed project. If you're part of the team, follow the coding conventions documented above (thin controllers, Repository + Service pattern, `DB::table()` in repositories, no business logic in controllers) and open a PR against `main` with a clear description of the change and which epic/module it touches.

---

*Built with Laravel, React, and Flutter.*
