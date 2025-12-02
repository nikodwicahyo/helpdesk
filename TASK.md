# TASK.md — HelpDesk Kemlu (Full-stack RBAC Project Plan)

**Stack:** Laravel 12 + Vue 3 (Inertia) + MySQL + Tailwind + Nginx (Ubuntu VPS)
**Format:** Clean atomic checklists `[ ]` for AI/automation tracking
**Purpose:** Complete full-stack development, testing, and deployment roadmap

---

## 🔐 RBAC Implementation Overview

### Role Hierarchy & Permissions Matrix

| Role | Access Level | Database Table | Key Permissions |
|------|--------------|----------------|-----------------|
| **Admin Helpdesk** | L4 (Highest) | `admin_helpdesks` | FULL_ACCESS, USER_MANAGEMENT, SYSTEM_CONFIG, REPORTS |
| **Admin Aplikasi** | L3 | `admin_aplikasis` | APP_MANAGEMENT, KATEGORI_MANAGEMENT, VIEW_REPORTS |
| **Teknisi** | L2 | `teknisis` | TICKET_HANDLE, STATUS_UPDATE, COMMENT_ADD |
| **User** | L1 (Lowest) | `users` | TICKET_CREATE, TICKET_VIEW_OWN, COMMENT_ADD_OWN |

### Permission Granularity

```json
{
  "admin_helpdesk": {
    "tickets": ["view_all", "create", "update", "assign", "update_priority", "close", "delete"],
    "users": ["create", "read", "update", "delete", "activate", "deactivate"],
    "teknisi": ["create", "read", "update", "delete", "assign_tickets"],
    "reports": ["generate", "export", "schedule"],
    "system": ["configure", "backup", "restore"]
  },
  "admin_aplikasi": {
    "aplikasi": ["create", "read", "update", "delete"],
    "kategori": ["create", "read", "update", "delete"],
    "tickets": ["view_related"],
    "reports": ["view_aplikasi_stats"]
  },
  "teknisi": {
    "tickets": ["view_assigned", "update_status", "resolve", "comment"],
    "profile": ["update_own"]
  },
  "user": {
    "tickets": ["create", "view_own", "comment_own", "close_own", "rate"],
    "profile": ["update_own"]
  }
}
```

## 🗃️ Database Implementation [0/20]

### General Setup

* [ ] Initialize Laravel project and `.env` for MySQL connection
* [ ] Create migration base folder structure
* [ ] Configure database connection in `.env` and `config/database.php`
* [ ] Verify `php artisan migrate` runs successfully

### Schema Design

* [ ] Create table `users` with fields: id, nip, name, email, password, is_active, timestamps
* [ ] Create table `admin_helpdesks` with same structure as `users`
* [ ] Create table `admin_aplikasis` with same structure as `users`
* [ ] Create table `teknisis` with same structure as `users`
* [ ] Create table `aplikasis` (id, nama_aplikasi, deskripsi, timestamps)
* [ ] Create table `kategori_masalahs` (id, nama_kategori, aplikasi_id, timestamps)
* [ ] Create table `tickets` with references to `users`, `teknisis`, `aplikasis`, `kategori_masalahs`
* [ ] Create table `ticket_comments` linked to `tickets` (user_id, message, attachments, timestamps)
* [ ] Create table `ticket_history` to log changes (ticket_id, actor_type, actor_id, action, timestamp)
* [ ] Create table `notifications` for in-app alerts
* [ ] Create table `reports` for generated exports (type, period, file_path)
* [ ] Define foreign keys and cascading delete/update rules
* [ ] Add indexes on ticket_number, user_id, status, created_at
* [ ] Implement data seeders for all tables
* [ ] Add factories for testing (UserFactory, TicketFactory, etc.)
* [ ] Verify migrations and seeders execute without errors

---

## ⚙️ Backend Development (Laravel) [0/70]

### Core Setup

* [ ] Install Laravel dependencies (Sanctum, Breeze, Spatie Permissions optional)
* [ ] Setup Inertia.js server-side configuration
* [ ] Setup Mail and Queue configuration
* [ ] Add custom helpers folder (`app/Helpers`)

---

### 🧍 User

