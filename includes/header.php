<?php
// header.php harus di-include SETELAH function.php sudah di-require di halaman utama.
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../config/function.php';
}
requireLogin();
$current_user_name = getCurrentUserName();
$current_role = getCurrentUserRole();
$unread_count = getUnreadNotificationCount(getCurrentUserId());
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Dashboard - IT Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link href="<?= getBaseUrl() ?>css/styles.css?v=<?= time() ?>" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand " style="background-color: #f4f5fa">
        <a class="navbar-brand ps-3" href="<?= getBaseUrl() ?>"><i class="fas fa-ticket" style="color: #8c57ff;"></i> IT Helpdesk</a>
        <button class="btn btn-link btn-sm order-0 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!" style="color: #8c57ff;"><i class="fas fa-bars"></i></button>
        <ul class="navbar-nav ms-auto align-items-center">
            <!-- Notification Bell -->
            <li class="nav-item dropdown me-2">
                <a class="nav-link dropdown-toggle position-relative p-2" id="notifDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:1.2rem;">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                    <span class="position-absolute badge rounded-pill bg-danger" style="font-size: 0.65rem; padding: 0.35em 0.5em; top: 4px; right: 0px; border: 2px solid #f4f5fa;">
                        <?= $unread_count > 99 ? '99+' : $unread_count ?>
                    </span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notifDropdown" style="width:320px; max-height:400px; overflow-y:auto;">
                    <li class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                        <span class="fw-semibold">Notifikasi</span>
                        <?php if ($unread_count > 0): ?>
                        <a href="#" class="text-decoration-none small fw-semibold" id="markAllRead" style="color:#8c57ff;">Tandai dibaca</a>
                        <?php endif; ?>
                    </li>
                    <li><hr class="dropdown-divider my-0"></li>
                    <?php
                    $notifications = getNotifications(getCurrentUserId(), 10);
                    if (empty($notifications)):
                    ?>
                    <li class="text-center text-muted py-4"><small>Tidak ada notifikasi</small></li>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                        <li>
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
                            <a class="dropdown-item py-2 <?= ($notif['is_read'] ?? 0) ? '' : 'bg-light fw-bold' ?>" href="<?= $notif_link ?>" data-notif-id="<?= htmlspecialchars($notif['id']) ?>">
                                <div class="small text-muted"><?= formatTanggal($notif['created_at'] ?? '') ?></div>
                                <div class="text-truncate" style="max-width:280px;"><?= htmlspecialchars($notif['message'] ?? '') ?></div>
                                <?php if ($notif['points'] ?? null): ?>
                                <span class="badge bg-success mt-1">+<?= $notif['points'] ?> poin</span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider my-0"></li>
                    <li><a class="dropdown-item text-center small py-2" href="<?= getBaseUrl() ?>page/notifikasi/">Lihat Semua</a></li>
                </ul>
            </li>
            <!-- User Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle p-2" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i> <span class="d-none d-sm-inline"><?= htmlspecialchars($current_user_name) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdown">
                    <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($current_role) ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= getBaseUrl() ?>page/profil/"><i class="fas fa-user-pen me-2"></i>Profil</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= getBaseUrl() ?>logout/"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </li>
        </ul>
    </nav>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var baseUrl = '<?= getBaseUrl() ?>';
        
        document.querySelectorAll('[data-notif-id]').forEach(function(el) {
            el.addEventListener('click', function() {
                var notifId = this.getAttribute('data-notif-id');
                fetch(baseUrl + 'page/notifikasi/mark_read.php?id=' + notifId, {method: 'POST'});
            });
        });

        var markAllBtn = document.getElementById('markAllRead');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                fetch(baseUrl + 'page/notifikasi/mark_all_read.php', {method: 'POST'}).then(function() {
                    location.reload();
                });
            });
        }
    });
    </script>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-light" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">

                        <?php if ($current_role === 'USER'): ?>
                        <!-- Menu User -->
                        <div class="sb-sidenav-menu-heading">
                            <div class="d-flex align-items-center mb-2">
                                <div style="flex:1; height:1px; background:#8c57ff33;"></div>
                                <div class="mx-2">Menu User</div>
                                <div style="flex:10; height:1px; background:#8c57ff33;"></div>
                            </div>
                        </div>
                        <a class="nav-link" href="<?= getBaseUrl() ?>page/dashboard/user.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                            Halaman Utama
                        </a>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#tiket_user" aria-expanded="false">
                            <div class="sb-nav-link-icon"><i class="fas fa-ticket"></i></div>
                            Tiket
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="tiket_user" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="<?= getBaseUrl() ?>page/tiket/buat.php">Buat Tiket Baru</a>
                                <a class="nav-link" href="<?= getBaseUrl() ?>page/tiket/antrian.php">Dalam Antrian</a>
                                <a class="nav-link" href="<?= getBaseUrl() ?>page/tiket/selesai.php">Selesai</a>
                            </nav>
                        </div>
                        <?php endif; ?>

                        <?php if ($current_role === 'STAFF'): ?>
                        <!-- Menu Support -->
                        <div class="sb-sidenav-menu-heading">
                            <div class="d-flex align-items-center mb-2">
                                <div style="flex:1; height:1px; background:#8c57ff33;"></div>
                                <div class="mx-2">Menu Support</div>
                                <div style="flex:10; height:1px; background:#8c57ff33;"></div>
                            </div>
                        </div>
                        <a class="nav-link" href="<?= getBaseUrl() ?>page/dashboard/">
                            <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                            Halaman Utama
                        </a>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#tiket_support" aria-expanded="false">
                            <div class="sb-nav-link-icon"><i class="fas fa-ticket"></i></div>
                            Tiket
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="tiket_support" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="<?= getBaseUrl() ?>page/tiket/baru.php">Baru</a>
                                <a class="nav-link" href="<?= getBaseUrl() ?>page/tiket/proses.php">Dalam Antrian</a>
                                <a class="nav-link" href="<?= getBaseUrl() ?>page/tiket/riwayat.php">Selesai</a>
                            </nav>
                        </div>
                        <?php endif; ?>

                        <?php if ($current_role === 'MANAGER'): ?>
                        <!-- Menu Manager -->
                        <div class="sb-sidenav-menu-heading">
                            <div class="d-flex align-items-center mb-2">
                                <div style="flex:1; height:1px; background:#8c57ff33;"></div>
                                <div class="mx-2">Menu Manager</div>
                                <div style="flex:10; height:1px; background:#8c57ff33;"></div>
                            </div>
                        </div>
                        <a class="nav-link" href="<?= getBaseUrl() ?>page/dashboard/manager.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                            Halaman Ranking
                        </a>
                        <a class="nav-link" href="<?= getBaseUrl() ?>page/validasi/">
                            <div class="sb-nav-link-icon"><i class="fab fa-bitcoin"></i></div>
                            Validasi Poin
                        </a>
                        <a class="nav-link" href="<?= getBaseUrl() ?>page/tiket/baru.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-ticket"></i></div>
                            Tiket Baru
                        </a>

                        <!-- Pengaturan (Manager only) -->
                        <div class="sb-sidenav-menu-heading">
                            <div class="d-flex align-items-center mb-2">
                                <div style="flex:1; height:1px; background:#8c57ff33;"></div>
                                <div class="mx-2">Pengaturan</div>
                                <div style="flex:10; height:1px; background:#8c57ff33;"></div>
                            </div>
                        </div>
                        <a class="nav-link" href="<?= getBaseUrl() ?>page/akun/">
                            <div class="sb-nav-link-icon"><i class="fas fa-user"></i></div>
                            Akun
                        </a>
                        <a class="nav-link" href="<?= getBaseUrl() ?>page/kategori/">
                            <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
                            Kategori
                        </a>
                        <a class="nav-link" href="<?= getBaseUrl() ?>page/divisi/">
                            <div class="sb-nav-link-icon"><i class="fas fa-building"></i></div>
                            Divisi
                        </a>
                        <a class="nav-link" href="<?= getBaseUrl() ?>page/wa-settings/">
                            <div class="sb-nav-link-icon"><i class="fab fa-whatsapp"></i></div>
                            Pengaturan WA
                        </a>
                        <?php endif; ?>

                        <!-- Logout (semua role) -->
                        <div class="sb-sidenav-menu-heading">
                            <div class="d-flex align-items-center mb-2">
                                <div style="flex:1; height:1px; background:#8c57ff33;"></div>
                                <div class="mx-2">LogOut</div>
                                <div style="flex:10; height:1px; background:#8c57ff33;"></div>
                            </div>
                        </div>
                        <a class="nav-link" href="<?= getBaseUrl() ?>logout/">
                            <div class="sb-nav-link-icon"><i class="fas fa-door-open"></i></div>
                            Keluar
                        </a>

                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Login sebagai:</div>
                    <?= htmlspecialchars($current_user_name) ?> (<?= htmlspecialchars($current_role) ?>)
                </div>
            </nav>
        </div>
