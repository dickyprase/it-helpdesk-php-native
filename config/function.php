<?php
// ============================================================
// function.php — Semua fungsi helper & CRUD
// Pola: mysqli procedural, global $conn, return ['status'=>bool,'message'=>'...']
// ============================================================

session_start();
require __DIR__ . "/config.php";

// ============================================================
// AUTH HELPERS
// ============================================================

function getBaseUrl() {
    global $url;
    return $url;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserName() {
    return $_SESSION['user_name'] ?? '';
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? '';
}

function getCurrentUserData() {
    global $conn;
    $id = mysqli_real_escape_string($conn, getCurrentUserId());
    $result = mysqli_query($conn, "SELECT u.*, d.name AS division_name, d.priority_level AS division_priority
                                   FROM `User` u
                                   LEFT JOIN `Division` d ON d.id = u.division_id
                                   WHERE u.id = '$id'");
    return $result ? mysqli_fetch_assoc($result) : [];
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . 'login/');
        exit;
    }
}

function requireRole() {
    $roles = func_get_args();
    requireLogin();
    if (!in_array(getCurrentUserRole(), $roles)) {
        header('Location: ' . getBaseUrl() . 'login/');
        exit;
    }
}

function checkRateLimit($key, $max = 10, $window = 900) {
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['count' => 0, 'reset' => time() + $window];
    }
    if (time() > $_SESSION['rate_limit'][$key]['reset']) {
        $_SESSION['rate_limit'][$key] = ['count' => 0, 'reset' => time() + $window];
    }
    $_SESSION['rate_limit'][$key]['count']++;
    return $_SESSION['rate_limit'][$key]['count'] <= $max;
}

// ============================================================
// LOGIN / LOGOUT
// ============================================================

function login($email, $password) {
    global $conn;

    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT * FROM `User` WHERE email = '$email' AND is_active = 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role']       = $user['role'];
            return ['status' => true, 'message' => 'Login berhasil'];
        }
        return ['status' => false, 'message' => 'Password salah'];
    }
    return ['status' => false, 'message' => 'Email tidak ditemukan atau akun nonaktif'];
}

function register($name, $email, $phone, $password) {
    global $conn;

    $name     = mysqli_real_escape_string($conn, $name);
    $email    = mysqli_real_escape_string($conn, strtolower($email));
    $phone    = mysqli_real_escape_string($conn, $phone);
    $hash     = password_hash($password, PASSWORD_BCRYPT);

    $check = mysqli_query($conn, "SELECT id FROM `User` WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        return ['status' => false, 'message' => 'Email sudah terdaftar'];
    }

    $query = "INSERT INTO `User` (id, name, email, phone, password_hash, role, is_active, created_at, updated_at)
              VALUES (UUID(), '$name', '$email', '$phone', '$hash', 'USER', 1, NOW(), NOW())";
    
    if (mysqli_query($conn, $query)) {
        return ['status' => true, 'message' => 'Registrasi berhasil'];
    }
    return ['status' => false, 'message' => 'Gagal registrasi: ' . mysqli_error($conn)];
}

function logout() {
    session_unset();
    session_destroy();
}

// ============================================================
// CATEGORIES
// ============================================================

function getCategories() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM `Category` ORDER BY name ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function getCategoryById($id) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);
    $result = mysqli_query($conn, "SELECT * FROM `Category` WHERE id = '$id'");
    return $result ? mysqli_fetch_assoc($result) : null;
}

function createCategory($name, $description) {
    global $conn;
    $name = mysqli_real_escape_string($conn, $name);
    $description = mysqli_real_escape_string($conn, $description);

    $check = mysqli_query($conn, "SELECT id FROM `Category` WHERE name = '$name'");
    if (mysqli_num_rows($check) > 0) {
        return ['status' => false, 'message' => 'Kategori sudah ada'];
    }

    $query = "INSERT INTO `Category` (id, name, description) VALUES (UUID(), '$name', '$description')";
    if (mysqli_query($conn, $query)) {
        return ['status' => true, 'message' => 'Kategori berhasil dibuat'];
    }
    return ['status' => false, 'message' => 'Gagal membuat kategori: ' . mysqli_error($conn)];
}

function updateCategory($id, $name, $description) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);
    $name = mysqli_real_escape_string($conn, $name);
    $description = mysqli_real_escape_string($conn, $description);

    $check = mysqli_query($conn, "SELECT id FROM `Category` WHERE name = '$name' AND id != '$id'");
    if (mysqli_num_rows($check) > 0) {
        return ['status' => false, 'message' => 'Nama kategori sudah digunakan'];
    }

    mysqli_query($conn, "UPDATE `Category` SET name='$name', description='$description' WHERE id='$id'");
    return ['status' => true, 'message' => 'Kategori berhasil diperbarui'];
}

function deleteCategory($id) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);

    $check = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM `Ticket` WHERE category_id = '$id'");
    $row = mysqli_fetch_assoc($check);
    if ((int)$row['cnt'] > 0) {
        return ['status' => false, 'message' => 'Kategori masih digunakan oleh ' . $row['cnt'] . ' tiket'];
    }

    mysqli_query($conn, "DELETE FROM `Category` WHERE id = '$id'");
    return ['status' => true, 'message' => 'Kategori berhasil dihapus'];
}

// ============================================================
// DIVISIONS (tanpa points, hanya label prioritas)
// ============================================================

function getDivisions() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM `Division` ORDER BY 
        CASE priority_level WHEN 'TINGGI' THEN 1 WHEN 'SEDANG' THEN 2 WHEN 'RENDAH' THEN 3 END ASC,
        name ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function getDivisionById($id) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);
    $result = mysqli_query($conn, "SELECT * FROM `Division` WHERE id = '$id'");
    return $result ? mysqli_fetch_assoc($result) : null;
}

