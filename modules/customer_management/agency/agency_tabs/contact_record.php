<?php
// modules/customer_management/agency/agency_tabs/contact_record.php
// 代理机构联系记录管理
include_once(__DIR__ . '/../../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
$agency_id = intval($_GET['agency_id'] ?? $_POST['agency_id'] ?? 0);
if ($agency_id <= 0) {
    echo '<div style="color:#f44336;text-align:center;">未指定代理机构ID</div>';
    exit;
}
// 字典选项
$contact_methods = ['电话', '拜访', '邮件', '微信', '短信', 'QQ', '其他'];
$contact_types = ['案件通知', '费用通知', '官文通知', '售前', '售后', '回访', '其他'];
function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
// 查询所有联系人（当前代理机构）
$stmt = $pdo->prepare("SELECT id, name FROM agency_contact WHERE agency_id=? ORDER BY sort_order ASC, id ASC");
$stmt->execute([$agency_id]);
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
            'agency_contact_id' => intval($_POST['agency_contact_id'] ?? 0),
            'contact_time' => trim($_POST['contact_time'] ?? ''),
            'contact_method' => trim($_POST['contact_method'] ?? ''),
            'contact_type' => trim($_POST['contact_type'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'user_id' => intval($_POST['user_id'] ?? $_SESSION['user_id']),
        ];
        if ($data['agency_contact_id'] <= 0 || $data['contact_time'] === '' || $data['contact_method'] === '' || $data['contact_type'] === '' || $data['content'] === '' || $data['user_id'] <= 0) {
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
                $sql = "UPDATE agency_contact_record SET $set WHERE id=:id AND agency_contact_id IN (SELECT id FROM agency_contact WHERE agency_id=:agency_id)";
                $stmt = $pdo->prepare($sql);
                foreach ($data as $k => $v) {
                    $stmt->bindValue(":$k", $v);
                }
                $stmt->bindValue(":id", $id, PDO::PARAM_INT);
                $stmt->bindValue(":agency_id", $agency_id, PDO::PARAM_INT);
                $ok = $stmt->execute();
                echo json_encode(['success' => $ok]);
            } else {
                $data_insert = $data;
                $fields = implode(',', array_keys($data_insert));
                $placeholders = ':' . implode(', :', array_keys($data_insert));
                $sql = "INSERT INTO agency_contact_record ($fields) VALUES ($placeholders)";
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
            $stmt = $pdo->prepare("DELETE FROM agency_contact_record WHERE id=? AND agency_contact_id IN (SELECT id FROM agency_contact WHERE agency_id=?)");
            $ok = $stmt->execute([$id, $agency_id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $agency_id_check = intval($_POST['agency_id'] ?? 0);

        if ($id <= 0 || $agency_id_check <= 0 || $agency_id_check != $agency_id) {
            echo json_encode(['success' => false, 'msg' => '参数错误或代理机构ID不匹配']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM agency_contact_record WHERE id=? AND agency_contact_id IN (SELECT id FROM agency_contact WHERE agency_id=?)");
        $stmt->execute([$id, $agency_id]);
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
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM agency_contact_record r JOIN agency_contact c ON r.agency_contact_id=c.id WHERE c.agency_id=?");
$count_stmt->execute([$agency_id]);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $page_size);
// 当前页数据
$sql = "SELECT r.*, c.name as contact_name, u.real_name as user_name FROM agency_contact_record r JOIN agency_contact c ON r.agency_contact_id=c.id LEFT JOIN user u ON r.user_id=u.id WHERE c.agency_id=:agency_id ORDER BY r.id DESC LIMIT :offset, :limit";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':agency_id', $agency_id, PDO::PARAM_INT);
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
                <th style="width:120px;">代理机构联系人</th>
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
            <input type="hidden" name="agency_id" value="<?= $agency_id ?>">
            <table class="module-table">
                <tr>
                    <td class="module-label module-req">*代理机构联系人</td>
                    <td>
                        <div class="module-select-search-box" style="width:100%;">
                            <input type="text" id="agency-contact-search-input" class="module-input module-select-search-input" placeholder="输入姓名搜索..." autocomplete="off" readonly style="background:#fff;cursor:pointer;">
                            <input type="hidden" name="agency_contact_id" id="agency-contact-id-hidden" value="">
                            <div class="module-select-search-list" id="agency-contact-search-list"></div>
                        </div>
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
                        <div class="module-select-search-box" style="width:100%;">
                            <input type="text" id="user-search-input" class="module-input module-select-search-input" placeholder="输入姓名搜索..." autocomplete="off" readonly style="background:#fff;cursor:pointer;">
                            <input type="hidden" name="user_id" id="user-id-hidden" value="">
                            <div class="module-select-search-list" id="user-search-list"></div>
                        </div>
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
                // 代理机构联系人回显
                var ac = <?php echo json_encode($contacts, JSON_UNESCAPED_UNICODE); ?>;
                var ac = ac.find(function(c) {
                    return c.id == data.agency_contact_id;
                });
                if (ac) {
                    document.getElementById('agency-contact-search-input').value = ac.name;
                    document.getElementById('agency-contact-id-hidden').value = ac.id;
                } else {
                    document.getElementById('agency-contact-search-input').value = '';
                    document.getElementById('agency-contact-id-hidden').value = '';
                }
                // 我方联系人回显
                var u = <?php echo json_encode($users, JSON_UNESCAPED_UNICODE); ?>;
                var u = u.find(function(u) {
                    return u.id == data.user_id;
                });
                if (u) {
                    document.getElementById('user-search-input').value = u.real_name;
                    document.getElementById('user-id-hidden').value = u.id;
                } else {
                    document.getElementById('user-search-input').value = '';
                    document.getElementById('user-id-hidden').value = '';
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
                    agency_id: form.agency_id.value,
                    agency_contact_id: '',
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
                    fd.append('agency_id', form.agency_id.value);
                    xhr.open('POST', 'modules/customer_management/agency/agency_tabs/contact_record.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) showModal(res.data);
                            else alert(res.msg || '获取失败');
                        } catch (e) {
                            alert('获取失败');
                        }
                    };
                    xhr.send(fd);
                } else if (e.target.classList.contains('btn-del-contact-record')) {
                    if (!confirm('确定删除该联系记录？')) return;
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    xhr.open('POST', 'modules/customer_management/agency/agency_tabs/contact_record.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                                if (tabContent) {
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', 'modules/customer_management/agency/agency_tabs/contact_record.php?agency_id=' + form.agency_id.value, true);
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
                xhr.open('POST', 'modules/customer_management/agency/agency_tabs/contact_record.php', true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                            if (tabContent) {
                                var xhr2 = new XMLHttpRequest();
                                xhr2.open('GET', 'modules/customer_management/agency/agency_tabs/contact_record.php?agency_id=' + form.agency_id.value, true);
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
            var agencyId = document.querySelector('[name=agency_id]').value;

            function loadContactRecordPage(page, pageSize) {
                var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                var url = 'modules/customer_management/agency/agency_tabs/contact_record.php?agency_id=' + agencyId + '&page=' + page + '&page_size=' + pageSize;
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

            // 代理机构联系人搜索下拉
            var agencyContacts = <?php echo json_encode($contacts, JSON_UNESCAPED_UNICODE); ?>;
            var agencyContactInput = document.getElementById('agency-contact-search-input');
            var agencyContactIdHidden = document.getElementById('agency-contact-id-hidden');
            var agencyContactListDiv = document.getElementById('agency-contact-search-list');

            function renderAgencyContactList(filter) {
                var html = '';
                var found = false;
                agencyContacts.forEach(function(c) {
                    if (!filter || c.name.indexOf(filter) !== -1) {
                        html += '<div class="module-select-search-item" data-id="' + c.id + '">' + c.name + '</div>';
                        found = true;
                    }
                });
                if (!found) html = '<div class="module-select-search-item no-match">无匹配联系人</div>';
                agencyContactListDiv.innerHTML = html;
                agencyContactListDiv.style.display = 'block';
            }
            agencyContactInput.onclick = function(e) {
                agencyContactInput.readOnly = false;
                agencyContactInput.value = '';
                renderAgencyContactList('');
                agencyContactInput.focus();
                e.stopPropagation();
            };
            agencyContactInput.oninput = function() {
                renderAgencyContactList(agencyContactInput.value.trim());
            };
            agencyContactListDiv.onclick = function(e) {
                var item = e.target.closest('.module-select-search-item');
                if (item && !item.classList.contains('no-match')) {
                    var id = item.getAttribute('data-id');
                    var name = item.textContent;
                    agencyContactInput.value = name;
                    agencyContactIdHidden.value = id;
                    agencyContactListDiv.style.display = 'none';
                    agencyContactInput.readOnly = true;
                }
            };
            document.addEventListener('click', function(e) {
                agencyContactListDiv.style.display = 'none';
                agencyContactInput.readOnly = true;
            });

            // 我方联系人搜索下拉
            var userList = <?php echo json_encode($users, JSON_UNESCAPED_UNICODE); ?>;
            var userInput = document.getElementById('user-search-input');
            var userIdHidden = document.getElementById('user-id-hidden');
            var userListDiv = document.getElementById('user-search-list');

            function renderUserList(filter) {
                var html = '';
                var found = false;
                userList.forEach(function(u) {
                    if (!filter || u.real_name.indexOf(filter) !== -1) {
                        html += '<div class="module-select-search-item" data-id="' + u.id + '">' + u.real_name + '</div>';
                        found = true;
                    }
                });
                if (!found) html = '<div class="module-select-search-item no-match">无匹配用户</div>';
                userListDiv.innerHTML = html;
                userListDiv.style.display = 'block';
            }
            userInput.onclick = function(e) {
                userInput.readOnly = false;
                userInput.value = '';
                renderUserList('');
                userInput.focus();
                e.stopPropagation();
            };
            userInput.oninput = function() {
                renderUserList(userInput.value.trim());
            };
            userListDiv.onclick = function(e) {
                var item = e.target.closest('.module-select-search-item');
                if (item && !item.classList.contains('no-match')) {
                    var id = item.getAttribute('data-id');
                    var name = item.textContent;
                    userInput.value = name;
                    userIdHidden.value = id;
                    userListDiv.style.display = 'none';
                    userInput.readOnly = true;
                }
            };
            document.addEventListener('click', function(e) {
                userListDiv.style.display = 'none';
                userInput.readOnly = true;
            });
        }
        bindContactRecordEvents();
    })();
</script>