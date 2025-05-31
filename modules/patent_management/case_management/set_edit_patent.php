<?php
// 设置编辑专利ID的会话变量
require_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();

if (isset($_POST['patent_id'])) {
    $_SESSION['edit_patent_id'] = intval($_POST['patent_id']);
    echo 'success';
} else {
    http_response_code(400);
    echo 'error: missing patent_id';
}
