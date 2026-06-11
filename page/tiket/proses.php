<?php
require_once '../../config/function.php';
requireRole('STAFF', 'MANAGER');

$staff_id = getCurrentUserId();
$tickets = getTickets("t.staff_id = '$staff_id' AND t.status IN ('IN_PROGRESS','PENDING')");

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
                        <p>Tidak ada tiket dalam antrian.</p>
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
                                    <a href="<?= getBaseUrl() . htmlspecialchars($a['filepath']) ?>" target="_blank" class="btn btn-warning btn-sm mb-1"><i class="fas fa-file"></i></a>
                                    <?php endforeach; else: ?>-<?php endif; ?>
                                </td>
                                <td><?= difficultyBadge($t['difficulty_level'] ?? 1) ?></td>
                                <td>
                                    <?php
                                    $st = $t['status'] ?? '';
                                    if ($st === 'IN_PROGRESS') echo '<span class="badge bg-primary">Diproses</span>';
                                    elseif ($st === 'PENDING') echo '<span class="badge bg-warning">Tertunda</span>';
                                    ?>
                                </td>
                                <td class="text-nowrap">
                                    <a href="<?= getBaseUrl() ?>page/chat/?id=<?= htmlspecialchars($t['id']) ?>" class="btn btn-danger btn-sm">Buka Chat</a>
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
