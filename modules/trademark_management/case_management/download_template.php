<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 获取关联表数据用于提示
function getRelatedTableData($pdo)
{
    $data = [];

    // 获取部门数据
    $stmt = $pdo->prepare("SELECT id, dept_name FROM department WHERE is_active = 1 ORDER BY id");
    $stmt->execute();
    $departments = $stmt->fetchAll();
    $data['departments'] = $departments;

    // 获取客户数据
    $stmt = $pdo->prepare("SELECT id, customer_name_cn FROM customer ORDER BY id");
    $stmt->execute();
    $customers = $stmt->fetchAll();
    $data['customers'] = $customers;

    // 获取用户数据
    $stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active = 1 ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll();
    $data['users'] = $users;

    return $data;
}

// 获取选项数据
function getOptionsData()
{
    // 处理事项选项（从add_trademark.php复制）
    $process_items = ['请求优先审查', '开卷', '放弃', '更正', '无效答辩', '不予受理', '官文转达', '缴费', '民事诉讼上诉', '主动补正', '商标评价报告', '驳回', '取得检索报告', '请求无效', '翻译', '审查高速公路', '资助监控', '赔偿请求', '请求检索报告', '许可备案', '诉讼', '取得副本', '请求加速审查', '民事诉讼答辩', '取得申请号', '请求中止', '办理注册手续', '复审决定', '避免重复授权', '民事诉讼上诉答辩', '确认通知书', '请求保密审查', '结案', '补正', '请求恢复权利', '视为未提出', '手续补正', '取得证书', '费用滞纳金', '复审意见陈述', '提交证据', '复审受理', '请求延长期限', '撤回', '请求提前公告', '处理审查意见', '口审', '诉讼举证', '项目申报', '办理变更', '行政诉讼上诉答辩', '请求复审', '无效行政诉讼答辩', '请求退款', '提出行政诉讼', '缴费', '终止', '无效诉讼', '公众意见', '保密决定', '变更代理人申请补正通知', '请求实审', '提出民事诉讼', '请求副本', '新申请', '复议申请', '无效请求补充意见', '著录项目变更', '行政诉讼上诉', '请求费用减缓', '视为未要求'];

    // 案件类型选项
    $case_types = ['商标注册申请', '商标续展', '商标转让', '商标变更', '商标撤销', '商标异议', '商标复审', '商标无效', '马德里国际注册', '其他'];

    // 商标类别
    $trademark_classes = ['1(化工原料)', '2(颜料油漆)', '3(日化用品)', '4(燃料油脂)', '5(医药)', '6(金属材料)', '7(机械设备)', '8(手工器械)', '9(科学仪器)', '10(医疗器材)', '11(灯具空调)', '12(运输工具)', '13(军火烟火)', '14(珠宝钟表)', '15(乐器)', '16(办公用品)', '17(橡胶制品)', '18(皮革皮具)', '19(建筑材料)', '20(家具)', '21(厨房洁具)', '22(绳网袋篷)', '23(纱线丝)', '24(布料床单)', '25(服装鞋帽)', '26(纽扣拉链)', '27(地毯席垫)', '28(健身器材)', '29(食品)', '30(方便食品)', '31(饲料种籽)', '32(啤酒饮料)', '33(酒)', '34(烟草烟具)', '35(广告销售)', '36(金融物管)', '37(建筑修理)', '38(通讯服务)', '39(运输贮藏)', '40(材料加工)', '41(教育娱乐)', '42(网站服务)', '43(餐饮住宿)', '44(医疗园艺)', '45(社会服务)'];

    // 商标种类
    $trademark_types = ['一般', '集体', '证明', '特殊', '其他'];

    return [
        'process_items' => $process_items,
        'case_types' => $case_types,
        'trademark_classes' => $trademark_classes,
        'trademark_types' => $trademark_types
    ];
}

$relatedData = getRelatedTableData($pdo);
$optionsData = getOptionsData();

// 商标案件基本信息表的字段（根据add_trademark.php的必填字段修正）
$headers = [
    'case_code' => '我方文号(可选，留空则自动生成)',
    'case_name' => '商标名称*',
    'case_name_en' => '英文名称',
    'application_no' => '申请号',
    'business_dept_id' => '承办部门ID*',
    'trademark_class' => '商标类别(多个用逗号分隔)',
    'initial_publication_date' => '初审公告日(YYYY-MM-DD)',
    'initial_publication_period' => '初审公告期',
    'client_id' => '客户ID*',
    'client_name' => '客户名称(中)*',
    'case_type' => '案件类型',
    'business_type' => '业务类型',
    'entrust_date' => '委案日期(YYYY-MM-DD)',
    'case_status' => '案件状态',
    'process_item' => '处理事项*',
    'source_country' => '案源国',
    'trademark_description' => '商标说明',
    'other_name' => '其它名称',
    'application_date' => '申请日(YYYY-MM-DD)',
    'business_user_ids' => '业务人员ID(多个用逗号分隔)',
    'business_assistant_ids' => '业务助理ID(多个用逗号分隔)',
    'trademark_type' => '商标种类',
    'initial_publication_no' => '初审公告号',
    'registration_no' => '注册号',
    'country' => '国家(地区)',
    'case_flow' => '案件流向',
    'application_mode' => '申请方式',
    'open_date' => '开卷日期(YYYY-MM-DD)',
    'client_case_code' => '客户文号',
    'approval_date' => '获批日(YYYY-MM-DD)',
    'remarks' => '备注',
    'is_main_case' => '是否主案(1是0否)',
    'registration_publication_date' => '注册公告日(YYYY-MM-DD)',
    'registration_publication_period' => '注册公告期',
    'client_status' => '客户状态',
    'renewal_date' => '续展日(YYYY-MM-DD)',
    'expire_date' => '终止日(YYYY-MM-DD)'
];

