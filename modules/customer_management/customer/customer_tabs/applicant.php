<?php
// modules/customer_management/customer/customer_tabs/applicant.php
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
$applicant_types = ['大专院校', '科研单位', '事业单位', '工矿企业', '个人'];
$entity_types = ['大实体', '小实体', '微实体'];
$case_types = ['patent' => '专利', 'trademark' => '商标', 'copyright' => '版权'];
$id_types = ['居民身份证', '护照', '营业执照', '其他'];
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
            'case_type' => trim($_POST['case_type'] ?? ''),
            'applicant_type' => trim($_POST['applicant_type'] ?? ''),
            'entity_type' => trim($_POST['entity_type'] ?? ''),
            'name_cn' => trim($_POST['name_cn'] ?? ''),
            'name_en' => trim($_POST['name_en'] ?? ''),
            'name_xing_cn' => trim($_POST['name_xing_cn'] ?? ''),
            'name_xing_en' => trim($_POST['name_xing_en'] ?? ''),
            'is_first_contact' => intval($_POST['is_first_contact'] ?? 0),
            'is_receipt_title' => intval($_POST['is_receipt_title'] ?? 0),
            'receipt_title' => trim($_POST['receipt_title'] ?? ''),
            'credit_code' => trim($_POST['credit_code'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'province' => trim($_POST['province'] ?? ''),
            'city_cn' => trim($_POST['city_cn'] ?? ''),
            'city_en' => trim($_POST['city_en'] ?? ''),
            'district' => trim($_POST['district'] ?? ''),
            'postcode' => trim($_POST['postcode'] ?? ''),
            'address_cn' => trim($_POST['address_cn'] ?? ''),
            'address_en' => trim($_POST['address_en'] ?? ''),
            'department_cn' => trim($_POST['department_cn'] ?? ''),
            'department_en' => trim($_POST['department_en'] ?? ''),
            'id_type' => trim($_POST['id_type'] ?? ''),
            'id_number' => trim($_POST['id_number'] ?? ''),
            'is_fee_reduction' => intval($_POST['is_fee_reduction'] ?? 0),
            'fee_reduction_start' => trim($_POST['fee_reduction_start'] ?? ''),
            'fee_reduction_end' => trim($_POST['fee_reduction_end'] ?? ''),
            'fee_reduction_code' => trim($_POST['fee_reduction_code'] ?? ''),
            'cn_agent_code' => trim($_POST['cn_agent_code'] ?? ''),
            'pct_agent_code' => trim($_POST['pct_agent_code'] ?? ''),
            'is_fee_monitor' => intval($_POST['is_fee_monitor'] ?? 0),
            'country' => trim($_POST['country'] ?? ''),
            'nationality' => trim($_POST['nationality'] ?? ''),
            'business_license' => trim($_POST['business_license'] ?? ''),
            'remark' => trim($_POST['remark'] ?? ''),
        ];
        if ($data['name_cn'] === '' || $data['applicant_type'] === '') {
            echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
            exit;
        }
        // 日期字段空字符串转为NULL
        if ($data['fee_reduction_start'] === '') $data['fee_reduction_start'] = null;
        if ($data['fee_reduction_end'] === '') $data['fee_reduction_end'] = null;
        try {
            if ($id > 0) {
                $set = '';
                foreach ($data as $k => $v) {
                    $set .= "$k=:$k,";
                }
                $set = rtrim($set, ',');
                $sql = "UPDATE applicant SET $set WHERE id=:id AND customer_id=:customer_id";
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
                $sql = "INSERT INTO applicant ($fields) VALUES ($placeholders)";
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
            $stmt = $pdo->prepare("DELETE FROM applicant WHERE id=? AND customer_id=?");
            $ok = $stmt->execute([$id, $customer_id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM applicant WHERE id=? AND customer_id=?");
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
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM applicant WHERE customer_id=?");
$count_stmt->execute([$customer_id]);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $page_size);
// 当前页数据
$stmt = $pdo->prepare("SELECT * FROM applicant WHERE customer_id=:customer_id ORDER BY id ASC LIMIT :offset, :limit");
$stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $page_size, PDO::PARAM_INT);
$stmt->execute();
$applicants = $stmt->fetchAll();
?>
<div class="module-panel">
    <div style="margin-bottom:12px;text-align:left;">
        <button type="button" class="btn-mini" id="btn-add-applicant"><i class="icon-add"></i> 添加申请人</button>
    </div>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:30px;">序号</th>
                <th style="width:120px;">案件类型</th>
                <th style="width:120px;">申请人(中文)</th>
                <th style="width:120px;">申请人(英文)</th>
                <th style="width:120px;">所属地区</th>
                <th style="width:100px;">联系电话</th>
                <th style="width:160px;">邮件</th>
                <th style="width:60px;">第一联系人</th>
                <th style="width:110px;">操作</th>
            </tr>
        </thead>
        <tbody id="applicant-list">
            <?php if (empty($applicants)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;color:#888;">暂无申请人</td>
                </tr>
                <?php else: foreach ($applicants as $i => $a): ?>
                    <tr data-id="<?= $a['id'] ?>">
                        <td style="text-align:center; width:40px;"> <?= $offset + $i + 1 ?> </td>
                        <td style="width:100px;"> <?= h(str_replace(',', '，', $a['case_type'])) ?> </td>
                        <td style="width:120px;"> <?= h($a['name_cn']) ?> </td>
                        <td style="width:120px;"> <?= h($a['name_en']) ?> </td>
                        <td style="width:120px;"> <?= h($a['province'] . ($a['city_cn'] ? ' ' . $a['city_cn'] : '') . ($a['district'] ? ' ' . $a['district'] : '')) ?> </td>
                        <td style="width:100px;"> <?= h($a['phone']) ?> </td>
                        <td style="width:160px;"> <?= h($a['email']) ?> </td>
                        <td style="width:60px; text-align:center;"> <?= $a['is_first_contact'] ? '是' : '否' ?> </td>
                        <td style="width:110px; text-align:center;">
                            <button type="button" class="btn-mini btn-edit-applicant">编辑</button>
                            <button type="button" class="btn-mini btn-del-applicant">删除</button>
                        </td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>
    <div class="module-pagination" id="applicant-pagination">
        <span>共 <span id="applicant-total-records"><?= $total_records ?></span> 条记录，每页</span>
        <select id="applicant-page-size-select">
            <option value="10" <?= $page_size == 10 ? ' selected' : '' ?>>10</option>
            <option value="20" <?= $page_size == 20 ? ' selected' : '' ?>>20</option>
            <option value="50" <?= $page_size == 50 ? ' selected' : '' ?>>50</option>
            <option value="100" <?= $page_size == 100 ? ' selected' : '' ?>>100</option>
        </select>
        <span>条，当前 <span id="applicant-current-page"><?= $page ?></span>/<span id="applicant-total-pages"><?= $total_pages ?></span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="applicant-btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="<?= max(1, $page - 1) ?>" id="applicant-btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="<?= min($total_pages, $page + 1) ?>" id="applicant-btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="<?= $total_pages ?>" id="applicant-btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="applicant-page-input" min="1" value="<?= $page ?>">
        <span>页</span>
        <button type="button" id="applicant-btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>
<div id="applicant-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:950px;max-width:98vw;max-height:80vh;position:relative;display:flex;flex-direction:column;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="applicant-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">申请人信息</h3>
        <div style="flex:1 1 auto;overflow-y:auto;">
            <form id="applicant-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <input type="hidden" name="customer_id" value="<?= $customer_id ?>">
                <table class="module-table" style="table-layout:fixed;width:100%;min-width:0;">
                    <colgroup>
                        <col style="width:120px;">
                        <col style="width:320px;">
                        <col style="width:120px;">
                        <col style="width:320px;">
                    </colgroup>
                    <tr>
                        <td class="module-label module-req">*名称(中文)</td>
                        <td><input type="text" name="name_cn" class="module-input" required></td>
                        <td class="module-label">名称(英文)</td>
                        <td><input type="text" name="name_en" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label module-req">*申请人类型</td>
                        <td>
                            <select name="applicant_type" class="module-input" required>
                                <option value="">--请选择--</option>
                                <?php foreach ($applicant_types as $v): ?>
                                    <option value="<?= h($v) ?>"><?= h($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="module-label module-req">*实体类型</td>
                        <td>
                            <select name="entity_type" class="module-input" required>
                                <option value="">--请选择--</option>
                                <?php foreach ($entity_types as $v): ?>
                                    <option value="<?= h($v) ?>"><?= h($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">案件类型</td>
                        <td colspan="3">
                            <?php foreach ($case_types as $k => $v): ?>
                                <label style="margin-right:18px;">
                                    <input type="checkbox" name="case_type_<?= $k ?>" value="<?= h($v) ?>"> <?= h($v) ?>
                                </label>
                            <?php endforeach; ?>
                            <input type="hidden" name="case_type" value="">
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">名称/姓(中文)</td>
                        <td><input type="text" name="name_xing_cn" class="module-input"></td>
                        <td class="module-label">名称/姓(英文)</td>
                        <td><input type="text" name="name_xing_en" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">电话</td>
                        <td><input type="text" name="phone" class="module-input"></td>
                        <td class="module-label">邮件</td>
                        <td><input type="email" name="email" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">省份</td>
                        <td><input type="text" name="province" class="module-input"></td>
                        <td class="module-label">城市(中文)</td>
                        <td><input type="text" name="city_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">城市(英文)</td>
                        <td><input type="text" name="city_en" class="module-input"></td>
                        <td class="module-label">行政区划</td>
                        <td><input type="text" name="district" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">邮编</td>
                        <td><input type="text" name="postcode" class="module-input"></td>
                        <td class="module-label">街道地址(中文)</td>
                        <td><input type="text" name="address_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">街道地址(英文)</td>
                        <td><input type="text" name="address_en" class="module-input"></td>
                        <td class="module-label">部门/楼层(中文)</td>
                        <td><input type="text" name="department_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">部门/楼层(英文)</td>
                        <td><input type="text" name="department_en" class="module-input"></td>
                        <td class="module-label">证件类型</td>
                        <td>
                            <select name="id_type" class="module-input">
                                <option value="">--请选择--</option>
                                <?php foreach ($id_types as $v): ?>
                                    <option value="<?= h($v) ?>"><?= h($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label module-req">*证件号</td>
                        <td><input type="text" name="id_number" class="module-input" required></td>
                        <td class="module-label">费用减案</td>
                        <td>
                            <select name="is_fee_reduction" class="module-input">
                                <option value="0">否</option>
                                <option value="1">是</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">费用减案有效期</td>
                        <td>
                            <input type="date" name="fee_reduction_start" class="module-input" style="width:48%;display:inline-block;"> -
                            <input type="date" name="fee_reduction_end" class="module-input" style="width:48%;display:inline-block;">
                        </td>
                        <td class="module-label">备案证件号</td>
                        <td><input type="text" name="fee_reduction_code" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">中国总委托编号</td>
                        <td><input type="text" name="cn_agent_code" class="module-input"></td>
                        <td class="module-label">PCT总委托编号</td>
                        <td><input type="text" name="pct_agent_code" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">监控年费</td>
                        <td>
                            <select name="is_fee_monitor" class="module-input">
                                <option value="0">否</option>
                                <option value="1">是</option>
                            </select>
                        </td>
                        <td class="module-label">国家(地区)</td>
                        <td><input type="text" name="country" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">国籍</td>
                        <td><input type="text" name="nationality" class="module-input"></td>
                        <td class="module-label">营业执照</td>
                        <td><input type="text" name="business_license" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">是否第一联系人</td>
                        <td>
                            <label><input type="checkbox" name="is_first_contact" value="1"> 是</label>
                        </td>
                        <td class="module-label">作为收据抬头</td>
                        <td>
                            <label><input type="checkbox" name="is_receipt_title" value="1" id="is_receipt_title_cb"> 是</label>
                        </td>
                    </tr>
                    <tr id="receipt_title_row" style="display:none;">
                        <td class="module-label">申请人收据抬头</td>
                        <td><input type="text" name="receipt_title" class="module-input"></td>
                        <td class="module-label">申请人统一社会信用代码</td>
                        <td><input type="text" name="credit_code" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">备注</td>
                        <td colspan="3"><textarea name="remark" class="module-input" style="min-height:48px;width:100%;"></textarea></td>
                    </tr>
                    <tr>
                        <td class="module-label">上传文件</td>
                        <td colspan="3">
                            <div style="margin-bottom:8px;">
                                <label>费减证明：</label>
                                <input type="text" id="file-name-fee-reduction" placeholder="文件命名（可选）" style="width:120px;">
                                <input type="file" id="file-fee-reduction" style="display:inline-block;width:auto;">
                                <button type="button" class="btn-mini" id="btn-upload-fee-reduction">上传</button>
                                <div id="list-fee-reduction" style="margin-top:4px;"></div>
                            </div>
                            <div style="margin-bottom:8px;">
                                <label>总委托书：</label>
                                <input type="text" id="file-name-power" placeholder="文件命名（可选）" style="width:120px;">
                                <input type="file" id="file-power" style="display:inline-block;width:auto;">
                                <button type="button" class="btn-mini" id="btn-upload-power">上传</button>
                                <div id="list-power" style="margin-top:4px;"></div>
                            </div>
                            <div>
                                <label>附件：</label>
                                <input type="text" id="file-name-attach" placeholder="文件命名（可选，所有文件同名）" style="width:120px;">
                                <input type="file" id="file-attach" multiple style="display:inline-block;width:auto;">
                                <button type="button" class="btn-mini" id="btn-upload-attach">上传</button>
                                <div id="list-attach" style="margin-top:4px;"></div>
                            </div>
                        </td>
                    </tr>
                </table>
                <div style="text-align:center;margin-top:12px;">
                    <button type="button" class="btn-save-applicant btn-mini" style="margin-right:16px;">保存</button>
                    <button type="button" class="btn-cancel-applicant btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function() {
        function bindApplicantEvents() {
            var modal = document.getElementById('applicant-modal');
            var closeBtn = document.getElementById('applicant-modal-close');
            var addBtn = document.getElementById('btn-add-applicant');
            var list = document.getElementById('applicant-list');
            var form = document.getElementById('applicant-form');
            if (!modal || !addBtn || !list || !form) return;

            function showModal(data) {
                form.reset();
                for (var k in data) {
                    if (form[k]) form[k].value = data[k] !== null ? data[k] : '';
                }
                if (data.case_type) {
                    var arr = data.case_type.split(',');
                    form.querySelectorAll('input[type=checkbox][name^=case_type_]').forEach(function(cb) {
                        cb.checked = arr.indexOf(cb.value) !== -1;
                    });
                } else {
                    form.querySelectorAll('input[type=checkbox][name^=case_type_]').forEach(function(cb) {
                        cb.checked = false;
                    });
                }
                form.is_first_contact.checked = data.is_first_contact == 1;
                form.is_receipt_title.checked = data.is_receipt_title == 1;
                document.getElementById('receipt_title_row').style.display = form.is_receipt_title.checked ? '' : 'none';
                modal.style.display = 'flex';
                bindFileUpload(data.id);
            }

            function hideModal() {
                modal.style.display = 'none';
            }
            addBtn.onclick = function() {
                showModal({
                    id: 0,
                    customer_id: form.customer_id.value,
                    case_type: '',
                    applicant_type: '',
                    entity_type: '',
                    name_cn: '',
                    name_en: '',
                    name_xing_cn: '',
                    name_xing_en: '',
                    is_first_contact: 0,
                    is_receipt_title: 0,
                    receipt_title: '',
                    credit_code: '',
                    contact_person: '',
                    phone: '',
                    email: '',
                    province: '',
                    city_cn: '',
                    city_en: '',
                    district: '',
                    postcode: '',
                    address_cn: '',
                    address_en: '',
                    department_cn: '',
                    department_en: '',
                    id_type: '',
                    id_number: '',
                    is_fee_reduction: 0,
                    fee_reduction_start: '',
                    fee_reduction_end: '',
                    fee_reduction_code: '',
                    cn_agent_code: '',
                    pct_agent_code: '',
                    is_fee_monitor: 0,
                    country: '',
                    nationality: '',
                    business_license: '',
                    remark: ''
                });
            };
            closeBtn.onclick = hideModal;
            form.querySelector('.btn-cancel-applicant').onclick = hideModal;
            list.onclick = function(e) {
                var tr = e.target.closest('tr[data-id]');
                if (!tr) return;
                var id = tr.getAttribute('data-id');
                if (e.target.classList.contains('btn-edit-applicant')) {
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'get');
                    fd.append('id', id);
                    fd.append('customer_id', form.customer_id.value);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant.php', true);
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
                } else if (e.target.classList.contains('btn-del-applicant')) {
                    if (!confirm('确定删除该申请人？')) return;
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    fd.append('customer_id', form.customer_id.value);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                                if (tabContent) {
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', 'modules/customer_management/customer/customer_tabs/applicant.php?customer_id=' + form.customer_id.value, true);
                                    xhr2.onload = function() {
                                        tabContent.innerHTML = xhr2.responseText;
                                        setTimeout(bindApplicantEvents, 0);
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
            form.querySelector('.btn-save-applicant').onclick = function() {
                var checkedTypes = Array.from(form.querySelectorAll('input[type=checkbox][name^=case_type_]:checked')).map(function(cb) {
                    return cb.value;
                });
                form.case_type.value = checkedTypes.join(',');
                form.is_first_contact.value = form.is_first_contact.checked ? 1 : 0;
                form.is_receipt_title.value = form.is_receipt_title.checked ? 1 : 0;
                if (!form.is_receipt_title.checked) {
                    form.receipt_title.value = '';
                    form.credit_code.value = '';
                }
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant.php', true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                            if (tabContent) {
                                var xhr2 = new XMLHttpRequest();
                                xhr2.open('GET', 'modules/customer_management/customer/customer_tabs/applicant.php?customer_id=' + form.customer_id.value, true);
                                xhr2.onload = function() {
                                    tabContent.innerHTML = xhr2.responseText;
                                    setTimeout(bindApplicantEvents, 0);
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
            var pageSizeSelect = document.getElementById('applicant-page-size-select');
            var btnFirstPage = document.getElementById('applicant-btn-first-page');
            var btnPrevPage = document.getElementById('applicant-btn-prev-page');
            var btnNextPage = document.getElementById('applicant-btn-next-page');
            var btnLastPage = document.getElementById('applicant-btn-last-page');
            var pageInput = document.getElementById('applicant-page-input');
            var btnPageJump = document.getElementById('applicant-btn-page-jump');
            var totalPages = parseInt(document.getElementById('applicant-total-pages').textContent) || 1;
            var customerId = document.querySelector('[name=customer_id]').value;

            function loadApplicantPage(page, pageSize) {
                var tabContent = window.parent && window.parent.document.getElementById('customer-tab-content');
                var url = 'modules/customer_management/customer/customer_tabs/applicant.php?customer_id=' + customerId + '&page=' + page + '&page_size=' + pageSize;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.onload = function() {
                    if (tabContent) {
                        tabContent.innerHTML = xhr.responseText;
                        setTimeout(bindApplicantEvents, 0);
                    }
                };
                xhr.send();
            }
            if (pageSizeSelect) pageSizeSelect.onchange = function() {
                loadApplicantPage(1, this.value);
            };
            if (btnFirstPage) btnFirstPage.onclick = function() {
                loadApplicantPage(1, pageSizeSelect.value);
            };
            if (btnPrevPage) btnPrevPage.onclick = function() {
                var cur = parseInt(document.getElementById('applicant-current-page').textContent) || 1;
                if (cur > 1) loadApplicantPage(cur - 1, pageSizeSelect.value);
            };
            if (btnNextPage) btnNextPage.onclick = function() {
                var cur = parseInt(document.getElementById('applicant-current-page').textContent) || 1;
                if (cur < totalPages) loadApplicantPage(cur + 1, pageSizeSelect.value);
            };
            if (btnLastPage) btnLastPage.onclick = function() {
                loadApplicantPage(totalPages, pageSizeSelect.value);
            };
            if (btnPageJump) btnPageJump.onclick = function() {
                var page = parseInt(pageInput.value) || 1;
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages;
                loadApplicantPage(page, pageSizeSelect.value);
            };
            // 控制"作为收据抬头"显示隐藏
            var receiptCb = document.getElementById('is_receipt_title_cb');
            if (receiptCb) {
                receiptCb.addEventListener('change', function() {
                    document.getElementById('receipt_title_row').style.display = receiptCb.checked ? '' : 'none';
                });
            }
        }
        // 文件上传/删除/回显逻辑
        function renderFileList(applicantId, fileType, listDivId) {
            var listDiv = document.getElementById(listDivId);
            listDiv.innerHTML = '加载中...';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php?action=list&applicant_id=' + applicantId + '&file_type=' + encodeURIComponent(fileType), true);
            xhr.onload = function() {
                console.log('文件列表响应', fileType, xhr.status, xhr.responseText);
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.files.length > 0) {
                        // 优化为表格样式
                        var html = '<table class="module-table" style="margin:0;width:100%;"><thead><tr>' +
                            '<th style="width:180px;">文件名</th>' +
                            '<th style="width:180px;">原文件名</th>' +
                            '<th style="width:140px;">上传时间</th>' +
                            '<th style="width:120px;">操作</th>' +
                            '</tr></thead><tbody>';
                        res.files.forEach(function(f) {
                            html += '<tr>' +
                                '<td>' + (f.file_name || '') + '</td>' +
                                '<td>' + (f.origin_name || f.file_name || '') + '</td>' +
                                '<td>' + (f.created_at ? f.created_at.substr(0, 16) : '') + '</td>' +
                                '<td>' +
                                '<a href="' + f.file_path + '" download class="btn-mini" style="margin-right:8px;">下载</a>' +
                                '<button type="button" class="btn-mini file-del" data-id="' + f.id + '" style="color:#f44336;">删除</button>' +
                                '</td>' +
                                '</tr>';
                        });
                        html += '</tbody></table>';
                        listDiv.innerHTML = html;
                        listDiv.querySelectorAll('.file-del').forEach(function(btn) {
                            btn.onclick = function(e) {
                                e.preventDefault();
                                if (!confirm('确定删除该文件？')) return;
                                var id = this.getAttribute('data-id');
                                var xhr2 = new XMLHttpRequest();
                                var fd = new FormData();
                                fd.append('action', 'delete');
                                fd.append('id', id);
                                xhr2.open('POST', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php', true);
                                xhr2.onload = function() {
                                    renderFileList(applicantId, fileType, listDivId);
                                };
                                xhr2.send(fd);
                            };
                        });
                    } else {
                        listDiv.innerHTML = '<span style="color:#888;">暂无文件</span>';
                    }
                } catch (e) {
                    console.error('文件列表解析失败', e, xhr.responseText);
                    listDiv.innerHTML = '<span style="color:#888;">加载失败</span>';
                }
            };
            xhr.onerror = function(e) {
                console.error('文件列表请求失败', e);
            };
            xhr.send();
        }

        function bindFileUpload(applicantId) {
            // 先解绑旧事件，防止重复绑定
            var btnFee = document.getElementById('btn-upload-fee-reduction');
            var btnPower = document.getElementById('btn-upload-power');
            var btnAttach = document.getElementById('btn-upload-attach');
            if (btnFee) btnFee.onclick = null;
            if (btnPower) btnPower.onclick = null;
            if (btnAttach) btnAttach.onclick = null;
            // 费减证明
            document.getElementById('btn-upload-fee-reduction').onclick = function() {
                var fileInput = document.getElementById('file-fee-reduction');
                var nameInput = document.getElementById('file-name-fee-reduction');
                if (!fileInput.files[0]) {
                    alert('请选择文件');
                    return;
                }
                var fd = new FormData();
                fd.append('action', 'upload');
                fd.append('applicant_id', applicantId);
                fd.append('file_type', '费减证明');
                fd.append('file', fileInput.files[0]);
                fd.append('file_name', nameInput ? nameInput.value : '');
                console.log('上传参数', '费减证明', applicantId, nameInput ? nameInput.value : '', fileInput.files[0]);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php', true);
                xhr.onload = function() {
                    console.log('上传响应', '费减证明', xhr.status, xhr.responseText);
                    fileInput.value = '';
                    if (nameInput) nameInput.value = '';
                    renderFileList(applicantId, '费减证明', 'list-fee-reduction');
                };
                xhr.onerror = function(e) {
                    console.error('上传请求失败', e);
                };
                xhr.send(fd);
            };
            // 总委托书
            document.getElementById('btn-upload-power').onclick = function() {
                var fileInput = document.getElementById('file-power');
                var nameInput = document.getElementById('file-name-power');
                if (!fileInput.files[0]) {
                    alert('请选择文件');
                    return;
                }
                var fd = new FormData();
                fd.append('action', 'upload');
                fd.append('applicant_id', applicantId);
                fd.append('file_type', '总委托书');
                fd.append('file', fileInput.files[0]);
                fd.append('file_name', nameInput ? nameInput.value : '');
                console.log('上传参数', '总委托书', applicantId, nameInput ? nameInput.value : '', fileInput.files[0]);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php', true);
                xhr.onload = function() {
                    console.log('上传响应', '总委托书', xhr.status, xhr.responseText);
                    fileInput.value = '';
                    if (nameInput) nameInput.value = '';
                    renderFileList(applicantId, '总委托书', 'list-power');
                };
                xhr.onerror = function(e) {
                    console.error('上传请求失败', e);
                };
                xhr.send(fd);
            };
            // 附件（多文件）
            document.getElementById('btn-upload-attach').onclick = function() {
                var fileInput = document.getElementById('file-attach');
                var nameInput = document.getElementById('file-name-attach');
                if (!fileInput.files.length) {
                    alert('请选择文件');
                    return;
                }
                var files = Array.from(fileInput.files);
                var uploadNext = function(idx) {
                    if (idx >= files.length) {
                        fileInput.value = '';
                        if (nameInput) nameInput.value = '';
                        renderFileList(applicantId, '附件', 'list-attach');
                        return;
                    }
                    var fd = new FormData();
                    fd.append('action', 'upload');
                    fd.append('applicant_id', applicantId);
                    fd.append('file_type', '附件');
                    fd.append('file', files[idx]);
                    fd.append('file_name', nameInput ? nameInput.value : '');
                    console.log('上传参数', '附件', applicantId, nameInput ? nameInput.value : '', files[idx]);
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php', true);
                    xhr.onload = function() {
                        console.log('上传响应', '附件', xhr.status, xhr.responseText);
                        uploadNext(idx + 1);
                    };
                    xhr.onerror = function(e) {
                        console.error('上传请求失败', e);
                    };
                    xhr.send(fd);
                };
                uploadNext(0);
            };
            // 初始加载
            renderFileList(applicantId, '费减证明', 'list-fee-reduction');
            renderFileList(applicantId, '总委托书', 'list-power');
            renderFileList(applicantId, '附件', 'list-attach');
        }
        bindApplicantEvents();
    })();
</script>