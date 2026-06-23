<?php
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$rootDir   = str_replace('\\', '/', dirname(__DIR__));
$rootName  = basename($rootDir);
$nginxRoot = '/var/www/' . $rootName;
$isDirectRoot = ($rootDir === $nginxRoot);
$url = $protocol . '://' . $http_host . '/' . ($isDirectRoot ? '' : $rootName . '/');
$host     = 'localhost';
$username = 'root';
$password = 'hujan123';
$database = 'helpdesk';
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) { die('DB error'); }
mysqli_set_charset($conn, 'utf8mb4');
