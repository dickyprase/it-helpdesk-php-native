<?php
require_once '../../config/function.php';
requireRole('USER');

$user_id = getCurrentUserId();
$count_antrian = count(getTickets("t.user_id = '$user_id' AND t.status IN ('OPEN','IN_PROGRESS','PENDING')"));
$count_selesai = count(getTickets("t.user_id = '$user_id' AND t.status IN ('RESOLVED','CLOSED')"));
$count_total = $count_antrian + $count_selesai;

include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <h4 class="mt-4 mb-4 text-muted">Selamat datang, <?= htmlspecialchars(getCurrentUserName()) ?></h4>

            <div class="row" style="font-weight: bold; font-size: 20px; color: rgb(134, 134, 134);">
                
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow p-3 bg-body rounded h-100">
                        <div class="row d-flex align-items-center mb-3">
                            <div class="col-8">
                                <div class="card-body">Total Tiket</div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="card-body" style="font-size: 30px; color: #8c57ff;"><?= $count_total ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow p-3 bg-body rounded h-100">
                        <div class="row d-flex align-items-center mb-3">
                            <div class="col-8">
                                <div class="card-body">Tiket Dalam Antrian</div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="card-body" style="font-size: 30px; color: #8c57ff;"><?= $count_antrian ?></div>
                            </div>
                        </div>
                        <a class="btn mt-auto" href="<?= getBaseUrl() ?>page/tiket/antrian.php" style="background-color: #8c57ff; color: white; text-decoration: none;">Selengkapnya</a>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow p-3 bg-body rounded h-100">
                        <div class="row d-flex align-items-center mb-3">
                            <div class="col-8">
                                <div class="card-body">Tiket Selesai</div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="card-body" style="font-size: 30px; color: #8c57ff;"><?= $count_selesai ?></div>
                            </div>
                        </div>
                        <a class="btn mt-auto" href="<?= getBaseUrl() ?>page/tiket/selesai.php" style="background-color: #8c57ff; color: white; text-decoration: none;">Selengkapnya</a>
                    </div>
                </div>

            </div>

            <div class="row mt-2">
                <div class="col-12 text-center">
                    <a href="<?= getBaseUrl() ?>page/tiket/buat.php" class="btn btn-lg btn-success shadow-sm px-5 py-3 rounded-pill">
                        <i class="fas fa-plus me-2"></i> Buat Tiket Kendala Baru
                    </a>
                </div>
            </div>

        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>