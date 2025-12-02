Perfect, Niko ✅

You now have `TASK.md` as the **execution layer** — a detailed, agent-readable checklist.

The next logical file, `CONTEXT.md`, will act as the **strategic brain** for your AI or automation pipeline.
It will define:

* 🧠 **Project purpose, scope, and architecture**
* 🗂️ **Rules for how AI should interpret, execute, and update tasks**
* ⚙️ **Technical stack and environment context**
* 🔐 **RBAC role logic and permissions**
* 🪄 **AI operation workflow** — how an agent determines dependencies, progress updates, and verification
* 🧩 **Development conventions** (naming, folder structure, commit rules, etc.)

---

Below is your **ready-to-use `CONTEXT.md`** — optimized for large-language models or autonomous dev agents to understand and correctly execute your project:

---

# CONTEXT.md — HelpDesk Kemlu AI Execution Guide

**Version:** 2025-10-09
**Author:** Niko Dwicahyo Widiyanto
**Purpose:**
To guide AI agents or autonomous systems in understanding, executing, and maintaining the *HelpDesk Kemlu* full-stack project based on the tasks listed in `TASK.md`.
This file defines architecture, conventions, RBAC logic, dependencies, and execution workflow.

---

## 1. 🎯 Project Overview

The **HelpDesk Kemlu** system is an internal IT helpdesk platform for the Ministry of Foreign Affairs (KEMLU).
It centralizes user problem reporting, technician assignment, ticket monitoring, and reporting analytics.

### Objectives

* Build a modular, secure, and scalable web application.
* Enable multi-role access with clear RBAC logic.
* Support real-time notifications and scheduled reporting.
* Provide easy deployment and maintenance via VPS.

### Core Stack

| Layer          | Technology                                               |
| -------------- | -------------------------------------------------------- |
| Backend        | Laravel 12 (PHP 8.2+)                                    |
| Frontend       | Vue 3 + Inertia.js + TailwindCSS                         |
| Database       | MySQL 8.0                                                |
| Server         | Ubuntu 22.04 + Nginx + PHP-FPM                           |
| Authentication | Laravel Sanctum                                          |
| Messaging      | Laravel Queue + Mail + Broadcasting (Pusher / Socket.io) |
| Deployment     | GitHub Actions + Supervisor + Cron                       |
| Monitoring     | Logrotate + UptimeRobot / Sentry                         |

---

## 2. 🧩 System Architecture Summary

```
Client (Vue 3 + Inertia)
      ↓
Laravel Controllers (per Role)
      ↓
Services (Business Logic Layer)
      ↓
Models & Eloquent ORM
      ↓
MySQL Database
```

### Key Modules

1. Ticketing System
2. Notification & Comment System
3. User & Role Management
4. Application & Category Management
5. Reporting & Analytics
6. RBAC Authentication

---

## 3. 🔐 RBAC Model

### Roles

| Role           | Description                                         | Table             | Dashboard                   |
| -------------- | ----------------------------------------------------| ----------------- | --------------------------- |
| User           | Submits and monitors tickets                        | `users`           | `/user/dashboard`           |
| Admin Helpdesk | Full Access, Manage all role, Manages tickets,      | `admin_helpdesks` | `/admin/dashboard` |
|                |  assigns technicians, reports, and monitor          |                   |                             |
| Admin Aplikasi | Manages application and category data               | `admin_aplikasis` | `/admin-aplikasi/dashboard` |
| Teknisi        | Handles assigned tickets and resolves issues        | `teknisis`        | `/teknisi/dashboard`        |

### Access Logic

* Each role has **separate authentication table**.
* Login search order: Admin Helpdesk → Admin Aplikasi → Teknisi → User.
* Middleware enforces per-role access.
* Policies and Gates manage per-resource permissions.

---

## 4. ⚙️ AI Execution Workflow

### Step 1 — Read & Interpret Tasks

* The AI reads `TASK.md` from top to bottom.
* Each unchecked `[ ]` item represents an executable task.
* Each section defines logical dependencies (DB → BE → FE → Test → Deploy).

### Step 2 — Resolve Dependencies

* AI should **not start a task** until its preceding dependency group is complete.
  Example:

  * Don’t create `TicketController` before `Ticket` model & migration exist.
  * Don’t deploy before CI/CD and Supervisor setup are complete.

### Step 3 — Execute Development Actions

For each task:

1. Generate or edit code according to Laravel/Vue conventions.
2. Ensure code compiles, passes tests, and adheres to PSR-12.
3. Commit with semantic prefix:

   ```
   feat: <feature name>
   fix: <bug description>
   refactor: <component>
   chore: <non-code update>
   ```
4. Update `TASK.md` by marking `[x]` when done.

### Step 4 — Validation & Self-Check

After each operation:

* Run `php artisan test` → must pass.
* Run `npm run build` → must succeed.
* Verify migrations, routes, and logs.
* Validate Inertia views render with expected data.

