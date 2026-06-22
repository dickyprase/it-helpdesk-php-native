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
| Server | Laragon / Apache / Nginx |

## Struktur Folder

```
it-helpdesk-php-native/
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
│   ├── evidence/              # Bukti kendala tiket (uploaded files)
│   ├── tickets/               # Lampiran tiket
│   └── chat/                  # Lampiran chat
├── login/
│   └── index.php              # Halaman login
├── logout/
│   └── index.php              # Proses logout
├── page/
│   ├── akun/index.php         # MANAGER: CRUD akun user
│   ├── kategori/index.php     # MANAGER: CRUD kategori
│   ├── divisi/index.php       # MANAGER: CRUD divisi
│   ├── chat/
│   │   ├── index.php          # STAFF/MANAGER: Chat + resolve tiket
│   │   ├── user.php           # USER: Chat view
│   │   └── ajax_messages.php  # AJAX endpoint chat polling
│   ├── dashboard/
│   │   ├── index.php          # STAFF: Dashboard + leaderboard
│   │   ├── manager.php        # MANAGER: Charts + leaderboard + detail staff
│   │   └── user.php           # USER: Dashboard + statistik tiket
│   ├── profil/index.php       # Semua role: profil & ubah password
│   ├── tiket/
│   │   ├── buat.php           # USER: Buat tiket baru
│   │   ├── open.php           # STAFF: Tiket OPEN belum ditugaskan (klaim)
│   │   ├── baru.php           # STAFF/MANAGER: Semua tiket + filter adaptive
│   │   ├── antrian.php        # USER: Tiket dalam proses
│   │   ├── proses.php         # STAFF: Tiket ditangani
│   │   ├── selesai.php        # USER: Tiket selesai
│   │   └── riwayat.php        # STAFF: Riwayat tiket selesai
│   ├── validasi/index.php     # MANAGER: Validasi poin tiket
│   ├── notifikasi/            # Notifikasi + mark read
│   └── wa-settings/           # Pengaturan WhatsApp gateway
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
│  │          │    │  (Statistik  │    │  (OPEN,       │          │
│  │          │    │  + Leaderboard)   │  unassigned)  │          │
│  └──────────┘    └──────────────┘    └──────┬───────┘          │
│                                             │                   │
│                                    ┌────────▼────────┐          │
│                                    │  Semua Tiket   │          │
│                                    │  (+ filter:    │          │
│                                    │  status, user, │          │
│                                    │  staff, tanggal)│         │
│                                    └────────┬────────┘          │
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
│  │  Menu Tiket (Semua tiket + filter adaptive)          │      │
│  │  Filter: status, nama user, staff IT, rentang tanggal │      │
│  └──────────────────────────────────────────────────────┘      │
│                                                                 │
│  ┌──────────────────────────────────────────────────────┐      │
│  │  Pengaturan                                           │      │
│  │  ┌─────────────┐  ┌─────────────┐  ┌──────────────┐ │      │
│  │  │  Akun       │  │  Kategori   │  │  Validasi    │ │      │
│  │  │  (CRUD user │  │  (CRUD      │  │  Poin        │ │      │
│  │  │  + role)    │  │  kategori)  │  │  (Setujui    │ │      │
│  │  │  + auto     │  │             │  │  poin tiket) │ │      │
│  │  │  divisi)    │  │             │  │              │ │      │
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
| `RESOLVED` | Selesai | Diselesaikan oleh staff, menunggu validasi | Cyan |
| `CLOSED` | Ditutup | Divalidasi oleh manager, poin diberikan | Abu-abu |

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
│ Tiket Baru (Klaim)  │         │   ✓     │      ✓           │
│ Semua Tiket+Filter  │         │   ✓     │      ✓           │
│ Proses Tiket        │         │   ✓     │                  │
│ Riwayat Tiket       │         │   ✓     │                  │
│ Chat (Staff)        │         │   ✓     │                  │
│ Dashboard Manager   │         │         │      ✓           │
│ Validasi Poin       │         │         │      ✓           │
│ Kelola Akun         │         │         │      ✓           │
│ Kelola Kategori     │         │         │      ✓           │
│ Kelola Divisi       │         │         │      ✓           │
│ Pengaturan WA       │         │         │      ✓           │
└─────────────────────┴─────────┴─────────┴──────────────────┘
```

