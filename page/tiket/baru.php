<?php
require_once '../../config/function.php';
requireRole('STAFF', 'MANAGER');

$success = '';
$error = '';
$is_staff = getCurrentUserRole() === 'STAFF';
$is_manager = getCurrentUserRole() === 'MANAGER';

// Claim ticket (STAFF only)
if ($is_staff && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_id'])) {
    $result = claimTicket($_POST['claim_id']);
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/tiket/baru.php');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Build filter query
$conditions = ["1=1"];
$params = [];

// Status filter
$status_filter = $_GET['status'] ?? 'all';
if ($status_filter && $status_filter !== 'all') {
    $status_map = [
        'open' => "t.status = 'OPEN' AND t.staff_id IS NULL",
        'in_progress' => "t.status = 'IN_PROGRESS'",
        'pending' => "t.status = 'PENDING'",
        'resolved' => "t.status = 'RESOLVED'",
        'closed' => "t.status = 'CLOSED'",
    ];
    if (isset($status_map[$status_filter])) {
        $conditions[] = $status_map[$status_filter];
    }
}

// User filter
$user_filter = $_GET['user_id'] ?? '';
if ($user_filter) {
    $user_filter_esc = mysqli_real_escape_string($conn, $user_filter);
    $conditions[] = "t.user_id = '$user_filter_esc'";
}

// Staff filter
$staff_filter = $_GET['staff_id'] ?? '';
if ($staff_filter) {
    $staff_filter_esc = mysqli_real_escape_string($conn, $staff_filter);
    if ($staff_filter === 'unassigned') {
        $conditions[] = "t.staff_id IS NULL";
    } else {
        $conditions[] = "t.staff_id = '$staff_filter_esc'";
    }
}

// Date range filter
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
if ($date_from) {
    $date_from_esc = mysqli_real_escape_string($conn, $date_from);
    $conditions[] = "DATE(t.created_at) >= '$date_from_esc'";
}
if ($date_to) {
    $date_to_esc = mysqli_real_escape_string($conn, $date_to);
    $conditions[] = "DATE(t.created_at) <= '$date_to_esc'";
}

// Staff sees only their tickets + unassigned (unless Manager)
if ($is_staff && !$is_manager) {
    $current_uid = getCurrentUserId();
    $conditions[] = "(t.staff_id = '$current_uid' OR t.staff_id IS NULL)";
}

$where = implode(' AND ', $conditions);
$tickets = getTickets($where);

// Get users and staff for filter dropdowns
$all_users = getUsers();
$staff_list = array_filter($all_users, function($u) { return $u['role'] === 'STAFF'; });
$user_list = array_filter($all_users, function($u) { return $u['role'] === 'USER'; });

$flash_success = flashMessage('success');

include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <?php if ($flash_success): ?>
            <script>
                Swal.fire({ icon: 'success', title: 'Berhasil', text: '<?= htmlspecialchars($flash_success) ?>', timer: 3000, showConfirmButton: false });
            </script>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card mb-4 shadow p-3 mb-5 bg-body rounded mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-ticket me-1"></i>
                        Tiket (<?= count($tickets) ?>)
                    </span>
                    <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel" aria-expanded="true">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>

                <!-- Filter Panel -->
                <div class="collapse show" id="filterPanel">
                    <div class="card-body border-bottom pb-3" style="background-color: #ffffff; border-bottom: 2px solid #8c57ff !important;">
                        <form method="GET" class="row g-2 align-items-end">
                            <!-- Status -->
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">Status</label>
                                <select class="form-select form-select-sm" name="status">
                                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Semua</option>
                                    <option value="open" <?= $status_filter === 'open' ? 'selected' : '' ?>>Baru (Open)</option>
                                    <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>On Going</option>
                                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Closed</option>
                                </select>
                            </div>

                            <!-- User -->
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Nama User</label>
                                <select class="form-select form-select-sm" name="user_id">
                                    <option value="">Semua User</option>
                                    <?php foreach ($user_list as $u): ?>
                                    <option value="<?= htmlspecialchars($u['id']) ?>" <?= $user_filter === $u['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Staff -->
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Staff IT</label>
                                <select class="form-select form-select-sm" name="staff_id">
                                    <option value="">Semua Staff</option>
                                    <option value="unassigned" <?= $staff_filter === 'unassigned' ? 'selected' : '' ?>>Belum Ditugaskan</option>
                                    <?php foreach ($staff_list as $s): ?>
                                    <option value="<?= htmlspecialchars($s['id']) ?>" <?= $staff_filter === $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Date From -->
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                                <input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                            </div>

                            <!-- Date To -->
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                                <input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                            </div>

                            <!-- Buttons -->
                            <div class="col-12 d-flex gap-2 mt-2">
                                <button type="submit" class="btn btn-sm btn-primary" style="background-color: #8c57ff; border-color: #8c57ff;">
                                    <i class="fas fa-search me-1"></i> Terapkan Filter
                                </button>
                                <a href="<?= getBaseUrl() ?>page/tiket/baru.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ticket Table -->
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Tidak ada tiket ditemukan.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                    <table id="datatablesSimpleTicket">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>No Tiket</th>
                                <th>Nama User</th>
                                <th>Staff IT</th>
                                <th>Tanggal</th>
                                <th>Deskripsi Kendala</th>
                                <th>Kategori</th>
                                <th>Divisi</th>
                                <th class="text-center">Prioritas</th>
                                <th class="text-center">Bukti</th>
                                <th class="text-center">Kesulitan</th>
                                <th class="text-center">Status</th>
                                <?php if ($is_staff): ?>
                                <th class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($tickets as $t): ?>
                            <tr>
                                <td class="text-center align-middle"><?= $no++ ?></td>
                                <td class="align-middle td-code"><?= htmlspecialchars($t['code'] ?? '-') ?></td>
                                <td class="align-middle"><?= htmlspecialchars($t['user_name'] ?? '-') ?></td>
                                <td class="align-middle">
                                    <?php if (!empty($t['staff_name'])): ?>
                                        <?= htmlspecialchars($t['staff_name']) ?>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Belum Ditugaskan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle td-date"><div class="date-val"><?= formatTanggal($t['created_at'] ?? '') ?></div></td>
                                <td class="align-middle"><?= htmlspecialchars(potongTeks($t['description'] ?? '', 80)) ?></td>
                                <td class="align-middle"><?= htmlspecialchars($t['category_name'] ?? '-') ?></td>
                                <td class="align-middle"><?= htmlspecialchars($t['division_name'] ?? '-') ?></td>
                                <td class="text-center align-middle"><?= priorityBadge($t['division_priority'] ?? '') ?></td>
                                <td class="text-center align-middle">
                                    <?php $atts = getTicketAttachments($t['id']); if (!empty($atts)): foreach ($atts as $a): ?>
                                    <a href="<?= getBaseUrl() . htmlspecialchars($a['filepath']) ?>" target="_blank" class="btn-action btn-action-file"><i class="fas fa-paperclip"></i> Lampiran</a>
                                    <?php endforeach; else: ?><span class="text-muted">-</span><?php endif; ?>
                                </td>
                                <td class="text-center align-middle"><?= difficultyBadge($t['difficulty_level'] ?? 1) ?></td>
                                <td class="text-center align-middle">
                                    <?= statusBadge($t['status'] ?? '') ?>
                                </td>
                                <?php if ($is_staff): ?>
                                <td class="text-center align-middle text-nowrap">
                                    <?php if (empty($t['staff_id'])): ?>
                                    <form method="POST" class="d-inline claim-form" data-ticket="<?= htmlspecialchars($t['code'] ?? '') ?>">
                                        <input type="hidden" name="claim_id" value="<?= htmlspecialchars($t['id']) ?>">
                                        <button type="submit" class="btn-action btn-action-hand">
                                            <i class="fas fa-hand-paper"></i> Ambil
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <a href="<?= getBaseUrl() ?>page/chat/?id=<?= htmlspecialchars($t['id']) ?>" class="btn-action btn-action-view">
                                        <i class="fas fa-eye"></i> Lihat
                                    </a>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.claim-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var self = this;
                var ticketCode = self.dataset.ticket;
                Swal.fire({
                    title: 'Ambil Tiket?',
                    text: 'Tiket ' + ticketCode + ' akan ditugaskan kepada Anda',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Ambil',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) self.submit();
                });
            });
        });
    });
    </script>

    <?php include '../../includes/footer.php'; ?>
