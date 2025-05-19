<?php
// modules/customer_management/agency/agency_tabs/bank_info.php
// 代理机构银行账户管理
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
function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
$yesno = ['1' => '是', '0' => '否'];

// 处理保存/编辑/删除/获取单条
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $data = [
            'payee_name' => trim($_POST['payee_name'] ?? ''),
            'payee_address' => trim($_POST['payee_address'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'bank_branch' => trim($_POST['bank_branch'] ?? ''),
            'bank_address' => trim($_POST['bank_address'] ?? ''),
            'bank_account' => trim($_POST['bank_account'] ?? ''),
            'intermediary_bank' => trim($_POST['intermediary_bank'] ?? ''),
            'intermediary_account' => trim($_POST['intermediary_account'] ?? ''),
            'swift_code' => trim($_POST['swift_code'] ?? ''),
            'other_info' => trim($_POST['other_info'] ?? ''),
            'is_active' => intval($_POST['is_active'] ?? 1),
        ];
        if ($data['payee_name'] === '' || $data['bank_name'] === '' || $data['bank_account'] === '') {
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
                $sql = "UPDATE agency_bank_account SET $set WHERE id=:id AND agency_id=:agency_id";
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
                $sql = "INSERT INTO agency_bank_account ($fields) VALUES ($placeholders)";
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
            $stmt = $pdo->prepare("DELETE FROM agency_bank_account WHERE id=? AND agency_id=?");
            $ok = $stmt->execute([$id, $agency_id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM agency_bank_account WHERE id=? AND agency_id=?");
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
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM agency_bank_account WHERE agency_id=?");
$count_stmt->execute([$agency_id]);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $page_size);
// 当前页数据
$stmt = $pdo->prepare("SELECT * FROM agency_bank_account WHERE agency_id=:agency_id ORDER BY id DESC LIMIT :offset, :limit");
$stmt->bindValue(':agency_id', $agency_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $page_size, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<div class="module-panel">
    <div style="margin-bottom:12px;text-align:left;">
        <button type="button" class="btn-mini" id="btn-add-bank"><i class="icon-add"></i> 新增银行账户</button>
    </div>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:30px;">序号</th>
                <th style="width:120px;">收款人名称</th>
                <th style="width:120px;">开户银行</th>
                <th style="width:120px;">银行账号</th>
                <th style="width:120px;">分行</th>
                <th style="width:120px;">中转银行</th>
                <th style="width:100px;">Swift Code</th>
                <th style="width:60px;">有效</th>
                <th style="width:120px;">操作</th>
            </tr>
        </thead>
        <tbody id="bank-list">
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;color:#888;">暂无银行账户</td>
                </tr>
                <?php else: foreach ($rows as $i => $r): ?>
                    <tr data-id="<?= $r['id'] ?>">
                        <td style="text-align:center; width:40px;"> <?= $offset + $i + 1 ?> </td>
                        <td><?= h($r['payee_name']) ?></td>
                        <td><?= h($r['bank_name']) ?></td>
                        <td><?= h($r['bank_account']) ?></td>
                        <td><?= h($r['bank_branch']) ?></td>
                        <td><?= h($r['intermediary_bank']) ?></td>
                        <td><?= h($r['swift_code']) ?></td>
                        <td style="text-align:center;"><?= $r['is_active'] ? '是' : '否' ?></td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-mini btn-edit-bank">编辑</button>
                            <button type="button" class="btn-mini btn-del-bank" style="color:#f44336;">删除</button>
                        </td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>
    <div class="module-pagination" id="bank-pagination">
        <span>共 <span id="bank-total-records"><?= $total_records ?></span> 条记录，每页</span>
        <select id="bank-page-size-select">
            <option value="10" <?= $page_size == 10 ? ' selected' : '' ?>>10</option>
            <option value="20" <?= $page_size == 20 ? ' selected' : '' ?>>20</option>
            <option value="50" <?= $page_size == 50 ? ' selected' : '' ?>>50</option>
            <option value="100" <?= $page_size == 100 ? ' selected' : '' ?>>100</option>
        </select>
        <span>条，当前 <span id="bank-current-page"><?= $page ?></span>/<span id="bank-total-pages"><?= $total_pages ?></span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="bank-btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="<?= max(1, $page - 1) ?>" id="bank-btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="<?= min($total_pages, $page + 1) ?>" id="bank-btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="<?= $total_pages ?>" id="bank-btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="bank-page-input" min="1" value="<?= $page ?>">
        <span>页</span>
        <button type="button" id="bank-btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>
<div id="bank-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:600px;max-width:98vw;position:relative;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="bank-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">银行账户信息</h3>
        <form id="bank-form" class="module-form">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="agency_id" value="<?= $agency_id ?>">
            <table class="module-table" style="table-layout:fixed;width:100%;min-width:0;">
                <tr>
                    <td class="module-label module-req">*收款人名称</td>
                    <td><input type="text" name="payee_name" class="module-input" required></td>
                </tr>
                <tr>
                    <td class="module-label">收款人地址</td>
                    <td><input type="text" name="payee_address" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*开户银行</td>
                    <td><input type="text" name="bank_name" class="module-input" required></td>
                </tr>
                <tr>
                    <td class="module-label">分行</td>
                    <td><input type="text" name="bank_branch" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">银行地址</td>
                    <td><input type="text" name="bank_address" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*银行账号</td>
                    <td><input type="text" name="bank_account" class="module-input" required></td>
                </tr>
                <tr>
                    <td class="module-label">中转银行</td>
                    <td><input type="text" name="intermediary_bank" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">中转账号(ABA Routing No.)</td>
                    <td><input type="text" name="intermediary_account" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">银行国际代码(Swift Code No.)</td>
                    <td><input type="text" name="swift_code" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">其他信息</td>
                    <td><input type="text" name="other_info" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*是否有效</td>
                    <td>
                        <select name="is_active" class="module-input" required>
                            <?php foreach ($yesno as $k => $v): ?>
                                <option value="<?= h($k) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <div style="text-align:center;margin-top:12px;">
                <button type="button" class="btn-save-bank btn-mini" style="margin-right:16px;">保存</button>
                <button type="button" class="btn-cancel-bank btn-mini">取消</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function() {
        function bindBankEvents() {
            var modal = document.getElementById('bank-modal');
            var closeBtn = document.getElementById('bank-modal-close');
            var addBtn = document.getElementById('btn-add-bank');
            var list = document.getElementById('bank-list');
            var form = document.getElementById('bank-form');
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
                    agency_id: form.agency_id.value,
                    payee_name: '',
                    payee_address: '',
                    bank_name: '',
                    bank_branch: '',
                    bank_address: '',
                    bank_account: '',
                    intermediary_bank: '',
                    intermediary_account: '',
                    swift_code: '',
                    other_info: '',
                    is_active: 1
                });
            };
            closeBtn.onclick = hideModal;
            form.querySelector('.btn-cancel-bank').onclick = hideModal;
            list.onclick = function(e) {
                var tr = e.target.closest('tr[data-id]');
                if (!tr) return;
                var id = tr.getAttribute('data-id');
                if (e.target.classList.contains('btn-edit-bank')) {
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'get');
                    fd.append('id', id);
                    fd.append('agency_id', form.agency_id.value);
                    xhr.open('POST', 'modules/customer_management/agency/agency_tabs/bank_info.php', true);
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
                } else if (e.target.classList.contains('btn-del-bank')) {
                    if (!confirm('确定删除该银行账户？')) return;
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    fd.append('agency_id', form.agency_id.value);
                    xhr.open('POST', 'modules/customer_management/agency/agency_tabs/bank_info.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                                if (tabContent) {
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', 'modules/customer_management/agency/agency_tabs/bank_info.php?agency_id=' + form.agency_id.value, true);
                                    xhr2.onload = function() {
                                        tabContent.innerHTML = xhr2.responseText;
                                        setTimeout(bindBankEvents, 0);
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
            form.querySelector('.btn-save-bank').onclick = function() {
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/agency/agency_tabs/bank_info.php', true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                            if (tabContent) {
                                var xhr2 = new XMLHttpRequest();
                                xhr2.open('GET', 'modules/customer_management/agency/agency_tabs/bank_info.php?agency_id=' + form.agency_id.value, true);
                                xhr2.onload = function() {
                                    tabContent.innerHTML = xhr2.responseText;
                                    setTimeout(bindBankEvents, 0);
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
            var pageSizeSelect = document.getElementById('bank-page-size-select');
            var btnFirstPage = document.getElementById('bank-btn-first-page');
            var btnPrevPage = document.getElementById('bank-btn-prev-page');
            var btnNextPage = document.getElementById('bank-btn-next-page');
            var btnLastPage = document.getElementById('bank-btn-last-page');
            var pageInput = document.getElementById('bank-page-input');
            var btnPageJump = document.getElementById('bank-btn-page-jump');
            var totalPages = parseInt(document.getElementById('bank-total-pages').textContent) || 1;
            var agencyId = document.querySelector('[name=agency_id]').value;

            function loadBankPage(page, pageSize) {
                var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                var url = 'modules/customer_management/agency/agency_tabs/bank_info.php?agency_id=' + agencyId + '&page=' + page + '&page_size=' + pageSize;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.onload = function() {
                    if (tabContent) {
                        tabContent.innerHTML = xhr.responseText;
                        setTimeout(bindBankEvents, 0);
                    }
                };
                xhr.send();
            }
            if (pageSizeSelect) pageSizeSelect.onchange = function() {
                loadBankPage(1, this.value);
            };
            if (btnFirstPage) btnFirstPage.onclick = function() {
                loadBankPage(1, pageSizeSelect.value);
            };
            if (btnPrevPage) btnPrevPage.onclick = function() {
                var cur = parseInt(document.getElementById('bank-current-page').textContent) || 1;
                if (cur > 1) loadBankPage(cur - 1, pageSizeSelect.value);
            };
            if (btnNextPage) btnNextPage.onclick = function() {
                var cur = parseInt(document.getElementById('bank-current-page').textContent) || 1;
                if (cur < totalPages) loadBankPage(cur + 1, pageSizeSelect.value);
            };
            if (btnLastPage) btnLastPage.onclick = function() {
                loadBankPage(totalPages, pageSizeSelect.value);
            };
            if (btnPageJump) btnPageJump.onclick = function() {
                var page = parseInt(pageInput.value) || 1;
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages;
                loadBankPage(page, pageSizeSelect.value);
            };
        }
        // 首次绑定
        bindBankEvents();
    })();
</script>