* [ ] Create `Ticket` model with fillable attributes and relationships
* [ ] Create `TicketService` for ticket operations
* [ ] Implement `generateTicketNumber()` function
* [ ] Create `User/TicketController` with methods: index, create, store, show, addComment, close
* [ ] Implement file upload system for attachments
* [ ] Implement ticket creation validation (FormRequest)
* [ ] Add comment and close functionality
* [ ] Update ticket status flow for users
* [ ] Log ticket creation in `ticket_history`
* [ ] Trigger notification when user creates a ticket

---

### 🧭 Admin Helpdesk

* [ ] Create `AdminHelpdesk/TicketManagementController`
* [ ] Implement ticket assignment to teknisi
* [ ] Add bulk assignment feature
* [ ] Add ability to override ticket priority/status
* [ ] Create `AdminHelpdesk/UserManagementController`
* [ ] Implement user CRUD (create/update/delete)
* [ ] Implement CSV import for bulk users
* [ ] Add reset password feature for users
* [ ] Log all admin helpdesk actions in `ticket_history`
* [ ] Trigger notifications when tickets are assigned

---

### 🖥️ Admin Aplikasi

* [ ] Create `AdminAplikasi/AplikasiController`
* [ ] Implement CRUD for aplikasi data
* [ ] Create `AdminAplikasi/KategoriMasalahController`
* [ ] Implement CRUD for kategori masalah
* [ ] Update dependent dropdown logic on kategori → aplikasi
* [ ] Ensure delete cascades to related kategori
* [ ] Add validation for aplikasi/kategori fields
* [ ] Reflect kategori changes on user ticket form

---

### 🧰 Teknisi

* [ ] Create `Teknisi/TicketHandlingController`
* [ ] Implement teknisi dashboard endpoint (assigned, in progress, resolved)
* [ ] Add update ticket status feature
* [ ] Implement internal note functionality
* [ ] Add ability to upload resolution files
* [ ] Record actions in `ticket_history`
* [ ] Trigger notification to user when resolved
* [ ] Implement SLA calculation service

---

### 🧩 Shared Backend Logic

* [ ] Create `NotificationService` for system-wide events
* [ ] Implement queue-based mail notifications
* [ ] Add `ReportService` for data exports (Excel & PDF)
* [ ] Implement report filters (by aplikasi, category, technician, status)
* [ ] Add background job for heavy report generation
* [ ] Configure API routes for tickets, notifications, reports
* [ ] Add rate-limiting for APIs
* [ ] Implement error logging (Monolog / Sentry)
* [ ] Add unit tests for all services
* [ ] Validate data integrity via Policies

---

## 🎨 Frontend Development (Vue 3 + Inertia) [0/65]

### Base Setup

* [ ] Configure Vite + Vue 3 + TailwindCSS
* [ ] Setup Inertia pages structure
* [ ] Create `AppLayout.vue`, `Navbar.vue`, `Sidebar.vue`
* [ ] Implement responsive navigation with role-based menus
* [ ] Add Dark/Light mode toggle

---

### 👤 User Interface

* [ ] Create `User/Dashboard.vue`
* [ ] Create `User/TicketList.vue`
* [ ] Create `User/TicketCreate.vue`
* [ ] Create `User/TicketDetail.vue`
* [ ] Add file upload component
* [ ] Add ticket comment input with preview
* [ ] Display ticket history timeline
* [ ] Integrate notifications dropdown
* [ ] Add pagination and filters by status
* [ ] Validate all form inputs before submission

---

### 🧭 Admin Helpdesk UI

* [ ] Create `AdminHelpdesk/Dashboard.vue`
* [ ] Create `AdminHelpdesk/TicketManagement.vue`
* [ ] Add bulk selection and assign modal
* [ ] Add user management page
* [ ] Create CSV upload modal for users
* [ ] Display real-time notifications on ticket assignment
* [ ] Add ticket statistics chart by category and status
* [ ] Implement search bar for users and tickets

---

### 🖥️ Admin Aplikasi UI

* [ ] Create `AdminAplikasi/Dashboard.vue`
* [ ] Create `AdminAplikasi/AplikasiManagement.vue`
* [ ] Create `AdminAplikasi/KategoriManagement.vue`
* [ ] Add linked dropdowns (aplikasi → kategori)
* [ ] Add data table with sort & pagination
* [ ] Add edit & delete confirmation dialogs
* [ ] Validate aplikasi/kategori CRUD forms

---

### 🧰 Teknisi UI

* [ ] Create `Teknisi/Dashboard.vue` with Kanban-style board
* [ ] Add columns: Assigned, In Progress, Pending, Resolved
* [ ] Implement drag & drop to change status
* [ ] Create resolution upload modal
* [ ] Display internal comments only for teknisi
* [ ] Add real-time update of ticket status

