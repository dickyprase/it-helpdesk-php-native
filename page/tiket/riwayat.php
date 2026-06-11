<?php
require_once '../../config/function.php';
requireRole('STAFF', 'MANAGER');

$staff_id = getCurrentUserId();
$tickets = getTickets("t.staff_id = '$staff_id' AND t.status IN ('RESOLVED','CLOSED')");

include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <div class="card mb-4 shadow p-3 mb-5 bg-body rounded mt-4">
                <div class="card-header">
                    <i class="fas fa-check-circle me-1"></i>
                    Tiket Selesai (<?= count($tickets) ?>)
                </div>
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Belum ada tiket yang selesai.</p>
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
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($t['code'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($t['user_name'] ?? '-') ?></td>
                                <td><?= formatTanggal($t['created_at'] ?? '') ?></td>
                                <td><?= htmlspecialchars(potongTeks($t['description'] ?? '', 80)) ?></td>
                                <td><?= htmlspecialchars($t['category_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($t['division_name'] ?? '-') ?></td>
                                <td><?= priorityBadge($t['division_priority'] ?? '') ?></td>
                                <td>
                                    <?php $atts = getTicketAttachments($t['id']); if (!empty($atts)): foreach ($atts as $a): ?>
                                    <a href="<?= getBaseUrl() . htmlspecialchars($a['filepath']) ?>" target="_blank" class="btn btn-outline-warning btn-sm" title="Lampiran"><i class="fas fa-paperclip"></i></a>
                                    <?php endforeach; else: ?>-<?php endif; ?>
                                </td>
                                <td class="text-center"><?= difficultyBadge($t['difficulty_level'] ?? 1) ?></td>
                                <td class="text-center">
                                    <?php if (($t['status'] ?? '') === 'RESOLVED'): ?>
                                        <span class="badge bg-info">Proses Validasi</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Ditutup</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= getBaseUrl() ?>page/chat/?id=<?= htmlspecialchars($t['id']) ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-history me-1"></i>Riwayat
                                    </a>
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
    <?php include '../../includes/footer.php'; ?>
