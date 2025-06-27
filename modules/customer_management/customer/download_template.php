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

    // 获取用户数据
    $stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active = 1 ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll();
    $data['users'] = $users;

    return $data;
}

// 获取客户选项数据
function getOptionsData()
{
    // 客户等级选项
    $customer_levels = ['一般客户', '重要客户', '潜在客户', '个人', '企业', '中介'];

    // 成交状态选项
    $deal_statuses = ['否', '是'];

    // 客户来源选项
    $customer_sources = [
        '电话来访',
        '客户介绍',
        '客户',
        '立项开发',
        '媒体宣传',
        '代理商',
        '合作伙伴',
        '公开招标',
        '直邮',
        '网站',
        '回单',
        '其他',
        '2022年度商标局品牌导站（实员）建设项目'
    ];

    // 所属行业选项
    $industries = ['地产', '制造业', '互联网', '金融', '教育', '医疗', '能源', '交通', '物流', '传媒', '农业', '旅游', '政府', '军工', '其他'];

    // 信管等级选项
    $credit_levels = ['高度信誉', '一般信誉', '低信誉', '无信誉'];

    return [
        'customer_levels' => $customer_levels,
        'deal_statuses' => $deal_statuses,
        'customer_sources' => $customer_sources,
        'industries' => $industries,
        'credit_levels' => $credit_levels
    ];
}

$relatedData = getRelatedTableData($pdo);
$optionsData = getOptionsData();

// 客户信息表的字段（必填字段放在左侧，客户编号固定第一列）
$headers = [
    // 第1列：客户编号（固定第一列）
    'customer_code' => '客户编号(可选，留空则自动生成)',

    // 第2-5列：必填字段
    'customer_name_cn' => '客户名称(中)*',
    'case_type_patent' => '案件类型-专利(1是0否)*',
    'case_type_trademark' => '案件类型-商标(1是0否)*',
    'case_type_copyright' => '案件类型-版权(1是0否)*',

    // 第6列及以后：可选字段
    'customer_name_en' => '客户名称(英)',
    'company_leader' => '公司负责人',
    'email' => '邮件',
    'phone' => '电话',
    'business_staff_id' => '业务人员ID',
    'internal_signer' => '内部签署人',
    'external_signer' => '外部签署人',
    'process_staff_id' => '流程人员ID',
    'customer_level' => '客户等级',
    'address' => '地址',
    'bank_name' => '开户银行',
    'deal_status' => '成交状态',
    'project_leader_id' => '项目负责人ID',
    'remark' => '备注',
    'industry' => '所属行业(多选，逗号分隔)',
    'creator' => '创建人',
    'internal_signer_phone' => '内部签署人电话',
    'external_signer_phone' => '外部签署人电话',
    'billing_address' => '账单地址',
    'credit_level' => '信管等级',
    'address_en' => '英文地址',
    'bank_account' => '银行账号',
    'customer_id_code' => '客户代码',
    'new_case_manager_id' => '新申请配案主管ID',
    'fax' => '传真',
    'customer_source' => '客户来源',
    'internal_signer_email' => '内部签署人邮箱',
    'external_signer_email' => '外部签署人邮箱',
    'delivery_address' => '收货地址',
    'sign_date' => '客户签约日期(YYYY-MM-DD)',
    'public_email' => '本所业务公共邮箱',
    'tax_id' => '纳税人识别号'
];

// 设置Excel文件头
$current_date = date('Y年n月j日');
$filename = "客户批量导入模板{$current_date}.xls";
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

echo '<Worksheet ss:Name="客户导入模板">' . "\n";

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
    } elseif (strpos($header_text, '名称') !== false || strpos($header_text, '备注') !== false || strpos($header_text, '地址') !== false) {
        $width = 150; // 名称、备注和地址字段较宽
    } elseif (strpos($header_text, '所属行业') !== false) {
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
    '4、案件类型字段：1表示是，0表示否',
    '5、所属行业支持多选，多个用逗号分隔（如：地产,制造业,互联网）',
    '6、案件类型-专利、案件类型-商标、案件类型-版权至少填一个为1，其他可以为0'
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

// 用户信息
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$user_info = '用户ID对照（业务人员ID、流程人员ID、项目负责人ID、新申请配案主管ID）：';
foreach ($relatedData['users'] as $user) {
    $user_info .= $user['id'] . '-' . htmlspecialchars($user['real_name']) . '，';
}
$user_info = rtrim($user_info, '，');
echo '<Data ss:Type="String">' . $user_info . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// // 需要用户ID的字段提示
// echo '<Row ss:Height="25">' . "\n";
// echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="TipsStyle">' . "\n";
// echo '<Data ss:Type="String">需要用户ID的字段：业务人员ID、流程人员ID、项目负责人ID、新申请配案主管ID</Data>' . "\n";
// echo '</Cell>' . "\n";
// echo '</Row>' . "\n";

// 客户等级选项
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$customer_levels_info = '客户等级选项：' . implode('，', $optionsData['customer_levels']);
echo '<Data ss:Type="String">' . htmlspecialchars($customer_levels_info) . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 成交状态选项
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$deal_statuses_info = '成交状态选项：' . implode('，', $optionsData['deal_statuses']);
echo '<Data ss:Type="String">' . htmlspecialchars($deal_statuses_info) . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 客户来源选项
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$customer_sources_info = '客户来源选项：' . implode('，', $optionsData['customer_sources']);
echo '<Data ss:Type="String">' . htmlspecialchars($customer_sources_info) . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 所属行业选项
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$industries_info = '所属行业选项：' . implode('，', $optionsData['industries']);
echo '<Data ss:Type="String">' . htmlspecialchars($industries_info) . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 信管等级选项
echo '<Row ss:Height="25">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$credit_levels_info = '信管等级选项：' . implode('，', $optionsData['credit_levels']);
echo '<Data ss:Type="String">' . htmlspecialchars($credit_levels_info) . '</Data>' . "\n";
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