function createDivision($name, $priority_level) {
    global $conn;
    $name = mysqli_real_escape_string($conn, $name);
    $priority_level = mysqli_real_escape_string($conn, $priority_level);

    $check = mysqli_query($conn, "SELECT id FROM `Division` WHERE name = '$name'");
    if (mysqli_num_rows($check) > 0) {
        return ['status' => false, 'message' => 'Divisi sudah ada'];
    }

    $query = "INSERT INTO `Division` (id, name, priority_level) VALUES (UUID(), '$name', '$priority_level')";
    if (mysqli_query($conn, $query)) {
        return ['status' => true, 'message' => 'Divisi berhasil dibuat'];
    }
    return ['status' => false, 'message' => 'Gagal membuat divisi: ' . mysqli_error($conn)];
}

function updateDivision($id, $name, $priority_level) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);
    $name = mysqli_real_escape_string($conn, $name);
    $priority_level = mysqli_real_escape_string($conn, $priority_level);

    $check = mysqli_query($conn, "SELECT id FROM `Division` WHERE name = '$name' AND id != '$id'");
    if (mysqli_num_rows($check) > 0) {
        return ['status' => false, 'message' => 'Nama divisi sudah digunakan'];
    }

    mysqli_query($conn, "UPDATE `Division` SET name='$name', priority_level='$priority_level', updated_at=NOW() WHERE id='$id'");
    return ['status' => true, 'message' => 'Divisi berhasil diperbarui'];
}

function deleteDivision($id) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);

    $check = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM `Ticket` WHERE division_id = '$id'");
    $row = mysqli_fetch_assoc($check);
    if ((int)$row['cnt'] > 0) {
        return ['status' => false, 'message' => 'Divisi masih digunakan oleh ' . $row['cnt'] . ' tiket'];
    }

    mysqli_query($conn, "DELETE FROM `Division` WHERE id = '$id'");
    return ['status' => true, 'message' => 'Divisi berhasil dihapus'];
}

// ============================================================
// TICKETS
// ============================================================

function generateTicketCode() {
    $ts = strtoupper(base_convert(time(), 10, 36));
    $rand = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    return "TKT-{$ts}-{$rand}";
}

function createTicket($title, $description, $category_id, $division_id = null) {
    global $conn;

    $user_id = getCurrentUserId();
    if (empty($user_id)) {
        return ['status' => false, 'message' => 'Sesi tidak valid. Silakan login kembali.'];
    }

    // Validasi user_id ada di database
    $check_user = mysqli_query($conn, "SELECT id FROM `User` WHERE id = '" . mysqli_real_escape_string($conn, $user_id) . "' AND is_active = 1");
    if (!$check_user || mysqli_num_rows($check_user) === 0) {
        session_destroy();
        return ['status' => false, 'message' => 'User tidak ditemukan. Silakan login kembali.'];
    }

    $title       = mysqli_real_escape_string($conn, $title);
    $description = mysqli_real_escape_string($conn, $description);
    $category_id = mysqli_real_escape_string($conn, $category_id);
    $code        = generateTicketCode();

    if (!$division_id) {
        $user_data = getCurrentUserData();
        $division_id = $user_data['division_id'] ?? null;
    }
    $division = $division_id ? "'" . mysqli_real_escape_string($conn, $division_id) . "'" : 'NULL';

    $query = "INSERT INTO `Ticket` (id, code, title, description, status, difficulty_level, category_id, division_id, user_id, created_at, updated_at)
              VALUES (UUID(), '$code', '$title', '$description', 'OPEN', 1, '$category_id', $division, '$user_id', NOW(), NOW())";

    if (mysqli_query($conn, $query)) {
        $ticket_id = mysqli_insert_id($conn);
        if (!$ticket_id) {
            $r = mysqli_query($conn, "SELECT id FROM `Ticket` WHERE code = '$code'");
            $ticket_id = mysqli_fetch_assoc($r)['id'];
        }

        $user_name = getCurrentUserName();
        $staff_users = mysqli_query($conn, "SELECT id FROM `User` WHERE role IN ('STAFF','MANAGER') AND is_active = 1");
        while ($staff = mysqli_fetch_assoc($staff_users)) {
            createNotification($staff['id'], $ticket_id, 'ticket_created', "Tiket baru {$code} dari {$user_name}: {$title}");
        }

        // WA Notification
        $cat_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM `Category` WHERE id = '$category_id'"));
        $kategori_nama = $cat_row['name'] ?? '-';
        $wa_phones = getPhonesByRole(['STAFF', 'MANAGER']);
        $wa_msg = renderWaTemplate('ticket_created', [
            'kode_tiket' => $code,
            'judul_tiket' => $title,
            'nama_user' => $user_name,
            'kategori' => $kategori_nama,
            'status' => 'OPEN'
        ]);
        if ($wa_msg) sendWaNotification($wa_phones, $wa_msg);

        return ['status' => true, 'message' => 'Tiket berhasil dibuat! Kode: ' . $code, 'ticket_id' => $ticket_id];
    }
    return ['status' => false, 'message' => 'Gagal membuat tiket: ' . mysqli_error($conn)];
}

function getTickets($where_clause = "1=1", $order = "t.created_at DESC") {
    global $conn;

    $query = "SELECT t.*, 
                     c.name AS category_name,
                     d.name AS division_name, d.priority_level AS division_priority,
                     u.name AS user_name, u.email AS user_email,
                     s.name AS staff_name, s.email AS staff_email
              FROM `Ticket` t
              JOIN `Category` c ON c.id = t.category_id
              LEFT JOIN `Division` d ON d.id = t.division_id
              JOIN `User` u ON u.id = t.user_id
              LEFT JOIN `User` s ON s.id = t.staff_id
              WHERE $where_clause
              ORDER BY $order";

    $result = mysqli_query($conn, $query);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

function getTicketById($id) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);

    $query = "SELECT t.*, 
                     c.name AS category_name,
                     d.name AS division_name, d.priority_level AS division_priority,
                     u.name AS user_name, u.email AS user_email,
                     s.name AS staff_name, s.email AS staff_email
              FROM `Ticket` t
              JOIN `Category` c ON c.id = t.category_id
              LEFT JOIN `Division` d ON d.id = t.division_id
              JOIN `User` u ON u.id = t.user_id
              LEFT JOIN `User` s ON s.id = t.staff_id
              WHERE t.id = '$id'";

    $result = mysqli_query($conn, $query);
    return $result ? mysqli_fetch_assoc($result) : null;
}