### Alur Chat

```
┌─────────────┐                    ┌─────────────┐
│    USER     │                    │    STAFF    │
│             │                    │             │
│  Kirim      │──── Chat Table ───>│  Terima     │
│  Pesan      │    (Database)      │  Pesan      │
│  + Gambar   │                    │             │
│             │                    │             │
│  Terima     │<─── AJAX Poll ────│  Kirim      │
│  Pesan      │    (setiap 3 detik)│  Pesan      │
│  + Gambar   │    + Inline image  │  + Gambar   │
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
  - Detail per staff (tiket, kategori, kesulitan)
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
- `ChatAttachment.chat_id` → `Chat.id`
- `LeaderboardLog.staff_id` → `User.id`
- `LeaderboardLog.ticket_id` → `Ticket.id`
- `Session.user_id` → `User.id`

## Akun Default

Setelah import `schema/helpdesk.sql`, akun berikut tersedia:

### Manager

| Nama | Email | Password |
|------|-------|----------|
| Super Admin | admin@helpdesk.local | admin123 |

### Staff IT (divisi: IT Support)

| Nama | Email | Password |
|------|-------|----------|
| Dani | dani@helpdesk.local | staff123 |
| Andre | andre@helpdesk.local | staff123 |
| Zainal | zainal@helpdesk.local | staff123 |
| Rizal | rizal@helpdesk.local | staff123 |
| Angga | angga@helpdesk.local | staff123 |

### User (divisi: General Affairs)

| Nama | Email | Password |
|------|-------|----------|
| Budi Santoso | budi@helpdesk.local | user123 |
| Siti Rahayu | siti@helpdesk.local | user123 |
| Joko Widodo | joko@helpdesk.local | user123 |

## Instalasi

### Prerequisites
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.4+
- Nginx / Apache / Laragon / XAMPP

### Langkah-langkah

1. **Clone/Download** project ke folder web server:
   ```
   /var/www/html/it-helpdesk-php-native/
   ```

2. **Buat database** `helpdesk` di phpMyAdmin atau CLI:
   ```sql
   CREATE DATABASE helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import schema**: 
   ```bash
   mysql -u root helpdesk < schema/helpdesk.sql
   ```
   Atau via phpMyAdmin → Import → pilih `schema/helpdesk.sql`

4. **Konfigurasi database** di `config/config.php`:
   ```php
   $host     = 'localhost';
   $username = 'root';
   $password = '';
   $database = 'helpdesk';
   ```

5. **Buka aplikasi**: `http://localhost/it-helpdesk-php-native/login/`

6. **Login** dengan akun default di atas

### Update Data Awal (Opsional)
Jika ingin memperbarui password akun default:
```
http://localhost/it-helpdesk-php-native/schema/seed.php
```

## Fitur

### USER (Pengguna)
- Dashboard dengan statistik tiket (total, antrian, selesai)
- Buat tiket baru dengan deskripsi dan upload bukti
- Melihat tiket dalam antrian (OPEN, IN_PROGRESS, PENDING)
- Melihat tiket selesai (RESOLVED, CLOSED)
- Chat dengan staff support + upload gambar inline
- Melihat riwayat chat
- Ubah profil dan password

### STAFF (IT Support)
- Dashboard dengan statistik tiket dan leaderboard
- **Tiket Baru**: Melihat dan mengambil tiket OPEN yang belum ditugaskan
- **Semua Tiket**: View semua tiket dengan filter adaptive (status, user, staff, tanggal)
- Memproses tiket (IN_PROGRESS)
- Chat dengan user + upload gambar inline
- Menandai tiket selesai (RESOLVED)
- Menunda tiket (PENDING) dengan alasan
- Melihat riwayat tiket selesai

