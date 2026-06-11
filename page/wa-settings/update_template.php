<?php
require_once '../../config/function.php';
requireRole('MANAGER');

header('Content-Type: application/json');

$event_type = trim($_POST['event_type'] ?? '');
$template_body = trim($_POST['template_body'] ?? '');

if (empty($event_type) || empty($template_body)) {
    echo json_encode(['success' => false, 'message' => 'Event type dan template wajib diisi']);
    exit;
}

global $conn;
$event_type = mysqli_real_escape_string($conn, $event_type);
$template_body = mysqli_real_escape_string($conn, $template_body);

$query = "UPDATE `Notification_Template` SET template_body = '$template_body', updated_at = NOW() WHERE event_type = '$event_type'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_affected_rows($conn) >= 0) {
    echo json_encode(['success' => true, 'message' => 'Template berhasil disimpan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan template: ' . mysqli_error($conn)]);
}