function claimTicket($ticket_id) {
    global $conn;

    $ticket_id = mysqli_real_escape_string($conn, $ticket_id);
    $staff_id  = getCurrentUserId();

    mysqli_begin_transaction($conn);

    $q = "SELECT id, code, status, staff_id, user_id FROM `Ticket` WHERE id = '$ticket_id' FOR UPDATE";
    $r = mysqli_query($conn, $q);
    $ticket = mysqli_fetch_assoc($r);

    if (!$ticket) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => 'Tiket tidak ditemukan'];
    }
    if ($ticket['staff_id']) {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => 'Tiket sudah diklaim oleh staff lain'];
    }
    if ($ticket['status'] !== 'OPEN') {
        mysqli_rollback($conn);
        return ['status' => false, 'message' => 'Hanya tiket OPEN yang dapat diklaim'];
    }

    $staff_id = mysqli_real_escape_string($conn, $staff_id);
    mysqli_query($conn, "UPDATE `Ticket` SET staff_id = '$staff_id', status = 'IN_PROGRESS', updated_at = NOW() WHERE id = '$ticket_id'");
    mysqli_commit($conn);

    $staff_name = getCurrentUserName();
    createNotification($ticket['user_id'], $ticket_id, 'ticket_claimed', "Tiket {$ticket['code']} telah diambil oleh {$staff_name}.");

    // WA Notification
    $full_ticket = getTicketById($ticket_id);
    $user_phone = mysqli_fetch_assoc(mysqli_query($conn, "SELECT phone FROM `User` WHERE id = '{$ticket['user_id']}'"));
    $cat_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM `Category` WHERE id = '{$full_ticket['category_id']}'"));
    $wa_msg = renderWaTemplate('ticket_assigned', [
        'kode_tiket' => $ticket['code'],
        'judul_tiket' => $full_ticket['title'] ?? '',
        'nama_user' => $full_ticket['user_name'] ?? '',
        'nama_staff' => $staff_name,
        'kategori' => $cat_row['name'] ?? '-'
    ]);
    if ($wa_msg && !empty($user_phone['phone'])) sendWaNotification($user_phone['phone'], $wa_msg);

    return ['status' => true, 'message' => 'Tiket berhasil diklaim'];
}

function unclaimTicket($ticket_id, $reason) {
    global $conn;

    $ticket_id = mysqli_real_escape_string($conn, $ticket_id);
    $staff_id  = getCurrentUserId();

    $ticket = getTicketById($ticket_id);
    if (!$ticket) return ['status' => false, 'message' => 'Tiket tidak ditemukan'];
    if ($ticket['staff_id'] !== $staff_id) return ['status' => false, 'message' => 'Anda bukan staff yang ditugaskan'];
    if ($ticket['status'] !== 'IN_PROGRESS') return ['status' => false, 'message' => 'Hanya tiket Diproses yang dapat dilepas'];

    mysqli_query($conn, "UPDATE `Ticket` SET staff_id = NULL, status = 'OPEN', updated_at = NOW() WHERE id = '$ticket_id'");

    // Notification
    createNotification($ticket['user_id'], $ticket_id, 'ticket_unclaimed', "Tiket {$ticket['code']} telah dilepas oleh staff.");

    // WA Notification
    $user_phone = mysqli_fetch_assoc(mysqli_query($conn, "SELECT phone FROM `User` WHERE id = '{$ticket['user_id']}'"));
    $wa_msg = renderWaTemplate('ticket_unclaimed', [
        'kode_tiket' => $ticket['code'],
        'judul_tiket' => $ticket['title'],
        'nama_user' => $ticket['user_name'] ?? '',
        'kategori' => $ticket['category_name'] ?? '-'
    ]);
    if ($wa_msg && !empty($user_phone['phone'])) sendWaNotification($user_phone['phone'], $wa_msg);

    return ['status' => true, 'message' => 'Tiket berhasil dilepas'];
}

function setTicketPending($ticket_id, $reason) {
    global $conn;

    $ticket_id = mysqli_real_escape_string($conn, $ticket_id);
    $reason    = mysqli_real_escape_string($conn, $reason);
    $staff_id  = getCurrentUserId();

    $ticket = getTicketById($ticket_id);
    if (!$ticket) return ['status' => false, 'message' => 'Tiket tidak ditemukan'];
    if ($ticket['staff_id'] !== $staff_id) return ['status' => false, 'message' => 'Anda bukan staff yang ditugaskan'];
    if ($ticket['status'] !== 'IN_PROGRESS') return ['status' => false, 'message' => 'Hanya tiket Diproses yang dapat di-pending'];

    mysqli_query($conn, "UPDATE `Ticket` SET status = 'PENDING', pending_reason = '$reason', updated_at = NOW() WHERE id = '$ticket_id'");

    // Notification
    $staff_name = getCurrentUserName();
    createNotification($ticket['user_id'], $ticket_id, 'ticket_pending', "Tiket {$ticket['code']} di-pending oleh {$staff_name}.");

    // WA Notification
    $user_phone = mysqli_fetch_assoc(mysqli_query($conn, "SELECT phone FROM `User` WHERE id = '{$ticket['user_id']}'"));
    $wa_msg = renderWaTemplate('ticket_pending', [
        'kode_tiket' => $ticket['code'],
        'judul_tiket' => $ticket['title'],
        'nama_user' => $ticket['user_name'] ?? '',
        'nama_staff' => $staff_name,
        'kategori' => $ticket['category_name'] ?? '-'
    ]);
    if ($wa_msg && !empty($user_phone['phone'])) sendWaNotification($user_phone['phone'], $wa_msg);

    return ['status' => true, 'message' => 'Tiket berhasil di-pending'];
}

