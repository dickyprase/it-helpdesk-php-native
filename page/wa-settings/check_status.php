<?php
require_once '../../config/function.php';
requireRole('MANAGER');

header('Content-Type: application/json');

$setting = getWaSetting();
echo json_encode(['status' => $setting['connection_status'] ?? 'disconnected']);
