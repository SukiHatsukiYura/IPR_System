<?php
session_start();
include_once 'database.php';
include_once 'common/functions.php';

// 记录退出日志（在清除session之前）
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $session_id = session_id();
    log_user_logout($pdo, $user_id, $session_id);
}

// 清除所有session
$_SESSION = array();
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();
header('Location: login.php');
exit;
