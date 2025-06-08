<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 商标查询功能 - 商标管理/案件管理模块下的商标查询功能

// 统一选项声明
$options = [
    'case_types' => ['商标注册申请', '商标续展', '商标转让', '商标变更', '商标撤销', '商标异议', '商标复审', '商标无效', '马德里国际注册', '其他'],
    'case_statuses' => ['初审公告', '驳回', '结案', '实审', '初审', '受理', '领证', '不予核准', '撤回', '公告', '已递交', '不予受理', '处理中', '审理中', '提供使用证据', '部分散销', '未递交', '正据交换', '复审', '转让', '续展', '视为放弃', '客户放弃', '客户微回', '核准', '部分驳回', '撤三', '补正', '异议', '终止'],
    'application_modes' => ['电子申请', '纸本申请', '其他'],
    'client_statuses' => ['请选择', '放弃指示', '新申请指示递交', '补充申请信息资料', '修改意见', '著录项目变更指示', 'OA指示递交', '结案指示'],
    'business_types' => ['商标注册申请(电子)', '撤回商标注册申请(电子)', '变更名义地址申请(电子)', '撤回变更名义、地址申请(电子)', '变更代理文件接收人申请(电子)', '撤回变更代理文件接收人申请(电子)', '删减商品服务项目申请(电子)', '撤回删减申请(电子)', '商标转让转移申请(电子)', '撤回商标转让转移申请(电子)', '商标续展申请(电子)', '撤回商标续展申请(电子)', '商标注册申请(电子)', '撤回商标注册申请(电子)', '商标使用许可备案申请(电子)', '商标使用许可变更(电子)', '商标使用许可提前终止(电子)', '出具优先权证明申请(电子)', '补发商标注册证申请(电子)', '补发转让/变更/续展证明申请(电子)', '撤回取得连续三年不使用注册商标申请(纸件)', '取得连续三年不使用注册商标申请(纸件)', '商标异议申请(纸件)', '撤回商标异议申请(纸件)', '注册商标无效宣告复审申请(纸件)', '注册商标无效宣告申请(纸件)', '撤回商标评审申请(纸件)', '改正商标注册申请复审申请(纸件)', '撤销注册商标复审申请(纸件)', '商标不予注册复审申请(纸件)', '更正商标申请(电子)', '出具优先权证明文件申请(纸件)', '商标专用权质权登记主债权变更申请(纸件)', '商标专用权质权登记注销申请(纸件)', '商标异议申请(电子)', '商标专用权质权登记补发申请(纸件)', '商标专用权质权登记延期申请(纸件)', '商标专用权质权登记主债务申请(纸件)', '商标专用权质权登记补发延期申请(纸件)', '商标专用权质权登记注销申请(纸件)', '撤回取得成为商品服务通用名称注册商标申请(纸件)', '取得成为商品服务通用名称注册商标申请(纸件)', '特殊标志登记申请(纸件)', '商标评审案件答辩材料目录(纸件)', '商标评审案件证据目录(纸件)']
];

