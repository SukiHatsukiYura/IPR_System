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
<div class="user-basic-info-panel">
    <div class="panel-btns">
        <button type="button" class="btn-edit"><i class="icon-edit"></i> 修改</button>
        <button type="button" class="btn-save" style="display:none"><i class="icon-save"></i> 保存</button>
        <button type="button" class="btn-cancel" style="display:none"><i class="icon-cancel"></i> 取消</button>
    </div>
    <div class="panel-title">当前用户：<?= $user_name ?></div>
    <form class="info-form" autocomplete="off">
        <table class="info-table-grid">
            <tr>
                <td class="label req">*原密码：</td>
                <td colspan="3"><input type="password" name="old_password" class="info-input" value="" readonly autocomplete="current-password"></td>
            </tr>
            <tr>
                <td class="label req">*新密码：</td>
                <td colspan="3"><input type="password" name="new_password" class="info-input" value="" readonly autocomplete="new-password"></td>
            </tr>
            <tr>
                <td class="label req">*确认新密码：</td>
                <td colspan="3"><input type="password" name="confirm_password" class="info-input" value="" readonly autocomplete="new-password"></td>
            </tr>
        </table>
    </form>
</div>
<script>
    (function() {
        var form = document.querySelector('.info-form');
        var btnEdit = document.querySelector('.btn-edit');
        var btnSave = document.querySelector('.btn-save');
        var btnCancel = document.querySelector('.btn-cancel');
        var inputs = form.querySelectorAll('.info-input');
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
            // 校验必填
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
<style>
    .user-basic-info-panel {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        padding: 18px 24px 24px 24px;
        margin: 18px;
        min-width: 900px;
        max-width: 1100px;
    }

    .panel-btns {
        margin-bottom: 10px;
    }

    .panel-btns button {
        background: #f5f5f5;
        border: 1px solid #d0d0d0;
        border-radius: 3px;
        color: #333;
        font-size: 14px;
        padding: 4px 18px;
        margin-right: 8px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .panel-btns button:hover {
        background: #e0e0e0;
    }

    .icon-edit::before {
        content: '\270E';
        margin-right: 4px;
    }

    .icon-save::before {
        content: '\2714';
        margin-right: 4px;
    }

    .icon-cancel::before {
        content: '\2716';
        margin-right: 4px;
    }

    .info-table-grid {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #e0e0e0;
        background: #fafbfc;
    }

    .info-table-grid tr {
        border-bottom: 1px solid #e0e0e0;
    }

    .info-table-grid td {
        border-right: 1px solid #e0e0e0;
        border-bottom: 1px solid #e0e0e0;
        padding: 6px 10px;
        font-size: 15px;
        background: #fafbfc;
    }

    .info-table-grid td:last-child {
        border-right: none;
    }

    .info-table-grid tr:last-child td {
        border-bottom: none;
    }

    .label {
        color: #666;
        text-align: right;
        width: 120px;
        min-width: 90px;
        font-size: 14px;
        background: #f3f6fa;
    }

    .req::before {
        content: '*';
        color: #f44336;
        margin-right: 2px;
    }

    .info-input {
        width: 100%;
        background: #f3f3f3;
        border: 1px solid #e0e0e0;
        border-radius: 3px;
        padding: 5px 8px;
        font-size: 15px;
        color: #333;
        outline: none;
        box-sizing: border-box;
    }

    .info-input[readonly],
    .info-input:disabled {
        color: #888;
        background: #f3f3f3;
        cursor: default;
    }

    .info-form.editing .info-input:not([readonly]):not(:disabled) {
        background: #fff;
        border: 1px solid #29b6b0;
    }
</style>