<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => '未登录或会话超时']);
        exit;
    } else {
        header('Location: /login.php');
        exit;
    }
}

// 查询所有客户用于下拉
$customer_stmt = $pdo->prepare("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn ASC");
$customer_stmt->execute();
$customers = $customer_stmt->fetchAll();

// 查询所有在职用户用于下拉
$user_stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name ASC");
$user_stmt->execute();
$users = $user_stmt->fetchAll();

// 处理保存/编辑/删除/获取单条的AJAX请求
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
                $sql = "UPDATE contact_record SET $set WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                foreach ($data as $k => $v) {
                    $stmt->bindValue(":$k", $v);
                }
                $stmt->bindValue(":id", $id, PDO::PARAM_INT);
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
            $stmt = $pdo->prepare("DELETE FROM contact_record WHERE id=?");
            $ok = $stmt->execute([$id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'msg' => '参数错误']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM contact_record WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'msg' => '未找到对应的联系记录']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $row]);
        exit;
    } elseif ($action === 'get_contacts') {
        $customer_id = intval($_POST['customer_id'] ?? 0);
        if ($customer_id <= 0) {
            echo json_encode(['success' => false, 'msg' => '请选择客户']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, name FROM contact WHERE customer_id=? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$customer_id]);
        $contacts = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $contacts]);
        exit;
    }
}

