<?php
require_once '../../config/function.php';
requireRole('MANAGER');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_id'])) {
    $ticket_id = $_POST['validate_id'];
    $difficulty = (int)($_POST['difficulty'] ?? 1);
    $custom_points = !empty($_POST['custom_points']) ? (int)$_POST['custom_points'] : null;
    $admin_note = trim($_POST['admin_note'] ?? '');

    $r1 = setTicketDifficulty($ticket_id, $difficulty);
    if ($r1['status']) {
        $r2 = updateTicketStatus($ticket_id, 'CLOSED', $custom_points, $admin_note ?: null);
        if ($r2['status']) {
            if ($custom_points !== null) {
                $points = $custom_points;
            } else {
                $points = $difficulty * 10;
            }
            setFlash('success', 'Tiket berhasil divalidasi! Poin: ' . $points);
            header('Location: ' . getBaseUrl() . 'page/validasi/');
            exit;
        } else {
            $error = $r2['message'];
        }
    } else {
        $error = $r1['message'];
    }
}

$tickets = getTickets("t.status = 'RESOLVED'");
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
                    <i class="fas fa-check-double me-1" style="color: #8c57ff;"></i>
                    Validasi Poin — Tiket Menunggu Validasi (<?= count($tickets) ?>)
                </div>
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Tidak ada tiket yang menunggu validasi.</p>
                    </div>
                    <?php else: ?>
                    <table id="datatablesSimpleTicket">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Tiket</th>
                                <th>Nama User</th>
                                <th>Staff</th>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th>Kategori</th>
                                <th>Kesulitan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($tickets as $t): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($t['code'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($t['user_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($t['staff_name'] ?? '-') ?></td>
                                <td><?= formatTanggal($t['created_at'] ?? '') ?></td>
                                <td><?= htmlspecialchars(potongTeks($t['description'] ?? '', 60)) ?></td>
                                <td><?= htmlspecialchars($t['category_name'] ?? '-') ?></td>
                                <td><?= difficultyBadge($t['difficulty_level'] ?? 1) ?></td>
                                <td class="text-nowrap">
                                    <a href="<?= getBaseUrl() ?>page/chat/?id=<?= htmlspecialchars($t['id']) ?>" class="btn btn-warning btn-sm me-1">Riwayat Chat</a>
                                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal-<?= htmlspecialchars($t['id']) ?>">Validasi</button>
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

    <?php foreach ($tickets as $t): ?>
    <?php
        $diff_points = 10 * (int)($t['difficulty_level'] ?? 1);
    ?>
    <div class="modal fade" id="modal-<?= htmlspecialchars($t['id']) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="form-<?= htmlspecialchars($t['id']) ?>">
                    <input type="hidden" name="validate_id" value="<?= htmlspecialchars($t['id']) ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Validasi Tiket <?= htmlspecialchars($t['code'] ?? '') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Staff:</strong> <?= htmlspecialchars($t['staff_name'] ?? '-') ?></p>
                        <p><strong>Deskripsi:</strong> <?= htmlspecialchars($t['description'] ?? '-') ?></p>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label"><strong>Tingkat Kesulitan</strong></label>
                            <select class="form-select" name="difficulty" id="diff-<?= htmlspecialchars($t['id']) ?>">
                                <option value="1" <?= ($t['difficulty_level'] ?? 1) == 1 ? 'selected' : '' ?>>Mudah (10 Poin)</option>
                                <option value="2" <?= ($t['difficulty_level'] ?? 1) == 2 ? 'selected' : '' ?>>Sedang (20 Poin)</option>
                                <option value="3" <?= ($t['difficulty_level'] ?? 1) == 3 ? 'selected' : '' ?>>Sulit (30 Poin)</option>
                            </select>
                        </div>
                        <div class="alert alert-info mb-3" id="calc-<?= htmlspecialchars($t['id']) ?>">
                            <strong>Rumus:</strong> <span class="diff-val"><?= $diff_points ?></span> poin (difficulty x 10)
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><strong>Custom Poin</strong> (opsional):</label>
                            <input type="number" class="form-control" name="custom_points" min="0" max="999" placeholder="Kosongkan untuk pakai rumus otomatis">
                            <div class="form-text">Isi jika admin ingin override poin.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><strong>Catatan Admin</strong> (opsional):</label>
                            <textarea class="form-control" name="admin_note" rows="3" placeholder="Catatan untuk staff..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-validasi btn-success" data-form="form-<?= htmlspecialchars($t['id']) ?>" data-ticket="<?= htmlspecialchars($t['code'] ?? '') ?>">Validasi & Tutup Tiket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('select[name="difficulty"]').forEach(function(select) {
            select.addEventListener('change', function() {
                var ticketId = this.id.replace('diff-', '');
                var diffPoints = parseInt(this.value) * 10;
                var calcDiv = document.getElementById('calc-' + ticketId);
                if (calcDiv) {
                    calcDiv.querySelector('.diff-val').textContent = diffPoints;
                }
            });
        });

        document.querySelectorAll('.btn-validasi').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var formId = this.getAttribute('data-form');
                var ticketCode = this.getAttribute('data-ticket');
                var form = document.getElementById(formId);
                var diffSelect = form.querySelector('select[name="difficulty"]');
                var customInput = form.querySelector('input[name="custom_points"]');
                var diffPoints = parseInt(diffSelect.value) * 10;
                var customPoints = customInput.value ? parseInt(customInput.value) : null;
                var finalPoints = customPoints !== null ? customPoints : diffPoints;

                var text = 'Tiket ' + ticketCode + ' akan ditutup.';
                if (customPoints !== null) {
                    text += '\nCustom poin: ' + customPoints;
                } else {
                    text += '\nPoin: ' + diffPoints;
                }

                Swal.fire({
                    title: 'Validasi Tiket?',
                    text: text,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Validasi',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
    </script>

    <?php include '../../includes/footer.php'; ?>
