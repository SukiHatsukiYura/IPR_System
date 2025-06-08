<?php
session_start();
include_once(__DIR__ . '/../../database.php');
include_once(__DIR__ . '/../../common/functions.php'); // 引入通用函数库
check_access_via_framework();

// 检查是否通过框架访问
if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'index.php') === false) {
    header('Location: /index.php');
    exit;
}

// 检查用户权限
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 静态下拉选项
// 业务类型
$business_types = ['商标注册申请(电子)', '撤回商标注册申请(电子)', '变更名义地址申请(电子)', '撤回变更名义、地址申请(电子)', '变更代理文件接收人申请(电子)', '撤回变更代理文件接收人申请(电子)', '删减商品服务项目申请(电子)', '撤回删减申请(电子)', '商标转让转移申请(电子)', '撤回商标转让转移申请(电子)', '商标续展申请(电子)', '撤回商标续展申请(电子)', '商标注册申请(电子)', '撤回商标注册申请(电子)', '商标使用许可备案申请(电子)', '商标使用许可变更(电子)', '商标使用许可提前终止(电子)', '出具优先权证明申请(电子)', '补发商标注册证申请(电子)', '补发转让/变更/续展证明申请(电子)', '撤回取得连续三年不使用注册商标申请(纸件)', '取得连续三年不使用注册商标申请(纸件)', '商标异议申请(纸件)', '撤回商标异议申请(纸件)', '注册商标无效宣告复审申请(纸件)', '注册商标无效宣告申请(纸件)', '撤回商标评审申请(纸件)', '改正商标注册申请复审申请(纸件)', '撤销注册商标复审申请(纸件)', '商标不予注册复审申请(纸件)', '更正商标申请(电子)', '出具优先权证明文件申请(纸件)', '商标专用权质权登记主债权变更申请(纸件)', '商标专用权质权登记注销申请(纸件)', '商标异议申请(电子)', '商标专用权质权登记补发申请(纸件)', '商标专用权质权登记延期申请(纸件)', '商标专用权质权登记主债务申请(纸件)', '商标专用权质权登记补发延期申请(纸件)', '商标专用权质权登记注销申请(纸件)', '撤回取得成为商品服务通用名称注册商标申请(纸件)', '取得成为商品服务通用名称注册商标申请(纸件)', '特殊标志登记申请(纸件)', '商标评审案件答辩材料目录(纸件)', '商标评审案件证据目录(纸件)'];

// 案件状态
$case_statuses = ['初审公告', '驳回', '结案', '实审', '初审', '受理', '领证', '不予核准', '撤回', '公告', '已递交', '不予受理', '处理中', '审理中', '提供使用证据', '部分散销', '未递交', '正据交换', '复审', '转让', '续展', '视为放弃', '客户放弃', '客户微回', '核准', '部分驳回', '撤三', '补正', '异议', '终止'];

// 处理事项
$process_items = ['请求优先审查', '开卷', '放弃', '更正', '无效答辩', '不予受理', '官文转达', '缴费', '民事诉讼上诉', '主动补正', '商标评价报告', '驳回', '取得检索报告', '请求无效', '翻译', '审查高速公路', '资助监控', '赔偿请求', '请求检索报告', '许可备案', '诉讼', '取得副本', '请求加速审查', '民事诉讼答辩', '取得申请号', '请求中止', '办理注册手续', '复审决定', '避免重复授权', '民事诉讼上诉答辩', '确认通知书', '请求保密审查', '结案', '补正', '请求恢复权利', '视为未提出', '手续补正', '取得证书', '费用滞纳金', '复审意见陈述', '提交证据', '复审受理', '请求延长期限', '撤回', '请求提前公告', '处理审查意见', '口审', '诉讼举证', '项目申报', '办理变更', '行政诉讼上诉答辩', '请求复审', '无效行政诉讼答辩', '请求退款', '提出行政诉讼', '缴费', '终止', '无效诉讼', '公众意见', '保密决定', '变更代理人申请补正通知', '请求实审', '提出民事诉讼', '请求副本', '新申请', '复议申请', '无效请求补充意见', '著录项目变更', '行政诉讼上诉', '请求费用减缓', '视为未要求'];

