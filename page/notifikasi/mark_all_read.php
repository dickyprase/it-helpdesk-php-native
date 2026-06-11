<?php
require_once '../../config/function.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    markAllNotificationsRead(getCurrentUserId());
}
http_response_code(204);
