<?php
require_once '../../config/function.php';
requireRole('MANAGER');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_division'])) {
    $result = createDivision(
        trim($_POST['name'] ?? ''),
        $_POST['priority_level'] ?? 'SEDANG'
    );
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/divisi/');
        exit;
    } else {
        $error = $result['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_division'])) {
    $result = updateDivision(
        $_POST['division_id'],
        trim($_POST['edit_name'] ?? ''),
        $_POST['edit_priority_level'] ?? 'SEDANG'
    );
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/divisi/');
        exit;
    } else {
        $error = $result['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $result = deleteDivision($_POST['delete_id']);
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/divisi/');
        exit;
    } else {
        $error = $result['message'];
    }
}

$divisions = getDivisions();
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

            <div class="row mt-4">
                <!-- Form Buat Divisi -->
                <div class="col-md-12 col-xl-4">
                    <div class="card shadow p-3 mb-4 bg-body rounded">
                        <div class="card-body text-center rounded" style="font-weight: bold; font-size: 30px; color: #fff; background-color: #8c57ff;">Form Divisi Baru</div>
                        <hr>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Divisi :</label>
                                <input type="text" class="form-control" name="name" id="name" required minlength="2" maxlength="100" placeholder="Contoh: IT Infrastructure">
                            </div>
                            <div class="mb-3">
                                <label for="priority_level" class="form-label">Tingkat Prioritas :</label>
                                <select class="form-select" name="priority_level" id="priority_level" required>
                                    <option value="TINGGI">TINGGI</option>
                                    <option value="SEDANG" selected>SEDANG</option>
                                    <option value="RENDAH">RENDAH</option>
                                </select>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="create_division" value="1" class="btn btn-success"><i class="fas fa-plus me-1"></i>Buat Divisi</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Daftar Divisi -->
                <div class="col-md-12 col-xl-8">
                    <div class="card mb-4 shadow p-3 mb-5 bg-body rounded">
                        <div class="card-header">
                            <i class="fas fa-building me-1" style="color: #8c57ff;"></i>
                            Divisi (<?= count($divisions) ?>)
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimpleAkun">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Divisi</th>
                                        <th>Prioritas</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($divisions as $div): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($div['name'] ?? '-') ?></td>
                                        <td><?= priorityBadge($div['priority_level'] ?? 'SEDANG') ?></td>
                                        <td class="text-nowrap">
                                            <button type="button" class="btn btn-success btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modal-<?= htmlspecialchars($div['id']) ?>">Ubah</button>

                                            <div class="modal fade" id="modal-<?= htmlspecialchars($div['id']) ?>" data-bs-backdrop="static" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <input type="hidden" name="division_id" value="<?= htmlspecialchars($div['id']) ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Ubah Divisi</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nama Divisi :</label>
                                                                    <input type="text" class="form-control" name="edit_name" value="<?= htmlspecialchars($div['name'] ?? '') ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Tingkat Prioritas :</label>
                                                                    <select class="form-select" name="edit_priority_level">
                                                                        <option value="TINGGI" <?= ($div['priority_level'] ?? '') === 'TINGGI' ? 'selected' : '' ?>>TINGGI</option>
                                                                        <option value="SEDANG" <?= ($div['priority_level'] ?? '') === 'SEDANG' ? 'selected' : '' ?>>SEDANG</option>
                                                                        <option value="RENDAH" <?= ($div['priority_level'] ?? '') === 'RENDAH' ? 'selected' : '' ?>>RENDAH</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" name="update_division" value="1" class="btn btn-primary">Simpan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <form method="POST" class="d-inline delete-form" data-name="<?= htmlspecialchars($div['name'] ?? '') ?>">
                                                <input type="hidden" name="delete_id" value="<?= htmlspecialchars($div['id']) ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var self = this;
                var name = self.dataset.name;
                Swal.fire({
                    title: 'Hapus Divisi?',
                    text: 'Divisi "' + name + '" akan dihapus permanen',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) self.submit();
                });
            });
        });
    });
    </script>

    <?php include '../../includes/footer.php'; ?>
