# Product Requirements Document (PRD)
## HelpDesk Kemlu - Aplikasi Web Helpdesk Ticketing

**Primary Language**: Indonesian (Bahasa Indonesia)  
**Secondary Language**: English (for code and technical documentation)

---

## 1. Executive Summary

**HelpDesk Kemlu** adalah aplikasi web helpdesk ticketing berbasis Laravel yang dirancang khusus untuk mengelola permintaan layanan TI dan penyelesaian masalah teknis di lingkungan Kementerian Luar Negeri. Aplikasi ini menggunakan arsitektur **MVC (Model-View-Controller)** dengan teknologi modern **Laravel 12 + Inertia.js + Vue.js** untuk menghadirkan antarmuka yang interaktif dan responsif.

Sistem ini memisahkan data per role secara fisik di database untuk meningkatkan keamanan dan integritas data, menggunakan **NIP (Nomor Induk Pegawai)** sebagai kredensial login, serta menyediakan dashboard khusus untuk setiap peran dengan notifikasi real-time.

---

## 2. Tujuan Produk

### 2.1 Objektif Bisnis
- Meningkatkan efisiensi penanganan permintaan layanan TI di Kemlu
- Menyediakan tracking dan monitoring tiket secara real-time
- Memfasilitasi komunikasi antara user, admin, dan teknisi
- Menghasilkan laporan performa dan analitik untuk pengambilan keputusan
- Meningkatkan akuntabilitas dan transparansi penanganan masalah teknis

### 2.2 Target Pengguna
- **Pegawai Kemlu** (User) - Pembuat tiket
- **Admin Helpdesk** - Pengelola sistem dan koordinator
- **Admin Aplikasi** - Pengelola katalog aplikasi dan layanan
- **Teknisi** - Pelaksana penyelesaian masalah teknis

---

## 3. Arsitektur Sistem

### 3.1 Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| **Backend Framework** | Laravel | 12.x |
| **Frontend Framework** | Vue.js | 3.x |
| **Bridge Layer** | Inertia.js | Latest |
| **Database** | MySQL | 8.0+ |
| **CSS Framework** | TailwindCSS | 3.x |
| **Authentication** | Laravel Sanctum | Built-in |
| **Server** | PHP | 8.2+ |

### 3.2 Arsitektur Aplikasi

```
┌─────────────────────────────────────────┐
│         Landing Page (Public)           │
│  - Informasi Sistem                     │
│  - Portal Login (Semua Role)            │
|  - Portal Register (hanya role user)    │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│     Authentication & Role Detection      │
│  - Validasi NIP & Password               │
│  - Role Identification                   │
└──────────────┬──────────────────────────┘
               │
     ┌─────────┴─────────┬──────────┬──────────┐
     ▼                   ▼          ▼          ▼
┌─────────┐      ┌──────────┐  ┌──────────┐  ┌──────────┐
│  User   │      │  Admin   │  │  Admin   │  │ Teknisi  │
│Dashboard│      │Helpdesk  │  │ Aplikasi │  │Dashboard │
└─────────┘      │Dashboard │  │Dashboard │  └──────────┘
                 └──────────┘  └──────────┘
```

---

## 4. Struktur Role dan Akses

### 4.1 Role Definition

#### **Role 1: User (Pegawai)**
**Tabel Database:** `users`

**Hak Akses:**
- Membuat tiket baru
- Melihat daftar tiket pribadi
- Memperbarui detail tiket yang dibuat
- Menambahkan komentar/follow-up
- Menutup tiket yang telah selesai
- Menerima notifikasi update tiket

**Dashboard Features:**
- Summary tiket pribadi (Open, In Progress, Resolved, Closed)
- Daftar tiket aktif
- Form pembuatan tiket cepat
- History tiket
- Panel notifikasi

---

#### **Role 2: Admin Helpdesk**
**Tabel Database:** `admin_helpdesks`

**Hak Akses:**
- Melihat SEMUA tiket di sistem
- Menugaskan tiket ke teknisi
- Mengubah prioritas dan status tiket
- Mengelola semua role (CRUD users, admins, teknisi)
- Memonitor performa teknisi
- Generate dan export laporan ke pdf dan excel
- Mengelola kategori tiket
- Konfigurasi sistem

