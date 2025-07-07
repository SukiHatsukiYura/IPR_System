<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php'); // 引入通用函数库
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 已逾期功能 - 商标管理/个人案件模块下的已逾期功能

// 业务类型
$business_types = ['商标注册申请(电子)', '撤回商标注册申请(电子)', '变更名义地址申请(电子)', '撤回变更名义、地址申请(电子)', '变更代理文件接收人申请(电子)', '撤回变更代理文件接收人申请(电子)', '删减商品服务项目申请(电子)', '撤回删减申请(电子)', '商标转让转移申请(电子)', '撤回商标转让转移申请(电子)', '商标续展申请(电子)', '撤回商标续展申请(电子)', '商标注册申请(电子)', '撤回商标注册申请(电子)', '商标使用许可备案申请(电子)', '商标使用许可变更(电子)', '商标使用许可提前终止(电子)', '出具优先权证明申请(电子)', '补发商标注册证申请(电子)', '补发转让/变更/续展证明申请(电子)', '撤回取得连续三年不使用注册商标申请(纸件)', '取得连续三年不使用注册商标申请(纸件)', '商标异议申请(纸件)', '撤回商标异议申请(纸件)', '注册商标无效宣告复审申请(纸件)', '注册商标无效宣告申请(纸件)', '撤回商标评审申请(纸件)', '改正商标注册申请复审申请(纸件)', '撤销注册商标复审申请(纸件)', '商标不予注册复审申请(纸件)', '更正商标申请(电子)', '出具优先权证明文件申请(纸件)', '商标专用权质权登记主债权变更申请(纸件)', '商标专用权质权登记注销申请(纸件)', '商标异议申请(电子)', '商标专用权质权登记补发申请(纸件)', '商标专用权质权登记延期申请(纸件)', '商标专用权质权登记主债务申请(纸件)', '商标专用权质权登记补发延期申请(纸件)', '商标专用权质权登记注销申请(纸件)', '撤回取得成为商品服务通用名称注册商标申请(纸件)', '取得成为商品服务通用名称注册商标申请(纸件)', '特殊标志登记申请(纸件)', '商标评审案件答辩材料目录(纸件)', '商标评审案件证据目录(纸件)'];
// 案件状态
$case_statuses = ['初审公告', '驳回', '结案', '实审', '初审', '受理', '领证', '不予核准', '撤回', '公告', '已递交', '不予受理', '处理中', '审理中', '提供使用证据', '部分散销', '未递交', '正据交换', '复审', '转让', '续展', '视为放弃', '客户放弃', '客户微回', '核准', '部分驳回', '撤三', '补正', '异议', '终止'];
// 案件类型
$case_types = ['商标注册申请', '商标续展', '商标转让', '商标变更', '商标撤销', '商标异议', '商标复审', '商标无效', '马德里国际注册', '其他'];
// 申请方式
$application_modes = ['电子申请', '纸本申请', '其他'];
// 商标类别
$trademark_classes = ['1(化工原料)', '2(颜料油漆)', '3(日化用品)', '4(燃料油脂)', '5(医药)', '6(金属材料)', '7(机械设备)', '8(手工器械)', '9(科学仪器)', '10(医疗器材)', '11(灯具空调)', '12(运输工具)', '13(军火烟火)', '14(珠宝钟表)', '15(乐器)', '16(办公用品)', '17(橡胶制品)', '18(皮革皮具)', '19(建筑材料)', '20(家具)', '21(厨房洁具)', '22(绳网袋篷)', '23(纱线丝)', '24(布料床单)', '25(服装鞋帽)', '26(纽扣拉链)', '27(地毯席垫)', '28(健身器材)', '29(食品)', '30(方便食品)', '31(饲料种籽)', '32(啤酒饮料)', '33(酒)', '34(烟草烟具)', '35(广告销售)', '36(金融物管)', '37(建筑修理)', '38(通讯服务)', '39(运输贮藏)', '40(材料加工)', '41(教育娱乐)', '42(网站服务)', '43(餐饮住宿)', '44(医疗园艺)', '45(社会服务)'];

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

