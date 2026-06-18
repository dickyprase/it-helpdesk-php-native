<?php
require_once '../../config/function.php';
requireRole('STAFF', 'MANAGER');

$ticket_id = $_GET['id'] ?? '';
if (!$ticket_id) { header('Location: ' . getBaseUrl() . 'page/tiket/proses.php'); exit; }

$msg_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_message'])) {
        $message = trim($_POST['message'] ?? '');
        if ($message) {
            $r = sendMessage($ticket_id, $message);
            if ($r['status']) {
                $chat_id = $r['chat_id'] ?? null;
                if ($chat_id && isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                    uploadChatAttachments($_FILES['attachments'], $chat_id);
                }
            } else {
                $msg_error = $r['message'];
            }
        }
    }
    if (isset($_POST['resolve_ticket'])) {
        $note = trim($_POST['resolution_note'] ?? '');
        if ($note) {
            $r = resolveTicket($ticket_id, $note);
            if ($r['status']) { header('Location: ' . getBaseUrl() . 'page/tiket/riwayat.php'); exit; }
            else $msg_error = $r['message'];
        } else {
            $msg_error = 'Catatan penyelesaian wajib diisi';
        }
    }
    if (isset($_POST['set_difficulty'])) {
        $level = (int)$_POST['difficulty_level'];
        $r = setTicketDifficulty($ticket_id, $level);
        if ($r['status']) { header('Location: ' . getBaseUrl() . 'page/chat/?id=' . urlencode($ticket_id)); exit; }
        else $msg_error = $r['message'];
    }
}

$ticket = getTicketById($ticket_id);
if (!$ticket) { header('Location: ' . getBaseUrl() . 'page/tiket/proses.php'); exit; }

$messages = getChatMessages($ticket_id);
$current_user_id = getCurrentUserId();
$is_in_progress = ($ticket['status'] ?? '') === 'IN_PROGRESS';
$ticket_attachments = getTicketAttachments($ticket_id);

