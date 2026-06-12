<?php
require_once '../../config/function.php';
requireRole('MANAGER');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $result = createCategory(trim($_POST['name'] ?? ''), trim($_POST['description'] ?? ''));
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/kategori/');
        exit;
    } else {
        $error = $result['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $result = updateCategory($_POST['category_id'], trim($_POST['edit_name'] ?? ''), trim($_POST['edit_description'] ?? ''));
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/kategori/');
        exit;
    } else {
        $error = $result['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $result = deleteCategory($_POST['delete_id']);
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/kategori/');
        exit;
    } else {
        $error = $result['message'];
    }
}

$categories = getCategories();
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

            <div class="row mt-4 align-items-stretch">
                <!-- Form Buat Kategori -->
                <div class="col-md-12 col-xl-4 mb-4">
                    <div class="card shadow p-3 bg-body rounded h-100">
                        <div class="card-body text-center rounded" style="font-weight: bold; font-size: 30px; color: #fff; background-color: #8c57ff; flex: 0 0 auto;">Form Kategori Baru</div>
                        <hr>
                        <form method="POST" style="flex: 1 1 auto; display: flex; flex-direction: column;">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Kategori :</label>
                                <input type="text" class="form-control" name="name" id="name" required minlength="2" maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi :</label>
                                <textarea class="form-control" name="description" id="description" rows="3" placeholder="Deskripsi singkat kategori"></textarea>
                            </div>
                            <div class="text-end mt-auto">
                                <button type="submit" name="create_category" value="1" class="btn btn-success"><i class="fas fa-plus me-1"></i>Buat Kategori</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Daftar Kategori -->
                <div class="col-md-12 col-xl-8 mb-4">
                    <div class="card shadow p-3 bg-body rounded h-100">
                        <div class="card-header">
                            <i class="fas fa-tags me-1" style="color: #8c57ff;"></i>
                            Kategori Tiket (<?= count($categories) ?>)
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                            <table id="datatablesSimpleAkun">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th>Nama Kategori</th>
                                        <th>Deskripsi</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($categories as $cat): ?>
                                    <tr>
                                        <td class="text-center align-middle"><?= $no++ ?></td>
                                        <td class="align-middle"><?= htmlspecialchars($cat['name'] ?? '-') ?></td>
                                        <td class="align-middle"><?= htmlspecialchars($cat['description'] ?? '-') ?></td>
                                        <td class="text-center align-middle text-nowrap">
                                            <button type="button" class="btn-action btn-action-edit me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $no ?>"><i class="fas fa-edit"></i> Ubah</button>

                                            <div class="modal fade" id="editModal<?= $no ?>" data-bs-backdrop="static" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Ubah Kategori</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <input type="hidden" name="category_id" value="<?= htmlspecialchars($cat['id']) ?>">
                                                            <div class="modal-body text-start">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nama Kategori :</label>
                                                                    <input type="text" class="form-control" name="edit_name" value="<?= htmlspecialchars($cat['name'] ?? '') ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Deskripsi :</label>
                                                                    <textarea class="form-control" name="edit_description" rows="3"><?= htmlspecialchars($cat['description'] ?? '') ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" name="update_category" value="1" class="btn btn-primary">Simpan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <form method="POST" class="d-inline delete-form" data-name="<?= htmlspecialchars($cat['name'] ?? '') ?>">
                                                <input type="hidden" name="delete_id" value="<?= htmlspecialchars($cat['id']) ?>">
                                                <button type="submit" class="btn-action btn-action-danger"><i class="fas fa-trash"></i> Hapus</button>
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
                    title: 'Hapus Kategori?',
                    text: 'Kategori "' + name + '" akan dihapus permanen',
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
