<?php
require_once '../../config/function.php';
requireLogin();

$success = '';
$error = '';

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $result = updateProfile(
        trim($_POST['name'] ?? ''),
        trim($_POST['email'] ?? ''),
        trim($_POST['phone'] ?? '')
    );
    if ($result['status']) $success = $result['message'];
    else $error = $result['message'];
}

// Handle change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error = 'Password baru dan konfirmasi tidak cocok';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password baru minimal 6 karakter';
    } else {
        $result = changePassword($_POST['current_password'] ?? '', $new_password);
        if ($result['status']) $success = $result['message'];
        else $error = $result['message'];
    }
}

$profile = getProfile();

include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <?php if ($success): ?>
            <div class="alert alert-success mt-3"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="row mt-4">
                <!-- Update Profile -->
                <div class="col-md-12 col-xl-6">
                    <div class="card shadow p-3 mb-4 bg-body rounded">
                        <div class="card-body text-center rounded" style="font-weight: bold; font-size: 30px; color: #fff; background-color: #8c57ff;">Profil Saya</div>
                        <hr>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama :</label>
                                <input type="text" class="form-control" name="name" id="name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" required minlength="2">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email :</label>
                                <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">No. HP :</label>
                                <input type="text" class="form-control" name="phone" id="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" pattern="^(08|628)\d{8,13}$" title="Nomor harus dimulai dengan 08 atau 628, minimal 10 digit">
                                <div class="form-text">Nomor WhatsApp aktif. Format: 08xx atau 628xx. Digunakan untuk notifikasi WhatsApp.</div>
                                <div class="invalid-feedback" id="phoneError">Nomor harus dimulai dengan 08 atau 628, hanya angka, minimal 10 digit.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role :</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($profile['role'] ?? '') ?>" disabled>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_profile" value="1" class="btn btn-success">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="col-md-12 col-xl-6">
                    <div class="card shadow p-3 mb-4 bg-body rounded">
                        <div class="card-body text-center rounded" style="font-weight: bold; font-size: 30px; color: #fff; background-color: #8c57ff;">Ubah Password</div>
                        <hr>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Password Saat Ini :</label>
                                <input type="password" class="form-control" name="current_password" id="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Password Baru :</label>
                                <input type="password" class="form-control" name="new_password" id="new_password" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password Baru :</label>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required minlength="6">
                            </div>
                            <div class="text-end">
                                <button type="submit" name="change_password" value="1" class="btn btn-warning">Ubah Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                var val = this.value.replace(/\D/g, '');
                if (val.length > 0 && !val.match(/^(08|628)/)) {
                    this.classList.add('is-invalid');
                } else if (val.length > 0 && val.length < 10) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });

            var form = phoneInput.closest('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    var val = phoneInput.value.replace(/\D/g, '');
                    if (val.length > 0 && !val.match(/^(08|628)\d{8,13}$/)) {
                        e.preventDefault();
                        phoneInput.classList.add('is-invalid');
                        phoneInput.focus();
                    }
                });
            }
        }
    });
    </script>

    <?php include '../../includes/footer.php'; ?>
