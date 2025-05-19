<?php
// modules/customer_management/agency/agency_tabs/agent.php
// 代理机构代理人管理
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
// 获取本所用户
$user_stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name ASC");
$user_stmt->execute();
$users = $user_stmt->fetchAll();
$genders = ['1' => '男', '0' => '女'];
$yesno = ['0' => '否', '1' => '是'];
function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
// 处理保存/编辑/删除/获取单条
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $data = [
            'user_id' => intval($_POST['user_id'] ?? 0),
            'name_cn' => trim($_POST['name_cn'] ?? ''),
            'xing_cn' => trim($_POST['xing_cn'] ?? ''),
            'ming_cn' => trim($_POST['ming_cn'] ?? ''),
            'name_en' => trim($_POST['name_en'] ?? ''),
            'xing_en' => trim($_POST['xing_en'] ?? ''),
            'ming_en' => trim($_POST['ming_en'] ?? ''),
            'gender' => trim($_POST['gender'] ?? '1'),
            'major' => trim($_POST['major'] ?? ''),
            'birthday' => trim($_POST['birthday'] ?? ''),
            'license_no' => trim($_POST['license_no'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'qualification_no' => trim($_POST['qualification_no'] ?? ''),
            'qualification_date' => trim($_POST['qualification_date'] ?? ''),
            'is_default' => intval($_POST['is_default'] ?? 0),
            'is_active' => intval($_POST['is_active'] ?? 1),
        ];
        if ($data['name_cn'] === '' || $data['license_no'] === '') {
            echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
            exit;
        }
        // 日期字段空字符串转为NULL
        if ($data['birthday'] === '') $data['birthday'] = null;
        if ($data['qualification_date'] === '') $data['qualification_date'] = null;
        try {
            if ($id > 0) {
                $set = '';
                foreach ($data as $k => $v) {
                    $set .= "$k=:$k,";
                }
                $set = rtrim($set, ',');
                $sql = "UPDATE agency_agent SET $set WHERE id=:id AND agency_id=:agency_id";
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
                $data_insert['agency_id'] = $agency_id;
                $fields = implode(',', array_keys($data_insert));
                $placeholders = ':' . implode(', :', array_keys($data_insert));
                $sql = "INSERT INTO agency_agent ($fields) VALUES ($placeholders)";
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
            $stmt = $pdo->prepare("DELETE FROM agency_agent WHERE id=? AND agency_id=?");
            $ok = $stmt->execute([$id, $agency_id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM agency_agent WHERE id=? AND agency_id=?");
        $stmt->execute([$id, $agency_id]);
        $row = $stmt->fetch();
        echo json_encode(['success' => !!$row, 'data' => $row]);
        exit;
    }
}
// 分页参数
$page = max(1, intval($_GET['page'] ?? $_POST['page'] ?? 1));
$page_size = min(max(1, intval($_GET['page_size'] ?? $_POST['page_size'] ?? 10)), 100);
$offset = ($page - 1) * $page_size;
// 总数
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM agency_agent WHERE agency_id=?");
$count_stmt->execute([$agency_id]);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $page_size);
// 当前页数据
$stmt = $pdo->prepare("SELECT a.*, u.real_name as user_name FROM agency_agent a LEFT JOIN user u ON a.user_id=u.id WHERE a.agency_id=:agency_id ORDER BY a.id ASC LIMIT :offset, :limit");
$stmt->bindValue(':agency_id', $agency_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $page_size, PDO::PARAM_INT);
$stmt->execute();
$agents = $stmt->fetchAll();
?>
<div class="module-panel">
    <div style="margin-bottom:12px;text-align:left;">
        <button type="button" class="btn-mini" id="btn-add-agent"><i class="icon-add"></i> 添加代理人</button>
    </div>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:30px;">序号</th>
                <th style="width:100px;">姓名(中文)</th>
                <th style="width:100px;">本所用户</th>
                <th style="width:100px;">执业证号</th>
                <th style="width:80px;">性别</th>
                <th style="width:100px;">电话</th>
                <th style="width:100px;">邮箱</th>
                <th style="width:80px;">默认本所</th>
                <th style="width:80px;">是否有效</th>
                <th style="width:120px;">操作</th>
            </tr>
        </thead>
        <tbody id="agent-list">
            <?php if (empty($agents)): ?>
                <tr>
                    <td colspan="10" style="text-align:center;color:#888;">暂无代理人</td>
                </tr>
                <?php else: foreach ($agents as $i => $a): ?>
                    <tr data-id="<?= $a['id'] ?>">
                        <td style="text-align:center;"> <?= $offset + $i + 1 ?> </td>
                        <td> <?= h($a['name_cn']) ?> </td>
                        <td> <?= h($a['user_name']) ?> </td>
                        <td> <?= h($a['license_no']) ?> </td>
                        <td> <?= $a['gender'] == 1 ? '男' : '女' ?> </td>
                        <td> <?= h($a['phone']) ?> </td>
                        <td> <?= h($a['email']) ?> </td>
                        <td> <?= $a['is_default'] ? '是' : '否' ?> </td>
                        <td> <?= $a['is_active'] ? '是' : '否' ?> </td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-mini btn-edit-agent">编辑</button>
                            <button type="button" class="btn-mini btn-del-agent">删除</button>
                        </td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>
    <div class="module-pagination" id="agent-pagination">
        <span>共 <span id="agent-total-records"><?= $total_records ?></span> 条记录，每页</span>
        <select id="agent-page-size-select">
            <option value="10" <?= $page_size == 10 ? ' selected' : '' ?>>10</option>
            <option value="20" <?= $page_size == 20 ? ' selected' : '' ?>>20</option>
            <option value="50" <?= $page_size == 50 ? ' selected' : '' ?>>50</option>
            <option value="100" <?= $page_size == 100 ? ' selected' : '' ?>>100</option>
        </select>
        <span>条，当前 <span id="agent-current-page"><?= $page ?></span>/<span id="agent-total-pages"><?= $total_pages ?></span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="agent-btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="<?= max(1, $page - 1) ?>" id="agent-btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="<?= min($total_pages, $page + 1) ?>" id="agent-btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="<?= $total_pages ?>" id="agent-btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="agent-page-input" min="1" value="<?= $page ?>">
        <span>页</span>
        <button type="button" id="agent-btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>
<div id="agent-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:6px;box-shadow:0 2px 12px rgba(0,0,0,0.18);padding:24px 32px;min-width:900px;max-width:98vw;position:relative;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="agent-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">代理人信息</h3>
        <form id="agent-form" class="module-form">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="agency_id" value="<?= $agency_id ?>">
            <table class="module-table">
                <tr>
                    <td class="module-label">本所用户</td>
                    <td colspan="3">
                        <div class="module-select-search-box" style="width:100%;">
                            <input type="text" id="user-search-input" class="module-input module-select-search-input" placeholder="输入姓名搜索..." autocomplete="off" readonly style="background:#fff;cursor:pointer;">
                            <input type="hidden" name="user_id" id="user-id-hidden" value="0">
                            <div class="module-select-search-list" id="user-search-list"></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="module-label module-req">*姓名(中文)</td>
                    <td><input type="text" name="name_cn" class="module-input" required></td>
                    <td class="module-label">*姓名(英文)</td>
                    <td><input type="text" name="name_en" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">*名称/姓(中文)</td>
                    <td>
                        <input type="text" name="xing_cn" class="module-input">
                        <span style="color:#888;font-size:12px;margin-left:6px;">PCT使用</span>
                    </td>
                    <td class="module-label">*名称/姓(英文)</td>
                    <td>
                        <input type="text" name="xing_en" class="module-input">
                        <span style="color:#888;font-size:12px;margin-left:6px;">PCT使用</span>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">*名(中文)</td>
                    <td>
                        <input type="text" name="ming_cn" class="module-input">
                        <span style="color:#888;font-size:12px;margin-left:6px;">PCT使用</span>
                    </td>
                    <td class="module-label">*名(英文)</td>
                    <td>
                        <input type="text" name="ming_en" class="module-input">
                        <span style="color:#888;font-size:12px;margin-left:6px;">PCT使用</span>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">性别</td>
                    <td><select name="gender" class="module-input">
                            <?php foreach ($genders as $k => $v): ?>
                                <option value="<?= h($k) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select></td>
                    <td class="module-label">出生日期</td>
                    <td><input type="date" name="birthday" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*执业证号</td>
                    <td><input type="text" name="license_no" class="module-input" required></td>
                    <td class="module-label">资格证号</td>
                    <td><input type="text" name="qualification_no" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">获得代理资格日期</td>
                    <td><input type="date" name="qualification_date" class="module-input"></td>
                    <td class="module-label">电话</td>
                    <td><input type="text" name="phone" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">邮箱</td>
                    <td><input type="email" name="email" class="module-input"></td>
                    <td class="module-label">默认本所代理人</td>
                    <td><select name="is_default" class="module-input">
                            <?php foreach ($yesno as $k => $v): ?>
                                <option value="<?= h($k) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select></td>
                </tr>
                <tr>
                    <td class="module-label">是否有效</td>
                    <td><select name="is_active" class="module-input">
                            <?php foreach ($yesno as $k => $v): ?>
                                <option value="<?= h($k) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select></td>
                    <td class="module-label"></td>
                    <td></td>
                </tr>
            </table>
            <div style="text-align:center;margin-top:12px;">
                <button type="button" class="btn-save-agent btn-mini" style="margin-right:16px;">保存</button>
                <button type="button" class="btn-cancel-agent btn-mini">取消</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function() {
        function bindAgentEvents() {
            var modal = document.getElementById('agent-modal');
            var closeBtn = document.getElementById('agent-modal-close');
            var addBtn = document.getElementById('btn-add-agent');
            var list = document.getElementById('agent-list');
            var form = document.getElementById('agent-form');
            if (!modal || !addBtn || !list || !form) return;

            function showModal(data) {
                form.reset();
                for (var k in data) {
                    if (form[k]) form[k].value = data[k] !== null ? data[k] : '';
                }
                // 回显本所用户
                var user = <?php echo json_encode($users, JSON_UNESCAPED_UNICODE); ?>.find(function(u) {
                    return u.id == data.user_id;
                });
                if (user) {
                    document.getElementById('user-search-input').value = user.real_name;
                    document.getElementById('user-id-hidden').value = user.id;
                } else {
                    document.getElementById('user-search-input').value = '';
                    document.getElementById('user-id-hidden').value = 0;
                }
                modal.style.display = 'flex';
            }

            function hideModal() {
                modal.style.display = 'none';
            }
            addBtn.onclick = function() {
                showModal({
                    id: 0,
                    agency_id: form.agency_id.value,
                    user_id: 0,
                    name_cn: '',
                    xing_cn: '',
                    ming_cn: '',
                    name_en: '',
                    xing_en: '',
                    ming_en: '',
                    gender: '1',
                    major: '',
                    birthday: '',
                    license_no: '',
                    phone: '',
                    email: '',
                    qualification_no: '',
                    qualification_date: '',
                    is_default: 0,
                    is_active: 1
                });
            };
            closeBtn.onclick = hideModal;
            form.querySelector('.btn-cancel-agent').onclick = hideModal;
            list.onclick = function(e) {
                var tr = e.target.closest('tr[data-id]');
                if (!tr) return;
                var id = tr.getAttribute('data-id');
                if (e.target.classList.contains('btn-edit-agent')) {
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'get');
                    fd.append('id', id);
                    fd.append('agency_id', form.agency_id.value);
                    xhr.open('POST', 'modules/customer_management/agency/agency_tabs/agent.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) showModal(res.data);
                            else alert('获取失败');
                        } catch (e) {
                            alert('获取失败');
                        }
                    };
                    xhr.send(fd);
                } else if (e.target.classList.contains('btn-del-agent')) {
                    if (!confirm('确定删除该代理人？')) return;
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    fd.append('agency_id', form.agency_id.value);
                    xhr.open('POST', 'modules/customer_management/agency/agency_tabs/agent.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                                if (tabContent) {
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', 'modules/customer_management/agency/agency_tabs/agent.php?agency_id=' + form.agency_id.value, true);
                                    xhr2.onload = function() {
                                        tabContent.innerHTML = xhr2.responseText;
                                        setTimeout(bindAgentEvents, 0);
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
            form.querySelector('.btn-save-agent').onclick = function() {
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/agency/agency_tabs/agent.php', true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                            if (tabContent) {
                                var xhr2 = new XMLHttpRequest();
                                xhr2.open('GET', 'modules/customer_management/agency/agency_tabs/agent.php?agency_id=' + form.agency_id.value, true);
                                xhr2.onload = function() {
                                    tabContent.innerHTML = xhr2.responseText;
                                    setTimeout(bindAgentEvents, 0);
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
            var pageSizeSelect = document.getElementById('agent-page-size-select');
            var btnFirstPage = document.getElementById('agent-btn-first-page');
            var btnPrevPage = document.getElementById('agent-btn-prev-page');
            var btnNextPage = document.getElementById('agent-btn-next-page');
            var btnLastPage = document.getElementById('agent-btn-last-page');
            var pageInput = document.getElementById('agent-page-input');
            var btnPageJump = document.getElementById('agent-btn-page-jump');
            var totalPages = parseInt(document.getElementById('agent-total-pages').textContent) || 1;
            var agencyId = document.querySelector('[name=agency_id]').value;

            function loadAgentPage(page, pageSize) {
                var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                var url = 'modules/customer_management/agency/agency_tabs/agent.php?agency_id=' + agencyId + '&page=' + page + '&page_size=' + pageSize;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.onload = function() {
                    if (tabContent) {
                        tabContent.innerHTML = xhr.responseText;
                        setTimeout(bindAgentEvents, 0);
                    }
                };
                xhr.send();
            }
            if (pageSizeSelect) pageSizeSelect.onchange = function() {
                loadAgentPage(1, this.value);
            };
            if (btnFirstPage) btnFirstPage.onclick = function() {
                loadAgentPage(1, pageSizeSelect.value);
            };
            if (btnPrevPage) btnPrevPage.onclick = function() {
                var cur = parseInt(document.getElementById('agent-current-page').textContent) || 1;
                if (cur > 1) loadAgentPage(cur - 1, pageSizeSelect.value);
            };
            if (btnNextPage) btnNextPage.onclick = function() {
                var cur = parseInt(document.getElementById('agent-current-page').textContent) || 1;
                if (cur < totalPages) loadAgentPage(cur + 1, pageSizeSelect.value);
            };
            if (btnLastPage) btnLastPage.onclick = function() {
                loadAgentPage(totalPages, pageSizeSelect.value);
            };
            if (btnPageJump) btnPageJump.onclick = function() {
                var page = parseInt(pageInput.value) || 1;
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages;
                loadAgentPage(page, pageSizeSelect.value);
            };
            // 本所用户搜索下拉逻辑
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
        bindAgentEvents();
    })();
</script>