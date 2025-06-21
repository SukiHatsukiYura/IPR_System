<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 专利查询功能 - 专利管理/案件管理模块下的专利查询功能

// 统一选项声明
$options = [
    'case_types' => ['发明专利', '实用新型专利', '外观设计专利', 'PCT国际专利'],
    'case_statuses' => ['待提交', '已提交', '已受理', '审查中', '已授权', '已驳回', '已放弃', '已失效'],
    'application_modes' => ['普通申请', '优先审查', '加快审查', '提前公开', '秘密申请', '国防专利'],
    'is_allocated' => ['是', '否'],
    'client_statuses' => ['正常', '关注', '重要', '紧急'],
    'business_types' => ['国内发明', '国内实用新型', '国内外观设计', '国际发明', '国际实用新型', '国际外观设计']
];

// 替换为与add_patent.php一致的选项声明
// 业务类型
$business_types = ['无效案件', '普通新申请', '专利转让', '著泉项目变更', 'PCT国际阶段', '复审', '香港登记案', '申请香港', '临时申请', '公众意见', '翻译', '专利检索案件', '代缴年费案件', '诉讼案件', '顾问', '专利许可备案', '海关备案', '其他', 'PCT国家阶段', '办理副本案件'];
// 案件状态
$case_statuses = ['请选择', '未递交', '已递交', '暂缓申请', '受理', '初审合格', '初审', '公开', '实审', '补正', '审查', '一通', '二通', '三通', '四通', '五通', '六通', '七通', '八通', '一补', '九通', '二补', '三补', '视为撤回', '主动撤回', '驳回', '复审', '无效', '视为放弃', '主动放弃', '授权', '待领证', '维持', '终止', '结案', '届满', 'PCT国际检索', '中止', '保全', '诉讼', '办理登记手续', '复审受理', 'Advisory Action', 'Appeal', 'Election Action', 'Final Action', 'Non Final Action', 'Petition', 'RCE', '公告', '视为未提出'];
// 处理事项
$process_items = ['请求优先审查', '开卷', '放弃', '更正', '无效答辩', '不予受理', '官文转达', '缴年费', '民事诉讼上诉', '主动补正', '专利权评价报告', '驳回', '取得检索报告', '请求无效', '翻译', '审查高速公路', '资助监控', '赔偿请求', '请求检索报告', '许可备案', '诉讼', '取得副本', '请求加速审查', '民事诉讼答辩', '取得申请号', '请求中止', '办理登记手续', '复审决定', '避免重复授权', '民事诉讼上诉答辩', '确认通知书', '请求保密审查', '结案', '补正', '请求恢复权利', '视为未提出', '手续补正', '取得证书', '年费滞纳金', '复审意见陈述', '提交IDS', '复审受理', '请求延长期限', '撤回', '请求提前公开', '处理审查意见', '口审', '诉讼举证', '项目申报', '办理DAS', '行政诉讼上诉答辩', '请求复审', '无效行政诉讼答辩', '请求退款', '提出行政诉讼', '缴费', '终止', '无效诉讼', '公众意见', '保密决定', '变更代理人申请补正通知', '请求实审', '提出民事诉讼', '请求副本', '新申请', '复议申请', '无效请求补充意见', '著泉项目变更', '行政诉讼上诉', '请求费用减缓', '视为未要求'];
// 申请类型
$application_types = ['请选择', '发明', '实用新型', '外观设计', '临时申请', '再公告', '植物', '集成电路布图设计', '年费', '无效', '其他'];
// 申请方式
$application_modes = ['电子申请(事务所)', '纸件申请', '其他'];
// 国家(地区)
$countries = ['中国', '美国', '日本', '韩国', '德国', '法国', '英国', '其他'];
// 案件流向
$case_flows = ['内-内', '内-外', '外-内', '外-外'];
// 起始阶段
$start_stages = ['无', '新申请', '答辩', '缴费'];
// 客户状态
$client_statuses = ['请选择', '放弃指示', '新申请指示递交', '补充申请信息资料', '修改意见', '著录项目变更指示', 'OA指示递交', '结案指示'];
// 案源国
$source_countries = ['中国', '美国', '日本', '其他'];
// 其他选项
$other_options = ['同步提交', '提前公布', '请求保密审查', '预审案件', '优先审查', '同时请求DAS码', '请求提前公开', '请求费用减缓'];

// 查询所有在职用户用于下拉
$user_stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name ASC");
$user_stmt->execute();
$users = $user_stmt->fetchAll();

