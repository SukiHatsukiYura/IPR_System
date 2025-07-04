<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo '未登录或会话超时';
    exit;
}

try {
    if (isset($_GET['action']) && $_GET['action'] === 'batch') {
        // 批量下载
        if (!isset($_GET['ids']) || empty($_GET['ids'])) {
            http_response_code(400);
            echo '未指定要下载的文件';
            exit;
        }

        $file_ids = explode(',', $_GET['ids']);
        $file_ids = array_map('intval', $file_ids);
        $file_ids = array_filter($file_ids, function ($id) {
            return $id > 0;
        });

        if (empty($file_ids)) {
            http_response_code(400);
            echo '无效的文件ID';
            exit;
        }

        if (count($file_ids) > 50) {
            http_response_code(400);
            echo '一次最多只能下载50个文件';
            exit;
        }

        // 查询文件信息
        $placeholders = str_repeat('?,', count($file_ids) - 1) . '?';
        $sql = "SELECT f.*, c.case_code 
                FROM copyright_case_file f 
                LEFT JOIN copyright_case_info c ON f.copyright_case_info_id = c.id 
                WHERE f.id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($file_ids);
        $files = $stmt->fetchAll();

        if (empty($files)) {
            http_response_code(404);
            echo '未找到指定的文件';
            exit;
        }

        // 创建临时ZIP文件
        $zip = new ZipArchive();
        $zip_filename = tempnam(sys_get_temp_dir(), 'copyright_files_') . '.zip';

        if ($zip->open($zip_filename, ZipArchive::CREATE) !== TRUE) {
            http_response_code(500);
            echo '无法创建ZIP文件';
            exit;
        }

        $added_count = 0;
        foreach ($files as $file) {
            $file_path = __DIR__ . '/../../../' . $file['file_path'];
            if (file_exists($file_path)) {
                // 使用案件编号_文件名的格式
                $zip_entry_name = ($file['case_code'] ? $file['case_code'] . '_' : '') . $file['file_name'];
                // 确保文件名唯一
                $counter = 1;
                $original_name = $zip_entry_name;
                while ($zip->locateName($zip_entry_name) !== false) {
                    $pathinfo = pathinfo($original_name);
                    $zip_entry_name = $pathinfo['filename'] . '_' . $counter . '.' . $pathinfo['extension'];
                    $counter++;
                }
                $zip->addFile($file_path, $zip_entry_name);
                $added_count++;
            }
        }

        $zip->close();

        if ($added_count === 0) {
            unlink($zip_filename);
            http_response_code(404);
            echo '没有找到可下载的文件';
            exit;
        }

        // 输出ZIP文件
        $download_name = '版权文件_' . date('Y-m-d_H-i-s') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $download_name . '"');
        header('Content-Length: ' . filesize($zip_filename));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        readfile($zip_filename);
        unlink($zip_filename); // 删除临时文件
        exit;
    } else {
        // 单个文件下载
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            http_response_code(400);
            echo '无效的文件ID';
            exit;
        }

        $file_id = intval($_GET['id']);

        // 查询文件信息
        $stmt = $pdo->prepare("SELECT * FROM copyright_case_file WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch();

        if (!$file) {
            http_response_code(404);
            echo '文件不存在';
            exit;
        }

        $file_path = __DIR__ . '/../../../' . $file['file_path'];

        if (!file_exists($file_path)) {
            http_response_code(404);
            echo '文件不存在于服务器上';
            exit;
        }

        // 输出文件
        $mime_type = $file['mime_type'] ?: 'application/octet-stream';
        $file_name = $file['file_name'];

        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        readfile($file_path);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo '下载失败: ' . $e->getMessage();
    exit;
}