**Dashboard Features:**
- Overview statistik seluruh sistem
- Queue management tiket
- Assignment tools
- Performance metrics teknisi
- Report generator
- User management interface
- Activity logs
- Panel notifikasi untuk tiket mendesak

---

#### **Role 3: Admin Aplikasi**
**Tabel Database:** `admin_aplikasis`

**Hak Akses:**
- Mengelola katalog aplikasi/layanan
- Menambah/edit/hapus aplikasi
- Mendefinisikan kategori masalah per aplikasi
- Melihat statistik tiket per aplikasi
- Generate laporan khusus aplikasi

**Dashboard Features:**
- Daftar aplikasi dan layanan
- Form manajemen aplikasi
- Statistik tiket per aplikasi
- Kategori masalah aplikasi
- Panel notifikasi perubahan status aplikasi

---

#### **Role 4: Teknisi**
**Tabel Database:** `teknisis`

**Hak Akses:**
- Melihat tiket yang ditugaskan
- Update status tiket (In Progress, Pending, Resolved)
- Menambahkan catatan teknis
- Upload dokumentasi/screenshot solusi
- Menandai tiket selesai
- Request reassignment jika diperlukan

**Dashboard Features:**
- Daftar tiket assigned
- Kalender jadwal pekerjaan
- Update status tools
- Knowledge base access
- Panel notifikasi tiket baru/urgent

---

## 5. Database Schema

### 5.1 Tabel Per Role (Separated Authentication)

#### **Tabel: users**
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    jabatan VARCHAR(100),
    unit_kerja VARCHAR(100),
    no_telepon VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **Tabel: admin_helpdesks**
```sql
CREATE TABLE admin_helpdesks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    level_admin ENUM('super', 'regular') DEFAULT 'regular',
    no_telepon VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **Tabel: admin_aplikasis**
```sql
CREATE TABLE admin_aplikasis (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    spesialisasi TEXT,
    no_telepon VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **Tabel: teknisis**
```sql
CREATE TABLE teknisis (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    keahlian TEXT,
    level_teknisi ENUM('junior', 'senior', 'expert') DEFAULT 'junior',
    no_telepon VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    max_concurrent_tickets INT DEFAULT 5,
    rating_avg DECIMAL(3,2) DEFAULT 0.00,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

### 5.2 Tabel Core System

#### **Tabel: aplikasis (Master Data Aplikasi/Layanan)**
```sql
CREATE TABLE aplikasis (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    kode_aplikasi VARCHAR(20) UNIQUE NOT NULL,
    nama_aplikasi VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    kategori ENUM('web', 'desktop', 'mobile', 'infrastruktur', 'lainnya'),
    pemilik_aplikasi VARCHAR(100),
    status ENUM('aktif', 'maintenance', 'deprecated') DEFAULT 'aktif',
    dokumentasi_url VARCHAR(255),
    created_by_admin_id BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_admin_id) REFERENCES admin_aplikasis(id)
);
```

#### **Tabel: kategori_masalahs**
```sql
CREATE TABLE kategori_masalahs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    aplikasi_id BIGINT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aplikasi_id) REFERENCES aplikasis(id) ON DELETE CASCADE
);
```

#### **Tabel: tickets**
```sql
CREATE TABLE tickets (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(20) UNIQUE NOT NULL, -- Format: TKT-YYYYMMDD-XXXX
    user_id BIGINT NOT NULL,
    aplikasi_id BIGINT,
    kategori_masalah_id BIGINT,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT NOT NULL,
    prioritas ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'assigned', 'in_progress', 'pending', 'resolved', 'closed', 'cancelled') DEFAULT 'open',
    assigned_teknisi_id BIGINT NULL,
    assigned_by_admin_id BIGINT NULL,
    assigned_at TIMESTAMP NULL,
    lokasi VARCHAR(100),
    lampiran JSON, -- Array of file paths
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (aplikasi_id) REFERENCES aplikasis(id),
    FOREIGN KEY (kategori_masalah_id) REFERENCES kategori_masalahs(id),
    FOREIGN KEY (assigned_teknisi_id) REFERENCES teknisis(id),
    FOREIGN KEY (assigned_by_admin_id) REFERENCES admin_helpdesks(id)
);
```

#### **Tabel: ticket_comments**
```sql
CREATE TABLE ticket_comments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ticket_id BIGINT NOT NULL,
    commenter_type ENUM('user', 'teknisi', 'admin_helpdesk', 'admin_aplikasi'),
    commenter_id BIGINT NOT NULL, -- ID dari tabel role yang relevan
    comment TEXT NOT NULL,
    lampiran JSON,
    is_internal BOOLEAN DEFAULT FALSE, -- Internal note untuk teknisi/admin saja
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);
```

#### **Tabel: ticket_history**
```sql
CREATE TABLE ticket_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ticket_id BIGINT NOT NULL,
    actor_type ENUM('user', 'teknisi', 'admin_helpdesk', 'admin_aplikasi', 'system'),
    actor_id BIGINT,
    action VARCHAR(50) NOT NULL, -- 'created', 'assigned', 'status_changed', 'priority_changed', dll
    old_value TEXT,
    new_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);