function resolveTicket($ticket_id, $note) {
    global $conn;

    $ticket_id = mysqli_real_escape_string($conn, $ticket_id);
    $note      = mysqli_real_escape_string($conn, $note);
    $staff_id  = getCurrentUserId();

    $ticket = getTicketById($ticket_id);
    if (!$ticket) return ['status' => false, 'message' => 'Tiket tidak ditemukan'];
    if ($ticket['staff_id'] !== $staff_id) return ['status' => false, 'message' => 'Anda bukan staff yang ditugaskan'];
    if ($ticket['status'] !== 'IN_PROGRESS') return ['status' => false, 'message' => 'Hanya tiket Diproses yang dapat diselesaikan'];

    mysqli_query($conn, "UPDATE `Ticket` SET status = 'RESOLVED', resolution_note = '$note', updated_at = NOW() WHERE id = '$ticket_id'");

    createNotification($ticket['user_id'], $ticket_id, 'ticket_resolved', "Tiket {$ticket['code']} telah diselesaikan oleh staff. Menunggu validasi manager.");

    $managers = mysqli_query($conn, "SELECT id FROM `User` WHERE role = 'MANAGER' AND is_active = 1");
    while ($mgr = mysqli_fetch_assoc($managers)) {
        createNotification($mgr['id'], $ticket_id, 'ticket_resolved_manager', "Tiket {$ticket['code']} telah diselesaikan oleh staff dan menunggu validasi Anda.");
    }

    // WA Notification
    $staff_name = getCurrentUserName();
    $user_phone = mysqli_fetch_assoc(mysqli_query($conn, "SELECT phone FROM `User` WHERE id = '{$ticket['user_id']}'"));
    $cat_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM `Category` WHERE id = '{$ticket['category_id']}'"));
    $wa_msg = renderWaTemplate('ticket_resolved', [
        'kode_tiket' => $ticket['code'],
        'judul_tiket' => $ticket['title'],
        'nama_user' => $ticket['user_name'] ?? '',
        'nama_staff' => $staff_name,
        'kategori' => $cat_row['name'] ?? '-'
    ]);
    if ($wa_msg) {
        // Send to user
        if (!empty($user_phone['phone'])) sendWaNotification($user_phone['phone'], $wa_msg);
        // Send to all managers
        $manager_phones = getPhonesByRole(['MANAGER']);
        if (!empty($manager_phones)) sendWaNotification($manager_phones, $wa_msg);
    }

    return ['status' => true, 'message' => 'Tiket berhasil diselesaikan'];
}

function setTicketDifficulty($ticket_id, $level) {
    global $conn;
    $ticket_id = mysqli_real_escape_string($conn, $ticket_id);
    $level = (int)$level;
    if ($level < 1 || $level > 3) return ['status' => false, 'message' => 'Level harus 1-3'];

    mysqli_query($conn, "UPDATE `Ticket` SET difficulty_level = $level, updated_at = NOW() WHERE id = '$ticket_id'");
    return ['status' => true, 'message' => 'Difficulty berhasil diatur'];
}

function updateTicketStatus($ticket_id, $new_status, $custom_points = null, $admin_note = null) {
    global $conn;
    $ticket_id  = mysqli_real_escape_string($conn, $ticket_id);
    $new_status = mysqli_real_escape_string($conn, $new_status);

    $allowed = [
        'OPEN'        => ['IN_PROGRESS', 'CLOSED'],
        'IN_PROGRESS' => ['PENDING', 'RESOLVED', 'OPEN'],
        'PENDING'     => ['IN_PROGRESS'],
        'RESOLVED'    => ['CLOSED', 'IN_PROGRESS'],
        'CLOSED'      => [],
    ];

    $ticket = getTicketById($ticket_id);
    if (!$ticket) return ['status' => false, 'message' => 'Tiket tidak ditemukan'];

    $current = $ticket['status'];
    if (!in_array($new_status, $allowed[$current] ?? [])) {
        return ['status' => false, 'message' => "Tidak dapat mengubah status dari $current ke $new_status"];
    }

    mysqli_query($conn, "UPDATE `Ticket` SET status = '$new_status', updated_at = NOW() WHERE id = '$ticket_id'");

    if ($new_status === 'CLOSED' && $ticket['staff_id']) {
        $staff_id = mysqli_real_escape_string($conn, $ticket['staff_id']);

        if ($custom_points !== null) {
            $points = (int)$custom_points;
        } else {
            $points = 10 * (int)($ticket['difficulty_level'] ?? 1);
        }

        $month = date('n');
        $year = date('Y');
        $note_sql = $admin_note ? "'" . mysqli_real_escape_string($conn, $admin_note) . "'" : 'NULL';

        $check = mysqli_query($conn, "SELECT id FROM `LeaderboardLog` WHERE ticket_id = '$ticket_id'");
        if (mysqli_num_rows($check) === 0) {
            mysqli_query($conn, "INSERT INTO `LeaderboardLog` (id, staff_id, ticket_id, points, admin_note, period_month, period_year, created_at)
                                 VALUES (UUID(), '$staff_id', '$ticket_id', $points, $note_sql, $month, $year, NOW())");

            createNotification($staff_id, $ticket_id, 'points_awarded', "Anda mendapat {$points} poin dari tiket {$ticket['code']}.", $points);
        }

        createNotification($ticket['user_id'], $ticket_id, 'ticket_closed', "Tiket {$ticket['code']} telah ditutup. Terima kasih.");

        // WA Notification - ke user
        $user_phone = mysqli_fetch_assoc(mysqli_query($conn, "SELECT phone FROM `User` WHERE id = '{$ticket['user_id']}'"));
        $cat_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM `Category` WHERE id = '{$ticket['category_id']}'"));
        $wa_msg = renderWaTemplate('ticket_closed', [
            'kode_tiket' => $ticket['code'],
            'judul_tiket' => $ticket['title'],
            'nama_user' => $ticket['user_name'] ?? '',
            'kategori' => $cat_row['name'] ?? '-',
            'status' => 'CLOSED'
        ]);
        if ($wa_msg && !empty($user_phone['phone'])) sendWaNotification($user_phone['phone'], $wa_msg);

        // WA Notification - ke staff penangani
        $staff_phone = mysqli_fetch_assoc(mysqli_query($conn, "SELECT phone, name FROM `User` WHERE id = '$staff_id'"));
        $wa_msg_staff = renderWaTemplate('ticket_closed_staff', [
            'kode_tiket' => $ticket['code'],
            'judul_tiket' => $ticket['title'],
            'nama_user' => $ticket['user_name'] ?? '',
            'nama_staff' => $staff_phone['name'] ?? '',
            'kategori' => $cat_row['name'] ?? '-'
        ]);
        if ($wa_msg_staff && !empty($staff_phone['phone'])) sendWaNotification($staff_phone['phone'], $wa_msg_staff);
    }

    return ['status' => true, 'message' => 'Status berhasil diubah'];
}

function assignTicket($ticket_id, $staff_id) {
    global $conn;
    $ticket_id = mysqli_real_escape_string($conn, $ticket_id);
    $staff_id  = mysqli_real_escape_string($conn, $staff_id);

    mysqli_query($conn, "UPDATE `Ticket` SET staff_id = '$staff_id', 
                         status = CASE WHEN status = 'OPEN' THEN 'IN_PROGRESS' ELSE status END,
                         updated_at = NOW() WHERE id = '$ticket_id'");
    return ['status' => true, 'message' => 'Staff berhasil ditugaskan'];
}

function getStaffList() {
    global $conn;
    $result = mysqli_query($conn, "SELECT id, name, email, role FROM `User` WHERE role IN ('STAFF','MANAGER') AND is_active = 1 ORDER BY name");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// ============================================================
// TICKET ATTACHMENTS (multiple files)
// ============================================================

function uploadTicketAttachments($files, $ticket_id) {
    global $conn;
    $upload_dir = __DIR__ . '/../uploads/tickets/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $max_total = 75 * 1024 * 1024;
    $total_size = 0;
    $uploaded = [];

    $file_count = count($files['name']);
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        $total_size += $files['size'][$i];
    }

    if ($total_size > $max_total) {
        return ['status' => false, 'message' => 'Total ukuran file maksimal 75MB'];
    }

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg','jpeg','png','gif','bmp','webp','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','zip','rar','7z'];
        $allowed_mime = ['image/jpeg','image/png','image/gif','image/bmp','image/webp','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation','text/plain','text/csv','application/zip','application/x-rar-compressed','application/x-7z-compressed','application/octet-stream'];

        if (!in_array($ext, $allowed_ext)) {
            return ['status' => false, 'message' => 'Format file .'.$ext.' tidak diizinkan. Format: ' . implode(', ', $allowed_ext)];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $files['tmp_name'][$i]);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_mime)) {
            return ['status' => false, 'message' => 'Tipe MIME tidak diizinkan: ' . $mime];
        }
        $filename = 'ticket_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
            $fname = mysqli_real_escape_string($conn, $files['name'][$i]);
            $fpath = mysqli_real_escape_string($conn, 'uploads/tickets/' . $filename);
            $ftype = mysqli_real_escape_string($conn, $files['type'][$i]);
            $fsize = (int)$files['size'][$i];
            $tid   = mysqli_real_escape_string($conn, $ticket_id);
            $uid   = mysqli_real_escape_string($conn, getCurrentUserId());

            mysqli_query($conn, "INSERT INTO `TicketAttachment` (id, filename, filepath, filetype, filesize, ticket_id, uploaded_by)
                                 VALUES (UUID(), '$fname', '$fpath', '$ftype', $fsize, '$tid', '$uid')");
            $uploaded[] = $fname;
        }
    }

    return ['status' => true, 'message' => count($uploaded) . ' file berhasil diupload', 'files' => $uploaded];
}

