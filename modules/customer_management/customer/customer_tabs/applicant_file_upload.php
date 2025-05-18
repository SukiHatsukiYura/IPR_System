<?php
// 申请人文件上传/删除/列表接口
include_once(__DIR__ . '/../../../../database.php');
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('未登录');
}
$action = $_REQUEST['action'] ?? '';
$customer_id = intval($_REQUEST['customer_id'] ?? 0);
$applicant_id = intval($_REQUEST['applicant_id'] ?? 0);
$file_type = $_REQUEST['file_type'] ?? '';
$upload_dir = __DIR__ . '/../../../../uploads/applicant/';
if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);
header('Content-Type: application/json');
if ($action === 'upload') {
    if ($applicant_id <= 0 || !$file_type || !isset($_FILES['file'])) {
        echo json_encode(['success' => false, 'msg' => '参数错误']);
        exit;
    }
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'msg' => '上传失败']);
        exit;
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $basename = uniqid('appfile_') . ($ext ? ('.' . $ext) : '');
    $target = $upload_dir . $basename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'msg' => '保存失败']);
        exit;
    }
    $file_path = 'uploads/applicant/' . $basename;
    $custom_name = trim($_POST['file_name'] ?? '');
    $save_name = $custom_name !== '' ? $custom_name : $file['name'];
    $stmt = $pdo->prepare("INSERT INTO applicant_file (applicant_id, file_type, file_name, file_path, created_at) VALUES (?, ?, ?, ?, NOW())");
    $ok = $stmt->execute([$applicant_id, $file_type, $save_name, $file_path]);
    echo json_encode(['success' => $ok, 'file' => ['file_name' => $save_name, 'origin_name' => $file['name'], 'file_path' => $file_path, 'id' => $pdo->lastInsertId()]]);
    exit;
} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false]);
        exit;
    }
    $stmt = $pdo->prepare("SELECT file_path FROM applicant_file WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && $row['file_path']) {
        $file = __DIR__ . '/../../../../' . $row['file_path'];
        if (is_file($file)) @unlink($file);
    }
    $pdo->prepare("DELETE FROM applicant_file WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
} elseif ($action === 'list') {
    if ($applicant_id <= 0 || !$file_type) {
        echo json_encode(['success' => false, 'files' => []]);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id, file_name, file_path, created_at FROM applicant_file WHERE applicant_id=? AND file_type=? ORDER BY id ASC");
    $stmt->execute([$applicant_id, $file_type]);
    $files = $stmt->fetchAll();
    echo json_encode(['success' => true, 'files' => $files]);
    exit;
}
echo json_encode(['success' => false, 'msg' => '无效操作']);
