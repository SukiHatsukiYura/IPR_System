<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 上传文件处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
    header('Content-Type: application/json');

    try {
        $document_id = intval($_POST['document_id']);
        $file_type = $_POST['file_type'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        $upload_user_id = $_SESSION['user_id'];

        // 验证必填字段
        if (!$document_id) {
            echo json_encode(['success' => false, 'msg' => '缺少来文记录ID']);
            exit;
        }
        if (!$file_type) {
            echo json_encode(['success' => false, 'msg' => '请选择文件类型']);
            exit;
        }
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'msg' => '请选择要上传的文件']);
            exit;
        }

        // 验证来文记录是否存在并获取商标案件ID
        $check_stmt = $pdo->prepare("SELECT trademark_case_info_id FROM trademark_incoming_document WHERE id = ?");
        $check_stmt->execute([$document_id]);
        $document_info = $check_stmt->fetch();
        if (!$document_info) {
            echo json_encode(['success' => false, 'msg' => '来文记录不存在']);
            exit;
        }
        $trademark_case_info_id = $document_info['trademark_case_info_id'];

        $file = $_FILES['file'];
        $original_name = $file['name'];
        $file_size = $file['size'];
        $mime_type = $file['type'];

        // 检查文件大小（10MB限制）
        if ($file_size > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'msg' => '文件大小不能超过10MB']);
            exit;
        }

        // 检查文件类型
        $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', 'txt'];
        $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            echo json_encode(['success' => false, 'msg' => '不支持的文件格式']);
            exit;
        }

        // 创建上传目录
        $upload_dir = __DIR__ . '/../../../uploads/trademark_incoming_files/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                echo json_encode(['success' => false, 'msg' => '创建上传目录失败']);
                exit;
            }
        }

        // 生成唯一文件名
        $unique_name = 'trademark_incoming_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $unique_name;

        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            echo json_encode(['success' => false, 'msg' => '文件上传失败']);
            exit;
        }

        // 保存文件记录到数据库
        $sql = "INSERT INTO trademark_incoming_document_file 
                (trademark_incoming_document_id, trademark_case_info_id, file_type, file_name, 
                 file_path, file_size, mime_type, upload_user_id, remarks) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $relative_path = 'uploads/trademark_incoming_files/' . $unique_name;
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $document_id,
            $trademark_case_info_id,
            $file_type,
            $original_name,
            $relative_path,
            $file_size,
            $mime_type,
            $upload_user_id,
            $remarks
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'msg' => '文件上传成功']);
        } else {
            // 删除已上传的文件
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            echo json_encode(['success' => false, 'msg' => '保存文件记录失败']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '系统异常: ' . $e->getMessage()]);
    }
    exit;
}

// 获取文件列表
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_files') {
    header('Content-Type: application/json');

    try {
        $document_id = intval($_GET['document_id']);

        if (!$document_id) {
            echo json_encode(['success' => false, 'msg' => '缺少来文记录ID']);
            exit;
        }

        $sql = "SELECT f.*, u.real_name as uploader_name
                FROM trademark_incoming_document_file f
                LEFT JOIN user u ON f.upload_user_id = u.id
                WHERE f.trademark_incoming_document_id = ?
                ORDER BY f.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$document_id]);
        $files = $stmt->fetchAll();

        // 格式化文件数据
        foreach ($files as &$file) {
            $file['created_at'] = date('Y-m-d H:i', strtotime($file['created_at']));
        }

        echo json_encode(['success' => true, 'data' => $files]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '系统异常: ' . $e->getMessage()]);
    }
    exit;
}

// 下载文件
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'download_file') {
    try {
        $file_id = intval($_GET['file_id']);

        if (!$file_id) {
            die('缺少文件ID');
        }

        // 获取文件信息
        $stmt = $pdo->prepare("SELECT file_name, file_path FROM trademark_incoming_document_file WHERE id = ?");
        $stmt->execute([$file_id]);
        $file_info = $stmt->fetch();

        if (!$file_info) {
            die('文件不存在');
        }

        $file_path = __DIR__ . '/../../../' . $file_info['file_path'];

        if (!file_exists($file_path)) {
            die('文件已丢失');
        }

        // 设置下载头
        $file_name = $file_info['file_name'];
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . urlencode($file_name) . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        // 输出文件内容
        readfile($file_path);
    } catch (Exception $e) {
        die('下载失败: ' . $e->getMessage());
    }
    exit;
}

// 删除文件
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    header('Content-Type: application/json');

    try {
        $file_id = intval($_POST['file_id']);

        if (!$file_id) {
            echo json_encode(['success' => false, 'msg' => '缺少文件ID']);
            exit;
        }

        // 获取文件信息
        $stmt = $pdo->prepare("SELECT file_path FROM trademark_incoming_document_file WHERE id = ?");
        $stmt->execute([$file_id]);
        $file_info = $stmt->fetch();

        if (!$file_info) {
            echo json_encode(['success' => false, 'msg' => '文件记录不存在']);
            exit;
        }

        // 删除数据库记录
        $stmt = $pdo->prepare("DELETE FROM trademark_incoming_document_file WHERE id = ?");
        $result = $stmt->execute([$file_id]);

        if ($result) {
            // 删除物理文件
            $file_path = __DIR__ . '/../../../' . $file_info['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            echo json_encode(['success' => true, 'msg' => '删除成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '删除失败']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '系统异常: ' . $e->getMessage()]);
    }
    exit;
}

// 如果没有匹配的操作，返回错误
header('Content-Type: application/json');
echo json_encode(['success' => false, 'msg' => '无效的操作']);