---

### 🔔 Shared Components

* [ ] Create `NotificationBell.vue`
* [ ] Create `TicketCard.vue` reusable component
* [ ] Add `Pagination.vue` reusable component
* [ ] Create `Modal.vue` base component
* [ ] Add `Loader.vue` for async actions
* [ ] Implement Toast notification system
* [ ] Add accessibility and ARIA attributes
* [ ] Test responsive breakpoints

---

## 🔐 Authentication & RBAC [0/20]

* [ ] Implement multi-table login system (users, admin_helpdesks, admin_aplikasis, teknisis)
* [ ] Create unified `AuthController` handling multiple roles
* [ ] Setup `RoleMiddleware` for route protection
* [ ] Create `AuthService` for NIP-based login
* [ ] Add logout and session timeout logic
* [ ] Create Sanctum API token authentication
* [ ] Protect all routes with appropriate middleware
* [ ] Implement redirect by role (dashboard per role)
* [ ] Add password reset for each role
* [ ] Create `TicketPolicy`, `AplikasiPolicy`, and `UserPolicy`
* [ ] Add gate checks for update/delete permissions
* [ ] Add audit trail for all RBAC actions
* [ ] Display role info in Inertia shared props
* [ ] Add "Change Password" page for all roles
* [ ] Test login and access restrictions for each role

---

## 📣 Notification & Reporting [0/15]

* [ ] Implement `NotificationService` backend
* [ ] Configure Laravel Echo and Pusher/Socket.io
* [ ] Broadcast notifications on ticket events
* [ ] Create email templates for ticket actions
* [ ] Add queue workers for sending emails
* [ ] Add Notification settings page
* [ ] Implement mark-as-read functionality
* [ ] Add reports generator service
* [ ] Create report export endpoints (CSV, Excel, PDF)
* [ ] Add report filters by aplikasi and technician
* [ ] Build reports dashboard UI
* [ ] Schedule daily/weekly reports via cron job
* [ ] Send summary report via email to admin
* [ ] Test notification delivery performance

---

## 🧪 Testing & Quality Assurance [0/15]

* [ ] Write unit tests for models and services
* [ ] Write feature tests for ticket lifecycle
* [ ] Test login/logout and session behavior
* [ ] Test file uploads and validation errors
* [ ] Test email notification queue
* [ ] Test role-based route access
* [ ] Conduct integration tests (API + Inertia pages)
* [ ] Conduct end-to-end manual testing (UAT)
* [ ] Perform security tests (SQL injection, XSS)
* [ ] Conduct performance testing with JMeter
* [ ] Validate mobile responsiveness
* [ ] Ensure Lighthouse accessibility score > 90
* [ ] Verify audit trail logging works
* [ ] Fix all critical and high-severity bugs

---

## 🧰 DevOps & Deployment [0/20]

* [ ] Setup Ubuntu VPS environment
* [ ] Create deploy user and SSH key-based access
* [ ] Install Nginx, PHP 8.2, Composer, Node.js
* [ ] Install MySQL 8.0 and secure installation
* [ ] Setup Redis (optional) for queues
* [ ] Configure Nginx vhost for Laravel app
* [ ] Setup SSL with Let’s Encrypt
* [ ] Configure `.env` for production
* [ ] Run `php artisan migrate --force` on production
* [ ] Setup Supervisor for queue workers
* [ ] Configure cron job for scheduled reports
* [ ] Setup daily MySQL backups
* [ ] Test restore from backup
* [ ] Setup log rotation and monitoring
* [ ] Integrate uptime monitoring (UptimeRobot or similar)
* [ ] Implement CI/CD pipeline via GitHub Actions
* [ ] Run automated test suite in pipeline
* [ ] Setup staging environment
* [ ] Document deployment process

---

## 📘 Documentation & Handover [0/10]

* [ ] Write complete `README.md` with setup instructions
* [ ] Create environment variable documentation
* [ ] Document database schema (ERD)
* [ ] Document API endpoints (OpenAPI/Postman)
* [ ] Create admin user guide
* [ ] Create teknisi and user guide
* [ ] Record demo video for system walkthrough
* [ ] Write maintenance SOP and escalation guide
* [ ] Prepare presentation slides for final handover
* [ ] Archive release package and changelog

---

