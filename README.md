# IT Helpdesk - PHP Native

Aplikasi helpdesk IT berbasis PHP native dengan arsitektur MVC sederhana, menggunakan Bootstrap 5 (SB Admin template) dan MySQL.

## Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP 8.1+ (Native/Procedural) |
| Database | MySQL / MariaDB |
| Frontend | Bootstrap 5.2.3 + SB Admin v7.0.7 |
| Icons | Font Awesome 6.3.0 |
| Font | Inter (Google Fonts) |
| Charts | Chart.js 2.8.0 |
| Tables | Simple-DataTables 7.1.2 |
| Alerts | SweetAlert2 v11 |
| Server | Laragon / Apache |

## Struktur Folder

```
it-helpdesk-php-native-master/
├── assets/
│   └── demo/                  # Chart.js demo scripts
├── config/
│   ├── config.php             # Koneksi DB & base URL
│   └── function.php           # Semua fungsi helper & CRUD
├── css/
│   └── styles.css             # Bootstrap + SB Admin + custom CSS
├── includes/
│   ├── header.php             # Navbar, sidebar, HTML head
│   └── footer.php             # Footer, JS scripts
├── js/
│   ├── scripts.js             # Sidebar toggle logic
│   └── datatables-simple-demo.js  # DataTable init
├── uploads/
│   └── evidence/              # Bukti kendala tiket (uploaded files)
├── login/
│   └── index.php              # Halaman login
├── logout/
│   └── index.php              # Proses logout
├── page/
│   ├── akun/index.php         # MANAGER: CRUD akun user
│   ├── kategori/index.php     # MANAGER: CRUD kategori
│   ├── chat/
│   │   ├── index.php          # STAFF: Chat + resolve tiket
│   │   ├── user.php           # USER: Chat view
│   │   └── ajax_messages.php  # AJAX endpoint chat polling
│   ├── dashboard/
│   │   ├── index.php          # STAFF: Dashboard + leaderboard
│   │   ├── manager.php        # MANAGER: Charts + leaderboard + detail staff
│   │   └── user.php           # USER: Dashboard + statistik tiket
│   ├── profil/index.php       # Semua role: profil & ubah password
│   ├── tiket/
│   │   ├── buat.php           # USER: Buat tiket baru
│   │   ├── baru.php           # STAFF: Tiket OPEN (ambil tiket)
│   │   ├── antrian.php        # USER: Tiket dalam proses
│   │   ├── proses.php         # STAFF: Tiket ditangani
│   │   ├── selesai.php        # USER: Tiket selesai
│   │   └── riwayat.php        # STAFF: Riwayat tiket selesai
│   └── validasi/index.php     # MANAGER: Validasi poin tiket
├── schema/
│   ├── helpdesk.sql           # Database schema + seed data
│   └── seed.php               # Seed/update data awal
└── index.php                  # Redirect ke login/
```

## System Flow Map

### Alur Umum Aplikasi

