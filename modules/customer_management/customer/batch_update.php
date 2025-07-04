<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();

// 设置错误处理，防止PHP Warning影响JSON输出
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'batch_update') {
    echo json_encode(['success' => false, 'message' => '无效的请求']);
    exit;
}

if (!isset($_FILES['update_file']) || $_FILES['update_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '文件上传失败']);
    exit;
}

$file = $_FILES['update_file'];

// 检查文件类型
$allowed_types = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'text/csv',
    'text/plain',
    'application/csv'
];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => '不支持的文件格式，请上传Excel或CSV文件']);
    exit;
}

// 检查文件大小（10MB）
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => '文件大小超过限制（10MB）']);
    exit;
}

try {
    // 读取Excel文件
    $data = parseExcelFile($file['tmp_name']);

    if (empty($data)) {
        echo json_encode(['success' => false, 'message' => 'Excel文件为空或格式错误']);
        exit;
    }

    // 查找真正的表头行（跳过信息提示行）
    $header_row_index = -1;
    $headers = [];

    foreach ($data as $index => $row) {
        // 调试信息：记录每一行的内容
        error_log("第{$index}行数据: " . json_encode($row, JSON_UNESCAPED_UNICODE));

        // 跳过空行
        if (empty(array_filter($row, function ($cell) {
            return trim($cell) !== '';
        }))) {
            error_log("第{$index}行为空行，跳过");
            continue;
        }

        // 跳过信息提示行（包含"对照"、"选项"、"当前系统数据"等关键词）
        $first_cell = trim($row[0] ?? '');
        if (
            strpos($first_cell, '对照') !== false ||
            strpos($first_cell, '选项') !== false ||
            strpos($first_cell, 'ID对照') !== false ||
            strpos($first_cell, '关联表') !== false ||
            strpos($first_cell, '当前系统数据') !== false ||
            strpos($first_cell, '数据截止') !== false ||
            strpos($first_cell, '说明') !== false ||
            strpos($first_cell, '提示') !== false ||
            strpos($first_cell, '注意') !== false ||
            strpos($first_cell, '请在下方') !== false ||
            strpos($first_cell, '填写说明') !== false ||
            strlen($first_cell) > 50 // 跳过过长的说明文字
        ) {
            error_log("第{$index}行为说明行，跳过: '{$first_cell}'");
            continue;
        }

        // 检查是否为表头行（包含"id"或"客户名称"等关键字段）
        $row_text = implode('', $row);
        if (
            (strpos($row_text, 'id') !== false && strpos($row_text, '客户名称') !== false) ||
            (strpos($row_text, '客户编号') !== false && strpos($row_text, '客户名称') !== false) ||
            (strpos($row_text, '案件类型-专利') !== false && strpos($row_text, '案件类型-商标') !== false) ||
            (strpos($row_text, '案件类型-版权') !== false && strpos($row_text, '客户名称') !== false)
        ) {
            error_log("第{$index}行识别为表头行: " . json_encode($row, JSON_UNESCAPED_UNICODE));
            $header_row_index = $index;
            $headers = $row;
            break;
        } else {
            error_log("第{$index}行不符合表头条件: '{$row_text}'");
        }
    }

    if ($header_row_index === -1 || empty($headers)) {
        echo json_encode(['success' => false, 'message' => '无法找到表头行，请确保文件包含正确的表头']);
        exit;
    }

    // 获取表头映射
    $header_map = getHeaderMapping($headers);

    if (empty($header_map)) {
        echo json_encode(['success' => false, 'message' => '无法识别Excel表头格式，请使用提供的模板']);
        exit;
    }

    // 检查是否包含id字段
    if (!in_array('id', $header_map)) {
        echo json_encode(['success' => false, 'message' => '批量修改必须包含id字段，请使用"下载当前客户信息"功能获取包含id的文件']);
        exit;
    }

    // 获取数据行（跳过表头和信息行）
    $rows = array_slice($data, $header_row_index + 1);

    // 过滤空行
    $rows = array_filter($rows, function ($row) {
        return !empty(array_filter($row, function ($cell) {
            return trim($cell) !== '';
        }));
    });

    if (empty($rows)) {
        echo json_encode(['success' => false, 'message' => '没有找到有效的数据行']);
        exit;
    }

    // 批量修改数据
    $result = batchUpdateCustomer($pdo, $rows, $header_map, $_SESSION['user_id']);

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '修改失败: ' . $e->getMessage()]);
}

