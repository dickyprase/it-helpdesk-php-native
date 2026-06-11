<?php
ob_start();
header('Content-Type: application/json');
set_error_handler(function($errno, $errstr, $errfile, $errline) { return true; });

require_once '../../config/function.php';

if (!isLoggedIn() || getCurrentUserRole() !== 'MANAGER') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}
ob_end_clean();

$event_type = trim($_POST['event_type'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (empty($event_type) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Event type dan nomor test wajib diisi']);
    exit;
}

$dummy_vars = [
    'kode_tiket' => 'TKT-TEST-001',
    'judul_tiket' => 'Test Ticket',
    'nama_user' => 'User Test',
    'nama_staff' => 'Staff Test',
    'kategori' => 'Test Category',
    'status' => 'OPEN'
];

$message = renderWaTemplate($event_type, $dummy_vars);
if (!$message) {
    echo json_encode(['success' => false, 'message' => 'Template tidak ditemukan untuk event: ' . $event_type]);
    exit;
}

$setting = getWaSetting();
if (!$setting || empty($setting['gateway_url']) || empty($setting['api_key'])) {
    echo json_encode(['success' => false, 'message' => 'Konfigurasi gateway belum diisi']);
    exit;
}

$gateway_url = rtrim($setting['gateway_url'], '/');
$api_key = $setting['api_key'];
$url = $gateway_url . '/api/send';
$payload = json_encode(['to' => $phone, 'message' => $message]);

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-api-key: ' . $api_key],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        echo json_encode(['success' => false, 'message' => 'CURL Error: ' . $curl_error]);
        exit;
    }
} else {
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nx-api-key: " . $api_key . "\r\n",
            'content' => $payload,
            'timeout' => 10,
            'ignore_errors' => true
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    $http_code = 0;
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                $http_code = (int)$matches[1];
            }
        }
    }

    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Gagal terhubung ke gateway']);
        exit;
    }
}

$data = json_decode($response, true);
echo json_encode([
    'success' => ($http_code === 200 && isset($data['success']) && $data['success'] === true),
    'message' => $data['message'] ?? 'HTTP ' . $http_code,
    'template_preview' => $message,
    'response' => $data
]);
