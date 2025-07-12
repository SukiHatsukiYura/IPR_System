<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();

// 审核中功能 - 专利管理/核稿管理模块下的审核中功能



session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

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

// 处理更新核稿状态功能AJAX请求
if (isset($_POST['action']) && in_array($_POST['action'], ['set_draft', 'set_completed'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => '用户未登录']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $case_ids = $_POST['case_ids'] ?? '';
    $action = $_POST['action'];

    if (empty($case_ids)) {
        echo json_encode(['success' => false, 'msg' => '请选择要更新状态的案件']);
        exit;
    }

    // 确定要设置的状态
    $status_map = [
        'set_draft' => '草稿',
        'set_completed' => '已完成'
    ];

    $new_status = $status_map[$action];
    $selected_case_ids = explode(',', $case_ids);

    try {
        $success_count = 0;
        $pdo->beginTransaction();

        foreach ($selected_case_ids as $case_id) {
            $case_id = intval($case_id);
            if ($case_id <= 0) continue;

            // 更新核稿状态表中的状态（只更新审核中状态的记录）
            $update_stmt = $pdo->prepare("UPDATE patent_case_review_status SET review_status = ?, updated_at = NOW() WHERE patent_case_info_id = ? AND review_status = '审核中'");
            $result = $update_stmt->execute([$new_status, $case_id]);

            if ($result && $update_stmt->rowCount() > 0) {
                $success_count++;
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'msg' => "成功将 {$success_count} 个案件状态设置为：{$new_status}"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => '更新状态失败: ' . $e->getMessage()]);
    }
    exit;
}

// 处理获取案件核稿任务的AJAX请求
if (isset($_POST['action']) && $_POST['action'] == 'get_case_review_tasks') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => '用户未登录']);
        exit;
    }

    $case_id = intval($_POST['case_id'] ?? 0);
    if ($case_id <= 0) {
        echo json_encode(['success' => false, 'msg' => '案件ID无效']);
        exit;
    }

    try {
        // 查询案件的所有处理事项及其核稿状态和附件信息
        $sql = "SELECT 
                    t.id as task_id,
                    t.task_item,
                    u.real_name as reviewer_name,
                    r.review_comments,
                    r.id as review_id,
                    GROUP_CONCAT(
                        CONCAT(a.id, '|', a.file_name, '|', a.file_path) 
                        ORDER BY a.id 
                        SEPARATOR ';;'
                    ) as attachments
                FROM patent_case_task t
                LEFT JOIN patent_case_review_status r ON t.id = r.patent_case_task_id
                LEFT JOIN user u ON r.reviewer_id = u.id
                LEFT JOIN patent_task_attachment a ON t.id = a.task_id
                WHERE t.patent_case_info_id = ? AND r.review_status = '审核中'
                GROUP BY t.id, t.task_item, u.real_name, r.review_comments, r.id
                ORDER BY t.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$case_id]);
        $tasks = $stmt->fetchAll();

        echo json_encode(['success' => true, 'tasks' => $tasks]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '查询失败: ' . $e->getMessage()]);
    }
    exit;
}