function getTicketAttachments($ticket_id) {
    global $conn;
    $ticket_id = mysqli_real_escape_string($conn, $ticket_id);
    $result = mysqli_query($conn, "SELECT * FROM `TicketAttachment` WHERE ticket_id = '$ticket_id' ORDER BY created_at ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// ============================================================
// CHAT
// ============================================================

function getChatMessages($ticket_id) {
    global $conn;
    $ticket_id = mysqli_real_escape_string($conn, $ticket_id);

    $query = "SELECT ch.*, u.name AS sender_name, u.role AS sender_role
              FROM `Chat` ch
              JOIN `User` u ON u.id = ch.sender_id
              WHERE ch.ticket_id = '$ticket_id'
              ORDER BY ch.created_at ASC";

    $result = mysqli_query($conn, $query);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['attachments'] = getChatAttachments($row['id']);
            $data[] = $row;
        }
    }
    return $data;
}

function sendMessage($ticket_id, $message) {
    global $conn;

    $ticket_id = mysqli_real_escape_string($conn, $ticket_id);
    $sender_id = mysqli_real_escape_string($conn, getCurrentUserId());
    $message   = mysqli_real_escape_string($conn, $message);

    $query = "INSERT INTO `Chat` (id, message, ticket_id, sender_id, created_at)
              VALUES (UUID(), '$message', '$ticket_id', '$sender_id', NOW())";

    if (mysqli_query($conn, $query)) {
        $chat_id = mysqli_insert_id($conn);
        if (!$chat_id) {
            $r = mysqli_query($conn, "SELECT LAST_INSERT_ID() AS id");
            $chat_id = mysqli_fetch_assoc($r)['id'];
        }

        $ticket = getTicketById($ticket_id);
        if ($ticket) {
            $sender_role = getCurrentUserRole();
            $sender_name = getCurrentUserName();
            if ($sender_role === 'USER' && $ticket['staff_id']) {
                createNotification($ticket['staff_id'], $ticket_id, 'chat_message', "Pesan baru dari {$sender_name} di tiket {$ticket['code']}.");
            } elseif ($sender_role !== 'USER') {
                createNotification($ticket['user_id'], $ticket_id, 'chat_message', "Pesan baru dari {$sender_name} di tiket {$ticket['code']}.");
            }
        }

        return ['status' => true, 'message' => 'Pesan terkirim', 'chat_id' => $chat_id];
    }
    return ['status' => false, 'message' => 'Gagal mengirim pesan: ' . mysqli_error($conn)];
}

