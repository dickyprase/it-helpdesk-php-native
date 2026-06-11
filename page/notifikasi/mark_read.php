<?php
require_once '../../config/function.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    markNotificationRead($_GET['id']);
}
http_response_code(204);
