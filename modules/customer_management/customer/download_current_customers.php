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

// 构建查询条件
function buildWhereCondition($params)
{
    $where = [];
    $values = [];

    if (!empty($params['customer_code'])) {
        $where[] = "c.customer_code LIKE ?";
        $values[] = '%' . $params['customer_code'] . '%';
    }

    if (!empty($params['customer_name_cn'])) {
        $where[] = "c.customer_name_cn LIKE ?";
        $values[] = '%' . $params['customer_name_cn'] . '%';
    }

    if (!empty($params['customer_name_en'])) {
        $where[] = "c.customer_name_en LIKE ?";
        $values[] = '%' . $params['customer_name_en'] . '%';
    }

    if (!empty($params['business_staff_id'])) {
        $where[] = "c.business_staff_id = ?";
        $values[] = $params['business_staff_id'];
    }

    if (!empty($params['customer_level'])) {
        $where[] = "c.customer_level = ?";
        $values[] = $params['customer_level'];
    }

    if (!empty($params['deal_status'])) {
        $where[] = "c.deal_status = ?";
        $values[] = $params['deal_status'];
    }

    if (!empty($params['credit_level'])) {
        $where[] = "c.credit_level = ?";
        $values[] = $params['credit_level'];
    }

    if (!empty($params['customer_source'])) {
        $where[] = "c.customer_source = ?";
        $values[] = $params['customer_source'];
    }

    if (!empty($params['industry'])) {
        $where[] = "c.industry LIKE ?";
        $values[] = '%' . $params['industry'] . '%';
    }

    if (!empty($params['case_type_patent'])) {
        $where[] = "c.case_type_patent = ?";
        $values[] = ($params['case_type_patent'] === '是') ? 1 : 0;
    }

    if (!empty($params['case_type_trademark'])) {
        $where[] = "c.case_type_trademark = ?";
        $values[] = ($params['case_type_trademark'] === '是') ? 1 : 0;
    }

    if (!empty($params['case_type_copyright'])) {
        $where[] = "c.case_type_copyright = ?";
        $values[] = ($params['case_type_copyright'] === '是') ? 1 : 0;
    }

    if (!empty($params['sign_date_start'])) {
        $where[] = "c.sign_date >= ?";
        $values[] = $params['sign_date_start'];
    }

    if (!empty($params['sign_date_end'])) {
        $where[] = "c.sign_date <= ?";
        $values[] = $params['sign_date_end'];
    }

    return [
        'where' => $where ? 'WHERE ' . implode(' AND ', $where) : '',
        'values' => $values
    ];
}

// 获取客户数据
function getCustomers($pdo, $params = [])
{
    $condition = buildWhereCondition($params);

    $sql = "SELECT c.id,
                c.customer_code,
                c.customer_name_cn,
                c.customer_name_en,
                c.company_leader,
                c.email,
                c.business_staff_id,
                u1.real_name as business_staff_name,
                c.internal_signer,
                c.external_signer,
                c.process_staff_id,
                u2.real_name as process_staff_name,
                c.customer_level,
                c.address,
                c.bank_name,
                c.deal_status,
                c.project_leader_id,
                u3.real_name as project_leader_name,
                c.remark,
                c.case_type_patent,
                c.case_type_trademark,
                c.case_type_copyright,
                c.phone,
                c.industry,
                c.creator,
                c.created_at,
                c.internal_signer_phone,
                c.external_signer_phone,
                c.billing_address,
                c.credit_level,
                c.address_en,
                c.bank_account,
                c.customer_id_code,
                c.new_case_manager_id,
                u4.real_name as new_case_manager_name,
                c.fax,
                c.customer_source,
                c.internal_signer_email,
                c.external_signer_email,
                c.delivery_address,
                c.sign_date,
                c.public_email,
                c.tax_id
            FROM customer c
            LEFT JOIN user u1 ON c.business_staff_id = u1.id
            LEFT JOIN user u2 ON c.process_staff_id = u2.id
            LEFT JOIN user u3 ON c.project_leader_id = u3.id
            LEFT JOIN user u4 ON c.new_case_manager_id = u4.id
            {$condition['where']}
            ORDER BY c.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($condition['values']);
    return $stmt->fetchAll();
}

$relatedData = getRelatedTableData($pdo);
$optionsData = getOptionsData();

// 获取搜索参数
$searchParams = [];
foreach ($_GET as $key => $value) {
    if (!empty($value) && $key !== 'page' && $key !== 'page_size') {
        $searchParams[$key] = $value;
    }
}

// 获取客户数据
$customers = getCustomers($pdo, $searchParams);

