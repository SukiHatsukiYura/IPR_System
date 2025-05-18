<?php
// modules/customer_management/customer/customer_tabs/contact_record.php
// 联系记录管理
include_once(__DIR__ . '/../../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
$customer_id = intval($_GET['customer_id'] ?? $_POST['customer_id'] ?? 0);
if ($customer_id <= 0) {
    echo '<div style="color:#f44336;text-align:center;">未指定客户ID</div>';
    exit;
}
// 字典选项
$contact_methods = ['电话', '拜访', '邮件', '微信', '短信', 'QQ', '其他'];
$contact_types = ['案件通知', '费用通知', '官文通知', '售前', '售后', '回访', '其他'];
function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
// 查询所有联系人（当前客户）
$stmt = $pdo->prepare("SELECT id, name FROM contact WHERE customer_id=? ORDER BY sort_order ASC, id ASC");
$stmt->execute([$customer_id]);
$contacts = $stmt->fetchAll();
// 查询所有在职用户
$user_stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name ASC");
$user_stmt->execute();
$users = $user_stmt->fetchAll();
// 处理保存/编辑/删除/获取单条
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $data = [
            'contact_id' => intval($_POST['contact_id'] ?? 0),
            'contact_time' => trim($_POST['contact_time'] ?? ''),
            'contact_method' => trim($_POST['contact_method'] ?? ''),
            'contact_type' => trim($_POST['contact_type'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'user_id' => intval($_POST['user_id'] ?? $_SESSION['user_id']),
        ];
        if ($data['contact_id'] <= 0 || $data['contact_time'] === '' || $data['contact_method'] === '' || $data['contact_type'] === '' || $data['content'] === '' || $data['user_id'] <= 0) {
            echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
            exit;
        }
        try {
            if ($id > 0) {
                $set = '';
                foreach ($data as $k => $v) {
                    $set .= "$k=:$k,";
                }
                $set = rtrim($set, ',');
                $sql = "UPDATE contact_record SET $set WHERE id=:id AND contact_id IN (SELECT id FROM contact WHERE customer_id=:customer_id)";
                $stmt = $pdo->prepare($sql);
                foreach ($data as $k => $v) {
                    $stmt->bindValue(":$k", $v);
                }
                $stmt->bindValue(":id", $id, PDO::PARAM_INT);
                $stmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
                $ok = $stmt->execute();
                echo json_encode(['success' => $ok]);
            } else {
                $data_insert = $data;
                $fields = implode(',', array_keys($data_insert));
                $placeholders = ':' . implode(', :', array_keys($data_insert));
                $sql = "INSERT INTO contact_record ($fields) VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                foreach ($data_insert as $k => $v) {
                    $stmt->bindValue(":$k", $v);
                }
                $ok = $stmt->execute();
                echo json_encode(['success' => $ok]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '数据库异常:' . $e->getMessage()]);
        }
        exit;
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM contact_record WHERE id=? AND contact_id IN (SELECT id FROM contact WHERE customer_id=?)");
            $ok = $stmt->execute([$id, $customer_id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $customer_id_check = intval($_POST['customer_id'] ?? 0);

        if ($id <= 0 || $customer_id_check <= 0 || $customer_id_check != $customer_id) {
            echo json_encode(['success' => false, 'msg' => '参数错误或客户ID不匹配']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM contact_record WHERE id=? AND contact_id IN (SELECT id FROM contact WHERE customer_id=?)");
        $stmt->execute([$id, $customer_id]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'msg' => '未找到对应的联系记录']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $row]);
        exit;
    }
}
// 分页参数
$page = max(1, intval($_GET['page'] ?? $_POST['page'] ?? 1));
$page_size = min(max(1, intval($_GET['page_size'] ?? $_POST['page_size'] ?? 10)), 100);
$offset = ($page - 1) * $page_size;
// 总数
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_record r JOIN contact c ON r.contact_id=c.id WHERE c.customer_id=?");
$count_stmt->execute([$customer_id]);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $page_size);
// 当前页数据
$sql = "SELECT r.*, c.name as contact_name, u.real_name as user_name FROM contact_record r JOIN contact c ON r.contact_id=c.id LEFT JOIN user u ON r.user_id=u.id WHERE c.customer_id=:customer_id ORDER BY r.id DESC LIMIT :offset, :limit";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $page_size, PDO::PARAM_INT);
$stmt->execute();
$records = $stmt->fetchAll();
?>
<div class="module-panel">
    <div style="margin-bottom:12px;text-align:left;">
        <button type="button" class="btn-mini" id="btn-add-contact-record"><i class="icon-add"></i> 添加联系记录</button>
    </div>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:30px;">序号</th>
                <th style="width:120px;">客户联系人</th>
                <th style="width:100px;">联系时间</th>
                <th style="width:100px;">联系方式</th>
                <th style="width:100px;">联系类型</th>
                <th style="width:220px;">联系内容</th>
                <th style="width:100px;">我方联系人</th>
                <th style="width:120px;">操作</th>
            </tr>
        </thead>
        <tbody id="contact-record-list">
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;color:#888;">暂无联系记录</td>
                </tr>
                <?php else: foreach ($records as $i => $r): ?>
                    <tr data-id="<?= $r['id'] ?>">
                        <td style="text-align:center; width:40px;"> <?= $offset + $i + 1 ?> </td>
                        <td style="width:120px;"> <?= h($r['contact_name']) ?> </td>
                        <td style="width:100px;"> <?= h($r['contact_time']) ?> </td>
                        <td style="width:100px;"> <?= h($r['contact_method']) ?> </td>
                        <td style="width:100px;"> <?= h($r['contact_type']) ?> </td>
                        <td style="width:220px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;" title="<?= h($r['content']) ?>"> <?= h(mb_substr($r['content'], 0, 30)) . (mb_strlen($r['content']) > 30 ? '...' : '') ?> </td>
                        <td style="width:100px;"> <?= h($r['user_name']) ?> </td>
                        <td style="width:120px; text-align:center;">
                            <button type="button" class="btn-mini btn-edit-contact-record">编辑</button>
                            <button type="button" class="btn-mini btn-del-contact-record">删除</button>
                        </td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>
    <div class="module-pagination" id="contact-record-pagination">
        <span>共 <span id="contact-record-total-records"><?= $total_records ?></span> 条记录，每页</span>
        <select id="contact-record-page-size-select">
            <option value="10" <?= $page_size == 10 ? ' selected' : '' ?>>10</option>
            <option value="20" <?= $page_size == 20 ? ' selected' : '' ?>>20</option>
            <option value="50" <?= $page_size == 50 ? ' selected' : '' ?>>50</option>
            <option value="100" <?= $page_size == 100 ? ' selected' : '' ?>>100</option>
        </select>
        <span>条，当前 <span id="contact-record-current-page"><?= $page ?></span>/<span id="contact-record-total-pages"><?= $total_pages ?></span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="contact-record-btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="<?= max(1, $page - 1) ?>" id="contact-record-btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="<?= min($total_pages, $page + 1) ?>" id="contact-record-btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="<?= $total_pages ?>" id="contact-record-btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="contact-record-page-input" min="1" value="<?= $page ?>">
        <span>页</span>
        <button type="button" id="contact-record-btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>