/**
 * 解析Excel文件（支持CSV和Excel XML格式）
 */
function parseExcelFile($file_path)
{
    $data = [];
    $file_content = file_get_contents($file_path);

    // 检查是否为Excel XML格式
    if (strpos($file_content, '<Workbook') !== false || strpos($file_content, '<Table>') !== false) {
        // 解析Excel XML格式
        $data = parseExcelXml($file_content);
    } else {
        // 尝试以CSV格式读取
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            // 检测BOM并跳过
            $bom = fread($handle, 3);
            if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
                rewind($handle);
            }

            while (($row = fgetcsv($handle, 0, ',')) !== FALSE) {
                $data[] = $row;
            }
            fclose($handle);
        }
    }

    return $data;
}

/**
 * 解析Excel XML格式
 */
function parseExcelXml($xml_content)
{
    $data = [];

    // 使用DOMDocument进行更准确的XML解析
    if (class_exists('DOMDocument')) {
        try {
            $dom = new DOMDocument();
            @$dom->loadXML($xml_content); // 使用@抑制XML格式警告

            $rows = $dom->getElementsByTagName('Row');
            foreach ($rows as $row) {
                $row_data = [];
                $cells = $row->childNodes;

                $current_col = 0;
                foreach ($cells as $node) {
                    if ($node->nodeType !== XML_ELEMENT_NODE || $node->nodeName !== 'Cell') {
                        continue;
                    }

                    // 确保$node是DOMElement类型
                    if (!($node instanceof DOMElement)) {
                        continue;
                    }

                    $cell = $node; // 现在$cell确定是DOMElement类型

                    // 检查Cell是否有Index属性，如果有则可能跳过了一些列
                    $index_attr = $cell->getAttribute('ss:Index');
                    if ($index_attr) {
                        $target_col = intval($index_attr) - 1; // Excel索引从1开始，数组从0开始
                        // 填充跳过的列
                        while ($current_col < $target_col) {
                            $row_data[] = '';
                            $current_col++;
                        }
                    }

                    // 获取Data元素的内容
                    $data_elements = $cell->getElementsByTagName('Data');
                    if ($data_elements->length > 0) {
                        $row_data[] = trim($data_elements->item(0)->nodeValue);
                    } else {
                        $row_data[] = '';
                    }
                    $current_col++;
                }

                if (!empty($row_data)) {
                    $data[] = $row_data;
                }
            }
        } catch (Exception $e) {
            // DOMDocument解析失败，使用正则表达式备用方案
            error_log("DOMDocument解析失败: " . $e->getMessage());
            $data = parseExcelXmlRegex($xml_content);
        }
    } else {
        // DOMDocument不可用，使用正则表达式
        $data = parseExcelXmlRegex($xml_content);
    }

    return $data;
}

/**
 * 使用正则表达式解析Excel XML的备用方案
 */
function parseExcelXmlRegex($xml_content)
{
    $data = [];

    if (preg_match_all('/<Row[^>]*>(.*?)<\/Row>/s', $xml_content, $row_matches)) {
        foreach ($row_matches[1] as $row_content) {
            $row_data = [];

            // 更精确地匹配Cell元素
            if (preg_match_all('/<Cell[^>]*?(?:ss:Index="(\d+)"[^>]*)?(?:\/>|>(.*?)<\/Cell>)/s', $row_content, $cell_matches, PREG_SET_ORDER)) {
                $current_col = 0;

                foreach ($cell_matches as $match) {
                    $index = isset($match[1]) && $match[1] ? intval($match[1]) - 1 : $current_col;

                    // 填充跳过的列
                    while ($current_col < $index) {
                        $row_data[] = '';
                        $current_col++;
                    }

                    $cell_content = isset($match[2]) ? $match[2] : '';
                    if (preg_match('/<Data[^>]*>(.*?)<\/Data>/s', $cell_content, $data_matches)) {
                        $row_data[] = html_entity_decode(trim($data_matches[1]), ENT_QUOTES, 'UTF-8');
                    } else {
                        $row_data[] = '';
                    }
                    $current_col++;
                }
            }

            if (!empty($row_data)) {
                $data[] = $row_data;
            }
        }
    }

    return $data;
}

