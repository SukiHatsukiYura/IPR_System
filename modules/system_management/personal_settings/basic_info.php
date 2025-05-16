<?php
// 基本信息功能 - 系统管理/个人设置模块下的基本信息功能

include_once(__DIR__ . '/../../../database.php');

// 假设当前用户ID为1，后续可用 $_SESSION['user_id']
$user_id = 1;

// 查询用户信息及角色
$stmt = $pdo->prepare("
    SELECT u.*, r.name AS role_name
    FROM user u
    LEFT JOIN role r ON u.role_id = r.id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

function show_val($val)
{
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}
function show_gender($g)
{
    if ($g === null) return '';
    return $g == 1 ? '男' : '女';
}
function show_bool($v)
{
    if ($v === null) return '';
    return $v ? '是' : '否';
}
?>
<div class="user-basic-info-panel">
    <div class="panel-title">基本信息</div>
    <form class="info-form" autocomplete="off">
        <table class="info-table">
            <tr>
                <td class="label">*用户名：</td>
                <td><?= show_val($user['username']) ?></td>
                <td class="label">工号：</td>
                <td><?= show_val($user['job_number']) ?></td>
            </tr>
            <tr>
                <td class="label">姓名：</td>
                <td><?= show_val($user['real_name']) ?></td>
                <td class="label">*邮箱：</td>
                <td><?= show_val($user['email']) ?></td>
            </tr>
            <tr>
                <td class="label">英文名：</td>
                <td><?= show_val($user['english_name']) ?></td>
                <td class="label">性别：</td>
                <td><?= show_gender($user['gender']) ?></td>
            </tr>
            <tr>
                <td class="label">电话：</td>
                <td><?= show_val($user['phone']) ?></td>
                <td class="label">手机：</td>
                <td><?= show_val($user['mobile']) ?></td>
            </tr>
            <tr>
                <td class="label">出生日期：</td>
                <td><?= show_val($user['birthday']) ?></td>
                <td class="label">是否在职：</td>
                <td><?= show_bool($user['is_active']) ?></td>
            </tr>
            <tr>
                <td class="label">专业：</td>
                <td><?= show_val($user['major']) ?></td>
                <td class="label">创建时间：</td>
                <td><?= show_val($user['created_at']) ?></td>
            </tr>
            <tr>
                <td class="label">更新用户：</td>
                <td><?= show_val($user['updated_by']) ?></td>
                <td class="label">更新时间：</td>
                <td><?= show_val($user['updated_at']) ?></td>
            </tr>
            <tr>
                <td class="label">联系地址：</td>
                <td><?= show_val($user['address']) ?></td>
                <td class="label">工作地：</td>
                <td><?= show_val($user['workplace']) ?></td>
            </tr>
            <tr>
                <td class="label">*是否分代理人：</td>
                <td><?= show_bool($user['is_agent']) ?></td>
                <td class="label">用户角色：</td>
                <td><?= show_val($user['role_name']) ?></td>
            </tr>
        </table>
        <div class="info-bottom">
            <div>
                <span class="label">部门信息：</span>
                <?= show_val($user['department_info']) ?>
            </div>
            <div>
                <span class="label">备注：</span>
                <?= nl2br(show_val($user['remark'])) ?>
            </div>
        </div>
    </form>
</div>
<style>
    .user-basic-info-panel {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        padding: 18px 24px 24px 24px;
        margin: 18px;
        min-width: 900px;
    }

    .panel-title {
        font-size: 18px;
        color: #29b6b0;
        font-weight: bold;
        margin-bottom: 18px;
    }

    .info-form {
        width: 100%;
    }

    .info-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    .info-table .label {
        color: #666;
        text-align: right;
        width: 120px;
        padding-right: 8px;
        font-size: 14px;
        vertical-align: top;
    }

    .info-table td {
        font-size: 15px;
        padding: 4px 8px;
        vertical-align: top;
    }

    .info-bottom {
        margin-top: 18px;
        display: flex;
        gap: 40px;
    }

    .info-bottom .label {
        color: #666;
        font-size: 14px;
        margin-right: 8px;
    }
</style>