// 处理保存核稿意见的AJAX请求
if (isset($_POST['action']) && $_POST['action'] == 'save_review_comments') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => '用户未登录']);
        exit;
    }

    $review_id = intval($_POST['review_id'] ?? 0);
    $comments = trim($_POST['comments'] ?? '');

    if ($review_id <= 0) {
        echo json_encode(['success' => false, 'msg' => '核稿记录ID无效']);
        exit;
    }

    try {
        $sql = "UPDATE patent_case_review_status SET review_comments = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$comments, $review_id]);

        if ($result) {
            echo json_encode(['success' => true, 'msg' => '核稿意见保存成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '保存失败']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '保存失败: ' . $e->getMessage()]);
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

    // 获取核稿状态为"审核中"的案件ID列表
    $under_review_stmt = $pdo->prepare("SELECT DISTINCT patent_case_info_id FROM patent_case_review_status WHERE review_status = '审核中'");
    $under_review_stmt->execute();
    $under_review_cases = $under_review_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($under_review_cases)) {
        // 没有审核中状态的案件
        echo json_encode([
            'success' => true,
            'html' => '<tr><td colspan="11" style="text-align:center;padding:20px 0;">暂无审核中状态的案件</td></tr>',
            'total_records' => 0,
            'total_pages' => 1,
            'current_page' => 1
        ]);
        exit;
    }

    // 添加审核中案件的筛选条件
    $placeholders = str_repeat('?,', count($under_review_cases) - 1) . '?';
    $where[] = "id IN ($placeholders)";
    $params = array_merge($params, $under_review_cases);

    // 合并其他查询条件
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
            $where[] = "$field " . ($op == 'LIKE' ? "LIKE ?" : "= ?");
            $params[] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
        }
    }

    // 特殊处理"是否配案"字段 - 将"是"/"否"转换为1/0
    if (!empty($_GET['is_allocated'])) {
        $is_allocated_value = ($_GET['is_allocated'] === '是') ? 1 : 0;
        $where[] = "is_allocated = ?";
        $params[] = $is_allocated_value;
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
            $html .= '<td>' . htmlspecialchars($patent['handler_name'] ?? '') . '</td>';
            $html .= '<td style="text-align:center;"><button type="button" class="btn-mini btn-review-tasks" data-case-id="' . $patent['id'] . '">核稿</button></td>';
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
    <div class="module-btns" style="display: flex; flex-direction: column; gap: 10px;">

        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
            <button type="button" class="btn-add" onclick="window.parent.openTab ? window.parent.openTab(1, 0, null) : alert('框架导航功能不可用')"><i class="icon-add"></i> 新增专利</button>
            <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
            <span style="color: #666; font-size: 14px; line-height: 28px;">状态设置：</span>
            <button type="button" class="btn-set-draft btn-mini" disabled style="background:#9e9e9e;color:#fff;border-color:#9e9e9e;">置为草稿</button>
            <button type="button" class="btn-set-completed btn-mini" disabled style="background:#2196f3;color:#fff;border-color:#2196f3;">置为已完成</button>
        </div>
    </div>
    <?php
    // 获取当前用户信息
    $current_user_stmt = $pdo->prepare("SELECT real_name FROM user WHERE id = ?");
    $current_user_stmt->execute([$_SESSION['user_id']]);
    $current_user = $current_user_stmt->fetch();
    $current_user_name = $current_user ? $current_user['real_name'] : '未知用户';
    ?>
    <?php render_info_notice("审核中管理（当前用户：" . $current_user_name . "）：只显示核稿状态为审核中的专利案件", 'success', 'icon-search'); ?>
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
                <th style="width:100px;">处理人</th>
                <th style="width:80px;text-align:center;">操作</th>
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

<!-- 核稿任务模态框 -->
<div class="module-modal" id="reviewTasksModal" style="display:none;">
    <div class="module-modal-content" style="width:800px;">
        <button class="module-modal-close" onclick="closeReviewTasksModal()">&times;</button>
        <h3 class="module-modal-title">案件核稿任务</h3>
        <div class="module-modal-body">
            <div id="reviewTasksContent">
                <div class="module-loading">加载中...</div>
            </div>
        </div>
        <div class="module-modal-footer">
            <button type="button" class="btn-theme" onclick="saveAllReviewComments()">保存全部</button>
            <button type="button" class="btn-cancel" onclick="closeReviewTasksModal()">关闭</button>
        </div>
    </div>
</div>

<script>
    (function() {
        var form = document.getElementById('search-form'),
            btnSearch = document.querySelector('.btn-search'),
            btnReset = document.querySelector('.btn-reset'),
            btnEdit = document.querySelector('.btn-edit'),
            btnSetDraft = document.querySelector('.btn-set-draft'),
            btnSetCompleted = document.querySelector('.btn-set-completed'),
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
            pageSizeSelect = document.getElementById('page-size-select'),
            selectAllCheckbox = document.getElementById('select-all');
        var currentPage = 1,
            pageSize = 10,
            totalPages = 1,
            selectedId = null;

        window.loadPatentData = function() {
            patentList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            selectedId = null;
            btnEdit.disabled = true;
            btnSetDraft.disabled = true;
            btnSetCompleted.disabled = true;
            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);
            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') params.append(pair[0], pair[1]);
            }
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/patent_management/review_management/under_review.php';
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
                updateFollowButtonState();
            };

            // 单个复选框变化
            patentList.querySelectorAll('.case-checkbox').forEach(function(checkbox) {
                checkbox.onchange = function() {
                    updateSelectAllState();
                    updateFollowButtonState();
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

        function updateFollowButtonState() {
            var checkedCount = patentList.querySelectorAll('.case-checkbox:checked').length;
            btnSetDraft.disabled = checkedCount === 0;
            btnSetCompleted.disabled = checkedCount === 0;
        }

        btnEdit.onclick = function() {
            if (!selectedId) {
                alert('请先选择要修改的专利');
                return;
            }
            // 记录来源页面信息
            sessionStorage.setItem('patent_edit_source_module', '1');
            sessionStorage.setItem('patent_edit_source_menu', '3');
            sessionStorage.setItem('patent_edit_source_submenu', '2');

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



        // 通用状态设置函数
        function setReviewStatus(action, statusName) {
            var checkedBoxes = patentList.querySelectorAll('.case-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('请先选择要设置状态的案件');
                return;
            }

            var caseIds = Array.from(checkedBoxes).map(function(checkbox) {
                return checkbox.value;
            }).join(',');

            if (confirm('确定要将选中的 ' + checkedBoxes.length + ' 个案件核稿状态设置为 "' + statusName + '" 吗？')) {
                var xhr = new XMLHttpRequest();
                var baseUrl = window.location.href.split('?')[0];
                var requestUrl = baseUrl.replace('index.php', '') + 'modules/patent_management/review_management/under_review.php';
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
                                    updateFollowButtonState();
                                    loadPatentData();
                                } else {
                                    alert('设置状态失败：' + response.msg);
                                }
                            } catch (e) {
                                alert('设置状态失败：服务器响应错误');
                            }
                        } else {
                            alert('设置状态失败：网络错误');
                        }
                    }
                };
                xhr.send('action=' + action + '&case_ids=' + encodeURIComponent(caseIds));
            }
        }

        // 置为草稿按钮事件
        btnSetDraft.onclick = function() {
            setReviewStatus('set_draft', '草稿');
        };

        // 置为已完成按钮事件  
        btnSetCompleted.onclick = function() {
            setReviewStatus('set_completed', '已完成');
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
            btnSetDraft.disabled = true;
            btnSetCompleted.disabled = true;
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

        document.querySelectorAll('.module-select-search-box').forEach(bindUserSearch);
        loadPatentData();
    })();

    // 获取下载文件名，确保包含正确的扩展名
    function getDownloadFileName(customName, originalName) {
        // 如果没有自定义文件名，使用原文件名
        if (!customName || customName.trim() === '') {
            return originalName || '未知文件';
        }

        // 如果没有原文件名，直接返回自定义文件名
        if (!originalName || originalName.trim() === '') {
            return customName;
        }

        // 获取原文件的扩展名
        var originalExt = '';
        var dotIndex = originalName.lastIndexOf('.');
        if (dotIndex > 0 && dotIndex < originalName.length - 1) {
            originalExt = originalName.substring(dotIndex);
        }

        // 检查自定义文件名是否已有扩展名
        var customDotIndex = customName.lastIndexOf('.');
        var hasCustomExt = customDotIndex > 0 && customDotIndex < customName.length - 1;

        // 如果自定义文件名没有扩展名，且原文件有扩展名，则补上扩展名
        if (!hasCustomExt && originalExt) {
            return customName + originalExt;
        }

        // 否则直接返回自定义文件名
        return customName;
    }

    // 打开核稿任务模态框
    function openReviewTasksModal(caseId) {
        document.getElementById('reviewTasksModal').style.display = 'flex';
        document.getElementById('reviewTasksContent').innerHTML = '<div class="module-loading">加载中...</div>';

        // 获取案件的核稿任务
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'modules/patent_management/review_management/under_review.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        renderReviewTasksTable(response.tasks);
                    } else {
                        document.getElementById('reviewTasksContent').innerHTML =
                            '<div class="module-error">加载失败: ' + response.msg + '</div>';
                    }
                } catch (e) {
                    document.getElementById('reviewTasksContent').innerHTML =
                        '<div class="module-error">数据解析失败</div>';
                }
            }
        };

        xhr.send('action=get_case_review_tasks&case_id=' + caseId);
    }

    // 渲染核稿任务表格
    function renderReviewTasksTable(tasks) {
        if (!tasks || tasks.length === 0) {
            document.getElementById('reviewTasksContent').innerHTML =
                '<div style="text-align:center;padding:20px;">暂无审核中的处理事项</div>';
            return;
        }

        var html = '<table class="module-table" style="width:100%;">';
        html += '<thead>';
        html += '<tr style="background:#f2f2f2;">';
        html += '<th style="width:180px;">处理事项</th>';
        html += '<th style="width:100px;">核稿人</th>';
        html += '<th style="width:150px;">相关文件</th>';
        html += '<th>核稿意见</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        tasks.forEach(function(task) {
            html += '<tr>';
            html += '<td>' + (task.task_item || '') + '</td>';
            html += '<td>' + (task.reviewer_name || '') + '</td>';

            // 处理相关文件列
            html += '<td>';
            if (task.attachments && task.attachments.trim() !== '') {
                var attachments = task.attachments.split(';;');
                attachments.forEach(function(attachmentStr, index) {
                    if (attachmentStr.trim() !== '') {
                        var parts = attachmentStr.split('|');
                        if (parts.length >= 3) {
                            var attachmentId = parts[0];
                            var fileName = parts[1];
                            var filePath = parts[2];
                            // 从文件路径中提取原始文件名
                            var originalFileName = filePath.split('/').pop();
                            var downloadName = getDownloadFileName(fileName, originalFileName);
                            if (index > 0) html += '<br>';
                            html += '<div style="display:flex;align-items:center;margin:2px 0;">';
                            html += '<span style="flex:1;font-size:12px;margin-right:5px;" title="' + fileName + '">' +
                                (fileName.length > 15 ? fileName.substring(0, 15) + '...' : fileName) + '</span>';
                            html += '<a href="' + filePath + '" download="' + downloadName + '" class="btn-mini" style="padding:2px 6px;font-size:11px;text-decoration:none;">下载</a>';
                            html += '</div>';
                        }
                    }
                });
            } else {
                html += '<span style="color:#999;font-size:12px;">无附件</span>';
            }
            html += '</td>';

            html += '<td>';
            html += '<textarea class="module-textarea review-comments" style="background:#fff;"';
            html += 'data-review-id="' + (task.review_id || '') + '" ';
            html += 'rows="3" style="width:100%;resize:vertical;">';
            html += (task.review_comments || '');
            html += '</textarea>';
            html += '</td>';
            html += '</tr>';
        });

        html += '</tbody>';
        html += '</table>';

        document.getElementById('reviewTasksContent').innerHTML = html;
    }

    // 关闭核稿任务模态框
    function closeReviewTasksModal() {
        document.getElementById('reviewTasksModal').style.display = 'none';
    }

    // 保存所有核稿意见
    function saveAllReviewComments() {
        var textareas = document.querySelectorAll('.review-comments');
        var savePromises = [];

        textareas.forEach(function(textarea) {
            var reviewId = textarea.getAttribute('data-review-id');
            var comments = textarea.value.trim();

            if (reviewId && reviewId !== '') {
                savePromises.push(saveReviewComment(reviewId, comments));
            }
        });

        if (savePromises.length === 0) {
            alert('没有需要保存的内容');
            return;
        }

        Promise.all(savePromises).then(function(results) {
            var allSuccess = results.every(function(result) {
                return result.success;
            });
            if (allSuccess) {
                alert('所有核稿意见保存成功');
                closeReviewTasksModal();
            } else {
                var failedCount = results.filter(function(result) {
                    return !result.success;
                }).length;
                alert('部分保存失败 (' + failedCount + '个)，请重试');
            }
        }).catch(function(error) {
            alert('保存过程中发生错误: ' + error.message);
        });
    }

    // 保存单个核稿意见
    function saveReviewComment(reviewId, comments) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/review_management/under_review.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (e) {
                            reject(new Error('数据解析失败'));
                        }
                    } else {
                        reject(new Error('网络请求失败'));
                    }
                }
            };

            xhr.send('action=save_review_comments&review_id=' + reviewId + '&comments=' + encodeURIComponent(comments));
        });
    }



    // 绑定核稿按钮事件
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-review-tasks')) {
            var caseId = e.target.getAttribute('data-case-id');
            if (caseId) {
                openReviewTasksModal(caseId);
            }
        }
    });
</script>

<style>
    /* 复选框样式优化 */
    .case-checkbox,
    #select-all {
        cursor: pointer;
        transform: scale(1.1);
    }

    /* 表格行悬停效果 */
    .module-table tbody tr:hover {
        background-color: #f5f5f5;
    }

    /* 选中行样式 */
    .module-table tbody tr.module-selected {
        background-color: #e3f2fd !important;
    }

    /* 全选复选框的半选状态样式 */
    #select-all:indeterminate {
        background-color: #29b6b0;
    }
</style>