// 处理取消关注功能AJAX请求
if (isset($_POST['action']) && $_POST['action'] == 'remove_from_follow') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => '用户未登录']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $case_ids = $_POST['case_ids'] ?? '';

    if (empty($case_ids)) {
        echo json_encode(['success' => false, 'msg' => '请选择要取消关注的案件']);
        exit;
    }

    try {
        // 查询用户当前关注的案件
        $stmt = $pdo->prepare("SELECT followed_case_ids FROM user_trademark_follow WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current_follow = $stmt->fetch();

        if (!$current_follow || empty($current_follow['followed_case_ids'])) {
            echo json_encode(['success' => false, 'msg' => '您当前没有关注任何案件']);
            exit;
        }

        $remove_case_ids = explode(',', $case_ids);
        $existing_case_ids = explode(',', $current_follow['followed_case_ids']);

        // 移除指定的案件ID
        $remaining_case_ids = array_diff($existing_case_ids, $remove_case_ids);
        $remaining_case_ids = array_filter($remaining_case_ids); // 移除空值

        $followed_case_ids_str = implode(',', $remaining_case_ids);
        $follow_count = count($remaining_case_ids);

        if ($follow_count > 0) {
            // 更新记录
            $stmt = $pdo->prepare("UPDATE user_trademark_follow SET followed_case_ids = ?, follow_count = ?, last_follow_time = NOW() WHERE user_id = ?");
            $stmt->execute([$followed_case_ids_str, $follow_count, $user_id]);
        } else {
            // 如果没有关注的案件了，删除记录
            $stmt = $pdo->prepare("DELETE FROM user_trademark_follow WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }

        $removed_count = count($remove_case_ids);
        echo json_encode(['success' => true, 'msg' => "成功取消关注 {$removed_count} 个案件"]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '取消关注失败: ' . $e->getMessage()]);
    }
    exit;
}

// 处理设置案件状态功能AJAX请求（只处理进行中和已完成）
if (isset($_POST['action']) && in_array($_POST['action'], ['set_in_progress', 'set_completed'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => '用户未登录']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $case_ids = $_POST['case_ids'] ?? '';
    $action = $_POST['action'];

    if (empty($case_ids)) {
        echo json_encode(['success' => false, 'msg' => '请选择要设置状态的案件']);
        exit;
    }

    // 确定要设置的状态
    $status_map = [
        'set_in_progress' => '进行中',
        'set_completed' => '已完成'
    ];

    $new_status = $status_map[$action];

    try {
        // 获取用户的关注记录
        $follow_stmt = $pdo->prepare("SELECT id, followed_case_ids FROM user_trademark_follow WHERE user_id = ?");
        $follow_stmt->execute([$user_id]);
        $follow_data = $follow_stmt->fetch();

        if (!$follow_data || empty($follow_data['followed_case_ids'])) {
            echo json_encode(['success' => false, 'msg' => '您当前没有关注任何案件']);
            exit;
        }

        $user_trademark_follow_id = $follow_data['id'];
        $followed_case_ids = explode(',', $follow_data['followed_case_ids']);
        $selected_case_ids = explode(',', $case_ids);

        // 验证选中的案件是否都在关注列表中
        $invalid_cases = array_diff($selected_case_ids, $followed_case_ids);
        if (!empty($invalid_cases)) {
            echo json_encode(['success' => false, 'msg' => '包含未关注的案件，操作失败']);
            exit;
        }

        $success_count = 0;
        $pdo->beginTransaction();

        foreach ($selected_case_ids as $case_id) {
            $case_id = intval($case_id);
            if ($case_id <= 0) continue;

            // 检查状态记录是否存在
            $check_stmt = $pdo->prepare("SELECT id FROM user_trademark_follow_case_status WHERE user_trademark_follow_id = ? AND trademark_case_id = ?");
            $check_stmt->execute([$user_trademark_follow_id, $case_id]);
            $existing_status = $check_stmt->fetch();

            if ($existing_status) {
                // 更新现有记录
                $update_stmt = $pdo->prepare("UPDATE user_trademark_follow_case_status SET case_status = ?, status_update_time = NOW() WHERE id = ?");
                $update_stmt->execute([$new_status, $existing_status['id']]);
            } else {
                // 插入新记录
                $insert_stmt = $pdo->prepare("INSERT INTO user_trademark_follow_case_status (user_trademark_follow_id, user_id, trademark_case_id, case_status, status_update_time) VALUES (?, ?, ?, ?, NOW())");
                $insert_stmt->execute([$user_trademark_follow_id, $user_id, $case_id, $new_status]);
            }

            $success_count++;
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'msg' => "成功将 {$success_count} 个案件状态设置为：{$new_status}"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => '设置状态失败: ' . $e->getMessage()]);
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

    // 获取状态为"已逾期"的案件ID列表
    $user_id = $_SESSION['user_id'];
    $follow_info = $pdo->prepare("SELECT id FROM user_trademark_follow WHERE user_id = ?");
    $follow_info->execute([$user_id]);
    $follow_info_data = $follow_info->fetch();

    if ($follow_info_data) {
        // 直接查询状态为"已逾期"的案件ID
        $status_stmt = $pdo->prepare("SELECT trademark_case_id FROM user_trademark_follow_case_status WHERE user_trademark_follow_id = ? AND case_status = '已逾期'");
        $status_stmt->execute([$follow_info_data['id']]);
        $overdue_case_ids = $status_stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $overdue_case_ids = [];
    }

    if (empty($overdue_case_ids)) {
        echo json_encode([
            'success' => true,
            'html' => '<tr><td colspan="11" style="text-align:center;padding:20px 0;">暂无已逾期的案件</td></tr>',
            'total_records' => 0,
            'total_pages' => 1,
            'current_page' => 1
        ]);
        exit;
    }

    // 添加已逾期案件的筛选条件
    $placeholders = str_repeat('?,', count($overdue_case_ids) - 1) . '?';
    $where[] = "id IN ($placeholders)";
    $params = array_merge($params, $overdue_case_ids);

    // 合并其他查询条件
    $search_fields = [
        'case_code' => 'LIKE',
        'case_name' => 'LIKE',
        'application_no' => 'LIKE',
        'business_dept_id' => '=',
        'client_id' => '=',
        'business_type' => '=',
        'case_status' => '=',
        'trademark_class' => 'LIKE'
    ];

    foreach ($search_fields as $field => $op) {
        if (!empty($_GET[$field])) {
            $where[] = "$field " . ($op == 'LIKE' ? "LIKE ?" : "= ?");
            $params[] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
        }
    }

    // 特殊处理"是否主案"字段 - 将"是"/"否"转换为1/0
    if (!empty($_GET['is_main_case'])) {
        $is_main_case_value = ($_GET['is_main_case'] === '是') ? 1 : 0;
        $where[] = "is_main_case = ?";
        $params[] = $is_main_case_value;
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
    $count_sql = "SELECT COUNT(*) FROM trademark_case_info" . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);

    $sql = "SELECT t.*, 
            (SELECT dept_name FROM department WHERE id = t.business_dept_id) as business_dept_name,
            (SELECT customer_name_cn FROM customer WHERE id = t.client_id) as client_name,
            CASE 
                WHEN LENGTH(t.trademark_class) > 30 THEN CONCAT(LEFT(t.trademark_class, 30), '...')
                ELSE t.trademark_class
            END as trademark_class_display
            FROM trademark_case_info t" . $sql_where . " ORDER BY t.id DESC LIMIT ?, ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$offset, $page_size]));
    $trademarks = $stmt->fetchAll();

    $html = '';
    if (empty($trademarks)) {
        $html = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($trademarks as $index => $trademark) {
            $html .= '<tr data-id="' . $trademark['id'] . '">';
            $html .= '<td style="text-align:center;"><input type="checkbox" class="case-checkbox" value="' . $trademark['id'] . '"></td>';
            $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($trademark['case_code'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['case_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['business_dept_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['client_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['business_type'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['case_status'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['application_no'] ?? '') . '</td>';
            $html .= '<td>' . ($trademark['application_date'] ? date('Y-m-d', strtotime($trademark['application_date'])) : '') . '</td>';
            // 商标类别按逗号换行显示
            $trademark_class_display = htmlspecialchars($trademark['trademark_class_display'] ?? '');
            $trademark_class_display = str_replace(',', ',<br>', $trademark_class_display);
            $html .= '<td>' . $trademark_class_display . '</td>';
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
$trademark_classes_options = [];

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

foreach ($trademark_classes as $class) {
    $trademark_classes_options[$class] = $class;
}
// 引入搜索下拉框资源
render_select_search_assets();
?>
<div class="module-panel">
    <div class="module-btns" style="display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
            <button type="button" class="btn-add" onclick="window.parent.openTab ? window.parent.openTab(2, 0, null) : alert('框架导航功能不可用')"><i class="icon-add"></i> 新增商标</button>
            <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
            <button type="button" class="btn-remove-follow btn-mini" disabled><i class="icon-cancel"></i> 取消关注</button>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
            <span style="color: #666; font-size: 14px; line-height: 28px;">状态设置：</span>
            <button type="button" class="btn-set-in-progress btn-mini" disabled style="background:#4caf50;color:#fff;border-color:#4caf50;">置为进行中</button>
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
    <div style="background:#ffebee;padding:8px 12px;margin-bottom:10px;border-radius:4px;color:#c62828;font-size:14px;">
        <i class="icon-search"></i> 已逾期案件（当前用户：<?= htmlspecialchars($current_user_name) ?>）：只显示您关注的已逾期商标案件
    </div>
    <form id="search-form" class="module-form" autocomplete="off">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="page_size" value="10">
        <table class="module-table" style="margin-bottom:15px;">
            <tr>
                <td class="module-label">我方文号：</td>
                <td><input type="text" name="case_code" class="module-input"></td>
                <td class="module-label">商标名称：</td>
                <td><input type="text" name="case_name" class="module-input"></td>
                <td class="module-label">申请号：</td>
                <td><input type="text" name="application_no" class="module-input"></td>
            </tr>
            <tr>
                <td class="module-label">承办部门：</td>
                <td><?php render_select_search('business_dept_id', $departments_options, $_GET['business_dept_id'] ?? ''); ?></td>
                <td class="module-label">客户名称：</td>
                <td><?php render_select_search('client_id', $customers_options, $_GET['client_id'] ?? ''); ?></td>
                <td class="module-label">商标类别：</td>
                <td><select name="trademark_class" class="module-input">
                        <option value="">--全部--</option><?php foreach ($trademark_classes as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
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
                <td class="module-label">是否主案：</td>
                <td><select name="is_main_case" class="module-input">
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
                <th style="width:40px;text-align:center;"><input type="checkbox" id="select-all"></th>
                <th style="width:50px;text-align:center;">序号</th>
                <th style="width:100px;text-align:center;">我方文号</th>
                <th style="width:180px;">商标名称</th>
                <th style="width:100px;">承办部门</th>
                <th style="width:120px;">客户名称</th>
                <th style="width:100px;">业务类型</th>
                <th style="width:80px;">案件状态</th>
                <th style="width:120px;">申请号</th>
                <th style="width:100px;">申请日</th>
                <th style="width:100px;">商标类别</th>
            </tr>
        </thead>
        <tbody id="trademark-list">
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
            btnRemoveFollow = document.querySelector('.btn-remove-follow'),
            btnSetInProgress = document.querySelector('.btn-set-in-progress'),
            btnSetCompleted = document.querySelector('.btn-set-completed'),
            selectAllCheckbox = document.getElementById('select-all'),
            trademarkList = document.getElementById('trademark-list'),
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

        window.loadTrademarkData = function() {
            trademarkList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            selectedId = null;
            btnEdit.disabled = true;
            btnRemoveFollow.disabled = true;
            btnSetInProgress.disabled = true;
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
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/personal_cases/overdue.php';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                trademarkList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                                bindCheckboxEvents();
                            } else {
                                trademarkList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            trademarkList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        trademarkList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            trademarkList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.onclick = function(e) {
                    // 如果点击的是复选框，不触发行选择
                    if (e.target.type === 'checkbox') return;

                    trademarkList.querySelectorAll('tr[data-id]').forEach(r => r.classList.remove('module-selected'));
                    this.classList.add('module-selected');
                    selectedId = this.getAttribute('data-id');
                    btnEdit.disabled = false;
                }
            });
        }

        function bindCheckboxEvents() {
            // 全选/取消全选
            selectAllCheckbox.onchange = function() {
                var checkboxes = trademarkList.querySelectorAll('.case-checkbox');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateFollowButtonState();
            };

            // 单个复选框变化
            trademarkList.querySelectorAll('.case-checkbox').forEach(function(checkbox) {
                checkbox.onchange = function() {
                    updateSelectAllState();
                    updateFollowButtonState();
                };
            });
        }

        function updateSelectAllState() {
            var checkboxes = trademarkList.querySelectorAll('.case-checkbox');
            var checkedCount = trademarkList.querySelectorAll('.case-checkbox:checked').length;

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
            var checkedCount = trademarkList.querySelectorAll('.case-checkbox:checked').length;
            btnRemoveFollow.disabled = checkedCount === 0;
            btnSetInProgress.disabled = checkedCount === 0;
            btnSetCompleted.disabled = checkedCount === 0;
        }

        btnRemoveFollow.onclick = function() {
            var checkedBoxes = trademarkList.querySelectorAll('.case-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('请先选择要取消关注的案件');
                return;
            }

            var caseIds = Array.from(checkedBoxes).map(function(checkbox) {
                return checkbox.value;
            }).join(',');

            if (confirm('确定要取消关注选中的 ' + checkedBoxes.length + ' 个案件吗？')) {
                var xhr = new XMLHttpRequest();
                var baseUrl = window.location.href.split('?')[0];
                var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/personal_cases/overdue.php';
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
                                    btnRemoveFollow.disabled = true;
                                    btnSetInProgress.disabled = true;
                                    btnSetCompleted.disabled = true;
                                    loadTrademarkData();
                                } else {
                                    alert('取消关注失败：' + response.msg);
                                }
                            } catch (e) {
                                alert('取消关注失败：服务器响应错误');
                            }
                        } else {
                            alert('取消关注失败：网络错误');
                        }
                    }
                };
                xhr.send('action=remove_from_follow&case_ids=' + encodeURIComponent(caseIds));
            }
        };

        // 通用状态设置函数
        function setFollowStatus(action, statusName) {
            var checkedBoxes = trademarkList.querySelectorAll('.case-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('请先选择要设置状态的案件');
                return;
            }

            var caseIds = Array.from(checkedBoxes).map(function(checkbox) {
                return checkbox.value;
            }).join(',');

            if (confirm('确定要将选中的 ' + checkedBoxes.length + ' 个案件状态设置为 "' + statusName + '" 吗？')) {
                var xhr = new XMLHttpRequest();
                var baseUrl = window.location.href.split('?')[0];
                var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/personal_cases/overdue.php';
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
                                    loadTrademarkData();
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

        // 置为进行中按钮事件
        btnSetInProgress.onclick = function() {
            setFollowStatus('set_in_progress', '进行中');
        };

        // 置为已完成按钮事件
        btnSetCompleted.onclick = function() {
            setFollowStatus('set_completed', '已完成');
        };

        btnEdit.onclick = function() {
            if (!selectedId) {
                alert('请先选择要修改的商标');
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/trademark_management/case_management/set_edit_trademark.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    if (window.parent.openTab) {
                        // 商标管理模块索引为2，商标编辑菜单索引为4，subIndex必须为null
                        window.parent.openTab(2, 4, null);
                    } else {
                        alert('框架导航功能不可用');
                    }
                }
            };
            xhr.send('trademark_id=' + selectedId);
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
            loadTrademarkData();
        };
        btnReset.onclick = function() {
            form.reset();
            document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
            document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
            // 重置复选框状态
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            btnSetInProgress.disabled = true;
            btnSetCompleted.disabled = true;
            currentPage = 1;
            loadTrademarkData();
        };
        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            loadTrademarkData();
        };
        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    loadTrademarkData();
                }
            };
        });
        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            loadTrademarkData();
        };

        loadTrademarkData();
    })();
</script>