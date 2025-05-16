<?php
// 用户注册页面
include_once 'database.php';

$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $real_name = trim($_POST['real_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $agency_name = trim($_POST['agency_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $agree = isset($_POST['agree']);

    if ($username === '' || $mobile === '' || $password === '' || $confirm_password === '' || $real_name === '' || $agency_name === '' || $email === '') {
        $error = '请填写所有必填项！';
    } elseif ($password !== $confirm_password) {
        $error = '两次输入的密码不一致！';
    } elseif (!$agree) {
        $error = '请阅读并同意服务协议！';
    } else {
        // 检查用户名和手机号唯一
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ? OR mobile = ? LIMIT 1");
        $stmt->execute([$username, $mobile]);
        if ($stmt->fetch()) {
            $error = '用户名或手机号已注册！';
        } else {
            // 注册用户，默认角色user，is_active=1
            $stmt = $pdo->prepare("INSERT INTO user (username, password, real_name, mobile, email, department_info, is_active, role_id, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())");
            // 查找user角色id
            $roleStmt = $pdo->prepare("SELECT id FROM role WHERE name = 'user' LIMIT 1");
            $roleStmt->execute();
            $role = $roleStmt->fetch();
            $role_id = $role ? $role['id'] : 2;
            $ok = $stmt->execute([
                $username,
                md5($password),
                $real_name,
                $mobile,
                $email,
                $agency_name,
                $role_id
            ]);
            if ($ok) {
                $success = true;
            } else {
                $error = '注册失败，请重试！';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <title>鸿鼎知识产权系统 - 用户注册</title>
    <meta name="viewport" content="width=1024">
    <style>
        body {
            background: #f3f3f3;
            margin: 0;
            font-family: "Microsoft YaHei", Arial, sans-serif;
        }

        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .register-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            width: 700px;
            padding: 38px 48px 38px 48px;
            margin: 48px 0;
        }

        .register-title {
            font-size: 26px;
            color: #222;
            font-weight: bold;
            text-align: center;
            margin-bottom: 18px;
            letter-spacing: 2px;
        }

        .register-link {
            text-align: right;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .register-link a {
            color: #29b6b0;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .register-form {
            width: 100%;
        }

        .form-section {
            margin-bottom: 28px;
        }

        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
        }

        .form-label {
            width: 140px;
            text-align: right;
            color: #444;
            font-size: 15px;
            margin-right: 18px;
        }

        .form-input {
            flex: 1;
        }

        .form-input input {
            width: 100%;
            padding: 10px 12px;
            font-size: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: #fafbfc;
        }

        .form-input input[type="password"] {
            letter-spacing: 2px;
        }

        .form-input input:focus {
            border-color: #29b6b0;
            outline: none;
        }

        .form-divider {
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 32px 0 24px 0;
        }

        .form-agree {
            margin: 18px 0 0 0;
            font-size: 14px;
            color: #444;
        }

        .form-agree input {
            margin-right: 6px;
        }

        .form-agree .protocol {
            color: #f44336;
            text-decoration: underline;
            cursor: pointer;
        }

        .register-btn {
            width: 100%;
            background: #29b6b0;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 17px;
            padding: 13px 0;
            margin-top: 18px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .register-btn:hover {
            background: #26a69a;
        }

        .register-error {
            color: #f44336;
            text-align: center;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .register-success {
            color: #29b6b0;
            text-align: center;
            font-size: 20px;
            margin-bottom: 18px;
            margin-top: 30px;
        }

        .register-success-btn {
            display: block;
            margin: 0 auto;
            background: #29b6b0;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 17px;
            padding: 13px 38px;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            text-align: center;
        }

        .register-success-btn:hover {
            background: #26a69a;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-title">鸿鼎知识产权系统 - 用户注册</div>
            <?php if ($success): ?>
                <div class="register-success">注册成功！</div>
                <a class="register-success-btn" href="login.php">返回登录</a>
            <?php else: ?>
                <div class="register-link">已有账户？<a href="login.php">前往登录 &gt;</a></div>
                <?php if ($error): ?>
                    <div class="register-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form class="register-form" method="post" action="register.php" autocomplete="off">
                    <div class="form-section">
                        <div class="form-row">
                            <div class="form-label">用户名：</div>
                            <div class="form-input"><input type="text" name="username" placeholder="请输入用户名" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-label">管理员手机：</div>
                            <div class="form-input"><input type="text" name="mobile" placeholder="请输入您的手机号" required value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-label">姓名：</div>
                            <div class="form-input"><input type="text" name="real_name" placeholder="请输入您的姓名" required value="<?= htmlspecialchars($_POST['real_name'] ?? '') ?>"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-label">密码：</div>
                            <div class="form-input"><input type="password" name="password" placeholder="请输入密码" required></div>
                        </div>
                        <div class="form-row">
                            <div class="form-label">确认密码：</div>
                            <div class="form-input"><input type="password" name="confirm_password" placeholder="请确认密码" required></div>
                        </div>
                    </div>
                    <hr class="form-divider">
                    <div class="form-section">
                        <div class="form-row">
                            <div class="form-label">代理机构名称：</div>
                            <div class="form-input"><input type="text" name="agency_name" placeholder="请输入代理机构名称" required value="<?= htmlspecialchars($_POST['agency_name'] ?? '') ?>"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-label">管理员邮箱：</div>
                            <div class="form-input"><input type="email" name="email" placeholder="请输入您的邮箱" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></div>
                        </div>
                    </div>
                    <div class="form-agree">
                        <label><input type="checkbox" name="agree" required <?= isset($_POST['agree']) ? 'checked' : '' ?>>我已阅读并同意 <span class="protocol">《服务协议》</span></label>
                    </div>
                    <button class="register-btn" type="submit">同意条款并注册</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>