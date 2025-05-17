<?php
// 基本信息功能 - 系统管理/个人设置模块下的基本信息功能

include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// 处理保存请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $fields = [
        'username',
        'real_name',
        'english_name',
        'job_number',
        'email',
        'gender',
        'phone',
        'mobile',
        'birthday',
        'is_active',
        'major',
        'updated_by',
        'address',
        'is_agent',
        'role_name',
        'workplace',
        'department_info',
        'remark'
    ];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }
    // 必填项校验
    if ($data['username'] === '' || $data['real_name'] === '' || $data['email'] === '' || $data['is_agent'] === '') {
        echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
        exit;
    }
    // 邮箱简单校验
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'msg' => '邮箱格式不正确']);
        exit;
    }
    // 检查用户名唯一性
    $stmt = $pdo->prepare('SELECT id FROM user WHERE username=? AND id<>? LIMIT 1');
    $stmt->execute([$data['username'], $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'msg' => '用户名已存在']);
        exit;
    }
    // 更新数据库
    $data['gender'] = ($data['gender'] === '') ? null : $data['gender'];
    $data['is_active'] = ($data['is_active'] === '') ? null : $data['is_active'];
    $data['is_agent'] = ($data['is_agent'] === '') ? null : $data['is_agent'];
    $data['birthday'] = ($data['birthday'] === '') ? null : $data['birthday'];
    $sql = "UPDATE user SET username=?, real_name=?, english_name=?, job_number=?, email=?, gender=?, phone=?, mobile=?, birthday=?, is_active=?, major=?, updated_by=?, address=?, is_agent=?, workplace=?, department_info=?, remark=?, updated_at=NOW() WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        $data['username'],
        $data['real_name'],
        $data['english_name'],
        $data['job_number'],
        $data['email'],
        $data['gender'],
        $data['phone'],
        $data['mobile'],
        $data['birthday'],
        $data['is_active'],
        $data['major'],
        $_SESSION['username'],
        $data['address'],
        $data['is_agent'],
        $data['workplace'],
        $data['department_info'],
        $data['remark'],
        $user_id
    ]);
    if ($ok) {
        $_SESSION['username'] = $data['username'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => '保存失败']);
    }
    exit;
}

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
<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-edit"><i class="icon-edit"></i> 修改</button>
        <button type="button" class="btn-save" style="display:none"><i class="icon-save"></i> 保存</button>
        <button type="button" class="btn-cancel" style="display:none"><i class="icon-cancel"></i> 取消</button>
    </div>
    <form class="module-form" autocomplete="off">
        <table class="module-table">
            <tr>
                <td class="module-label module-req">*用户名：</td>
                <td><input type="text" name="username" class="module-input" value="<?= show_val($user['username']) ?>" readonly></td>
                <td class="module-label">工号：</td>
                <td><input type="text" name="job_number" class="module-input" value="<?= show_val($user['job_number']) ?>" readonly></td>
            </tr>
            <tr>
                <td class="module-label module-req">*姓名：</td>
                <td><input type="text" name="real_name" class="module-input" value="<?= show_val($user['real_name']) ?>" readonly></td>
                <td class="module-label module-req">*邮箱：</td>
                <td><input type="email" name="email" class="module-input" value="<?= show_val($user['email']) ?>" readonly></td>
            </tr>
            <tr>
                <td class="module-label">英文名：</td>
                <td><input type="text" name="english_name" class="module-input" value="<?= show_val($user['english_name']) ?>" readonly></td>
                <td class="module-label">性别：</td>
                <td>
                    <select name="gender" class="module-input" disabled>
                        <option value="">请选择</option>
                        <option value="1" <?= $user['gender'] == '1' ? 'selected' : '' ?>>男</option>
                        <option value="0" <?= $user['gender'] == '0' ? 'selected' : '' ?>>女</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="module-label">电话：</td>
                <td><input type="text" name="phone" class="module-input" value="<?= show_val($user['phone']) ?>" readonly></td>
                <td class="module-label">手机：</td>
                <td><input type="text" name="mobile" class="module-input" value="<?= show_val($user['mobile']) ?>" readonly></td>
            </tr>
            <tr>
                <td class="module-label">出生日期：</td>
                <td><input type="date" name="birthday" class="module-input" value="<?= show_val($user['birthday']) ?>" readonly></td>
                <td class="module-label">是否在职：</td>
                <td>
                    <select name="is_active" class="module-input" disabled>
                        <option value="1" <?= $user['is_active'] == '1' ? 'selected' : '' ?>>是</option>
                        <option value="0" <?= $user['is_active'] == '0' ? 'selected' : '' ?>>否</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="module-label">专业：</td>
                <td><input type="text" name="major" class="module-input" value="<?= show_val($user['major']) ?>" readonly></td>
                <td class="module-label">创建时间：</td>
                <td><input type="text" class="module-input" value="<?= show_val($user['created_at']) ?>" readonly disabled></td>
            </tr>
            <tr>
                <td class="module-label">更新用户：</td>
                <td><input type="text" name="updated_by" class="module-input" value="<?= show_val($user['updated_by']) ?>" readonly></td>
                <td class="module-label">更新时间：</td>
                <td><input type="text" class="module-input" value="<?= show_val($user['updated_at']) ?>" readonly disabled></td>
            </tr>
            <tr>
                <td class="module-label">联系地址：</td>
                <td><input type="text" name="address" class="module-input" value="<?= show_val($user['address']) ?>" readonly></td>
                <td class="module-label">工作地：</td>
                <td><input type="text" name="workplace" class="module-input" value="<?= show_val($user['workplace']) ?>" readonly></td>
            </tr>
            <tr>
                <td class="module-label module-req">*是否分代理人：</td>
                <td>
                    <select name="is_agent" class="module-input" disabled>
                        <option value="">请选择</option>
                        <option value="1" <?= $user['is_agent'] == '1' ? 'selected' : '' ?>>是</option>
                        <option value="0" <?= $user['is_agent'] == '0' ? 'selected' : '' ?>>否</option>
                    </select>
                </td>
                <td class="module-label">用户角色：</td>
                <td><input type="text" name="role_name" class="module-input" value="<?= show_val($user['role_name']) ?>" readonly disabled></td>
            </tr>
            <tr>
                <td class="module-label">部门信息：</td>
                <td colspan="3"><textarea name="department_info" class="module-textarea" readonly><?= show_val($user['department_info']) ?></textarea></td>
            </tr>
            <tr>
                <td class="module-label">备注：</td>
                <td colspan="3"><textarea name="remark" class="module-textarea" readonly><?= show_val($user['remark']) ?></textarea></td>
            </tr>
        </table>
    </form>
