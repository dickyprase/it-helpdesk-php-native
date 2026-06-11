-- ============================================================
-- IT Helpdesk Database Schema
-- Kompatibel dengan MySQL / MariaDB / phpMyAdmin
-- 
-- Cara import:
-- 1. Buka phpMyAdmin
-- 2. Buat database baru (misal: helpdesk)
-- 3. Pilih database tersebut
-- 4. Klik tab "Import"
-- 5. Pilih file ini → klik "Go"
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABEL: Division (harus sebelum User karena direferensi)
-- ============================================================
CREATE TABLE IF NOT EXISTS `Division` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `name` VARCHAR(100) NOT NULL,
  `priority_level` ENUM('TINGGI','SEDANG','RENDAH') NOT NULL DEFAULT 'SEDANG' COMMENT 'Label prioritas divisi',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Division_name_key` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: User
-- ============================================================
CREATE TABLE IF NOT EXISTS `User` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL COMMENT 'Nomor WhatsApp (08xx atau 628xx)',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
  `role` ENUM('USER', 'STAFF', 'MANAGER') NOT NULL DEFAULT 'USER',
  `division_id` CHAR(36) DEFAULT NULL COMMENT 'Divisi user',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `User_email_key` (`email`),
  KEY `User_division_id_idx` (`division_id`),
  CONSTRAINT `User_division_id_fkey` FOREIGN KEY (`division_id`) REFERENCES `Division` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: Session
-- ============================================================
CREATE TABLE IF NOT EXISTS `Session` (
  `id` VARCHAR(64) NOT NULL COMMENT 'Token session (random hex)',
  `user_id` CHAR(36) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  KEY `Session_user_id_idx` (`user_id`),
  KEY `Session_expires_at_idx` (`expires_at`),
  CONSTRAINT `Session_user_id_fkey` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: Category
-- ============================================================
CREATE TABLE IF NOT EXISTS `Category` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Category_name_key` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: Ticket
-- ============================================================
CREATE TABLE IF NOT EXISTS `Ticket` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `code` VARCHAR(50) NOT NULL COMMENT 'Format: TKT-XXXXX-XXXX',
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('OPEN', 'IN_PROGRESS', 'PENDING', 'RESOLVED', 'CLOSED') NOT NULL DEFAULT 'OPEN',
  `difficulty_level` TINYINT NOT NULL DEFAULT 1 COMMENT '1=Mudah(10poin), 2=Sedang(20poin), 3=Sulit(30poin)',
  `resolution_note` TEXT DEFAULT NULL COMMENT 'Catatan solusi dari staff',
  `pending_reason` TEXT DEFAULT NULL COMMENT 'Alasan pending',
  `category_id` CHAR(36) NOT NULL,
  `division_id` CHAR(36) DEFAULT NULL COMMENT 'Divisi penentu prioritas',
  `user_id` CHAR(36) NOT NULL COMMENT 'Pembuat tiket',
  `staff_id` CHAR(36) DEFAULT NULL COMMENT 'Staff yang menangani',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Ticket_code_key` (`code`),
  KEY `Ticket_status_idx` (`status`),
  KEY `Ticket_category_id_idx` (`category_id`),
  KEY `Ticket_user_id_idx` (`user_id`),
  KEY `Ticket_staff_id_idx` (`staff_id`),
  KEY `Ticket_division_id_idx` (`division_id`),
  KEY `Ticket_created_at_idx` (`created_at`),
  CONSTRAINT `Ticket_category_id_fkey` FOREIGN KEY (`category_id`) REFERENCES `Category` (`id`),
  CONSTRAINT `Ticket_division_id_fkey` FOREIGN KEY (`division_id`) REFERENCES `Division` (`id`),
  CONSTRAINT `Ticket_user_id_fkey` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`),
  CONSTRAINT `Ticket_staff_id_fkey` FOREIGN KEY (`staff_id`) REFERENCES `User` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: TicketAttachment (lampiran tiket - multiple)
