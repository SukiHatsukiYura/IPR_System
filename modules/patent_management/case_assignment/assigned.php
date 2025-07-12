<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php'); // 引入通用函数库
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 已配案功能 - 专利管理/配案管理模块下的已配案案件管理功能


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



// 处理置为未配案功能AJAX请求
if (isset($_POST['action']) && $_POST['action'] == 'set_unallocated') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => '用户未登录']);
        exit;
    }

    $case_ids = $_POST['case_ids'] ?? '';

    if (empty($case_ids)) {
        echo json_encode(['success' => false, 'msg' => '请选择要设置为未配案的案件']);
        exit;
    }

    try {
        $case_ids_array = explode(',', $case_ids);
        $success_count = 0;

        $pdo->beginTransaction();

        foreach ($case_ids_array as $case_id) {
            $case_id = intval($case_id);
            if ($case_id <= 0) continue;

            // 检查案件是否存在且已配案
            $check_stmt = $pdo->prepare("SELECT id, case_code FROM patent_case_info WHERE id = ? AND is_allocated = 1");
            $check_stmt->execute([$case_id]);
            $case_info = $check_stmt->fetch();

            if ($case_info) {
                // 更新为未配案
                $update_stmt = $pdo->prepare("UPDATE patent_case_info SET is_allocated = 0, updated_at = NOW() WHERE id = ?");
                $update_stmt->execute([$case_id]);
                $success_count++;
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'msg' => "成功将 {$success_count} 个案件设置为未配案"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => '设置未配案失败: ' . $e->getMessage()]);
    }
    exit;
}

