<?php
// 申请人列表页面 - 客户管理/客户模块下的申请人管理功能

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

// 查询条件选项
$yesno = ['' => '--请选择--', '0' => '否', '1' => '是'];

// 查询所有客户用于下拉
$customer_stmt = $pdo->prepare("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn ASC");
$customer_stmt->execute();
$customers = $customer_stmt->fetchAll();

// 处理AJAX请求
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    $page = max(1, intval($_GET['page'] ?? 1));
    $page_size = min(max(1, intval($_GET['page_size'] ?? 10)), 100);
    $offset = ($page - 1) * $page_size;
    $where = [];
    $params = [];
    // 查询条件
    if (!empty($_GET['name_cn'])) {
        $where[] = 'a.name_cn LIKE :name_cn';
        $params['name_cn'] = '%' . $_GET['name_cn'] . '%';
    }
    if (!empty($_GET['address_cn'])) {
        $where[] = 'a.address_cn LIKE :address_cn';
        $params['address_cn'] = '%' . $_GET['address_cn'] . '%';
    }
    if (!empty($_GET['country'])) {
        $where[] = 'a.country LIKE :country';
        $params['country'] = '%' . $_GET['country'] . '%';
    }
    if (!empty($_GET['customer_id'])) {
        $where[] = 'a.customer_id = :customer_id';
        $params['customer_id'] = $_GET['customer_id'];
    }
    if (!empty($_GET['created_from'])) {
        $where[] = 'a.created_at >= :created_from';
        $params['created_from'] = $_GET['created_from'] . ' 00:00:00';
    }
    if (!empty($_GET['created_to'])) {
        $where[] = 'a.created_at <= :created_to';
        $params['created_to'] = $_GET['created_to'] . ' 23:59:59';
    }
    if (isset($_GET['is_fee_monitor']) && $_GET['is_fee_monitor'] !== '') {
        $where[] = 'a.is_fee_monitor = :is_fee_monitor';
        $params['is_fee_monitor'] = $_GET['is_fee_monitor'];
    }
    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count_sql = "SELECT COUNT(*) FROM applicant a $sql_where";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);
    $sql = "SELECT a.*, c.customer_name_cn FROM applicant a LEFT JOIN customer c ON a.customer_id = c.id $sql_where ORDER BY a.id DESC LIMIT :offset, :limit";
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
        foreach ($rows as $index => $a) {
            $area = htmlspecialchars(($a['province'] ?? '') . ($a['city_cn'] ? ' ' . $a['city_cn'] : '') . ($a['district'] ? ' ' . $a['district'] : ''));
            $case_types = htmlspecialchars(str_replace(',', '，', $a['case_type'] ?? ''));
            $html .= '<tr data-id="' . $a['id'] . '" data-customer-id="' . $a['customer_id'] . '">' .
                '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>' .
                '<td>' . htmlspecialchars($a['customer_name_cn'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($a['name_cn'] ?? '') . '</td>' .
                // '<td>' . htmlspecialchars($a['name_en'] ?? '') . '</td>' .
                '<td>' . $area . '</td>' .
                '<td>' . htmlspecialchars($a['address_cn'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($a['phone'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($a['email'] ?? '') . '</td>' .
                '<td style="text-align:center;">' . ($a['is_first_contact'] ? '是' : '否') . '</td>' .
                '<td>' . $case_types . '</td>' .
                '<td style="text-align:center;">' .
                '<button type="button" class="btn-mini btn-edit">✎</button>' .
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
?>
<div class="module-panel">
    <form id="search-form" class="module-form" autocomplete="off" style="margin-bottom:12px;">
        <div class="module-btns">
            <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
        </div>
        <table class="module-table" style="margin-bottom:10px;width:100%;min-width:0;">
            <tr>
                <td class="module-label" style="width:110px;">申请人：</td>
                <td style="width:240px;"><input type="text" name="name_cn" class="module-input" style="width:220px;"></td>
                <td class="module-label" style="width:110px;">申请人地址：</td>
                <td style="width:240px;"><input type="text" name="address_cn" class="module-input" style="width:220px;"></td>
            </tr>
            <tr>
                <td class="module-label">客户名称：</td>
                <td>
                    <select name="customer_id" class="module-input" style="width:220px;">
                        <option value="">--全部--</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= h($c['id']) ?>"><?= h($c['customer_name_cn']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="module-label">国家(地区)：</td>
                <td><input type="text" name="country" class="module-input" style="width:220px;"></td>
            </tr>
            <tr>
                <td class="module-label">创建日期：</td>
                <td>
                    <input type="date" name="created_from" class="module-input" style="width:104px;display:inline-block;"> -
                    <input type="date" name="created_to" class="module-input" style="width:104px;display:inline-block;">
                </td>
                <td class="module-label">是否监控年费：</td>
                <td>
                    <select name="is_fee_monitor" class="module-input" style="width:220px;">
                        <?php foreach ($yesno as $k => $v): ?>
                            <option value="<?= h($k) ?>"><?= h($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

    </form>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:40px;text-align:center;">序号</th>
                <th style="width:140px;">客户名称</th>
                <th style="width:120px;">申请人(中文)</th>
                <!-- <th style="width:120px;">申请人(英文)</th> -->
                <th style="width:120px;">所属地区</th>
                <th style="width:160px;">申请人地址</th>
                <th style="width:100px;">联系电话</th>
                <th style="width:140px;">邮件</th>
                <th style="width:60px;">第一联系人</th>
                <th style="width:100px;">案件类型</th>
                <th style="width:90px;">操作</th>
            </tr>
        </thead>
        <tbody id="applicant-list">
            <tr>
                <td colspan="11" style="text-align:center;padding:20px 0;">正在加载数据...</td>
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
<!-- 申请人编辑弹窗（完整字段，复用applicant.php结构） -->
<div id="edit-applicant-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:950px;max-width:98vw;max-height:80vh;position:relative;display:flex;flex-direction:column;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="edit-applicant-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">编辑申请人</h3>
        <div style="flex:1 1 auto;overflow-y:auto;">
            <form id="edit-applicant-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <input type="hidden" name="customer_id" value="">
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
                                <option value="大专院校">大专院校</option>
                                <option value="科研单位">科研单位</option>
                                <option value="事业单位">事业单位</option>
                                <option value="工矿企业">工矿企业</option>
                                <option value="个人">个人</option>
                            </select>
                        </td>
                        <td class="module-label module-req">*实体类型</td>
                        <td>
                            <select name="entity_type" class="module-input" required>
                                <option value="">--请选择--</option>
                                <option value="大实体">大实体</option>
                                <option value="小实体">小实体</option>
                                <option value="微实体">微实体</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">案件类型</td>
                        <td colspan="3">
                            <label style="margin-right:18px;"><input type="checkbox" name="case_type_patent" value="专利"> 专利</label>
                            <label style="margin-right:18px;"><input type="checkbox" name="case_type_trademark" value="商标"> 商标</label>
                            <label style="margin-right:18px;"><input type="checkbox" name="case_type_copyright" value="版权"> 版权</label>
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
                                <option value="居民身份证">居民身份证</option>
                                <option value="护照">护照</option>
                                <option value="营业执照">营业执照</option>
                                <option value="其他">其他</option>
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
                    <button type="button" class="btn-save-edit-applicant btn-mini" style="margin-right:16px;">保存</button>
                    <button type="button" class="btn-cancel-edit-applicant btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function() {
        var form = document.getElementById('search-form'),
            btnSearch = form.querySelector('.btn-search'),
            btnReset = form.querySelector('.btn-reset'),
            applicantList = document.getElementById('applicant-list'),
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

        function loadApplicantData() {
            applicantList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);
            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') params.append(pair[0], pair[1]);
            }
            var requestUrl = 'modules/customer_management/customer/applicant_list.php'; // 绝对路径
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log('AJAX响应内容:', xhr.status, xhr.responseText); // 调试输出
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                applicantList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                            } else {
                                applicantList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            applicantList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            console.error('JSON解析失败', e, xhr.responseText);
                        }
                    } else {
                        applicantList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            applicantList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.querySelector('.btn-del').onclick = function() {
                    if (!confirm('确定删除该申请人？')) return;
                    var id = row.getAttribute('data-id');
                    var customerId = row.getAttribute('data-customer-id');
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    fd.append('customer_id', customerId);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                loadApplicantData();
                            } else {
                                alert('删除失败');
                            }
                        } catch (e) {
                            alert('删除失败');
                        }
                    };
                    xhr.send(fd);
                };
                row.querySelector('.btn-edit').onclick = function() {
                    // 完整弹窗编辑，传递id和customer_id
                    var id = row.getAttribute('data-id');
                    var customerId = row.getAttribute('data-customer-id');
                    var modal = document.getElementById('edit-applicant-modal');
                    var form = document.getElementById('edit-applicant-form');
                    form.reset();
                    var fd = new FormData();
                    fd.append('action', 'get');
                    fd.append('id', id);
                    fd.append('customer_id', customerId);
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success && res.data) {
                                for (var k in res.data) {
                                    if (form[k] !== undefined && form[k].type !== 'checkbox') form[k].value = res.data[k] !== null ? res.data[k] : '';
                                }
                                // 多选案件类型
                                if (res.data.case_type) {
                                    var arr = res.data.case_type.split(',');
                                    form.querySelectorAll('input[type=checkbox][name^=case_type_]').forEach(function(cb) {
                                        cb.checked = arr.indexOf(cb.value) !== -1;
                                    });
                                } else {
                                    form.querySelectorAll('input[type=checkbox][name^=case_type_]').forEach(function(cb) {
                                        cb.checked = false;
                                    });
                                }
                                form.is_first_contact.checked = res.data.is_first_contact == 1;
                                form.is_receipt_title.checked = res.data.is_receipt_title == 1;
                                document.getElementById('receipt_title_row').style.display = form.is_receipt_title.checked ? '' : 'none';
                                form.customer_id.value = customerId;
                                modal.style.display = 'flex';
                                // 绑定文件上传
                                bindFileUpload(id);
                            } else {
                                alert('获取数据失败');
                            }
                        } catch (e) {
                            alert('获取数据失败');
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
            loadApplicantData();
        };
        btnReset.onclick = function() {
            form.reset();
            currentPage = 1;
            loadApplicantData();
        };
        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            loadApplicantData();
        };
        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    loadApplicantData();
                }
            };
        });
        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            loadApplicantData();
        };
        // 弹窗关闭
        document.getElementById('edit-applicant-modal-close').onclick = function() {
            document.getElementById('edit-applicant-modal').style.display = 'none';
        };
        document.querySelector('.btn-cancel-edit-applicant').onclick = function() {
            document.getElementById('edit-applicant-modal').style.display = 'none';
        };
        // 弹窗保存
        document.querySelector('.btn-save-edit-applicant').onclick = function() {
            var form = document.getElementById('edit-applicant-form');
            // 多选案件类型
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
                        document.getElementById('edit-applicant-modal').style.display = 'none';
                        loadApplicantData();
                    } else {
                        alert(res.msg || '保存失败');
                    }
                } catch (e) {
                    alert('保存失败');
                }
            };
            xhr.send(fd);
        };
        // 控制"作为收据抬头"显示隐藏
        var receiptCb = document.getElementById('is_receipt_title_cb');
        if (receiptCb) {
            receiptCb.addEventListener('change', function() {
                document.getElementById('receipt_title_row').style.display = receiptCb.checked ? '' : 'none';
            });
        }
        // 文件上传/删除/回显逻辑（复用applicant.php的bindFileUpload、renderFileList）
        function renderFileList(applicantId, fileType, listDivId) {
            var listDiv = document.getElementById(listDivId);
            listDiv.innerHTML = '加载中...';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php?action=list&applicant_id=' + applicantId + '&file_type=' + encodeURIComponent(fileType), true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.files.length > 0) {
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
                                // 把删除按钮改成a链接
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
                    listDiv.innerHTML = '<span style="color:#888;">加载失败</span>';
                }
            };
            xhr.onerror = function(e) {
                listDiv.innerHTML = '<span style="color:#888;">加载失败</span>';
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
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php', true);
                xhr.onload = function() {
                    fileInput.value = '';
                    if (nameInput) nameInput.value = '';
                    renderFileList(applicantId, '费减证明', 'list-fee-reduction');
                };
                xhr.onerror = function(e) {
                    alert('上传失败');
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
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php', true);
                xhr.onload = function() {
                    fileInput.value = '';
                    if (nameInput) nameInput.value = '';
                    renderFileList(applicantId, '总委托书', 'list-power');
                };
                xhr.onerror = function(e) {
                    alert('上传失败');
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
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php', true);
                    xhr.onload = function() {
                        uploadNext(idx + 1);
                    };
                    xhr.onerror = function(e) {
                        alert('上传失败');
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
        loadApplicantData();
    })();
</script>