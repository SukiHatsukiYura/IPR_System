<?php
// 设置要编辑的合同ID
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未登录或会话超时']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contract_id'])) {
    $contract_id = intval($_POST['contract_id']);

    // 验证合同是否存在
    $stmt = $pdo->prepare("SELECT id FROM contract WHERE id = ?");
    $stmt->execute([$contract_id]);

    if ($stmt->fetch()) {
        $_SESSION['edit_contract_id'] = $contract_id;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => '合同不存在']);
    }
} else {
    echo json_encode(['success' => false, 'msg' => '参数错误']);
}