// 设置Excel文件头
$current_date = date('Y年n月j日');
$filename = "商标案件批量导入模板{$current_date}.xls";
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// 生成Excel XML格式
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
echo ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
echo ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
echo ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

// 样式定义
echo '<Styles>' . "\n";
echo '<Style ss:ID="HeaderStyle">' . "\n";
echo '<Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF"/>' . "\n";
echo '<Interior ss:Color="#4472C4" ss:Pattern="Solid"/>' . "\n";
echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
echo '<Borders>' . "\n";
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '</Borders>' . "\n";
echo '</Style>' . "\n";

echo '<Style ss:ID="InfoStyle">' . "\n";
echo '<Font ss:Bold="1" ss:Size="10" ss:Color="#2F5597"/>' . "\n";
echo '<Interior ss:Color="#E7F1FF" ss:Pattern="Solid"/>' . "\n";
echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
echo '<Borders>' . "\n";
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>' . "\n";
echo '</Borders>' . "\n";
echo '</Style>' . "\n";

echo '<Style ss:ID="TipsStyle">' . "\n";
echo '<Font ss:Bold="1" ss:Size="10" ss:Color="#D84315"/>' . "\n";
echo '<Interior ss:Color="#FFF3E0" ss:Pattern="Solid"/>' . "\n";
echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
echo '<Borders>' . "\n";
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>' . "\n";
echo '</Borders>' . "\n";
echo '</Style>' . "\n";

echo '<Style ss:ID="TitleStyle">' . "\n";
echo '<Font ss:Bold="1" ss:Size="12" ss:Color="#1976D2"/>' . "\n";
echo '<Interior ss:Color="#E3F2FD" ss:Pattern="Solid"/>' . "\n";
echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
echo '<Borders>' . "\n";
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>' . "\n";
echo '</Borders>' . "\n";
echo '</Style>' . "\n";

echo '<Style ss:ID="DataStyle">' . "\n";
echo '<Font ss:Size="10"/>' . "\n";
echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>' . "\n";
echo '<Borders>' . "\n";
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#EEEEEE"/>' . "\n";
echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#EEEEEE"/>' . "\n";
echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#EEEEEE"/>' . "\n";
echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#EEEEEE"/>' . "\n";
echo '</Borders>' . "\n";
echo '</Style>' . "\n";

echo '<Style ss:ID="RequiredHeaderStyle">' . "\n";
echo '<Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF"/>' . "\n";
echo '<Interior ss:Color="#DC3545" ss:Pattern="Solid"/>' . "\n";
echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
echo '<Borders>' . "\n";
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '</Borders>' . "\n";
echo '</Style>' . "\n";
echo '</Styles>' . "\n";

echo '<Worksheet ss:Name="商标案件导入模板">' . "\n";

// 设置列宽
echo '<Table>' . "\n";
$column_count = count($headers);
for ($i = 0; $i < $column_count; $i++) {
    // 根据字段内容设置不同的列宽
    $width = 120; // 默认宽度
    $header_text = array_values($headers)[$i];

    if (strpos($header_text, 'ID') !== false) {
        $width = 80; // ID字段较窄
    } elseif (strpos($header_text, '日期') !== false || strpos($header_text, '日') !== false) {
        $width = 100; // 日期字段
    } elseif (strpos($header_text, '名称') !== false || strpos($header_text, '备注') !== false) {
        $width = 150; // 名称和备注字段较宽
    } elseif (strpos($header_text, '业务人员') !== false || strpos($header_text, '业务助理') !== false) {
        $width = 180; // 多选字段更宽
    }

    echo '<Column ss:Width="' . $width . '"/>' . "\n";
}

// 输出标题行
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="TitleStyle">' . "\n";
echo '<Data ss:Type="String">模板填写说明：</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 输出使用说明提示行（两列显示）
$tips = [
    '1、红色表头为必填项，必须填写数据',
    '2、部分字段需要填写当前系统已有的数据，请参考下方对照表',
    '3、日期格式必须为：YYYY-MM-DD（如：2025-01-01）',
    '4、多个ID用逗号分隔（如业务人员ID：1,2,3）',
    '5、商标类别可多选，用逗号分隔（如：1(化工原料),2(颜料油漆)）',
    '6、是否主案字段：1表示是，0表示否，默认为0',
    '7、商标图片需要导入后在编辑页面单独上传',
    '8、客户ID和客户名称(中)必须至少填写一个，优先使用客户ID，如果客户不存在会自动创建',
];