```

---

### 5.3 Tabel Notifikasi

#### **Tabel: notifications**
```sql
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    recipient_type ENUM('user', 'teknisi', 'admin_helpdesk', 'admin_aplikasi') NOT NULL,
    recipient_id BIGINT NOT NULL,
    ticket_id BIGINT,
    type VARCHAR(50) NOT NULL, -- 'ticket_created', 'ticket_assigned', 'status_updated', dll
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    action_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);
```

---

### 5.4 Tabel Laporan

#### **Tabel: reports**
```sql
CREATE TABLE reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    report_type ENUM('daily', 'weekly', 'monthly', 'custom'),
    generated_by_admin_id BIGINT NOT NULL,
    periode_start DATE NOT NULL,
    periode_end DATE NOT NULL,
    total_tickets INT DEFAULT 0,
    resolved_tickets INT DEFAULT 0,
    avg_resolution_time DECIMAL(10,2), -- dalam jam
    report_data JSON, -- Data statistik lengkap
    file_path VARCHAR(255), -- Path ke file export (PDF/Excel)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by_admin_id) REFERENCES admin_helpdesks(id)
);
```

---

## 6. Fitur Utama Aplikasi

### 6.1 Landing Page (Public)

**URL:** `/`

**Konten:**
- Header dengan logo Kemlu
- Hero section dengan deskripsi sistem helpdesk
- Fitur utama aplikasi (icon-based)
- Statistik publik (total tiket resolved, response time avg)
- FAQ section
- Footer dengan informasi kontak

**CTA Button:**
- **"Login"** → Mengarah ke `/login` (Portal login semua role)
- **'Register'** → Mengarah ke `/register (Portal register hanya untuk role user)

---

### 6.2 Portal Login (Multi-Role)

**URL:** `/login`

**Form Fields:**
- NIP (Text input, required)
- Password (Password input, required)
- Remember Me (Checkbox)

**Proses Authentication:**
1. Input NIP dan password
2. System melakukan pencarian NIP di 4 tabel secara berurutan:
   - `admin_helpdesks` (prioritas tertinggi)
   - `admin_aplikasis`
   - `teknisis`
   - `users`
3. Validasi password dengan hash yang tersimpan
4. Set session dan token (Sanctum)
5. Redirect ke dashboard sesuai role:
   - Admin Helpdesk → `/admin/dashboard`
   - Admin Aplikasi → `/admin-aplikasi/dashboard`
   - Teknisi → `/teknisi/dashboard`
   - User → `/user/dashboard`

**Error Handling:**
- NIP tidak ditemukan → "NIP atau password salah"
- Password salah → "NIP atau password salah"
- Akun tidak aktif → "Akun Anda telah dinonaktifkan. Hubungi admin."

---

### 6.3 Dashboard User

**URL:** `/user/dashboard`

**Komponen Utama:**

1. **Header Bar:**
   - Logo HelpDesk Kemlu
   - Search bar tiket
   - Notification bell dengan badge
   - User profile dropdown (logout)