// 商标类别 - 完整的45个类别
$trademark_classes = ['1(化工原料)', '2(颜料油漆)', '3(日化用品)', '4(燃料油脂)', '5(医药)', '6(金属材料)', '7(机械设备)', '8(手工器械)', '9(科学仪器)', '10(医疗器材)', '11(灯具空调)', '12(运输工具)', '13(军火烟火)', '14(珠宝钟表)', '15(乐器)', '16(办公用品)', '17(橡胶制品)', '18(皮革皮具)', '19(建筑材料)', '20(家具)', '21(厨房洁具)', '22(绳网袋篷)', '23(纱线丝)', '24(布料床单)', '25(服装鞋帽)', '26(纽扣拉链)', '27(地毯席垫)', '28(健身器材)', '29(食品)', '30(方便食品)', '31(饲料种籽)', '32(啤酒饮料)', '33(酒)', '34(烟草烟具)', '35(广告销售)', '36(金融物管)', '37(建筑修理)', '38(通讯服务)', '39(运输贮藏)', '40(材料加工)', '41(教育娱乐)', '42(网站服务)', '43(餐饮住宿)', '44(医疗园艺)', '45(社会服务)'];

// 商标种类
$trademark_types = ['一般', '集体', '证明', '特殊', '其他'];

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

// 查询动态数据
$departments = $pdo->query("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();
$users = $pdo->query("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name")->fetchAll();
$customers = $pdo->query("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn")->fetchAll();

// 格式化数据为通用下拉框函数所需格式
$departments_options = [];
$users_options = [];
$customers_options = [];
$process_items_options = [];
$business_types_options = [];
$case_statuses_options = [];
$trademark_classes_options = [];
$trademark_types_options = [];
$case_types_options = [];
$countries_options = [];
$case_flows_options = [];
$client_statuses_options = [];
$source_countries_options = [];
$application_modes_options = [];

foreach ($departments as $dept) {
    $departments_options[$dept['id']] = $dept['dept_name'];
}

foreach ($users as $user) {
    $users_options[$user['id']] = $user['real_name'];
}

foreach ($customers as $customer) {
    $customers_options[$customer['id']] = $customer['customer_name_cn'];
}

foreach ($process_items as $item) {
    $process_items_options[$item] = $item;
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

foreach ($trademark_types as $type) {
    $trademark_types_options[$type] = $type;
}

foreach ($case_types as $type) {
    $case_types_options[$type] = $type;
}

foreach ($countries as $country) {
    $countries_options[$country] = $country;
}

foreach ($case_flows as $flow) {
    $case_flows_options[$flow] = $flow;
}

foreach ($client_statuses as $status) {
    $client_statuses_options[$status] = $status;
}

foreach ($source_countries as $country) {
    $source_countries_options[$country] = $country;
}

foreach ($application_modes as $mode) {
    $application_modes_options[$mode] = $mode;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json');

    // 自动生成唯一文号
    function generate_case_code($pdo)
    {
        $prefix = 'TM' . date('Ymd');
        $sql = "SELECT COUNT(*) FROM trademark_case_info WHERE case_code LIKE :prefix";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);
        $count = $stmt->fetchColumn();
        $serial = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        return $prefix . $serial;
    }

    // 处理图片上传
    function handle_image_upload($case_code)
    {
        if (!isset($_FILES['trademark_image']) || $_FILES['trademark_image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['trademark_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('只支持JPG、PNG、GIF格式的图片');
        }

        if ($file['size'] > $max_size) {
            throw new Exception('图片文件大小不能超过5MB');
        }

        // 创建上传目录
        $upload_dir = __DIR__ . '/../../uploads/trademark_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // 生成唯一文件名
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $case_code . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('图片上传失败');
        }

        return [
            'path' => 'uploads/trademark_images/' . $filename,
            'name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type']
        ];
    }

    $data = [
        'case_name' => trim($_POST['case_name'] ?? ''),
        'case_name_en' => trim($_POST['case_name_en'] ?? ''),
        'application_no' => trim($_POST['application_no'] ?? ''),
        'business_dept_id' => intval($_POST['business_dept_id'] ?? 0),
        'trademark_class' => trim($_POST['trademark_class'] ?? ''), // 多选值，逗号分隔
        'initial_publication_date' => trim($_POST['initial_publication_date'] ?? ''),
        'initial_publication_period' => trim($_POST['initial_publication_period'] ?? ''),
        'client_id' => intval($_POST['client_id'] ?? 0),
        'case_type' => trim($_POST['case_type'] ?? ''),
        'business_type' => trim($_POST['business_type'] ?? ''),
        'entrust_date' => trim($_POST['entrust_date'] ?? ''),
        'case_status' => trim($_POST['case_status'] ?? ''),
        'process_item' => trim($_POST['process_item'] ?? ''),
        'source_country' => trim($_POST['source_country'] ?? ''),
        'trademark_description' => trim($_POST['trademark_description'] ?? ''),
        'other_name' => trim($_POST['other_name'] ?? ''),
        'application_date' => trim($_POST['application_date'] ?? ''),
        'business_user_ids' => trim($_POST['business_user_ids'] ?? ''),
        'business_assistant_ids' => trim($_POST['business_assistant_ids'] ?? ''),
        'trademark_type' => trim($_POST['trademark_type'] ?? ''),
        'initial_publication_no' => trim($_POST['initial_publication_no'] ?? ''),
        'registration_no' => trim($_POST['registration_no'] ?? ''),
        'country' => trim($_POST['country'] ?? ''),
        'case_flow' => trim($_POST['case_flow'] ?? ''),
        'application_mode' => trim($_POST['application_mode'] ?? ''),
        'open_date' => trim($_POST['open_date'] ?? ''),
        'client_case_code' => trim($_POST['client_case_code'] ?? ''),
        'approval_date' => trim($_POST['approval_date'] ?? ''),
        'remarks' => trim($_POST['remarks'] ?? ''),
        'is_main_case' => intval($_POST['is_main_case'] ?? 0),
        'registration_publication_date' => trim($_POST['registration_publication_date'] ?? ''),
        'registration_publication_period' => trim($_POST['registration_publication_period'] ?? ''),
        'client_status' => trim($_POST['client_status'] ?? ''),
        'renewal_date' => trim($_POST['renewal_date'] ?? ''),
        'expire_date' => trim($_POST['expire_date'] ?? ''),
        'trademark_image_path' => null,
        'trademark_image_name' => null,
        'trademark_image_size' => null,
        'trademark_image_type' => null,
    ];

    // 修正：所有DATE类型字段为空字符串时转为null，避免MySQL日期类型报错
    $date_fields = [
        'initial_publication_date',
        'entrust_date',
        'application_date',
        'open_date',
        'approval_date',
        'registration_publication_date',
        'renewal_date',
        'expire_date'
    ];
    foreach ($date_fields as $field) {
        if (isset($data[$field]) && $data[$field] === '') {
            $data[$field] = null;
        }
    }

    // 修正：所有外键字段为0或小于0时转为null，避免外键约束报错
    $fk_fields = ['business_dept_id', 'client_id'];
    foreach ($fk_fields as $field) {
        if (isset($data[$field]) && $data[$field] <= 0) {
            $data[$field] = null;
        }
    }

    if ($data['case_name'] === '' || $data['business_dept_id'] <= 0 || $data['client_id'] <= 0) {
        echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
        exit;
    }

    try {
        // 新增模式 - 自动生成唯一文号并执行INSERT操作
        $data['case_code'] = generate_case_code($pdo);

        // 处理图片上传
        $image_info = handle_image_upload($data['case_code']);
        if ($image_info) {
            $data['trademark_image_path'] = $image_info['path'];
            $data['trademark_image_name'] = $image_info['name'];
            $data['trademark_image_size'] = $image_info['size'];
            $data['trademark_image_type'] = $image_info['type'];
        }

        $fields = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO trademark_case_info ($fields) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        foreach ($data as $k => $v) {
            $stmt->bindValue(":$k", $v);
        }
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常:' . $e->getMessage()]);
    }
    exit;
}