/**
 * 获取表头映射
 */
function getHeaderMapping($headers)
{
    $map = [];

    // 定义表头映射关系（客户字段）
    $header_mapping = [
        'id' => 'id',
        '客户编号' => 'customer_code',
        '客户编号(可编辑，会自动生成)' => 'customer_code',
        '客户名称(中)' => 'customer_name_cn',
        '客户名称(中)*' => 'customer_name_cn',
        '案件类型-专利(1是0否)' => 'case_type_patent',
        '案件类型-专利(1是0否)*' => 'case_type_patent',
        '案件类型-商标(1是0否)' => 'case_type_trademark',
        '案件类型-商标(1是0否)*' => 'case_type_trademark',
        '案件类型-版权(1是0否)' => 'case_type_copyright',
        '案件类型-版权(1是0否)*' => 'case_type_copyright',
        '客户名称(英)' => 'customer_name_en',
        '公司负责人' => 'company_leader',
        '邮件' => 'email',
        '业务人员ID' => 'business_staff_id',
        '内部签署人' => 'internal_signer',
        '外部签署人' => 'external_signer',
        '流程人员ID' => 'process_staff_id',
        '客户等级' => 'customer_level',
        '地址' => 'address',
        '开户银行' => 'bank_name',
        '成交状态' => 'deal_status',
        '项目负责人ID' => 'project_leader_id',
        '备注' => 'remark',
        '电话' => 'phone',
        '所属行业' => 'industry',
        '创建人' => 'creator',
        '内部签署人电话' => 'internal_signer_phone',
        '外部签署人电话' => 'external_signer_phone',
        '账单地址' => 'billing_address',
        '信管等级' => 'credit_level',
        '英文地址' => 'address_en',
        '银行账号' => 'bank_account',
        '客户代码' => 'customer_id_code',
        '新申请配案主管ID' => 'new_case_manager_id',
        '传真' => 'fax',
        '客户来源' => 'customer_source',
        '内部签署人邮箱' => 'internal_signer_email',
        '外部签署人邮箱' => 'external_signer_email',
        '收货地址' => 'delivery_address',
        '客户签约日期' => 'sign_date',
        '客户签约日期(YYYY-MM-DD)' => 'sign_date',
        '本所业务公共邮箱' => 'public_email',
        '纳税人识别号' => 'tax_id'
    ];

    // 调试信息：记录原始表头
    error_log("原始表头: " . json_encode($headers, JSON_UNESCAPED_UNICODE));

    foreach ($headers as $index => $header) {
        $original_header = trim($header);

        // 首先尝试直接匹配原始表头
        if (isset($header_mapping[$original_header])) {
            $map[$index] = $header_mapping[$original_header];
            error_log("直接映射成功: 列{$index} '{$original_header}' -> '{$header_mapping[$original_header]}'");
            continue;
        }

        // 清理表头：移除星号、括号内容和多余空格
        $clean_header = preg_replace('/\*|\(.*?\)|（.*?）/', '', trim($header));
        $clean_header = trim($clean_header);

        // 调试信息：记录表头清理过程
        if ($header !== $clean_header) {
            error_log("表头清理: '{$header}' -> '{$clean_header}'");
        }

        if (isset($header_mapping[$clean_header])) {
            $map[$index] = $header_mapping[$clean_header];
            error_log("清理后映射成功: 列{$index} '{$clean_header}' -> '{$header_mapping[$clean_header]}'");
        } else {
            error_log("映射失败: 列{$index} 原始='{$original_header}' 清理后='{$clean_header}' 无匹配");
        }
    }

    // 调试信息：记录最终映射结果
    error_log("最终字段映射: " . json_encode($map, JSON_UNESCAPED_UNICODE));

    return $map;
}

/**
 * 批量修改客户
 */
