<?php
// modules/customer_management/customer/customer_tabs/inventor.php
// 发明人管理
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
            'name_cn' => trim($_POST['name_cn'] ?? ''),
            'name_en' => trim($_POST['name_en'] ?? ''),
            'job_no' => trim($_POST['job_no'] ?? ''),
            'xing_cn' => trim($_POST['xing_cn'] ?? ''),
            'xing_en' => trim($_POST['xing_en'] ?? ''),
            'ming_cn' => trim($_POST['ming_cn'] ?? ''),
            'ming_en' => trim($_POST['ming_en'] ?? ''),
            'nationality' => trim($_POST['nationality'] ?? ''),
            'country' => trim($_POST['country'] ?? ''),
            'is_tech_contact' => intval($_POST['is_tech_contact'] ?? 0),
            'province' => trim($_POST['province'] ?? ''),
            'city_cn' => trim($_POST['city_cn'] ?? ''),
            'city_en' => trim($_POST['city_en'] ?? ''),
            'address_cn' => trim($_POST['address_cn'] ?? ''),
            'address_en' => trim($_POST['address_en'] ?? ''),
            'department_cn' => trim($_POST['department_cn'] ?? ''),
            'department_en' => trim($_POST['department_en'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'id_number' => trim($_POST['id_number'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'qq' => trim($_POST['qq'] ?? ''),
            'mobile' => trim($_POST['mobile'] ?? ''),
            'postcode' => trim($_POST['postcode'] ?? ''),
            'remark' => trim($_POST['remark'] ?? ''),
        ];
        if ($data['name_cn'] === '') {
            echo json_encode(['success' => false, 'msg' => '请填写中文名']);
            exit;
        }
        try {
            if ($id > 0) {
                $set = '';
                foreach ($data as $k => $v) {
                    $set .= "$k=:$k,";
                }
                $set = rtrim($set, ',');
                $sql = "UPDATE inventor SET $set WHERE id=:id AND customer_id=:customer_id";
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
                $sql = "INSERT INTO inventor ($fields) VALUES ($placeholders)";
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
            $stmt = $pdo->prepare("DELETE FROM inventor WHERE id=? AND customer_id=?");
            $ok = $stmt->execute([$id, $customer_id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM inventor WHERE id=? AND customer_id=?");
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
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM inventor WHERE customer_id=?");
$count_stmt->execute([$customer_id]);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $page_size);
// 当前页数据
$stmt = $pdo->prepare("SELECT * FROM inventor WHERE customer_id=:customer_id ORDER BY id ASC LIMIT :offset, :limit");
$stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $page_size, PDO::PARAM_INT);
$stmt->execute();
$inventors = $stmt->fetchAll();
?>
<div class="module-panel">
    <div style="margin-bottom:12px;text-align:left;">
        <button type="button" class="btn-mini" id="btn-add-inventor"><i class="icon-add"></i> 添加发明人</button>
    </div>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:30px;">序号</th>
                <th style="width:100px;">中文名</th>
                <th style="width:100px;">英文名</th>
                <th style="width:100px;">工号</th>
                <th style="width:100px;">国籍</th>
                <th style="width:100px;">是否技术联系人</th>
                <th style="width:100px;">手机</th>
                <th style="width:160px;">邮件</th>
                <th style="width:120px;">操作</th>
            </tr>
        </thead>
        <tbody id="inventor-list">
            <?php if (empty($inventors)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;color:#888;">暂无发明人</td>
                </tr>
                <?php else: foreach ($inventors as $i => $a): ?>
                    <tr data-id="<?= $a['id'] ?>">
                        <td style="text-align:center; width:40px;"> <?= $offset + $i + 1 ?> </td>
                        <td style="width:100px;"> <?= h($a['name_cn']) ?> </td>
                        <td style="width:100px;"> <?= h($a['name_en']) ?> </td>
                        <td style="width:100px;"> <?= h($a['job_no']) ?> </td>
                        <td style="width:100px;"> <?= h($a['nationality']) ?> </td>
                        <td style="width:100px; text-align:center;"> <?= $a['is_tech_contact'] ? '是' : '否' ?> </td>
                        <td style="width:100px;"> <?= h($a['mobile']) ?> </td>
                        <td style="width:160px;"> <?= h($a['email']) ?> </td>
                        <td style="width:120px; text-align:center;">
                            <button type="button" class="btn-mini btn-edit-inventor">编辑</button>
                            <button type="button" class="btn-mini btn-del-inventor">删除</button>
                        </td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>
    <div class="module-pagination" id="inventor-pagination">
        <span>共 <span id="inventor-total-records"><?= $total_records ?></span> 条记录，每页</span>
        <select id="inventor-page-size-select">
            <option value="10" <?= $page_size == 10 ? ' selected' : '' ?>>10</option>
            <option value="20" <?= $page_size == 20 ? ' selected' : '' ?>>20</option>
            <option value="50" <?= $page_size == 50 ? ' selected' : '' ?>>50</option>
            <option value="100" <?= $page_size == 100 ? ' selected' : '' ?>>100</option>
        </select>
        <span>条，当前 <span id="inventor-current-page"><?= $page ?></span>/<span id="inventor-total-pages"><?= $total_pages ?></span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="inventor-btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="<?= max(1, $page - 1) ?>" id="inventor-btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="<?= min($total_pages, $page + 1) ?>" id="inventor-btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="<?= $total_pages ?>" id="inventor-btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="inventor-page-input" min="1" value="<?= $page ?>">
        <span>页</span>
        <button type="button" id="inventor-btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>
<div id="inventor-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:6px;box-shadow:0 2px 12px rgba(0,0,0,0.18);padding:24px 32px;min-width:800px;max-width:98vw;position:relative;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="inventor-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">发明人信息</h3>
        <form id="inventor-form" class="module-form">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="customer_id" value="<?= $customer_id ?>">
            <table class="module-table">
                <tr>
                    <td class="module-label module-req">*中文名</td>
                    <td><input type="text" name="name_cn" class="module-input" required></td>
                    <td class="module-label">英文名</td>
                    <td><input type="text" name="name_en" class="module-input"></td>
                    <td class="module-label">工号</td>
                    <td><input type="text" name="job_no" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">名称/姓(中文)</td>
                    <td><input type="text" name="xing_cn" class="module-input"></td>
                    <td class="module-label">名称/姓(英文)</td>
                    <td><input type="text" name="xing_en" class="module-input"></td>
                    <td class="module-label">名(中文)</td>
                    <td><input type="text" name="ming_cn" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">名(英文)</td>
                    <td><input type="text" name="ming_en" class="module-input"></td>
                    <td class="module-label">国籍</td>
                    <td><input type="text" name="nationality" class="module-input" value="中国"></td>
                    <td class="module-label">国家(地区)</td>
                    <td><input type="text" name="country" class="module-input" value="中国"></td>
                </tr>
                <tr>
                    <td class="module-label">是否为技术联系人</td>
                    <td>
                        <select name="is_tech_contact" class="module-input">
                            <option value="0">否</option>
                            <option value="1">是</option>
                        </select>
                    </td>
                    <td class="module-label">省份</td>
                    <td><input type="text" name="province" class="module-input"></td>
                    <td class="module-label">城市(中文)</td>
                    <td><input type="text" name="city_cn" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">城市(英文)</td>
                    <td><input type="text" name="city_en" class="module-input"></td>
                    <td class="module-label">街道地址(中文)</td>
                    <td><input type="text" name="address_cn" class="module-input"></td>
                    <td class="module-label">街道地址(英文)</td>
                    <td><input type="text" name="address_en" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">部门/楼层(中文)</td>
                    <td><input type="text" name="department_cn" class="module-input"></td>
                    <td class="module-label">部门/楼层(英文)</td>
                    <td><input type="text" name="department_en" class="module-input"></td>
                    <td class="module-label">邮件</td>
                    <td><input type="email" name="email" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">证件号码</td>
                    <td><input type="text" name="id_number" class="module-input"></td>
                    <td class="module-label">座机</td>
                    <td><input type="text" name="phone" class="module-input"></td>
                    <td class="module-label">QQ</td>
                    <td><input type="text" name="qq" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">手机</td>
                    <td><input type="text" name="mobile" class="module-input"></td>
                    <td class="module-label">邮编</td>
                    <td><input type="text" name="postcode" class="module-input"></td>
                    <td class="module-label">备注</td>
                    <td rowspan="2"><textarea name="remark" class="module-input" style="min-height:48px;"></textarea></td>
                </tr>
            </table>
            <div style="text-align:center;margin-top:12px;">
                <button type="button" class="btn-save-inventor btn-mini" style="margin-right:16px;">保存</button>
                <button type="button" class="btn-cancel-inventor btn-mini">取消</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function() {
        function bindInventorEvents() {
            var modal = document.getElementById('inventor-modal');
            var closeBtn = document.getElementById('inventor-modal-close');
            var addBtn = document.getElementById('btn-add-inventor');
            var list = document.getElementById('inventor-list');
            var form = document.getElementById('inventor-form');
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
                    name_cn: '',
                    name_en: '',
                    job_no: '',
                    xing_cn: '',
                    xing_en: '',
                    ming_cn: '',
                    ming_en: '',
                    nationality: '中国',
                    country: '中国',
                    is_tech_contact: 0,
                    province: '',
                    city_cn: '',
                    city_en: '',
                    address_cn: '',
                    address_en: '',
                    department_cn: '',
                    department_en: '',
                    email: '',
                    id_number: '',
                    phone: '',
                    qq: '',
                    mobile: '',
                    postcode: '',
                    remark: ''
                });
            };
            closeBtn.onclick = hideModal;
            form.querySelector('.btn-cancel-inventor').onclick = hideModal;
            list.onclick = function(e) {
                var tr = e.target.closest('tr[data-id]');
                if (!tr) return;
                var id = tr.getAttribute('data-id');
                if (e.target.classList.contains('btn-edit-inventor')) {
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'get');
                    fd.append('id', id);
                    fd.append('customer_id', form.customer_id.value);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/inventor.php', true);
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
                } else if (e.target.classList.contains('btn-del-inventor')) {
                    if (!confirm('确定删除该发明人？')) return;
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    fd.append('customer_id', form.customer_id.value);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/inventor.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                                if (tabContent) {
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', 'modules/customer_management/customer/customer_tabs/inventor.php?customer_id=' + form.customer_id.value, true);
                                    xhr2.onload = function() {
                                        tabContent.innerHTML = xhr2.responseText;
                                        setTimeout(bindInventorEvents, 0);
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
            form.querySelector('.btn-save-inventor').onclick = function() {
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/customer/customer_tabs/inventor.php', true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                            if (tabContent) {
                                var xhr2 = new XMLHttpRequest();
                                xhr2.open('GET', 'modules/customer_management/customer/customer_tabs/inventor.php?customer_id=' + form.customer_id.value, true);
                                xhr2.onload = function() {
                                    tabContent.innerHTML = xhr2.responseText;
                                    setTimeout(bindInventorEvents, 0);
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
            var pageSizeSelect = document.getElementById('inventor-page-size-select');
            var btnFirstPage = document.getElementById('inventor-btn-first-page');
            var btnPrevPage = document.getElementById('inventor-btn-prev-page');
            var btnNextPage = document.getElementById('inventor-btn-next-page');
            var btnLastPage = document.getElementById('inventor-btn-last-page');
            var pageInput = document.getElementById('inventor-page-input');
            var btnPageJump = document.getElementById('inventor-btn-page-jump');
            var totalPages = parseInt(document.getElementById('inventor-total-pages').textContent) || 1;
            var customerId = document.querySelector('[name=customer_id]').value;

            function loadInventorPage(page, pageSize) {
                var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                var url = 'modules/customer_management/customer/customer_tabs/inventor.php?customer_id=' + customerId + '&page=' + page + '&page_size=' + pageSize;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.onload = function() {
                    if (tabContent) {
                        tabContent.innerHTML = xhr.responseText;
                        setTimeout(bindInventorEvents, 0);
                    }
                };
                xhr.send();
            }
            if (pageSizeSelect) pageSizeSelect.onchange = function() {
                loadInventorPage(1, this.value);
            };
            if (btnFirstPage) btnFirstPage.onclick = function() {
                loadInventorPage(1, pageSizeSelect.value);
            };
            if (btnPrevPage) btnPrevPage.onclick = function() {
                var cur = parseInt(document.getElementById('inventor-current-page').textContent) || 1;
                if (cur > 1) loadInventorPage(cur - 1, pageSizeSelect.value);
            };
            if (btnNextPage) btnNextPage.onclick = function() {
                var cur = parseInt(document.getElementById('inventor-current-page').textContent) || 1;
                if (cur < totalPages) loadInventorPage(cur + 1, pageSizeSelect.value);
            };
            if (btnLastPage) btnLastPage.onclick = function() {
                loadInventorPage(totalPages, pageSizeSelect.value);
            };
            if (btnPageJump) btnPageJump.onclick = function() {
                var page = parseInt(pageInput.value) || 1;
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages;
                loadInventorPage(page, pageSizeSelect.value);
            };
        }
        bindInventorEvents();
    })();
</script>