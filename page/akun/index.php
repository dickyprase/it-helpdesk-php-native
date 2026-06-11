<?php
require_once '../../config/function.php';
requireRole('MANAGER');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $result = createUser(
        trim($_POST['nama'] ?? ''),
        trim($_POST['email'] ?? ''),
        trim($_POST['phone'] ?? ''),
        $_POST['password'] ?? '',
        $_POST['role'] ?? 'USER',
        $_POST['division_id'] ?: null
    );
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/akun/');
        exit;
    } else {
        $error = $result['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $result = updateUser(
        $_POST['user_id'],
        trim($_POST['edit_nama'] ?? ''),
        trim($_POST['edit_email'] ?? ''),
        trim($_POST['edit_phone'] ?? ''),
        $_POST['edit_role'] ?? 'USER',
        $_POST['edit_division_id'] ?: null
    );
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/akun/');
        exit;
    } else {
        $error = $result['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $result = toggleUserActive($_POST['toggle_id']);
    if ($result['status']) {
        setFlash('success', $result['message']);
        header('Location: ' . getBaseUrl() . 'page/akun/');
        exit;
    } else {
        $error = $result['message'];
    }
}

$users = getUsers();
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
                <!-- Form Buat User Baru -->
                <div class="col-md-12 col-xl-4">
                    <div class="card shadow p-3 mb-4 bg-body rounded">
                        <div class="card-body text-center rounded" style="font-weight: bold; font-size: 30px; color: #fff; background-color: #8c57ff;">Form User Baru</div>
                        <hr>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama :</label>
                                <input type="text" class="form-control" name="nama" id="nama" required minlength="2">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email :</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">No. HP (opsional) :</label>
                                <input type="text" class="form-control" name="phone" id="phone" pattern="^(08|628)\d{8,13}$" title="Format: 08xx atau 628xx, minimal 10 digit">
                                <div class="form-text">Nomor WhatsApp aktif untuk notifikasi.</div>
                            </div>
                            <div class="mb-3">
                                <label for="division_id" class="form-label">Divisi <span class="text-danger">*</span>:</label>
                                <select class="form-select" name="division_id" id="division_id" required>
                                    <option value="">-- Pilih Divisi --</option>
                                    <?php foreach ($divisions as $div): ?>
                                    <option value="<?= htmlspecialchars($div['id']) ?>">
                                        <?= htmlspecialchars($div['name']) ?> (<?= $div['priority_level'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Pilih Role :</label>
                                <select class="form-select" name="role" id="role" required>
                                    <option value="USER">User</option>
                                    <option value="STAFF">Support</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password :</label>
                                <input type="password" class="form-control" name="password" id="password" required minlength="6">
                            </div>
                            <div class="text-end">
                                <button type="submit" name="create_user" value="1" class="btn btn-success"><i class="fas fa-plus me-1"></i>Buat Akun</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Daftar User -->
                <div class="col-md-12 col-xl-8">
                    <div class="card mb-4 shadow p-3 mb-5 bg-body rounded">
                        <div class="card-header">
                            <i class="fas fa-user me-1" style="color: #8c57ff;"></i>
                            Akun User (<?= count($users) ?>)
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimpleAkun">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Divisi</th>
                                        <th>Prioritas</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($users as $u): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($u['name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($u['division_name'] ?? '-') ?></td>
                                        <td class="text-center"><?= priorityBadge($u['division_priority'] ?? '') ?></td>
                                        <td class="text-center"><span class="badge bg-secondary"><?= htmlspecialchars($u['role'] ?? '-') ?></span></td>
                                        <td class="text-center">
                                            <?php if ($u['is_active'] ?? true): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Non Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <button type="button" class="btn btn-success btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modal-<?= htmlspecialchars($u['id']) ?>" title="Ubah">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <div class="modal fade" id="modal-<?= htmlspecialchars($u['id']) ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Ubah Akun</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                                            <div class="modal-body text-start">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nama :</label>
                                                                    <input type="text" class="form-control" name="edit_nama" value="<?= htmlspecialchars($u['name'] ?? '') ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Email :</label>
                                                                    <input type="email" class="form-control" name="edit_email" value="<?= htmlspecialchars($u['email'] ?? '') ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">No. HP :</label>
                                                                    <input type="text" class="form-control" name="edit_phone" value="<?= htmlspecialchars($u['phone'] ?? '') ?>" pattern="^(08|628)\d{8,13}$" title="Format: 08xx atau 628xx">
                                                                    <div class="form-text">Format: 08xx atau 628xx</div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Divisi <span class="text-danger">*</span>:</label>
                                                                    <select class="form-select" name="edit_division_id" required>
                                                                        <option value="">-- Pilih Divisi --</option>
                                                                        <?php foreach ($divisions as $div): ?>
                                                                        <option value="<?= htmlspecialchars($div['id']) ?>" <?= ($u['division_id'] ?? '') === $div['id'] ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($div['name']) ?> (<?= $div['priority_level'] ?>)
                                                                        </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Pilih Role :</label>
                                                                    <select class="form-select" name="edit_role">
                                                                        <option value="USER" <?= ($u['role'] ?? '') === 'USER' ? 'selected' : '' ?>>User</option>
                                                                        <option value="STAFF" <?= ($u['role'] ?? '') === 'STAFF' ? 'selected' : '' ?>>Support</option>
                                                                        <option value="MANAGER" <?= ($u['role'] ?? '') === 'MANAGER' ? 'selected' : '' ?>>Manager</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" name="update_user" value="1" class="btn btn-primary">Simpan Perubahan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <form method="POST" class="d-inline toggle-form" data-active="<?= ($u['is_active'] ?? true) ? '1' : '0' ?>" data-name="<?= htmlspecialchars($u['name'] ?? '') ?>">
                                                <input type="hidden" name="toggle_id" value="<?= htmlspecialchars($u['id']) ?>">
                                                <?php if ($u['is_active'] ?? true): ?>
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Nonaktifkan">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-success btn-sm" title="Aktifkan">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
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
        document.querySelectorAll('.toggle-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var self = this;
                var isActive = self.dataset.active === '1';
                var name = self.dataset.name;
                Swal.fire({
                    title: isActive ? 'Nonaktifkan Akun?' : 'Aktifkan Akun?',
                    text: isActive ? 'Akun "' + name + '" tidak akan bisa login' : 'Akun "' + name + '" akan bisa login kembali',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: isActive ? '#dc3545' : '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: isActive ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) self.submit();
                });
            });
        });
    });
    </script>

    <?php include '../../includes/footer.php'; ?>
