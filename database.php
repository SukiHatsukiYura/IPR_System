<?php
// 数据库配置文件，供全站php引用
// 兼容php7.1，使用PDO连接MySQL8.0，字符集utf8mb4
$isLocalorserver = 1; //1为本地，0为服务器
if ($isLocalorserver == 1) {
    $DB_HOST = 'localhost';      // 数据库主机
    $DB_NAME = 'IPR_SYSTEM';  // 数据库名，请替换为实际名称
    $DB_USER = 'root';      // 数据库用户名，请替换为实际用户名
    $DB_PASS = 'root';  // 数据库密码，请替换为实际密码
} else {
    $DB_HOST = 'localhost';      // 数据库主机
    $DB_NAME = 'IPR_SYSTEM';  // 数据库名，请替换为实际名称
    $DB_USER = 'root';      // 数据库用户名，请替换为实际用户名
    $DB_PASS = 'windowsX999';  // 数据库密码，请替换为实际密码
}
// 版本号
$version = '1.4.0';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // 抛出异常
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 默认关联数组
            PDO::ATTR_EMULATE_PREPARES => false, // 禁用模拟预处理
        ]
    );
} catch (PDOException $e) {
    // 生产环境可隐藏详细信息
    die('数据库连接失败: ' . $e->getMessage());
}
// 禁止直接访问
if (!function_exists('check_access_via_framework')) {
    /**
     * 检查是否通过框架访问
     * 如果通过框架访问，则不进行任何操作
     * 如果未通过框架访问，则输出禁止直接访问的HTML
     */
    function check_access_via_framework()
    {
        if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'index.php') === false) {
            // 输出完整HTML，保证body存在
            echo '<!DOCTYPE html>
                    <html lang="zh-CN">
                    <head>
                        <meta charset="UTF-8">
                        <title>禁止直接访问</title>
                        <style>
                            body { display:flex; align-items:center; justify-content:center; height:100vh; font-size:22px; color:#f44336; background:#fff; flex-direction:column; }
                        </style>
                    </head>
                    <body>
                        <div>禁止直接访问此页面，请通过系统主界面进入！<br>3秒后返回首页...</div>
                        <script>
                            setTimeout(function() {
                                var path = window.location.pathname;
                                var idx = path.indexOf("/modules/");
                                if (idx !== -1) {
                                    var root = path.substring(0, idx);
                                    window.top.location.href = root + "/index.php";
                                } else {
                                    window.top.location.href = "/index.php";
                                }
                            }, 3000);
                        </script>
                    </body>
                    </html>';
            exit;
        }
    }
}
