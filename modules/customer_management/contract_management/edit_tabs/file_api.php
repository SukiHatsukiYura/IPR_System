<?php
// 合同编辑-文件管理API接口
include_once(__DIR__ . '/../../../../database.php');
include_once(__DIR__ . '/../../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未登录或会话超时']);
    exit;
}

if (!isset($_GET['contract_id']) || intval($_GET['contract_id']) <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未指定合同ID']);
    exit;
}
$contract_id = intval($_GET['contract_id']);

// 验证合同是否存在
$contract_stmt = $pdo->prepare("SELECT id FROM contract WHERE id = ?");
$contract_stmt->execute([$contract_id]);
if (!$contract_stmt->fetch()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未找到该合同信息']);
    exit;
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'upload') {
        try {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('文件上传失败');
            }

            $file = $_FILES['file'];
            $file_type = $_POST['file_type'] ?? '其他';
            $custom_filename = $_POST['custom_filename'] ?? '';

            // 验证文件类型
            $allowed_types = ['合同正本', '合同副本', '补充协议', '其他'];
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('无效的文件类型');
            }

            // 创建上传目录
            $upload_dir = __DIR__ . '/../../../../uploads/contract_files/' . $contract_id . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // 确定文件名
            $original_name = $file['name'];
            $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);

            if (!empty($custom_filename)) {
                $filename = $custom_filename;
                if (!pathinfo($filename, PATHINFO_EXTENSION)) {
                    $filename .= '.' . $file_extension;
                }
            } else {
                $filename = $original_name;
            }

            // 确保文件名唯一
            $counter = 1;
            $base_filename = pathinfo($filename, PATHINFO_FILENAME);
            $final_filename = $filename;

            while (file_exists($upload_dir . $final_filename)) {
                $final_filename = $base_filename . '_' . $counter . '.' . $file_extension;
                $counter++;
            }

            $file_path = $upload_dir . $final_filename;

            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                throw new Exception('文件保存失败');
            }

            // 保存到数据库
            $relative_path = 'uploads/contract_files/' . $contract_id . '/' . $final_filename;

            $stmt = $pdo->prepare("
                INSERT INTO contract_file (
                    contract_id, file_type, file_name, file_path, 
                    file_size, mime_type, upload_user_id, upload_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())
            ");

            $result = $stmt->execute([
                $contract_id,
                $file_type,
                $final_filename,
                $relative_path,
                $file['size'],
                $file['type'],
                $_SESSION['user_id']
            ]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => '文件上传成功']);
            } else {
                throw new Exception('数据库保存失败');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'list') {
        try {
            $file_type = $_POST['file_type'] ?? '';

            $sql = "SELECT cf.*, u.real_name as uploader_name 
                    FROM contract_file cf 
                    LEFT JOIN user u ON cf.upload_user_id = u.id 
                    WHERE cf.contract_id = ?";
            $params = [$contract_id];

            if (!empty($file_type)) {
                $sql .= " AND cf.file_type = ?";
                $params[] = $file_type;
            }

            $sql .= " ORDER BY cf.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $files = $stmt->fetchAll();

            echo json_encode(['success' => true, 'files' => $files]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        try {
            $file_id = intval($_POST['file_id'] ?? 0);
            if ($file_id <= 0) {
                throw new Exception('无效的文件ID');
            }

            // 获取文件信息
            $stmt = $pdo->prepare("SELECT file_path FROM contract_file WHERE id = ? AND contract_id = ?");
            $stmt->execute([$file_id, $contract_id]);
            $file_info = $stmt->fetch();

            if (!$file_info) {
                throw new Exception('文件不存在');
            }

            // 删除数据库记录
            $stmt = $pdo->prepare("DELETE FROM contract_file WHERE id = ? AND contract_id = ?");
            $result = $stmt->execute([$file_id, $contract_id]);

            if ($result) {
                // 删除物理文件
                $file_path = __DIR__ . '/../../../../' . $file_info['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                echo json_encode(['success' => true, 'message' => '文件删除成功']);
            } else {
                throw new Exception('删除失败');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => '未知操作']);
    exit;
}

// 如果不是POST请求或没有action参数，返回错误
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => '无效的请求']);
exit;
