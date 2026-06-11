<?php
require_once '../../config/function.php';
requireRole('MANAGER');

global $conn;
// Hapus template ticket_in_progress jika masih ada
mysqli_query($conn, "DELETE FROM `Notification_Template` WHERE event_type = 'ticket_in_progress'");

$success = '';
$error = '';

// Handle save config
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    global $conn;
    $gateway_url = mysqli_real_escape_string($conn, rtrim(trim($_POST['gateway_url'] ?? ''), '/'));
    $api_key = mysqli_real_escape_string($conn, trim($_POST['api_key'] ?? ''));

    $setting = getWaSetting();
    if ($setting) {
        mysqli_query($conn, "UPDATE `WA_Setting` SET gateway_url = '$gateway_url', api_key = '$api_key', is_enabled = 1, updated_at = NOW() WHERE id = '{$setting['id']}'");
    } else {
        mysqli_query($conn, "INSERT INTO `WA_Setting` (id, gateway_url, api_key, is_enabled, connection_status) VALUES (UUID(), '$gateway_url', '$api_key', 1, 'disconnected')");
    }
    setFlash('success', 'Konfigurasi WA berhasil disimpan');
    header('Location: ' . getBaseUrl() . 'page/wa-settings/');
    exit;
}

$setting = getWaSetting();
$templates = [];
$result = mysqli_query($conn, "SELECT * FROM `Notification_Template` ORDER BY event_type ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $templates[] = $row;
}

