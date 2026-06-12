<?php
require_once '../../config/function.php';
requireRole('MANAGER');

$view = $_GET['view'] ?? 'monthly';
$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));

$leaderboard = getLeaderboard($view, $month, $year);
$chart_keluhan = getChartDataKeluhan();
$chart_progress = getChartDataProgress();
$load_charts = true;

// Get detail for selected staff (if any)
$detail_staff_id = $_GET['staff_id'] ?? null;
$detail_data = [];
$detail_stats = [];
if ($detail_staff_id) {
    $detail_data = getLeaderboardDetail($detail_staff_id, $view, $month, $year);
    $detail_stats = getStaffStats($detail_staff_id, $view, $month, $year);
}

include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <!-- Charts -->
            <div class="row mt-4">
                <div class="col-xl-6">
                    <div class="card shadow mb-4 bg-body rounded">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-chart-area me-2" style="color: #8c57ff;"></i>
                            <span class="fw-semibold">Keluhan User</span>
                        </div>
                        <div class="card-body">
                            <canvas id="myAreaChart" width="100%" height="40"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card shadow mb-4 bg-body rounded">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-chart-bar me-2" style="color: #8c57ff;"></i>
                            <span class="fw-semibold">Progress Penyelesaian IT Support</span>
                        </div>
                        <div class="card-body">
                            <canvas id="myBarChart" width="100%" height="40"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="card mb-3 shadow bg-body rounded">
                <div class="card-body py-2">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-auto">
                            <select name="view" class="form-select form-select-sm">
                                <option value="monthly" <?= $view === 'monthly' ? 'selected' : '' ?>>Bulanan</option>
                                <option value="yearly" <?= $view === 'yearly' ? 'selected' : '' ?>>Tahunan</option>
                            </select>
                        </div>
                        <?php if ($view === 'monthly'): ?>
                        <div class="col-auto">
                            <select name="month" class="form-select form-select-sm">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-auto">
                            <select name="year" class="form-select form-select-sm">
                                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Leaderboard -->
            <div class="card mb-4 shadow p-3 mb-5 bg-body rounded">
                <div class="card-header">
                    <i class="fas fa-trophy me-1" style="color: #8c57ff;"></i>
                    Peringkat
                    <span class="text-muted small ms-2">
                        (<?= $view === 'monthly' ? date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year : 'Tahun ' . $year ?>)
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($leaderboard)): ?>
                    <p class="text-muted text-center">Belum ada data ranking.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                    <table id="datatablesSimple">
                        <thead>
                            <tr>
                                <th class="text-center">Peringkat</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th class="text-center">Total Poin</th>
                                <th class="text-center">Tiket Selesai</th>
                                <th class="text-center">Rata-rata Poin</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($leaderboard as $lb): ?>
                            <?php
                            $is_selected = $detail_staff_id === $lb['staff_id'];
                            $avg = $lb['tickets_closed'] > 0 ? round($lb['total_points'] / $lb['tickets_closed'], 1) : 0;
                            $rank_class = $no === 1 ? 'table-warning' : ($no === 2 ? 'table-light' : ($no === 3 ? 'table-danger' : ''));
                            ?>
                            <tr class="<?= $rank_class ?>">
                                <td class="text-center align-middle">
                                    <?php if ($no === 1): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-crown"></i> 1</span>
                                    <?php elseif ($no === 2): ?>
                                    <span class="badge bg-secondary">2</span>
                                    <?php elseif ($no === 3): ?>
                                    <span class="badge bg-danger">3</span>
                                    <?php else: ?>
                                    <?= $no ?>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle"><strong><?= htmlspecialchars($lb['staff_name'] ?? '-') ?></strong></td>
                                <td class="align-middle"><?= htmlspecialchars($lb['staff_email'] ?? '-') ?></td>
                                <td class="text-center align-middle"><span class="fs-5 fw-bold" style="color: #8c57ff;"><?= $lb['total_points'] ?? 0 ?></span></td>
                                <td class="text-center align-middle"><?= $lb['tickets_closed'] ?? 0 ?></td>
                                <td class="text-center align-middle"><?= $avg ?> poin/tiket</td>
                                <td class="text-center align-middle">
                                    <a href="?view=<?= $view ?>&month=<?= $month ?>&year=<?= $year ?>&staff_id=<?= urlencode($lb['staff_id']) ?>" 
                                       class="btn-action <?= $is_selected ? 'btn-action-chat' : 'btn-action-view' ?>">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                            <?php $no++; endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detail Section -->
            <?php if ($detail_staff_id && !empty($detail_data)): ?>
            <?php
            $staff_name = $detail_data[0]['staff_name'] ?? '-';
            $staff_email = $detail_data[0]['staff_email'] ?? '-';
            ?>
            <div class="card mb-4 shadow p-3 mb-5 bg-body rounded">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-user-chart me-1" style="color: #8c57ff;"></i>
                        Detail Peringkat: <strong><?= htmlspecialchars($staff_name) ?></strong>
                        <span class="text-muted small ms-2">(<?= htmlspecialchars($staff_email) ?>)</span>
                    </div>
                    <a href="?view=<?= $view ?>&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Tutup
                    </a>
                </div>
                <div class="card-body">
                    <!-- Stats Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white text-center p-3">
                                <div class="fs-2 fw-bold"><?= $detail_stats['total_closed'] ?? 0 ?></div>
                                <div>Total Tiket Selesai</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white text-center p-3">
                                <div class="fs-2 fw-bold"><?= array_sum($detail_stats['difficulty_breakdown'] ?? []) > 0 ? $detail_stats['avg_points'] : 0 ?></div>
                                <div>Rata-rata Poin</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark text-center p-3">
                                <div class="fs-2 fw-bold"><?= $detail_stats['difficulty_breakdown'][3] ?? 0 ?></div>
                                <div>Tiket Sulit</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white text-center p-3">
                                <div class="fs-2 fw-bold"><?= count($detail_stats['category_breakdown'] ?? []) ?></div>
                                <div>Kategori Ditangani</div>
                            </div>
                        </div>
                    </div>

                    <!-- Difficulty Breakdown -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header"><strong>Breakdown Kesulitan</strong></div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Mudah:</span>
                                        <span class="badge bg-success"><?= $detail_stats['difficulty_breakdown'][1] ?? 0 ?> tiket</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Sedang:</span>
                                        <span class="badge bg-warning"><?= $detail_stats['difficulty_breakdown'][2] ?? 0 ?> tiket</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Sulit:</span>
                                        <span class="badge bg-danger"><?= $detail_stats['difficulty_breakdown'][3] ?? 0 ?> tiket</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header"><strong>Kategori Ditangani</strong></div>
                                <div class="card-body">
                                    <?php if (empty($detail_stats['category_breakdown'])): ?>
                                    <p class="text-muted mb-0">Belum ada data.</p>
                                    <?php else: ?>
                                        <?php foreach ($detail_stats['category_breakdown'] as $cat): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><?= htmlspecialchars($cat['category_name']) ?></span>
                                            <span class="badge bg-secondary"><?= $cat['cnt'] ?> tiket</span>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket Detail Table -->
                    <h6 class="mb-3"><i class="fas fa-list me-1"></i>Riwayat Tiket Selesai</h6>
                    <div class="table-responsive">
                    <table id="datatablesSimpleTicket" class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Kode Tiket</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>User</th>
                                <th class="text-center">Kesulitan</th>
                                <th class="text-center">Poin</th>
                                <th>Catatan Admin</th>
                                <th>Tanggal</th>
                                <th class="text-center">Riwayat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($detail_data as $d): ?>
                            <tr>
                                <td class="text-center align-middle"><?= $no++ ?></td>
                                <td class="align-middle td-code"><code><?= htmlspecialchars($d['ticket_code'] ?? '-') ?></code></td>
                                <td class="align-middle"><?= htmlspecialchars(potongTeks($d['ticket_title'] ?? '', 50)) ?></td>
                                <td class="align-middle"><?= htmlspecialchars($d['category_name'] ?? '-') ?></td>
                                <td class="align-middle"><?= htmlspecialchars($d['user_name'] ?? '-') ?></td>
                                <td class="text-center align-middle"><?= difficultyBadge($d['difficulty_level'] ?? 1) ?></td>
                                <td class="text-center align-middle"><strong><?= $d['points'] ?? 0 ?></strong></td>
                                <td class="align-middle"><?= htmlspecialchars($d['admin_note'] ?? '-') ?></td>
                                <td class="align-middle td-date"><div class="date-val"><?= formatTanggal($d['created_at'] ?? '') ?></div></td>
                                <td class="text-center align-middle">
                                    <a href="<?= getBaseUrl() ?>page/chat/?id=<?= htmlspecialchars($d['ticket_id'] ?? '') ?>" class="btn-action btn-action-view" title="Lihat Riwayat Chat">
                                        <i class="fas fa-comments"></i> Chat
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
            <?php elseif ($detail_staff_id && empty($detail_data)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i>Tidak ada data detail untuk staff ini pada periode yang dipilih.
            </div>
            <?php endif; ?>

        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>

    <script>
    (function() {
        var labels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
        var keluhan = <?= json_encode($chart_keluhan) ?>;
        var progress = <?= json_encode($chart_progress) ?>;

        var ctxArea = document.getElementById('myAreaChart');
        if (ctxArea) {
            new Chart(ctxArea.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Keluhan',
                        data: keluhan,
                        lineTension: 0.3,
                        backgroundColor: 'rgba(140,87,255,0.08)',
                        borderColor: '#8c57ff',
                        pointRadius: 3,
                        pointBackgroundColor: '#8c57ff',
                        pointBorderColor: '#8c57ff',
                        pointHoverRadius: 5,
                        fill: true
                    }]
                },
                options: {
                    scales: { yAxes: [{ ticks: { beginAtZero: true } }] },
                    legend: { display: true }
                }
            });
        }

        var ctxBar = document.getElementById('myBarChart');
        if (ctxBar) {
            new Chart(ctxBar.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Tiket Selesai',
                        data: progress,
                        backgroundColor: '#8c57ff',
                        borderColor: '#b08bff',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: { yAxes: [{ ticks: { beginAtZero: true } }] },
                    legend: { display: true }
                }
            });
        }
    })();
    </script>
