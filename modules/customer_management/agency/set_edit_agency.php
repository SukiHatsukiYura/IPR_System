<?php
session_start();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $_SESSION['edit_agency_id'] = $id;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => '参数错误']);
    }
} else {
    echo json_encode(['success' => false, 'msg' => '非法请求']);
}
