<?php
// 用户登录页面
session_start();
include_once 'database.php';

// 如果已登录，跳转到首页
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 登录处理逻辑
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = '请输入用户名和密码！';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM user WHERE (username = ? OR mobile = ?) AND is_active = 1 LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        if (!$user) {
            $error = '用户不存在或已停用！';
        } elseif (strtolower($user['password']) !== strtolower(md5($password))) {
            $error = '密码错误！';
        } else {
            // 登录成功
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            if (isset($_POST['remember'])) {
                setcookie('remember_username', $username, time() + 30 * 24 * 3600, '/');
            } else {
                setcookie('remember_username', '', time() - 3600, '/');
            }
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <title>用户登录 - 鸿鼎知识产权系统</title>
    <meta name="viewport" content="width=1024">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f6fa;
            font-family: "Microsoft YaHei", Arial, sans-serif;
            min-height: 100vh;
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            width: 400px;
            padding: 38px 38px 28px 38px;
            display: flex;
            flex-direction: column;
        }

        .login-title {
            font-size: 22px;
            color: #333;
            font-weight: bold;
            margin-bottom: 28px;
            text-align: center;
            letter-spacing: 2px;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .input-group {
            display: flex;
            align-items: center;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: #fafbfc;
            padding: 0 12px;
        }

        .input-group i {
            color: #b0b0b0;
            font-size: 18px;
            margin-right: 8px;
        }

        .login-input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 15px;
            padding: 12px 0;
            flex: 1;
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            color: #888;
            margin-bottom: 8px;
        }

        .login-btn {
            width: 100%;
            background: #29b6b0;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            padding: 12px 0;
            margin-top: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .login-btn:hover {
            background: #26a69a;
        }

        .login-links {
            display: flex;
            justify-content: space-between;
            margin-top: 18px;
            font-size: 13px;
        }

        .login-links a {
            color: #29b6b0;
            text-decoration: none;
        }

        .login-links a:hover {
            text-decoration: underline;
        }

        .login-footer {
            text-align: center;
            color: #aaa;
            font-size: 12px;
            margin-top: 38px;
        }

        .login-error {
            color: #f44336;
            text-align: center;
            margin-bottom: 10px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-title">用户登录</div>
            <?php if ($error): ?>
                <div class="login-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form class="login-form" method="post" action="" autocomplete="off">
                <div class="input-group">
                    <i>👤</i>
                    <input class="login-input" type="text" name="username" placeholder="用户名/手机号" required value="<?= htmlspecialchars($_COOKIE['remember_username'] ?? '') ?>">
                </div>
                <div class="input-group">
                    <i>🔒</i>
                    <input class="login-input" type="password" name="password" placeholder="您的密码" required>
                </div>
                <div class="login-options">
                    <label><input type="checkbox" name="remember" value="1" <?= isset($_COOKIE['remember_username']) ? 'checked' : '' ?>> 记住账号</label>
                    <a href="#">忘记密码?</a>
                </div>
                <button class="login-btn" type="submit">登录</button>
            </form>
            <div class="login-links">
                <span></span>
                <a href="register.php">立即注册</a>
            </div>
            <div class="login-footer">
                Copyright© 2025 广州市鸿鼎知识产权信息有限公司
            </div>
        </div>
    </div>
</body>

</html>