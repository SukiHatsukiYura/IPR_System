<?php
// 邮件设置功能 - 系统管理/个人设置模块下的邮件设置功能

include_once(__DIR__ . '/../../../database.php');
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// 处理保存/删除/测试请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $fields = [
            'imap_server',
            'imap_port',
            'smtp_server',
            'smtp_port',
            'is_default',
            'receive_email',
            'send_email',
            'imap_password',
            'smtp_password',
            'signature'
        ];
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = trim($_POST[$f] ?? '');
        }
        // 必填校验
        $required = ['imap_server', 'imap_port', 'smtp_server', 'smtp_port', 'receive_email', 'send_email', 'imap_password', 'smtp_password'];
        foreach ($required as $f) {
            if ($data[$f] === '') {
                echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
                exit;
            }
        }
        $data['is_default'] = $data['is_default'] ? 1 : 0;
        if ($id > 0) {
            // 更新
            $sql = "UPDATE user_email_account SET imap_server=?, imap_port=?, smtp_server=?, smtp_port=?, is_default=?, receive_email=?, send_email=?, imap_password=?, smtp_password=?, signature=?, updated_at=NOW() WHERE id=? AND user_id=?";
            $params = array_values($data);
            $params[] = $id;
            $params[] = $user_id;
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute($params);
            if ($ok) {
                $stmt2 = $pdo->prepare('SELECT * FROM user_email_account WHERE id=? AND user_id=?');
                $stmt2->execute([$id, $user_id]);
                $row = $stmt2->fetch();
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit;
        } else {
            // 新建
            $sql = "INSERT INTO user_email_account (user_id, imap_server, imap_port, smtp_server, smtp_port, is_default, receive_email, send_email, imap_password, smtp_password, signature, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $params = [$user_id];
            foreach ($fields as $f) $params[] = $data[$f];
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute($params);
            if ($ok) {
                $newid = $pdo->lastInsertId();
                $stmt2 = $pdo->prepare('SELECT * FROM user_email_account WHERE id=? AND user_id=?');
                $stmt2->execute([$newid, $user_id]);
                $row = $stmt2->fetch();
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit;
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM user_email_account WHERE id=? AND user_id=?');
            $ok = $stmt->execute([$id, $user_id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT * FROM user_email_account WHERE id=? AND user_id=?');
            $stmt->execute([$id, $user_id]);
            $row = $stmt->fetch();
            if ($row) {
                echo json_encode(['success' => true, 'data' => $row]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
        exit;
    }
    // 预留：测试发信
    echo json_encode(['success' => false, 'msg' => '暂未实现']);
    exit;
}
// 查询所有邮箱账户
$stmt = $pdo->prepare('SELECT * FROM user_email_account WHERE user_id=? ORDER BY is_default DESC, id ASC');
$stmt->execute([$user_id]);
$email_accounts = $stmt->fetchAll();
?>
<div class="user-basic-info-panel">
    <div class="panel-btns">
        <button type="button" class="btn-new"><i class="icon-edit"></i> 新建</button>
        <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
        <button type="button" class="btn-delete" disabled><i class="icon-cancel"></i> 删除</button>
        <button type="button" class="btn-test" disabled><i class="icon-save"></i> 测试发信</button>
    </div>
    <div class="email-list-area">
        <table class="info-table-grid">
            <tr>
                <td>收信服务器(IMAP)</td>
                <td>收信端口</td>
                <td>发信服务器(SMTP)</td>
                <td>发信端口</td>
                <td>收信地址</td>
                <td>发信地址</td>
                <td>默认邮箱</td>
            </tr>
            <?php foreach ($email_accounts as $row): ?>
                <tr data-id="<?= $row['id'] ?>">
                    <td><?= htmlspecialchars($row['imap_server']) ?></td>
                    <td><?= htmlspecialchars($row['imap_port']) ?></td>
                    <td><?= htmlspecialchars($row['smtp_server']) ?></td>
                    <td><?= htmlspecialchars($row['smtp_port']) ?></td>
                    <td><?= htmlspecialchars($row['receive_email']) ?></td>
                    <td><?= htmlspecialchars($row['send_email']) ?></td>
                    <td><?= $row['is_default'] ? '是' : '否' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($email_accounts)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:#aaa;">无数据</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
    <form class="info-form" autocomplete="off" style="margin-top:18px;display:none;">
        <input type="hidden" name="id" value="">
        <table class="info-table-grid">
            <tr>
                <td class="label req">*收信服务器(IMAP)：</td>
                <td><input type="text" name="imap_server" class="info-input"></td>
                <td class="label req">*收信端口(IMAP)：</td>
                <td><input type="text" name="imap_port" class="info-input"></td>
            </tr>
            <tr>
                <td class="label req">*发信服务器(SMTP)：</td>
                <td><input type="text" name="smtp_server" class="info-input"></td>
                <td class="label req">*发信端口(SMTP)：</td>
                <td><input type="text" name="smtp_port" class="info-input"></td>
            </tr>
            <tr>
                <td class="label req">*收信地址：</td>
                <td><input type="email" name="receive_email" class="info-input"></td>
                <td class="label req">*发信地址：</td>
                <td><input type="email" name="send_email" class="info-input"></td>
            </tr>
            <tr>
                <td class="label req">*邮箱密码：</td>
                <td><input type="password" name="imap_password" class="info-input"></td>
                <td class="label req">*发信密码：</td>
                <td><input type="password" name="smtp_password" class="info-input"></td>
            </tr>
            <tr>
                <td class="label">是否默认发件邮箱：</td>
                <td><select name="is_default" class="info-input">
                        <option value="0">否</option>
                        <option value="1">是</option>
                    </select></td>
                <td class="label">个性签名：</td>
                <td colspan="1"><textarea name="signature" class="info-input" style="height:60px;"></textarea></td>
            </tr>
        </table>
        <div style="margin-top:12px;text-align:right;">
            <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
            <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
        </div>
    </form>
</div>
<script>
    (function() {
        var listArea = document.querySelector('.email-list-area');
        var form = document.querySelector('.info-form');
        var btnNew = document.querySelector('.btn-new');
        var btnEdit = document.querySelector('.btn-edit');
        var btnDelete = document.querySelector('.btn-delete');
        var btnTest = document.querySelector('.btn-test');
        var btnSave = form.querySelector('.btn-save');
        var btnCancel = form.querySelector('.btn-cancel');
        var selectedId = null;
        // 表格行点击选中
        listArea.addEventListener('click', function(e) {
            var tr = e.target.closest('tr[data-id]');
            if (!tr) return;
            // 取消所有高亮
            listArea.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.classList.remove('selected');
            });
            tr.classList.add('selected');
            selectedId = tr.getAttribute('data-id');
            btnEdit.disabled = false;
            btnDelete.disabled = false;
            btnTest.disabled = false;
            // 新增：如果表单已显示，自动切换表单内容
            if (form.style.display !== 'none') {
                var fd = new FormData();
                fd.append('action', 'get');
                fd.append('id', selectedId);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/system_management/personal_settings/email_settings.php', true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success && res.data) {
                                form.style.display = '';
                                form.querySelector('[name="id"]').value = res.data.id;
                                form.querySelector('[name="imap_server"]').value = res.data.imap_server;
                                form.querySelector('[name="imap_port"]').value = res.data.imap_port;
                                form.querySelector('[name="smtp_server"]').value = res.data.smtp_server;
                                form.querySelector('[name="smtp_port"]').value = res.data.smtp_port;
                                form.querySelector('[name="receive_email"]').value = res.data.receive_email;
                                form.querySelector('[name="send_email"]').value = res.data.send_email;
                                form.querySelector('[name="is_default"]').value = res.data.is_default;
                                form.querySelector('[name="imap_password"]').value = res.data.imap_password;
                                form.querySelector('[name="smtp_password"]').value = res.data.smtp_password;
                                form.querySelector('[name="signature"]').value = res.data.signature;
                            } else {
                                alert('获取数据失败');
                            }
                        } catch (e) {
                            alert('获取数据失败');
                        }
                    }
                };
                xhr.send(fd);
            }
        });
        // 新建
        btnNew.onclick = function() {
            form.reset();
            form.style.display = '';
            selectedId = null;
            form.querySelector('[name="id"]').value = '';
            btnEdit.disabled = true;
            btnDelete.disabled = true;
            btnTest.disabled = true;
            // 取消表格高亮
            listArea.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.classList.remove('selected');
            });
        };
        // 修改
        btnEdit.onclick = function() {
            if (!selectedId) return;
            // 通过AJAX获取完整数据
            var fd = new FormData();
            fd.append('action', 'get');
            fd.append('id', selectedId);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/system_management/personal_settings/email_settings.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success && res.data) {
                            form.style.display = '';
                            form.querySelector('[name="id"]').value = res.data.id;
                            form.querySelector('[name="imap_server"]').value = res.data.imap_server;
                            form.querySelector('[name="imap_port"]').value = res.data.imap_port;
                            form.querySelector('[name="smtp_server"]').value = res.data.smtp_server;
                            form.querySelector('[name="smtp_port"]').value = res.data.smtp_port;
                            form.querySelector('[name="receive_email"]').value = res.data.receive_email;
                            form.querySelector('[name="send_email"]').value = res.data.send_email;
                            form.querySelector('[name="is_default"]').value = res.data.is_default;
                            form.querySelector('[name="imap_password"]').value = res.data.imap_password;
                            form.querySelector('[name="smtp_password"]').value = res.data.smtp_password;
                            form.querySelector('[name="signature"]').value = res.data.signature;
                        } else {
                            alert('获取数据失败');
                        }
                    } catch (e) {
                        alert('获取数据失败');
                    }
                }
            };
            xhr.send(fd);
        };
        // 删除
        btnDelete.onclick = function() {
            if (!selectedId) {
                alert('请先选择要删除的邮箱账户');
                return;
            }
            if (!confirm('确定要删除该邮箱账户吗？')) return;
            var fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', selectedId);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/system_management/personal_settings/email_settings.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            alert('删除成功');
                            form.style.display = 'none';
                            form.reset();
                            // 动态删表格行
                            var table = document.querySelector('.email-list-area table');
                            var tr = table.querySelector('tr[data-id="' + selectedId + '"]');
                            if (tr) tr.parentNode.removeChild(tr);
                            // 若表格无数据，补"无数据"行
                            if (table.rows.length == 1) {
                                var nodata = document.createElement('tr');
                                nodata.innerHTML = '<td colspan="7" style="text-align:center;color:#aaa;">无数据</td>';
                                table.appendChild(nodata);
                            }
                            selectedId = null;
                            btnEdit.disabled = true;
                            btnDelete.disabled = true;
                            btnTest.disabled = true;
                        } else {
                            alert('删除失败');
                        }
                    } catch (e) {
                        alert('删除失败');
                    }
                }
            };
            xhr.send(fd);
        };
        // 保存
        btnSave.onclick = function() {
            var fd = new FormData(form);
            fd.append('action', 'save');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/system_management/personal_settings/email_settings.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success && res.data) {
                            alert('保存成功');
                            form.style.display = 'none';
                            form.reset();
                            selectedId = null;
                            btnEdit.disabled = true;
                            btnDelete.disabled = true;
                            btnTest.disabled = true;
                            // 动态增/改表格行
                            var table = document.querySelector('.email-list-area table');
                            var row = res.data;
                            var existTr = table.querySelector('tr[data-id="' + row.id + '"]');
                            var html = '<td>' + escapeHtml(row.imap_server) + '</td>' +
                                '<td>' + escapeHtml(row.imap_port) + '</td>' +
                                '<td>' + escapeHtml(row.smtp_server) + '</td>' +
                                '<td>' + escapeHtml(row.smtp_port) + '</td>' +
                                '<td>' + escapeHtml(row.receive_email) + '</td>' +
                                '<td>' + escapeHtml(row.send_email) + '</td>' +
                                '<td>' + (row.is_default == 1 ? '是' : '否') + '</td>';
                            if (existTr) {
                                existTr.innerHTML = html;
                            } else {
                                // 新建插入到表格第二行（第一行为表头）
                                var tr = document.createElement('tr');
                                tr.setAttribute('data-id', row.id);
                                tr.innerHTML = html;
                                if (table.rows.length > 1) {
                                    table.insertBefore(tr, table.rows[1]);
                                } else {
                                    table.appendChild(tr);
                                }
                                // 移除"无数据"行
                                var nodata = table.querySelector('tr td[colspan]');
                                if (nodata) nodata.parentNode.remove();
                            }
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
        // 取消
        btnCancel.onclick = function() {
            form.style.display = 'none';
            form.reset();
            selectedId = null;
            btnEdit.disabled = true;
            btnDelete.disabled = true;
            btnTest.disabled = true;
            // 取消表格高亮
            listArea.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.classList.remove('selected');
            });
        };
        // 测试发信（预留）
        btnTest.onclick = function() {
            if (!selectedId) return;
            alert('测试发信功能暂未实现');
        };
        // 工具函数：HTML转义
        function escapeHtml(str) {
            if (str == null) return '';
            return String(str).replace(/[&<>"]|'/g, function(s) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [s];
            });
        }
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

    .panel-btns button:disabled {
        background: #eee;
        color: #aaa;
        cursor: not-allowed;
    }

    .panel-btns button:hover:not(:disabled) {
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

    .btn-mini {
        background: #e0e0e0;
        border: 1px solid #bbb;
        border-radius: 2px;
        color: #333;
        font-size: 13px;
        padding: 2px 10px;
        margin: 0 2px;
        cursor: pointer;
    }

    .btn-mini:hover {
        background: #d0d0d0;
    }

    .selected {
        background: #e0f7fa !important;
    }

    .info-table-grid tr.selected td {
        background: #e0f7fa !important;
    }
</style>