### MANAGER (Super Admin)
- Dashboard dengan chart (keluhan user, progress penyelesaian)
- Leaderboard ranking staff + detail per staff
- **Menu Tiket**: Semua tiket dengan filter adaptive (status, nama user, staff IT, rentang tanggal)
- Validasi poin tiket selesai
- Kelola akun user (CRUD + aktifkan/nonaktifkan + auto-assign divisi IT Support)
- Kelola kategori tiket (CRUD)
- Kelola divisi (CRUD + prioritas)
- Pengaturan WhatsApp notification (gateway, template)

## Changelog

### v2.0 (Juni 2026)
- **Menu Tiket Baru**: Halaman khusus tiket OPEN belum ditugaskan untuk staff
- **Semua Tiket**: Halaman tiket general dengan filter adaptive (status, user, staff, tanggal)
- **Chat**: Upload gambar langsung tampil inline (bukan link), support attachment-only tanpa teks
- **Akun**: Role "Support" → "IT Support", auto-lock divisi IT Support saat pilih role IT Support
- **Kolom "Nama"**: Diganti jadi "Nama User" di semua tabel tiket untuk klaritas
- **Sidebar Staff**: "Menu Support" → "Menu IT Support", sub-menu "Baru" → "Semua Tiket"
- **Data dummy**: 5 staff IT (Dani, Andre, Zainal, Rizal, Angga) + 3 user (Budi, Siti, Joko)
- **Schema**: helpdesk.sql updated dengan akun baru, hapus staff demo & user demo
- **Upload**: Fix nginx client_max_body_size, fix UUID chat_id untuk attachment upload
- **Tabel tiket**: Tambah kolom "Staff IT" untuk menampilkan siapa yang menangani

### v1.0
- Aplikasi helpdesk IT dengan 3 role (USER, STAFF, MANAGER)
- CRUD tiket, chat real-time (AJAX polling), leaderboard & poin
- Dashboard dengan chart untuk manager
- WhatsApp notification gateway
- Upload bukti tiket + lampiran chat

## Troubleshooting

### Upload Gambar/Lampiran Tidak Tersimpan

1. **Permission folder `uploads/`**
   - Pastikan folder `uploads/tickets/` dan `uploads/chat/` writable (www-data / nginx)
   - `chown -R www-data:www-data uploads/`

2. **nginx: 413 Request Entity Too Large**
   - Tambahkan `client_max_body_size 75M;` di nginx.conf / sites-enabled
   - Reload nginx: `systemctl reload nginx`

3. **PHP upload limit**
   ```ini
   file_uploads = On
   upload_max_filesize = 50M
   post_max_size = 80M
   ```

4. **Fallback upload**
   - Jika `move_uploaded_file()` gagal, aplikasi otomatis fallback ke `copy()`

### Chat Attachment Tidak Masuk DB
- Periksa `sendMessage()` return `chat_id` dengan benar (UUID primary key, bukan auto_increment)
- Query attachment menggunakan `SELECT id FROM Chat WHERE ... ORDER BY created_at DESC LIMIT 1`

### Koneksi Database Gagal

1. Buka `config/config.php`
2. Sesuaikan kredensial MySQL:
   ```php
   $password = '';        // Laragon default (kosong)
   $password = 'root';    // XAMPP default
   ```

### Reset Database

```sql
DROP DATABASE IF EXISTS helpdesk;
CREATE DATABASE helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Lalu import ulang:
```bash
mysql -u root helpdesk < schema/helpdesk.sql
```

## Keamanan

- Password di-hash dengan `password_hash()` (bcrypt)
- Session-based authentication dengan token random
- Rate limiting pada login
- `.htaccess` di semua folder `uploads/` untuk mencegah eksekusi PHP
- `mysqli_real_escape_string()` pada semua query
- Role-based access control (USER, STAFF, MANAGER)
- Server-side auto-assign divisi IT Support untuk role STAFF

## Browser Cache

Aplikasi menggunakan cache-busting pada CSS (`?v=<?= time() ?>`) sehingga perubahan style langsung terlihat tanpa perlu hard refresh.

## Lisensi

MIT License - bebas digunakan dan dimodifikasi.
