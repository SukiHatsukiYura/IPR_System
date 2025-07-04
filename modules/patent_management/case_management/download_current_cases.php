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

// 获取专利选项数据
function getOptionsData()
{
    // 业务类型选项
    $business_types = ['无效案件', '普通新申请', '专利转让', '著泉项目变更', 'PCT国际阶段', '复审', '香港登记案', '申请香港', '临时申请', '公众意见', '翻译', '专利检索案件', '代缴年费案件', '诉讼案件', '顾问', '专利许可备案', '海关备案', '其他', 'PCT国家阶段', '办理副本案件'];

    // 处理事项选项
    $process_items = ['请求优先审查', '开卷', '放弃', '更正', '无效答辩', '不予受理', '官文转达', '缴年费', '民事诉讼上诉', '主动补正', '专利权评价报告', '驳回', '取得检索报告', '请求无效', '翻译', '审查高速公路', '资助监控', '赔偿请求', '请求检索报告', '许可备案', '诉讼', '取得副本', '请求加速审查', '民事诉讼答辩', '取得申请号', '请求中止', '办理登记手续', '复审决定', '避免重复授权', '民事诉讼上诉答辩', '确认通知书', '请求保密审查', '结案', '补正', '请求恢复权利', '视为未提出', '手续补正', '取得证书', '年费滞纳金', '复审意见陈述', '提交IDS', '复审受理', '请求延长期限', '撤回', '请求提前公开', '处理审查意见', '口审', '诉讼举证', '项目申报', '办理DAS', '行政诉讼上诉答辩', '请求复审', '无效行政诉讼答辩', '请求退款', '提出行政诉讼', '缴费', '终止', '无效诉讼', '公众意见', '保密决定', '变更代理人申请补正通知', '请求实审', '提出民事诉讼', '请求副本', '新申请', '复议申请', '无效请求补充意见', '著泉项目变更', '行政诉讼上诉', '请求费用减缓', '视为未要求'];

    // 申请类型选项
    $application_types = ['发明', '实用新型', '外观设计', '临时申请', '再公告', '植物', '集成电路布图设计', '年费', '无效', '其他'];

    return [
        'business_types' => $business_types,
        'process_items' => $process_items,
        'application_types' => $application_types
    ];
}

// 构建查询条件
function buildWhereCondition($params)
{
    $where = [];
    $values = [];

    if (!empty($params['case_code'])) {
        $where[] = "c.case_code LIKE ?";
        $values[] = '%' . $params['case_code'] . '%';
    }

    if (!empty($params['case_name'])) {
        $where[] = "c.case_name LIKE ?";
        $values[] = '%' . $params['case_name'] . '%';
    }

    if (!empty($params['application_no'])) {
        $where[] = "c.application_no LIKE ?";
        $values[] = '%' . $params['application_no'] . '%';
    }

    if (!empty($params['business_dept_id'])) {
        $where[] = "c.business_dept_id = ?";
        $values[] = $params['business_dept_id'];
    }

    if (!empty($params['client_id'])) {
        $where[] = "c.client_id = ?";
        $values[] = $params['client_id'];
    }

    if (!empty($params['handler_id'])) {
        $where[] = "c.handler_id = ?";
        $values[] = $params['handler_id'];
    }

    if (!empty($params['application_type'])) {
        $where[] = "c.application_type = ?";
        $values[] = $params['application_type'];
    }

    if (!empty($params['business_type'])) {
        $where[] = "c.business_type = ?";
        $values[] = $params['business_type'];
    }

    if (!empty($params['case_status'])) {
        $where[] = "c.case_status = ?";
        $values[] = $params['case_status'];
    }

    if (!empty($params['is_allocated'])) {
        $where[] = "c.is_allocated = ?";
        $values[] = ($params['is_allocated'] === '是') ? 1 : 0;
    }

    if (!empty($params['application_date_start'])) {
        $where[] = "c.application_date >= ?";
        $values[] = $params['application_date_start'];
    }

    if (!empty($params['application_date_end'])) {
        $where[] = "c.application_date <= ?";
        $values[] = $params['application_date_end'];
    }

    return [
        'where' => $where ? 'WHERE ' . implode(' AND ', $where) : '',
        'values' => $values
    ];
}