function uploadChatAttachments($files, $chat_id) {
    global $conn;
    $upload_dir = __DIR__ . '/../uploads/chat/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $max_total = 75 * 1024 * 1024;
    $total_size = 0;
    $uploaded = [];

    $file_count = count($files['name']);
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        $total_size += $files['size'][$i];
    }

    if ($total_size > $max_total) {
        return ['status' => false, 'message' => 'Total ukuran file maksimal 75MB'];
    }

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg','jpeg','png','gif','bmp','webp','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','zip','rar','7z'];
        $allowed_mime = ['image/jpeg','image/png','image/gif','image/bmp','image/webp','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation','text/plain','text/csv','application/zip','application/x-rar-compressed','application/x-7z-compressed','application/octet-stream'];

        if (!in_array($ext, $allowed_ext)) {
            return ['status' => false, 'message' => 'Format file .'.$ext.' tidak diizinkan. Format: ' . implode(', ', $allowed_ext)];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $files['tmp_name'][$i]);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_mime)) {
            return ['status' => false, 'message' => 'Tipe MIME tidak diizinkan: ' . $mime];
        }

        $filename = 'chat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
            $fname = mysqli_real_escape_string($conn, $files['name'][$i]);
            $fpath = mysqli_real_escape_string($conn, 'uploads/chat/' . $filename);
            $ftype = mysqli_real_escape_string($conn, $files['type'][$i]);
            $fsize = (int)$files['size'][$i];
            $cid   = mysqli_real_escape_string($conn, $chat_id);
            $uid   = mysqli_real_escape_string($conn, getCurrentUserId());

            mysqli_query($conn, "INSERT INTO `ChatAttachment` (id, chat_id, filename, filepath, filetype, filesize, uploaded_by)
                                 VALUES (UUID(), '$cid', '$fname', '$fpath', '$ftype', $fsize, '$uid')");
            $uploaded[] = $fname;
        }
    }

    return ['status' => true, 'message' => count($uploaded) . ' file berhasil diupload', 'files' => $uploaded];
}

function getChatAttachments($chat_id) {
    global $conn;
    $chat_id = mysqli_real_escape_string($conn, $chat_id);
    $result = mysqli_query($conn, "SELECT * FROM `ChatAttachment` WHERE chat_id = '$chat_id' ORDER BY created_at ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// ============================================================
// USERS (Manager)
// ============================================================

function getUsers() {
    global $conn;
    $query = "SELECT u.*, 
                     d.name AS division_name, d.priority_level AS division_priority,
                     (SELECT COUNT(*) FROM `Ticket` WHERE user_id = u.id) AS tickets_created,
                     (SELECT COUNT(*) FROM `Ticket` WHERE staff_id = u.id) AS tickets_handled
              FROM `User` u
              LEFT JOIN `Division` d ON d.id = u.division_id
              ORDER BY u.role ASC, u.name ASC";
    $result = mysqli_query($conn, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function createUser($name, $email, $phone, $password, $role, $division_id = null) {
    global $conn;

    $name  = mysqli_real_escape_string($conn, $name);
    $email = mysqli_real_escape_string($conn, strtolower($email));
    $phone = mysqli_real_escape_string($conn, $phone);
    $role  = mysqli_real_escape_string($conn, $role);
    $hash  = password_hash($password, PASSWORD_BCRYPT);
    $division = $division_id ? "'" . mysqli_real_escape_string($conn, $division_id) . "'" : 'NULL';

    $check = mysqli_query($conn, "SELECT id FROM `User` WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        return ['status' => false, 'message' => 'Email sudah terdaftar'];
    }

    $query = "INSERT INTO `User` (id, name, email, phone, password_hash, role, division_id, is_active, created_at, updated_at)
              VALUES (UUID(), '$name', '$email', '$phone', '$hash', '$role', $division, 1, NOW(), NOW())";

    if (mysqli_query($conn, $query)) {
        return ['status' => true, 'message' => 'Akun berhasil dibuat'];
    }
    return ['status' => false, 'message' => 'Gagal membuat akun: ' . mysqli_error($conn)];
}

function updateUser($id, $name, $email, $phone, $role, $division_id = null) {
    global $conn;

    $id    = mysqli_real_escape_string($conn, $id);
    $name  = mysqli_real_escape_string($conn, $name);
    $email = mysqli_real_escape_string($conn, strtolower($email));
    $phone = mysqli_real_escape_string($conn, $phone);
    $role  = mysqli_real_escape_string($conn, $role);
    $division = $division_id ? "'" . mysqli_real_escape_string($conn, $division_id) . "'" : 'NULL';

    $check = mysqli_query($conn, "SELECT id FROM `User` WHERE email = '$email' AND id != '$id'");
    if (mysqli_num_rows($check) > 0) {
        return ['status' => false, 'message' => 'Email sudah digunakan akun lain'];
    }

    mysqli_query($conn, "UPDATE `User` SET name='$name', email='$email', phone='$phone', role='$role', division_id=$division, updated_at=NOW() WHERE id='$id'");
    return ['status' => true, 'message' => 'Akun berhasil diperbarui'];
}

function toggleUserActive($id) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);

    mysqli_query($conn, "UPDATE `User` SET is_active = NOT is_active, updated_at = NOW() WHERE id = '$id'");

    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM `User` WHERE id = '$id'"));
    if ($user && !$user['is_active']) {
        mysqli_query($conn, "DELETE FROM `Session` WHERE user_id = '$id'");
    }

    return ['status' => true, 'message' => 'Status akun berhasil diubah'];
}

// ============================================================
// PROFILE
// ============================================================

function getProfile() {
    global $conn;
    $id = mysqli_real_escape_string($conn, getCurrentUserId());
    $result = mysqli_query($conn, "SELECT id, name, email, phone, role, is_active, created_at FROM `User` WHERE id = '$id'");
    return mysqli_fetch_assoc($result);
}

function updateProfile($name, $email, $phone) {
    global $conn;

    $id    = mysqli_real_escape_string($conn, getCurrentUserId());
    $name  = mysqli_real_escape_string($conn, $name);
    $email = mysqli_real_escape_string($conn, strtolower($email));
    $phone = mysqli_real_escape_string($conn, $phone);

    $check = mysqli_query($conn, "SELECT id FROM `User` WHERE email = '$email' AND id != '$id'");
    if (mysqli_num_rows($check) > 0) {
        return ['status' => false, 'message' => 'Email sudah digunakan akun lain'];
    }

    mysqli_query($conn, "UPDATE `User` SET name='$name', email='$email', phone='$phone', updated_at=NOW() WHERE id='$id'");
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    return ['status' => true, 'message' => 'Profil berhasil diperbarui'];
}

function changePassword($current_password, $new_password) {
    global $conn;

    $id = mysqli_real_escape_string($conn, getCurrentUserId());
    $result = mysqli_query($conn, "SELECT password_hash FROM `User` WHERE id = '$id'");
    $user = mysqli_fetch_assoc($result);

    if (!$user || !password_verify($current_password, $user['password_hash'])) {
        return ['status' => false, 'message' => 'Password saat ini salah'];
    }

    $hash = password_hash($new_password, PASSWORD_BCRYPT);
    mysqli_query($conn, "UPDATE `User` SET password_hash = '$hash', updated_at = NOW() WHERE id = '$id'");
    return ['status' => true, 'message' => 'Password berhasil diubah'];
}

// ============================================================
// NOTIFICATIONS
// ============================================================

function createNotification($user_id, $ticket_id, $type, $message, $points = null) {
    global $conn;
    if (empty($user_id)) return false;

    $user_id   = mysqli_real_escape_string($conn, $user_id);
    $ticket_id = $ticket_id ? "'" . mysqli_real_escape_string($conn, $ticket_id) . "'" : 'NULL';
    $type      = mysqli_real_escape_string($conn, $type);
    $message   = mysqli_real_escape_string($conn, $message);
    $points    = $points !== null ? (int)$points : 'NULL';

    $query = "INSERT INTO `Notification` (id, user_id, ticket_id, type, message, points, is_read, created_at)
              VALUES (UUID(), '$user_id', $ticket_id, '$type', '$message', $points, 0, NOW())";
    return mysqli_query($conn, $query);
}

function getNotifications($user_id, $limit = 20) {
    global $conn;
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $limit = (int)$limit;

    $result = mysqli_query($conn, "SELECT * FROM `Notification` WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT $limit");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function getUnreadNotificationCount($user_id) {
    global $conn;
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $result = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM `Notification` WHERE user_id = '$user_id' AND is_read = 0");
    $row = mysqli_fetch_assoc($result);
    return (int)$row['cnt'];
}

function markNotificationRead($id) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);
    mysqli_query($conn, "UPDATE `Notification` SET is_read = 1 WHERE id = '$id'");
    return ['status' => true, 'message' => 'Notifikasi ditandai dibaca'];
}

function markAllNotificationsRead($user_id) {
    global $conn;
    $user_id = mysqli_real_escape_string($conn, $user_id);
    mysqli_query($conn, "UPDATE `Notification` SET is_read = 1 WHERE user_id = '$user_id' AND is_read = 0");
    return ['status' => true, 'message' => 'Semua notifikasi ditandai dibaca'];
}

// ============================================================
// WHATSAPP NOTIFICATION
// ============================================================

function getWaSetting() {
    static $wa_cache = null;
    if ($wa_cache !== null) return $wa_cache;

    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM `WA_Setting` LIMIT 1");
    $wa_cache = $result ? mysqli_fetch_assoc($result) : null;
    return $wa_cache;
}

function renderWaTemplate($event_type, $vars = []) {
    global $conn;
    $event_type = mysqli_real_escape_string($conn, $event_type);
    $result = mysqli_query($conn, "SELECT template_body FROM `Notification_Template` WHERE event_type = '$event_type' LIMIT 1");
    if (!$result || mysqli_num_rows($result) === 0) return null;

    $row = mysqli_fetch_assoc($result);
    $template = $row['template_body'];

    foreach ($vars as $key => $value) {
        $template = str_replace('{' . $key . '}', $value, $template);
    }
    return $template;
}

function getPhonesByRole($roles = []) {
    global $conn;
    if (empty($roles)) return [];

    $escaped = array_map(function($r) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $r) . "'";
    }, $roles);
    $in = implode(',', $escaped);

    $result = mysqli_query($conn, "SELECT phone FROM `User` WHERE role IN ($in) AND is_active = 1 AND phone IS NOT NULL AND phone != ''");
    $phones = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $phones[] = $row['phone'];
    }
    return $phones;
}

