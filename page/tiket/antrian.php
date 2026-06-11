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
                    <table id="datatablesSimpleTicket">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Tiket</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Divisi</th>
                                <th>Prioritas</th>
                                <th>Bukti</th>
                                <th>Kesulitan</th>
                                <th>Status</th>
                                <th>Staff</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($tickets as $t): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><code><?= htmlspecialchars($t['code'] ?? '-') ?></code></td>
                                <td><?= htmlspecialchars(potongTeks($t['title'] ?? '', 50)) ?></td>
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
                                    <?php
                                    $status = $t['status'] ?? '';
                                    $badge = 'secondary';
                                    $label = $status;
                                    if ($status === 'OPEN')        { $badge = 'success'; $label = 'Terbuka'; }
                                    if ($status === 'IN_PROGRESS') { $badge = 'primary'; $label = 'Diproses'; }
                                    if ($status === 'PENDING')     { $badge = 'warning'; $label = 'Tertunda'; }
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= $label ?></span>
                                </td>
                                <td class="text-center"><?= htmlspecialchars($t['staff_name'] ?? 'Belum ada') ?></td>
                                <td class="text-center"><?= formatTanggal($t['created_at'] ?? '') ?></td>
                                <td class="text-center">
                                    <a href="<?= getBaseUrl() ?>page/chat/user.php?id=<?= htmlspecialchars($t['id']) ?>" class="btn btn-danger btn-sm">
                                        <i class="fas fa-comments me-1"></i>Chat
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
