<?php
// 商标编辑-基本信息
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['ajax']) || isset($_POST['ajax']) || (isset($_POST['action']) && $_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => '未登录或会话超时']);
        exit;
    } else {
        header('Location: /login.php');
        exit;
    }
}

if (!isset($_GET['trademark_id']) || intval($_GET['trademark_id']) <= 0) {
    echo '<div class="module-error">未指定商标ID</div>';
    exit;
}
$trademark_id = intval($_GET['trademark_id']);

// 验证商标是否存在
$trademark_stmt = $pdo->prepare("SELECT * FROM trademark_case_info WHERE id = ?");
$trademark_stmt->execute([$trademark_id]);
$trademark_info = $trademark_stmt->fetch();
if (!$trademark_info) {
    echo '<div class="module-error">未找到该商标信息</div>';
    exit;
}

// 处理POST请求（保存数据）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json');

    // 处理图片上传
    function handle_image_upload($case_code, $old_image_path = null)
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
        $upload_dir = __DIR__ . '/../../../uploads/trademark_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // 删除旧图片
        if ($old_image_path && file_exists(__DIR__ . '/../../../' . $old_image_path)) {
            unlink(__DIR__ . '/../../../' . $old_image_path);
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
        'trademark_class' => trim($_POST['trademark_class'] ?? ''),
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
    ];

    // 修正：所有DATE类型字段为空字符串时转为null
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

    // 修正：所有外键字段为0或小于0时转为null
    $fk_fields = ['business_dept_id', 'client_id'];
    foreach ($fk_fields as $field) {
        if (isset($data[$field]) && $data[$field] <= 0) {
            $data[$field] = null;
        }
    }

    try {
        // 处理图片上传
        $image_info = handle_image_upload($trademark_info['case_code'], $trademark_info['trademark_image_path']);
        if ($image_info) {
            $data['trademark_image_path'] = $image_info['path'];
            $data['trademark_image_name'] = $image_info['name'];
            $data['trademark_image_size'] = $image_info['size'];
            $data['trademark_image_type'] = $image_info['type'];
        }

        $set = [];
        foreach ($data as $k => $v) {
            $set[] = "$k = :$k";
        }
        $data['id'] = $trademark_id;
        $sql = "UPDATE trademark_case_info SET " . implode(',', $set) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($data);
        echo json_encode(['success' => $result, 'msg' => $result ? null : '更新失败']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    }
    exit;
}

