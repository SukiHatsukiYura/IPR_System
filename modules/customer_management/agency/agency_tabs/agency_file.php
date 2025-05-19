<?php
// modules/customer_management/agency/agency_tabs/agency_file.php
// 代理机构文件管理
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
            $save_dir = '/uploads/agency_file/' . date('Ymd') . '/';
            $abs_dir = $_SERVER['DOCUMENT_ROOT'] . $save_dir;
            if (!is_dir($abs_dir)) mkdir($abs_dir, 0777, true);
            $new_name = uniqid('af_', true) . '.' . $ext;
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
                $sql = "UPDATE agency_file SET $set WHERE id=:id AND agency_id=:agency_id";
                $stmt = $pdo->prepare($sql);
                foreach ($data as $k => $v) {
                    if ($k == 'file_path' && !$v) continue;
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
                $sql = "INSERT INTO agency_file ($fields) VALUES ($placeholders)";
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
            $stmt = $pdo->prepare("DELETE FROM agency_file WHERE id=? AND agency_id=?");
            $ok = $stmt->execute([$id, $agency_id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM agency_file WHERE id=? AND agency_id=?");
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
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM agency_file WHERE agency_id=?");
$count_stmt->execute([$agency_id]);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $page_size);
// 当前页数据
$stmt = $pdo->prepare("SELECT af.*, u.real_name as uploader_name FROM agency_file af LEFT JOIN user u ON af.uploader_id=u.id WHERE af.agency_id=:agency_id ORDER BY af.id DESC LIMIT :offset, :limit");
$stmt->bindValue(':agency_id', $agency_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $page_size, PDO::PARAM_INT);
$stmt->execute();
$files = $stmt->fetchAll();
?>
<div class="module-panel">
    <div style="margin-bottom:12px;text-align:left;">
        <button type="button" class="btn-mini" id="btn-add-agency-file"><i class="icon-add"></i> 添加文件</button>
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
        <tbody id="agency-file-list">
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
                                <button type="button" class="btn-mini btn-download-agency-file"
                                    data-url="<?= h($f['file_path']) ?>"
                                    data-name="<?= h(get_download_name($f['file_name'], $f['file_path'])) ?>">
                                    下载
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-mini" disabled>下载</button>
                            <?php endif; ?>
                            <button type="button" class="btn-mini btn-edit-agency-file">编辑</button>
                            <button type="button" class="btn-mini btn-del-agency-file">删除</button>
                        </td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>
    <div class="module-pagination" id="agency-file-pagination">
        <span>共 <span id="agency-file-total-records"><?= $total_records ?></span> 条记录，每页</span>
        <select id="agency-file-page-size-select">
            <option value="10" <?= $page_size == 10 ? ' selected' : '' ?>>10</option>
            <option value="20" <?= $page_size == 20 ? ' selected' : '' ?>>20</option>
            <option value="50" <?= $page_size == 50 ? ' selected' : '' ?>>50</option>
            <option value="100" <?= $page_size == 100 ? ' selected' : '' ?>>100</option>
        </select>
        <span>条，当前 <span id="agency-file-current-page"><?= $page ?></span>/<span id="agency-file-total-pages"><?= $total_pages ?></span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="agency-file-btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="<?= max(1, $page - 1) ?>" id="agency-file-btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="<?= min($total_pages, $page + 1) ?>" id="agency-file-btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="<?= $total_pages ?>" id="agency-file-btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="agency-file-page-input" min="1" value="<?= $page ?>">
        <span>页</span>
        <button type="button" id="agency-file-btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>
<div id="agency-file-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:600px;max-width:98vw;position:relative;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="agency-file-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">代理机构文件</h3>
        <form id="agency-file-form" class="module-form" enctype="multipart/form-data">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="agency_id" value="<?= $agency_id ?>">
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
                        <span id="agency-file-upload-tip" style="color:#888;font-size:13px;">（编辑时可不重新上传）</span>
                    </td>
                </tr>
            </table>
            <div style="text-align:center;margin-top:12px;">
                <button type="button" class="btn-save-agency-file btn-mini" style="margin-right:16px;">保存</button>
                <button type="button" class="btn-cancel-agency-file btn-mini">取消</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function() {
        function bindAgencyFileEvents() {
            var modal = document.getElementById('agency-file-modal');
            var closeBtn = document.getElementById('agency-file-modal-close');
            var addBtn = document.getElementById('btn-add-agency-file');
            var list = document.getElementById('agency-file-list');
            var form = document.getElementById('agency-file-form');
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
                    file_name: '',
                    file_type: '',
                    file_desc: ''
                });
            };
            closeBtn.onclick = hideModal;
            form.querySelector('.btn-cancel-agency-file').onclick = hideModal;
            list.onclick = function(e) {
                var tr = e.target.closest('tr[data-id]');
                if (!tr) return;
                var id = tr.getAttribute('data-id');
                if (e.target.classList.contains('btn-download-agency-file')) {
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
                if (e.target.classList.contains('btn-edit-agency-file')) {
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'get');
                    fd.append('id', id);
                    fd.append('agency_id', form.agency_id.value);
                    xhr.open('POST', 'modules/customer_management/agency/agency_tabs/agency_file.php', true);
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
                } else if (e.target.classList.contains('btn-del-agency-file')) {
                    if (!confirm('确定删除该文件？')) return;
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    fd.append('agency_id', form.agency_id.value);
                    xhr.open('POST', 'modules/customer_management/agency/agency_tabs/agency_file.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                                if (tabContent) {
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', 'modules/customer_management/agency/agency_tabs/agency_file.php?agency_id=' + form.agency_id.value, true);
                                    xhr2.onload = function() {
                                        tabContent.innerHTML = xhr2.responseText;
                                        setTimeout(bindAgencyFileEvents, 0);
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
            form.querySelector('.btn-save-agency-file').onclick = function() {
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/agency/agency_tabs/agency_file.php', true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                            if (tabContent) {
                                var xhr2 = new XMLHttpRequest();
                                xhr2.open('GET', 'modules/customer_management/agency/agency_tabs/agency_file.php?agency_id=' + form.agency_id.value, true);
                                xhr2.onload = function() {
                                    tabContent.innerHTML = xhr2.responseText;
                                    setTimeout(bindAgencyFileEvents, 0);
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
            var pageSizeSelect = document.getElementById('agency-file-page-size-select');
            var btnFirstPage = document.getElementById('agency-file-btn-first-page');
            var btnPrevPage = document.getElementById('agency-file-btn-prev-page');
            var btnNextPage = document.getElementById('agency-file-btn-next-page');
            var btnLastPage = document.getElementById('agency-file-btn-last-page');
            var pageInput = document.getElementById('agency-file-page-input');
            var btnPageJump = document.getElementById('agency-file-btn-page-jump');
            var totalPages = parseInt(document.getElementById('agency-file-total-pages').textContent) || 1;
            var agencyId = document.querySelector('[name=agency_id]').value;

            function loadAgencyFilePage(page, pageSize) {
                var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                var url = 'modules/customer_management/agency/agency_tabs/agency_file.php?agency_id=' + agencyId + '&page=' + page + '&page_size=' + pageSize;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.onload = function() {
                    if (tabContent) {
                        tabContent.innerHTML = xhr.responseText;
                        setTimeout(bindAgencyFileEvents, 0);
                    }
                };
                xhr.send();
            }
            if (pageSizeSelect) pageSizeSelect.onchange = function() {
                loadAgencyFilePage(1, this.value);
            };
            if (btnFirstPage) btnFirstPage.onclick = function() {
                loadAgencyFilePage(1, pageSizeSelect.value);
            };
            if (btnPrevPage) btnPrevPage.onclick = function() {
                var cur = parseInt(document.getElementById('agency-file-current-page').textContent) || 1;
                if (cur > 1) loadAgencyFilePage(cur - 1, pageSizeSelect.value);
            };
            if (btnNextPage) btnNextPage.onclick = function() {
                var cur = parseInt(document.getElementById('agency-file-current-page').textContent) || 1;
                if (cur < totalPages) loadAgencyFilePage(cur + 1, pageSizeSelect.value);
            };
            if (btnLastPage) btnLastPage.onclick = function() {
                loadAgencyFilePage(totalPages, pageSizeSelect.value);
            };
            if (btnPageJump) btnPageJump.onclick = function() {
                var page = parseInt(pageInput.value) || 1;
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages;
                loadAgencyFilePage(page, pageSizeSelect.value);
            };
        }
        // 首次绑定
        bindAgencyFileEvents();
    })();
</script>