function render_select($name, $options, $val = '', $placeholder = '--请选择--')
{
    $html = "<select name=\"$name\" class=\"module-input\">";
    $html .= "<option value=\"\">$placeholder</option>";
    foreach ($options as $o) {
        $selected = ($val == $o) ? 'selected' : '';
        $html .= "<option value=\"" . h($o) . "\" $selected>" . h($o) . "</option>";
    }
    $html .= "</select>";
    return $html;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>新增商标</title>
    <link rel="stylesheet" href="../../css/module.css">
    <?php render_select_search_assets(); ?>
</head>

<body>
    <div class="module-panel">
        <div class="module-btns">
            <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
            <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
        </div>
        <h3 style="text-align:center;margin-bottom:15px;">新增商标</h3>
        <form id="add-trademark-form" class="module-form" autocomplete="off" enctype="multipart/form-data">
            <table class="module-table" style="width:100%;max-width:1800px;table-layout:fixed;">
                <colgroup>
                    <col style="width:120px;">
                    <col style="width:220px;">
                    <col style="width:120px;">
                    <col style="width:220px;">
                    <col style="width:120px;">
                    <col style="width:220px;">
                </colgroup>
                <tr>
                    <td class="module-label">我方文号</td>
                    <td><input type="text" name="case_code" class="module-input" value="系统自动生成" readonly></td>
                    <td class="module-label module-req">*商标名称</td>
                    <td><input type="text" name="case_name" class="module-input" value="" required></td>
                    <td class="module-label">商标图片</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div id="image-preview" style="width:80px;height:80px;border:2px dashed #ddd;display:flex;align-items:center;justify-content:center;background:#f9f9f9;border-radius:4px;position:relative;">
                                <span style="color:#999;font-size:12px;text-align:center;">暂无图片</span>
                                <img id="preview-img" style="display:none;width:100%;height:100%;object-fit:contain;border-radius:4px;">
                            </div>
                            <div>
                                <input type="file" id="trademark-image" name="trademark_image" accept="image/*" style="display:none;">
                                <button type="button" id="upload-btn" class="btn-mini" style="background:#29b6b0;color:#fff;border:none;padding:6px 12px;">上传</button>
                                <button type="button" id="remove-btn" class="btn-mini" style="display:none;margin-left:5px;">删除</button>
                                <div style="font-size:12px;color:#666;margin-top:5px;">支持JPG、PNG、GIF<br>最大5MB</div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">英文名称</td>
                    <td><input type="text" name="case_name_en" class="module-input" value=""></td>
                    <td class="module-label">其它名称</td>
                    <td><input type="text" name="other_name" class="module-input" value=""></td>
                    <td class="module-label">是否主案</td>
                    <td>
                        <label><input type="radio" name="is_main_case" value="1">是</label>
                        <label><input type="radio" name="is_main_case" value="0" checked>否</label>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">申请号</td>
                    <td><input type="text" name="application_no" class="module-input" value=""></td>
                    <td class="module-label">申请日</td>
                    <td><input type="date" name="application_date" class="module-input" value=""></td>
                    <td class="module-label">注册公告日</td>
                    <td><input type="date" name="registration_publication_date" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*承办部门</td>
                    <td>
                        <?php render_select_search('business_dept_id', $departments_options, ''); ?>
                    </td>
                    <td class="module-label">业务人员</td>
                    <td>
                        <?php render_select_search_multi('business_user_ids', $users_options, ''); ?>
                    </td>
                    <td class="module-label">注册公告期</td>
                    <td><input type="text" name="registration_publication_period" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label">商标类别</td>
                    <td>
                        <?php render_select_search_multi('trademark_class', $trademark_classes_options, ''); ?>
                    </td>
                    <td class="module-label">业务助理</td>
                    <td>
                        <?php render_select_search_multi('business_assistant_ids', $users_options, ''); ?>
                    </td>
                    <td class="module-label">客户状态</td>
                    <td>
                        <?php echo render_select('client_status', $client_statuses, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">初审公告日</td>
                    <td><input type="date" name="initial_publication_date" class="module-input" value=""></td>
                    <td class="module-label">商标种类</td>
                    <td>
                        <?php echo render_select('trademark_type', $trademark_types, ''); ?>
                    </td>
                    <td class="module-label">续展日</td>
                    <td><input type="date" name="renewal_date" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label">初审公告期</td>
                    <td><input type="text" name="initial_publication_period" class="module-input" value=""></td>
                    <td class="module-label">初审公告号</td>
                    <td><input type="text" name="initial_publication_no" class="module-input" value=""></td>
                    <td class="module-label">终止日</td>
                    <td><input type="date" name="expire_date" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*客户名称</td>
                    <td>
                        <?php render_select_search('client_id', $customers_options, ''); ?>
                    </td>
                    <td class="module-label">注册号</td>
                    <td><input type="text" name="registration_no" class="module-input" value=""></td>
                    <td class="module-label"></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="module-label">客户债权</td>
                    <td><input type="text" name="client_case_code" class="module-input" value=""></td>
                    <td class="module-label">国家(地区)</td>
                    <td>
                        <?php echo render_select('country', $countries, ''); ?>
                    </td>
                    <td class="module-label"></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="module-label">案件类型</td>
                    <td>
                        <?php echo render_select('case_type', $case_types, ''); ?>
                    </td>
                    <td class="module-label">案件流向</td>
                    <td>
                        <?php echo render_select('case_flow', $case_flows, ''); ?>
                    </td>
                    <td class="module-label"></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="module-label">业务类型</td>
                    <td>
                        <?php render_select_search('business_type', $business_types_options, ''); ?>
                    </td>
                    <td class="module-label">申请方式</td>
                    <td>
                        <?php echo render_select('application_mode', $application_modes, ''); ?>
                    </td>
                    <td class="module-label"></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="module-label">委案日期</td>
                    <td><input type="date" name="entrust_date" class="module-input" value=""></td>
                    <td class="module-label">开卷日期</td>
                    <td><input type="date" name="open_date" class="module-input" value=""></td>
                    <td class="module-label"></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="module-label">案件状态</td>
                    <td>
                        <?php echo render_select('case_status', $case_statuses, ''); ?>
                    </td>
                    <td class="module-label">客户文号</td>
                    <td><input type="text" name="client_case_code" class="module-input" value=""></td>
                    <td class="module-label"></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="module-label">处理事项</td>
                    <td>
                        <?php render_select_search('process_item', $process_items_options, ''); ?>
                    </td>
                    <td class="module-label">获批日</td>
                    <td><input type="date" name="approval_date" class="module-input" value=""></td>
                    <td class="module-label"></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="module-label">案源国</td>
                    <!-- 默认显示中国 -->
                    <td>
                        <?php echo render_select('source_country', $source_countries, '中国'); ?>
                    </td>
                    <td class="module-label">备注</td>
                    <td><textarea name="remarks" class="module-textarea" rows="2"></textarea></td>
                    <td class="module-label"></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="module-label">商标说明</td>
                    <td colspan="5"><textarea name="trademark_description" class="module-textarea" rows="3" style="width:100%;"></textarea></td>
                </tr>
            </table>
        </form>
    </div>
    <script>
        (function() {
            var form = document.getElementById('add-trademark-form'),
                btnSave = document.querySelector('.btn-save'),
                btnCancel = document.querySelector('.btn-cancel'),
                uploadBtn = document.getElementById('upload-btn'),
                removeBtn = document.getElementById('remove-btn'),
                fileInput = document.getElementById('trademark-image'),
                imagePreview = document.getElementById('image-preview'),
                previewImg = document.getElementById('preview-img');

            // 图片上传功能
            uploadBtn.onclick = function() {
                fileInput.click();
            };

            fileInput.onchange = function() {
                var file = this.files[0];
                if (file) {
                    // 验证文件类型
                    var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('只支持JPG、PNG、GIF格式的图片');
                        this.value = '';
                        return;
                    }

                    // 验证文件大小
                    if (file.size > 5 * 1024 * 1024) {
                        alert('图片文件大小不能超过5MB');
                        this.value = '';
                        return;
                    }

                    // 预览图片
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewImg.style.display = 'block';
                        imagePreview.querySelector('span').style.display = 'none';
                        removeBtn.style.display = 'inline-block';
                    };
                    reader.readAsDataURL(file);
                }
            };

            // 删除图片
            removeBtn.onclick = function() {
                fileInput.value = '';
                previewImg.style.display = 'none';
                previewImg.src = '';
                imagePreview.querySelector('span').style.display = 'block';
                removeBtn.style.display = 'none';
            };

            // 重置图片预览
            function resetImagePreview() {
                fileInput.value = '';
                previewImg.style.display = 'none';
                previewImg.src = '';
                imagePreview.querySelector('span').style.display = 'block';
                removeBtn.style.display = 'none';
            }

            // 保存按钮AJAX提交
            btnSave.onclick = function() {
                var required = ['case_name', 'business_dept_id', 'client_id'];
                for (var i = 0; i < required.length; i++) {
                    var el = form.querySelector('[name="' + required[i] + '"]');
                    if (!el || !el.value.trim()) {
                        alert('请填写所有必填项');
                        el && el.focus();
                        return;
                    }
                }
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/trademark_management/add_trademark.php', true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                alert('保存成功');
                                form.reset();
                                resetImagePreview();
                                // 重置所有下拉搜索框
                                document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
                                document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
                                document.querySelectorAll('.module-select-search-multi-input').forEach(i => i.value = '');
                                document.querySelectorAll('.module-select-search-multi-box input[type=hidden]').forEach(i => i.value = '');
                            } else {
                                alert(res.msg || '保存失败');
                            }
                        } catch (e) {
                            alert('保存失败：' + xhr.responseText);
                        }
                    }
                };
                xhr.send(fd);
            };

            // 取消按钮
            btnCancel.onclick = function() {
                if (confirm('确定要取消吗？未保存的内容将丢失')) {
                    form.reset();
                    resetImagePreview();
                    // 重置所有下拉搜索框
                    document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
                    document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
                    document.querySelectorAll('.module-select-search-multi-input').forEach(i => i.value = '');
                    document.querySelectorAll('.module-select-search-multi-box input[type=hidden]').forEach(i => i.value = '');
                }
            };
        })();
    </script>
</body>

</html>