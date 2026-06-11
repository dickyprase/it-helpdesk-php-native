<?php
require_once '../../config/function.php';
requireRole('STAFF', 'MANAGER');

$staff_id = getCurrentUserId();
$error = '';

// Handle unclaim ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unclaim_id'])) {
    $result = unclaimTicket($_POST['unclaim_id'], '');
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/tiket/proses.php');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Handle pending ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pending_id'])) {
    $reason = trim($_POST['pending_reason'] ?? '');
    if (empty($reason)) {
        $error = 'Alasan pending wajib diisi';
    } else {
        $result = setTicketPending($_POST['pending_id'], $reason);
        if ($result['status']) {
            setFlash('success', $result['message']);
            header('Location: ' . getBaseUrl() . 'page/tiket/proses.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Handle resume ticket (PENDING -> IN_PROGRESS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resume_id'])) {
    $result = updateTicketStatus($_POST['resume_id'], 'IN_PROGRESS');
    if ($result['status']) {
        setFlash('success', 'Tiket berhasil dilanjutkan');
        header('Location: ' . getBaseUrl() . 'page/tiket/proses.php');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Handle resolve ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_id'])) {
    $note = trim($_POST['resolve_note'] ?? '');
    if (empty($note)) {
        $error = 'Catatan penyelesaian wajib diisi';
    } else {
        $result = resolveTicket($_POST['resolve_id'], $note);
        if ($result['status']) {
            setFlash('success', $result['message']);
            header('Location: ' . getBaseUrl() . 'page/tiket/proses.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$tickets = getTickets("t.staff_id = '$staff_id' AND t.status IN ('IN_PROGRESS','PENDING')");
$flash_success = flashMessage('success');

include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <div class="card mb-4 shadow p-3 mb-5 bg-body rounded mt-4">
                <div class="card-header">
                    <i class="fas fa-list me-1"></i>
                    Tiket dalam Proses (<?= count($tickets) ?>)
                </div>
                <div class="card-body">
                    <?php if ($flash_success): ?>
                    <script>
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: '<?= htmlspecialchars($flash_success) ?>', timer: 3000, showConfirmButton: false });
                    </script>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if (empty($tickets)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Tidak ada tiket dalam proses.</p>
                    </div>
                    <?php else: ?>
                    <table id="datatablesSimpleTicket">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Tiket</th>
                                <th>Nama</th>
                                <th>Tanggal</th>
                                <th>Deskripsi Kendala</th>
                                <th>Kategori</th>
                                <th>Divisi</th>
                                <th>Prioritas</th>
                                <th>Bukti</th>
                                <th>Kesulitan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($tickets as $t): ?>
                            <?php $st = $t['status'] ?? ''; ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($t['code'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($t['user_name'] ?? '-') ?></td>
                                <td><?= formatTanggal($t['created_at'] ?? '') ?></td>
                                <td><?= htmlspecialchars(potongTeks($t['description'] ?? '', 80)) ?></td>
                                <td><?= htmlspecialchars($t['category_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($t['division_name'] ?? '-') ?></td>
                                <td class="text-center"><?= priorityBadge($t['division_priority'] ?? '') ?></td>
                                <td class="text-center">
                                    <?php $atts = getTicketAttachments($t['id']); if (!empty($atts)): foreach ($atts as $a): ?>
                                    <a href="<?= getBaseUrl() . htmlspecialchars($a['filepath']) ?>" target="_blank" class="btn btn-outline-warning btn-sm" title="Lampiran"><i class="fas fa-paperclip"></i></a>
                                    <?php endforeach; else: ?>-<?php endif; ?>
                                </td>
                                <td class="text-center"><?= difficultyBadge($t['difficulty_level'] ?? 1) ?></td>
                                <td class="text-center">
                                    <?php
                                    if ($st === 'IN_PROGRESS') echo '<span class="badge bg-primary">Diproses</span>';
                                    elseif ($st === 'PENDING') echo '<span class="badge bg-warning">Tertunda</span>';
                                    ?>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="<?= getBaseUrl() ?>page/chat/?id=<?= htmlspecialchars($t['id']) ?>" class="btn btn-danger btn-sm" title="Buka Chat">
                                        <i class="fas fa-comments"></i>
                                    </a>

                                    <?php if ($st === 'IN_PROGRESS'): ?>
                                    <!-- Tombol untuk tiket IN_PROGRESS -->
                                    <button type="button" class="btn btn-warning btn-sm pending-btn"
                                            data-id="<?= htmlspecialchars($t['id']) ?>"
                                            data-code="<?= htmlspecialchars($t['code'] ?? '') ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#pendingModal"
                                            title="Pending Tiket">
                                        <i class="fas fa-pause-circle"></i>
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm resolve-btn"
                                            data-id="<?= htmlspecialchars($t['id']) ?>"
                                            data-code="<?= htmlspecialchars($t['code'] ?? '') ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#resolveModal"
                                            title="Selesaikan Tiket">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <form method="POST" class="d-inline unclaim-form" data-ticket="<?= htmlspecialchars($t['code'] ?? '') ?>">
                                        <input type="hidden" name="unclaim_id" value="<?= htmlspecialchars($t['id']) ?>">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm" title="Lepas Tiket">
                                            <i class="fas fa-hand-paper"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($st === 'PENDING'): ?>
                                    <!-- Tombol untuk tiket PENDING -->
                                    <form method="POST" class="d-inline resume-form" data-ticket="<?= htmlspecialchars($t['code'] ?? '') ?>">
                                        <input type="hidden" name="resume_id" value="<?= htmlspecialchars($t['id']) ?>">
                                        <button type="submit" class="btn btn-primary btn-sm" title="Lanjutkan Tiket">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-success btn-sm resolve-btn"
                                            data-id="<?= htmlspecialchars($t['id']) ?>"
                                            data-code="<?= htmlspecialchars($t['code'] ?? '') ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#resolveModal"
                                            title="Selesaikan Tiket">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Pending -->
    <div class="modal fade" id="pendingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="pending_id" id="pending_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Pending Tiket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Tiket: <strong id="pending_code">-</strong></p>
                        <div class="mb-3">
                            <label class="form-label">Alasan Pending <span class="text-danger">*</span></label>
                            <textarea name="pending_reason" class="form-control" rows="4" required placeholder="Jelaskan alasan menunda tiket ini..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-pause-circle me-1"></i>Pending Tiket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Resolve -->
    <div class="modal fade" id="resolveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="resolve_id" id="resolve_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Selesaikan Tiket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Tiket: <strong id="resolve_code">-</strong></p>
                        <div class="mb-3">
                            <label class="form-label">Catatan Penyelesaian <span class="text-danger">*</span></label>
                            <textarea name="resolve_note" class="form-control" rows="4" required placeholder="Jelaskan solusi yang diberikan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Selesaikan Tiket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pending modal
        var pendingModal = document.getElementById('pendingModal');
        if (pendingModal) {
            pendingModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                document.getElementById('pending_id').value = button.getAttribute('data-id');
                document.getElementById('pending_code').textContent = button.getAttribute('data-code');
            });
        }

        // Resolve modal
        var resolveModal = document.getElementById('resolveModal');
        if (resolveModal) {
            resolveModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                document.getElementById('resolve_id').value = button.getAttribute('data-id');
                document.getElementById('resolve_code').textContent = button.getAttribute('data-code');
            });
        }

        // Resume confirmation
        document.querySelectorAll('.resume-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var self = this;
                var ticketCode = self.dataset.ticket;
                Swal.fire({
                    title: 'Lanjutkan Tiket?',
                    text: 'Tiket ' + ticketCode + ' akan kembali ke status Diproses',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) self.submit();
                });
            });
        });

        // Unclaim confirmation
        document.querySelectorAll('.unclaim-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var self = this;
                var ticketCode = self.dataset.ticket;
                Swal.fire({
                    title: 'Lepas Tiket?',
                    text: 'Tiket ' + ticketCode + ' akan dilepas dan kembali ke status Terbuka',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Lepas',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) self.submit();
                });
            });
        });
    });
    </script>

    <?php include '../../includes/footer.php'; ?>