2. **Statistics Cards:**
   - Total Tiket Saya
   - Tiket Open
   - Tiket In Progress
   - Tiket Resolved

3. **Quick Action:**
   - Button "Buat Tiket Baru" (prominent)

4. **Tabel Tiket Aktif:**
   - Kolom: No. Tiket, Judul, Aplikasi, Prioritas, Status, Teknisi, Tanggal Dibuat, Aksi
   - Filter: Status, Prioritas, Aplikasi
   - Sort: Terbaru, Terlama, Prioritas
   - Pagination

5. **Panel Notifikasi:**
   - List notifikasi real-time
   - Badge unread count
   - Mark as read functionality

**Fitur Interaktif:**
- Modal detail tiket (klik row)
- Form komentar tiket
- Upload lampiran
- Tombol tutup tiket (jika status = resolved)

---

### 6.4 Dashboard Admin Helpdesk

**URL:** `/admin/dashboard`

**Komponen Utama:**

1. **Overview Statistics (Cards):**
   - Total Tiket Hari Ini
   - Tiket Belum Ditugaskan
   - Tiket In Progress
   - Avg. Resolution Time
   - Tiket Resolved Hari Ini

2. **Chart & Analytics:**
   - Line chart: Trend tiket 7 hari terakhir
   - Pie chart: Distribusi status tiket
   - Bar chart: Tiket per aplikasi
   - Doughnut chart: Tiket per prioritas

3. **Queue Management:**
   - Daftar tiket belum assigned (high priority)
   - Quick assign dropdown (pilih teknisi)
   - Bulk assignment tools

4. **Teknisi Performance Table:**
   - Kolom: Nama, Tiket Aktif, Tiket Resolved, Avg. Rating, Avg. Resolution Time
   - Sortable columns

5. **Quick Access Menu:**
   - Kelola User
   - Kelola Admin
   - Kelola Teknisi
   - Generate Laporan
   - Pengaturan Sistem

6. **Panel Notifikasi:**
   - Notifikasi tiket urgent
   - Notifikasi escalation
   - Notifikasi perubahan sistem

**Fitur Admin:**
- CRUD semua role
- Assign/reassign tiket
- Override status tiket
- Export data ke Excel dan PDF
- Activity log viewer

---

### 6.5 Dashboard Admin Aplikasi

**URL:** `/admin-aplikasi/dashboard`

**Komponen Utama:**

1. **Statistics Cards:**
   - Total Aplikasi Aktif
   - Aplikasi Maintenance
   - Total Kategori Masalah
   - Tiket Bulan Ini

2. **Daftar Aplikasi (Table):**
   - Kolom: Kode, Nama, Kategori, Status, Total Tiket, Aksi
   - Search dan filter
   - Button "Tambah Aplikasi Baru"

3. **Manajemen Kategori Masalah:**
   - List kategori per aplikasi
   - Quick edit inline
   - Add/delete kategori

4. **Statistik Tiket per Aplikasi:**
   - Bar chart horizontal
   - Clickable untuk drill-down

5. **Panel Notifikasi:**
   - Notifikasi tiket baru terkait aplikasi tertentu
   - Notifikasi request penambahan aplikasi

---

### 6.6 Dashboard Teknisi

**URL:** `/teknisi/dashboard`

**Komponen Utama:**

1. **Statistics Cards:**
   - Tiket Assigned ke Saya
   - Tiket In Progress
   - Tiket Resolved Hari Ini
   - Rating Rata-rata Saya

2. **My Tasks (Kanban Board):**
   - Column: Assigned, In Progress, Pending, Resolved
   - Drag & drop untuk update status
   - Label prioritas (color-coded)

3. **Tabel Detail Tiket:**
   - Kolom: No. Tiket, Judul, User, Prioritas, Status, Deadline, Aksi
   - Filter dan search

4. **Knowledge Base Access:**
   - Search dokumentasi solusi
   - FAQ teknisi

5. **Panel Notifikasi:**
   - Notifikasi tiket baru assigned
   - Notifikasi urgent ticket
   - Notifikasi comment dari user

**Fitur Teknisi:**
- Update status tiket
- Add technical notes (internal)
- Upload screenshot solusi
- Mark ticket resolved
- Request reassignment

