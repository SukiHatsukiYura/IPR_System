<?php
// modules/customer_management/agency/agency_tabs/contact.php
// 代理机构联系人管理
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
// 联系人类别
$contact_types = ['--请选择--', 'IPR', '流程人员', '技术联系人', '财务人员', '公司负责人', '发明人', '来文通知人员', '商标联系人', '其他'];
// 称呼
$salutations = ['--请选择--', '无', '博士', '小姐', '教授', '先生', '女士', '经理', '总经理'];
// 性别
$genders = ['2' => '未知', '1' => '男', '0' => '女'];
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
            'name' => trim($_POST['name'] ?? ''),
            'mobile' => trim($_POST['mobile'] ?? ''),
            'position' => trim($_POST['position'] ?? ''),
            'private_email' => trim($_POST['private_email'] ?? ''),
            'gender' => trim($_POST['gender'] ?? '2'),
            'fax' => trim($_POST['fax'] ?? ''),
            'wechat' => trim($_POST['wechat'] ?? ''),
            'letter_title' => trim($_POST['letter_title'] ?? ''),
            'work_address' => trim($_POST['work_address'] ?? ''),
            'home_address' => trim($_POST['home_address'] ?? ''),
            'hobby' => trim($_POST['hobby'] ?? ''),
            'remark' => trim($_POST['remark'] ?? ''),
            'work_email' => trim($_POST['work_email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'salutation' => trim($_POST['salutation'] ?? ''),
            'is_active' => intval($_POST['is_active'] ?? 1),
            'contact_type' => trim($_POST['contact_type'] ?? ''),
            'qq' => trim($_POST['qq'] ?? ''),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'postcode' => trim($_POST['postcode'] ?? ''),
            'case_type_patent' => intval($_POST['case_type_patent'] ?? 0),
            'case_type_trademark' => intval($_POST['case_type_trademark'] ?? 0),
            'case_type_copyright' => intval($_POST['case_type_copyright'] ?? 0),
            'history_address' => trim($_POST['history_address'] ?? ''),
        ];
        if ($data['name'] === '' || $data['mobile'] === '' || $data['contact_type'] === '') {
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
                $sql = "UPDATE agency_contact SET $set WHERE id=:id AND agency_id=:agency_id";
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
                $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM agency_contact WHERE agency_id=?");
                $stmt->execute([$agency_id]);
                $max_sort = $stmt->fetchColumn();
                $data_insert['sort_order'] = is_numeric($max_sort) ? ($max_sort + 1) : 0;
                $fields = implode(',', array_keys($data_insert));
                $placeholders = ':' . implode(', :', array_keys($data_insert));
                $sql = "INSERT INTO agency_contact ($fields) VALUES ($placeholders)";
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
            $stmt = $pdo->prepare("DELETE FROM agency_contact WHERE id=? AND agency_id=?");
            $ok = $stmt->execute([$id, $agency_id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM agency_contact WHERE id=? AND agency_id=?");
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
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM agency_contact WHERE agency_id=?");
$count_stmt->execute([$agency_id]);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $page_size);
// 当前页数据
$stmt = $pdo->prepare("SELECT * FROM agency_contact WHERE agency_id=:agency_id ORDER BY sort_order ASC, id ASC LIMIT :offset, :limit");
$stmt->bindValue(':agency_id', $agency_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $page_size, PDO::PARAM_INT);
$stmt->execute();
$contacts = $stmt->fetchAll();
?>
<div class="module-panel">
    <div style="margin-bottom:12px;text-align:left;">
        <button type="button" class="btn-mini" id="btn-add-contact"><i class="icon-add"></i> 添加联系人</button>
    </div>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:30px;">序号</th>
                <th style="width:100px;">姓名</th>
                <th style="width:100px;">手机</th>
                <th style="width:110px;">联系人类别</th>
                <th style="width:100px;">职位</th>
                <th style="width:160px;">邮箱</th>
                <th style="width:100px;">案件类型</th>
                <th style="width:60px;">是否在职</th>
                <th style="width:160px;">操作</th>
            </tr>
        </thead>
        <tbody id="contact-list">
            <?php if (empty($contacts)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;color:#888;">暂无联系人</td>
                </tr>
                <?php else: foreach ($contacts as $i => $c): ?>
                    <tr data-id="<?= $c['id'] ?>">
                        <td style="text-align:center; width:40px;"> <?= $offset + $i + 1 ?> </td>
                        <td style="width:100px;"> <?= h($c['name']) ?> </td>
                        <td style="width:100px;"> <?= h($c['mobile']) ?> </td>
                        <td style="width:110px;"> <?= h($c['contact_type']) ?> </td>
                        <td style="width:100px;"> <?= h($c['position']) ?> </td>
                        <td style="width:160px;"> <?= h($c['work_email']) ?> </td>
                        <td style="width:100px;">
                            <?php
                            $types = [];
                            if (!empty($c['case_type_patent'])) $types[] = '专利';
                            if (!empty($c['case_type_trademark'])) $types[] = '商标';
                            if (!empty($c['case_type_copyright'])) $types[] = '版权';
                            echo implode(',', $types);
                            ?>
                        </td>
                        <td style="width:60px; text-align:center;"> <?= $c['is_active'] ? '是' : '否' ?> </td>
                        <td style="width:160px; text-align:center;">
                            <button type="button" class="btn-mini btn-edit-contact">编辑</button>
                            <button type="button" class="btn-mini btn-del-contact">删除</button>
                            <button type="button" class="btn-mini btn-move-up" <?= $i == 0 ? 'disabled' : '' ?>>↑</button>
                            <button type="button" class="btn-mini btn-move-down" <?= $i == count($contacts) - 1 ? 'disabled' : '' ?>>↓</button>
                        </td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>
    <div class="module-pagination" id="contact-pagination">
        <span>共 <span id="contact-total-records"><?= $total_records ?></span> 条记录，每页</span>
        <select id="contact-page-size-select">
            <option value="10" <?= $page_size == 10 ? ' selected' : '' ?>>10</option>
            <option value="20" <?= $page_size == 20 ? ' selected' : '' ?>>20</option>
            <option value="50" <?= $page_size == 50 ? ' selected' : '' ?>>50</option>
            <option value="100" <?= $page_size == 100 ? ' selected' : '' ?>>100</option>
        </select>
        <span>条，当前 <span id="contact-current-page"><?= $page ?></span>/<span id="contact-total-pages"><?= $total_pages ?></span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="contact-btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="<?= max(1, $page - 1) ?>" id="contact-btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="<?= min($total_pages, $page + 1) ?>" id="contact-btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="<?= $total_pages ?>" id="contact-btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="contact-page-input" min="1" value="<?= $page ?>">
        <span>页</span>
        <button type="button" id="contact-btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>
<div id="contact-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:6px;box-shadow:0 2px 12px rgba(0,0,0,0.18);padding:24px 32px;min-width:800px;max-width:98vw;position:relative;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="contact-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">联系人信息</h3>
        <form id="contact-form" class="module-form">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="agency_id" value="<?= $agency_id ?>">
            <table class="module-table">
                <tr>
                    <td class="module-label">案件类型</td>
                    <td colspan="5">
                        <label style="margin-right:18px;"><input type="checkbox" name="case_type_patent" value="1"> 专利</label>
                        <label style="margin-right:18px;"><input type="checkbox" name="case_type_trademark" value="1"> 商标</label>
                        <label style="margin-right:18px;"><input type="checkbox" name="case_type_copyright" value="1"> 版权</label>
                    </td>
                </tr>
                <tr>
                    <td class="module-label module-req">*姓名</td>
                    <td><input type="text" name="name" class="module-input" required></td>
                    <td class="module-label module-req">*手机</td>
                    <td><input type="text" name="mobile" class="module-input" required></td>
                    <td class="module-label">职位</td>
                    <td><input type="text" name="position" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">私人邮箱</td>
                    <td><input type="email" name="private_email" class="module-input"></td>
                    <td class="module-label">性别</td>
                    <td>
                        <select name="gender" class="module-input">
                            <?php foreach ($genders as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="module-label">传真</td>
                    <td><input type="text" name="fax" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">微信号</td>
                    <td><input type="text" name="wechat" class="module-input"></td>
                    <td class="module-label">信函抬头</td>
                    <td><input type="text" name="letter_title" class="module-input"></td>
                    <td class="module-label module-req">*联系人类别</td>
                    <td>
                        <select name="contact_type" class="module-input" required>
                            <?php foreach ($contact_types as $v): ?>
                                <option value="<?= h($v) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">工作地址</td>
                    <td><input type="text" name="work_address" class="module-input"></td>
                    <td class="module-label">家庭地址</td>
                    <td><input type="text" name="home_address" class="module-input"></td>
                    <td class="module-label">兴趣爱好</td>
                    <td><input type="text" name="hobby" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">备注</td>
                    <td colspan="5"><textarea name="remark" class="module-input" style="min-height:48px;"></textarea></td>
                </tr>
                <tr>
                    <td class="module-label">工作邮箱</td>
                    <td><input type="email" name="work_email" class="module-input"></td>
                    <td class="module-label">电话</td>
                    <td><input type="text" name="phone" class="module-input"></td>
                    <td class="module-label">称呼</td>
                    <td>
                        <select name="salutation" class="module-input">
                            <?php foreach ($salutations as $v): ?>
                                <option value="<?= h($v) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">是否在职</td>
                    <td>
                        <select name="is_active" class="module-input">
                            <option value="1">是</option>
                            <option value="0">否</option>
                        </select>
                    </td>
                    <td class="module-label">QQ</td>
                    <td><input type="text" name="qq" class="module-input"></td>
                    <td class="module-label">邮编</td>
                    <td><input type="text" name="postcode" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">历史地址</td>
                    <td><input type="text" name="history_address" class="module-input"></td>
                    <td class="module-label"></td>
                    <td></td>
                    <td class="module-label"></td>
                    <td></td>
                </tr>
            </table>
            <div style="text-align:center;margin-top:12px;">
                <button type="button" class="btn-save-contact btn-mini" style="margin-right:16px;">保存</button>
                <button type="button" class="btn-cancel-contact btn-mini">取消</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function() {
        function bindContactEvents() {
            var modal = document.getElementById('contact-modal');
            var closeBtn = document.getElementById('contact-modal-close');
            var addBtn = document.getElementById('btn-add-contact');
            var list = document.getElementById('contact-list');
            var form = document.getElementById('contact-form');
            if (!modal || !addBtn || !list || !form) return;

            function showModal(data) {
                form.reset();
                for (var k in data) {
                    if (form[k]) form[k].value = data[k] !== null ? data[k] : '';
                }
                form.case_type_patent.checked = data.case_type_patent == 1;
                form.case_type_trademark.checked = data.case_type_trademark == 1;
                form.case_type_copyright.checked = data.case_type_copyright == 1;
                modal.style.display = 'flex';
            }

            function hideModal() {
                modal.style.display = 'none';
            }
            addBtn.onclick = function() {
                showModal({
                    id: 0,
                    agency_id: form.agency_id.value,
                    name: '',
                    mobile: '',
                    position: '',
                    private_email: '',
                    gender: '2',
                    fax: '',
                    wechat: '',
                    letter_title: '',
                    work_address: '',
                    home_address: '',
                    hobby: '',
                    remark: '',
                    work_email: '',
                    phone: '',
                    salutation: '',
                    is_active: 1,
                    contact_type: '',
                    qq: '',
                    sort_order: 0,
                    postcode: '',
                    case_type_patent: 0,
                    case_type_trademark: 0,
                    case_type_copyright: 0,
                    history_address: ''
                });
            };
            closeBtn.onclick = hideModal;
            form.querySelector('.btn-cancel-contact').onclick = hideModal;
            list.onclick = function(e) {
                var tr = e.target.closest('tr[data-id]');
                if (!tr) return;
                var id = tr.getAttribute('data-id');
                if (e.target.classList.contains('btn-edit-contact')) {
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'get');
                    fd.append('id', id);
                    fd.append('agency_id', form.agency_id.value);
                    xhr.open('POST', 'modules/customer_management/agency/agency_tabs/contact.php', true);
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
                } else if (e.target.classList.contains('btn-del-contact')) {
                    if (!confirm('确定删除该联系人？')) return;
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    fd.append('agency_id', form.agency_id.value);
                    xhr.open('POST', 'modules/customer_management/agency/agency_tabs/contact.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                                if (tabContent) {
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', 'modules/customer_management/agency/agency_tabs/contact.php?agency_id=' + form.agency_id.value, true);
                                    xhr2.onload = function() {
                                        tabContent.innerHTML = xhr2.responseText;
                                        setTimeout(bindContactEvents, 0);
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
                } else if (e.target.classList.contains('btn-move-up') || e.target.classList.contains('btn-move-down')) {
                    var direction = e.target.classList.contains('btn-move-up') ? 'up' : 'down';
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'move');
                    fd.append('id', id);
                    fd.append('agency_id', form.agency_id.value);
                    fd.append('direction', direction);
                    xhr.open('POST', 'modules/customer_management/agency/agency_tabs/contact.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                                if (tabContent) {
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', 'modules/customer_management/agency/agency_tabs/contact.php?agency_id=' + form.agency_id.value, true);
                                    xhr2.onload = function() {
                                        tabContent.innerHTML = xhr2.responseText;
                                        setTimeout(bindContactEvents, 0);
                                    };
                                    xhr2.send();
                                } else {
                                    location.reload();
                                }
                            } else alert('排序失败');
                        } catch (e) {
                            alert('排序失败');
                        }
                    };
                    xhr.send(fd);
                }
            };
            form.querySelector('.btn-save-contact').onclick = function() {
                var fd = new FormData(form);
                fd.set('case_type_patent', form.case_type_patent.checked ? 1 : 0);
                fd.set('case_type_trademark', form.case_type_trademark.checked ? 1 : 0);
                fd.set('case_type_copyright', form.case_type_copyright.checked ? 1 : 0);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/agency/agency_tabs/contact.php', true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                            if (tabContent) {
                                var xhr2 = new XMLHttpRequest();
                                xhr2.open('GET', 'modules/customer_management/agency/agency_tabs/contact.php?agency_id=' + form.agency_id.value, true);
                                xhr2.onload = function() {
                                    tabContent.innerHTML = xhr2.responseText;
                                    setTimeout(bindContactEvents, 0);
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
            var pageSizeSelect = document.getElementById('contact-page-size-select');
            var btnFirstPage = document.getElementById('contact-btn-first-page');
            var btnPrevPage = document.getElementById('contact-btn-prev-page');
            var btnNextPage = document.getElementById('contact-btn-next-page');
            var btnLastPage = document.getElementById('contact-btn-last-page');
            var pageInput = document.getElementById('contact-page-input');
            var btnPageJump = document.getElementById('contact-btn-page-jump');
            var totalPages = parseInt(document.getElementById('contact-total-pages').textContent) || 1;
            var agencyId = document.querySelector('[name=agency_id]').value;

            function loadContactPage(page, pageSize) {
                var tabContent = window.parent && window.parent.document.getElementById('agency-tab-content');
                var url = 'modules/customer_management/agency/agency_tabs/contact.php?agency_id=' + agencyId + '&page=' + page + '&page_size=' + pageSize;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.onload = function() {
                    if (tabContent) {
                        tabContent.innerHTML = xhr.responseText;
                        setTimeout(bindContactEvents, 0);
                    }
                };
                xhr.send();
            }
            if (pageSizeSelect) pageSizeSelect.onchange = function() {
                loadContactPage(1, this.value);
            };
            if (btnFirstPage) btnFirstPage.onclick = function() {
                loadContactPage(1, pageSizeSelect.value);
            };
            if (btnPrevPage) btnPrevPage.onclick = function() {
                var cur = parseInt(document.getElementById('contact-current-page').textContent) || 1;
                if (cur > 1) loadContactPage(cur - 1, pageSizeSelect.value);
            };
            if (btnNextPage) btnNextPage.onclick = function() {
                var cur = parseInt(document.getElementById('contact-current-page').textContent) || 1;
                if (cur < totalPages) loadContactPage(cur + 1, pageSizeSelect.value);
            };
            if (btnLastPage) btnLastPage.onclick = function() {
                loadContactPage(totalPages, pageSizeSelect.value);
            };
            if (btnPageJump) btnPageJump.onclick = function() {
                var page = parseInt(pageInput.value) || 1;
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages;
                loadContactPage(page, pageSizeSelect.value);
            };
        }
        bindContactEvents();
    })();
</script>