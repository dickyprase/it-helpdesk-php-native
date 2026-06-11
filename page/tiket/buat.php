<?php
require_once '../../config/function.php';
requireRole('USER');

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? '';

    if ($title && $description && $category_id) {
        $result = createTicket($title, $description, $category_id);
        if ($result['status']) {
            $ticket_id = $result['ticket_id'] ?? null;
            if ($ticket_id && isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                uploadTicketAttachments($_FILES['attachments'], $ticket_id);
            }
            setFlash('success', $result['message']);
            header('Location: ' . getBaseUrl() . 'page/tiket/buat.php');
            exit;
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Semua field wajib diisi';
    }
}

$categories = getCategories();
$flash_success = flashMessage('success');
include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid">
            <div class="row mt-4 d-flex justify-content-center">
                <div class="col">
                    <div class="card shadow p-3 mb-4 bg-body rounded">
                        <div class="card-body text-center rounded" style="font-weight: bold; font-size: 30px; color: #fff; background-color: #8c57ff;">Form Tiket Baru</div>
                        <hr>

                        <?php if ($flash_success): ?>
                        <script>
                            Swal.fire({ icon: 'success', title: 'Berhasil', text: '<?= htmlspecialchars($flash_success) ?>', timer: 3000, showConfirmButton: false });
                        </script>
                        <?php endif; ?>

                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">

                            <div class="mb-3">
                                <label for="title" class="form-label">Judul Kendala :</label>
                                <input type="text" class="form-control" name="title" id="title" placeholder="Contoh: Printer lantai 2 error" minlength="5" maxlength="200" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori :</label>
                                <select class="form-select" name="category_id" id="category_id" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['id']) ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] === $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="description" name="description" style="height: 200px" minlength="10" maxlength="5000" required placeholder="Tulis kendala"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                <label for="description">Tulis Kendala Disini</label>
                            </div>

                            <div class="mb-3">
                                <label for="attachments" class="form-label">Lampiran (opsional) :</label>
                                <input type="file" class="form-control" name="attachments[]" id="attachments" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
                                <div class="form-text">Bisa pilih beberapa file. Format: gambar, PDF, Office, ZIP. Maks total 75MB.</div>
                                <div id="fileList" class="mt-2"></div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i>Kirim Kendala</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.getElementById('attachments').addEventListener('change', function() {
        var fileList = document.getElementById('fileList');
        fileList.innerHTML = '';
        var totalSize = 0;
        for (var i = 0; i < this.files.length; i++) {
            var file = this.files[i];
            totalSize += file.size;
            var sizeMB = (file.size / 1024 / 1024).toFixed(2);
            var div = document.createElement('div');
            div.className = 'badge bg-light text-dark p-2 me-1 mb-1';
            div.innerHTML = '<i class="fas fa-file me-1"></i>' + file.name + ' (' + sizeMB + ' MB)';
            fileList.appendChild(div);
        }
        if (totalSize > 75 * 1024 * 1024) {
            fileList.innerHTML += '<div class="text-danger mt-1"><i class="fas fa-exclamation-triangle"></i> Total melebihi 75MB!</div>';
        }
    });
    </script>

    <?php include '../../includes/footer.php'; ?>