```
┌─────────────────────────────────────────────────────────────────┐
│                        USER (Pengguna)                          │
│                                                                 │
│  ┌──────────┐    ┌──────────────┐    ┌──────────────┐          │
│  │  Login   │───>│  Buat Tiket  │───>│  Antrian     │          │
│  │          │    │  + Upload    │    │  (OPEN/      │          │
│  │          │    │  Bukti       │    │  IN_PROGRESS/ │          │
│  │          │    │              │    │  PENDING)     │          │
│  └──────────┘    └──────────────┘    └──────┬───────┘          │
│                                             │                   │
│                                    ┌────────▼────────┐          │
│                                    │  Chat dengan    │          │
│                                    │  Staff Support  │          │
│                                    └────────┬────────┘          │
│                                             │                   │
│                                    ┌────────▼────────┐          │
│                                    │  Selesai        │          │
│                                    │  (RESOLVED/     │          │
│                                    │  CLOSED)        │          │
│                                    └─────────────────┘          │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      STAFF (IT Support)                         │
│                                                                 │
│  ┌──────────┐    ┌──────────────┐    ┌──────────────┐          │
│  │  Login   │───>│  Dashboard   │───>│  Tiket Baru  │          │
│  │          │    │  (Statistik  │    │  (OPEN)      │          │
│  │          │    │  + Leaderboard)   │  Klaim tiket  │          │
│  └──────────┘    └──────────────┘    └──────┬───────┘          │
│                                             │                   │
│                                    ┌────────▼────────┐          │
│                                    │  Proses Tiket   │          │
│                                    │  (IN_PROGRESS)  │          │
│                                    │  + Chat User    │          │
│                                    └────────┬────────┘          │
│                                             │                   │
│                          ┌──────────────────┼──────────────┐    │
│                          │                  │              │    │
│                 ┌────────▼───────┐ ┌────────▼───────┐     │    │
│                 │  Pending       │ │  Selesai       │     │    │
│                 │  (PENDING)     │ │  (RESOLVED)    │     │    │
│                 │  + Alasan      │ │  + Catatan     │     │    │
│                 └────────────────┘ └────────────────┘     │    │
│                                                            │    │
│                                    ┌───────────────────────┘    │
│                                    │                            │
│                           ┌────────▼────────┐                   │
│                           │  Riwayat        │                   │
│                           │  (RESOLVED/     │                   │
│                           │  CLOSED)        │                   │
│                           └─────────────────┘                   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      MANAGER (Super Admin)                      │
│                                                                 │
│  ┌──────────┐    ┌──────────────┐    ┌──────────────┐          │
│  │  Login   │───>│  Dashboard   │───>│  Charts:     │          │
│  │          │    │  (Charts +   │    │  - Keluhan   │          │
│  │          │    │  Leaderboard)│    │  - Progress  │          │
│  └──────────┘    └──────────────┘    └──────────────┘          │
│                                                                 │
│  ┌──────────────────────────────────────────────────────┐      │
│  │  Pengaturan                                           │      │
│  │  ┌─────────────┐  ┌─────────────┐  ┌──────────────┐ │      │
│  │  │  Akun       │  │  Kategori   │  │  Validasi    │ │      │
│  │  │  (CRUD user │  │  (CRUD      │  │  Poin        │ │      │
│  │  │  + role)    │  │  kategori)  │  │  (Setujui    │ │      │
│  │  │             │  │             │  │  poin tiket) │ │      │
│  │  └─────────────┘  └─────────────┘  └──────────────┘ │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
```

### Alur Status Tiket

```
                    ┌─────────────┐
                    │   USER      │
                    │  Buat Tiket │
                    └──────┬──────┘
                           │
                           ▼
                    ┌─────────────┐
                    │    OPEN     │  Tiket baru, menunggu staff
                    └──────┬──────┘
                           │
                    STAFF klaim tiket
                           │
                           ▼
                    ┌─────────────┐
                    │ IN_PROGRESS │  Staff sedang menangani
                    └──┬───────┬──┘
                       │       │
          Staff pending │       │ Staff selesaikan
                       │       │
                       ▼       ▼
              ┌──────────┐  ┌───────────┐
              │ PENDING  │  │ RESOLVED  │  Menunggu validasi manager
              │ + Alasan │  │ + Catatan │
              └────┬─────┘  └─────┬─────┘
                   │              │
          Staff    │     Manager  │
          lanjutkan│     validasi │
                   │              │
                   ▼              ▼
              IN_PROGRESS   ┌──────────┐
                            │  CLOSED   │  Tiket ditutup
                            │  + Poin   │  Poin diberikan ke staff
                            └──────────┘
```

### Detail Status Tiket

| Status | Label | Keterangan | Badge |
|--------|-------|------------|-------|
| `OPEN` | Terbuka | Tiket baru, menunggu staff mengambil | Hijau |
| `IN_PROGRESS` | Diproses | Sedang ditangani staff | Biru |
| `PENDING` | Tertunda | Ditunda oleh staff (dengan alasan) | Kuning |
| `TRANSFERRING` | Dialihkan | Dialihkan ke staff/divisi lain | Kuning |
| `RESOLVED` | Selesai | Diselesaikan oleh staff, menunggu validasi | Cyan |
| `CLOSED` | Ditutup | Divalidasi oleh manager, poin diberikan | Abu-abu |

### Alur Login & Autentikasi