-- ============================================================
CREATE TABLE IF NOT EXISTS `TicketAttachment` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `filename` VARCHAR(255) NOT NULL COMMENT 'Nama file asli',
  `filepath` VARCHAR(500) NOT NULL COMMENT 'Path untuk serving',
  `filetype` VARCHAR(100) NOT NULL COMMENT 'MIME type',
  `filesize` INT NOT NULL COMMENT 'Ukuran dalam bytes',
  `ticket_id` CHAR(36) NOT NULL,
  `uploaded_by` CHAR(36) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `TicketAttachment_ticket_id_idx` (`ticket_id`),
  CONSTRAINT `TicketAttachment_ticket_id_fkey` FOREIGN KEY (`ticket_id`) REFERENCES `Ticket` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: Chat
-- ============================================================
CREATE TABLE IF NOT EXISTS `Chat` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `message` TEXT NOT NULL,
  `ticket_id` CHAR(36) NOT NULL,
  `sender_id` CHAR(36) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `Chat_ticket_id_created_at_idx` (`ticket_id`, `created_at`),
  KEY `Chat_sender_id_idx` (`sender_id`),
  CONSTRAINT `Chat_ticket_id_fkey` FOREIGN KEY (`ticket_id`) REFERENCES `Ticket` (`id`),
  CONSTRAINT `Chat_sender_id_fkey` FOREIGN KEY (`sender_id`) REFERENCES `User` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: ChatAttachment (lampiran chat - multiple)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ChatAttachment` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `chat_id` CHAR(36) NOT NULL,
  `filename` VARCHAR(255) NOT NULL COMMENT 'Nama file asli',
  `filepath` VARCHAR(500) NOT NULL COMMENT 'Path untuk serving',
  `filetype` VARCHAR(100) NOT NULL COMMENT 'MIME type',
  `filesize` INT NOT NULL COMMENT 'Ukuran dalam bytes',
  `uploaded_by` CHAR(36) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ChatAttachment_chat_id_idx` (`chat_id`),
  CONSTRAINT `ChatAttachment_chat_id_fkey` FOREIGN KEY (`chat_id`) REFERENCES `Chat` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: LeaderboardLog
-- ============================================================
CREATE TABLE IF NOT EXISTS `LeaderboardLog` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `points` INT NOT NULL COMMENT 'Poin = difficulty_level x 10, bisa dicustom admin',
  `admin_note` TEXT DEFAULT NULL COMMENT 'Catatan admin saat validasi',
  `period_month` TINYINT NOT NULL COMMENT '1-12',
  `period_year` SMALLINT NOT NULL COMMENT 'Contoh: 2026',
  `staff_id` CHAR(36) NOT NULL,
  `ticket_id` CHAR(36) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `LeaderboardLog_staff_id_idx` (`staff_id`),
  KEY `LeaderboardLog_ticket_id_idx` (`ticket_id`),
  KEY `LeaderboardLog_period_idx` (`period_month`, `period_year`),
  CONSTRAINT `LeaderboardLog_staff_id_fkey` FOREIGN KEY (`staff_id`) REFERENCES `User` (`id`),
  CONSTRAINT `LeaderboardLog_ticket_id_fkey` FOREIGN KEY (`ticket_id`) REFERENCES `Ticket` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: Notification (notifikasi untuk staff/user)
-- ============================================================
CREATE TABLE IF NOT EXISTS `Notification` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `user_id` CHAR(36) NOT NULL COMMENT 'User yang menerima notifikasi',
  `ticket_id` CHAR(36) DEFAULT NULL COMMENT 'Tiket terkait',
  `type` VARCHAR(50) NOT NULL COMMENT 'Tipe: points_awarded, ticket_created, dll',
  `message` TEXT NOT NULL COMMENT 'Pesan notifikasi',
  `points` INT DEFAULT NULL COMMENT 'Poin yang didapat (jika ada)',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=belum dibaca, 1=sudah dibaca',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `Notification_user_id_idx` (`user_id`),
  KEY `Notification_is_read_idx` (`is_read`),
  CONSTRAINT `Notification_user_id_fkey` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: WA_Setting