function batchUpdateCustomer($pdo, $rows, $header_map, $user_id)
{
    $success_count = 0;  // 实际更新的客户数量
    $processed_count = 0;  // 处理的客户总数量（包括无需更新的）
    $error_count = 0;
    $errors = [];

    // 预加载部门和客户数据
    $departments = getDepartmentMap($pdo);
    $customers = getCustomerMap($pdo);
    $users = getUserMap($pdo);

    // 调试信息：记录字段映射
    error_log("开始批量修改，字段映射: " . json_encode($header_map, JSON_UNESCAPED_UNICODE));

    // 预加载所有需要更新的客户数据（性能优化）
    $customer_ids = [];
    foreach ($rows as $row) {
        $temp_data = [];
        foreach ($header_map as $col_index => $field_name) {
            if ($field_name === 'id') {
                $value = isset($row[$col_index]) ? trim($row[$col_index]) : '';
                if (!empty($value) && is_numeric($value)) {
                    $customer_ids[] = intval($value);
                }
                break;
            }
        }
    }

    $current_customers_data = [];
    if (!empty($customer_ids)) {
        $current_customers_data = getCurrentCustomerDataBatch($pdo, $customer_ids);
        error_log("预加载了 " . count($current_customers_data) . " 个客户的当前数据");
    }

    $pdo->beginTransaction();

    try {
        foreach ($rows as $row_index => $row) {
            $line_number = $row_index + 2; // Excel行号（从2开始，因为第1行是表头）

            try {
                // 调试信息：记录原始行数据
                error_log("第{$line_number}行原始数据: " . json_encode($row, JSON_UNESCAPED_UNICODE));

                // 解析行数据
                $customer_data = parseCustomerRow($pdo, $row, $header_map, $departments, $customers, $users);

                // 调试信息：记录解析后的数据
                error_log("第{$line_number}行解析后数据: " . json_encode($customer_data, JSON_UNESCAPED_UNICODE));

                // 验证必须包含id字段
                if (empty($customer_data['id']) || !is_numeric($customer_data['id'])) {
                    $errors[] = "第{$line_number}行: 缺少有效的id字段，无法确定要修改的客户";
                    $error_count++;
                    continue;
                }

                $customer_id = intval($customer_data['id']);

                // 检查客户是否存在
                $stmt = $pdo->prepare("SELECT id FROM customer WHERE id = ?");
                $stmt->execute([$customer_id]);
                if (!$stmt->fetch()) {
                    $errors[] = "第{$line_number}行(客户ID:{$customer_id}): 该客户不存在";
                    $error_count++;
                    continue;
                }

                // 验证关联字段的有效性
                if (isset($customer_data['business_staff_id']) && !empty($customer_data['business_staff_id'])) {
                    $stmt = $pdo->prepare("SELECT id FROM user WHERE id = ? AND is_active = 1");
                    $stmt->execute([$customer_data['business_staff_id']]);
                    if (!$stmt->fetch()) {
                        $errors[] = "第{$line_number}行(客户ID:{$customer_id}): 业务人员ID {$customer_data['business_staff_id']} 不存在或已停用";
                        $error_count++;
                        continue;
                    }
                }

                // 验证必填字段（只验证有值的字段）
                $validation_result = validateCustomerData($customer_data, $line_number, $customer_id);
                if (!$validation_result['valid']) {
                    $errors[] = "第{$line_number}行(客户ID:{$customer_id}): " . $validation_result['message'];
                    $error_count++;
                    error_log("第{$line_number}行(客户ID:{$customer_id})验证失败: " . $validation_result['message']);
                    continue;
                }

                // 更新客户（只更新发生变化的字段）
                $current_data = isset($current_customers_data[$customer_id]) ? $current_customers_data[$customer_id] : null;
                $update_result = updateCustomerOptimized($pdo, $customer_data, $customer_id, $current_data);
                $processed_count++; // 无论是否更新，都算作已处理

                if ($update_result['updated']) {
                    $success_count++;
                    error_log("第{$line_number}行(客户ID:{$customer_id}): 成功更新了 {$update_result['changed_fields']} 个字段");
                } else {
                    // 没有字段需要更新，不计入成功数量，但记录处理状态
                    error_log("第{$line_number}行(客户ID:{$customer_id}): 没有字段需要更新");
                }
            } catch (Exception $e) {
                $customer_id_text = isset($customer_id) ? "(客户ID:{$customer_id})" : "";
                $errors[] = "第{$line_number}行{$customer_id_text}: " . $e->getMessage();
                $error_count++;
                error_log("第{$line_number}行{$customer_id_text}处理异常: " . $e->getMessage());
            }
        }

        $pdo->commit();

        // 统计性能信息
        $no_change_count = $processed_count - $success_count; // 无需更新的客户数量
        $total_processed = $processed_count + $error_count;
        $performance_info = "性能统计: 处理 {$total_processed} 行数据，实际更新 {$success_count} 个客户，无需更新 {$no_change_count} 个客户，预加载 " . count($current_customers_data) . " 个客户数据";
        error_log($performance_info);

        return [
            'success' => true,
            'success_count' => $success_count,
            'processed_count' => $processed_count,
            'no_change_count' => $no_change_count,
            'error_count' => $error_count,
            'errors' => array_slice($errors, 0, 10), // 最多返回10个错误
            'performance_info' => $performance_info
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'message' => '数据库操作失败: ' . $e->getMessage()
        ];
    }
}

