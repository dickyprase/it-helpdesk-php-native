<?php
// ============================================================
// config.php — Konfigurasi Database & Aplikasi
// ============================================================

// Base URL aplikasi (otomatis mendeteksi folder project)
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$rootDir   = str_replace('\\', '/', dirname(__DIR__));
$rootName  = basename($rootDir);
$url       = $protocol . '://' . $http_host . '/' . $rootName . '/';

// Koneksi MySQL
$host     = 'localhost';
$username = 'root';
$password = 'hujan123';
$database = 'helpdesk';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error);
}

// Set charset
mysqli_set_charset($conn, 'utf8mb4');
