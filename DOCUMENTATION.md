# HelpDesk Kemlu - Comprehensive Technical Documentation

This document provides a detailed overview of the HelpDesk Kemlu project, including its architecture, features, and development guidelines, with deep dives into core components.

---

## 1. Project Overview

HelpDesk Kemlu is a modern, web-based ticketing system designed and developed for the Indonesian Ministry of Foreign Affairs (Kementerian Luar Negeri). It aims to streamline IT support processes, improve response times, and provide a centralized platform for managing technical issues across the organization.

- **Application Type:** Web Application - Helpdesk Ticketing System
- **Client:** Kementerian Luar Negeri (Indonesian Ministry of Foreign Affairs)

---

## 2. Technology Stack

The project is built on a modern technology stack, leveraging the power of Laravel for the backend and Vue.js for a reactive frontend experience.

| Layer | Technology | Version |
|--------------|-----------------|---------|
| Backend | Laravel | 12.x |
| Frontend | Vue.js | 3.x |
| Bridge | Inertia.js | Latest |
| Database | MySQL | 8.0+ |
| CSS | TailwindCSS | 4.x |
| Authentication | Laravel Sanctum | Built-in |
| Server | PHP | 8.2+ |
| Broadcasting | Laravel Reverb | Latest |
| Search | Laravel Scout | Latest |

---

## 3. System Architecture

The system is designed with a focus on security, scalability, and maintainability. It features a modular, service-oriented architecture on the backend.

### 3.1. Multi-Role Authentication

A key architectural feature is the separation of user roles into different database tables. This enhances security by isolating different types of users at the database level.

- **`users`:** Regular employees who create tickets.
- **`admin_helpdesks`:** System administrators with full system access.
- **`admin_aplikasis`:** Application managers who oversee the application catalog.
- **`teknisis`:** Technical support staff who resolve tickets.

### 3.2. Authentication Service (`AuthService`)

The `app/Services/AuthService.php` is the heart of the authentication process.

-   **Login Flow**:
    1.  A user logs in with their NIP (Nomor Induk Pegawai) and password.
    2.  The service checks for rate limiting to prevent brute-force attacks (Max **5 attempts** per **15 minutes**).
    3.  It queries the user tables in a specific priority order defined in the `TABLE_PRIORITY` constant: `AdminHelpdesk` -> `AdminAplikasi` -> `Teknisi` -> `User`.
    4.  It verifies the user's status is 'active'.
    5.  It validates the password using `password_verify()`.
    6.  It checks for concurrent session limits (Max **3 active sessions** per user).
    7.  Upon success, it creates a detailed session payload in the `user_sessions` table for tracking and management.

-   **Permissions**: The service also defines a `getUserPermissions` method that returns a list of capabilities for the authenticated user's role, forming the basis for the system's authorization.

### 3.3. Ticket Service (`TicketService`)

Located at `app/Services/TicketService.php`, this service encapsulates all business logic related to tickets.

-   **Ticket Number Generation**: A unique ticket number is generated with the format `TKT-YYYYMMDD-XXXX`, where `XXXX` is an auto-incrementing sequence for the day. This process is locked for updates to prevent race conditions.
-   **File Uploads**: The service validates and stores attachments in `storage/app/public/tickets/{ticket_number}`. It enforces a size limit of 2MB and allows specific MIME types (images, PDFs, documents).
-   **State Transitions**: The `updateTicketStatus` method validates if a status change is allowed before performing the update (e.g., a 'Closed' ticket cannot be reopened directly). Every state change is recorded in the `ticket_history` table.

### 3.4. Notification Service (`NotificationService`)

Found in `app/Services/NotificationService.php`, this service handles all user notifications. While the project includes Laravel Reverb, the primary notification mechanism is database-driven.

-   **Event-Driven Notifications**: The service contains methods that are triggered by specific events in the application lifecycle.
-   **Recipient Logic**:
    -   **New Ticket (`notifyTicketCreated`)**: All active `AdminHelpdesk` users are notified.
    -   **Ticket Assigned (`notifyTicketAssigned`)**: The specific `Teknisi` the ticket is assigned to is notified.
    -   **Status Change (`notifyStatusChanged`)**: The original `User` who created the ticket is notified.
    -   **Comment Added (`notifyCommentAdded`)**: The ticket creator and assigned `Teknisi` are notified (unless they are the ones who commented). `AdminHelpdesk` users are also notified of internal or technical comments.
    -   **Ticket Resolved (`notifyTicketResolved`)**: The original `User` is notified and prompted for feedback.

### 3.5. Frontend Structure

The frontend is built with Vue.js and Inertia.js.
- **Pages**: Role-specific dashboards and pages are located in `resources/js/Pages`. Each role (`AdminHelpdesk`, `Teknisi`, etc.) has its own subdirectory.
- **Components**: Reusable UI elements like buttons, modals, charts, and form inputs are located in `resources/js/Components`. This promotes consistency and rapid development.