---

## 7. Fitur Sistem Notifikasi

### 7.1 Trigger Notifikasi

| Event | Recipient | Notification Message |
|-------|-----------|---------------------|
| Tiket baru dibuat | Admin Helpdesk | "Tiket baru #{ticket_number} dibuat oleh {user_name}" |
| Tiket assigned | Teknisi | "Tiket #{ticket_number} telah ditugaskan kepada Anda" |
| Status tiket berubah | User (pembuat) | "Status tiket #{ticket_number} diubah menjadi {new_status}" |
| Komentar baru di tiket | User & Teknisi | "Komentar baru di tiket #{ticket_number}" |
| Tiket resolved | User (pembuat) | "Tiket #{ticket_number} telah diselesaikan. Silakan berikan rating." |
| Tiket urgent tidak tertangani | Admin Helpdesk | "Tiket urgent #{ticket_number} belum ditugaskan selama {duration}" |
| Rating diberikan | Teknisi | "Anda menerima rating {stars} bintang untuk tiket #{ticket_number}" |

### 7.2 Notification Display

**Lokasi:** Header bar setiap dashboard (Icon bell dengan badge)

**Interaksi:**
- Click icon bell → Dropdown list notifikasi
- Max 5 notifikasi terbaru di dropdown
- Link "Lihat Semua" → Halaman notifikasi lengkap
- Click notifikasi → Mark as read & redirect ke tiket/halaman terkait

**Real-time:**
- Gunakan **Laravel reverb + laravel echo websocket** untuk notifikasi real-time
- Badge count update otomatis tanpa refresh

---

## 8. Fitur Manajemen Tiket

### 8.1 Pembuatan Tiket (User)

**Form Fields:**
- Aplikasi/Layanan (Dropdown, required)
- Kategori Masalah (Dropdown, auto-populated based on aplikasi, required)
- Judul (Text input, max 200 char, required)
- Deskripsi Detail (Textarea, required)
- Prioritas (Radio/Dropdown: Low, Medium, High, default: Medium)
- Lokasi (Text input, optional)
- Lampiran (File upload, multiple, max 5 files @ 2MB, allowed: jpg, png, pdf, docx)

**Proses:**
1. User mengisi form
2. System generate ticket number (TKT-20250929-0001)
3. Status default: "open"
4. Insert ke database `tickets`
5. Create notification untuk Admin Helpdesk
6. Redirect ke detail tiket dengan pesan sukses

#### 8.1.1 Draft Saving Feature

**Purpose:** Allow users to save incomplete ticket forms and resume later

**Implementation:**
- Backend persistence using `ticket_drafts` table
- Auto-save every 3 seconds after form changes
- Manual save via "Save as Draft" button
- Drafts expire after 7 days
- One draft per user (updateOrCreate behavior)
- Automatic cleanup via scheduled command

**User Experience:**
- Draft saved indicator shows last save time
- On page load, prompt user if draft exists
- User can accept (load draft) or decline (delete draft)
- Draft includes: aplikasi, kategori, judul, deskripsi, prioritas, lokasi
- File attachments are NOT saved in drafts (security consideration)

**Technical Details:**
- API Endpoints:
  - POST `/user/tickets/drafts/save` - Save/update draft
  - GET `/user/tickets/drafts/load` - Load active draft
  - DELETE `/user/tickets/drafts/delete` - Delete draft
- Scheduled Task: `drafts:cleanup` runs daily at 2:00 AM
- Database: `ticket_drafts` table with foreign keys to users, aplikasis, kategori_masalahs