/**
 * 获取部门映射
 */
function getDepartmentMap($pdo)
{
    $stmt = $pdo->prepare("SELECT id, dept_name FROM department WHERE is_active = 1");
    $stmt->execute();
    $result = [];
    while ($row = $stmt->fetch()) {
        $result[$row['dept_name']] = $row['id'];
    }
    return $result;
}

/**
 * 获取客户映射（暂不使用，保留兼容性）
 */
function getCustomerMap($pdo)
{
    return [];
}

/**
 * 获取用户映射
 */
function getUserMap($pdo)
{
    $stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active = 1");
    $stmt->execute();
    $result = [];
    while ($row = $stmt->fetch()) {
        $result[$row['real_name']] = $row['id'];
    }
    return $result;
}

/**
 * 解析客户行数据
 */
function parseCustomerRow($pdo, $row, $header_map, $departments, $customers, $users)
{
    $data = [];

    foreach ($header_map as $col_index => $field_name) {
        $value = isset($row[$col_index]) ? trim($row[$col_index]) : '';

        switch ($field_name) {
            case 'id':
                $data[$field_name] = !empty($value) && is_numeric($value) ? intval($value) : null;
                break;
            case 'business_staff_id':
            case 'process_staff_id':
            case 'project_leader_id':
            case 'new_case_manager_id':
                $data[$field_name] = !empty($value) && is_numeric($value) ? intval($value) : null;
                break;
            case 'case_type_patent':
            case 'case_type_trademark':
            case 'case_type_copyright':
                if (is_numeric($value)) {
                    $data[$field_name] = intval($value);
                } else {
                    $data[$field_name] = ($value === '是') ? 1 : 0;
                }
                break;
            case 'sign_date':
                if (!empty($value)) {
                    // 尝试多种日期格式
                    $date_formats = ['Y-m-d', 'Y/m/d', 'm/d/Y', 'd/m/Y', 'Y-m-d H:i:s'];
                    $parsed_date = null;

                    foreach ($date_formats as $format) {
                        $date_obj = DateTime::createFromFormat($format, $value);
                        if ($date_obj !== false) {
                            $parsed_date = $date_obj->format('Y-m-d');
                            break;
                        }
                    }

                    // 如果格式化失败，尝试strtotime
                    if ($parsed_date === null) {
                        $timestamp = strtotime($value);
                        if ($timestamp !== false) {
                            $parsed_date = date('Y-m-d', $timestamp);
                        }
                    }

                    $data[$field_name] = $parsed_date;
                } else {
                    $data[$field_name] = null;
                }
                break;
            default:
                // 对于文本字段，确保不会将空字符串当作有效值
                $data[$field_name] = !empty($value) ? $value : null;
                break;
        }
    }

    return $data;
}

/**
 * 验证客户数据
 */
