<?php

// Baris paling atas di function.php — ganti sesuai server REST API Anda
$API_BASE_URL = "https://api.zorroserver.net/api/v1/";

/**
 * Kirim request ke REST API (fungsi dasar).
 * Otomatis kirim Bearer token dari $_SESSION jika ada.
 */
function api_request($method, $endpoint, $data = null) {
    global $API_BASE_URL;
 
    $url = $API_BASE_URL . $endpoint;
    $ch = curl_init();
 
    $headers = ['Content-Type: application/json'];
    if (isset($_SESSION['token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
    }
 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
 
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
 
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
 
    if ($curlError) {
        return ['error' => true, 'message' => 'Gagal terhubung ke server: ' . $curlError];
    }
 
    return json_decode($response, true);
}
 
function api_post($endpoint, $data) { return api_request('POST', $endpoint, $data); }
 
/**
 * Login — kirim email+password ke API, simpan token di $_SESSION.
 */
function api_login($email, $password) {
    $result = api_post('/auth/login', [
        'email'    => $email,
        'password' => $password,
    ]);
 
    if (!$result['error'] && isset($result['data']['token'])) {
        $_SESSION['token'] = $result['data']['token'];
        $_SESSION['user']  = $result['data']['user'];
        return true;
    }
 
    $_SESSION['login_error'] = $result['message'] ?? 'Login gagal';
    return false;
}
 
function is_logged_in() { return isset($_SESSION['token']); }
function get_user_role() { return $_SESSION['user']['role'] ?? ''; }
function get_current_user_data() { return $_SESSION['user'] ?? null; }
 
function require_login() {
    if (!is_logged_in()) { header('Location: login.php'); exit; }
}

?>