function sendWaNotification($recipients, $message) {
    $setting = getWaSetting();
    if (!$setting) return false;
    if (empty($setting['gateway_url']) || empty($setting['api_key'])) return false;

    if (is_array($recipients)) {
        if (empty($recipients)) return false;
        $payload = json_encode(['recipients' => $recipients, 'message' => $message]);
    } else {
        if (empty($recipients)) return false;
        $payload = json_encode(['to' => $recipients, 'message' => $message]);
    }

    $url = $setting['gateway_url'] . '/api/send';
    $api_key = $setting['api_key'];

    // Coba pakai cURL, fallback ke file_get_contents
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $api_key
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) return false;
    } else {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nx-api-key: " . $api_key . "\r\n",
                'content' => $payload,
                'timeout' => 5,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        $http_code = 0;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $http_code = (int)$matches[1];
                }
            }
        }
    }

    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        return isset($data['success']) && $data['success'] === true;
    }
    return false;
}

// ============================================================
// LEADERBOARD
// ============================================================

function getLeaderboard($view = 'monthly', $month = null, $year = null) {
    global $conn;

    $month = $month ?: date('n');
    $year  = $year ?: date('Y');

    if ($view === 'yearly') {
        $where = "l.period_year = " . (int)$year;
    } else {
        $where = "l.period_month = " . (int)$month . " AND l.period_year = " . (int)$year;
    }

    $query = "SELECT l.staff_id, u.name AS staff_name, u.email AS staff_email,
                     SUM(l.points) AS total_points,
                     COUNT(l.id) AS tickets_closed
              FROM `LeaderboardLog` l
              JOIN `User` u ON u.id = l.staff_id
              WHERE $where
              GROUP BY l.staff_id, u.name, u.email
              ORDER BY total_points DESC";

    $result = mysqli_query($conn, $query);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

function getLeaderboardDetail($staff_id, $view = 'monthly', $month = null, $year = null) {
    global $conn;

    $month = $month ?: date('n');
    $year  = $year ?: date('Y');
    $staff_id = mysqli_real_escape_string($conn, $staff_id);

    if ($view === 'yearly') {
        $where = "l.period_year = " . (int)$year;
    } else {
        $where = "l.period_month = " . (int)$month . " AND l.period_year = " . (int)$year;
    }

    $query = "SELECT l.*, t.code AS ticket_code, t.title AS ticket_title, t.description AS ticket_description,
                     t.difficulty_level, t.status AS ticket_status, t.created_at AS ticket_created,
                     c.name AS category_name,
                     u2.name AS user_name
              FROM `LeaderboardLog` l
              JOIN `Ticket` t ON t.id = l.ticket_id
              LEFT JOIN `Category` c ON c.id = t.category_id
              LEFT JOIN `User` u2 ON u2.id = t.user_id
              WHERE l.staff_id = '$staff_id' AND $where
              ORDER BY l.created_at DESC";

    $result = mysqli_query($conn, $query);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

function getStaffStats($staff_id, $view = 'monthly', $month = null, $year = null) {
    global $conn;

    $month = $month ?: date('n');
    $year  = $year ?: date('Y');
    $staff_id = mysqli_real_escape_string($conn, $staff_id);

    if ($view === 'yearly') {
        $where = "YEAR(t.updated_at) = " . (int)$year;
    } else {
        $where = "MONTH(t.updated_at) = " . (int)$month . " AND YEAR(t.updated_at) = " . (int)$year;
    }

    $stats = [
        'total_closed' => 0,
        'avg_points' => 0,
        'difficulty_breakdown' => [1 => 0, 2 => 0, 3 => 0],
        'category_breakdown' => []
    ];

    $query = "SELECT COUNT(*) AS cnt, AVG(l.points) AS avg_pts
              FROM `LeaderboardLog` l
              WHERE l.staff_id = '$staff_id' AND l.period_month = " . (int)$month . " AND l.period_year = " . (int)$year;
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['total_closed'] = (int)$row['cnt'];
        $stats['avg_points'] = round((float)($row['avg_pts'] ?? 0), 1);
    }

    $query = "SELECT t.difficulty_level, COUNT(*) AS cnt
              FROM `Ticket` t
              WHERE t.staff_id = '$staff_id' AND t.status IN ('RESOLVED','CLOSED') AND $where
              GROUP BY t.difficulty_level";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $stats['difficulty_breakdown'][(int)$row['difficulty_level']] = (int)$row['cnt'];
        }
    }

    $query = "SELECT c.name AS category_name, COUNT(*) AS cnt
              FROM `Ticket` t
              JOIN `Category` c ON c.id = t.category_id
              WHERE t.staff_id = '$staff_id' AND t.status IN ('RESOLVED','CLOSED') AND $where
              GROUP BY c.name
              ORDER BY cnt DESC";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $stats['category_breakdown'][] = $row;
        }
    }

    return $stats;
}