// 处理AJAX请求
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    $page = max(1, intval($_GET['page'] ?? 1));
    $page_size = min(max(1, intval($_GET['page_size'] ?? 10)), 100);
    $offset = ($page - 1) * $page_size;
    $where = [];
    $params = [];

    // 固定筛选条件：只显示已配案的案件
    $where[] = "is_allocated = 1";

    // 合并其他查询条件
    $search_fields = [
        'case_code' => 'LIKE',
        'case_name' => 'LIKE',
        'application_no' => 'LIKE',
        'business_dept_id' => '=',
        'client_id' => '=',
        'business_type' => '=',
        'case_status' => '=',
        'application_type' => '='
    ];

    foreach ($search_fields as $field => $op) {
        if (!empty($_GET[$field])) {
            $where[] = "$field " . ($op == 'LIKE' ? "LIKE ?" : "= ?");
            $params[] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
        }
    }

    // 处理申请日期范围
    if (!empty($_GET['application_date_start'])) {
        $where[] = "application_date >= ?";
        $params[] = $_GET['application_date_start'];
    }
    if (!empty($_GET['application_date_end'])) {
        $where[] = "application_date <= ?";
        $params[] = $_GET['application_date_end'];
    }

    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count_sql = "SELECT COUNT(*) FROM patent_case_info" . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);

    $sql = "SELECT p.*, 
            (SELECT dept_name FROM department WHERE id = p.business_dept_id) as business_dept_name,
            (SELECT customer_name_cn FROM customer WHERE id = p.client_id) as client_name
            FROM patent_case_info p" . $sql_where . " ORDER BY p.id DESC LIMIT ?, ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$offset, $page_size]));
    $patents = $stmt->fetchAll();
    $html = '';
    if (empty($patents)) {
        $html = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($patents as $index => $patent) {
            $html .= '<tr data-id="' . $patent['id'] . '">';
            $html .= '<td style="text-align:center;"><input type="checkbox" class="case-checkbox" value="' . $patent['id'] . '"></td>';
            $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($patent['case_code'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['case_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['business_dept_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['client_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['business_type'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['case_status'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['application_no'] ?? '') . '</td>';
            $html .= '<td>' . ($patent['application_date'] ? date('Y-m-d', strtotime($patent['application_date'])) : '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['application_type'] ?? '') . '</td>';
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

// 格式化数据为通用下拉框函数所需格式
$departments_options = [];
$customers_options = [];
$business_types_options = [];
$case_statuses_options = [];
$application_types_options = [];

foreach ($departments as $dept) {
    $departments_options[$dept['id']] = $dept['dept_name'];
}

foreach ($customers as $customer) {
    $customers_options[$customer['id']] = $customer['customer_name_cn'];
}

foreach ($business_types as $type) {
    $business_types_options[$type] = $type;
}

foreach ($case_statuses as $status) {
    $case_statuses_options[$status] = $status;
}

foreach ($application_types as $type) {
    $application_types_options[$type] = $type;
}
// 引入搜索下拉框资源
render_select_search_assets();
?>
<div class="module-panel">
    <div class="module-btns" style="display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
            <button type="button" class="btn-add" onclick="window.parent.openTab ? window.parent.openTab(1, 0, null) : alert('框架导航功能不可用')"><i class="icon-add"></i> 新增专利</button>
            <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
            <button type="button" class="btn-set-unallocated btn-mini" disabled><i class="icon-cancel"></i> 置为未配案</button>
        </div>
    </div>
    <?php render_info_notice("已配案：只显示已配案的专利案件", 'success', 'icon-list'); ?>
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
                <td><?php render_select_search('business_dept_id', $departments_options, $_GET['business_dept_id'] ?? ''); ?></td>
                <td class="module-label">客户名称：</td>
                <td><?php render_select_search('client_id', $customers_options, $_GET['client_id'] ?? ''); ?></td>
                <td class="module-label">申请类型：</td>
                <td><select name="application_type" class="module-input">
                        <option value="">--全部--</option><?php foreach ($application_types as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
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
                <td class="module-label"></td>
                <td></td>
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
                <th style="width:40px;text-align:center;"><input type="checkbox" id="select-all"></th>
                <th style="width:50px;text-align:center;">序号</th>
                <th style="width:100px;text-align:center;">我方文号</th>
                <th style="width:180px;">案件名称</th>
                <th style="width:100px;">承办部门</th>
                <th style="width:120px;">客户名称</th>
                <th style="width:100px;">业务类型</th>
                <th style="width:80px;">案件状态</th>
                <th style="width:120px;">申请号</th>
                <th style="width:100px;">申请日</th>
                <th style="width:100px;">申请类型</th>
            </tr>
        </thead>
        <tbody id="patent-list">
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

<style>
    /* 半选状态样式 */
    #select-all:indeterminate {
        background-color: #29b6b0;
        border-color: #29b6b0;
    }

    #select-all:indeterminate::before {
        content: '−';
        color: white;
        font-weight: bold;
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }
</style>

<script>
    (function() {
        var form = document.getElementById('search-form'),
            btnSearch = document.querySelector('.btn-search'),
            btnReset = document.querySelector('.btn-reset'),
            btnEdit = document.querySelector('.btn-edit'),
            btnSetUnallocated = document.querySelector('.btn-set-unallocated'),
            selectAllCheckbox = document.getElementById('select-all'),
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
            patentList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            selectedId = null;
            btnEdit.disabled = true;
            btnSetUnallocated.disabled = true;
            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);
            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') params.append(pair[0], pair[1]);
            }
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/patent_management/case_assignment/assigned.php';
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
                                bindCheckboxEvents();
                            } else {
                                patentList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            patentList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        patentList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            patentList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.onclick = function(e) {
                    // 如果点击的是复选框，不触发行选择
                    if (e.target.type === 'checkbox') return;

                    patentList.querySelectorAll('tr[data-id]').forEach(r => r.classList.remove('module-selected'));
                    this.classList.add('module-selected');
                    selectedId = this.getAttribute('data-id');
                    btnEdit.disabled = false;
                }
            });
        }

        function bindCheckboxEvents() {
            // 全选/取消全选
            selectAllCheckbox.onchange = function() {
                var checkboxes = patentList.querySelectorAll('.case-checkbox');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateUnallocatedButtonState();
            };

            // 单个复选框变化
            patentList.querySelectorAll('.case-checkbox').forEach(function(checkbox) {
                checkbox.onchange = function() {
                    updateSelectAllState();
                    updateUnallocatedButtonState();
                };
            });
        }

        function updateSelectAllState() {
            var checkboxes = patentList.querySelectorAll('.case-checkbox');
            var checkedCount = patentList.querySelectorAll('.case-checkbox:checked').length;

            if (checkedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === checkboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        function updateUnallocatedButtonState() {
            var checkedCount = patentList.querySelectorAll('.case-checkbox:checked').length;
            btnSetUnallocated.disabled = checkedCount === 0;
        }

        btnSetUnallocated.onclick = function() {
            var checkedBoxes = patentList.querySelectorAll('.case-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('请先选择要设置为未配案的案件');
                return;
            }

            var caseIds = Array.from(checkedBoxes).map(function(checkbox) {
                return checkbox.value;
            }).join(',');

            if (confirm('确定要将选中的 ' + checkedBoxes.length + ' 个案件设置为未配案吗？')) {
                var xhr = new XMLHttpRequest();
                var baseUrl = window.location.href.split('?')[0];
                var requestUrl = baseUrl.replace('index.php', '') + 'modules/patent_management/case_assignment/assigned.php';
                xhr.open('POST', requestUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    alert(response.msg);
                                    // 清除选择状态并重新加载数据
                                    selectAllCheckbox.checked = false;
                                    selectAllCheckbox.indeterminate = false;
                                    btnSetUnallocated.disabled = true;
                                    loadPatentData();
                                } else {
                                    alert('设置未配案失败：' + response.msg);
                                }
                            } catch (e) {
                                alert('设置未配案失败：服务器响应错误');
                            }
                        } else {
                            alert('设置未配案失败：网络错误');
                        }
                    }
                };
                xhr.send('action=set_unallocated&case_ids=' + encodeURIComponent(caseIds));
            }
        };

        btnEdit.onclick = function() {
            if (!selectedId) {
                alert('请先选择要修改的专利');
                return;
            }

            // 记录来源页面信息
            sessionStorage.setItem('patent_edit_source_module', '1');
            sessionStorage.setItem('patent_edit_source_menu', '2');
            sessionStorage.setItem('patent_edit_source_submenu', '1');

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
            // 重置复选框状态
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            btnSetUnallocated.disabled = true;
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
        // 通用搜索下拉框已通过render_select_search_assets()自动初始化



        loadPatentData();
    })();
</script>