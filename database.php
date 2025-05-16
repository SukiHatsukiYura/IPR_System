<?php
// 数据库配置文件，供全站php引用
// 兼容php7.1，使用PDO连接MySQL8.0，字符集utf8mb4

$DB_HOST = 'localhost';      // 数据库主机
$DB_NAME = 'IPR_SYSTEM';  // 数据库名，请替换为实际名称
$DB_USER = 'root';      // 数据库用户名，请替换为实际用户名
$DB_PASS = 'root';  // 数据库密码，请替换为实际密码

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