// ============================================================
// FORMAT HELPERS
// ============================================================

function formatTanggal($datetime) {
    if (!$datetime) return '-';
    $dt = new DateTime($datetime);
    return $dt->format('d/m/Y H:i');
}

function potongTeks($text, $max = 100) {
    if (strlen($text) <= $max) return $text;
    return substr($text, 0, $max) . '...';
}

function statusBadge($status) {
    $map = [
        'OPEN'        => ['badge-status-open', 'Terbuka'],
        'IN_PROGRESS' => ['badge-status-in_progress', 'Diproses'],
        'PENDING'     => ['badge-status-pending', 'Tertunda'],
        'RESOLVED'    => ['badge-status-resolved', 'Selesai'],
        'CLOSED'      => ['badge-status-closed', 'Ditutup'],
    ];
    $s = $map[$status] ?? ['badge-status-closed', $status];
    return '<span class="badge ' . $s[0] . '">' . $s[1] . '</span>';
}

function flashMessage($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function setFlash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

// ============================================================
// CHART DATA (Manager Dashboard)
// ============================================================

function getChartDataKeluhan($year = null) {
    global $conn;
    $year = $year ?: date('Y');
    $year = (int)$year;

    $query = "SELECT MONTH(created_at) AS month, COUNT(*) AS total
              FROM `Ticket` WHERE YEAR(created_at) = $year
              GROUP BY MONTH(created_at) ORDER BY month";
    $result = mysqli_query($conn, $query);
    $data = array_fill(1, 12, 0);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[(int)$row['month']] = (int)$row['total'];
        }
    }
    return array_values($data);
}

function getChartDataProgress($year = null) {
    global $conn;
    $year = $year ?: date('Y');
    $year = (int)$year;

    $query = "SELECT MONTH(updated_at) AS month, COUNT(*) AS total
              FROM `Ticket` WHERE status IN ('RESOLVED','CLOSED') AND YEAR(updated_at) = $year
              GROUP BY MONTH(updated_at) ORDER BY month";
    $result = mysqli_query($conn, $query);
    $data = array_fill(1, 12, 0);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[(int)$row['month']] = (int)$row['total'];
        }
    }
    return array_values($data);
}

// ============================================================
// BADGE HELPERS
// ============================================================

function difficultyBadge($level) {
    $map = [
        1 => ['badge-diff-mudah', 'Mudah'],
        2 => ['badge-diff-sedang', 'Sedang'],
        3 => ['badge-diff-sulit', 'Sulit'],
    ];
    $s = $map[(int)$level] ?? ['badge-diff-mudah', '-'];
    return '<span class="badge ' . $s[0] . '">' . $s[1] . '</span>';
}

function priorityBadge($level) {
    $map = [
        'TINGGI'  => ['badge-priority-tinggi', 'Tinggi'],
        'SEDANG'  => ['badge-priority-sedang', 'Sedang'],
        'RENDAH'  => ['badge-priority-rendah', 'Rendah'],
    ];
    $s = $map[$level] ?? ['badge-priority-sedang', '-'];
    return '<span class="badge ' . $s[0] . '">' . $s[1] . '</span>';
}
