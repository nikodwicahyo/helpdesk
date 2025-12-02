# PLANNING.md
# HelpDesk Kemlu - Project Planning & Implementation Guide

---

## 📋 Table of Contents

1. [Project Overview](#1-project-overview)
2. [Team Structure](#2-team-structure)
3. [Development Phases](#3-development-phases)
4. [Sprint Planning](#4-sprint-planning)
5. [Technical Setup](#5-technical-setup)
6. [Database Implementation](#6-database-implementation)
7. [Backend Development](#7-backend-development)
8. [Frontend Development](#8-frontend-development)
9. [Testing Strategy](#9-testing-strategy)
10. [Deployment Plan](#10-deployment-plan)
11. [Daily Checklist](#11-daily-checklist)
12. [Risk Management](#12-risk-management)

---

## 1. Project Overview

### 1.1 Project Information
- **Project Name:** HelpDesk Kemlu
- **Project Type:** Web Application - Helpdesk Ticketing System
- **Duration:** 18 weeks (4.5 months)
- **Start Date:** [TBD]
- **Target Launch:** [TBD]
- **Budget:** Rp 312.400.000

### 1.2 Technology Stack
- **Backend:** Laravel 12
- **Frontend:** Vue.js 3 + Inertia.js
- **Database:** MySQL 8.0+
- **Styling:** TailwindCSS 3.x
- **Version Control:** Git (GitHub/GitLab)

### 1.3 Key Features
- Multi-role authentication (4 roles dengan tabel terpisah)
- Dashboard interaktif per role
- Ticket management system
- Real-time notifications
- Reporting & analytics
- File upload system
- Email notifications

---

## 2. Team Structure

### 2.1 Core Team

| Role | Responsibility | Count |
|------|---------------|-------|
| **Project Manager** | Overall coordination, timeline management | 1 |
| **Backend Developer** | Laravel development, API, database | 2 |
| **Frontend Developer** | Vue.js, Inertia.js, UI implementation | 2 |
| **UI/UX Designer** | Interface design, user experience | 1 |
| **QA Engineer** | Testing, bug tracking | 1 |
| **DevOps Engineer** | Server setup, deployment | 1 |

### 2.2 Communication Plan
- **Daily Standup:** 09:00 AM (15 minutes)
- **Sprint Planning:** Every 2 weeks (Monday)
- **Sprint Review:** Every 2 weeks (Friday)
- **Retrospective:** Every 2 weeks (Friday)
- **Tools:** Slack, Jira/Trello, GitHub

---

## 3. Development Phases

### Phase 1: Planning & Design (Week 1-3)

#### Week 1: Requirements & Analysis
**Days 1-2: Kickoff & Requirements Gathering**
- [ ] Kickoff meeting dengan stakeholders
- [ ] Review dan finalisasi PRD
- [ ] Identifikasi pain points existing system
- [ ] Define success metrics (KPIs)
- [ ] Create user personas per role

**Days 3-5: Technical Planning**
- [ ] Setup project repository (GitHub/GitLab)
- [ ] Create project structure
- [ ] Define coding standards
- [ ] Setup development environment
- [ ] Create technical architecture document

#### Week 2: Database & Architecture Design
**Days 1-3: Database Design**
- [ ] Create Entity Relationship Diagram (ERD)
- [ ] Define all tables dan relationships
- [ ] Design indexes untuk optimization
- [ ] Plan data migration strategy (jika ada)
- [ ] Review database design dengan team

**Days 4-5: System Architecture**
- [ ] Create system architecture diagram
- [ ] Define API structure (REST/GraphQL)
- [ ] Plan file storage strategy
- [ ] Design notification system architecture
- [ ] Plan caching strategy

#### Week 3: UI/UX Design
**Days 1-3: Wireframing**
- [ ] Create low-fidelity wireframes
- [ ] Landing page wireframe
- [ ] Login portal wireframe
- [ ] Dashboard wireframes (4 roles)
- [ ] Ticket management wireframes

**Days 4-5: High-Fidelity Design**
- [ ] Design system creation (colors, typography, components)
- [ ] High-fidelity mockups untuk semua pages
- [ ] Interactive prototype (Figma/Adobe XD)
- [ ] Design review dengan stakeholders
- [ ] Export assets untuk development

---

### Phase 2: Development (Week 4-13)

#### Week 4-5: Backend Foundation

**Week 4: Core Setup**
- [ ] Install Laravel 12
- [ ] Configure MySQL database
- [ ] Setup Laravel Sanctum untuk authentication
- [ ] Create migrations untuk semua tabel
- [ ] Setup seeders untuk test data
- [ ] Configure `.env` file
- [ ] Setup Laravel Debugbar

**Week 5: Authentication System**
- [ ] Implement multi-table authentication logic
- [ ] Create AuthService untuk NIP-based login
- [ ] Build LoginController
- [ ] Implement role detection logic
- [ ] Create RoleMiddleware
- [ ] Setup session management
- [ ] Test authentication flow

#### Week 6-7: Models & Relationships

**Week 6: Core Models**
- [ ] Create Model: User
- [ ] Create Model: AdminHelpdesk
- [ ] Create Model: AdminAplikasi
- [ ] Create Model: Teknisi
- [ ] Create Model: Ticket
- [ ] Create Model: Aplikasi
- [ ] Create Model: KategoriMasalah
- [ ] Define all Eloquent relationships

**Week 7: Supporting Models**
- [ ] Create Model: TicketComment
- [ ] Create Model: TicketHistory
- [ ] Create Model: Notification
- [ ] Create Model: Report
- [ ] Implement Model Observers (TicketObserver)
- [ ] Create accessors & mutators
- [ ] Test all relationships

#### Week 8-9: Controllers & Business Logic

**Week 8: User & Ticket Controllers**
- [ ] Create UserDashboardController
- [ ] Create TicketController (User)
  - [ ] index() - list tickets
  - [ ] create() - form buat tiket
  - [ ] store() - save tiket
  - [ ] show() - detail tiket
  - [ ] addComment() - tambah komentar
  - [ ] close() - tutup tiket
- [ ] Create TicketService untuk business logic
- [ ] Implement file upload handler
- [ ] Implement ticket number generator

**Week 9: Admin & Teknisi Controllers**
- [ ] Create AdminHelpdeskDashboardController
- [ ] Create TicketManagementController
  - [ ] index() - all tickets
  - [ ] assign() - assign teknisi
  - [ ] updatePriority()
  - [ ] bulkAssign()
- [ ] Create UserManagementController (CRUD all roles)
- [ ] Create TeknisiDashboardController
- [ ] Create TicketHandlingController
  - [ ] myTickets()
  - [ ] updateStatus()
  - [ ] resolve()

#### Week 10: Notification System

**Day 1-2: Notification Service**
- [ ] Create NotificationService
- [ ] Implement notification triggers
  - [ ] notifyTicketCreated()
  - [ ] notifyTicketAssigned()
  - [ ] notifyStatusChanged()
  - [ ] notifyCommentAdded()
  - [ ] notifyTicketResolved()
- [ ] Create NotificationController
  - [ ] getUnread()
  - [ ] markAsRead()
  - [ ] markAllAsRead()

**Day 3-5: Email Notifications**
- [ ] Setup mail configuration (SMTP)
- [ ] Create mail templates
  - [ ] TicketCreatedMail
  - [ ] TicketAssignedMail
  - [ ] TicketResolvedMail
- [ ] Queue setup untuk email
- [ ] Test email sending

#### Week 11: Reporting System

**Day 1-3: Report Generation**
- [ ] Create ReportController
- [ ] Implement report types
  - [ ] Daily report
  - [ ] Weekly report
  - [ ] Monthly report
  - [ ] Custom report
- [ ] Calculate metrics
  - [ ] Total tickets
  - [ ] Resolved tickets
  - [ ] Average resolution time
  - [ ] Teknisi performance

**Day 4-5: Export Functionality**
- [ ] Install Laravel Excel / DomPDF
- [ ] Implement Excel export
- [ ] Implement PDF export
- [ ] Create report templates
- [ ] Test report generation

#### Week 12-13: Frontend Development

**Week 12: Core Frontend Setup**
- [ ] Install Vue 3 & Inertia.js
- [ ] Setup TailwindCSS
- [ ] Configure Vite
- [ ] Create layout components
  - [ ] Navbar.vue
  - [ ] Sidebar.vue
  - [ ] Footer.vue
  - [ ] NotificationBell.vue
- [ ] Create common components
  - [ ] StatCard.vue
  - [ ] DataTable.vue
  - [ ] Modal.vue
  - [ ] FileUpload.vue
  - [ ] Loading.vue

**Week 13: Pages Development**

**Day 1: Public Pages**
- [ ] Landing.vue - Landing page
- [ ] Login.vue - Login portal

**Day 2: User Dashboard**
- [ ] User/Dashboard.vue
- [ ] User/TicketCreate.vue
- [ ] User/TicketDetail.vue
- [ ] User/TicketList.vue

**Day 3: Admin Helpdesk Dashboard**
- [ ] AdminHelpdesk/Dashboard.vue
- [ ] AdminHelpdesk/TicketManagement.vue
- [ ] AdminHelpdesk/UserManagement.vue
- [ ] AdminHelpdesk/Reports.vue

**Day 4: Admin Aplikasi & Teknisi**
- [ ] AdminAplikasi/Dashboard.vue
- [ ] AdminAplikasi/AplikasiManagement.vue
- [ ] Teknisi/Dashboard.vue
- [ ] Teknisi/TicketHandling.vue

**Day 5: Integration & Polish**
- [ ] Integrate all components
- [ ] Add transitions & animations
- [ ] Responsive design testing
- [ ] Cross-browser testing

---

### Phase 3: Testing (Week 14-16)

#### Week 14: Unit & Integration Testing

**Day 1-2: Backend Unit Tests**
- [ ] Test AuthService
- [ ] Test TicketService
- [ ] Test NotificationService
- [ ] Test ReportService
- [ ] Test Model relationships
- [ ] Test Observers

**Day 3-4: Feature Tests**
- [ ] Test authentication flow
- [ ] Test ticket creation
- [ ] Test ticket assignment
- [ ] Test status updates
- [ ] Test notification triggers
- [ ] Test file uploads

**Day 5: Integration Tests**
- [ ] Test complete ticket lifecycle
- [ ] Test multi-role interactions
- [ ] Test notification system end-to-end
- [ ] Test report generation

#### Week 15: User Acceptance Testing (UAT)

**Day 1: UAT Preparation**
- [ ] Create test scenarios per role
- [ ] Prepare test data
- [ ] Setup staging environment
- [ ] Create UAT documentation

**Day 2-3: UAT Execution**
- [ ] User role testing
- [ ] Admin Helpdesk role testing
- [ ] Admin Aplikasi role testing
- [ ] Teknisi role testing
- [ ] Document bugs & issues

**Day 4-5: Bug Fixing**
- [ ] Prioritize bugs (Critical, High, Medium, Low)
- [ ] Fix critical bugs
- [ ] Fix high priority bugs
- [ ] Retest fixed issues

#### Week 16: Performance & Security Testing

**Day 1-2: Performance Testing**
- [ ] Load testing dengan Apache JMeter
- [ ] Database query optimization
- [ ] Frontend performance optimization
- [ ] Image optimization
- [ ] Caching implementation

**Day 3-4: Security Testing**
- [ ] SQL Injection testing
- [ ] XSS vulnerability testing
- [ ] CSRF protection testing
- [ ] Authentication & authorization testing
- [ ] File upload security testing
- [ ] Session management testing

**Day 5: Final Review**
- [ ] Code review complete
- [ ] Security audit complete
- [ ] Performance benchmarks met
- [ ] All critical bugs resolved
- [ ] UAT sign-off

---

### Phase 4: Deployment (Week 17-18)

#### Week 17: Pre-Production Setup

**Day 1-2: Server Setup**
- [ ] Provision Ubuntu server (AWS/Azure/VPS)
- [ ] Install PHP 8.2+
- [ ] Install MySQL 8.0+
- [ ] Install Nginx
- [ ] Install Node.js 18+
- [ ] Configure firewall (UFW)
- [ ] Setup SSH keys

**Day 3: Application Deployment**
- [ ] Clone repository ke server
- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run `npm install && npm run build`
- [ ] Configure `.env` production
- [ ] Generate application key
- [ ] Run migrations
- [ ] Run seeders (initial data)
- [ ] Setup storage link
- [ ] Configure file permissions

**Day 4: Web Server Configuration**
- [ ] Configure Nginx virtual host
- [ ] Setup SSL certificate (Let's Encrypt)
- [ ] Force HTTPS redirect
- [ ] Configure PHP-FPM
- [ ] Setup log rotation
- [ ] Test server configuration

**Day 5: Final Configuration**
- [ ] Setup cron jobs
  - [ ] Queue worker
  - [ ] Scheduled tasks
  - [ ] Database backup
- [ ] Configure email (SMTP)
- [ ] Setup monitoring (Uptime Robot)
- [ ] Setup backup automation
- [ ] Configure error logging

#### Week 18: Launch & Handover

**Day 1: Pre-Launch Checklist**
- [ ] Final smoke testing
- [ ] Database backup before launch
- [ ] Check all environment variables
- [ ] Test email notifications
- [ ] Test file uploads
- [ ] Test all role dashboards
- [ ] Load testing production

**Day 2: Soft Launch**
- [ ] Deploy to production
- [ ] Internal testing dengan tim Kemlu
- [ ] Monitor error logs
- [ ] Monitor performance metrics
- [ ] Fix any critical issues

**Day 3: Training**
- [ ] Admin Helpdesk training
- [ ] Admin Aplikasi training
- [ ] Teknisi training
- [ ] User training (demo/video)
- [ ] Distribute user manuals

**Day 4: Official Launch**
- [ ] Go-live announcement
- [ ] Monitor system closely
- [ ] 24/7 support standby
- [ ] Collect initial feedback

**Day 5: Handover & Documentation**
- [ ] Technical documentation handover
- [ ] Admin credentials handover
- [ ] Backup strategy explanation
- [ ] Support contact handover
- [ ] Project closure meeting

---

## 4. Sprint Planning

### Sprint Structure
- **Sprint Duration:** 2 weeks
- **Total Sprints:** 9 sprints
- **Sprint Ceremonies:**
  - Sprint Planning (Monday, Week 1)
  - Daily Standup (Every day, 15 min)
  - Sprint Review (Friday, Week 2)
  - Sprint Retrospective (Friday, Week 2)

### Sprint Breakdown

#### Sprint 1 (Week 1-2)
**Goal:** Complete planning, design, and setup
- Requirements finalization
- Database design
- UI/UX wireframes & mockups
- Project setup & repository
- Development environment setup

#### Sprint 2 (Week 3-4)
**Goal:** Backend foundation
- Laravel installation
- Database migrations
- Authentication system
- Role middleware
- Basic models

#### Sprint 3 (Week 5-6)
**Goal:** Core models & relationships
- Complete all models
- Define relationships
- Model observers
- Seeders for test data

#### Sprint 4 (Week 7-8)
**Goal:** User & ticket management
- User controllers
- Ticket CRUD
- File upload
- Business logic implementation

#### Sprint 5 (Week 9-10)
**Goal:** Admin features & notifications
- Admin controllers
- Ticket assignment logic
- Notification system
- Email notifications

#### Sprint 6 (Week 11-12)
**Goal:** Reporting & frontend setup
- Report generation
- Export functionality
- Vue.js setup
- Core components

#### Sprint 7 (Week 13-14)
**Goal:** Frontend pages & unit testing
- All dashboard pages
- Component integration
- Backend unit tests
- Feature tests

#### Sprint 8 (Week 15-16)
**Goal:** Testing & optimization
- UAT execution
- Bug fixing
- Performance optimization
- Security testing

#### Sprint 9 (Week 17-18)
**Goal:** Deployment & launch
- Server setup
- Production deployment
- Training
- Go-live

---

## 5. Technical Setup

### 5.1 Development Environment Setup

#### Prerequisites
```bash
# Check versions
php --version   # Should be 8.2+
composer --version
node --version  # Should be 18+
npm --version
mysql --version # Should be 8.0+
```

#### Step 1: Install Laravel
```bash
# Create new Laravel project
composer create-project laravel/laravel helpdesk-kemlu "12.*"

cd helpdesk-kemlu

# Install Inertia.js server-side
composer require inertiajs/inertia-laravel
```

#### Step 2: Install Frontend Dependencies
```bash
# Install Vue 3 & Inertia client
npm install @inertiajs/vue3 vue@3

# Install Vite plugin
npm install @vitejs/plugin-vue

# Install TailwindCSS
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

#### Step 3: Configure Inertia
**app/Http/Middleware/HandleInertiaRequests.php:**
```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => session('user_name'),
                'role' => session('user_role'),
                'nip' => session('nip'),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }
}
```

#### Step 4: Configure Vite (vite.config.js)
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
```

#### Step 5: Configure TailwindCSS
**tailwind.config.js:**
```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

**resources/css/app.css:**
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

#### Step 6: Setup Vue in Laravel
**resources/js/app.js:**
```javascript
import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    title: (title) => `${title} - HelpDesk Kemlu`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4F46E5',
    },
});
```

#### Step 7: Create App Blade Template
**resources/views/app.blade.php:**
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'HelpDesk Kemlu') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
```

#### Step 8: Configure Database
**.env:**
```env
APP_NAME="HelpDesk Kemlu"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk_kemlu
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@helpdesk.kemlu.go.id
MAIL_FROM_NAME="${APP_NAME}"
```

#### Step 9: Run Development Server
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server
npm run dev
```

---

## 6. Database Implementation

### 6.1 Create All Migrations

```bash
# Role tables
php artisan make:migration create_users_table
php artisan make:migration create_admin_helpdesks_table
php artisan make:migration create_admin_aplikasis_table
php artisan make:migration create_teknisis_table

# Core tables
php artisan make:migration create_aplikasis_table
php artisan make:migration create_kategori_masalahs_table
php artisan make:migration create_tickets_table
php artisan make:migration create_ticket_comments_table
php artisan make:migration create_ticket_history_table
php artisan make:migration create_notifications_table
php artisan make:migration create_reports_table
```

### 6.2 Example Migration (Users)

**database/migrations/xxxx_create_users_table.php:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 20)->unique();
            $table->string('nama_lengkap', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('jabatan', 100)->nullable();
            $table->string('unit_kerja', 100)->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->string('foto_profil')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            
            $table->index('nip');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### 6.3 Create Seeders

```bash
php artisan make:seeder AdminHelpdeskSeeder
php artisan make:seeder UserSeeder
php artisan make:seeder AplikasiSeeder
```

**database/seeders/AdminHelpdeskSeeder.php:**
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminHelpdesk;

class AdminHelpdeskSeeder extends Seeder
{
    public function run(): void
    {
        AdminHelpdesk::create([
            'nip' => '199001012023011001',
            'nama_lengkap' => 'Admin Super',
            'email' => 'admin@kemlu.go.id',
            'password' => Hash::make('password123'),
            'level_admin' => 'super',
            'no_telepon' => '081234567890',
            'is_active' => true,
        ]);
    }
}
```

### 6.4 Run Migrations & Seeders

```bash
# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed

# Or run specific seeder
php artisan db:seed --class=AdminHelpdeskSeeder
```

---

## 7. Backend Development

### 7.1 Create Models

```bash
php artisan make:model User
php artisan make:model AdminHelpdesk
php artisan make:model AdminAplikasi
php artisan make:model Teknisi
php artisan make:model Ticket
php artisan make:model Aplikasi
php artisan make:model KategoriMasalah
php artisan make:model TicketComment
php artisan make:model TicketHistory
php artisan make:model Notification
php artisan make:model Report
```

### 7.2 Example Model (Ticket)

**app/Models/Ticket.php:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'aplikasi_id',
        'kategori_masalah_id',
        'judul',
        'deskripsi',
        'prioritas',
        'status',
        'assigned_teknisi_id',
        'assigned_by_admin_id',
        'assigned_at',
        'lokasi',
        'lampiran',
        'resolved_at',
        'closed_at',
        'rating',
        'feedback',
    ];

    protected $casts = [
        'lampiran' => 'array',
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aplikasi()
    {
        return $this->belongsTo(Aplikasi::class);
    }

    public function kategoriMasalah()
    {
        return $this->belongsTo(KategoriMasalah::class);
    }

    public function teknisi()
    {
        return $this->belongsTo(Teknisi::class, 'assigned_teknisi_id');
    }

    public function assignedByAdmin()
    {
        return $this->belongsTo(AdminHelpdesk::class, 'assigned_by_admin_id');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function history()
    {
        return $this->hasMany(TicketHistory::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeUrgent($query)
    {
        return $query->where('prioritas', 'urgent');
    }

    // Accessors
    public function getStatusBadgeColorAttribute()
    {
        return match($this->status) {
            'open' => 'yellow',
            'assigned' => 'blue',
            'in_progress' => 'indigo',
            'pending' => 'orange',
            'resolved' => 'green',
            'closed' => 'gray',
            'cancelled' => 'red',
            default => 'gray',
        };
    }
}
```

### 7.3 Create Controllers

```bash
# Auth
php artisan make:controller Auth/LoginController

# User
php artisan make:controller User/DashboardController
php artisan make:controller User/TicketController

# Admin Helpdesk
php artisan make:controller AdminHelpdesk/DashboardController
php artisan make:controller AdminHelpdesk/TicketManagementController
php artisan make:controller AdminHelpdesk/UserManagementController
php artisan make:controller AdminHelpdesk/ReportController

# Admin Aplikasi
php artisan make:controller AdminAplikasi/DashboardController
php artisan make:controller AdminAplikasi/AplikasiController

# Teknisi
php artisan make:controller Teknisi/DashboardController
php artisan make:controller Teknisi/TicketHandlingController
```

### 7.4 Create Services

```bash
mkdir app/Services
touch app/Services/AuthService.php
touch app/Services/TicketService.php
touch app/Services/NotificationService.php
touch app/Services/ReportService.php
```

---

## 8. Frontend Development

### 8.1 Directory Structure

```bash
mkdir -p resources/js/Pages/User
mkdir -p resources/js/Pages/AdminHelpdesk
mkdir -p resources/js/Pages/AdminAplikasi
mkdir -p resources/js/Pages/Teknisi
mkdir -p resources/js/Components/Layout
mkdir -p resources/js/Components/Ticket
mkdir -p resources/js/Components/Common
mkdir -p resources/js/Components/Charts
```

### 8.2 Create Common Components

```bash
touch resources/js/Components/Layout/Navbar.vue
touch resources/js/Components/Layout/Sidebar.vue
touch resources/js/Components/Layout/NotificationBell.vue
touch resources/js/Components/Common/StatCard.vue
touch resources/js/Components/Common/DataTable.vue
touch resources/js/Components/Common/Modal.vue
touch resources/js/Components/Ticket/TicketCard.vue
touch resources/js/Components/Ticket/TicketTable.vue
```

### 8.3 Create Pages

```bash
# Public
touch resources/js/Pages/Landing.vue
touch resources/js/Pages/Login.vue

# User
touch resources/js/Pages/User/Dashboard.vue
touch resources/js/Pages/User/TicketCreate.vue
touch resources/js/Pages/User/TicketDetail.vue

# Admin Helpdesk
touch resources/js/Pages/AdminHelpdesk/Dashboard.vue
touch resources/js/Pages/AdminHelpdesk/TicketManagement.vue
touch resources/js/Pages/AdminHelpdesk/UserManagement.vue
touch resources/js/Pages/AdminHelpdesk/Reports.vue

# Admin Aplikasi
touch resources/js/Pages/AdminAplikasi/Dashboard.vue
touch resources/js/Pages/AdminAplikasi/AplikasiManagement.vue

# Teknisi
touch resources/js/Pages/Teknisi/Dashboard.vue
touch resources/js/Pages/Teknisi/TicketHandling.vue
```

---

## 9. Testing Strategy

### 9.1 Setup Testing

```bash
# Install testing dependencies
composer require --dev phpunit/phpunit
composer require --dev laravel/dusk

# Create test database
php artisan config:clear