-- ============================================================
CREATE TABLE IF NOT EXISTS `WA_Setting` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Toggle notifikasi global',
  `gateway_url` VARCHAR(255) NOT NULL DEFAULT 'http://localhost:3001' COMMENT 'URL WA Gateway',
  `api_key` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'API Key WA Gateway',
  `session_data` TEXT DEFAULT NULL,
  `connection_status` VARCHAR(50) NOT NULL DEFAULT 'disconnected',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: Notification_Template
-- ============================================================
CREATE TABLE IF NOT EXISTS `Notification_Template` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `event_type` VARCHAR(50) NOT NULL COMMENT 'Contoh: ticket_created, ticket_resolved',
  `label` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Nama event yang ramah pengguna',
  `description` TEXT DEFAULT NULL COMMENT 'Keterangan kapan notif dikirim',
  `template_body` TEXT NOT NULL COMMENT 'Template pesan WA dengan variabel {curly}',
  `variables` TEXT NOT NULL COMMENT 'Comma-separated list variabel {curly}',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Notification_Template_event_type_key` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA AWAL: Kategori
-- ============================================================
INSERT IGNORE INTO `Category` (`id`, `name`, `description`) VALUES
(UUID(), 'Account', 'Masalah terkait akun, login, hak akses'),
(UUID(), 'Hardware', 'Masalah perangkat keras (printer, PC, monitor, dll)'),
(UUID(), 'Network', 'Masalah jaringan, internet, WiFi, VPN'),
(UUID(), 'Software', 'Masalah aplikasi, sistem operasi, update'),
(UUID(), 'Other', 'Masalah lain yang tidak termasuk kategori di atas');

-- ============================================================
-- DATA AWAL: Divisi (tanpa points, hanya label prioritas)
-- ============================================================
INSERT IGNORE INTO `Division` (`id`, `name`, `priority_level`) VALUES
(UUID(), 'IT Infrastructure', 'TINGGI'),
(UUID(), 'IT Support', 'SEDANG'),
(UUID(), 'General Affairs', 'RENDAH');

-- ============================================================
-- DATA AWAL: User (password ter-hash dengan bcrypt)
-- ============================================================
-- MANAGER:  admin@helpdesk.local / admin123 (divisi: IT Infrastructure)
-- STAFF:    staff@helpdesk.local / staff123 (divisi: IT Support)
-- USER:     user@helpdesk.local  / user123 (divisi: General Affairs)

INSERT IGNORE INTO `User` (`id`, `name`, `email`, `password_hash`, `role`, `division_id`) VALUES
(UUID(), 'Super Admin',      'admin@helpdesk.local', '$2y$10$JHdqr63F3P/Ze7xRbfGwe.uPxLpNxwHLBDa6DDRRmBrV2dWuUMpFG', 'MANAGER', (SELECT id FROM `Division` WHERE name = 'IT Infrastructure' LIMIT 1)),
(UUID(), 'IT Support Staff', 'staff@helpdesk.local', '$2y$10$pF7gBrCs3svz7a17IScEpOAMG0rIpPX2pTJDLPXwozddPFZywBrYe', 'STAFF',   (SELECT id FROM `Division` WHERE name = 'IT Support' LIMIT 1)),
(UUID(), 'User Demo',        'user@helpdesk.local',  '$2y$10$/6JJHFlthFT4I8Jrii3lDuxhvucWxcMBJ6D2yclEMJLnQlzqCtUke', 'USER',    (SELECT id FROM `Division` WHERE name = 'General Affairs' LIMIT 1));

-- ============================================================
-- DATA AWAL: WA Setting
-- ============================================================
INSERT IGNORE INTO `WA_Setting` (`id`, `is_enabled`, `connection_status`, `gateway_url`, `api_key`) VALUES
(UUID(), 0, 'disconnected', 'http://localhost:3001', '');

-- ============================================================
-- DATA AWAL: Notification Templates
-- ============================================================
INSERT IGNORE INTO `Notification_Template` (`id`, `event_type`, `label`, `description`, `template_body`, `variables`) VALUES
(UUID(), 'ticket_created', 'Tiket Baru Masuk', 'Dikirim ke semua staff & manager saat user membuat tiket baru', 'Ada tiket baru yang tersedia!\n\n*Kode:* {kode_tiket}\n*Judul:* {judul_tiket}\n*User:* {nama_user}\n*Kategori:* {kategori}\n\nSilakan cek dashboard untuk menangani tiket ini.', '{kode_tiket},{judul_tiket},{nama_user},{kategori},{status}'),
(UUID(), 'ticket_assigned', 'Tiket Diklaim Staff', 'Dikirim ke user pembuat tiket saat staff mengklaim tiket', 'Halo {nama_user}, tiketmu *{kode_tiket}* _{judul_tiket}_ telah ditugaskan kepada staff kami.\n\nStaff: *{nama_staff}*\nKategori: *{kategori}*', '{kode_tiket},{judul_tiket},{nama_user},{nama_staff},{kategori}'),
(UUID(), 'ticket_pending', 'Tiket Di-Pending', 'Dikirim ke user pembuat tiket saat staff menunda tiket', 'Halo {nama_user}, tiketmu *{kode_tiket}* _{judul_tiket}_ saat ini dalam status *Pending*.\n\nDitangani oleh: *{nama_staff}*', '{kode_tiket},{judul_tiket},{nama_user},{nama_staff},{kategori}'),
(UUID(), 'ticket_resolved', 'Tiket Diselesaikan Staff', 'Dikirim ke user + semua manager saat staff menyelesaikan tiket', 'Halo {nama_user}, tiketmu *{kode_tiket}* _{judul_tiket}_ telah diselesaikan oleh staff kami.\n\nDitangani oleh: *{nama_staff}*', '{kode_tiket},{judul_tiket},{nama_user},{nama_staff},{kategori}'),
(UUID(), 'ticket_closed', 'Tiket Ditutup (User)', 'Dikirim ke user pembuat tiket saat manager menutup tiket', 'Halo {nama_user}, tiketmu *{kode_tiket}* _{judul_tiket}_ telah ditutup secara resmi.\n\nKategori: *{kategori}*\nStatus Akhir: *{status}*', '{kode_tiket},{judul_tiket},{nama_user},{kategori},{status}'),
(UUID(), 'ticket_closed_staff', 'Tiket Ditutup (Staff)', 'Dikirim ke staff penangani saat manager menutup tiket', 'Tiket yang Anda tangani telah ditutup oleh manager.\n\n*Kode:* {kode_tiket}\n*Judul:* {judul_tiket}\n*User:* {nama_user}\n\nTerima kasih atas penyelesaiannya!', '{kode_tiket},{judul_tiket},{nama_user},{nama_staff},{kategori}'),
(UUID(), 'ticket_unclaimed', 'Tiket Dilepas Staff', 'Dikirim ke user pembuat tiket saat staff melepas tiket', 'Halo {nama_user}, tiketmu *{kode_tiket}* _{judul_tiket}_ telah dilepas oleh staff dan kembali ke status *Terbuka*.', '{kode_tiket},{judul_tiket},{nama_user},{kategori}');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SELESAI!
-- Setelah import SQL ini, data sudah langsung tersedia:
--   MANAGER : admin@helpdesk.local / admin123
--   STAFF   : staff@helpdesk.local / staff123
--   USER    : user@helpdesk.local  / user123
-- ============================================================