</div>
<script>
    (function() {
        var form = document.querySelector('.module-form');
        var btnEdit = document.querySelector('.btn-edit');
        var btnSave = document.querySelector('.btn-save');
        var btnCancel = document.querySelector('.btn-cancel');
        var inputs = form.querySelectorAll('.module-input, .module-textarea');
        var selects = form.querySelectorAll('select.module-input');
        var orig = {};
        // 缓存原始值
        function cacheOrig() {
            inputs.forEach(function(inp) {
                orig[inp.name] = inp.value;
            });
            selects.forEach(function(sel) {
                orig[sel.name] = sel.value;
            });
        }
        cacheOrig();
        // 切换编辑状态
        function setEdit(edit) {
            inputs.forEach(function(inp) {
                if (inp.name && inp.type !== 'date' && inp.type !== 'email' && inp.type !== 'textarea' && inp.name !== 'role_name') inp.readOnly = !edit;
                if (inp.type === 'date') inp.readOnly = !edit;
            });
            selects.forEach(function(sel) {
                sel.disabled = !edit;
            });
            form.querySelectorAll('.module-textarea').forEach(function(ta) {
                ta.readOnly = !edit;
            });
            btnEdit.style.display = edit ? 'none' : '';
            btnSave.style.display = edit ? '' : 'none';
            btnCancel.style.display = edit ? '' : 'none';
            if (edit) form.classList.add('editing');
            else form.classList.remove('editing');
        }
        // 编辑
        btnEdit.onclick = function() {
            setEdit(true);
        };
        // 取消
        btnCancel.onclick = function() {
            for (var k in orig) {
                var el = form.querySelector('[name="' + k + '"]');
                if (el) el.value = orig[k];
            }
            setEdit(false);
        };
        // 保存
        btnSave.onclick = function() {
            console.log('点击保存按钮');
            // 校验必填
            var required = ['username', 'real_name', 'email', 'is_agent'];
            for (var i = 0; i < required.length; i++) {
                var el = form.querySelector('[name="' + required[i] + '"]');
                console.log('校验字段', required[i], el ? el.value : '未找到');
                if (!el || !el.value.trim()) {
                    alert('请填写所有必填项');
                    el && el.focus();
                    return;
                }
            }
            // 邮箱格式
            var email = form.querySelector('[name="email"]');
            if (email && !/^\S+@\S+\.\S+$/.test(email.value)) {
                alert('邮箱格式不正确');
                email.focus();
                return;
            }
            // 收集数据
            var fd = new FormData(form);
            fd.append('action', 'save');
            console.log('FormData内容:');
            for (var pair of fd.entries()) {
                console.log(pair[0] + ':', pair[1]);
            }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/system_management/personal_settings/basic_info.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log('AJAX响应', xhr.status, xhr.responseText);
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            alert('保存成功');
                            cacheOrig();
                            setEdit(false);
                            // 刷新用户名显示
                            if (window.parent && window.parent.document.querySelector('.user-name')) {
                                window.parent.document.querySelector('.user-name').textContent = form.querySelector('[name="username"]').value;
                            }
                        } else {
                            alert(res.msg || '保存失败');
                        }
                    } catch (e) {
                        console.error('解析响应出错', e, xhr.responseText);
                        alert('保存失败');
                    }
                }
            };
            try {
                xhr.send(fd);
                console.log('AJAX已发送');
            } catch (e) {
                console.error('AJAX发送异常', e);
            }
        };
    })();
</script>
<!-- <link rel="stylesheet" href="../../../css/module.css"> -->