$flash_success = flashMessage('success');
include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h4 class="mt-4 mb-3 fw-semibold">Pengaturan WhatsApp</h4>

            <?php if ($flash_success): ?>
            <script>
                Swal.fire({ icon: 'success', title: 'Berhasil', text: '<?= htmlspecialchars($flash_success) ?>', timer: 3000, showConfirmButton: false });
            </script>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Section A: Gateway Config -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-server me-1"></i>
                            <span class="fw-semibold">Konfigurasi Gateway</span>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Base URL Gateway</label>
                                    <input type="text" class="form-control" name="gateway_url" id="gateway_url" value="<?= htmlspecialchars($setting['gateway_url'] ?? 'http://localhost:3001') ?>" placeholder="http://localhost:3001" required>
                                    <div class="form-text">URL WA Gateway (Node.js). Contoh: http://localhost:3001</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">API Key</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="api_key" id="api_key" value="<?= htmlspecialchars($setting['api_key'] ?? '') ?>" placeholder="Masukkan API Key">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleApiKey"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="save_config" value="1" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Simpan
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="testConnectionBtn">
                                        <i class="fas fa-plug me-1"></i>Test Koneksi
                                    </button>
                                </div>
                            </form>
                            <div id="testResult" class="mt-3"></div>
                        </div>
                    </div>

                    <!-- Section B: Test Send -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-paper-plane me-1"></i>
                            <span class="fw-semibold">Test Kirim Pesan</span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nomor Tujuan</label>
                                <input type="text" class="form-control" id="testPhone" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pesan</label>
                                <textarea class="form-control" id="testMessage" rows="3" placeholder="Tulis pesan test..."></textarea>
                            </div>
                            <button class="btn btn-primary" id="testSendBtn">
                                <i class="fas fa-paper-plane me-1"></i>Kirim Test
                            </button>
                            <div id="testSendResult" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <!-- Section C: Template Manager -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-file-alt me-1"></i>
                            <span class="fw-semibold">Template Pesan</span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nomor Test Template</label>
                                <input type="text" class="form-control form-control-sm" id="testTemplatePhone" placeholder="08xxxxxxxxxx" style="max-width:250px;">
                                <div class="form-text">Nomor WA untuk test kirim template</div>
                            </div>
                            <hr>
                            <div class="accordion" id="templateAccordion">
                                <?php foreach ($templates as $i => $tpl): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>">
                                            <strong><?= htmlspecialchars($tpl['label'] ?? $tpl['event_type']) ?></strong>
                                            <span class="small text-muted ms-2">— <?= htmlspecialchars($tpl['description'] ?? '') ?></span>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#templateAccordion">
                                        <div class="accordion-body">
                                            <div class="mb-2">
                                                <span class="small text-muted">Variabel (klik untuk insert):</span>
                                                <div class="mt-1">
                                                    <?php
                                                    $vars = array_map('trim', explode(',', $tpl['variables'] ?? ''));
                                                    foreach ($vars as $var):
                                                    ?>
                                                    <code class="badge bg-light text-dark me-1 mb-1 var-chip" style="cursor:pointer;" data-var="<?= htmlspecialchars($var) ?>"><?= htmlspecialchars($var) ?></code>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Template Pesan</label>
                                                <textarea class="form-control template-body" rows="5" data-event="<?= htmlspecialchars($tpl['event_type']) ?>"><?= htmlspecialchars($tpl['template_body'] ?? '') ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Preview</label>
                                                <div class="bg-light p-3 rounded small template-preview" id="preview<?= $i ?>">
                                                    <?= nl2br(htmlspecialchars($tpl['template_body'] ?? '')) ?>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-primary btn-sm save-template" data-event="<?= htmlspecialchars($tpl['event_type']) ?>">
                                                    <i class="fas fa-save me-1"></i>Simpan
                                                </button>
                                                <button class="btn btn-outline-primary btn-sm test-template" data-event="<?= htmlspecialchars($tpl['event_type']) ?>">
                                                    <i class="fas fa-paper-plane me-1"></i>Test
                                                </button>
                                            </div>
                                            <div class="test-template-result mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var baseUrl = '<?= getBaseUrl() ?>';

        // Toggle API Key visibility
        document.getElementById('toggleApiKey').addEventListener('click', function() {
            var input = document.getElementById('api_key');
            var icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // Test Connection - POST ke /api/send dengan API Key
        document.getElementById('testConnectionBtn').addEventListener('click', function() {
            var btn = this;
            var resultDiv = document.getElementById('testResult');
            var url = document.getElementById('gateway_url').value.trim().replace(/\/+$/, '');
            var apiKey = document.getElementById('api_key').value.trim();

            if (!url) {
                resultDiv.innerHTML = '<div class="alert alert-danger py-2 small">Masukkan Base URL Gateway terlebih dahulu</div>';
                return;
            }
            if (!apiKey) {
                resultDiv.innerHTML = '<div class="alert alert-danger py-2 small">Masukkan API Key terlebih dahulu</div>';
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing...';
            resultDiv.innerHTML = '';

            fetch(url + '/api/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'x-api-key': apiKey
                },
                body: JSON.stringify({ to: '6280000000000', message: 'test connection' })
            })
                .then(function(r) {
                    return r.text().then(function(text) {
                        var json = null;
                        try { json = JSON.parse(text); } catch(e) {}
                        return { status: r.status, ok: r.ok, json: json, raw: text };
                    });
                })
                .then(function(result) {
                    var formatted = result.json ? JSON.stringify(result.json, null, 2) : result.raw;
                    var statusClass = result.ok ? 'success' : 'warning';
                    resultDiv.innerHTML =
                        '<div class="alert alert-' + statusClass + ' py-2">' +
                        '<div class="d-flex justify-content-between align-items-center mb-2">' +
                        '<strong>HTTP ' + result.status + '</strong>' +
                        '</div>' +
                        '<pre class="mb-0" style="max-height:300px;overflow:auto;font-size:0.8rem;background:rgba(0,0,0,0.05);padding:0.75rem;border-radius:0.375rem;">' + formatted + '</pre>' +
                        '</div>';
                })
                .catch(function(err) {
                    resultDiv.innerHTML =
                        '<div class="alert alert-danger py-2">' +
                        '<div class="mb-2"><strong>Gagal terhubung</strong></div>' +
                        '<pre class="mb-0" style="max-height:300px;overflow:auto;font-size:0.8rem;background:rgba(0,0,0,0.05);padding:0.75rem;border-radius:0.375rem;">' + err.toString() + '</pre>' +
                        '</div>';
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-plug me-1"></i>Test Koneksi';
                });
        });

        // Test Send
        document.getElementById('testSendBtn').addEventListener('click', function() {
            var phone = document.getElementById('testPhone').value.trim();
            var message = document.getElementById('testMessage').value.trim();
            var resultDiv = document.getElementById('testSendResult');
            var btn = this;

            if (!phone || !message) {
                resultDiv.innerHTML = '<div class="alert alert-danger py-2 small">Nomor dan pesan wajib diisi</div>';
                return;
            }

            btn.disabled = true;
            resultDiv.innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Mengirim...</div>';

            var formData = new FormData();
            formData.append('phone', phone);
            formData.append('message', message);

            fetch(baseUrl + 'page/wa-settings/test_send.php', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var formatted = JSON.stringify(data, null, 2);
                    var statusClass = data.success ? 'success' : 'danger';
                    resultDiv.innerHTML =
                        '<div class="alert alert-' + statusClass + ' py-2">' +
                        '<div class="mb-2"><strong>' + (data.success ? 'Berhasil' : 'Gagal') + '</strong> — ' + (data.message || '') + '</div>' +
                        '<pre class="mb-0" style="max-height:300px;overflow:auto;font-size:0.8rem;background:rgba(0,0,0,0.05);padding:0.75rem;border-radius:0.375rem;">' + formatted + '</pre>' +
                        '</div>';
                })
                .catch(function(err) {
                    resultDiv.innerHTML =
                        '<div class="alert alert-danger py-2">' +
                        '<div class="mb-2"><strong>Error</strong></div>' +
                        '<pre class="mb-0" style="max-height:300px;overflow:auto;font-size:0.8rem;background:rgba(0,0,0,0.05);padding:0.75rem;border-radius:0.375rem;">' + err.toString() + '</pre>' +
                        '</div>';
                })
                .finally(function() { btn.disabled = false; });
        });

        // Variable chip click - insert to textarea
        document.querySelectorAll('.var-chip').forEach(function(chip) {
            chip.addEventListener('click', function() {
                var varName = this.getAttribute('data-var');
                var accordion = this.closest('.accordion-body');
                var textarea = accordion.querySelector('.template-body');
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                var text = textarea.value;
                textarea.value = text.substring(0, start) + varName + text.substring(end);
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = start + varName.length;
                updatePreview(textarea);
            });
        });

        // Live preview
        document.querySelectorAll('.template-body').forEach(function(textarea) {
            textarea.addEventListener('input', function() { updatePreview(this); });
        });

        function updatePreview(textarea) {
            var accordionBody = textarea.closest('.accordion-body');
            var preview = accordionBody.querySelector('.template-preview');
            var text = textarea.value;
            var html = text.replace(/\{(\w+)\}/g, '<mark>{$1}</mark>');
            html = html.replace(/\n/g, '<br>');
            preview.innerHTML = html;
        }

        // Save template
        document.querySelectorAll('.save-template').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var eventType = this.getAttribute('data-event');
                var accordionBody = this.closest('.accordion-body');
                var textarea = accordionBody.querySelector('.template-body');
                var templateBody = textarea.value.trim();
                var btnEl = this;

                if (!templateBody) return;

                btnEl.disabled = true;
                var formData = new FormData();
                formData.append('event_type', eventType);
                formData.append('template_body', templateBody);

                fetch(baseUrl + 'page/wa-settings/update_template.php', { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 2000, showConfirmButton: false });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
                        }
                    })
                    .catch(function() {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan koneksi' });
                    })
                    .finally(function() { btnEl.disabled = false; });
            });
        });

        // Test template
        document.querySelectorAll('.test-template').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var eventType = this.getAttribute('data-event');
                var phone = document.getElementById('testTemplatePhone').value.trim();
                var accordionBody = this.closest('.accordion-body');
                var resultDiv = accordionBody.querySelector('.test-template-result');
                var btnEl = this;

                if (!phone) {
                    resultDiv.innerHTML = '<div class="alert alert-danger py-1 small mb-0">Masukkan nomor test template di atas terlebih dahulu</div>';
                    return;
                }

                btnEl.disabled = true;
                resultDiv.innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Mengirim...</div>';

                var formData = new FormData();
                formData.append('event_type', eventType);
                formData.append('phone', phone);

                fetch(baseUrl + 'page/wa-settings/test_template.php', { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var formatted = JSON.stringify(data, null, 2);
                        var statusClass = data.success ? 'success' : 'danger';
                        resultDiv.innerHTML =
                            '<div class="alert alert-' + statusClass + ' py-2 mb-0">' +
                            '<div class="mb-1"><strong>' + (data.success ? 'Berhasil' : 'Gagal') + '</strong> — ' + (data.message || '') + '</div>' +
                            '<pre class="mb-0" style="max-height:200px;overflow:auto;font-size:0.75rem;background:rgba(0,0,0,0.05);padding:0.5rem;border-radius:0.25rem;">' + formatted + '</pre>' +
                            '</div>';
                    })
                    .catch(function(err) {
                        resultDiv.innerHTML = '<div class="alert alert-danger py-1 small mb-0">Error: ' + err.toString() + '</div>';
                    })
                    .finally(function() { btnEl.disabled = false; });
            });
        });
    });
    </script>

    <?php include '../../includes/footer.php'; ?>
