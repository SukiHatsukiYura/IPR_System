<?php
session_start();
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trademark_id'])) {
    $trademark_id = intval($_POST['trademark_id']);

    // 验证商标是否存在
    $stmt = $pdo->prepare("SELECT id FROM trademark_case_info WHERE id = ?");
    $stmt->execute([$trademark_id]);

    if ($stmt->fetch()) {
        $_SESSION['edit_trademark_id'] = $trademark_id;
        echo 'success';
    } else {
        echo 'error';
    }
} else {
    echo 'error';
}
