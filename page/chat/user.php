<?php
require_once '../../config/function.php';
requireRole('USER');

$ticket_id = $_GET['id'] ?? '';
if (!$ticket_id) { header('Location: ' . getBaseUrl() . 'page/tiket/antrian.php'); exit; }

$msg_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
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

$ticket = getTicketById($ticket_id);
if (!$ticket) { header('Location: ' . getBaseUrl() . 'page/tiket/antrian.php'); exit; }

$messages = getChatMessages($ticket_id);
$current_user_id = getCurrentUserId();
$ticket_attachments = getTicketAttachments($ticket_id);

include '../../includes/header.php';
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <?php if ($msg_error): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($msg_error) ?></div>
            <?php endif; ?>

            <!-- Ticket Detail -->
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
                                    <th>Nama Support</th>
                                    <th>Kategori</th>
                                    <th>Divisi</th>
                                    <th>Prioritas</th>
                                    <th>Tanggal</th>
                                    <th>Deskripsi Kendala</th>
                                    <th>Lampiran</th>
                                    <th>Kesulitan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-center align-middle">
                                    <td><?= htmlspecialchars($ticket['code'] ?? '-') ?></td>
                                    <td class="text-start"><?= htmlspecialchars($ticket['staff_name'] ?? 'Belum ada') ?></td>
                                    <td><?= htmlspecialchars($ticket['category_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($ticket['division_name'] ?? '-') ?></td>
                                    <td><?= priorityBadge($ticket['division_priority'] ?? '') ?></td>
                                    <td><?= formatTanggal($ticket['created_at'] ?? '') ?></td>
                                    <td class="text-start"><?= htmlspecialchars($ticket['description'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($ticket_attachments)): ?>
                                            <?php foreach ($ticket_attachments as $att): ?>
                                                <a href="<?= getBaseUrl() . htmlspecialchars($att['filepath']) ?>" target="_blank" class="btn btn-warning btn-sm mb-1">
                                                    <i class="fas fa-file"></i>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= difficultyBadge($ticket['difficulty_level'] ?? 1) ?></td>
                                    <td><?= statusBadge($ticket['status'] ?? '') ?></td>
                                    <td>
                                        <a href="<?= getBaseUrl() ?>page/tiket/antrian.php" class="btn-action btn-action-view"><i class="fas fa-arrow-left"></i> Kembali</a>
                                    </td>
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
                                                            <a href="<?= getBaseUrl() . htmlspecialchars($att['filepath']) ?>" target="_blank" class="badge bg-secondary text-decoration-none me-1">
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

                                <?php if (($ticket['status'] ?? '') !== 'CLOSED' && ($ticket['status'] ?? '') !== 'RESOLVED'): ?>
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

    <script>
    var ticketId = '<?= htmlspecialchars($ticket_id) ?>';
    var currentUserId = '<?= htmlspecialchars(getCurrentUserId()) ?>';
    var baseUrl = '<?= getBaseUrl() ?>';
    var ticketCode = '<?= htmlspecialchars($ticket['code'] ?? '') ?>';
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
                        html += '<div class="small text-muted mb-1 ' + (isOwn ? 'text-end' : '') + '">' + msg.sender_name + ' | ' + ticketCode + '</div>';
                        html += '<div class="p-2 rounded-3 ' + (isOwn ? 'bg-primary text-white' : 'bg-light') + '" style="word-wrap: break-word;">' + msg.message.replace(/\n/g, '<br>') + '</div>';
                        if (msg.attachments && msg.attachments.length > 0) {
                            html += '<div class="mt-1">';
                            msg.attachments.forEach(function(att) {
                                html += '<a href="' + baseUrl + att.filepath + '" target="_blank" class="badge bg-secondary text-decoration-none me-1"><i class="fas fa-paperclip"></i> ' + att.filename.substring(0, 20) + '</a>';
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