```
┌──────────┐     ┌──────────────┐     ┌──────────────────────┐
│  Input   │────>│  Cek Email   │────>│  Cek Password        │
│  Email + │     │  di Database │     │  (password_verify)   │
│  Password│     │              │     │                      │
└──────────┘     └──────────────┘     └──────────┬───────────┘
                                                  │
                                    ┌─────────────┼─────────────┐
                                    │             │             │
                                 Berhasil      Gagal         Rate Limit
                                    │             │             │
                                    ▼             ▼             ▼
                             ┌──────────┐  ┌──────────┐  ┌──────────┐
                             │ Redirect │  │  Error   │  │  Error   │
                             │ by Role: │  │  Message │  │  Too Many│
                             │ MANAGER  │  │          │  │  Attempts│
                             │ -> /dashboard/manager │  └──────────┘
                             │ STAFF    │
                             │ -> /dashboard/
                              │ USER     │
                              │ -> /dashboard/user.php
                              └──────────┘
```

### Struktur Role & Akses

```
┌─────────────────────────────────────────────────────────────┐
│                        AKSES MENU                           │
├─────────────────────┬─────────┬─────────┬──────────────────┤
│ Halaman             │ USER    │ STAFF   │ MANAGER          │
├─────────────────────┼─────────┼─────────┼──────────────────┤
│ Dashboard User      │   ✓     │         │                  │
│ Buat Tiket          │   ✓     │         │                  │
│ Tiket Antrian (User)│   ✓     │         │                  │
│ Tiket Selesai (User)│   ✓     │         │                  │
│ Chat (User)         │   ✓     │         │                  │
│ Profil              │   ✓     │   ✓     │      ✓           │
│ Dashboard           │         │   ✓     │                  │
│ Tiket Baru (Staff)  │         │   ✓     │      ✓           │
│ Proses Tiket        │         │   ✓     │                  │
│ Riwayat Tiket       │         │   ✓     │                  │
│ Chat (Staff)        │         │   ✓     │                  │
│ Dashboard Manager   │         │         │      ✓           │
│ Validasi Poin       │         │         │      ✓           │
│ Kelola Akun         │         │         │      ✓           │
│ Kelola Kategori     │         │         │      ✓           │
└─────────────────────┴─────────┴─────────┴──────────────────┘
```

### Alur Chat

```
┌─────────────┐                    ┌─────────────┐
│    USER     │                    │    STAFF    │
│             │                    │             │
│  Kirim      │──── Chat Table ───>│  Terima     │
│  Pesan      │    (Database)      │  Pesan      │
│             │                    │             │
│  Terima     │<─── AJAX Poll ────│  Kirim      │
│  Pesan      │    (setiap 3 detik)│  Pesan      │
│             │                    │             │
│  [Kembali]  │                    │  [Selesai]  │
│             │                    │  [Kesulitan]│
└─────────────┘                    └─────────────┘
```

### Alur Leaderboard & Poin

```
Tiket RESOLVED
      │
      ▼
Manager Validasi
      │
      ▼
┌─────────────────────────────────────────┐
│  LeaderboardLog                         │
│  - staff_id: ID staff                   │
│  - ticket_id: ID tiket                  │
│  - points: 10 x difficulty_level        │
│    (1=Mudah=10, 2=Sedang=20, 3=Sulit=30)│
│  - period_month: Bulan                  │
│  - period_year: Tahun                   │
└─────────────────────────────────────────┘
      │
      ▼
Dashboard Staff/Manager
  - Tabel Peringkat (ranking by poin)
  - Filter: Bulanan / Tahunan
```

## Database Schema

### Tabel Utama

```
User ─────────────┬──── Ticket ──────── Chat
  │                 │      │
  │                 │      └──── TicketAttachment
  │                 │
  └── Session       └──── LeaderboardLog
                           │
Category ──────────────────┘
  │
WA_Setting
Notification_Template
```

### Relasi

- `Ticket.category_id` → `Category.id`
- `Ticket.user_id` → `User.id` (pembuat tiket)
- `Ticket.staff_id` → `User.id` (staff penangani)
- `Chat.ticket_id` → `Ticket.id`
- `Chat.sender_id` → `User.id`
- `TicketAttachment.ticket_id` → `Ticket.id`
- `LeaderboardLog.staff_id` → `User.id`
- `LeaderboardLog.ticket_id` → `Ticket.id`
- `Session.user_id` → `User.id`

## Akun Default

| Role | Email | Password |
|------|-------|----------|
| MANAGER (Super Admin) | admin@helpdesk.local | admin123 |
| STAFF (IT Support) | staff@helpdesk.local | staff123 |
| USER (Demo) | user@helpdesk.local | user123 |

## Instalasi

### Prerequisites
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.4+
- Laragon / XAMPP / Apache

### Langkah-langkah