### Step 5 — Document & Report

* AI updates `README.md` or inline docs where code changed.
* Add changelog entries automatically under `/docs/CHANGELOG.md`.
* Notify in logs when a milestone (section completion) is achieved.

---

## 5. 🧱 Folder Structure Standard

```
app/
 ├── Models/
 ├── Http/
 │    ├── Controllers/
 │    │     ├── User/
 │    │     ├── AdminHelpdesk/
 │    │     ├── AdminAplikasi/
 │    │     └── Teknisi/
 │    ├── Middleware/
 │    └── Requests/
 ├── Services/
 ├── Policies/
 ├── Observers/
 └── Providers/
resources/
 ├── css/
 ├── js/
 │    ├── Pages/
 │    │     ├── User/
 │    │     ├── AdminHelpdesk/
 │    │     ├── AdminAplikasi/
 │    │     └── Teknisi/
 │    └── Components/
 └── views/
database/
 ├── migrations/
 ├── seeders/
 └── factories/
routes/
 ├── api.php
 ├── web.php
 └── console.php
tests/
 ├── Unit/
 ├── Feature/
 └── EndToEnd/
```

---

## 6. 📋 Development Conventions

### Commit Messages

Use the following prefixes:

```
feat: new feature
fix: bug fix
refactor: code improvement
chore: maintenance
test: adding or updating tests
docs: documentation updates
```

### Branching Model

```
main ← staging ← dev ← feature/*
```

### Code Style

* Backend: PSR-12 + Laravel naming conventions
* Frontend: ESLint + Prettier
* Test coverage target: ≥ 60%

---

## 7. 🧠 AI Behavior Rules

1. **Never modify both backend and frontend in one commit** — keep atomic changes.
2. **Run unit tests** after every backend modification.
3. **Check `.env` variables** before deploying (never expose secrets).
4. **Mark completed tasks** by replacing `[ ]` → `[x]` in `TASK.md`.
5. **If a failure occurs**, revert the last change and append an error log to `logs/agent.log`.
6. **Do not modify CONTEXT.md** — treat it as immutable.
7. **Always validate migrations** (`php artisan migrate:status`) before running.
8. **All generated files must follow structure above**.
9. **Use `.env.example` as base** for local and staging configuration.
10. **Follow RBAC restrictions strictly** — no cross-role UI leaks or unauthorized access.

---

## 8. 📦 Environment Variables (Required)

| Key                | Description                                 |
| ------------------ | ------------------------------------------- |
| APP_ENV            | Environment type (local/staging/production) |
| APP_URL            | Base URL of the app                         |
| DB_HOST            | Database host                               |
| DB_PORT            | Database port                               |
| DB_DATABASE        | Database name                               |
| DB_USERNAME        | Database user                               |
| DB_PASSWORD        | Database password                           |
| MAIL_MAILER        | smtp/sendmail/log                           |
| MAIL_HOST          | SMTP server                                 |
| MAIL_PORT          | SMTP port                                   |
| MAIL_USERNAME      | Mail user                                   |
| MAIL_PASSWORD      | Mail password                               |
| PUSHER_APP_KEY     | Real-time key (if used)                     |
| PUSHER_APP_CLUSTER | Pusher cluster                              |
| QUEUE_CONNECTION   | sync/redis/database                         |
| SESSION_DRIVER     | file/database                               |
| CACHE_DRIVER       | file/redis                                  |

---

## 9. 🚀 Deployment Flow (Ubuntu VPS)

1. SSH into VPS as `deploy` user
2. Pull latest code from `main` branch
3. Run `composer install --no-dev --optimize-autoloader`
4. Run `npm ci && npm run build`
5. Run `php artisan migrate --force`
6. Clear caches:

   ```
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan optimize
   ```
7. Restart queue and supervisor
8. Verify HTTPS and service availability
9. Run `php artisan test` for post-deploy validation

---

## 10. 🧾 Example Execution Flow (for AI Agent)

```text
1. Read CONTEXT.md (this file)
2. Load TASK.md
3. Locate first unchecked [ ] item under "Database Implementation"
4. Execute code generation / command
5. Validate change (run tests, migration)
6. Mark [x] when done
7. Proceed sequentially until all tasks complete
8. Generate final changelog + deployment confirmation
```

---

## 11. 🧩 Acceptance Rules for AI Completion

* All `[ ]` in `TASK.md` are `[x]`
* `php artisan test` returns 0 exit code
* `npm run build` succeeds
* `php artisan migrate:status` shows all ran
* System accessible on HTTPS
* Documentation updated and synced

---

## 12. ✅ Success Definition

✅ The project is **considered complete** when:

1. All checklists in `TASK.md` are marked `[x]`.
2. Final deployment is live and accessible.
3. All four RBAC roles can log in and perform their intended actions.
4. Notifications and reports function as defined.
5. Admin documentation and handover package are finalized.

---
