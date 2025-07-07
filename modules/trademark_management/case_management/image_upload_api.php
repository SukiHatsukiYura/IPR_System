<?php
// 商标图片上传API接口
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '用户未登录']);
    exit;
}

// 处理图片导入相关AJAX请求
if (isset($_GET['ajax']) && $_GET['ajax'] == 'image_list') {
    header('Content-Type: application/json');
    try {
        $sql = "SELECT t.id, t.case_code, t.case_name, t.trademark_image_path,
                (SELECT customer_name_cn FROM customer WHERE id = t.client_id) as client_name
                FROM trademark_case_info t 
                ORDER BY t.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $trademarks = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $trademarks
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '查询失败：' . $e->getMessage()
        ]);
    }
    exit;
}

// 处理图片上传请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_image') {
    header('Content-Type: application/json');

    function handle_trademark_image_upload($trademark_id)
    {
        if (!isset($_FILES['trademark_image']) || $_FILES['trademark_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('图片上传失败');
        }

        $file = $_FILES['trademark_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('只支持JPG、PNG、GIF格式的图片');
        }

        // 创建上传目录
        $upload_dir = __DIR__ . '/../../../uploads/trademark_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // 生成唯一文件名
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'trademark_' . $trademark_id . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('图片保存失败');
        }

        return [
            'path' => 'uploads/trademark_images/' . $filename,
            'name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type']
        ];
    }

    try {
        $trademark_id = intval($_POST['trademark_id'] ?? 0);
        if ($trademark_id <= 0) {
            throw new Exception('商标ID无效');
        }

        // 检查商标是否存在
        $check_stmt = $pdo->prepare("SELECT id FROM trademark_case_info WHERE id = ?");
        $check_stmt->execute([$trademark_id]);
        if (!$check_stmt->fetch()) {
            throw new Exception('商标案件不存在');
        }

        // 处理图片上传
        $image_info = handle_trademark_image_upload($trademark_id);

        // 更新数据库
        $update_sql = "UPDATE trademark_case_info SET 
                      trademark_image_path = ?, 
                      trademark_image_name = ?, 
                      trademark_image_size = ?, 
                      trademark_image_type = ?,
                      updated_at = NOW()
                      WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $result = $update_stmt->execute([
            $image_info['path'],
            $image_info['name'],
            $image_info['size'],
            $image_info['type'],
            $trademark_id
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => '图片上传成功']);
        } else {
            throw new Exception('数据库更新失败');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 处理图片删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_image') {
    header('Content-Type: application/json');

    try {
        $trademark_id = intval($_POST['trademark_id'] ?? 0);
        if ($trademark_id <= 0) {
            throw new Exception('商标ID无效');
        }

        // 获取当前图片信息
        $check_stmt = $pdo->prepare("SELECT id, trademark_image_path FROM trademark_case_info WHERE id = ?");
        $check_stmt->execute([$trademark_id]);
        $trademark = $check_stmt->fetch();

        if (!$trademark) {
            throw new Exception('商标案件不存在');
        }

        // 删除物理文件
        if (!empty($trademark['trademark_image_path'])) {
            $file_path = __DIR__ . '/../../../' . $trademark['trademark_image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // 清空数据库中的图片信息
        $update_sql = "UPDATE trademark_case_info SET 
                      trademark_image_path = NULL, 
                      trademark_image_name = NULL, 
                      trademark_image_size = NULL, 
                      trademark_image_type = NULL,
                      updated_at = NOW()
                      WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $result = $update_stmt->execute([$trademark_id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => '图片删除成功']);
        } else {
            throw new Exception('数据库更新失败');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 如果不是POST请求或没有action参数，返回错误
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => '无效的请求']);
exit;