1. **Clone/Download** project ke folder web server:
   ```
   C:\laragon\www\it-helpdesk-php-native-master\
   ```

2. **Buat database** `helpdesk` di phpMyAdmin:
   ```sql
   CREATE DATABASE helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import schema**: Buka phpMyAdmin → pilih database `helpdesk` → Import → pilih `schema/helpdesk.sql`

4. **Konfigurasi database** di `config/config.php`:
   ```php
   $host     = 'localhost';
   $username = 'root';
   $password = '';
   $database = 'helpdesk';
   ```

5. **Buka aplikasi**: `http://localhost/it-helpdesk-php-native-master/login/`

6. **Login** dengan akun default di atas

### Update Data Awal (Opsional)
Jika ingin memperbarui password akun default:
```
http://localhost/it-helpdesk-php-native-master/schema/seed.php
```

## Fitur

### USER (Pengguna)
- Dashboard dengan statistik tiket (total, antrian, selesai)
- Buat tiket baru dengan deskripsi dan upload bukti
- Melihat tiket dalam antrian (OPEN, IN_PROGRESS, PENDING)
- Melihat tiket selesai (RESOLVED, CLOSED)
- Chat dengan staff support
- Melihat riwayat chat
- Ubah profil dan password

### STAFF (IT Support)
- Dashboard dengan statistik tiket dan leaderboard
- Melihat dan mengambil tiket baru (OPEN)
- Memproses tiket (IN_PROGRESS)
- Chat dengan user
- Menandai tiket selesai (RESOLVED)
- Menunda tiket (PENDING) dengan alasan
- Melihat riwayat tiket

### MANAGER (Super Admin)
- Dashboard dengan chart (keluhan user, progress penyelesaian)
- Leaderboard ranking staff
- Validasi poin tiket selesai
- Kelola akun user (CRUD + aktifkan/nonaktifkan)
- Kelola kategori tiket (CRUD)
- Kelola divisi (CRUD + prioritas)
- Melihat tiket baru (tanpa bisa klaim)
- Detail peringkat per staff + riwayat tiket selesai
- Pengaturan WhatsApp notification (gateway, template)

## Troubleshooting

### Upload Gambar/Lampiran Tidak Tersimpan

Jika upload file tidak tersimpan atau error, periksa hal berikut:

1. **Permission folder `uploads/`**
   - Pastikan folder `uploads/tickets/` dan `uploads/chat/` writable
   - Cek: `is_writable('uploads/tickets/')` harus `true`

2. **Konfigurasi PHP (`php.ini`)**
   ```ini
   file_uploads = On
   upload_max_filesize = 2G
   post_max_size = 2G
   ```

3. **Ekstensi cURL** (untuk WA notification)
   - Jika `curl_init()` undefined, uncomment `extension=curl` di `php.ini`
   - Aplikasi tetap berfungsi tanpa cURL (WA notification di-skip otomatis)

4. **Error log**
   - Cek `C:\laragon\tmp\php_errors.log` untuk detail error
   - Fungsi upload sudah mencatat error ke log jika gagal

5. **Fallback upload**
   - Jika `move_uploaded_file()` gagal (umum di Windows), aplikasi otomatis fallback ke `copy()`

### Koneksi Database Gagal

Jika muncul error `Access denied for user 'root'@'localhost'`:

1. Buka `config/config.php`
2. Sesuaikan password MySQL:
   ```php
   $password = '';        // Laragon default (kosong)
   $password = 'root';    // XAMPP default
   ```

### Reset Database

Jika perlu reset database dari awal:

```sql
DROP DATABASE IF EXISTS helpdesk;
CREATE DATABASE helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Lalu import ulang `schema/helpdesk.sql` via phpMyAdmin atau CLI:
```bash
mysql -u root helpdesk < schema/helpdesk.sql
```

## Keamanan

- Password di-hash dengan `password_hash()` (bcrypt)
- Session-based authentication dengan token random
- Rate limiting pada login
- `.htaccess` di semua folder `uploads/` untuk mencegah eksekusi PHP
- Prepared statements / `mysqli_real_escape_string()` pada semua query
- Role-based access control (USER, STAFF, MANAGER)

## Browser Cache

Aplikasi menggunakan cache-busting pada CSS (`?v=<?= time() ?>`) sehingga perubahan style langsung terlihat tanpa perlu hard refresh.

## Lisensi

MIT License - bebas digunakan dan dimodifikasi.
