<?php
// modules/customer_management/customer/customer_tabs/customer_file.php
// 客户文件管理
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

// 文件类型字典（可根据实际需求扩展）
$file_types = ['合同', '营业执照', '身份证', '委托书', '其他'];

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function get_download_name($file_name, $file_path)
{
    if (!$file_name) return basename($file_path);
    if (strpos($file_name, '.') !== false) return $file_name;
    $ext = '';
    if (strpos($file_path, '.') !== false) {
        $ext = substr($file_path, strrpos($file_path, '.'));
    }
    return $file_name . $ext;
}

// 处理保存/编辑/删除/获取单条
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $data = [
            'file_name' => trim($_POST['file_name'] ?? ''),
            'file_type' => trim($_POST['file_type'] ?? ''),
            'file_desc' => trim($_POST['file_desc'] ?? ''),
            'uploader_id' => $_SESSION['user_id'],
        ];
        // 文件上传处理
        if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $save_dir = '/uploads/customer_file/' . date('Ymd') . '/';
            $abs_dir = $_SERVER['DOCUMENT_ROOT'] . $save_dir;
            if (!is_dir($abs_dir)) mkdir($abs_dir, 0777, true);
            $new_name = uniqid('cf_', true) . '.' . $ext;
            $save_path = $abs_dir . $new_name;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $save_path)) {
                $data['file_path'] = $save_dir . $new_name;
            } else {
                echo json_encode(['success' => false, 'msg' => '文件保存失败']);
                exit;
            }
        } elseif ($id == 0) {
            echo json_encode(['success' => false, 'msg' => '请上传文件']);
            exit;
        }
        if ($data['file_name'] === '' || $data['file_type'] === '') {
            echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
            exit;
        }
        try {
            if ($id > 0) {
                // 编辑时不强制要求重新上传文件
                $set = '';
                foreach ($data as $k => $v) {
                    if ($k == 'file_path' && !$v) continue;
                    $set .= "$k=:$k,";
                }
                $set = rtrim($set, ',');
                $sql = "UPDATE customer_file SET $set WHERE id=:id AND customer_id=:customer_id";
                $stmt = $pdo->prepare($sql);
                foreach ($data as $k => $v) {
                    if ($k == 'file_path' && !$v) continue;
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
                $sql = "INSERT INTO customer_file ($fields) VALUES ($placeholders)";
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
            $stmt = $pdo->prepare("DELETE FROM customer_file WHERE id=? AND customer_id=?");
            $ok = $stmt->execute([$id, $customer_id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM customer_file WHERE id=? AND customer_id=?");
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
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_file WHERE customer_id=?");
$count_stmt->execute([$customer_id]);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $page_size);
// 当前页数据
$stmt = $pdo->prepare("SELECT cf.*, u.real_name as uploader_name FROM customer_file cf LEFT JOIN user u ON cf.uploader_id=u.id WHERE cf.customer_id=:customer_id ORDER BY cf.id DESC LIMIT :offset, :limit");
$stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $page_size, PDO::PARAM_INT);
$stmt->execute();
$files = $stmt->fetchAll();
?>
<div class="module-panel">
    <div style="margin-bottom:12px;text-align:left;">
        <button type="button" class="btn-mini" id="btn-add-file"><i class="icon-add"></i> 添加文件</button>
    </div>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:30px;">序号</th>
                <th style="width:200px;">附件名称</th>
                <th style="width:120px;">文件类型</th>
                <th style="width:200px;">文件描述</th>
                <th style="width:100px;">上传者</th>
                <th style="width:140px;">上传时间</th>
                <th style="width:110px;">操作</th>
            </tr>
        </thead>
        <tbody id="file-list">
            <?php if (empty($files)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:#888;">暂无文件</td>
                </tr>
                <?php else: foreach ($files as $i => $f): ?>
                    <tr data-id="<?= $f['id'] ?>">
                        <td style="text-align:center; width:40px;"> <?= $offset + $i + 1 ?> </td>
                        <td style="width:200px;">
                            <?= h($f['file_name']) ?>
                        </td>
                        <td style="width:120px;"> <?= h($f['file_type']) ?> </td>
                        <td style="width:200px;"> <?= h($f['file_desc']) ?> </td>
                        <td style="width:100px;"> <?= h($f['uploader_name']) ?> </td>
                        <td style="width:140px;"> <?= $f['upload_time'] ? date('Y-m-d H:i', strtotime($f['upload_time'])) : '' ?> </td>
                        <td style="width:180px; text-align:center;">
                            <?php if ($f['file_path']): ?>
                                <button type="button" class="btn-mini btn-download-file"
                                    data-url="<?= h($f['file_path']) ?>"
                                    data-name="<?= h(get_download_name($f['file_name'], $f['file_path'])) ?>">
                                    下载
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-mini" disabled>下载</button>
                            <?php endif; ?>
                            <button type="button" class="btn-mini btn-edit-file">编辑</button>
                            <button type="button" class="btn-mini btn-del-file">删除</button>
                        </td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>
    <div class="module-pagination" id="file-pagination">
        <span>共 <span id="file-total-records"><?= $total_records ?></span> 条记录，每页</span>
        <select id="file-page-size-select">
            <option value="10" <?= $page_size == 10 ? ' selected' : '' ?>>10</option>
            <option value="20" <?= $page_size == 20 ? ' selected' : '' ?>>20</option>
            <option value="50" <?= $page_size == 50 ? ' selected' : '' ?>>50</option>
            <option value="100" <?= $page_size == 100 ? ' selected' : '' ?>>100</option>
        </select>
        <span>条，当前 <span id="file-current-page"><?= $page ?></span>/<span id="file-total-pages"><?= $total_pages ?></span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="file-btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="<?= max(1, $page - 1) ?>" id="file-btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="<?= min($total_pages, $page + 1) ?>" id="file-btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="<?= $total_pages ?>" id="file-btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="file-page-input" min="1" value="<?= $page ?>">
        <span>页</span>
        <button type="button" id="file-btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>
<div id="file-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:600px;max-width:98vw;position:relative;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="file-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">客户文件</h3>
        <form id="file-form" class="module-form" enctype="multipart/form-data">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="customer_id" value="<?= $customer_id ?>">
            <table class="module-table" style="table-layout:fixed;width:100%;min-width:0;">
                <tr>
                    <td class="module-label module-req">*附件名称</td>
                    <td><input type="text" name="file_name" class="module-input" required></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*文件类型</td>
                    <td>
                        <input type="text" name="file_type" class="module-input" required>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">文件描述</td>
                    <td><input type="text" name="file_desc" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*上传文件</td>
                    <td>
                        <input type="file" name="file" class="module-input" style="padding:3px 8px;">
                        <span id="file-upload-tip" style="color:#888;font-size:13px;">（编辑时可不重新上传）</span>
                    </td>
                </tr>
            </table>
            <div style="text-align:center;margin-top:12px;">
                <button type="button" class="btn-save-file btn-mini" style="margin-right:16px;">保存</button>
                <button type="button" class="btn-cancel-file btn-mini">取消</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function() {
        function bindFileEvents() {
            var modal = document.getElementById('file-modal');
            var closeBtn = document.getElementById('file-modal-close');
            var addBtn = document.getElementById('btn-add-file');
            var list = document.getElementById('file-list');
            var form = document.getElementById('file-form');
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
                    file_name: '',
                    file_type: '',
                    file_desc: ''
                });
            };
            closeBtn.onclick = hideModal;
            form.querySelector('.btn-cancel-file').onclick = hideModal;
            list.onclick = function(e) {
                var tr = e.target.closest('tr[data-id]');
                if (!tr) return;
                var id = tr.getAttribute('data-id');
                if (e.target.classList.contains('btn-download-file')) {
                    var url = e.target.getAttribute('data-url');
                    var name = e.target.getAttribute('data-name');
                    if (url && name) {
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = name;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }
                    return;
                }
                if (e.target.classList.contains('btn-edit-file')) {
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'get');
                    fd.append('id', id);
                    fd.append('customer_id', form.customer_id.value);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/customer_file.php', true);
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
                } else if (e.target.classList.contains('btn-del-file')) {
                    if (!confirm('确定删除该文件？')) return;
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    fd.append('customer_id', form.customer_id.value);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/customer_file.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                                if (tabContent) {
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', 'modules/customer_management/customer/customer_tabs/customer_file.php?customer_id=' + form.customer_id.value, true);
                                    xhr2.onload = function() {
                                        tabContent.innerHTML = xhr2.responseText;
                                        setTimeout(bindFileEvents, 0);
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
            form.querySelector('.btn-save-file').onclick = function() {
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/customer/customer_tabs/customer_file.php', true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                            if (tabContent) {
                                var xhr2 = new XMLHttpRequest();
                                xhr2.open('GET', 'modules/customer_management/customer/customer_tabs/customer_file.php?customer_id=' + form.customer_id.value, true);
                                xhr2.onload = function() {
                                    tabContent.innerHTML = xhr2.responseText;
                                    setTimeout(bindFileEvents, 0);
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
            var pageSizeSelect = document.getElementById('file-page-size-select');
            var btnFirstPage = document.getElementById('file-btn-first-page');
            var btnPrevPage = document.getElementById('file-btn-prev-page');
            var btnNextPage = document.getElementById('file-btn-next-page');
            var btnLastPage = document.getElementById('file-btn-last-page');
            var pageInput = document.getElementById('file-page-input');
            var btnPageJump = document.getElementById('file-btn-page-jump');
            var totalPages = parseInt(document.getElementById('file-total-pages').textContent) || 1;
            var customerId = document.querySelector('[name=customer_id]').value;

            function loadFilePage(page, pageSize) {
                var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                var url = 'modules/customer_management/customer/customer_tabs/customer_file.php?customer_id=' + customerId + '&page=' + page + '&page_size=' + pageSize;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.onload = function() {
                    if (tabContent) {
                        tabContent.innerHTML = xhr.responseText;
                        setTimeout(bindFileEvents, 0);
                    }
                };
                xhr.send();
            }
            if (pageSizeSelect) pageSizeSelect.onchange = function() {
                loadFilePage(1, this.value);
            };
            if (btnFirstPage) btnFirstPage.onclick = function() {
                loadFilePage(1, pageSizeSelect.value);
            };
            if (btnPrevPage) btnPrevPage.onclick = function() {
                var cur = parseInt(document.getElementById('file-current-page').textContent) || 1;
                if (cur > 1) loadFilePage(cur - 1, pageSizeSelect.value);
            };
            if (btnNextPage) btnNextPage.onclick = function() {
                var cur = parseInt(document.getElementById('file-current-page').textContent) || 1;
                if (cur < totalPages) loadFilePage(cur + 1, pageSizeSelect.value);
            };
            if (btnLastPage) btnLastPage.onclick = function() {
                loadFilePage(totalPages, pageSizeSelect.value);
            };
            if (btnPageJump) btnPageJump.onclick = function() {
                var page = parseInt(pageInput.value) || 1;
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages;
                loadFilePage(page, pageSizeSelect.value);
            };
        }
        // 首次绑定
        bindFileEvents();
    })();
</script>