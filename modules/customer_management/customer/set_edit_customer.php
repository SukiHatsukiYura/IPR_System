<?php
// 设置编辑客户ID的会话变量
require_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();

if (isset($_POST['customer_id'])) {
    $_SESSION['edit_customer_id'] = intval($_POST['customer_id']);
    echo 'success';
} else {
    http_response_code(400);
    echo 'error: missing customer_id';
}
