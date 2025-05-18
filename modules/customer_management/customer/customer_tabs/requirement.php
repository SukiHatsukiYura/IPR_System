<?php
// modules/customer_management/customer/customer_tabs/requirement.php
// 客户要求管理
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
$case_types = ['专利', '商标', '版权'];
$requirement_types = ['看稿要求', '费用要求', '其他要求'];
// 获取当前用户真实姓名
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT real_name FROM user WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$real_name = $user ? $user['real_name'] : '';
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
            'case_type' => trim($_POST['case_type'] ?? ''),
            'requirement_type' => trim($_POST['requirement_type'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'user_id' => intval($_SESSION['user_id']),
        ];
        if ($data['title'] === '' || $data['content'] === '') {
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
                $sql = "UPDATE customer_requirement SET $set WHERE id=:id AND customer_id=:customer_id";
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
                $data_insert['customer_id'] = $customer_id;
                $fields = implode(',', array_keys($data_insert));
                $placeholders = ':' . implode(', :', array_keys($data_insert));
                $sql = "INSERT INTO customer_requirement ($fields) VALUES ($placeholders)";
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
            $stmt = $pdo->prepare("DELETE FROM customer_requirement WHERE id=? AND customer_id=?");
            $ok = $stmt->execute([$id, $customer_id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM customer_requirement WHERE id=? AND customer_id=?");
        $stmt->execute([$id, $customer_id]);
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
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_requirement WHERE customer_id=?");
$count_stmt->execute([$customer_id]);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $page_size);
// 当前页数据
$stmt = $pdo->prepare("SELECT r.*, u.real_name FROM customer_requirement r LEFT JOIN user u ON r.user_id=u.id WHERE r.customer_id=:customer_id ORDER BY r.id ASC LIMIT :offset, :limit");
$stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $page_size, PDO::PARAM_INT);
$stmt->execute();
$requirements = $stmt->fetchAll();
?>
<div class="module-panel">
    <div style="margin-bottom:12px;text-align:left;">
        <button type="button" class="btn-mini" id="btn-add-requirement"><i class="icon-add"></i> 添加客户要求</button>
    </div>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:30px;">序号</th>
                <th style="width:100px;">案件类型</th>
                <th style="width:100px;">要求类型</th>
                <th style="width:180px;">要求标题</th>
                <th style="width:300px;">要求内容</th>
                <th style="width:100px;">更新者</th>
                <th style="width:120px;">操作</th>
            </tr>
        </thead>
        <tbody id="requirement-list">
            <?php if (empty($requirements)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:#888;">暂无客户要求</td>
                </tr>
                <?php else: foreach ($requirements as $i => $r): ?>
                    <tr data-id="<?= $r['id'] ?>">
                        <td style="text-align:center; width:40px;"> <?= $offset + $i + 1 ?> </td>
                        <td style="width:100px;"> <?= h($r['case_type']) ?> </td>
                        <td style="width:100px;"> <?= h($r['requirement_type']) ?> </td>
                        <td style="width:180px;"> <?= h($r['title']) ?> </td>
                        <td style="width:300px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;" title="<?= h($r['content']) ?>"> <?= h(mb_substr($r['content'], 0, 40)) ?> </td>
                        <td style="width:100px;"> <?= h($r['real_name']) ?> </td>
                        <td style="width:120px; text-align:center;">
                            <button type="button" class="btn-mini btn-edit-requirement">编辑</button>
                            <button type="button" class="btn-mini btn-del-requirement">删除</button>
                        </td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>
    <div class="module-pagination" id="requirement-pagination">
        <span>共 <span id="requirement-total-records"><?= $total_records ?></span> 条记录，每页</span>
        <select id="requirement-page-size-select">
            <option value="10" <?= $page_size == 10 ? ' selected' : '' ?>>10</option>
            <option value="20" <?= $page_size == 20 ? ' selected' : '' ?>>20</option>
            <option value="50" <?= $page_size == 50 ? ' selected' : '' ?>>50</option>
            <option value="100" <?= $page_size == 100 ? ' selected' : '' ?>>100</option>
        </select>
        <span>条，当前 <span id="requirement-current-page"><?= $page ?></span>/<span id="requirement-total-pages"><?= $total_pages ?></span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="requirement-btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="<?= max(1, $page - 1) ?>" id="requirement-btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="<?= min($total_pages, $page + 1) ?>" id="requirement-btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="<?= $total_pages ?>" id="requirement-btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="requirement-page-input" min="1" value="<?= $page ?>">
        <span>页</span>
        <button type="button" id="requirement-btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>
<div id="requirement-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:700px;max-width:98vw;max-height:80vh;position:relative;display:flex;flex-direction:column;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="requirement-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">客户要求</h3>
        <div style="flex:1 1 auto;overflow-y:auto;">
            <form id="requirement-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <input type="hidden" name="customer_id" value="<?= $customer_id ?>">
                <table class="module-table" style="table-layout:fixed;width:100%;min-width:0;">
                    <colgroup>
                        <col style="width:120px;">
                        <col style="width:520px;">
                    </colgroup>
                    <tr>
                        <td class="module-label">案件类型</td>
                        <td>
                            <select name="case_type" class="module-input">
                                <option value="">--请选择--</option>
                                <?php foreach ($case_types as $v): ?>
                                    <option value="<?= h($v) ?>"><?= h($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">要求类型</td>
                        <td>
                            <select name="requirement_type" class="module-input">
                                <option value="">--请选择--</option>
                                <?php foreach ($requirement_types as $v): ?>
                                    <option value="<?= h($v) ?>"><?= h($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label module-req">*要求标题</td>
                        <td><input type="text" name="title" class="module-input" required></td>
                    </tr>
                    <tr>
                        <td class="module-label module-req">*要求内容</td>
                        <td><textarea name="content" class="module-input" style="min-height:120px;" required></textarea></td>
                    </tr>
                    <tr>
                        <td class="module-label">更新者</td>
                        <td>
                            <input type="text" class="module-input" value="<?= htmlspecialchars($real_name) ?>" readonly>
                        </td>
                    </tr>
                </table>
                <div style="text-align:center;margin-top:12px;">
                    <button type="button" class="btn-save-requirement btn-mini" style="margin-right:16px;">保存</button>
                    <button type="button" class="btn-cancel-requirement btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function() {
        function bindRequirementEvents() {
            var modal = document.getElementById('requirement-modal');
            var closeBtn = document.getElementById('requirement-modal-close');
            var addBtn = document.getElementById('btn-add-requirement');
            var list = document.getElementById('requirement-list');
            var form = document.getElementById('requirement-form');
            if (!modal || !addBtn || !list || !form) return;

            function showModal(data) {
                form.reset();
                for (var k in data) {
                    if (form[k]) form[k].value = data[k] !== null ? data[k] : '';
                }
                modal.style.display = 'flex';
            }

            function hideModal() {
                modal.style.display = 'none';
            }
            addBtn.onclick = function() {
                showModal({
                    id: 0,
                    customer_id: form.customer_id.value,
                    case_type: '',
                    requirement_type: '',
                    title: '',
                    content: ''
                });
            };
            closeBtn.onclick = hideModal;
            form.querySelector('.btn-cancel-requirement').onclick = hideModal;
            list.onclick = function(e) {
                var tr = e.target.closest('tr[data-id]');
                if (!tr) return;
                var id = tr.getAttribute('data-id');
                if (e.target.classList.contains('btn-edit-requirement')) {
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'get');
                    fd.append('id', id);
                    fd.append('customer_id', form.customer_id.value);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/requirement.php', true);
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
                } else if (e.target.classList.contains('btn-del-requirement')) {
                    if (!confirm('确定删除该客户要求？')) return;
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    fd.append('customer_id', form.customer_id.value);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/requirement.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                                if (tabContent) {
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', 'modules/customer_management/customer/customer_tabs/requirement.php?customer_id=' + form.customer_id.value, true);
                                    xhr2.onload = function() {
                                        tabContent.innerHTML = xhr2.responseText;
                                        setTimeout(bindRequirementEvents, 0);
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
            form.querySelector('.btn-save-requirement').onclick = function() {
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/customer/customer_tabs/requirement.php', true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                            if (tabContent) {
                                var xhr2 = new XMLHttpRequest();
                                xhr2.open('GET', 'modules/customer_management/customer/customer_tabs/requirement.php?customer_id=' + form.customer_id.value, true);
                                xhr2.onload = function() {
                                    tabContent.innerHTML = xhr2.responseText;
                                    setTimeout(bindRequirementEvents, 0);
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
            var pageSizeSelect = document.getElementById('requirement-page-size-select');
            var btnFirstPage = document.getElementById('requirement-btn-first-page');
            var btnPrevPage = document.getElementById('requirement-btn-prev-page');
            var btnNextPage = document.getElementById('requirement-btn-next-page');
            var btnLastPage = document.getElementById('requirement-btn-last-page');
            var pageInput = document.getElementById('requirement-page-input');
            var btnPageJump = document.getElementById('requirement-btn-page-jump');
            var totalPages = parseInt(document.getElementById('requirement-total-pages').textContent) || 1;
            var customerId = document.querySelector('[name=customer_id]').value;

            function loadRequirementPage(page, pageSize) {
                var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                var url = 'modules/customer_management/customer/customer_tabs/requirement.php?customer_id=' + customerId + '&page=' + page + '&page_size=' + pageSize;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.onload = function() {
                    if (tabContent) {
                        tabContent.innerHTML = xhr.responseText;
                        setTimeout(bindRequirementEvents, 0);
                    }
                };
                xhr.send();
            }
            if (pageSizeSelect) pageSizeSelect.onchange = function() {
                loadRequirementPage(1, this.value);
            };
            if (btnFirstPage) btnFirstPage.onclick = function() {
                loadRequirementPage(1, pageSizeSelect.value);
            };
            if (btnPrevPage) btnPrevPage.onclick = function() {
                var cur = parseInt(document.getElementById('requirement-current-page').textContent) || 1;
                if (cur > 1) loadRequirementPage(cur - 1, pageSizeSelect.value);
            };
            if (btnNextPage) btnNextPage.onclick = function() {
                var cur = parseInt(document.getElementById('requirement-current-page').textContent) || 1;
                if (cur < totalPages) loadRequirementPage(cur + 1, pageSizeSelect.value);
            };
            if (btnLastPage) btnLastPage.onclick = function() {
                loadRequirementPage(totalPages, pageSizeSelect.value);
            };
            if (btnPageJump) btnPageJump.onclick = function() {
                var page = parseInt(pageInput.value) || 1;
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages;
                loadRequirementPage(page, pageSizeSelect.value);
            };
        }
        bindRequirementEvents();
    })();
</script>