include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <?php if ($msg_error): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($msg_error) ?></div>
            <?php endif; ?>

            <!-- Ticket Detail Table -->
            <div class="card mb-4 shadow p-3 bg-body rounded mt-3">
                <div class="card-header text-center">
                    <i class="fas fa-tools me-1" style="color: #8c57ff;"></i>
                    <span class="fw-semibold">Permasalahan</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered border-primary">
                            <thead>
                                <tr class="text-center align-middle">
                                    <th>No Tiket</th>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th>Divisi</th>
                                    <th>Prioritas</th>
                                    <th>Tanggal</th>
                                    <th>Deskripsi Kendala</th>
                                    <th>Lampiran</th>
                                    <th>Kesulitan</th>
                                    <th>Status</th>
                                    <?php if ($is_in_progress): ?>
                                    <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-center align-middle">
                                    <td><?= htmlspecialchars($ticket['code'] ?? '-') ?></td>
                                    <td class="text-start"><?= htmlspecialchars($ticket['user_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($ticket['category_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($ticket['division_name'] ?? '-') ?></td>
                                    <td><?= priorityBadge($ticket['division_priority'] ?? '') ?></td>
                                    <td><?= formatTanggal($ticket['created_at'] ?? '') ?></td>
                                    <td class="text-start"><?= htmlspecialchars($ticket['description'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($ticket_attachments)): ?>
                                            <?php foreach ($ticket_attachments as $att): ?>
                                                <a href="<?= getBaseUrl() . htmlspecialchars($att['filepath']) ?>" target="_blank" class="btn btn-warning btn-sm mb-1" title="<?= htmlspecialchars($att['filename']) ?>">
                                                    <i class="fas fa-file"></i>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= difficultyBadge($ticket['difficulty_level'] ?? 1) ?></td>
                                    <td><?= statusBadge($ticket['status'] ?? '') ?></td>
                                    <?php if ($is_in_progress): ?>
                                    <td class="text-nowrap">
                                        <button class="btn-action btn-action-danger me-1" data-bs-toggle="modal" data-bs-target="#difficultyModal"><i class="fas fa-gauge-high"></i></button>
                                        <button class="btn-action btn-action-check" data-bs-toggle="modal" data-bs-target="#resolveModal"><i class="fas fa-check"></i> Selesai</button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Chat Messages -->
            <div class="container-fluid pb-5">
                <div class="row">
                    <div class="col">
                        <div class="card shadow p-3 mb-5 bg-body rounded">
                            <div class="card-header">
                                <i class="fas fa-comments me-1" style="color: #8c57ff;"></i>
                                <span class="fw-semibold">Chat</span>
                            </div>
                            <div class="card-body">
                                <div class="chat-box" id="chatBox" style="height: 450px; overflow-y: auto; padding: 15px;">
                                    <?php if (empty($messages)): ?>
                                    <p class="text-muted text-center py-5">Belum ada pesan.</p>
                                    <?php else: ?>
                                        <?php foreach ($messages as $msg): ?>
                                            <?php $is_own = ($msg['sender_id'] ?? '') === $current_user_id; ?>
                                            <div class="d-flex flex-row <?= $is_own ? 'justify-content-end' : 'justify-content-start' ?> mb-3">
                                                <div style="max-width: 75%;">
                                                    <div class="small text-muted mb-1 <?= $is_own ? 'text-end' : '' ?>">
                                                        <?= htmlspecialchars($msg['sender_name'] ?? '-') ?> | <?= htmlspecialchars($ticket['code'] ?? '') ?>
                                                    </div>
                                                    <div class="p-2 rounded-3 <?= $is_own ? 'bg-primary text-white' : 'bg-light' ?>" style="word-wrap: break-word;">
                                                        <?= nl2br(htmlspecialchars($msg['message'] ?? '')) ?>
                                                    </div>
                                                    <?php if (!empty($msg['attachments'])): ?>
                                                    <div class="mt-1">
                                                        <?php foreach ($msg['attachments'] as $att): ?>
                                                            <a href="<?= getBaseUrl() . htmlspecialchars($att['filepath']) ?>" target="_blank" class="badge bg-secondary text-decoration-none me-1" title="<?= htmlspecialchars($att['filename']) ?>">
                                                                <i class="fas fa-paperclip"></i> <?= htmlspecialchars(potongTeks($att['filename'], 20)) ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div class="small text-muted mt-1 <?= $is_own ? 'text-end' : '' ?>">
                                                        <?= formatTanggal($msg['created_at'] ?? '') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if (($ticket['status'] ?? '') !== 'CLOSED'): ?>
                                <div class="border-top pt-3 mt-2">
                                    <form method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                                        <div class="d-flex gap-2">
                                            <input type="text" name="message" class="form-control form-control-lg" placeholder="Tulis Pesan" required autocomplete="off">
                                            <button type="submit" name="send_message" value="1" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                                        </div>
                                        <div>
                                            <input type="file" name="attachments[]" class="form-control form-control-sm" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
                                            <div class="form-text">Lampiran opsional. Maks total 75MB.</div>
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <?php if ($is_in_progress): ?>
    <div class="modal fade" id="resolveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Selesaikan Tiket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Catatan Penyelesaian <span class="text-danger">*</span></label>
                            <textarea name="resolution_note" class="form-control" rows="4" required minlength="10" placeholder="Jelaskan solusi yang diberikan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="resolve_ticket" value="1" class="btn btn-success">Selesaikan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="difficultyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Tingkat Kesulitan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <select name="difficulty_level" class="form-select">
                            <option value="1" <?= ($ticket['difficulty_level'] ?? 1) == 1 ? 'selected' : '' ?>>Mudah (10 poin)</option>
                            <option value="2" <?= ($ticket['difficulty_level'] ?? 1) == 2 ? 'selected' : '' ?>>Sedang (20 poin)</option>
                            <option value="3" <?= ($ticket['difficulty_level'] ?? 1) == 3 ? 'selected' : '' ?>>Sulit (30 poin)</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="set_difficulty" value="1" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
    var ticketId = '<?= htmlspecialchars($ticket_id) ?>';
    var currentUserId = '<?= htmlspecialchars(getCurrentUserId()) ?>';
    var baseUrl = '<?= getBaseUrl() ?>';
    var ticketCode = '<?= htmlspecialchars($ticket['code'] ?? '') ?>';

    function escapeHtml(text) {
        if (!text) return "";
        var div = document.createElement("div");
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
    function loadMessages() {
        fetch(baseUrl + 'page/chat/ajax_messages.php?id=' + ticketId)
            .then(r => r.json())
            .then(messages => {
                var box = document.getElementById('chatBox');
                if (!box) return;
                var html = '';
                if (messages.length === 0) {
                    html = '<p class="text-muted text-center py-5">Belum ada pesan.</p>';
                } else {
                    messages.forEach(function(msg) {
                        var isOwn = msg.sender_id === currentUserId;
                        html += '<div class="d-flex flex-row ' + (isOwn ? 'justify-content-end' : 'justify-content-start') + ' mb-3">';
                        html += '<div style="max-width: 75%;">';
                        html += '<div class="small text-muted mb-1 ' + (isOwn ? 'text-end' : '') + '">' + escapeHtml(msg.sender_name) + ' | ' + ticketCode + '</div>';
                        html += '<div class="p-2 rounded-3 ' + (isOwn ? 'bg-primary text-white' : 'bg-light') + '" style="word-wrap: break-word;">' + escapeHtml(msg.message).replace(/
/g, '<br>') + '</div>';
                        if (msg.attachments && msg.attachments.length > 0) {
                            html += '<div class="mt-1">';
                            msg.attachments.forEach(function(att) {
                                html += '<a href="' + baseUrl + escapeHtml(att.filepath) + '" target="_blank" class="badge bg-secondary text-decoration-none me-1"><i class="fas fa-paperclip"></i> ' + escapeHtml(att.filename).substring(0, 20) + '</a>';
                            });
                            html += '</div>';
                        }
                        html += '<div class="small text-muted mt-1 ' + (isOwn ? 'text-end' : '') + '">' + (msg.created_at || '') + '</div>';
                        html += '</div></div>';
                    });
                }
                box.innerHTML = html;
                box.scrollTop = box.scrollHeight;
            })
            .catch(function() {});
    }
    setInterval(loadMessages, 3000);
    document.addEventListener('DOMContentLoaded', function() {
        var box = document.getElementById('chatBox');
        if (box) box.scrollTop = box.scrollHeight;
    });
    </script>

    <?php include '../../includes/footer.php'; ?>
