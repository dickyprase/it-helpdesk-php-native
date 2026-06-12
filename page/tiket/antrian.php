<?php
require_once '../../config/function.php';
requireRole('USER');

$user_id = getCurrentUserId();
$tickets = getTickets("t.user_id = '$user_id' AND t.status IN ('OPEN','IN_PROGRESS','PENDING')");

include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <div class="card mb-4 shadow p-3 mb-5 bg-body rounded mt-4">
                <div class="card-header">
                    <i class="fas fa-list me-1"></i>
                    Tiket dalam Antrian (<?= count($tickets) ?>)
                </div>
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Belum ada tiket dalam antrian.</p>
                        <a href="<?= getBaseUrl() ?>page/tiket/buat.php" class="btn btn-success">Buat Tiket Baru</a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                    <table id="datatablesSimpleTicket">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Kode Tiket</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Divisi</th>
                                <th class="text-center">Prioritas</th>
                                <th class="text-center">Bukti</th>
                                <th class="text-center">Kesulitan</th>
                                <th class="text-center">Status</th>
                                <th>Staff</th>
                                <th>Tanggal</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($tickets as $t): ?>
                            <tr>
                                <td class="text-center align-middle"><?= $no++ ?></td>
                                <td class="align-middle td-code"><code><?= htmlspecialchars($t['code'] ?? '-') ?></code></td>
                                <td class="align-middle"><?= htmlspecialchars(potongTeks($t['title'] ?? '', 50)) ?></td>
                                <td class="align-middle"><?= htmlspecialchars($t['category_name'] ?? '-') ?></td>
                                <td class="align-middle"><?= htmlspecialchars($t['division_name'] ?? '-') ?></td>
                                <td class="text-center align-middle"><?= priorityBadge($t['division_priority'] ?? '') ?></td>
                                <td class="text-center align-middle">
                                    <?php $atts = getTicketAttachments($t['id']); if (!empty($atts)): foreach ($atts as $a): ?>
                                    <a href="<?= getBaseUrl() . htmlspecialchars($a['filepath']) ?>" target="_blank" class="btn-action btn-action-file"><i class="fas fa-paperclip"></i> Lampiran</a>
                                    <?php endforeach; else: ?><span class="text-muted">-</span><?php endif; ?>
                                </td>
                                <td class="text-center align-middle"><?= difficultyBadge($t['difficulty_level'] ?? 1) ?></td>
                                <td class="text-center align-middle"><?= statusBadge($t['status'] ?? '') ?></td>
                                <td class="align-middle"><?= htmlspecialchars($t['staff_name'] ?? 'Belum ada') ?></td>
                                <td class="align-middle td-date">
                                    <div class="date-val"><?= formatTanggal($t['created_at'] ?? '') ?></div>
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <a href="<?= getBaseUrl() ?>page/chat/user.php?id=<?= htmlspecialchars($t['id']) ?>" class="btn-action btn-action-chat">
                                        <i class="fas fa-comments"></i> Chat
                                    </a>
                                </td>
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

    <?php include '../../includes/footer.php'; ?>
