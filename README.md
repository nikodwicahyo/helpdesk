# HelpDesk Kemlu - Ticketing System

This is a helpdesk ticketing system built for the Indonesian Ministry of Foreign Affairs (Kementerian Luar Negeri). It is a modern web application designed to streamline IT support requests and management.

## 🎯 Project Overview

- **Application Type:** Web Application - Helpdesk Ticketing System
- **Client:** Kementerian Luar Negeri (Indonesian Ministry of Foreign Affairs)
- **Core Technologies:** Laravel 12, Vue.js 3, Inertia.js

## 🛠️ Technology Stack

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

## 📊 Key Features

- **Multi-Role Authentication:** A secure, multi-table authentication system for different user roles (Employees, Admins, Technicians).
- **Role-Based Dashboards:** Unique dashboards for each user role, providing relevant data and actions.
- **Real-time Notifications:** Instant notifications for ticket creations, assignments, and status changes.
- **Comprehensive Ticketing:** Full ticket lifecycle management, including creation, assignment, status updates, comments, and resolution.
- **File Uploads:** Secure file attachment system for tickets.
- **Audit Trails:** Complete history tracking for every ticket.
- **Analytics & Reporting:** Powerful reporting tools for system administrators.

## 🎭 User Roles

The system supports four distinct user roles:

1.  **User (Pegawai):** Regular employees who can create, track, and manage their own support tickets.
2.  **Admin Helpdesk:** System administrators with full control over all tickets, users, and system settings.
3.  **Admin Aplikasi:** Application managers responsible for the application catalog and problem categories.
4.  **Teknisi (Technician):** Technical support staff who handle and resolve assigned tickets.

## 🚀 Getting Started

Follow these instructions to get the project up and running on your local machine for development and testing purposes.

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL 8.0+

### Installation

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/nikodwicahyo/helpdesk
    cd helpdesk
    ```

2.  **Install PHP dependencies:**
    ```bash
    composer install
    ```

3.  **Install Node.js dependencies:**
    ```bash
    npm install
    ```

4.  **Set up your environment:**
    - Copy the example environment file.
      ```bash
      cp .env.example .env
      ```
    - Open the `.env` file and configure your database connection (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

5.  **Generate application key:**
    ```bash
    php artisan key:generate
    ```

6.  **Run database migrations and seeders:**
    This will create the necessary tables and populate them with initial data.
    ```bash
    php artisan migrate:fresh --seed
    ```

## 🔧 Development

To start the development server with all necessary services (web server, Vite, Reverb, and queue listener), you can use the custom `dev` script.

### Running All Services

-   **Start the development environment:**
    ```bash
    composer run dev
    ```

### Running Services Separately

Alternatively, you can start each service in a separate terminal:

-   **Laravel Development Server:**
    ```bash
    php artisan serve
    ```
-   **Vite Frontend Server:**
    ```bash
    npm run dev
    ```
-   **Queue Worker:**
    ```bash
    php artisan queue:listen --tries=1
    ```

### Running Tests

To run the application's test suite:
```bash
composer run test
# or
php artisan test
```

### Building for Production

To build the frontend assets for production:
```bash
npm run build
```
