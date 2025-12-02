# HelpDesk Kemlu - Project Overview for Qwen Code

This is a **Laravel 12 + Vue.js 3 + Inertia.js** helpdesk ticketing system for the Indonesian Ministry of Foreign Affairs (Kementerian Luar Negeri).

## 🎯 Project Context

**Type:** Web Application - Helpdesk Ticketing System  
**Client:** Kementerian Luar Negeri (Indonesian Ministry of Foreign Affairs)  
**Duration:** 18 weeks (4.5 months)  
**Budget:** Rp 312.400.000

## 🏗️ Architecture Overview

### Multi-Role Authentication System
- **4 Separate Tables** for different roles (security by design):
  - `users` - Regular employees (ticket creators)
  - `admin_helpdesks` - System administrators
  - `admin_aplikasis` - Application managers
  - `teknisis` - Technical support staff

### Login Flow
1. Users login with **NIP** (Nomor Induk Pegawai) + password
2. System searches across 4 tables in priority order: admin_helpdesks → admin_aplikasis → teknisis → users
3. Role detection and redirect to appropriate dashboard
4. Session-based authentication

## 🛠️ Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | 12.x |
| Frontend | Vue.js | 3.x |
| Bridge | Inertia.js | Latest |
| Database | MySQL | 8.0+ |
| CSS | TailwindCSS | 4.x |
| Auth | Laravel Sanctum | Built-in |
| Server | PHP | 8.2+ |
| Broadcasting | Laravel Reverb | Latest |
| Search | Laravel Scout | Latest |

## 📁 Key Project Structure

```
helpdesk-kemlu/
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth/LoginController.php (Multi-role login)
│   │   ├── User/ (User dashboard & tickets)
│   │   ├── AdminHelpdesk/ (Full system control)
│   │   ├── AdminAplikasi/ (Application catalog management)
│   │   └── Teknisi/ (Ticket handling)
│   ├── Models/ (All 11 models with relationships)
│   ├── Services/ (AuthService, TicketService, etc.)
│   └── Observers/ (TicketObserver for auto-notifications)
├── resources/js/
│   ├── Pages/ (Vue pages per role)
│   ├── Components/ (Reusable UI components)
│   └── app.js (Inertia setup)
└── database/
    ├── migrations/ (All 11 tables)
    └── seeders/ (Initial data)
```

## 🎭 User Roles & Capabilities

### User (Pegawai)
- Create tickets, track personal tickets, add comments, close tickets
- Dashboard: Personal ticket statistics, quick actions, notifications

### Admin Helpdesk
- **GOD MODE**: View ALL tickets, manage ALL users, assign tickets, generate reports
- Dashboard: System overview, queue management, performance metrics, analytics

### Admin Aplikasi
- Manage application catalog, define problem categories per app
- Dashboard: Application list, category management, app-specific statistics

### Teknisi (Technical Support)
- Handle assigned tickets, update status, add technical notes, mark resolved
- Dashboard: Personal task board, ticket list, knowledge base access

## 🎫 Ticket Lifecycle

```
User Creates → Open → Admin Assigns → Assigned → Teknisi Works → In Progress
→ Resolved → User Rates/Closes → Closed
```

**Priority Levels:** Low, Medium, High, Urgent  
**Status Flow:** open → assigned → in_progress → pending → resolved → closed

## 🔔 Notification System

**Real-time notifications** triggered by:
- New ticket creation (to Admin Helpdesk)
- Ticket assignment (to Teknisi)
- Status changes (to User)
- Comments added (to relevant parties)
- Ticket resolution (to User for rating)

## 📊 Key Features

### Core Functionality
- Multi-table authentication with NIP
- Role-based dashboards with different UI/UX
- Real-time notifications with badge counts
- File upload system (max 5 files @ 2MB each)
- Ticket history tracking and audit logs
- Comment system with internal notes option

### Analytics & Reports
- Daily/Weekly/Monthly reports (Admin Helpdesk)
- Performance metrics per teknisi
- Application-specific statistics
- Export to PDF/Excel functionality

### Security Features
- Role-based middleware protection
- Session timeout (120 minutes)
- CSRF protection on all forms
- Rate limiting on login attempts
- File upload validation and secure storage

## 🗄️ Database Schema (Key Tables)

### Role Tables (Separated Auth)
- `users` - Regular employees
- `admin_helpdesks` - System admins
- `admin_aplikasis` - Application managers
- `teknisis` - Technical staff

### Core Tables
- `tickets` - Main ticket data
- `aplikasis` - Application catalog
- `kategori_masalahs` - Problem categories per app
- `ticket_comments` - Comments and communication
- `ticket_history` - Audit trail
- `notifications` - System notifications
- `reports` - Generated reports data

## 🚀 Important Implementation Details

### AuthService (Multi-table Authentication)
- Searches 4 tables in order: admin_helpdesks → admin_aplikasis → teknisis → users
- Handles NIP validation, password checking, account status
- Creates session with role information
- Returns appropriate redirect URL per role

### Route Structure
- `/` - Landing page (public)
- `/login` - Multi-role login portal
- `/user/dashboard` - User dashboard
- `/admin/dashboard` - Admin Helpdesk dashboard
- `/admin-aplikasi/dashboard` - Admin Aplikasi dashboard
- `/teknisi/dashboard` - Teknisi dashboard

### Key Services
- `AuthService` - Multi-table authentication logic
- `TicketService` - Business logic for ticket operations
- `NotificationService` - Notification creation and management
- `ReportService` - Report generation and data aggregation

## 🔧 Development Commands

### Setup
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Create environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed the database
php artisan db:seed
```

### Development
```bash
# Start development server with all services
composer run dev

# Or start services separately
php artisan serve
php artisan reverb:start
php artisan queue:listen --tries=1
npm run dev
```

### Environment Management
```bash
# For local development
php artisan config:clear
php artisan cache:clear

# Run tests
composer run test
# or
php artisan test

# Build assets for production
npm run build
```

### Stop Development Server
```bash
# On Windows
composer run stop-all

# On Linux/Mac
pkill -f "artisan serve"
pkill -f "artisan reverb"
pkill -f "npm run dev"
```

## 🎨 UI/UX Guidelines

- **Color Scheme:** Blue/Indigo primary colors (professional government look)
- **Layout:** Sidebar navigation for dashboards, header with notifications
- **Components:** Reusable Vue components with TailwindCSS
- **Responsive:** Mobile-first design approach
- **Accessibility:** WCAG 2.1 AA compliance

## 📋 Current Development Status

This project is in **active development phase**. The comprehensive documentation in PLANNING.md and PRD.md was used to create the current implementation with multiple role-specific dashboards, authentication systems, and ticket management workflows.

## 🚨 Important Notes for Development

1. **Multi-table auth is critical** - Never merge role tables for security
2. **Role-based access control** - Every route must have proper middleware
3. **Real-time notifications** - Key feature for user experience
4. **Audit trails** - Every action must be logged in ticket_history
5. **File security** - Validate and secure all file uploads
6. **Performance** - Use eager loading, caching, and proper indexing
7. **Testing** - Comprehensive test coverage required

## 📞 Contact & Support

For questions about this project, refer to:
- `PLANNING.md` - Detailed implementation timeline and technical setup
- `PRD.md` - Complete product requirements and feature specifications
- Project documentation in `/docs` folder (when available)

---

**Last Updated:** Based on project documentation analysis  
**Qwen Context:** This document provides essential project context for AI assistance