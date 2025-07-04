<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 部门案件功能 - 商标管理/个人案件模块下的部门案件功能

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

// 查询当前用户作为部门负责人的部门用于下拉
$current_user_id = $_SESSION['user_id'];
$dept_stmt = $pdo->prepare("SELECT id, dept_name FROM department WHERE is_active=1 AND leader_id = ? ORDER BY dept_name ASC");
$dept_stmt->execute([$current_user_id]);
$departments = $dept_stmt->fetchAll();

// 查询所有客户用于下拉
$customer_stmt = $pdo->prepare("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn ASC");
$customer_stmt->execute();
$customers = $customer_stmt->fetchAll();

// 处理图片导入相关AJAX请求
if (isset($_GET['ajax']) && $_GET['ajax'] == 'image_list') {
    header('Content-Type: application/json');
    try {
        $sql = "SELECT t.id, t.case_code, t.case_name, t.trademark_image_path,
                (SELECT customer_name_cn FROM customer WHERE id = t.client_id) as client_name
                FROM trademark_case_info t 
                ORDER BY t.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $trademarks = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $trademarks
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '查询失败：' . $e->getMessage()
        ]);
    }
    exit;
}

// 处理图片上传请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_image') {
    header('Content-Type: application/json');

    function handle_trademark_image_upload($trademark_id)
    {
        if (!isset($_FILES['trademark_image']) || $_FILES['trademark_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('图片上传失败');
        }

        $file = $_FILES['trademark_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('只支持JPG、PNG、GIF格式的图片');
        }

        // 创建上传目录
        $upload_dir = __DIR__ . '/../../../uploads/trademark_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // 生成唯一文件名
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'trademark_' . $trademark_id . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('图片保存失败');
        }

        return [
            'path' => 'uploads/trademark_images/' . $filename,
            'name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type']
        ];
    }

    try {
        $trademark_id = intval($_POST['trademark_id'] ?? 0);
        if ($trademark_id <= 0) {
            throw new Exception('商标ID无效');
        }

        // 检查商标是否存在
        $check_stmt = $pdo->prepare("SELECT id FROM trademark_case_info WHERE id = ?");
        $check_stmt->execute([$trademark_id]);
        if (!$check_stmt->fetch()) {
            throw new Exception('商标案件不存在');
        }

        // 处理图片上传
        $image_info = handle_trademark_image_upload($trademark_id);

        // 更新数据库
        $update_sql = "UPDATE trademark_case_info SET 
                      trademark_image_path = ?, 
                      trademark_image_name = ?, 
                      trademark_image_size = ?, 
                      trademark_image_type = ?,
                      updated_at = NOW()
                      WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $result = $update_stmt->execute([
            $image_info['path'],
            $image_info['name'],
            $image_info['size'],
            $image_info['type'],
            $trademark_id
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => '图片上传成功']);
        } else {
            throw new Exception('数据库更新失败');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 处理图片删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_image') {
    header('Content-Type: application/json');

    try {
        $trademark_id = intval($_POST['trademark_id'] ?? 0);
        if ($trademark_id <= 0) {
            throw new Exception('商标ID无效');
        }

        // 获取当前图片信息
        $check_stmt = $pdo->prepare("SELECT id, trademark_image_path FROM trademark_case_info WHERE id = ?");
        $check_stmt->execute([$trademark_id]);
        $trademark = $check_stmt->fetch();

        if (!$trademark) {
            throw new Exception('商标案件不存在');
        }

        // 删除物理文件
        if (!empty($trademark['trademark_image_path'])) {
            $file_path = __DIR__ . '/../../../' . $trademark['trademark_image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // 清空数据库中的图片信息
        $update_sql = "UPDATE trademark_case_info SET 
                      trademark_image_path = NULL, 
                      trademark_image_name = NULL, 
                      trademark_image_size = NULL, 
                      trademark_image_type = NULL,
                      updated_at = NOW()
                      WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $result = $update_stmt->execute([$trademark_id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => '图片删除成功']);
        } else {
            throw new Exception('数据库更新失败');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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

    // 构建查询条件
    if (!empty($_GET['case_code'])) {
        $where[] = 'case_code LIKE ?';
        $params[] = '%' . $_GET['case_code'] . '%';
    }
    if (!empty($_GET['case_name'])) {
        $where[] = 'case_name LIKE ?';
        $params[] = '%' . $_GET['case_name'] . '%';
    }
    if (!empty($_GET['application_no'])) {
        $where[] = 'application_no LIKE ?';
        $params[] = '%' . $_GET['application_no'] . '%';
    }
    if (!empty($_GET['business_dept_id'])) {
        $where[] = 'business_dept_id = ?';
        $params[] = $_GET['business_dept_id'];
    }
    if (!empty($_GET['client_id'])) {
        $where[] = 'client_id = ?';
        $params[] = $_GET['client_id'];
    }
    if (!empty($_GET['business_type'])) {
        $where[] = 'business_type = ?';
        $params[] = $_GET['business_type'];
    }
    if (!empty($_GET['case_status'])) {
        $where[] = 'case_status = ?';
        $params[] = $_GET['case_status'];
    }
    if (!empty($_GET['trademark_class'])) {
        $where[] = 'trademark_class LIKE ?';
        $params[] = '%' . $_GET['trademark_class'] . '%';
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

    // 部门案件筛选：只显示当前登录用户作为部门负责人的部门的案件
    $current_user_id = $_SESSION['user_id'];

    // 先查询当前用户是哪些部门的负责人
    $user_dept_stmt = $pdo->prepare("SELECT id, dept_name FROM department WHERE leader_id = ? AND is_active = 1");
    $user_dept_stmt->execute([$current_user_id]);
    $user_departments = $user_dept_stmt->fetchAll();

    // 调试信息
    error_log("Debug - 当前用户ID: " . $current_user_id);
    error_log("Debug - 当前用户负责的部门: " . json_encode($user_departments));

    if (empty($user_departments)) {
        // 如果当前用户不是任何部门的负责人，则不显示任何案件
        $where[] = "1 = 0"; // 这会让查询返回空结果
        error_log("Debug - 当前用户不是任何部门的负责人，不显示任何案件");
    } else {
        // 只显示当前用户作为负责人的部门的案件
        $dept_ids = array_column($user_departments, 'id');
        $placeholders = str_repeat('?,', count($dept_ids) - 1) . '?';
        $where[] = "business_dept_id IN ($placeholders)";
        $params = array_merge($params, $dept_ids);
        error_log("Debug - 筛选部门ID: " . implode(',', $dept_ids));
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
    // 合并所有参数：搜索参数 + 部门筛选参数 + 分页参数
    $final_params = $params;
    $final_params[] = $offset;
    $final_params[] = $page_size;

    // 调试信息
    error_log("Debug - 最终SQL: " . $sql);
    error_log("Debug - 最终参数: " . json_encode($final_params));

    $stmt->execute($final_params);
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
    <div class="module-btns" style="display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
            <button type="button" class="btn-add" onclick="window.parent.openTab ? window.parent.openTab(2, 0, null) : alert('框架导航功能不可用')"><i class="icon-add"></i> 新增商标</button>
            <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-download-template"><i class="icon-save"></i> 下载模板</button>
            <button type="button" class="btn-batch-import"><i class="icon-add"></i> 批量导入</button>
            <button type="button" class="btn-download-current"><i class="icon-list"></i> 下载当前案件信息</button>
            <button type="button" class="btn-batch-update"><i class="icon-edit"></i> 批量修改</button>
            <button type="button" class="btn-image-import"><i class="icon-add"></i> 图片导入</button>
        </div>
    </div>
    <form id="search-form" class="module-form" autocomplete="off">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="page_size" value="10">
        <?php
        // 获取当前用户信息
        $current_user_stmt = $pdo->prepare("SELECT real_name FROM user WHERE id = ?");
        $current_user_stmt->execute([$_SESSION['user_id']]);
        $current_user = $current_user_stmt->fetch();
        $current_user_name = $current_user ? $current_user['real_name'] : '未知用户';
        ?>
        <div style="background:#e8f5e8;padding:8px 12px;margin-bottom:10px;border-radius:4px;color:#2e7d32;font-size:14px;">
            部门案件查询（当前用户：<?php echo htmlspecialchars($current_user_name); ?>）：只显示您作为对应部门的项目负责人的商标案件
        </div>
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
                <td class="module-label">选择部门：</td>
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

<!-- 批量导入模态框 -->
<div id="batch-import-modal" class="module-modal" style="display:none;">
    <div class="module-modal-content" style="width:600px;">
        <div class="module-modal-header">
            <h3 class="module-modal-title">批量导入商标案件</h3>
            <button class="module-modal-close">&times;</button>
        </div>
        <div class="module-modal-body" style="padding:20px;">
            <div style="margin-bottom:20px;">
                <h4>导入说明：</h4>
                <ul style="margin:10px 0;padding-left:20px;color:#666;">
                    <li>请先下载Excel模板文件，使用模板文件填写数据，然后上传文件进行导入</li>
                    <li>必填字段：商标名称、承办部门ID、客户ID/客户名称</li>
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

<!-- 批量修改模态框 -->
<div id="batch-update-modal" class="module-modal" style="display:none;">
    <div class="module-modal-content" style="width:600px;">
        <div class="module-modal-header">
            <h3 class="module-modal-title">批量修改商标案件</h3>
            <button class="module-modal-close">&times;</button>
        </div>
        <div class="module-modal-body" style="padding:20px;">
            <div style="margin-bottom:20px;">
                <h4>修改说明：</h4>
                <ul style="margin:10px 0;padding-left:20px;color:#666;">
                    <li>请先使用"下载当前案件信息"功能获取现有案件数据</li>
                    <li>在Excel文件中修改需要更新的字段，保持id列不变</li>
                    <li>灰色表头的id字段禁止修改，用于定位要更新的案件</li>
                    <li>必填字段：商标名称、承办部门ID、客户ID/客户名称</li>
                    <li>日期格式：YYYY-MM-DD（如：2025-01-01）</li>
                    <li>支持的文件格式：.xlsx, .xls, .csv</li>
                    <li>最大文件大小：10MB</li>
                </ul>
            </div>
            <form id="update-form" enctype="multipart/form-data">
                <table class="module-table">
                    <tr>
                        <td class="module-label module-req">*选择文件</td>
                        <td>
                            <input type="file" name="update_file" id="update-file" accept=".xlsx,.xls,.csv" class="module-input" required>
                        </td>
                    </tr>
                </table>
            </form>
            <div id="update-progress" style="display:none;margin-top:20px;">
                <div style="background:#f0f0f0;border-radius:10px;overflow:hidden;">
                    <div id="update-progress-bar" style="height:20px;background:#29b6b0;width:0%;transition:width 0.3s;"></div>
                </div>
                <div id="update-progress-text" style="text-align:center;margin-top:10px;">准备修改...</div>
            </div>
            <div id="update-result" style="display:none;margin-top:20px;"></div>
        </div>
        <div class="module-modal-footer">
            <button type="button" class="btn-theme" id="btn-start-update">开始修改</button>
            <button type="button" class="btn-cancel" id="btn-cancel-update">取消</button>
        </div>
    </div>
</div>

<!-- 图片导入模态框 -->
<div id="image-import-modal" class="module-modal" style="display:none;">
    <div class="module-modal-content" style="width:90%;max-width:1200px;max-height:80vh;">
        <div class="module-modal-header">
            <h3 class="module-modal-title">商标图片批量导入</h3>
            <button class="module-modal-close">&times;</button>
        </div>
        <div class="module-modal-body" style="padding:20px;overflow-y:auto;">
            <div style="margin-bottom:20px;">
                <h4>导入说明：</h4>
                <ul style="margin:10px 0;padding-left:20px;color:#666;">
                    <li>为每个商标案件选择对应的图片文件</li>
                    <li>支持的图片格式：JPG、PNG、GIF</li>
                    <li>图片会自动压缩以提高上传速度</li>
                    <li>支持<strong style="color:#29b6b0;">拖拽上传</strong>：可以直接将图片拖拽到预览区域</li>
                    <li>支持<strong style="color:#29b6b0;">点击上传</strong>：点击"选择图片"按钮或预览区域选择文件</li>
                    <li>可以同时为多个案件上传图片</li>
                    <li>点击预览图可以查看大图</li>
                </ul>
            </div>
            <div id="trademark-image-list">
                <div style="text-align:center;padding:20px;color:#666;">正在加载商标案件列表...</div>
            </div>
            <div id="image-upload-progress" style="display:none;margin-top:20px;">
                <div style="background:#f0f0f0;border-radius:10px;overflow:hidden;">
                    <div id="image-progress-bar" style="height:20px;background:#29b6b0;width:0%;transition:width 0.3s;"></div>
                </div>
                <div id="image-progress-text" style="text-align:center;margin-top:10px;">准备上传...</div>
            </div>
        </div>
        <div class="module-modal-footer" style="display:flex;justify-content:space-between;align-items:center;">
            <!-- 左侧：批量删除按钮 -->
            <div>
                <button type="button" class="btn-cancel" id="btn-batch-delete-images" style="background:#f44336;border-color:#f44336;" disabled>批量删除选中图片</button>
            </div>
            <!-- 右侧：保存和取消按钮 -->
            <div>
                <button type="button" class="btn-theme" id="btn-save-images">保存所有图片</button>
                <button type="button" class="btn-cancel" id="btn-cancel-image-import">取消</button>
            </div>
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
            btnDownloadCurrent = document.querySelector('.btn-download-current'),
            btnBatchImport = document.querySelector('.btn-batch-import'),
            btnBatchUpdate = document.querySelector('.btn-batch-update'),
            btnImageImport = document.querySelector('.btn-image-import'),
            batchImportModal = document.getElementById('batch-import-modal'),
            batchUpdateModal = document.getElementById('batch-update-modal'),
            imageImportModal = document.getElementById('image-import-modal'),
            btnStartImport = document.getElementById('btn-start-import'),
            btnCancelImport = document.getElementById('btn-cancel-import'),
            btnStartUpdate = document.getElementById('btn-start-update'),
            btnCancelUpdate = document.getElementById('btn-cancel-update'),
            btnSaveImages = document.getElementById('btn-save-images'),
            btnBatchDeleteImages = document.getElementById('btn-batch-delete-images'),
            btnCancelImageImport = document.getElementById('btn-cancel-image-import'),
            modalClose = batchImportModal.querySelector('.module-modal-close'),
            updateModalClose = batchUpdateModal.querySelector('.module-modal-close'),
            imageModalClose = imageImportModal.querySelector('.module-modal-close'),
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
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/personal_cases/department_cases.php';

            // 调试信息
            console.log('部门案件查询 - 请求URL:', requestUrl + '?' + params.toString());

            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            console.log('部门案件查询 - 响应数据:', response);
                            if (response.success) {
                                trademarkList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                                console.log('部门案件查询 - 数据更新完成，共', response.total_records, '条记录');
                            } else {
                                console.error('部门案件查询 - 请求失败:', response.message || '未知错误');
                                trademarkList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            console.error('部门案件查询 - 解析响应失败:', e);
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

        // 下载模板按钮事件
        btnDownloadTemplate.onclick = function() {
            var baseUrl = window.location.href.split('?')[0];
            var downloadUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/download_template.php';
            window.open(downloadUrl, '_blank');
        };

        // 下载当前案件信息按钮事件
        btnDownloadCurrent.onclick = function() {
            var formData = new FormData(form),
                params = new URLSearchParams();

            // 添加当前搜索条件
            for (var pair of formData.entries()) {
                if (pair[1] && pair[1].trim() !== '') {
                    params.append(pair[0], pair[1]);
                }
            }

            var baseUrl = window.location.href.split('?')[0];
            var downloadUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/download_current_cases.php';

            if (params.toString()) {
                downloadUrl += '?' + params.toString();
            }

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

        // 批量修改按钮事件
        btnBatchUpdate.onclick = function() {
            batchUpdateModal.style.display = 'flex';
            // 重置表单
            document.getElementById('update-form').reset();
            document.getElementById('update-progress').style.display = 'none';
            document.getElementById('update-result').style.display = 'none';
            btnStartUpdate.disabled = false;
            btnStartUpdate.textContent = '开始修改';
        };

        // 图片导入按钮事件
        btnImageImport.onclick = function() {
            imageImportModal.style.display = 'flex';
            loadTrademarkImageList();
        };

        // 关闭模态框
        window.closeBatchImportModal = function() {
            batchImportModal.style.display = 'none';
        };
        window.closeBatchUpdateModal = function() {
            batchUpdateModal.style.display = 'none';
        };
        window.closeImageImportModal = function() {
            imageImportModal.style.display = 'none';
        };
        btnCancelImport.onclick = closeBatchImportModal;
        btnCancelUpdate.onclick = closeBatchUpdateModal;
        btnCancelImageImport.onclick = closeImageImportModal;
        modalClose.onclick = closeBatchImportModal;
        updateModalClose.onclick = closeBatchUpdateModal;
        imageModalClose.onclick = closeImageImportModal;

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
            xhr.open('POST', 'modules/trademark_management/case_management/batch_import.php', true);

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
                            var resultHtml = '<div style="color:#388e3c;"><strong>导入完成！</strong><br>' +
                                '成功导入：' + response.success_count + ' 条<br>';

                            if (response.error_count > 0) {
                                resultHtml += '<span style="color:#f44336;">导入失败：' + response.error_count + ' 条</span><br>';
                            }

                            if (response.errors && response.errors.length > 0) {
                                resultHtml += '<br><details style="margin-top:10px;"><summary style="cursor:pointer;color:#f44336;">查看错误详情</summary>' +
                                    '<div style="margin-top:5px;color:#f44336;font-size:12px;">' + response.errors.join('<br>') + '</div></details>';
                            }

                            resultHtml += '<br><br><button class="btn-theme" onclick="loadTrademarkData(); closeBatchImportModal();">刷新列表并关闭</button></div>';
                            resultDiv.innerHTML = resultHtml;
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

        // 开始修改按钮事件
        btnStartUpdate.onclick = function() {
            var fileInput = document.getElementById('update-file');
            var file = fileInput.files[0];

            if (!file) {
                alert('请选择要修改的Excel文件');
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
            document.getElementById('update-progress').style.display = 'block';
            document.getElementById('update-result').style.display = 'none';
            btnStartUpdate.disabled = true;
            btnStartUpdate.textContent = '修改中...';

            // 准备表单数据
            var formData = new FormData(document.getElementById('update-form'));
            formData.append('action', 'batch_update');

            // 发送请求
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/trademark_management/case_management/batch_update.php', true);

            // 监听上传进度
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percentComplete = (e.loaded / e.total) * 100;
                    document.getElementById('update-progress-bar').style.width = percentComplete + '%';
                    document.getElementById('update-progress-text').textContent = '上传中... ' + Math.round(percentComplete) + '%';
                }
            };

            xhr.onload = function() {
                btnStartUpdate.disabled = false;
                btnStartUpdate.textContent = '开始修改';

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        document.getElementById('update-progress-bar').style.width = '100%';
                        document.getElementById('update-progress-text').textContent = '修改完成';

                        // 显示结果
                        var resultDiv = document.getElementById('update-result');
                        resultDiv.style.display = 'block';

                        if (response.success) {
                            var resultHtml = '<div style="color:#388e3c;"><strong>修改完成！</strong><br>' +
                                '处理案件总数：' + (response.processed_count + response.error_count) + ' 个<br>' +
                                '实际更新：' + response.success_count + ' 个案件<br>';

                            if (response.no_change_count > 0) {
                                resultHtml += '无需更新：' + response.no_change_count + ' 个案件<br>';
                            }

                            if (response.error_count > 0) {
                                resultHtml += '<span style="color:#f44336;">处理失败：' + response.error_count + ' 个案件</span><br>';
                            }

                            if (response.performance_info) {
                                resultHtml += '<br><small style="color:#666;">' + response.performance_info + '</small><br>';
                            }

                            if (response.errors && response.errors.length > 0) {
                                resultHtml += '<br><details style="margin-top:10px;"><summary style="cursor:pointer;color:#f44336;">查看错误详情</summary>' +
                                    '<div style="margin-top:5px;color:#f44336;font-size:12px;">' + response.errors.join('<br>') + '</div></details>';
                            }

                            resultHtml += '<br><br><button class="btn-theme" onclick="loadTrademarkData(); closeBatchUpdateModal();">刷新列表并关闭</button></div>';
                            resultDiv.innerHTML = resultHtml;
                        } else {
                            resultDiv.innerHTML = '<div style="color:#f44336;"><strong>修改失败：</strong><br>' +
                                (response.message || '未知错误') +
                                '<br><br><button class="btn-cancel" onclick="closeBatchUpdateModal();">关闭</button>' +
                                '</div>';
                        }
                    } catch (e) {
                        document.getElementById('update-result').innerHTML = '<div style="color:#f44336;">修改失败：服务器响应错误</div>';
                        document.getElementById('update-result').style.display = 'block';
                    }
                } else {
                    document.getElementById('update-result').innerHTML = '<div style="color:#f44336;">修改失败：网络错误</div>';
                    document.getElementById('update-result').style.display = 'block';
                }
            };

            xhr.onerror = function() {
                btnStartUpdate.disabled = false;
                btnStartUpdate.textContent = '开始修改';
                document.getElementById('update-result').innerHTML = '<div style="color:#f44336;">修改失败：网络连接错误</div>';
                document.getElementById('update-result').style.display = 'block';
            };

            xhr.send(formData);
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

        // 图片导入相关函数
        function loadTrademarkImageList() {
            var imageListDiv = document.getElementById('trademark-image-list');
            imageListDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#666;">正在加载商标案件列表...</div>';

            var xhr = new XMLHttpRequest();
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/personal_cases/department_cases.php?ajax=image_list';
            xhr.open('GET', requestUrl, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                renderTrademarkImageList(response.data);
                            } else {
                                imageListDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#f44336;">加载失败：' + (response.message || '未知错误') + '</div>';
                            }
                        } catch (e) {
                            imageListDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#f44336;">加载失败：数据格式错误</div>';
                        }
                    } else {
                        imageListDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#f44336;">加载失败：网络错误</div>';
                    }
                }
            };
            xhr.send();
        }

        function renderTrademarkImageList(trademarks) {
            var imageListDiv = document.getElementById('trademark-image-list');
            if (!trademarks || trademarks.length === 0) {
                imageListDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#666;">暂无商标案件</div>';
                return;
            }

            var html = '<table class="module-table" style="width:100%;">';
            html += '<thead><tr style="background:#f2f2f2;">';
            html += '<th style="width:50px;text-align:center;"><input type="checkbox" id="select-all-images" title="全选/取消全选" style="width:18px;height:18px;cursor:pointer;"> 全选</th>';
            html += '<th style="width:100px;">我方文号</th>';
            html += '<th style="width:150px;">商标名称</th>';
            html += '<th style="width:100px;">客户名称</th>';
            html += '<th style="width:120px;">当前图片</th>';
            html += '<th style="width:150px;">上传新图片</th>';
            html += '<th style="width:120px;">预览</th>';
            html += '</tr></thead><tbody>';

            trademarks.forEach(function(trademark) {
                html += '<tr data-id="' + trademark.id + '">';

                // 勾选框列 - 只有已有图片的案件才显示勾选框
                html += '<td style="text-align:center;">';
                if (trademark.trademark_image_path) {
                    html += '<input type="checkbox" class="image-checkbox" value="' + trademark.id + '" data-case-code="' + (trademark.case_code || '') + '" style="width:18px;height:18px;cursor:pointer;">';
                } else {
                    html += '<span style="color:#ccc;">-</span>';
                }
                html += '</td>';

                html += '<td>' + (trademark.case_code || '') + '</td>';
                html += '<td>' + (trademark.case_name || '') + '</td>';
                html += '<td>' + (trademark.client_name || '') + '</td>';

                // 当前图片
                html += '<td style="text-align:center;">';
                if (trademark.trademark_image_path) {
                    html += '<img src="' + trademark.trademark_image_path + '" style="width:60px;height:60px;object-fit:contain;border:1px solid #ddd;border-radius:4px;cursor:pointer;" onclick="showImagePreview(\'' + trademark.trademark_image_path + '\')">';
                } else {
                    html += '<span style="color:#999;">暂无图片</span>';
                }
                html += '</td>';

                // 上传新图片
                html += '<td>';
                html += '<div style="display:flex;align-items:center;gap:10px;">';
                html += '<input type="file" id="file-' + trademark.id + '" accept="image/*" style="display:none;" onchange="handleImageSelect(' + trademark.id + ', this)">';
                html += '<button type="button" class="btn-mini" onclick="document.getElementById(\'file-' + trademark.id + '\').click()">选择图片</button>';
                html += '<span id="filename-' + trademark.id + '" style="font-size:12px;color:#666;"></span>';
                html += '</div>';
                html += '</td>';

                // 预览
                html += '<td style="text-align:center;">';
                html += '<div id="preview-' + trademark.id + '" class="image-drop-zone" data-trademark-id="' + trademark.id + '" style="width:60px;height:60px;border:2px dashed #ddd;display:flex;align-items:center;justify-content:center;background:#f9f9f9;border-radius:4px;margin:0 auto;cursor:pointer;transition:all 0.3s;" title="点击或拖拽图片到此处">';
                html += '<span style="color:#999;font-size:12px;">拖拽或点击</span>';
                html += '</div>';
                html += '</td>';

                html += '</tr>';
            });

            html += '</tbody></table>';
            imageListDiv.innerHTML = html;

            // 绑定拖拽事件
            bindDragDropEvents();

            // 绑定勾选框事件
            bindCheckboxEvents();
        }

        // 绑定勾选框事件
        function bindCheckboxEvents() {
            var selectAllCheckbox = document.getElementById('select-all-images');
            var imageCheckboxes = document.querySelectorAll('.image-checkbox');

            // 全选/取消全选功能
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    var isChecked = this.checked;
                    imageCheckboxes.forEach(function(checkbox) {
                        checkbox.checked = isChecked;
                    });
                    updateBatchDeleteButton();
                });
            }

            // 单个勾选框变化
            imageCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    // 更新全选框状态
                    var checkedCount = document.querySelectorAll('.image-checkbox:checked').length;
                    var totalCount = imageCheckboxes.length;

                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = checkedCount === totalCount && totalCount > 0;
                        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < totalCount;
                    }

                    updateBatchDeleteButton();
                });
            });
        }

        // 更新批量删除按钮状态
        function updateBatchDeleteButton() {
            var checkedBoxes = document.querySelectorAll('.image-checkbox:checked');
            if (btnBatchDeleteImages) {
                btnBatchDeleteImages.disabled = checkedBoxes.length === 0;
                btnBatchDeleteImages.textContent = checkedBoxes.length > 0 ?
                    '批量删除选中图片 (' + checkedBoxes.length + ')' : '批量删除选中图片';
            }
        }

        // 绑定拖拽事件
        function bindDragDropEvents() {
            var dropZones = document.querySelectorAll('.image-drop-zone');

            dropZones.forEach(function(dropZone) {
                var trademarkId = dropZone.getAttribute('data-trademark-id');

                // 点击事件 - 触发文件选择
                dropZone.addEventListener('click', function() {
                    var fileInput = document.getElementById('file-' + trademarkId);
                    if (fileInput) {
                        fileInput.click();
                    }
                });

                // 拖拽进入
                dropZone.addEventListener('dragenter', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.style.borderColor = '#29b6b0';
                    this.style.backgroundColor = '#e8f5f4';
                    this.style.transform = 'scale(1.05)';
                });

                // 拖拽悬停
                dropZone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.style.borderColor = '#29b6b0';
                    this.style.backgroundColor = '#e8f5f4';
                });

                // 拖拽离开
                dropZone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // 检查是否真的离开了区域（防止子元素触发）
                    if (!this.contains(e.relatedTarget)) {
                        this.style.borderColor = '#ddd';
                        this.style.backgroundColor = '#f9f9f9';
                        this.style.transform = 'scale(1)';
                    }
                });

                // 文件放置
                dropZone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // 恢复样式
                    this.style.borderColor = '#ddd';
                    this.style.backgroundColor = '#f9f9f9';
                    this.style.transform = 'scale(1)';

                    var files = e.dataTransfer.files;
                    if (files.length > 0) {
                        var file = files[0];

                        // 检查文件类型
                        if (!file.type.startsWith('image/')) {
                            alert('请拖拽图片文件');
                            return;
                        }

                        // 直接更新真实的file input，然后调用处理函数
                        var fileInput = document.getElementById('file-' + trademarkId);
                        if (fileInput) {
                            var dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            fileInput.files = dataTransfer.files;

                            // 调用图片处理函数
                            handleImageSelect(trademarkId, fileInput);
                        }
                    }
                });
            });
        }

        // 图片压缩函数
        function compressImage(file, maxWidth = 800, quality = 0.8) {
            return new Promise((resolve) => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const img = new Image();

                img.onload = function() {
                    // 计算压缩比例
                    const ratio = Math.min(maxWidth / img.width, maxWidth / img.height);
                    const newWidth = img.width * ratio;
                    const newHeight = img.height * ratio;

                    canvas.width = newWidth;
                    canvas.height = newHeight;

                    // 绘制压缩后的图片
                    ctx.drawImage(img, 0, 0, newWidth, newHeight);

                    // 转换为Blob对象
                    canvas.toBlob(function(blob) {
                        // 创建新的File对象，保持原文件名和类型
                        const compressedFile = new File([blob], file.name, {
                            type: file.type,
                            lastModified: Date.now()
                        });
                        resolve(compressedFile);
                    }, file.type, quality);
                };

                img.src = URL.createObjectURL(file);
            });
        }

        window.handleImageSelect = function(trademarkId, input) {
            var file = input.files[0];
            if (!file) return;

            // 检查文件类型
            if (!file.type.startsWith('image/')) {
                alert('请选择图片文件');
                return;
            }

            // 显示原始文件名和大小
            var fileSizeText = (file.size / 1024 / 1024).toFixed(2) + 'MB';
            document.getElementById('filename-' + trademarkId).innerHTML = file.name + ' (' + fileSizeText + ')';

            var previewDiv = document.getElementById('preview-' + trademarkId);

            // 显示压缩中状态
            previewDiv.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;"><div style="font-size:12px;color:#666;">压缩中...</div></div>';

            // 压缩图片
            compressImage(file, 800, 0.8).then(function(compressedFile) {
                // 保存压缩后的文件到input（用于后续上传）
                var dataTransfer = new DataTransfer();
                dataTransfer.items.add(compressedFile);
                input.files = dataTransfer.files;

                // 更新文件大小显示
                var compressedSizeText = (compressedFile.size / 1024 / 1024).toFixed(2) + 'MB';
                document.getElementById('filename-' + trademarkId).innerHTML = file.name + ' (原: ' + fileSizeText + ' → 压缩后: ' + compressedSizeText + ')';

                // 显示预览
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewDiv.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:contain;border-radius:4px;cursor:pointer;" onclick="showImagePreview(\'' + e.target.result + '\')" title="点击查看大图">';
                };
                reader.readAsDataURL(compressedFile);
            }).catch(function(error) {
                console.error('图片压缩失败:', error);
                // 压缩失败时使用原图
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewDiv.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:contain;border-radius:4px;cursor:pointer;" onclick="showImagePreview(\'' + e.target.result + '\')" title="点击查看大图">';
                };
                reader.readAsDataURL(file);
            });
        };

        window.showImagePreview = function(imageSrc) {
            var modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:10000;display:flex;align-items:center;justify-content:center;';
            modal.innerHTML = '<div style="position:relative;max-width:90%;max-height:90%;"><img src="' + imageSrc + '" style="max-width:100%;max-height:100%;"><button style="position:absolute;top:-40px;right:0;background:#fff;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:18px;">&times;</button></div>';
            document.body.appendChild(modal);

            modal.onclick = function(e) {
                if (e.target === modal || e.target.tagName === 'BUTTON') {
                    document.body.removeChild(modal);
                }
            };
        };

        // 批量删除图片按钮事件
        btnBatchDeleteImages.onclick = function() {
            var checkedBoxes = document.querySelectorAll('.image-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('请选择要删除图片的案件');
                return;
            }

            var caseList = [];
            checkedBoxes.forEach(function(checkbox) {
                caseList.push(checkbox.getAttribute('data-case-code'));
            });

            if (!confirm('确定要删除选中的 ' + checkedBoxes.length + ' 个案件的图片吗？\n案件：' + caseList.join(', '))) {
                return;
            }

            // 显示删除进度
            var progressDiv = document.getElementById('image-upload-progress');
            progressDiv.style.display = 'block';
            progressDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#f44336;">正在删除图片...</div>';

            btnBatchDeleteImages.disabled = true;
            btnBatchDeleteImages.textContent = '删除中...';

            // 批量删除图片
            batchDeleteImages(Array.from(checkedBoxes).map(cb => cb.value));
        };

        // 批量删除图片函数
        function batchDeleteImages(trademarkIds) {
            var completed = 0;
            var errors = [];
            var total = trademarkIds.length;

            function deleteNext() {
                if (completed >= total) {
                    // 所有删除完成
                    var progressDiv = document.getElementById('image-upload-progress');
                    if (errors.length === 0) {
                        progressDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#388e3c;">✓ 所有图片删除成功！</div>';
                        // 2秒后重新加载列表
                        setTimeout(function() {
                            loadTrademarkImageList();
                            progressDiv.style.display = 'none';
                        }, 2000);
                    } else {
                        progressDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#f44336;">删除完成，但有 ' + errors.length + ' 个失败</div>';
                    }

                    btnBatchDeleteImages.disabled = false;
                    btnBatchDeleteImages.textContent = '批量删除选中图片';
                    return;
                }

                var trademarkId = trademarkIds[completed];
                var progressDiv = document.getElementById('image-upload-progress');
                progressDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#f44336;">正在删除第 ' + (completed + 1) + '/' + total + ' 个图片...</div>';

                // 发送删除请求
                var xhr = new XMLHttpRequest();
                var baseUrl = window.location.href.split('?')[0];
                var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/personal_cases/department_cases.php';
                xhr.open('POST', requestUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        completed++;
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (!response.success) {
                                errors.push('案件ID ' + trademarkId + ': ' + (response.message || '删除失败'));
                            }
                        } catch (e) {
                            errors.push('案件ID ' + trademarkId + ': 响应解析失败');
                        }
                        deleteNext();
                    }
                };

                xhr.send('action=delete_image&trademark_id=' + encodeURIComponent(trademarkId));
            }

            deleteNext();
        }

        // 保存所有图片按钮事件
        btnSaveImages.onclick = function() {
            var uploadData = [];
            var fileInputs = document.querySelectorAll('#trademark-image-list input[type="file"]');

            fileInputs.forEach(function(input) {
                if (input.files[0]) {
                    var trademarkId = input.id.replace('file-', '');
                    uploadData.push({
                        id: trademarkId,
                        file: input.files[0]
                    });
                }
            });

            if (uploadData.length === 0) {
                alert('请至少选择一个图片文件');
                return;
            }

            // 显示进度
            document.getElementById('image-upload-progress').style.display = 'block';
            btnSaveImages.disabled = true;
            btnSaveImages.textContent = '上传中...';

            uploadImagesSequentially(uploadData, 0);
        };

        function uploadImagesSequentially(uploadData, index) {
            if (index >= uploadData.length) {
                // 全部上传完成
                document.getElementById('image-progress-bar').style.width = '100%';
                document.getElementById('image-progress-text').textContent = '所有图片上传完成！';
                btnSaveImages.disabled = false;
                btnSaveImages.textContent = '保存所有图片';

                setTimeout(function() {
                    alert('图片上传完成！');
                    closeImageImportModal();
                    loadTrademarkData(); // 刷新主列表
                }, 1000);
                return;
            }

            var item = uploadData[index];
            var progress = ((index + 1) / uploadData.length) * 100;
            document.getElementById('image-progress-bar').style.width = progress + '%';
            document.getElementById('image-progress-text').textContent = '正在上传第 ' + (index + 1) + ' 个图片，共 ' + uploadData.length + ' 个...';

            var formData = new FormData();
            formData.append('action', 'upload_image');
            formData.append('trademark_id', item.id);
            formData.append('trademark_image', item.file);

            var xhr = new XMLHttpRequest();
            var baseUrl = window.location.href.split('?')[0];
            var uploadUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/personal_cases/department_cases.php';
            xhr.open('POST', uploadUrl, true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // 继续上传下一个
                            uploadImagesSequentially(uploadData, index + 1);
                        } else {
                            alert('上传失败：' + (response.message || '未知错误'));
                            btnSaveImages.disabled = false;
                            btnSaveImages.textContent = '保存所有图片';
                        }
                    } catch (e) {
                        alert('上传失败：服务器响应错误');
                        btnSaveImages.disabled = false;
                        btnSaveImages.textContent = '保存所有图片';
                    }
                } else {
                    alert('上传失败：网络错误');
                    btnSaveImages.disabled = false;
                    btnSaveImages.textContent = '保存所有图片';
                }
            };

            xhr.onerror = function() {
                alert('上传失败：网络连接错误');
                btnSaveImages.disabled = false;
                btnSaveImages.textContent = '保存所有图片';
            };

            xhr.send(formData);
        }

        loadTrademarkData();
    })();
</script>