// 将提示信息两两组合在一行中显示
for ($i = 0; $i < count($tips); $i += 2) {
    echo '<Row ss:Height="25">' . "\n";

    // 将两条提示合并在一个单元格中，用制表符分隔
    $combined_tips = $tips[$i];
    if (isset($tips[$i + 1])) {
        $combined_tips .= '        ' . $tips[$i + 1]; // 用多个空格作为间隔
    }

    echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="TipsStyle">' . "\n";
    echo '<Data ss:Type="String">' . htmlspecialchars($combined_tips) . '</Data>' . "\n";
    echo '</Cell>' . "\n";

    echo '</Row>' . "\n";
}

// 空行分隔
echo '<Row ss:Height="10">' . "\n";
for ($i = 0; $i < $column_count; $i++) {
    echo '<Cell><Data ss:Type="String"></Data></Cell>' . "\n";
}
echo '</Row>' . "\n";

// 数据对照标题
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="TitleStyle">' . "\n";
echo '<Data ss:Type="String">当前系统数据如下（id相关的字段需要填写对应的id数字，id-名称，如：1-张三）【数据截止到' . $current_date . '】：</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 部门信息
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$dept_info = '承办部门ID对照：';
foreach ($relatedData['departments'] as $dept) {
    $dept_info .= $dept['id'] . '-' . htmlspecialchars($dept['dept_name']) . '，';
}
$dept_info = rtrim($dept_info, '，');
echo '<Data ss:Type="String">' . $dept_info . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 客户信息
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$customer_info = '客户ID对照：';
foreach ($relatedData['customers'] as $customer) {
    $customer_info .= $customer['id'] . '-' . htmlspecialchars($customer['customer_name_cn']) . '，';
}
$customer_info = rtrim($customer_info, '，');
echo '<Data ss:Type="String">' . $customer_info . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 客户名称参考信息
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$customer_name_info = '客户名称参考：';
foreach ($relatedData['customers'] as $customer) {
    $customer_name_info .= htmlspecialchars($customer['customer_name_cn']) . '，';
}
$customer_name_info = rtrim($customer_name_info, '，');
echo '<Data ss:Type="String">' . $customer_name_info . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 用户信息
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$user_info = '用户ID对照（处理人、项目负责人、业务人员、业务助理）：';
foreach ($relatedData['users'] as $user) {
    $user_info .= $user['id'] . '-' . htmlspecialchars($user['real_name']) . '，';
}
$user_info = rtrim($user_info, '，');
echo '<Data ss:Type="String">' . $user_info . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 处理事项选项
echo '<Row ss:Height="40">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$process_items_info = '处理事项选项：' . implode('，', $optionsData['process_items']);
echo '<Data ss:Type="String">' . htmlspecialchars($process_items_info) . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 案件类型选项
echo '<Row ss:Height="25">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$case_types_info = '案件类型选项：' . implode('，', $optionsData['case_types']);
echo '<Data ss:Type="String">' . htmlspecialchars($case_types_info) . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 商标类别选项
echo '<Row ss:Height="40">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$trademark_classes_info = '商标类别选项：' . implode('，', $optionsData['trademark_classes']);
echo '<Data ss:Type="String">' . htmlspecialchars($trademark_classes_info) . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 商标种类选项
echo '<Row ss:Height="25">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$trademark_types_info = '商标种类选项：' . implode('，', $optionsData['trademark_types']);
echo '<Data ss:Type="String">' . htmlspecialchars($trademark_types_info) . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 空行分隔
echo '<Row ss:Height="15">' . "\n";
for ($i = 0; $i < $column_count; $i++) {
    echo '<Cell><Data ss:Type="String"></Data></Cell>' . "\n";
}
echo '</Row>' . "\n";

// 输出表头
echo '<Row ss:Height="35">' . "\n";
foreach ($headers as $header) {
    // 判断是否为必填字段（包含*号）
    $isRequired = strpos($header, '*') !== false;
    $styleID = $isRequired ? 'RequiredHeaderStyle' : 'HeaderStyle';

    echo '<Cell ss:StyleID="' . $styleID . '">' . "\n";
    echo '<Data ss:Type="String">' . htmlspecialchars($header) . '</Data>' . "\n";
    echo '</Cell>' . "\n";
}
echo '</Row>' . "\n";

// 输出100行空白数据行
for ($i = 0; $i < 100; $i++) {
    echo '<Row ss:Height="25">' . "\n";
    foreach ($headers as $key => $header) {
        echo '<Cell ss:StyleID="DataStyle">' . "\n";
        echo '<Data ss:Type="String"></Data>' . "\n";
        echo '</Cell>' . "\n";
    }
    echo '</Row>' . "\n";
}

echo '</Table>' . "\n";
echo '</Worksheet>' . "\n";
echo '</Workbook>' . "\n";
exit;