**Security:**
- Drafts are user-specific (cannot access other users' drafts)
- Drafts are automatically deleted after 7 days
- File uploads are not persisted in drafts (must be re-uploaded)

---

### 8.2 Assignment Tiket (Admin Helpdesk)

**Proses:**
1. Admin melihat daftar tiket open/unassigned
2. Klik tombol "Assign" pada tiket
3. Modal muncul dengan dropdown list teknisi (filter by keahlian)
4. Admin memilih teknisi
5. System update `tickets.assigned_teknisi_id` dan `tickets.status = 'assigned'`
6. Create notification untuk teknisi terpilih
7. Log di `ticket_history`

**Auto-Assignment (Optional Feature):**
- Algorithm: Round-robin atau load balancing based on `teknisis.max_concurrent_tickets`
- Bisa diaktifkan/nonaktifkan di settings

---

### 8.3 Penanganan Tiket (Teknisi)

**Status Flow:**
```
assigned → in_progress → pending (optional) → resolved → closed (by user)
```

**Actions:**
- **Start Working:** assigned → in_progress
- **Add Comment:** Internal note atau visible to user
- **Upload Documentation:** Screenshot solusi, dokumen teknis
- **Mark as Pending:** Jika butuh informasi tambahan dari user
- **Resolve Ticket:** in_progress → resolved (wajib add resolution note)

---

### 8.4 Penutupan Tiket (User)

**Proses:**
1. User menerima notifikasi "Tiket resolved"
2. User membuka detail tiket
3. Muncul form feedback:
   - Rating (1-5 stars)
   - Feedback text (optional)
4. Klik "Tutup Tiket"
5. System update `tickets.status = 'closed'`, `tickets.closed_at`, `tickets.rating`, `tickets.feedback`
6. Update `teknisis.rating_avg` (recalculate)

---

## 9. Fitur Laporan & Analytics

### 9.1 Report Types (Admin Helpdesk)

**1. Laporan Harian**
- Total tiket masuk hari ini
- Tiket resolved hari ini
- Avg. resolution time
- Tiket by status
- Tiket by prioritas

**2. Laporan Mingguan**
- Trend tiket 7 hari
- Top 5 aplikasi dengan tiket terbanyak
- Performa teknisi (resolved tickets, avg. rating)
- Escalation rate

**3. Laporan Bulanan**
- Total tiket bulan ini
- YoY comparison
- Aplikasi dengan tiket terbanyak
- Kategori masalah terbanyak
- Teknisi terbaik (best performer)
- User satisfaction (avg. rating)

**4. Laporan Custom**
- Admin bisa pilih date range
- Filter by aplikasi, kategori, teknisi
- Export ke PDF atau Excel

### 9.2 Dashboard Analytics Charts

**Chart Library:** Recharts (Vue compatible) atau Chart.js

**Chart Types:**
- Line Chart: Trend tiket over time
- Bar Chart: Tiket per aplikasi/kategori
- Pie/Doughnut Chart: Status distribution
- Horizontal Bar: Teknisi performance
- Area Chart: Response time trend

---

## 10. Pengaturan & Konfigurasi

### 10.1 System Settings (Admin Helpdesk)

**Kategori Pengaturan:**

**1. General Settings**
- Nama sistem
- Logo upload
- Working hours (untuk SLA calculation)
- Timezone

**2. Notification Settings**
- Enable/disable notifikasi per event
- Email notification toggle
- Notification retention period

**3. Ticket Settings**
- Auto-assignment toggle
- Default priority
- Escalation rules (misal: urgent ticket unassigned > 2 hours)
- Auto-close resolved ticket after X days

**4. SLA Configuration**
- Response time by priority (urgent: 2 hours, high: 4 hours, dll)
- Resolution time by priority

**5. Email Configuration**
- SMTP settings
- Email templates

---

## 11. Technical Implementation

### 11.1 Laravel Project Structure

```
helpdesk-kemlu/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── RegisterController.php (Register new account only for role users)
│   │   │   │   └── LoginController.php (Multi-role login)
│   │   │   ├── User/
│   │   │   │   ├── DashboardController.php
│   │   │   │   └── TicketController.php
│   │   │   ├── AdminHelpdesk/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── TicketManagementController.php
│   │   │   │   ├── UserManagementController.php
│   │   │   │   └── ReportController.php
│   │   │   ├── AdminAplikasi/
│   │   │   │   ├── DashboardController.php
│   │   │   │   └── AplikasiController.php
│   │   │   └── Teknisi/
│   │   │       ├── DashboardController.php
│   │   │       └── TicketHandlingController.php
│   │   └── Middleware/
│   │       ├── RoleMiddleware.php (Check role access)
│   │       └── CheckActiveAccount.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── AdminHelpdesk.php
│   │   ├── AdminAplikasi.php
│   │   ├── Teknisi.php
│   │   ├── Ticket.php
│   │   ├── Aplikasi.php
│   │   ├── KategoriMasalah.php
│   │   ├── TicketComment.php
│   │   ├── TicketHistory.php
│   │   ├── Notification.php
│   │   └── Report.php
│   ├── Services/
│   │   ├── AuthService.php (Multi-table authentication logic)
│   │   ├── TicketService.php
│   │   ├── NotificationService.php
│   │   └── ReportService.php
│   └── Observers/
│       └── TicketObserver.php (Auto-trigger notifications)
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── js/
│       ├── Pages/
│       │   ├── Landing.vue
│       │   ├── Login.vue
│       │   ├── User/
│       │   │   ├── Dashboard.vue
│       │   │   └── TicketCreate.vue
│       │   ├── AdminHelpdesk/
│       │   │   ├── Dashboard.vue
│       │   │   ├── TicketManagement.vue
│       │   │   ├── UserManagement.vue
│       │   │   └── Reports.vue
│       │   ├── AdminAplikasi/
│       │   │   ├── Dashboard.vue
│       │   │   └── AplikasiManagement.vue
│       │   └── Teknisi/
│       │       ├── Dashboard.vue
│       │       └── TicketHandling.vue
│       ├── Components/
│       │   ├── Layout/
│       │   │   ├── Navbar.vue
│       │   │   ├── Sidebar.vue
│       │   │   └── NotificationBell.vue
│       │   ├── Ticket/
│       │   │   ├── TicketCard.vue
│       │   │   ├── TicketTable.vue
│       │   │   ├── TicketModal.vue
│       │   │   └── CommentSection.vue
│       │   ├── Charts/
│       │   │   ├── LineChart.vue
│       │   │   ├── BarChart.vue
│       │   │   └── PieChart.vue
│       │   └── Common/
│       │       ├── StatCard.vue
│       │       ├── DataTable.vue
│       │       └── FileUpload.vue
│       └── app.js
├── routes/
│   ├── web.php (Inertia routes)
│   └── api.php (Optional API endpoints)
└── tailwind.config.js
```

## 13. Security Considerations

### 13.1 Authentication Security
- Password hashing menggunakan `bcrypt` (Laravel default)
- Session timeout: 120 menit (configurable)
- CSRF protection pada semua form
- Rate limiting pada login endpoint (max 5 attempts per 5 minutes)
- Account lockout setelah 5 failed login attempts

### 13.2 Authorization
- Role-based middleware pada setiap route
- Check ownership pada ticket actions (user hanya bisa edit tiket sendiri)
- Admin privilege validation
- Prevent privilege escalation

### 13.3 Data Protection
- Input validation dan sanitization
- XSS protection (Vue automatic escaping)
- SQL Injection prevention (Eloquent ORM)
- File upload validation (type, size, extension)
- Secure file storage dengan private disk

### 13.4 HTTPS & SSL
- Force HTTPS di production
- Secure cookie flags
- HTTP Strict Transport Security (HSTS)

---

## 14. Performance Optimization

### 14.1 Database
- Proper indexing pada kolom yang sering di-query (NIP, ticket_number, status)
- Eager loading untuk relasi (avoid N+1 problem)
- Query caching untuk data statis (aplikasi, kategori)
- Database connection pooling

### 14.2 Frontend
- Lazy loading komponen Vue
- Image optimization dan lazy loading
- Responsive design all devices
- Code splitting dengan Vite
- TailwindCSS purging untuk production
- Browser caching untuk static assets

### 14.3 Application
- Queue untuk email notifications
- Cache dashboard statistics (5 minutes TTL)
- Optimize file uploads dengan chunking
- Background job untuk report generation

---

## 15. Deployment & DevOps

### 15.1 Server Requirements
- **OS:** Ubuntu 22.04 LTS atau CentOS 8+
- **Web Server:** Nginx atau Apache
- **PHP:** 8.2+
- **Database:** MySQL 8.0+
- **Node.js:** 18+ (untuk build frontend)
- **Memory:** Minimum 2GB RAM
- **Storage:** Minimum 20GB (untuk file uploads)

### 15.2 Deployment Steps
1. Clone repository
2. Install dependencies: `composer install --optimize-autoloader --no-dev`
3. Install frontend: `npm install && npm run build`
4. Setup `.env` file
5. Generate application key: `php artisan key:generate`
6. Run migrations: `php artisan migrate --force`
7. Run seeders: `php artisan db:seed`
8. Setup storage link: `php artisan storage:link`
9. Setup cron job untuk scheduled tasks
10. Configure web server (Nginx/Apache)
11. Setup SSL certificate (Let's Encrypt)
12. Configure firewall

### 15.3 Backup Strategy
- **Database backup:** Daily automated backup
- **File uploads backup:** Weekly backup
- **Retention:** 30 days
- **Backup storage:** Off-site storage

---

## 16. Testing Strategy

### 16.1 Unit Testing
- Model relationships testing
- Service class logic testing
- Helper function testing

### 16.2 Feature Testing
- Authentication flow testing
- Ticket creation and management
- Role-based access control
- Notification triggering

### 16.3 Browser Testing
- E2E testing dengan Laravel Dusk
- Cross-browser compatibility (Chrome, Firefox, Safari, Edge)
- Responsive design testing (mobile, tablet, desktop)

---

## 17. Documentation Requirements

### 17.1 Technical Documentation
- API documentation (jika ada)
- Database schema diagram (ERD)
- System architecture diagram
- Deployment guide
- Configuration guide

### 17.2 User Documentation
- User manual per role
- FAQ section
- Video tutorial
- Troubleshooting guide

### 17.3 Admin Documentation
- System administration guide
- User management guide
- Report generation guide
- System configuration guide

---

## 18. Future Enhancements (Roadmap)

### Phase 2 (3-6 bulan setelah launch)
- Mobile app (React Native atau Flutter)
- Integrasi dengan sistem Single Sign-On (SSO) Kemlu
- Live chat support antar user dan teknisi
- AI-powered ticket categorization
- Self-service knowledge base

### Phase 3 (6-12 bulan)
- Dashboard analytics lanjutan dengan predictive analysis
- Integrasi dengan monitoring tools (Nagios, Zabbix)
- Auto-assignment dengan machine learning
- Voice-to-ticket (speech recognition)
- Multi-language support

---

## 19. Success Metrics (KPIs)

### 19.1 Operational Metrics
- **Average Response Time:** < 2 hours untuk urgent tickets
- **Average Resolution Time:** < 24 hours untuk high priority
- **First Contact Resolution Rate:** > 60%
- **Ticket Escalation Rate:** < 15%

### 19.2 User Satisfaction
- **User Satisfaction Score:** > 4.0/5.0
- **Net Promoter Score (NPS):** > 50
- **Teknisi Performance Rating:** > 4.0/5.0

### 19.3 System Performance
- **System Uptime:** > 99.5%
- **Page Load Time:** < 2 seconds
- **API Response Time:** < 500ms

---

## 20. Timeline

| Phase | Duration | Deliverables |
|-------|----------|-------------|
| **Phase 1: Planning & Design** | 3 weeks | Requirements finalization, Database design, UI/UX mockups |
| **Phase 2: Development** | 10 weeks | Backend API, Frontend components, Integration |
| **Phase 3: Testing** | 3 weeks | Unit testing, Integration testing, UAT |
| **Phase 4: Deployment** | 2 weeks | Server setup, Deployment, Training |
| **Phase 5: Support** | Ongoing | Bug fixes, Feature requests, Maintenance |
| **TOTAL** | **18 weeks** (~4.5 months) | |

---

## 21. Risks & Mitigation

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Scope creep | High | Medium | Clear requirements documentation, Change request process |
| Resource unavailability | High | Low | Backup developers, Knowledge transfer |
| Security breach | Critical | Low | Security audit, Penetration testing, Regular updates |
| Performance issues | Medium | Medium | Load testing, Performance monitoring, Scalable architecture |
| User adoption resistance | Medium | Medium | Comprehensive training, User-friendly interface, Change management |

---