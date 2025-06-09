<?php
// 版权编辑-申请人文件上传API
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未登录或会话超时']);
    exit;
}

if (!isset($_GET['copyright_id']) || intval($_GET['copyright_id']) <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未指定版权ID']);
    exit;
}
$copyright_id = intval($_GET['copyright_id']);

// 验证版权是否存在
$copyright_stmt = $pdo->prepare("SELECT id FROM copyright_case_info WHERE id = ?");
$copyright_stmt->execute([$copyright_id]);
if (!$copyright_stmt->fetch()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未找到该版权信息']);
    exit;
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'upload') {
        try {
            // 文件上传
            $applicant_id = intval($_POST['copyright_case_applicant_id'] ?? 0);
            $file_type = $_POST['file_type'] ?? '';
            $custom_filename = $_POST['custom_filename'] ?? '';

            if ($applicant_id <= 0) {
                echo json_encode(['success' => false, 'message' => '无效的申请人ID']);
                exit;
            }

            if (empty($file_type)) {
                echo json_encode(['success' => false, 'message' => '未指定文件类型']);
                exit;
            }

            // 验证申请人是否存在
            $applicant_stmt = $pdo->prepare("SELECT id FROM copyright_case_applicant WHERE id = ? AND copyright_case_info_id = ?");
            $applicant_stmt->execute([$applicant_id, $copyright_id]);
            if (!$applicant_stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => '申请人不存在']);
                exit;
            }

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => '文件上传失败']);
                exit;
            }

            $file = $_FILES['file'];
            $upload_dir = __DIR__ . '/../../../uploads/copyright_applicant_files/';

            // 创建上传目录
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // 生成文件名
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (!empty($custom_filename)) {
                $filename = $custom_filename;
                if (!pathinfo($filename, PATHINFO_EXTENSION)) {
                    $filename .= '.' . $file_extension;
                }
            } else {
                $filename = time() . '_' . $file['name'];
            }

            $file_path = $upload_dir . $filename;
            $relative_path = 'uploads/copyright_applicant_files/' . $filename;

            // 移动文件
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                echo json_encode(['success' => false, 'message' => '文件保存失败']);
                exit;
            }

            // 保存文件信息到数据库
            $insert_sql = "INSERT INTO copyright_case_applicant_file 
                          (copyright_case_applicant_id, copyright_case_info_id, file_type, file_name, file_path, file_size, mime_type, upload_user_id) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $result = $insert_stmt->execute([
                $applicant_id,
                $copyright_id,
                $file_type,
                $filename,
                $relative_path,
                $file['size'],
                $file['type'],
                $_SESSION['user_id']
            ]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => '文件上传成功']);
            } else {
                // 删除已上传的文件
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                echo json_encode(['success' => false, 'message' => '文件信息保存失败']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '上传失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'list') {
        try {
            // 获取文件列表
            $applicant_id = intval($_POST['copyright_case_applicant_id'] ?? 0);
            $file_type = $_POST['file_type'] ?? '';

            if ($applicant_id <= 0) {
                echo json_encode(['success' => false, 'message' => '无效的申请人ID']);
                exit;
            }

            $sql = "SELECT * FROM copyright_case_applicant_file 
                    WHERE copyright_case_applicant_id = ? AND copyright_case_info_id = ?";
            $params = [$applicant_id, $copyright_id];

            if (!empty($file_type)) {
                $sql .= " AND file_type = ?";
                $params[] = $file_type;
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $files = $stmt->fetchAll();

            echo json_encode(['success' => true, 'files' => $files]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '获取文件列表失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        try {
            // 删除文件
            $file_id = intval($_POST['file_id'] ?? 0);

            if ($file_id <= 0) {
                echo json_encode(['success' => false, 'message' => '无效的文件ID']);
                exit;
            }

            // 获取文件信息
            $stmt = $pdo->prepare("SELECT * FROM copyright_case_applicant_file WHERE id = ? AND copyright_case_info_id = ?");
            $stmt->execute([$file_id, $copyright_id]);
            $file_info = $stmt->fetch();

            if (!$file_info) {
                echo json_encode(['success' => false, 'message' => '文件不存在']);
                exit;
            }

            // 删除数据库记录
            $delete_stmt = $pdo->prepare("DELETE FROM copyright_case_applicant_file WHERE id = ?");
            $result = $delete_stmt->execute([$file_id]);

            if ($result) {
                // 删除物理文件
                $file_path = __DIR__ . '/../../../' . $file_info['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                echo json_encode(['success' => true, 'message' => '文件删除成功']);
            } else {
                echo json_encode(['success' => false, 'message' => '文件删除失败']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => '未知的操作']);
    exit;
}

// 如果不是POST请求，返回错误
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => '无效的请求方法']);
