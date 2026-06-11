<?php
require_once '../../config/function.php';
requireLogin();

$user_id = getCurrentUserId();
$current_role = getCurrentUserRole();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all'])) {
    markAllNotificationsRead($user_id);
    header('Location: ' . getBaseUrl() . 'page/notifikasi/');
    exit;
}

$notifications = getNotifications($user_id, 50);

include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <div class="card mb-4 shadow p-3 mb-5 bg-body rounded mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-bell me-1" style="color: #8c57ff;"></i>
                        Notifikasi
                    </div>
                    <?php if (!empty($notifications)): ?>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="mark_all" class="btn btn-sm btn-outline-primary">Tandai Semua Dibaca</button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-bell-slash fa-3x mb-3"></i>
                        <p>Tidak ada notifikasi.</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notif): ?>
                        <?php
                        $notif_link = '#';
                        if ($notif['ticket_id'] ?? '') {
                            if ($current_role === 'MANAGER' && ($notif['type'] ?? '') === 'ticket_resolved_manager') {
                                $notif_link = getBaseUrl() . 'page/validasi/';
                            } elseif ($current_role === 'USER') {
                                $notif_link = getBaseUrl() . 'page/chat/user.php?id=' . urlencode($notif['ticket_id']);
                            } else {
                                $notif_link = getBaseUrl() . 'page/chat/?id=' . urlencode($notif['ticket_id']);
                            }
                        }
                        ?>
                        <a href="<?= $notif_link ?>" 
                           class="list-group-item list-group-item-action <?= ($notif['is_read'] ?? 0) ? '' : 'list-group-item-light fw-bold' ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="mb-1"><?= htmlspecialchars($notif['message'] ?? '') ?></div>
                                    <?php if ($notif['points'] ?? null): ?>
                                    <span class="badge bg-success">+<?= $notif['points'] ?> poin</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted text-nowrap ms-3"><?= formatTanggal($notif['created_at'] ?? '') ?></small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
