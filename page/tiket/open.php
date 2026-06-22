<?php
require_once '../../config/function.php';
requireRole('STAFF', 'MANAGER');

$success = '';
$error = '';
$is_staff = getCurrentUserRole() === 'STAFF';

if ($is_staff && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_id'])) {
    $result = claimTicket($_POST['claim_id']);
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/tiket/open.php');
        exit;
    } else {
        $error = $result['message'];
    }
}

$tickets = getTickets("t.status = 'OPEN' AND t.staff_id IS NULL");
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
                <div class="card-header">
                    <i class="fas fa-inbox me-1" style="color: #8c57ff;"></i>
                    Tiket Baru - Belum Ditugaskan (<?= count($tickets) ?>)
                </div>
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-3x mb-3" style="color: #198754;"></i>
                        <p>Semua tiket sudah ditugaskan. Kerja bagus!</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                    <table id="datatablesSimpleTicket">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>No Tiket</th>
                                <th>Nama User</th>
                                <th>Tanggal</th>
                                <th>Deskripsi Kendala</th>
                                <th>Kategori</th>
                                <th>Divisi</th>
                                <th class="text-center">Prioritas</th>
                                <th class="text-center">Bukti</th>
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
                                <?php if ($is_staff): ?>
                                <td class="text-center align-middle text-nowrap">
                                    <form method="POST" class="d-inline claim-form" data-ticket="<?= htmlspecialchars($t['code'] ?? '') ?>">
                                        <input type="hidden" name="claim_id" value="<?= htmlspecialchars($t['id']) ?>">
                                        <button type="submit" class="btn-action btn-action-hand">
                                            <i class="fas fa-hand-paper"></i> Ambil
                                        </button>
                                    </form>
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