// 查询动态数据
$departments = $pdo->query("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();
$users = $pdo->query("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name")->fetchAll();
$customers = $pdo->query("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn")->fetchAll();

// 静态下拉选项
$business_types = ['商标注册申请(电子)', '撤回商标注册申请(电子)', '变更名义地址申请(电子)', '撤回变更名义、地址申请(电子)', '变更代理文件接收人申请(电子)', '撤回变更代理文件接收人申请(电子)', '删减商品服务项目申请(电子)', '撤回删减申请(电子)', '商标转让转移申请(电子)', '撤回商标转让转移申请(电子)', '商标续展申请(电子)', '撤回商标续展申请(电子)', '商标注册申请(电子)', '撤回商标注册申请(电子)', '商标使用许可备案申请(电子)', '商标使用许可变更(电子)', '商标使用许可提前终止(电子)', '出具优先权证明申请(电子)', '补发商标注册证申请(电子)', '补发转让/变更/续展证明申请(电子)', '撤回取得连续三年不使用注册商标申请(纸件)', '取得连续三年不使用注册商标申请(纸件)', '商标异议申请(纸件)', '撤回商标异议申请(纸件)', '注册商标无效宣告复审申请(纸件)', '注册商标无效宣告申请(纸件)', '撤回商标评审申请(纸件)', '改正商标注册申请复审申请(纸件)', '撤销注册商标复审申请(纸件)', '商标不予注册复审申请(纸件)', '更正商标申请(电子)', '出具优先权证明文件申请(纸件)', '商标专用权质权登记主债权变更申请(纸件)', '商标专用权质权登记注销申请(纸件)', '商标异议申请(电子)', '商标专用权质权登记补发申请(纸件)', '商标专用权质权登记延期申请(纸件)', '商标专用权质权登记主债务申请(纸件)', '商标专用权质权登记补发延期申请(纸件)', '商标专用权质权登记注销申请(纸件)', '撤回取得成为商品服务通用名称注册商标申请(纸件)', '取得成为商品服务通用名称注册商标申请(纸件)', '特殊标志登记申请(纸件)', '商标评审案件答辩材料目录(纸件)', '商标评审案件证据目录(纸件)'];

$case_statuses = ['初审公告', '驳回', '结案', '实审', '初审', '受理', '领证', '不予核准', '撤回', '公告', '已递交', '不予受理', '处理中', '审理中', '提供使用证据', '部分散销', '未递交', '正据交换', '复审', '转让', '续展', '视为放弃', '客户放弃', '客户微回', '核准', '部分驳回', '撤三', '补正', '异议', '终止'];

$process_items = ['请求优先审查', '开卷', '放弃', '更正', '无效答辩', '不予受理', '官文转达', '缴费', '民事诉讼上诉', '主动补正', '商标评价报告', '驳回', '取得检索报告', '请求无效', '翻译', '审查高速公路', '资助监控', '赔偿请求', '请求检索报告', '许可备案', '诉讼', '取得副本', '请求加速审查', '民事诉讼答辩', '取得申请号', '请求中止', '办理注册手续', '复审决定', '避免重复授权', '民事诉讼上诉答辩', '确认通知书', '请求保密审查', '结案', '补正', '请求恢复权利', '视为未提出', '手续补正', '取得证书', '费用滞纳金', '复审意见陈述', '提交证据', '复审受理', '请求延长期限', '撤回', '请求提前公告', '处理审查意见', '口审', '诉讼举证', '项目申报', '办理变更', '行政诉讼上诉答辩', '请求复审', '无效行政诉讼答辩', '请求退款', '提出行政诉讼', '缴费', '终止', '无效诉讼', '公众意见', '保密决定', '变更代理人申请补正通知', '请求实审', '提出民事诉讼', '请求副本', '新申请', '复议申请', '无效请求补充意见', '著录项目变更', '行政诉讼上诉', '请求费用减缓', '视为未要求'];

$trademark_classes = ['1(化工原料)', '2(颜料油漆)', '3(日化用品)', '4(燃料油脂)', '5(医药)', '6(金属材料)', '7(机械设备)', '8(手工器械)', '9(科学仪器)', '10(医疗器材)', '11(灯具空调)', '12(运输工具)', '13(军火烟火)', '14(珠宝钟表)', '15(乐器)', '16(办公用品)', '17(橡胶制品)', '18(皮革皮具)', '19(建筑材料)', '20(家具)', '21(厨房洁具)', '22(绳网袋篷)', '23(纱线丝)', '24(布料床单)', '25(服装鞋帽)', '26(纽扣拉链)', '27(地毯席垫)', '28(健身器材)', '29(食品)', '30(方便食品)', '31(饲料种籽)', '32(啤酒饮料)', '33(酒)', '34(烟草烟具)', '35(广告销售)', '36(金融物管)', '37(建筑修理)', '38(通讯服务)', '39(运输贮藏)', '40(材料加工)', '41(教育娱乐)', '42(网站服务)', '43(餐饮住宿)', '44(医疗园艺)', '45(社会服务)'];

$trademark_types = ['一般', '集体', '证明', '特殊', '其他'];
$case_types = ['商标注册申请', '商标续展', '商标转让', '商标变更', '商标撤销', '商标异议', '商标复审', '商标无效', '马德里国际注册', '其他'];
$application_modes = ['电子申请', '纸本申请', '其他'];
$countries = ['中国', '美国', '日本', '韩国', '德国', '法国', '英国', '其他'];
$case_flows = ['内-内', '内-外', '外-内', '外-外'];
$client_statuses = ['请选择', '放弃指示', '新申请指示递交', '补充申请信息资料', '修改意见', '著录项目变更指示', 'OA指示递交', '结案指示'];
$source_countries = ['中国', '美国', '日本', '其他'];

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

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
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

render_select_search_assets();
?>

<!-- <div class="module-panel"> -->
    <div class="module-btns">
        <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
        <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
    </div>

    <form id="edit-trademark-form" class="module-form" autocomplete="off" enctype="multipart/form-data">
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
                <td><input type="text" name="case_code" class="module-input" value="<?= h($trademark_info['case_code']) ?>" readonly></td>
                <td class="module-label module-req">*商标名称</td>
                <td><input type="text" name="case_name" class="module-input" value="<?= h($trademark_info['case_name']) ?>" required></td>
                <td class="module-label">商标图片</td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div id="image-preview" style="width:80px;height:80px;border:2px dashed #ddd;display:flex;align-items:center;justify-content:center;background:#f9f9f9;border-radius:4px;position:relative;">
                            <?php if ($trademark_info['trademark_image_path']): ?>
                                <img id="preview-img" src="<?= h($trademark_info['trademark_image_path']) ?>" style="width:100%;height:100%;object-fit:contain;border-radius:4px;">
                            <?php else: ?>
                                <span style="color:#999;font-size:12px;text-align:center;">暂无图片</span>
                                <img id="preview-img" style="display:none;width:100%;height:100%;object-fit:contain;border-radius:4px;">
                            <?php endif; ?>
                        </div>
                        <div>
                            <input type="file" id="trademark-image" name="trademark_image" accept="image/*" style="display:none;">
                            <button type="button" id="upload-btn" class="btn-mini" style="background:#29b6b0;color:#fff;border:none;padding:6px 12px;">上传</button>
                            <button type="button" id="remove-btn" class="btn-mini" style="<?= $trademark_info['trademark_image_path'] ? '' : 'display:none;' ?>margin-left:5px;">删除</button>
                            <div style="font-size:12px;color:#666;margin-top:5px;">支持JPG、PNG、GIF<br>最大5MB</div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="module-label">英文名称</td>
                <td><input type="text" name="case_name_en" class="module-input" value="<?= h($trademark_info['case_name_en']) ?>"></td>
                <td class="module-label">其它名称</td>
                <td><input type="text" name="other_name" class="module-input" value="<?= h($trademark_info['other_name']) ?>"></td>
                <td class="module-label">是否主案</td>
                <td>
                    <label><input type="radio" name="is_main_case" value="1" <?= $trademark_info['is_main_case'] == 1 ? 'checked' : '' ?>>是</label>
                    <label><input type="radio" name="is_main_case" value="0" <?= $trademark_info['is_main_case'] == 0 ? 'checked' : '' ?>>否</label>
                </td>
            </tr>
            <tr>
                <td class="module-label">申请号</td>
                <td><input type="text" name="application_no" class="module-input" value="<?= h($trademark_info['application_no']) ?>"></td>
                <td class="module-label">申请日</td>
                <td><input type="date" name="application_date" class="module-input" value="<?= h($trademark_info['application_date']) ?>"></td>
                <td class="module-label">注册公告日</td>
                <td><input type="date" name="registration_publication_date" class="module-input" value="<?= h($trademark_info['registration_publication_date']) ?>"></td>
            </tr>
            <tr>
                <td class="module-label module-req">*承办部门</td>
                <td>
                    <?php render_select_search('business_dept_id', $departments_options, $trademark_info['business_dept_id']); ?>
                </td>
                <td class="module-label">业务人员</td>
                <td>
                    <?php render_select_search_multi('business_user_ids', $users_options, $trademark_info['business_user_ids']); ?>
                </td>
                <td class="module-label">注册公告期</td>
                <td><input type="text" name="registration_publication_period" class="module-input" value="<?= h($trademark_info['registration_publication_period']) ?>"></td>
            </tr>
            <tr>
                <td class="module-label">商标类别</td>
                <td>
                    <?php render_select_search_multi('trademark_class', $trademark_classes_options, $trademark_info['trademark_class']); ?>
                </td>
                <td class="module-label">业务助理</td>
                <td>
                    <?php render_select_search_multi('business_assistant_ids', $users_options, $trademark_info['business_assistant_ids']); ?>
                </td>
                <td class="module-label">客户状态</td>
                <td>
                    <?php echo render_select('client_status', $client_statuses, $trademark_info['client_status']); ?>
                </td>
            </tr>
            <tr>
                <td class="module-label">初审公告日</td>
                <td><input type="date" name="initial_publication_date" class="module-input" value="<?= h($trademark_info['initial_publication_date']) ?>"></td>
                <td class="module-label">商标种类</td>
                <td>
                    <?php echo render_select('trademark_type', $trademark_types, $trademark_info['trademark_type']); ?>
                </td>
                <td class="module-label">续展日</td>
                <td><input type="date" name="renewal_date" class="module-input" value="<?= h($trademark_info['renewal_date']) ?>"></td>
            </tr>
            <tr>
                <td class="module-label">初审公告期</td>
                <td><input type="text" name="initial_publication_period" class="module-input" value="<?= h($trademark_info['initial_publication_period']) ?>"></td>
                <td class="module-label">初审公告号</td>
                <td><input type="text" name="initial_publication_no" class="module-input" value="<?= h($trademark_info['initial_publication_no']) ?>"></td>
                <td class="module-label">终止日</td>
                <td><input type="date" name="expire_date" class="module-input" value="<?= h($trademark_info['expire_date']) ?>"></td>
            </tr>
            <tr>
                <td class="module-label module-req">*客户名称</td>
                <td>
                    <?php render_select_search('client_id', $customers_options, $trademark_info['client_id']); ?>
                </td>
                <td class="module-label">注册号</td>
                <td><input type="text" name="registration_no" class="module-input" value="<?= h($trademark_info['registration_no']) ?>"></td>
                <td class="module-label"></td>
                <td></td>
            </tr>
            <tr>
                <td class="module-label">客户文号</td>
                <td><input type="text" name="client_case_code" class="module-input" value="<?= h($trademark_info['client_case_code']) ?>"></td>
                <td class="module-label">国家(地区)</td>
                <td>
                    <?php echo render_select('country', $countries, $trademark_info['country']); ?>
                </td>
                <td class="module-label"></td>
                <td></td>
            </tr>
            <tr>
                <td class="module-label">案件类型</td>
                <td>
                    <?php echo render_select('case_type', $case_types, $trademark_info['case_type']); ?>
                </td>
                <td class="module-label">案件流向</td>
                <td>
                    <?php echo render_select('case_flow', $case_flows, $trademark_info['case_flow']); ?>
                </td>
                <td class="module-label"></td>
                <td></td>
            </tr>
            <tr>
                <td class="module-label">业务类型</td>
                <td>
                    <?php render_select_search('business_type', $business_types_options, $trademark_info['business_type']); ?>
                </td>
                <td class="module-label">申请方式</td>
                <td>
                    <?php echo render_select('application_mode', $application_modes, $trademark_info['application_mode']); ?>
                </td>
                <td class="module-label"></td>
                <td></td>
            </tr>
            <tr>
                <td class="module-label">委案日期</td>
                <td><input type="date" name="entrust_date" class="module-input" value="<?= h($trademark_info['entrust_date']) ?>"></td>
                <td class="module-label">开卷日期</td>
                <td><input type="date" name="open_date" class="module-input" value="<?= h($trademark_info['open_date']) ?>"></td>
                <td class="module-label"></td>
                <td></td>
            </tr>
            <tr>
                <td class="module-label">案件状态</td>
                <td>
                    <?php echo render_select('case_status', $case_statuses, $trademark_info['case_status']); ?>
                </td>
                <td class="module-label">获批日</td>
                <td><input type="date" name="approval_date" class="module-input" value="<?= h($trademark_info['approval_date']) ?>"></td>
                <td class="module-label"></td>
                <td></td>
            </tr>
            <tr>
                <td class="module-label">处理事项</td>
                <td>
                    <?php render_select_search('process_item', $process_items_options, $trademark_info['process_item']); ?>
                </td>
                <td class="module-label">备注</td>
                <td><textarea name="remarks" class="module-textarea" rows="2"><?= h($trademark_info['remarks']) ?></textarea></td>
                <td class="module-label"></td>
                <td></td>
            </tr>
            <tr>
                <td class="module-label">案源国</td>
                <td>
                    <?php echo render_select('source_country', $source_countries, $trademark_info['source_country']); ?>
                </td>
                <td class="module-label"></td>
                <td></td>
                <td class="module-label"></td>
                <td></td>
            </tr>
            <tr>
                <td class="module-label">商标说明</td>
                <td colspan="5"><textarea name="trademark_description" class="module-textarea" rows="3" style="width:100%;"><?= h($trademark_info['trademark_description']) ?></textarea></td>
            </tr>
        </table>
    </form>
<!-- </div> -->

<script>
    window.initTrademarkTabEvents = function() {
        var form = document.getElementById('edit-trademark-form'),
            btnSave = document.querySelector('#trademark-tab-content .btn-save'),
            btnCancel = document.querySelector('#trademark-tab-content .btn-cancel'),
            uploadBtn = document.getElementById('upload-btn'),
            removeBtn = document.getElementById('remove-btn'),
            fileInput = document.getElementById('trademark-image'),
            imagePreview = document.getElementById('image-preview'),
            previewImg = document.getElementById('preview-img');

        // 图片上传功能
        if (uploadBtn) {
            uploadBtn.onclick = function() {
                fileInput.click();
            };
        }

        if (fileInput) {
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
                        var span = imagePreview.querySelector('span');
                        if (span) span.style.display = 'none';
                        removeBtn.style.display = 'inline-block';
                    };
                    reader.readAsDataURL(file);
                }
            };
        }

        // 删除图片
        if (removeBtn) {
            removeBtn.onclick = function() {
                fileInput.value = '';
                previewImg.style.display = 'none';
                previewImg.src = '';
                var span = imagePreview.querySelector('span');
                if (span) span.style.display = 'block';
                removeBtn.style.display = 'none';
            };
        }

        // 保存按钮AJAX提交
        if (btnSave) {
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
                xhr.open('POST', 'modules/trademark_management/edit_tabs/basic.php?trademark_id=<?= $trademark_id ?>', true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                alert('保存成功');
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
        }

        // 取消按钮
        if (btnCancel) {
            btnCancel.onclick = function() {
                if (confirm('确定要取消吗？未保存的内容将丢失。')) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'modules/trademark_management/edit_tabs/basic.php?trademark_id=<?= $trademark_id ?>', true);
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            var tabContent = document.querySelector('#trademark-tab-content');
                            if (tabContent) {
                                // 创建临时容器
                                var tempDiv = document.createElement('div');
                                tempDiv.innerHTML = xhr.responseText;

                                // 将所有脚本提取出来
                                var scripts = [];
                                tempDiv.querySelectorAll('script').forEach(function(script) {
                                    scripts.push(script);
                                    script.parentNode.removeChild(script);
                                });

                                // 更新内容
                                tabContent.innerHTML = tempDiv.innerHTML;

                                // 执行脚本
                                scripts.forEach(function(script) {
                                    var newScript = document.createElement('script');
                                    if (script.src) {
                                        newScript.src = script.src;
                                    } else {
                                        newScript.textContent = script.textContent;
                                    }
                                    document.body.appendChild(newScript);
                                });

                                // 延迟初始化下拉框
                                setTimeout(function() {
                                    if (typeof window.initSelectSearchControls === 'function') {
                                        window.initSelectSearchControls();
                                    }

                                    // 初始化其他事件处理
                                    if (typeof window.initTrademarkTabEvents === 'function') {
                                        window.initTrademarkTabEvents();
                                    }
                                }, 200);
                            }
                        } else {
                            alert('重置表单失败，请刷新页面重试');
                        }
                    };
                    xhr.send();
                }
            };
        }
    };

    // 初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initTrademarkTabEvents);
    } else {
        window.initTrademarkTabEvents();
    }
</script>