<div id="contact-record-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:6px;box-shadow:0 2px 12px rgba(0,0,0,0.18);padding:24px 32px;min-width:700px;max-width:98vw;position:relative;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="contact-record-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">联系记录</h3>
        <form id="contact-record-form" class="module-form">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="customer_id" value="<?= $customer_id ?>">
            <table class="module-table">
                <tr>
                    <td class="module-label module-req">*客户联系人</td>
                    <td>
                        <select name="contact_id" class="module-input" required>
                            <option value="">--请选择--</option>
                            <?php foreach ($contacts as $c): ?>
                                <option value="<?= h($c['id']) ?>"><?= h($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="module-label module-req">*联系时间</td>
                    <td><input type="date" name="contact_time" class="module-input" required></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*联系方式</td>
                    <td>
                        <select name="contact_method" class="module-input" required>
                            <option value="">--请选择--</option>
                            <?php foreach ($contact_methods as $v): ?>
                                <option value="<?= h($v) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="module-label module-req">*联系类型</td>
                    <td>
                        <select name="contact_type" class="module-input" required>
                            <option value="">--请选择--</option>
                            <?php foreach ($contact_types as $v): ?>
                                <option value="<?= h($v) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="module-label module-req">*联系内容</td>
                    <td colspan="3"><textarea name="content" class="module-input" style="min-height:60px;" required></textarea></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*我方联系人</td>
                    <td colspan="3">
                        <select name="user_id" class="module-input" required>
                            <option value="">--请选择--</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= h($u['id']) ?>" <?= $u['id'] == $_SESSION['user_id'] ? ' selected' : '' ?>><?= h($u['real_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <div style="text-align:center;margin-top:12px;">
                <button type="button" class="btn-save-contact-record btn-mini" style="margin-right:16px;">保存</button>
                <button type="button" class="btn-cancel-contact-record btn-mini">取消</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function() {
        function bindContactRecordEvents() {
            var modal = document.getElementById('contact-record-modal');
            var closeBtn = document.getElementById('contact-record-modal-close');
            var addBtn = document.getElementById('btn-add-contact-record');
            var list = document.getElementById('contact-record-list');
            var form = document.getElementById('contact-record-form');
            if (!modal || !addBtn || !list || !form) return;

            function showModal(data) {
                form.reset();
                for (var k in data) {
                    if (form[k] && form[k].type !== 'checkbox') {
                        form[k].value = data[k] !== null ? data[k] : '';
                    }
                }
                modal.style.display = 'flex';
            }

            function hideModal() {
                modal.style.display = 'none';
            }

            addBtn.onclick = function() {
                var today = new Date().toISOString().slice(0, 10); // 格式：YYYY-MM-DD
                showModal({
                    id: 0,
                    customer_id: form.customer_id.value,
                    contact_id: '',
                    contact_time: today,
                    contact_method: '',
                    contact_type: '',
                    content: '',
                    user_id: <?= $_SESSION['user_id'] ?>
                });
            };

            closeBtn.onclick = hideModal;
            form.querySelector('.btn-cancel-contact-record').onclick = hideModal;

            list.onclick = function(e) {
                var tr = e.target.closest('tr[data-id]');
                if (!tr) return;
                var id = tr.getAttribute('data-id');

                if (e.target.classList.contains('btn-edit-contact-record')) {
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'get');
                    fd.append('id', id);
                    fd.append('customer_id', form.customer_id.value);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/contact_record.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) showModal(res.data);
                            else alert(res.msg || '获取失败');
                        } catch (e) {
                            console.error('解析响应失败:', e, '原始响应:', xhr.responseText);
                            alert('获取失败：' + xhr.responseText);
                        }
                    };
                    xhr.send(fd);
                } else if (e.target.classList.contains('btn-del-contact-record')) {
                    if (!confirm('确定删除该联系记录？')) return;
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/contact_record.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                                if (tabContent) {
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', 'modules/customer_management/customer/customer_tabs/contact_record.php?customer_id=' + form.customer_id.value, true);
                                    xhr2.onload = function() {
                                        tabContent.innerHTML = xhr2.responseText;
                                        setTimeout(bindContactRecordEvents, 0);
                                    };
                                    xhr2.send();
                                } else {
                                    location.reload();
                                }
                            } else alert('删除失败');
                        } catch (e) {
                            alert('删除失败');
                        }
                    };
                    xhr.send(fd);
                }
            };

            form.querySelector('.btn-save-contact-record').onclick = function() {
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/customer/customer_tabs/contact_record.php', true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                            if (tabContent) {
                                var xhr2 = new XMLHttpRequest();
                                xhr2.open('GET', 'modules/customer_management/customer/customer_tabs/contact_record.php?customer_id=' + form.customer_id.value, true);
                                xhr2.onload = function() {
                                    tabContent.innerHTML = xhr2.responseText;
                                    setTimeout(bindContactRecordEvents, 0);
                                };
                                xhr2.send();
                            } else {
                                location.reload();
                            }
                            hideModal();
                        } else alert(res.msg || '保存失败');
                    } catch (e) {
                        alert('保存失败');
                    }
                };
                xhr.send(fd);
            };

            // 分页相关
            var pageSizeSelect = document.getElementById('contact-record-page-size-select');
            var btnFirstPage = document.getElementById('contact-record-btn-first-page');
            var btnPrevPage = document.getElementById('contact-record-btn-prev-page');
            var btnNextPage = document.getElementById('contact-record-btn-next-page');
            var btnLastPage = document.getElementById('contact-record-btn-last-page');
            var pageInput = document.getElementById('contact-record-page-input');
            var btnPageJump = document.getElementById('contact-record-btn-page-jump');
            var totalPages = parseInt(document.getElementById('contact-record-total-pages').textContent) || 1;
            var customerId = document.querySelector('[name=customer_id]').value;

            function loadContactRecordPage(page, pageSize) {
                var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                var url = 'modules/customer_management/customer/customer_tabs/contact_record.php?customer_id=' + customerId + '&page=' + page + '&page_size=' + pageSize;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.onload = function() {
                    if (tabContent) {
                        tabContent.innerHTML = xhr.responseText;
                        setTimeout(bindContactRecordEvents, 0);
                    }
                };
                xhr.send();
            }

            if (pageSizeSelect) pageSizeSelect.onchange = function() {
                loadContactRecordPage(1, this.value);
            };
            if (btnFirstPage) btnFirstPage.onclick = function() {
                loadContactRecordPage(1, pageSizeSelect.value);
            };
            if (btnPrevPage) btnPrevPage.onclick = function() {
                var cur = parseInt(document.getElementById('contact-record-current-page').textContent) || 1;
                if (cur > 1) loadContactRecordPage(cur - 1, pageSizeSelect.value);
            };
            if (btnNextPage) btnNextPage.onclick = function() {
                var cur = parseInt(document.getElementById('contact-record-current-page').textContent) || 1;
                if (cur < totalPages) loadContactRecordPage(cur + 1, pageSizeSelect.value);
            };
            if (btnLastPage) btnLastPage.onclick = function() {
                loadContactRecordPage(totalPages, pageSizeSelect.value);
            };
            if (btnPageJump) btnPageJump.onclick = function() {
                var page = parseInt(pageInput.value) || 1;
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages;
                loadContactRecordPage(page, pageSizeSelect.value);
            };
        }
        bindContactRecordEvents();
    })();
</script>