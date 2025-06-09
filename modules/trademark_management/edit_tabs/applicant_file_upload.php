<?php
// 商标申请人文件上传/删除/列表接口
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('未登录');
}

$action = $_REQUEST['action'] ?? '';
$trademark_case_applicant_id = intval($_REQUEST['trademark_case_applicant_id'] ?? 0);
$file_type = $_REQUEST['file_type'] ?? '';

// 上传目录
$upload_dir = __DIR__ . '/../../../uploads/trademark_applicant_files/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

header('Content-Type: application/json');

if ($action === 'upload') {
    // 检查必要参数
    if (!$trademark_case_applicant_id || !$file_type) {
        echo json_encode(['success' => false, 'message' => '缺少必要参数']);
        exit;
    }

    // 检查文件上传
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '文件上传失败']);
        exit;
    }

    // 验证申请人是否存在
    $stmt = $pdo->prepare("SELECT trademark_case_info_id FROM trademark_case_applicant WHERE id = ?");
    $stmt->execute([$trademark_case_applicant_id]);
    $applicant = $stmt->fetch();
    if (!$applicant) {
        echo json_encode(['success' => false, 'message' => '申请人不存在']);
        exit;
    }

    $file = $_FILES['file'];
    $original_filename = $file['name'];
    $file_size = $file['size'];
    $mime_type = $file['type'];

    // 检查是否有自定义文件名
    $custom_filename = $_REQUEST['custom_filename'] ?? '';
    $display_filename = $original_filename; // 默认使用原始文件名

    if (!empty($custom_filename)) {
        // 使用自定义文件名
        $display_filename = $custom_filename;

        // 如果自定义文件名没有扩展名，则添加原文件的扩展名
        $original_ext = pathinfo($original_filename, PATHINFO_EXTENSION);
        if (!empty($original_ext) && pathinfo($display_filename, PATHINFO_EXTENSION) === '') {
            $display_filename .= '.' . $original_ext;
        }
    }

    // 生成唯一的物理文件名（用于存储）
    $ext = pathinfo($original_filename, PATHINFO_EXTENSION);
    $unique_filename = uniqid() . '.' . $ext;
    $file_path = $upload_dir . $unique_filename;

    // 移动文件
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // 保存到数据库，file_name字段存储显示文件名
        $stmt = $pdo->prepare("INSERT INTO trademark_case_applicant_file 
            (trademark_case_applicant_id, trademark_case_info_id, file_type, file_name, file_path, file_size, mime_type, upload_user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $result = $stmt->execute([
            $trademark_case_applicant_id,
            $applicant['trademark_case_info_id'],
            $file_type,
            $display_filename, // 使用显示文件名（可能是自定义的）
            'uploads/trademark_applicant_files/' . $unique_filename,
            $file_size,
            $mime_type,
            $_SESSION['user_id']
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => '文件上传成功']);
        } else {
            // 删除已上传的文件
            @unlink($file_path);
            echo json_encode(['success' => false, 'message' => '数据库保存失败']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '文件保存失败']);
    }
} elseif ($action === 'delete') {
    $file_id = intval($_REQUEST['file_id'] ?? 0);
    if (!$file_id) {
        echo json_encode(['success' => false, 'message' => '缺少文件ID']);
        exit;
    }

    // 获取文件信息
    $stmt = $pdo->prepare("SELECT file_path FROM trademark_case_applicant_file WHERE id = ?");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch();

    if ($file) {
        // 删除数据库记录
        $stmt = $pdo->prepare("DELETE FROM trademark_case_applicant_file WHERE id = ?");
        if ($stmt->execute([$file_id])) {
            // 删除物理文件
            $full_path = __DIR__ . '/../../../' . $file['file_path'];
            @unlink($full_path);
            echo json_encode(['success' => true, 'message' => '文件删除成功']);
        } else {
            echo json_encode(['success' => false, 'message' => '删除失败']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '文件不存在']);
    }
} elseif ($action === 'list') {
    if (!$trademark_case_applicant_id || !$file_type) {
        echo json_encode(['success' => false, 'message' => '缺少必要参数']);
        exit;
    }

    // 获取文件列表
    $stmt = $pdo->prepare("SELECT id, file_name, file_path, file_size, created_at 
        FROM trademark_case_applicant_file 
        WHERE trademark_case_applicant_id = ? AND file_type = ? 
        ORDER BY created_at DESC");
    $stmt->execute([$trademark_case_applicant_id, $file_type]);
    $files = $stmt->fetchAll();

    echo json_encode(['success' => true, 'files' => $files]);
} else {
    echo json_encode(['success' => false, 'message' => '无效的操作']);
}