// 替换为与add_trademark.php一致的选项声明
// 业务类型
$business_types = ['商标注册申请(电子)', '撤回商标注册申请(电子)', '变更名义地址申请(电子)', '撤回变更名义、地址申请(电子)', '变更代理文件接收人申请(电子)', '撤回变更代理文件接收人申请(电子)', '删减商品服务项目申请(电子)', '撤回删减申请(电子)', '商标转让转移申请(电子)', '撤回商标转让转移申请(电子)', '商标续展申请(电子)', '撤回商标续展申请(电子)', '商标注册申请(电子)', '撤回商标注册申请(电子)', '商标使用许可备案申请(电子)', '商标使用许可变更(电子)', '商标使用许可提前终止(电子)', '出具优先权证明申请(电子)', '补发商标注册证申请(电子)', '补发转让/变更/续展证明申请(电子)', '撤回取得连续三年不使用注册商标申请(纸件)', '取得连续三年不使用注册商标申请(纸件)', '商标异议申请(纸件)', '撤回商标异议申请(纸件)', '注册商标无效宣告复审申请(纸件)', '注册商标无效宣告申请(纸件)', '撤回商标评审申请(纸件)', '改正商标注册申请复审申请(纸件)', '撤销注册商标复审申请(纸件)', '商标不予注册复审申请(纸件)', '更正商标申请(电子)', '出具优先权证明文件申请(纸件)', '商标专用权质权登记主债权变更申请(纸件)', '商标专用权质权登记注销申请(纸件)', '商标异议申请(电子)', '商标专用权质权登记补发申请(纸件)', '商标专用权质权登记延期申请(纸件)', '商标专用权质权登记主债务申请(纸件)', '商标专用权质权登记补发延期申请(纸件)', '商标专用权质权登记注销申请(纸件)', '撤回取得成为商品服务通用名称注册商标申请(纸件)', '取得成为商品服务通用名称注册商标申请(纸件)', '特殊标志登记申请(纸件)', '商标评审案件答辩材料目录(纸件)', '商标评审案件证据目录(纸件)'];
// 案件状态
$case_statuses = ['初审公告', '驳回', '结案', '实审', '初审', '受理', '领证', '不予核准', '撤回', '公告', '已递交', '不予受理', '处理中', '审理中', '提供使用证据', '部分散销', '未递交', '正据交换', '复审', '转让', '续展', '视为放弃', '客户放弃', '客户微回', '核准', '部分驳回', '撤三', '补正', '异议', '终止'];
// 处理事项
$process_items = ['请求优先审查', '开卷', '放弃', '更正', '无效答辩', '不予受理', '官文转达', '缴费', '民事诉讼上诉', '主动补正', '商标评价报告', '驳回', '取得检索报告', '请求无效', '翻译', '审查高速公路', '资助监控', '赔偿请求', '请求检索报告', '许可备案', '诉讼', '取得副本', '请求加速审查', '民事诉讼答辩', '取得申请号', '请求中止', '办理注册手续', '复审决定', '避免重复授权', '民事诉讼上诉答辩', '确认通知书', '请求保密审查', '结案', '补正', '请求恢复权利', '视为未提出', '手续补正', '取得证书', '费用滞纳金', '复审意见陈述', '提交证据', '复审受理', '请求延长期限', '撤回', '请求提前公告', '处理审查意见', '口审', '诉讼举证', '项目申报', '办理变更', '行政诉讼上诉答辩', '请求复审', '无效行政诉讼答辩', '请求退款', '提出行政诉讼', '缴费', '终止', '无效诉讼', '公众意见', '保密决定', '变更代理人申请补正通知', '请求实审', '提出民事诉讼', '请求副本', '新申请', '复议申请', '无效请求补充意见', '著录项目变更', '行政诉讼上诉', '请求费用减缓', '视为未要求'];
// 案件类型
$case_types = ['商标注册申请', '商标续展', '商标转让', '商标变更', '商标撤销', '商标异议', '商标复审', '商标无效', '马德里国际注册', '其他'];
// 申请方式
$application_modes = ['电子申请', '纸本申请', '其他'];
// 国家(地区)
$countries = ['中国', '美国', '日本', '韩国', '德国', '法国', '英国', '其他'];
// 案件流向
$case_flows = ['内-内', '内-外', '外-内', '外-外'];
// 客户状态
$client_statuses = ['请选择', '放弃指示', '新申请指示递交', '补充申请信息资料', '修改意见', '著录项目变更指示', 'OA指示递交', '结案指示'];
// 案源国
$source_countries = ['中国', '美国', '日本', '其他'];
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
        'business_type' => '=',
        'case_status' => '=',
        'trademark_class' => 'LIKE'
    ];

    foreach ($search_fields as $field => $op) {
        if (!empty($_GET[$field])) {
            $where[] = "$field " . ($op == 'LIKE' ? "LIKE :$field" : "= :$field");
            $params[$field] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
        }
    }

    // 特殊处理"是否主案"字段 - 将"是"/"否"转换为1/0
    if (!empty($_GET['is_main_case'])) {
        $is_main_case_value = ($_GET['is_main_case'] === '是') ? 1 : 0;
        $where[] = "is_main_case = :is_main_case";
        $params['is_main_case'] = $is_main_case_value;
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
            FROM trademark_case_info t" . $sql_where . " ORDER BY t.id DESC LIMIT :offset, :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $page_size, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    $trademarks = $stmt->fetchAll();
    $html = '';
    if (empty($trademarks)) {
        $html = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($trademarks as $index => $trademark) {
            $html .= '<tr data-id="' . $trademark['id'] . '">';
            $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($trademark['case_code'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['case_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['business_dept_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['client_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['business_type'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['case_status'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['application_no'] ?? '') . '</td>';
            $html .= '<td>' . ($trademark['application_date'] ? date('Y-m-d', strtotime($trademark['application_date'])) : '') . '</td>';
            // 商标类别按逗号换行显示，逗号说的是这个,换行是\n
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
        <button type="button" class="btn-add" onclick="window.parent.openTab ? window.parent.openTab(2, 0, null) : alert('框架导航功能不可用')"><i class="icon-add"></i> 新增商标</button>
        <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
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
                <td><?= render_dept_search('business_dept_id', $departments, '') ?></td>
                <td class="module-label">客户名称：</td>
                <td><?= render_customer_search('client_id', $customers, '') ?></td>
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
                <!-- 商标类别按逗号换行显示 -->
            </tr>
        </thead>
        <tbody id="trademark-list">
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
<script>
    (function() {
        var form = document.getElementById('search-form'),
            btnSearch = document.querySelector('.btn-search'),
            btnReset = document.querySelector('.btn-reset'),
            btnEdit = document.querySelector('.btn-edit'),
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

        function loadTrademarkData() {
            trademarkList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
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
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/trademark_search.php';
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
                            } else {
                                trademarkList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            trademarkList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        trademarkList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            trademarkList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.onclick = function() {
                    trademarkList.querySelectorAll('tr[data-id]').forEach(r => r.classList.remove('module-selected'));
                    this.classList.add('module-selected');
                    selectedId = this.getAttribute('data-id');
                    btnEdit.disabled = false;
                }
            });
        }
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
        loadTrademarkData();
    })();
</script>