// 客户信息表的字段（必填字段放在左侧，id固定第一列）
$headers = [
    // 第1列：数据库主键ID（固定第一列，禁止修改）
    'id' => 'id',
    // 第2列：客户编号（可选，留空则自动生成）
    'customer_code' => '客户编号(可编辑，会自动生成)',

    // 第3-6列：必填字段
    'customer_name_cn' => '客户名称(中)*',
    'case_type_patent' => '案件类型-专利(1是0否)*',
    'case_type_trademark' => '案件类型-商标(1是0否)*',
    'case_type_copyright' => '案件类型-版权(1是0否)*',

    // 第7列及以后：可选字段（按数据库表字段顺序排列）
    'customer_name_en' => '客户名称(英)',
    'company_leader' => '公司负责人',
    'email' => '邮件',
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
    'phone' => '电话',
    'industry' => '所属行业(逗号分隔)',
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
$filename = "客户信息{$current_date}.xls";
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

echo '<Style ss:ID="ReadOnlyStyle">' . "\n";
echo '<Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF"/>' . "\n";
echo '<Interior ss:Color="#6C757D" ss:Pattern="Solid"/>' . "\n";
echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
echo '<Borders>' . "\n";
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
echo '</Borders>' . "\n";
echo '</Style>' . "\n";
echo '</Styles>' . "\n";

echo '<Worksheet ss:Name="客户信息">' . "\n";

// 设置列宽
echo '<Table>' . "\n";
$column_count = count($headers);
for ($i = 0; $i < $column_count; $i++) {
    // 根据字段内容设置不同的列宽
    $width = 120; // 默认宽度
    $header_text = array_values($headers)[$i];

    if ($header_text === 'id') {
        $width = 60; // id主键字段最窄
    } elseif (strpos($header_text, 'ID') !== false) {
        $width = 80; // ID字段较窄
    } elseif (strpos($header_text, '日期') !== false || strpos($header_text, '日') !== false) {
        $width = 100; // 日期字段
    } elseif (strpos($header_text, '名称') !== false || strpos($header_text, '地址') !== false || strpos($header_text, '备注') !== false) {
        $width = 150; // 名称、地址和备注字段较宽
    } elseif (strpos($header_text, '行业') !== false) {
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
    '2、灰色表头为禁止修改字段，修改会导致数据混乱',
    '3、部分字段需要填写当前系统已有的数据，请参考下方对照表',
    '4、日期格式必须为：YYYY-MM-DD（如：2025-01-01）',
    '5、多个行业用逗号分隔（如：制造业,互联网）',
    '6、案件类型字段：1表示是，0表示否，3个案件类型至少有一个为1',
    '7、客户编号可留空，系统会自动生成',
    '8、业务人员ID：填写系统已有的用户ID数字',
    '9、客户等级：一般客户/重要客户/潜在客户/个人/企业/中介',
    '10、此文件包含当前系统中的实际客户数据，可修改后重新导入'
];

// 将提示信息两两组合在一行中显示
for ($i = 0; $i < count($tips); $i += 2) {
    echo '<Row ss:Height="25">' . "\n";

    // 将两条提示合并在一个单元格中，用多个空格分隔
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

// 用户信息（业务人员、流程人员、项目负责人、配案主管）
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$user_info = '用户ID对照（业务人员、流程人员、项目负责人、配案主管）：';
foreach ($relatedData['users'] as $user) {
    $user_info .= $user['id'] . '-' . htmlspecialchars($user['real_name']) . '，';
}
$user_info = rtrim($user_info, '，');
echo '<Data ss:Type="String">' . $user_info . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

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

// 信用等级选项
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$credit_levels_info = '信用等级选项：' . implode('，', $optionsData['credit_levels']);
echo '<Data ss:Type="String">' . htmlspecialchars($credit_levels_info) . '</Data>' . "\n";
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
echo '<Row ss:Height="25">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$industries_info = '所属行业选项：' . implode('，', $optionsData['industries']);
echo '<Data ss:Type="String">' . htmlspecialchars($industries_info) . '</Data>' . "\n";
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
foreach ($headers as $field => $header) {
    // 判断是否为必填字段或禁止修改字段
    if ($field === 'id') {
        // id字段禁止修改
        $styleID = 'ReadOnlyStyle';
    } elseif (strpos($header, '*') !== false) {
        // 必填字段
        $styleID = 'RequiredHeaderStyle';
    } else {
        // 普通字段
        $styleID = 'HeaderStyle';
    }

    echo '<Cell ss:StyleID="' . $styleID . '">' . "\n";
    echo '<Data ss:Type="String">' . htmlspecialchars($header) . '</Data>' . "\n";
    echo '</Cell>' . "\n";
}
echo '</Row>' . "\n";

// 输出客户数据
foreach ($customers as $customer) {
    echo '<Row ss:Height="25">' . "\n";
    foreach ($headers as $field => $header) {
        echo '<Cell ss:StyleID="DataStyle">' . "\n";

        $value = '';

        if (isset($customer[$field])) {
            $value = $customer[$field];

            // 特殊字段处理
            if ($field === 'case_type_patent' || $field === 'case_type_trademark' || $field === 'case_type_copyright') {
                $value = $value ? '1' : '0';
            } elseif ($field === 'sign_date' || $field === 'created_at') {
                // 日期字段格式化
                if ($value && $value !== '0000-00-00') {
                    $value = date('Y-m-d', strtotime($value));
                } else {
                    $value = '';
                }
            }
        }

        echo '<Data ss:Type="String">' . htmlspecialchars($value) . '</Data>' . "\n";
        echo '</Cell>' . "\n";
    }
    echo '</Row>' . "\n";
}

// 如果没有数据，输出提示行
if (empty($customers)) {
    echo '<Row ss:Height="25">' . "\n";
    echo '<Cell ss:StyleID="InfoStyle" ss:MergeAcross="' . ($column_count - 1) . '">' . "\n";
    echo '<Data ss:Type="String">没有找到符合条件的客户数据</Data>' . "\n";
    echo '</Cell>' . "\n";
    echo '</Row>' . "\n";
}

echo '</Table>' . "\n";
echo '</Worksheet>' . "\n";
echo '</Workbook>' . "\n";