function validateCustomerData($data, $line_number, $customer_id = null)
{
    $errors = [];

    // 批量更新时，只验证有值的字段
    // 客户名称(中)如果有值，不能为空字符串
    if (isset($data['customer_name_cn']) && $data['customer_name_cn'] === '') {
        $errors[] = "客户名称(中)不能为空字符串";
    }

    // 如果提供了案件类型字段，验证至少有一个为1
    $case_types = ['case_type_patent', 'case_type_trademark', 'case_type_copyright'];
    $has_case_type_field = false;
    $has_valid_case_type = false;

    foreach ($case_types as $case_type) {
        if (isset($data[$case_type])) {
            $has_case_type_field = true;
            if (intval($data[$case_type]) === 1) {
                $has_valid_case_type = true;
                break;
            }
        }
    }

    // 只有当Excel中包含案件类型字段时才验证
    if ($has_case_type_field && !$has_valid_case_type) {
        $errors[] = "提供的案件类型字段中至少有一个必须为1";
    }

    // 验证数值字段
    $numeric_fields = ['business_staff_id', 'process_staff_id', 'project_leader_id', 'new_case_manager_id'];
    foreach ($numeric_fields as $field) {
        if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '' && !is_numeric($data[$field])) {
            $errors[] = "字段 {$field} 必须是数字";
        }
    }

    // 如果有错误，返回所有错误信息
    if (!empty($errors)) {
        return [
            'valid' => false,
            'message' => implode('；', $errors)
        ];
    }

    return ['valid' => true];
}

/**
 * 更新客户（性能优化版：只更新发生变化的字段）
 */
function updateCustomerOptimized($pdo, $data, $customer_id, $current_data = null)
{
    // 移除id字段，因为不能更新主键
    unset($data['id']);

    // 如果没有提供当前数据，则查询获取
    if ($current_data === null) {
        $current_data = getCurrentCustomerData($pdo, $customer_id);
    }

    if (!$current_data) {
        throw new Exception("客户不存在");
    }

    // 比较数据，只更新发生变化的字段
    $changed_fields = [];
    $update_values = [];

    foreach ($data as $field => $new_value) {
        $current_value = isset($current_data[$field]) ? $current_data[$field] : null;

        // 特殊处理日期字段的比较
        if (in_array($field, ['sign_date', 'created_at'])) {
            $current_value = $current_value ? date('Y-m-d', strtotime($current_value)) : null;
            $new_value = $new_value ? date('Y-m-d', strtotime($new_value)) : null;
        }

        // 特殊处理布尔字段的比较
        if (in_array($field, ['case_type_patent', 'case_type_trademark', 'case_type_copyright'])) {
            $current_value = intval($current_value);
            $new_value = intval($new_value);
        }

        // 比较值是否发生变化（支持null值清空字段）
        if ($current_value != $new_value) {
            $changed_fields[] = "`{$field}` = ?";
            $update_values[] = $new_value; // 直接使用新值，包括null
            error_log("字段 {$field} 发生变化: '{$current_value}' -> '{$new_value}'");
        }
    }

    // 如果没有字段发生变化，直接返回
    if (empty($changed_fields)) {
        return ['updated' => false, 'message' => '没有字段发生变化'];
    }

    // 客户表没有updated_at字段，所以不添加更新时间
    $update_values[] = $customer_id;

    $sql = "UPDATE customer SET " . implode(', ', $changed_fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($update_values);

    if (!$result) {
        throw new Exception("更新客户失败");
    }

    $affected_rows = $stmt->rowCount();
    $field_count = count($changed_fields); // 不需要减1，因为没有added updated_at字段
    error_log("客户ID {$customer_id} 更新了 {$field_count} 个字段，影响行数: {$affected_rows}");

    return ['updated' => true, 'changed_fields' => $field_count];
}

/**
 * 获取当前客户数据
 */
function getCurrentCustomerData($pdo, $customer_id)
{
    $sql = "SELECT * FROM customer WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 批量获取当前客户数据（性能优化）
 */
function getCurrentCustomerDataBatch($pdo, $customer_ids)
{
    if (empty($customer_ids)) {
        return [];
    }

    $placeholders = str_repeat('?,', count($customer_ids) - 1) . '?';
    $sql = "SELECT * FROM customer WHERE id IN ({$placeholders})";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($customer_ids);

    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[$row['id']] = $row;
    }

    return $result;
}