// 处理列表数据查询的AJAX请求
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    $page = max(1, intval($_GET['page'] ?? 1));
    $page_size = min(max(1, intval($_GET['page_size'] ?? 10)), 100);
    $offset = ($page - 1) * $page_size;
    $where = [];
    $params = [];

    // 查询条件
    if (!empty($_GET['contact_name'])) {
        $where[] = 'con.name LIKE :contact_name';
        $params['contact_name'] = '%' . $_GET['contact_name'] . '%';
    }
    if (!empty($_GET['customer_id'])) {
        $where[] = 'c.id = :customer_id';
        $params['customer_id'] = $_GET['customer_id'];
    }
    if (!empty($_GET['contact_method'])) {
        $where[] = 'r.contact_method = :contact_method';
        $params['contact_method'] = $_GET['contact_method'];
    }
    if (!empty($_GET['contact_type'])) {
        $where[] = 'r.contact_type = :contact_type';
        $params['contact_type'] = $_GET['contact_type'];
    }
    if (!empty($_GET['user_id'])) {
        $where[] = 'r.user_id = :user_id';
        $params['user_id'] = $_GET['user_id'];
    }
    if (!empty($_GET['contact_time_from'])) {
        $where[] = 'r.contact_time >= :contact_time_from';
        $params['contact_time_from'] = $_GET['contact_time_from'];
    }
    if (!empty($_GET['contact_time_to'])) {
        $where[] = 'r.contact_time <= :contact_time_to';
        $params['contact_time_to'] = $_GET['contact_time_to'];
    }
    if (!empty($_GET['content'])) {
        $where[] = 'r.content LIKE :content';
        $params['content'] = '%' . $_GET['content'] . '%';
    }

    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $count_sql = "SELECT COUNT(*) FROM contact_record r 
                  JOIN contact con ON r.contact_id = con.id
                  JOIN customer c ON con.customer_id = c.id
                  LEFT JOIN user u ON r.user_id = u.id
                  $sql_where";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);

    $sql = "SELECT r.*, con.name as contact_name, c.customer_name_cn, c.id as customer_id, u.real_name as user_name 
            FROM contact_record r
            JOIN contact con ON r.contact_id = con.id
            JOIN customer c ON con.customer_id = c.id
            LEFT JOIN user u ON r.user_id = u.id
            $sql_where 
            ORDER BY r.id DESC 
            LIMIT :offset, :limit";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $page_size, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $html = '';
    if (empty($rows)) {
        $html = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($rows as $index => $r) {
            $html .= '<tr data-id="' . $r['id'] . '" data-contact-id="' . $r['contact_id'] . '" data-customer-id="' . $r['customer_id'] . '">' .
                '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>' .
                '<td>' . htmlspecialchars($r['customer_name_cn'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($r['contact_name'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($r['contact_time'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($r['contact_method'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($r['contact_type'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars(mb_substr($r['content'], 0, 30) . (mb_strlen($r['content']) > 30 ? '...' : '')) . '</td>' .
                '<td>' . htmlspecialchars($r['user_name'] ?? '') . '</td>' .
                '<td>' . ($r['created_at'] ? date('Y-m-d', strtotime($r['created_at'])) : '') . '</td>' .
                '<td style="text-align:center;">' .
                '<button type="button" class="btn-mini btn-edit">✎</button> ' .
                '<button type="button" class="btn-mini btn-del" style="color:#f44336;">✖</button>' .
                '</td>' .
                '</tr>';
        }
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);
    exit;
}

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// 字典选项
$contact_methods = ['电话', '拜访', '邮件', '微信', '短信', 'QQ', '其他'];
$contact_types = ['案件通知', '费用通知', '官文通知', '售前', '售后', '回访', '其他'];
?>
<div class="module-panel">
    <form id="search-form" class="module-form" autocomplete="off" style="margin-bottom:12px;">
        <div class="module-btns">
            <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
            <button type="button" class="btn-add"><i class="icon-add"></i> 添加联系记录</button>
        </div>
        <table class="module-table" style="margin-bottom:10px;width:100%;min-width:0;">
            <tr>
                <td class="module-label" style="width:80px;">客户名称：</td>
                <td style="width:220px;">
                    <select name="customer_id" class="module-input" style="width:200px;">
                        <option value="">--全部--</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= h($c['id']) ?>"><?= h($c['customer_name_cn']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="module-label" style="width:80px;">联系人：</td>
                <td style="width:220px;"><input type="text" name="contact_name" class="module-input" style="width:200px;"></td>
            </tr>
            <tr>
                <td class="module-label">联系时间：</td>
                <td>
                    <input type="date" name="contact_time_from" class="module-input" style="width:93.5px;display:inline-block;"> -
                    <input type="date" name="contact_time_to" class="module-input" style="width:93.5px;display:inline-block;">
                </td>
                <td class="module-label">联系方式：</td>
                <td>
                    <select name="contact_method" class="module-input" style="width:200px;">
                        <option value="">--全部--</option>
                        <?php foreach ($contact_methods as $v): ?>
                            <option value="<?= h($v) ?>"><?= h($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="module-label">联系类型：</td>
                <td>
                    <select name="contact_type" class="module-input" style="width:200px;">
                        <option value="">--全部--</option>
                        <?php foreach ($contact_types as $v): ?>
                            <option value="<?= h($v) ?>"><?= h($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="module-label">我方联系人：</td>
                <td>
                    <select name="user_id" class="module-input" style="width:200px;">
                        <option value="">--全部--</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= h($u['id']) ?>"><?= h($u['real_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="module-label">联系内容：</td>
                <td colspan="3"><input type="text" name="content" class="module-input" style="width:678px;"></td>
            </tr>
        </table>
    </form>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:40px;text-align:center;">序号</th>
                <th style="width:140px;">客户名称</th>
                <th style="width:120px;">联系人</th>
                <th style="width:100px;">联系时间</th>
                <th style="width:100px;">联系方式</th>
                <th style="width:100px;">联系类型</th>
                <th style="width:180px;">联系内容</th>
                <th style="width:100px;">我方联系人</th>
                <th style="width:100px;">创建时间</th>
                <th style="width:90px;">操作</th>
            </tr>
        </thead>
        <tbody id="record-list">
            <tr>
                <td colspan="10" style="text-align:center;padding:20px 0;">正在加载数据...</td>
            </tr>
        </tbody>
    </table>
    <div class="module-pagination">
        <span>共 <span id="total-records">0</span> 条记录，每页</span>
        <select id="page-size-select">
            <option value="10" selected>10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        <span>条，当前 <span id="current-page">1</span>/<span id="total-pages">1</span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="" id="btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="" id="btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="" id="btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="page-input" min="1" value="1">
        <span>页</span>
        <button type="button" id="btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>

<!-- 编辑/添加记录模态窗口 -->
<div id="edit-record-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:6px;box-shadow:0 2px 12px rgba(0,0,0,0.18);padding:24px 32px;min-width:700px;max-width:98vw;position:relative;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="edit-record-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">联系记录</h3>
        <form id="edit-record-form" class="module-form">
            <input type="hidden" name="id" value="0">
            <table class="module-table">
                <tr>
                    <td class="module-label module-req">*客户</td>
                    <td>
                        <select name="customer_id" class="module-input" required>
                            <option value="">--请选择--</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= h($c['id']) ?>"><?= h($c['customer_name_cn']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="module-label module-req">*客户联系人</td>
                    <td>
                        <select name="contact_id" class="module-input" required>
                            <option value="">--请先选择客户--</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="module-label module-req">*联系时间</td>
                    <td><input type="date" name="contact_time" class="module-input" required></td>
                    <td class="module-label module-req">*联系方式</td>
                    <td>
                        <select name="contact_method" class="module-input" required>
                            <option value="">--请选择--</option>
                            <?php foreach ($contact_methods as $v): ?>
                                <option value="<?= h($v) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="module-label module-req">*联系类型</td>
                    <td>
                        <select name="contact_type" class="module-input" required>
                            <option value="">--请选择--</option>
                            <?php foreach ($contact_types as $v): ?>
                                <option value="<?= h($v) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="module-label module-req">*我方联系人</td>
                    <td>
                        <select name="user_id" class="module-input" required>
                            <option value="">--请选择--</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= h($u['id']) ?>" <?= $u['id'] == $_SESSION['user_id'] ? ' selected' : '' ?>><?= h($u['real_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="module-label module-req">*联系内容</td>
                    <td colspan="3"><textarea name="content" class="module-input" style="min-height:60px;" required></textarea></td>
                </tr>
            </table>
            <div style="text-align:center;margin-top:12px;">
                <button type="button" class="btn-save-record btn-mini" style="margin-right:16px;">保存</button>
                <button type="button" class="btn-cancel-record btn-mini">取消</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        var form = document.getElementById('search-form'),
            btnSearch = form.querySelector('.btn-search'),
            btnReset = form.querySelector('.btn-reset'),
            btnAdd = form.querySelector('.btn-add'),
            recordList = document.getElementById('record-list'),
            totalRecordsEl = document.getElementById('total-records'),
            currentPageEl = document.getElementById('current-page'),
            totalPagesEl = document.getElementById('total-pages'),
            btnFirstPage = document.getElementById('btn-first-page'),
            btnPrevPage = document.getElementById('btn-prev-page'),
            btnNextPage = document.getElementById('btn-next-page'),
            btnLastPage = document.getElementById('btn-last-page'),
            pageInput = document.getElementById('page-input'),
            btnPageJump = document.getElementById('btn-page-jump'),
            pageSizeSelect = document.getElementById('page-size-select');
        var currentPage = 1,
            pageSize = 10,
            totalPages = 1;

        // 初始化编辑模态框事件
        var editModal = document.getElementById('edit-record-modal'),
            editForm = document.getElementById('edit-record-form'),
            editModalClose = document.getElementById('edit-record-modal-close'),
            btnSaveRecord = editForm.querySelector('.btn-save-record'),
            btnCancelRecord = editForm.querySelector('.btn-cancel-record');

        // 绑定客户下拉框变更事件，加载对应的联系人
        editForm.querySelector('select[name="customer_id"]').addEventListener('change', function() {
            var customerId = this.value;
            if (!customerId) {
                editForm.querySelector('select[name="contact_id"]').innerHTML = '<option value="">--请先选择客户--</option>';
                return;
            }

            var xhr = new XMLHttpRequest();
            var fd = new FormData();
            fd.append('action', 'get_contacts');
            fd.append('customer_id', customerId);
            xhr.open('POST', 'modules/customer_management/customer/contact_records.php', true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        var contactSelect = editForm.querySelector('select[name="contact_id"]');
                        var html = '<option value="">--请选择--</option>';
                        res.data.forEach(function(contact) {
                            html += '<option value="' + contact.id + '">' + contact.name + '</option>';
                        });
                        contactSelect.innerHTML = html;
                    } else {
                        alert(res.msg || '获取联系人失败');
                    }
                } catch (e) {
                    alert('获取联系人失败');
                }
            };
            xhr.send(fd);
        });

        // 关闭模态框
        editModalClose.onclick = btnCancelRecord.onclick = function() {
            editModal.style.display = 'none';
        };

        // 保存记录
        btnSaveRecord.onclick = function() {
            if (!editForm.checkValidity()) {
                alert('请填写所有必填项');
                return;
            }

            var fd = new FormData(editForm);
            fd.append('action', 'save');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/customer/contact_records.php', true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        editModal.style.display = 'none';
                        loadRecordData();
                    } else {
                        alert(res.msg || '保存失败');
                    }
                } catch (e) {
                    alert('保存失败');
                }
            };
            xhr.send(fd);
        };

        function loadRecordData() {
            recordList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);
            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') params.append(pair[0], pair[1]);
            }
            var requestUrl = 'modules/customer_management/customer/contact_records.php';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                recordList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                            } else {
                                recordList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            recordList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        recordList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            recordList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.querySelector('.btn-del').onclick = function() {
                    if (!confirm('确定删除该联系记录？')) return;
                    var id = row.getAttribute('data-id');
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    xhr.open('POST', 'modules/customer_management/customer/contact_records.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                loadRecordData();
                            } else {
                                alert(res.msg || '删除失败');
                            }
                        } catch (e) {
                            alert('删除失败');
                        }
                    };
                    xhr.send(fd);
                };

                row.querySelector('.btn-edit').onclick = function() {
                    var id = row.getAttribute('data-id');
                    var customerId = row.getAttribute('data-customer-id');
                    var contactId = row.getAttribute('data-contact-id');

                    // 重置表单
                    editForm.reset();
                    editForm.id.value = id;

                    // 设置客户并加载联系人
                    var customerSelect = editForm.querySelector('select[name="customer_id"]');
                    customerSelect.value = customerId;

                    // 触发客户选择变更事件，加载联系人
                    var customEvent = new Event('change');
                    customerSelect.dispatchEvent(customEvent);

                    // 再获取记录详情
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'get');
                    fd.append('id', id);
                    xhr.open('POST', 'modules/customer_management/customer/contact_records.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                // 直接设置表单值，不使用延时
                                editForm.querySelector('select[name="contact_id"]').value = contactId;
                                editForm.contact_time.value = res.data.contact_time;
                                editForm.contact_method.value = res.data.contact_method;
                                editForm.contact_type.value = res.data.contact_type;
                                editForm.content.value = res.data.content;
                                editForm.user_id.value = res.data.user_id;

                                // 显示模态框
                                editModal.style.display = 'flex';
                            } else {
                                alert(res.msg || '获取记录失败');
                            }
                        } catch (e) {
                            alert('获取记录失败');
                        }
                    };
                    xhr.send(fd);
                };
            });
        }

        function updatePaginationButtons() {
            btnFirstPage.disabled = currentPage <= 1;
            btnPrevPage.disabled = currentPage <= 1;
            btnNextPage.disabled = currentPage >= totalPages;
            btnLastPage.disabled = currentPage >= totalPages;
            btnPrevPage.setAttribute('data-page', currentPage - 1);
            btnNextPage.setAttribute('data-page', currentPage + 1);
            btnLastPage.setAttribute('data-page', totalPages);
            pageInput.max = totalPages;
            pageInput.value = currentPage;
        }

        btnSearch.onclick = function() {
            currentPage = 1;
            loadRecordData();
        };

        btnReset.onclick = function() {
            form.reset();
            currentPage = 1;
            loadRecordData();
        };

        btnAdd.onclick = function() {
            // 重置表单
            editForm.reset();
            editForm.id.value = 0;

            // 设置默认值
            var today = new Date().toISOString().split('T')[0];
            editForm.contact_time.value = today;
            editForm.user_id.value = <?= $_SESSION['user_id'] ?>;

            // 清空联系人下拉框
            editForm.querySelector('select[name="contact_id"]').innerHTML = '<option value="">--请先选择客户--</option>';

            // 显示模态框
            editModal.style.display = 'flex';
        };

        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            loadRecordData();
        };

        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    loadRecordData();
                }
            };
        });

        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            loadRecordData();
        };

        // 初始加载数据
        loadRecordData();
    })();
</script>