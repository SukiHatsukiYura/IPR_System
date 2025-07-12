<?php
// 专利处理事项文件上传/删除/列表接口
include_once(__DIR__ . '/../../database.php');
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('未登录');
}

$action = $_REQUEST['action'] ?? '';
$task_id = intval($_REQUEST['task_id'] ?? 0);
$file_type = $_REQUEST['file_type'] ?? '';
$upload_dir = __DIR__ . '/../../uploads/patent_task_attachments/';
if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);

header('Content-Type: application/json');

if ($action === 'upload') {
    if ($task_id <= 0 || !$file_type || !isset($_FILES['file'])) {
        echo json_encode(['success' => false, 'msg' => '参数错误']);
        exit;
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'msg' => '上传失败']);
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $basename = uniqid('taskfile_') . ($ext ? ('.' . $ext) : '');
    $target = $upload_dir . $basename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'msg' => '保存失败']);
        exit;
    }

    $file_path = 'uploads/patent_task_attachments/' . $basename;
    $custom_name = trim($_POST['file_name'] ?? '');
    $save_name = $custom_name !== '' ? $custom_name : $file['name'];

    // 获取专利案件ID
    $stmt = $pdo->prepare("SELECT patent_case_info_id FROM patent_case_task WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    if (!$task) {
        echo json_encode(['success' => false, 'msg' => '处理事项不存在']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO patent_task_attachment (task_id, patent_case_info_id, file_type, file_name, original_file_name, file_path, file_size, mime_type, upload_user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $ok = $stmt->execute([
        $task_id,
        $task['patent_case_info_id'],
        $file_type,
        $save_name,
        $file['name'],
        $file_path,
        $file['size'],
        $file['type'],
        $_SESSION['user_id']
    ]);

    echo json_encode([
        'success' => $ok,
        'file' => [
            'file_name' => $save_name,
            'origin_name' => $file['name'],
            'file_path' => $file_path,
            'id' => $pdo->lastInsertId()
        ]
    ]);
    exit;
} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT file_path FROM patent_task_attachment WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && $row['file_path']) {
        $file = __DIR__ . '/../../' . $row['file_path'];
        if (is_file($file)) @unlink($file);
    }

    $pdo->prepare("DELETE FROM patent_task_attachment WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
} elseif ($action === 'list') {
    if ($task_id <= 0 || !$file_type) {
        echo json_encode(['success' => false, 'files' => []]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, file_name, original_file_name, file_path, created_at FROM patent_task_attachment WHERE task_id=? AND file_type=? ORDER BY id ASC");
    $stmt->execute([$task_id, $file_type]);
    $files = $stmt->fetchAll();

    echo json_encode(['success' => true, 'files' => $files]);
    exit;
}

echo json_encode(['success' => false, 'msg' => '无效操作']);