---

## 4. Key Features (Detailed)

### 4.1. Core Ticketing
- **Unique Ticket Number:** `TKT-YYYYMMDD-XXXX` format.
- **File Upload System:** Attach up to 5 files (max 2MB each) per ticket or comment.
- **Ticket History & Audit Logs:** Every action, from creation to status changes and comments, is logged in the `ticket_history` table with user, timestamp, and IP address.
- **Commenting System:** Supports public comments (visible to the ticket creator) and internal notes (visible only to Admins and Technicians).

### 4.2. Security
- **Role-based Middleware:** Protects all routes, ensuring users can only access authorized areas.
- **Session Timeout:** Automatic session invalidation after **120 minutes** of inactivity, configured in `AuthService`.
- **Login Rate Limiting:** Prevents brute-force attacks by limiting login attempts.
- **Concurrent Session Control:** Limits users to a maximum of 3 active sessions simultaneously.
- **Secure File Handling:** Validates file types and sizes and stores them outside the web root.

---

## 5. User Roles & Permissions

The system has four roles with a detailed set of permissions managed by `AuthService`.

### 5.1. User (Pegawai)
- `create_tickets`, `view_own_tickets`, `add_ticket_comments`

### 5.2. Admin Helpdesk
- `manage_tickets`, `assign_tickets`, `view_reports`, `manage_users`, `system_settings`

### 5.3. Admin Aplikasi
- `manage_applications`, `assign_teknisi`, `view_reports`, `manage_categories`

### 5.4. Teknisi (Technical Support)
- `view_assigned_tickets`, `update_ticket_status`, `add_ticket_comments`, `view_knowledge_base`

---

## 6. Ticket Lifecycle & Status

The ticket lifecycle is strictly enforced by the `TicketService`.

-   **Flow:** `Open` → `Assigned` → `In Progress` → `Pending` → `Resolved` → `Closed`
-   **Priorities:** Low, Medium, High, Urgent

---

## 7. Database Schema & Models

The database is structured to support the multi-role architecture. Eloquent models in `app/Models` define relationships.

### 7.1. Key Model Relationships
-   **`Ticket`**:
    -   `belongsTo(User::class, 'user_nip', 'nip')`
    -   `belongsTo(Teknisi::class, 'assigned_to', 'nip')`
    -   `hasMany(TicketComment::class)`
    -   `hasMany(TicketHistory::class)`
-   **`TicketComment`**:
    -   `belongsTo(Ticket::class)`
    -   `morphTo('commenter')` (Can be a User, Teknisi, or Admin)
-   **`Notification`**:
    -   `morphTo('notifiable')` (The user receiving the notification)

### 7.2. Core Tables
- `tickets`: The main table for all ticket data.
- `aplikasis`: The application catalog managed by Admin Aplikasi.
- `kategori_masalahs`: Problem categories linked to applications.
- `ticket_comments`: All comments and internal notes for tickets.
- `ticket_history`: The audit trail for every action on a ticket.
- `notifications`: Stores all system-generated notifications.
- `user_sessions`: Tracks active user sessions for concurrent login control.

---

## 8. Development Commands

### 8.1. Setup Instructions

1.  **Clone Repository:** `git clone <url>`
2.  **Install PHP Dependencies:** `composer install`
3.  **Install Node.js Dependencies:** `npm install`
4.  **Create Environment File:** `cp .env.example .env` and configure your database in the `.env` file.
5.  **Generate App Key:** `php artisan key:generate`
6.  **Run Migrations & Seeders:** `php artisan migrate --seed`

### 8.2. Running the Development Environment

-   **All-In-One Command:** This is the recommended way to start all necessary services.
    ```bash
    composer run dev
    ```

-   **Separate Services (in different terminals):**
    -   `php artisan serve` (PHP Server)
    -   `npm run dev` (Vite Frontend Server)
    -   `php artisan reverb:start` (Real-time Broadcasting Server)
    -   `php artisan queue:listen --tries=1` (Queue Worker for background jobs)

### 8.3. Other Useful Commands

-   **Run Tests:** `composer run test` or `php artisan test`
-   **Build for Production:** `npm run build`
-   **Clear Configuration/Cache:** `php artisan config:clear && php artisan cache:clear`

---

## 9. UI/UX Guidelines

- **Color Scheme:** Professional blue/indigo primary colors to align with government branding.
- **Layout:** Sidebar navigation for all dashboards, with a consistent header containing user info and notifications.
- **Components:** Built with reusable Vue components styled with TailwindCSS.
- **Responsiveness:** A mobile-first design approach is used.
- **Accessibility:** The application aims for WCAG 2.1 AA compliance.