// 查询所有部门用于下拉
$dept_stmt = $pdo->prepare("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY dept_name ASC");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll();

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

    // 合并查询条件
    $search_fields = [
        'case_code' => 'LIKE',
        'case_name' => 'LIKE',
        'application_no' => 'LIKE',
        'business_dept_id' => '=',
        'client_id' => '=',
        'handler_id' => '=',
        'business_type' => '=',
        'case_status' => '='
    ];

    foreach ($search_fields as $field => $op) {
        if (!empty($_GET[$field])) {
            $where[] = "$field " . ($op == 'LIKE' ? "LIKE :$field" : "= :$field");
            $params[$field] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
        }
    }

    // 特殊处理"是否配案"字段 - 将"是"/"否"转换为1/0
    if (!empty($_GET['is_allocated'])) {
        $is_allocated_value = ($_GET['is_allocated'] === '是') ? 1 : 0;
        $where[] = "is_allocated = :is_allocated";
        $params['is_allocated'] = $is_allocated_value;
    }

    // 处理申请日期范围
    if (!empty($_GET['application_date_start'])) {
        $where[] = "application_date >= :application_date_start";
        $params['application_date_start'] = $_GET['application_date_start'];
    }
    if (!empty($_GET['application_date_end'])) {
        $where[] = "application_date <= :application_date_end";
        $params['application_date_end'] = $_GET['application_date_end'];
    }

    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count_sql = "SELECT COUNT(*) FROM patent_case_info" . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);

    $sql = "SELECT p.*, 
            (SELECT dept_name FROM department WHERE id = p.business_dept_id) as business_dept_name,
            (SELECT customer_name_cn FROM customer WHERE id = p.client_id) as client_name,
            (SELECT real_name FROM user WHERE id = p.handler_id) as handler_name
            FROM patent_case_info p" . $sql_where . " ORDER BY p.id DESC LIMIT :offset, :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $page_size, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    $patents = $stmt->fetchAll();
    $html = '';
    if (empty($patents)) {
        $html = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($patents as $index => $patent) {
            $html .= '<tr data-id="' . $patent['id'] . '">';
            $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($patent['case_code'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['case_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['business_dept_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['client_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['business_type'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['case_status'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['application_no'] ?? '') . '</td>';
            $html .= '<td>' . ($patent['application_date'] ? date('Y-m-d', strtotime($patent['application_date'])) : '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['handler_name'] ?? '') . '</td>';
            $html .= '</tr>';
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

function render_user_search($name, $users, $get_val)
{
    $val = isset($_GET[$name]) ? intval($_GET[$name]) : 0;
    $display = '';
    foreach ($users as $u) {
        if ($u['id'] == $val) {
            $display = htmlspecialchars($u['real_name'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
    return '<div class="module-select-search-box">'
        . '<input type="text" class="module-input module-select-search-input" name="' . $name . '_display" value="' . $display . '" readonly placeholder="点击选择" data-realname="' . $display . '">'
        . '<input type="hidden" name="' . $name . '" value="' . ($val ? $val : '') . '">'
        . '<div class="module-select-search-list" style="display:none;">'
        .   '<input type="text" class="module-select-search-list-input" placeholder="搜索姓名">'
        .   '<div class="module-select-search-list-items"></div>'
        . '</div>'
        . '</div>';
}

function render_dept_search($name, $departments, $get_val)
{
    $val = isset($_GET[$name]) ? intval($_GET[$name]) : 0;
    $display = '';
    foreach ($departments as $d) {
        if ($d['id'] == $val) {
            $display = htmlspecialchars($d['dept_name'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
    return '<div class="module-select-search-box">'
        . '<input type="text" class="module-input module-select-search-input" name="' . $name . '_display" value="' . $display . '" readonly placeholder="点击选择" data-deptname="' . $display . '">'
        . '<input type="hidden" name="' . $name . '" value="' . ($val ? $val : '') . '">'
        . '<div class="module-select-search-list" style="display:none;">'
        .   '<input type="text" class="module-select-search-list-input" placeholder="搜索部门">'
        .   '<div class="module-select-search-list-items"></div>'
        . '</div>'
        . '</div>';
}

function render_customer_search($name, $customers, $get_val)
{
    $val = isset($_GET[$name]) ? intval($_GET[$name]) : 0;
    $display = '';
    foreach ($customers as $c) {
        if ($c['id'] == $val) {
            $display = htmlspecialchars($c['customer_name_cn'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
    return '<div class="module-select-search-box">'
        . '<input type="text" class="module-input module-select-search-input" name="' . $name . '_display" value="' . $display . '" readonly placeholder="点击选择" data-customername="' . $display . '">'
        . '<input type="hidden" name="' . $name . '" value="' . ($val ? $val : '') . '">'
        . '<div class="module-select-search-list" style="display:none;">'
        .   '<input type="text" class="module-select-search-list-input" placeholder="搜索客户">'
        .   '<div class="module-select-search-list-items"></div>'
        . '</div>'
        . '</div>';
}
?>
<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
        <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
        <button type="button" class="btn-add" onclick="window.parent.openTab ? window.parent.openTab(1, 0, null) : alert('框架导航功能不可用')"><i class="icon-add"></i> 新增专利</button>
        <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
        <button type="button" class="btn-download-template"><i class="icon-save"></i> 下载模板</button>
        <button type="button" class="btn-batch-import"><i class="icon-add"></i> 批量导入</button>
    </div>
    <form id="search-form" class="module-form" autocomplete="off">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="page_size" value="10">
        <table class="module-table" style="margin-bottom:15px;">
            <tr>
                <td class="module-label">我方文号：</td>
                <td><input type="text" name="case_code" class="module-input"></td>
                <td class="module-label">案件名称：</td>
                <td><input type="text" name="case_name" class="module-input"></td>
                <td class="module-label">申请号：</td>
                <td><input type="text" name="application_no" class="module-input"></td>
            </tr>
            <tr>
                <td class="module-label">承办部门：</td>
                <td><?= render_dept_search('business_dept_id', $departments, '') ?></td>
                <td class="module-label">客户名称：</td>
                <td><?= render_customer_search('client_id', $customers, '') ?></td>
                <td class="module-label">处理人：</td>
                <td><?= render_user_search('handler_id', $users, '') ?></td>
            </tr>
            <tr>
                <td class="module-label">业务类型：</td>
                <td><select name="business_type" class="module-input">
                        <option value="">--全部--</option><?php foreach ($business_types as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">案件状态：</td>
                <td><select name="case_status" class="module-input">
                        <option value="">--全部--</option><?php foreach ($case_statuses as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">是否配案：</td>
                <td><select name="is_allocated" class="module-input">
                        <option value="">--全部--</option>
                        <option value="是">是</option>
                        <option value="否">否</option>
                    </select></td>
            </tr>
            <tr>
                <td class="module-label">申请日期：</td>
                <td colspan="5">
                    <input type="date" name="application_date_start" class="module-input" style="width:200px;"> 至
                    <input type="date" name="application_date_end" class="module-input" style="width:200px;">
                </td>
            </tr>
        </table>
    </form>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:50px;text-align:center;">序号</th>
                <th style="width:100px;text-align:center;">我方文号</th>
                <th style="width:180px;">案件名称</th>
                <th style="width:100px;">承办部门</th>
                <th style="width:120px;">客户名称</th>
                <th style="width:100px;">业务类型</th>
                <th style="width:80px;">案件状态</th>
                <th style="width:120px;">申请号</th>
                <th style="width:100px;">申请日</th>
                <th style="width:100px;">处理人</th>
            </tr>
        </thead>
        <tbody id="patent-list">
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

<!-- 批量导入模态框 -->
<div id="batch-import-modal" class="module-modal" style="display:none;">
    <div class="module-modal-content" style="width:600px;">
        <div class="module-modal-header">
            <h3 class="module-modal-title">批量导入专利案件</h3>
            <button class="module-modal-close">&times;</button>
        </div>
        <div class="module-modal-body" style="padding:20px;">
            <div style="margin-bottom:20px;">
                <h4>导入说明：</h4>
                <ul style="margin:10px 0;padding-left:20px;color:#666;">
                    <li>请先下载Excel模板文件，使用模板文件填写数据，然后上传文件进行导入</li>
                    <li>必填字段：案件名称、承办部门ID、客户ID、处理事项、申请类型</li>
                    <li>日期格式：YYYY-MM-DD（如：2025-01-01）</li>
                    <li>支持的文件格式：.xlsx, .xls, .csv</li>
                    <li>最大文件大小：10MB</li>
                </ul>
            </div>
            <form id="import-form" enctype="multipart/form-data">
                <table class="module-table">
                    <tr>
                        <td class="module-label module-req">*选择文件</td>
                        <td>
                            <input type="file" name="import_file" id="import-file" accept=".xlsx,.xls,.csv" class="module-input" required>
                        </td>
                    </tr>

                </table>
            </form>
            <div id="import-progress" style="display:none;margin-top:20px;">
                <div style="background:#f0f0f0;border-radius:10px;overflow:hidden;">
                    <div id="progress-bar" style="height:20px;background:#29b6b0;width:0%;transition:width 0.3s;"></div>
                </div>
                <div id="progress-text" style="text-align:center;margin-top:10px;">准备导入...</div>
            </div>
            <div id="import-result" style="display:none;margin-top:20px;"></div>
        </div>
        <div class="module-modal-footer">
            <button type="button" class="btn-theme" id="btn-start-import">开始导入</button>
            <button type="button" class="btn-cancel" id="btn-cancel-import">取消</button>
        </div>
    </div>
</div>

<script>
    (function() {
        var form = document.getElementById('search-form'),
            btnSearch = document.querySelector('.btn-search'),
            btnReset = document.querySelector('.btn-reset'),
            btnEdit = document.querySelector('.btn-edit'),
            btnDownloadTemplate = document.querySelector('.btn-download-template'),
            btnBatchImport = document.querySelector('.btn-batch-import'),
            batchImportModal = document.getElementById('batch-import-modal'),
            btnStartImport = document.getElementById('btn-start-import'),
            btnCancelImport = document.getElementById('btn-cancel-import'),
            modalClose = batchImportModal.querySelector('.module-modal-close'),
            patentList = document.getElementById('patent-list'),
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
            totalPages = 1,
            selectedId = null;

        window.loadPatentData = function() {
            patentList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            selectedId = null;
            btnEdit.disabled = true;
            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);
            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') params.append(pair[0], pair[1]);
            }
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/patent_management/case_management/patent_search.php';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                patentList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                            } else {
                                patentList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            patentList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        patentList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            patentList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.onclick = function() {
                    patentList.querySelectorAll('tr[data-id]').forEach(r => r.classList.remove('module-selected'));
                    this.classList.add('module-selected');
                    selectedId = this.getAttribute('data-id');
                    btnEdit.disabled = false;
                }
            });
        }
        btnEdit.onclick = function() {
            if (!selectedId) {
                alert('请先选择要修改的专利');
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/case_management/set_edit_patent.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    if (window.parent.openTab) {
                        // 专利管理模块索引为1，专利编辑菜单索引为6，subIndex必须为null
                        window.parent.openTab(1, 6, null);
                    } else {
                        alert('框架导航功能不可用');
                    }
                }
            };
            xhr.send('patent_id=' + selectedId);
        };

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
            loadPatentData();
        };
        btnReset.onclick = function() {
            form.reset();
            document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
            document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
            currentPage = 1;
            loadPatentData();
        };
        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            loadPatentData();
        };
        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    loadPatentData();
                }
            };
        });
        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            loadPatentData();
        };

        // 用户搜索下拉
        var userData = <?php echo json_encode($users, JSON_UNESCAPED_UNICODE); ?>;
        var deptData = <?php echo json_encode($departments, JSON_UNESCAPED_UNICODE); ?>;
        var customerData = <?php echo json_encode($customers, JSON_UNESCAPED_UNICODE); ?>;

        function bindUserSearch(box) {
            var input = box.querySelector('.module-select-search-input');
            var hidden = box.querySelector('input[type=hidden]');
            var list = box.querySelector('.module-select-search-list');
            var searchInput = list.querySelector('.module-select-search-list-input');
            var itemsDiv = list.querySelector('.module-select-search-list-items');
            var data = [];

            if (input.hasAttribute('data-realname')) {
                data = userData;
            } else if (input.hasAttribute('data-deptname')) {
                data = deptData;
            } else if (input.hasAttribute('data-customername')) {
                data = customerData;
            }

            function renderList(filter) {
                var html = '<div class="module-select-search-item" data-id="">--全部--</div>',
                    found = false;
                data.forEach(function(item) {
                    var displayName = item.real_name || item.dept_name || item.customer_name_cn;
                    if (!filter || displayName.indexOf(filter) !== -1) {
                        html += '<div class="module-select-search-item" data-id="' + item.id + '">' + displayName + '</div>';
                        found = true;
                    }
                });
                if (!found && filter) html += '<div class="no-match">无匹配</div>';
                itemsDiv.innerHTML = html;
            }
            input.onclick = function() {
                renderList('');
                list.style.display = 'block';
                searchInput.value = '';
                searchInput.focus();
            };
            searchInput.oninput = function() {
                renderList(searchInput.value.trim());
            };
            document.addEventListener('click', function(e) {
                if (!box.contains(e.target)) list.style.display = 'none';
            });
            itemsDiv.onmousedown = function(e) {
                var item = e.target.closest('.module-select-search-item');
                if (item) {
                    input.value = item.textContent === '--全部--' ? '' : item.textContent;
                    hidden.value = item.getAttribute('data-id');
                    list.style.display = 'none';
                }
            };
        }

        // 下载模板按钮事件
        btnDownloadTemplate.onclick = function() {
            var baseUrl = window.location.href.split('?')[0];
            var downloadUrl = baseUrl.replace('index.php', '') + 'modules/patent_management/case_management/download_template.php';
            window.open(downloadUrl, '_blank');
        };

        // 批量导入按钮事件
        btnBatchImport.onclick = function() {
            batchImportModal.style.display = 'flex';
            // 重置表单
            document.getElementById('import-form').reset();
            document.getElementById('import-progress').style.display = 'none';
            document.getElementById('import-result').style.display = 'none';
            btnStartImport.disabled = false;
            btnStartImport.textContent = '开始导入';
        };

        // 关闭模态框
        window.closeBatchImportModal = function() {
            batchImportModal.style.display = 'none';
        };
        btnCancelImport.onclick = closeBatchImportModal;
        modalClose.onclick = closeBatchImportModal;

        // 开始导入按钮事件
        btnStartImport.onclick = function() {
            var fileInput = document.getElementById('import-file');
            var file = fileInput.files[0];

            if (!file) {
                alert('请选择要导入的Excel文件');
                return;
            }

            // 检查文件类型
            var allowedTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'text/csv',
                'text/plain',
                'application/csv'
            ];
            if (!allowedTypes.includes(file.type)) {
                alert('请选择Excel或CSV文件（.xlsx、.xls或.csv格式）');
                return;
            }

            // 检查文件大小（10MB）
            if (file.size > 10 * 1024 * 1024) {
                alert('文件大小不能超过10MB');
                return;
            }

            // 显示进度条
            document.getElementById('import-progress').style.display = 'block';
            document.getElementById('import-result').style.display = 'none';
            btnStartImport.disabled = true;
            btnStartImport.textContent = '导入中...';

            // 准备表单数据
            var formData = new FormData(document.getElementById('import-form'));
            formData.append('action', 'batch_import');

            // 发送请求
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/case_management/batch_import.php', true);

            // 监听上传进度
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percentComplete = (e.loaded / e.total) * 100;
                    document.getElementById('progress-bar').style.width = percentComplete + '%';
                    document.getElementById('progress-text').textContent = '上传中... ' + Math.round(percentComplete) + '%';
                }
            };

            xhr.onload = function() {
                btnStartImport.disabled = false;
                btnStartImport.textContent = '开始导入';

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        document.getElementById('progress-bar').style.width = '100%';
                        document.getElementById('progress-text').textContent = '导入完成';

                        // 显示结果
                        var resultDiv = document.getElementById('import-result');
                        resultDiv.style.display = 'block';

                        if (response.success) {
                            resultDiv.innerHTML = '<div style="color:#388e3c;"><strong>导入成功！</strong><br>' +
                                '成功导入：' + response.success_count + ' 条<br>' +
                                (response.error_count > 0 ? '导入失败：' + response.error_count + ' 条<br>' : '') +
                                (response.errors && response.errors.length > 0 ? '<br>错误详情：<br>' + response.errors.join('<br>') : '') +
                                '<br><br><button class="btn-theme" onclick="loadPatentData(); closeBatchImportModal();">刷新列表并关闭</button>' +
                                '</div>';
                        } else {
                            resultDiv.innerHTML = '<div style="color:#f44336;"><strong>导入失败：</strong><br>' +
                                (response.message || '未知错误') +
                                '<br><br><button class="btn-cancel" onclick="closeBatchImportModal();">关闭</button>' +
                                '</div>';
                        }
                    } catch (e) {
                        document.getElementById('import-result').innerHTML = '<div style="color:#f44336;">导入失败：服务器响应错误</div>';
                        document.getElementById('import-result').style.display = 'block';
                    }
                } else {
                    document.getElementById('import-result').innerHTML = '<div style="color:#f44336;">导入失败：网络错误</div>';
                    document.getElementById('import-result').style.display = 'block';
                }
            };

            xhr.onerror = function() {
                btnStartImport.disabled = false;
                btnStartImport.textContent = '开始导入';
                document.getElementById('import-result').innerHTML = '<div style="color:#f44336;">导入失败：网络连接错误</div>';
                document.getElementById('import-result').style.display = 'block';
            };

            xhr.send(formData);
        };

        document.querySelectorAll('.module-select-search-box').forEach(bindUserSearch);
        loadPatentData();
    })();
</script>