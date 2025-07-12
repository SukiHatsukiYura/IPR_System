<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 处理文件上传请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
    header('Content-Type: application/json');

    try {
        $document_id = intval($_POST['document_id']);
        $file_type = $_POST['file_type'] ?? '';
        $remarks = $_POST['remarks'] ?? '';

        // 验证必填字段
        if (!$document_id || !$file_type) {
            throw new Exception('请填写必要信息');
        }

        // 验证来文记录是否存在
        $stmt = $pdo->prepare("SELECT d.*, p.case_code, p.case_name FROM patent_incoming_document d LEFT JOIN patent_case_info p ON d.patent_case_info_id = p.id WHERE d.id = ?");
        $stmt->execute([$document_id]);
        $document = $stmt->fetch();
        if (!$document) {
            throw new Exception('来文记录不存在');
        }

        // 检查文件上传
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('文件上传失败');
        }

        $file = $_FILES['file'];
        $original_filename = $file['name'];
        $file_size = $file['size'];
        $mime_type = $file['type'];

        // 检查文件大小（10MB限制）
        if ($file_size > 10 * 1024 * 1024) {
            throw new Exception('文件大小不能超过10MB');
        }

        // 创建上传目录
        $upload_dir = __DIR__ . '/../../../uploads/patent_incoming_files/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        // 生成唯一文件名
        $ext = pathinfo($original_filename, PATHINFO_EXTENSION);
        $unique_filename = uniqid('incoming_') . '.' . $ext;
        $file_path = 'uploads/patent_incoming_files/' . $unique_filename;
        $full_path = $upload_dir . $unique_filename;

        // 移动上传文件
        if (!move_uploaded_file($file['tmp_name'], $full_path)) {
            throw new Exception('文件保存失败');
        }

        // 保存文件信息到数据库
        $sql = "INSERT INTO patent_incoming_document_file (
            patent_incoming_document_id, patent_case_info_id, file_type, 
            file_name, file_path, file_size, mime_type, upload_user_id, 
            remarks, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $document_id,
            $document['patent_case_info_id'],
            $file_type,
            $original_filename,
            $file_path,
            $file_size,
            $mime_type,
            $_SESSION['user_id'],
            $remarks
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'msg' => '文件上传成功']);
        } else {
            throw new Exception('数据库保存失败');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 处理获取文件列表请求
if (isset($_GET['action']) && $_GET['action'] === 'get_files') {
    header('Content-Type: application/json');
    $document_id = intval($_GET['document_id']);

    if (!$document_id) {
        echo json_encode(['success' => false, 'msg' => '无效的记录ID']);
        exit;
    }

    try {
        $sql = "SELECT f.*, u.real_name as uploader_name 
                FROM patent_incoming_document_file f
                LEFT JOIN user u ON f.upload_user_id = u.id
                WHERE f.patent_incoming_document_id = ?
                ORDER BY f.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$document_id]);
        $files = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $files]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '获取文件列表失败']);
    }
    exit;
}

// 处理文件删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    header('Content-Type: application/json');

    try {
        $file_id = intval($_POST['file_id']);

        if (!$file_id) {
            throw new Exception('无效的文件ID');
        }

        // 获取文件信息
        $stmt = $pdo->prepare("SELECT * FROM patent_incoming_document_file WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch();

        if (!$file) {
            throw new Exception('文件不存在');
        }

        // 删除物理文件
        $full_path = __DIR__ . '/../../../' . $file['file_path'];
        if (file_exists($full_path)) {
            @unlink($full_path);
        }

        // 删除数据库记录
        $stmt = $pdo->prepare("DELETE FROM patent_incoming_document_file WHERE id = ?");
        $result = $stmt->execute([$file_id]);

        if ($result) {
            echo json_encode(['success' => true, 'msg' => '文件删除成功']);
        } else {
            throw new Exception('删除失败');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 处理文件下载请求
if (isset($_GET['action']) && $_GET['action'] === 'download_file') {
    $file_id = intval($_GET['file_id']);

    if (!$file_id) {
        http_response_code(400);
        exit('无效的文件ID');
    }

    try {
        // 获取文件信息
        $stmt = $pdo->prepare("SELECT * FROM patent_incoming_document_file WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch();

        if (!$file) {
            http_response_code(404);
            exit('文件不存在');
        }

        $file_path = __DIR__ . '/../../../' . $file['file_path'];

        if (!file_exists($file_path)) {
            http_response_code(404);
            exit('文件不存在');
        }

        // 设置下载头
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        // 输出文件内容
        readfile($file_path);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        exit('下载失败');
    }
}

// 如果没有匹配的action，返回错误
http_response_code(400);
exit('无效的请求');
