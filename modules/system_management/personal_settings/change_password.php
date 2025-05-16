<?php
// 修改密码功能 - 系统管理/个人设置模块下的修改密码功能

include_once(__DIR__ . '/../../../database.php');
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_name = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// 处理保存请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    if ($old_password === '' || $new_password === '' || $confirm_password === '') {
        echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
        exit;
    }
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'msg' => '两次输入的新密码不一致']);
        exit;
    }
    // 校验原密码
    $stmt = $pdo->prepare('SELECT password FROM user WHERE id=? LIMIT 1');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user || strtolower($user['password']) !== strtolower(md5($old_password))) {
        echo json_encode(['success' => false, 'msg' => '原密码错误']);
        exit;
    }
    // 更新密码
    $stmt = $pdo->prepare('UPDATE user SET password=? WHERE id=?');
    $ok = $stmt->execute([md5($new_password), $user_id]);
    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => '保存失败']);
    }
    exit;
}
?>
<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-edit"><i class="icon-edit"></i> 修改</button>
        <button type="button" class="btn-save" style="display:none"><i class="icon-save"></i> 保存</button>
        <button type="button" class="btn-cancel" style="display:none"><i class="icon-cancel"></i> 取消</button>
    </div>
    <div class="panel-title">当前用户：<?= $user_name ?></div>
    <form class="module-form" autocomplete="off">
        <table class="module-table">
            <tr>
                <td class="module-label module-req">*原密码：</td>
                <td colspan="3"><input type="password" name="old_password" class="module-input" value="" readonly autocomplete="current-password"></td>
            </tr>
            <tr>
                <td class="module-label module-req">*新密码：</td>
                <td colspan="3"><input type="password" name="new_password" class="module-input" value="" readonly autocomplete="new-password"></td>
            </tr>
            <tr>
                <td class="module-label module-req">*确认新密码：</td>
                <td colspan="3"><input type="password" name="confirm_password" class="module-input" value="" readonly autocomplete="new-password"></td>
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
        var inputs = form.querySelectorAll('.module-input');
        var orig = {};

        function cacheOrig() {
            inputs.forEach(function(inp) {
                orig[inp.name] = inp.value;
            });
        }
        cacheOrig();

        function setEdit(edit) {
            inputs.forEach(function(inp) {
                inp.readOnly = !edit;
            });
            btnEdit.style.display = edit ? 'none' : '';
            btnSave.style.display = edit ? '' : 'none';
            btnCancel.style.display = edit ? '' : 'none';
            if (edit) form.classList.add('editing');
            else form.classList.remove('editing');
            if (edit) inputs[0].focus();
        }
        btnEdit.onclick = function() {
            setEdit(true);
        };
        btnCancel.onclick = function() {
            for (var k in orig) {
                var el = form.querySelector('[name="' + k + '"]');
                if (el) el.value = '';
            }
            setEdit(false);
        };
        btnSave.onclick = function() {
            var required = ['old_password', 'new_password', 'confirm_password'];
            for (var i = 0; i < required.length; i++) {
                var el = form.querySelector('[name="' + required[i] + '"]');
                if (!el || !el.value.trim()) {
                    alert('请填写所有必填项');
                    el && el.focus();
                    return;
                }
            }
            var np = form.querySelector('[name="new_password"]');
            var cp = form.querySelector('[name="confirm_password"]');
            if (np.value !== cp.value) {
                alert('两次输入的新密码不一致');
                cp.focus();
                return;
            }
            var fd = new FormData(form);
            fd.append('action', 'save');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/system_management/personal_settings/change_password.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            alert('修改成功');
                            for (var k in orig) {
                                var el = form.querySelector('[name="' + k + '"]');
                                if (el) el.value = '';
                            }
                            setEdit(false);
                        } else {
                            alert(res.msg || '保存失败');
                        }
                    } catch (e) {
                        alert('保存失败');
                    }
                }
            };
            xhr.send(fd);
        };
    })();
</script>