// 获取专利案件数据
function getPatentCases($pdo, $params = [])
{
    $condition = buildWhereCondition($params);

    $sql = "SELECT c.id,
                c.case_code,
                c.case_name,
                c.case_name_en,
                c.client_case_code,
                c.client_id,
                cu.customer_name_cn as client_name,
                c.business_type,
                c.process_item,
                c.case_status,
                c.entrust_date,
                c.business_dept_id,
                d.dept_name as business_dept_name,
                c.business_user_ids,
                c.business_assistant_ids,
                c.handler_id,
                u.real_name as handler_name,
                c.project_leader_id,
                c.application_mode,
                c.application_type,
                c.country,
                c.case_flow,
                c.source_country,
                c.start_stage,
                c.client_status,
                c.open_date,
                c.application_no,
                c.application_date,
                c.publication_no,
                c.publication_date,
                c.announcement_no,
                c.announcement_date,
                c.certificate_no,
                c.expire_date,
                c.enter_substantive_date,
                c.is_allocated,
                c.same_day_apply,
                c.same_day_submit,
                c.agent_rule,
                c.other_options,
                c.remarks
            FROM patent_case_info c
            LEFT JOIN customer cu ON c.client_id = cu.id
            LEFT JOIN department d ON c.business_dept_id = d.id
            LEFT JOIN user u ON c.handler_id = u.id
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

// 获取案件数据
$cases = getPatentCases($pdo, $searchParams);

// 专利案件基本信息表的字段（必填字段放在左侧，id固定第一列）
$headers = [
    // 第1列：数据库主键ID（固定第一列，禁止修改）
    'id' => 'id',
    // 第2列：我方文号（可选，留空则自动生成）
    'case_code' => '我方文号(可选，留空则自动生成)',

    // 第3-8列：必填字段
    'case_name' => '案件名称*',
    'business_dept_id' => '承办部门ID*',
    'process_item' => '处理事项*',
    'client_id' => '客户ID*',
    'client_name' => '客户名称(中)*',
    'application_type' => '申请类型*',

    // 第9列及以后：可选字段（按数据库表字段顺序排列）
    'case_name_en' => '英文名称',
    'open_date' => '开卷日期(YYYY-MM-DD)',
    'client_case_code' => '客户文号',
    'business_type' => '业务类型',
    'entrust_date' => '委案日期(YYYY-MM-DD)',
    'case_status' => '案件状态',
    'same_day_apply' => '同日申请',
    'same_day_submit' => '同日递交',
    'agent_rule' => '代理费规则',
    'remarks' => '案件备注',
    'application_no' => '申请号',
    'application_date' => '申请日(YYYY-MM-DD)',
    'publication_no' => '公开号',
    'publication_date' => '公开日(YYYY-MM-DD)',
    'handler_id' => '处理人ID',
    'announcement_no' => '公告号',
    'announcement_date' => '公告日(YYYY-MM-DD)',
    'certificate_no' => '证书号',
    'expire_date' => '届满日(YYYY-MM-DD)',
    'enter_substantive_date' => '进入实审日(YYYY-MM-DD)',
    'application_mode' => '申请方式',
    'business_user_ids' => '业务人员ID(多个用逗号分隔)',
    'business_assistant_ids' => '业务助理ID(多个用逗号分隔)',
    'project_leader_id' => '项目负责人ID',
    'is_allocated' => '是否配案(1是0否，默认1)',
    'country' => '国家(地区)',
    'case_flow' => '案件流向',
    'start_stage' => '起始阶段',
    'client_status' => '客户状态',
    'source_country' => '案源国',
    'other_options' => '其他复选项'
];

// 设置Excel文件头
$current_date = date('Y年n月j日');
$filename = "专利案件信息{$current_date}.xls";
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

echo '<Worksheet ss:Name="专利案件信息">' . "\n";

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
    '2、灰色表头为禁止修改字段，修改会导致数据混乱',
    '3、部分字段需要填写当前系统已有的数据，请参考下方对照表',
    '4、日期格式必须为：YYYY-MM-DD（如：2025-01-01）',
    '5、多个ID用逗号分隔（如业务人员ID：1,2,3）',
    '6、是否配案字段：1表示是，0表示否，默认为1',
    '7、客户ID和客户名称(中)二选一填写，不能都为空',
    '8、客户ID：填写系统已有的客户ID数字',
    '9、客户名称(中)：填写客户名称，不存在则自动创建',
    '10、此文件包含当前系统中的实际案件数据，可修改后重新导入'
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

// 客户ID对照信息
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

// 业务类型选项
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$business_types_info = '业务类型选项：' . implode('，', $optionsData['business_types']);
echo '<Data ss:Type="String">' . htmlspecialchars($business_types_info) . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 处理事项选项
echo '<Row ss:Height="30">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$process_items_info = '处理事项选项：' . implode('，', $optionsData['process_items']);
echo '<Data ss:Type="String">' . htmlspecialchars($process_items_info) . '</Data>' . "\n";
echo '</Cell>' . "\n";
echo '</Row>' . "\n";

// 申请类型选项
echo '<Row ss:Height="25">' . "\n";
echo '<Cell ss:MergeAcross="' . ($column_count - 1) . '" ss:StyleID="InfoStyle">' . "\n";
$application_types_info = '申请类型选项：' . implode('，', $optionsData['application_types']);
echo '<Data ss:Type="String">' . htmlspecialchars($application_types_info) . '</Data>' . "\n";
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

// 输出案件数据
foreach ($cases as $case) {
    echo '<Row ss:Height="25">' . "\n";
    foreach ($headers as $field => $header) {
        echo '<Cell ss:StyleID="DataStyle">' . "\n";

        $value = '';

        // 客户ID字段不填充数据，留空让用户选择填写
        if ($field === 'client_id') {
            $value = '';
        } elseif (isset($case[$field])) {
            $value = $case[$field];

            // 特殊字段处理
            if ($field === 'is_allocated') {
                $value = $value ? '1' : '0';
            } elseif (in_array($field, ['entrust_date', 'open_date', 'application_date', 'publication_date', 'announcement_date', 'expire_date', 'enter_substantive_date'])) {
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
if (empty($cases)) {
    echo '<Row ss:Height="25">' . "\n";
    echo '<Cell ss:StyleID="InfoStyle" ss:MergeAcross="' . ($column_count - 1) . '">' . "\n";
    echo '<Data ss:Type="String">没有找到符合条件的专利案件数据</Data>' . "\n";
    echo '</Cell>' . "\n";
    echo '</Row>' . "\n";
}

echo '</Table>' . "\n";
echo '</Worksheet>' . "\n";
echo '</Workbook>' . "\n";
