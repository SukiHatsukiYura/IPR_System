<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'batch_import') {
    echo json_encode(['success' => false, 'message' => '无效的请求']);
    exit;
}

if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '文件上传失败']);
    exit;
}

$file = $_FILES['import_file'];

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
        // 跳过空行
        if (empty(array_filter($row, function ($cell) {
            return trim($cell) !== '';
        }))) {
            continue;
        }

        // 跳过信息提示行（包含"对照"、"选项"等关键词）
        $first_cell = trim($row[0] ?? '');
        if (
            strpos($first_cell, '对照') !== false ||
            strpos($first_cell, '选项') !== false ||
            strpos($first_cell, 'ID对照') !== false ||
            strpos($first_cell, '关联表') !== false
        ) {
            continue;
        }

        // 检查是否为表头行（包含"我方文号"或"商标名称"等关键字段）
        $row_text = implode('', $row);
        if (
            strpos($row_text, '我方文号') !== false ||
            strpos($row_text, '商标名称') !== false ||
            strpos($row_text, '承办部门') !== false
        ) {
            $header_row_index = $index;
            $headers = $row;
            break;
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

    // 批量导入数据
    $result = batchImportTrademarks($pdo, $rows, $header_map, $_SESSION['user_id']);

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '导入失败: ' . $e->getMessage()]);
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

    // 定义表头映射关系（支持ID字段和名称字段）
    $header_mapping = [
        '我方文号' => 'case_code',
        '商标名称' => 'case_name',
        '英文名称' => 'case_name_en',
        '申请号' => 'application_no',
        '承办部门' => 'business_dept_name', // 向后兼容
        '承办部门ID' => 'business_dept_id', // 新格式
        '商标类别' => 'trademark_class',
        '初审公告日' => 'initial_publication_date',
        '初审公告期' => 'initial_publication_period',
        '客户名称' => 'client_name', // 向后兼容
        '客户名称(中)' => 'client_name', // 新格式：客户名称
        '客户ID' => 'client_id', // 新格式
        '案件类型' => 'case_type',
        '业务类型' => 'business_type',
        '委案日期' => 'entrust_date',
        '案件状态' => 'case_status',
        '处理事项' => 'process_item',
        '案源国' => 'source_country',
        '商标说明' => 'trademark_description',
        '其它名称' => 'other_name',
        '申请日' => 'application_date',
        '业务人员' => 'business_user_names', // 向后兼容
        '业务人员ID' => 'business_user_ids', // 新格式
        '业务助理' => 'business_assistant_names', // 向后兼容
        '业务助理ID' => 'business_assistant_ids', // 新格式
        '商标种类' => 'trademark_type',
        '初审公告号' => 'initial_publication_no',
        '注册号' => 'registration_no',
        '国家' => 'country',
        '国家(地区)' => 'country', // 兼容不同写法
        '案件流向' => 'case_flow',
        '申请方式' => 'application_mode',
        '开卷日期' => 'open_date',
        '客户文号' => 'client_case_code',
        '获批日' => 'approval_date',
        '备注' => 'remarks',
        '是否主案' => 'is_main_case',
        '注册公告日' => 'registration_publication_date',
        '注册公告期' => 'registration_publication_period',
        '客户状态' => 'client_status',
        '续展日' => 'renewal_date',
        '终止日' => 'expire_date'
    ];

    // 调试信息：记录原始表头
    error_log("原始表头: " . json_encode($headers, JSON_UNESCAPED_UNICODE));

    foreach ($headers as $index => $header) {
        // 清理表头：移除星号、括号内容和多余空格
        $clean_header = preg_replace('/\*|\(.*?\)|（.*?）/', '', trim($header));
        $clean_header = trim($clean_header);

        // 调试信息：记录表头清理过程
        if ($header !== $clean_header) {
            error_log("表头清理: '{$header}' -> '{$clean_header}'");
        }

        if (isset($header_mapping[$clean_header])) {
            $map[$index] = $header_mapping[$clean_header];
            error_log("映射成功: 列{$index} '{$clean_header}' -> '{$header_mapping[$clean_header]}'");
        } else {
            error_log("映射失败: 列{$index} '{$clean_header}' 无匹配");
        }
    }

    // 调试信息：记录最终映射结果
    error_log("最终字段映射: " . json_encode($map, JSON_UNESCAPED_UNICODE));

    return $map;
}

/**
 * 批量导入商标案件
 */
function batchImportTrademarks($pdo, $rows, $header_map, $user_id)
{
    $success_count = 0;
    $error_count = 0;
    $errors = [];

    // 预加载部门和客户数据
    $departments = getDepartmentMap($pdo);
    $customers = getCustomerMap($pdo);
    $users = getUserMap($pdo);

    // 调试信息：记录字段映射
    error_log("开始批量导入，字段映射: " . json_encode($header_map, JSON_UNESCAPED_UNICODE));

    $pdo->beginTransaction();

    try {
        foreach ($rows as $row_index => $row) {
            $line_number = $row_index + 2; // Excel行号（从2开始，因为第1行是表头）

            try {
                // 调试信息：记录原始行数据
                error_log("第{$line_number}行原始数据: " . json_encode($row, JSON_UNESCAPED_UNICODE));

                // 解析行数据
                $trademark_data = parseTrademarkRow($pdo, $row, $header_map, $departments, $customers, $users);

                // 调试信息：记录解析后的数据
                error_log("第{$line_number}行解析后数据: " . json_encode($trademark_data, JSON_UNESCAPED_UNICODE));

                // 验证必填字段
                $validation_result = validateTrademarkData($trademark_data, $line_number);
                if (!$validation_result['valid']) {
                    $errors[] = "第{$line_number}行: " . $validation_result['message'];
                    $error_count++;
                    error_log("第{$line_number}行验证失败: " . $validation_result['message']);
                    continue;
                }

                // 如果用户填写了客户ID，验证客户ID是否存在并检查商标案件类型
                if (isset($trademark_data['client_id']) && !empty($trademark_data['client_id']) && is_numeric($trademark_data['client_id'])) {
                    $stmt = $pdo->prepare("SELECT id, case_type_trademark FROM customer WHERE id = ?");
                    $stmt->execute([$trademark_data['client_id']]);
                    $customer = $stmt->fetch();

                    if (!$customer) {
                        $errors[] = "第{$line_number}行: 客户ID {$trademark_data['client_id']} 不存在，请填写有效的客户ID";
                        $error_count++;
                        continue;
                    }

                    // 如果客户存在但没有商标案件类型，则添加商标案件类型
                    if ($customer['case_type_trademark'] == 0) {
                        try {
                            $stmt = $pdo->prepare("UPDATE customer SET case_type_trademark = 1 WHERE id = ?");
                            $stmt->execute([$trademark_data['client_id']]);
                            error_log("为客户ID {$trademark_data['client_id']} 添加商标案件类型");
                        } catch (Exception $e) {
                            error_log("更新客户ID {$trademark_data['client_id']} 商标案件类型失败: " . $e->getMessage());
                            // 不抛出异常，因为客户存在，只是案件类型更新失败
                        }
                    }
                }

                // 检查是否存在重复案件编号（只在用户提供了案件编号时检查）
                if (!empty($trademark_data['case_code'])) {
                    $existing_id = checkExistingCase($pdo, $trademark_data['case_code']);
                    if ($existing_id) {
                        $errors[] = "第{$line_number}行: 案件编号 {$trademark_data['case_code']} 已存在";
                        $error_count++;
                        continue;
                    }
                }

                // 插入新案件（如果没有提供案件编号，会在insertTrademarkCase中自动生成）
                insertTrademarkCase($pdo, $trademark_data, $user_id);
                $success_count++;
            } catch (Exception $e) {
                $errors[] = "第{$line_number}行: " . $e->getMessage();
                $error_count++;
                error_log("第{$line_number}行处理异常: " . $e->getMessage());
            }
        }

        $pdo->commit();

        return [
            'success' => true,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'errors' => array_slice($errors, 0, 10) // 最多返回10个错误
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
 * 获取客户映射
 */
function getCustomerMap($pdo)
{
    $stmt = $pdo->prepare("SELECT id, customer_name_cn FROM customer");
    $stmt->execute();
    $result = [];
    while ($row = $stmt->fetch()) {
        $result[$row['customer_name_cn']] = $row['id'];
    }
    return $result;
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
 * 解析商标行数据
 */
function parseTrademarkRow($pdo, $row, $header_map, $departments, $customers, $users)
{
    $data = [];

    foreach ($header_map as $col_index => $field_name) {
        $value = isset($row[$col_index]) ? trim($row[$col_index]) : '';

        switch ($field_name) {
                // 向后兼容：名称字段转换为ID
            case 'business_dept_name':
                $data['business_dept_id'] = isset($departments[$value]) ? $departments[$value] : null;
                break;
            case 'client_name':
                // 支持客户名称，如果不存在则自动创建
                if (!empty($value)) {
                    $data['client_id_from_name'] = getOrCreateCustomer($pdo, $value);
                }
                break;
            case 'handler_name':
                $data['handler_id'] = isset($users[$value]) ? $users[$value] : null;
                break;
            case 'project_leader_name':
                $data['project_leader_id'] = isset($users[$value]) ? $users[$value] : null;
                break;
            case 'business_user_names':
                // 处理多个业务人员（逗号分隔）
                if (!empty($value)) {
                    $names = array_map('trim', explode(',', $value));
                    $user_ids = [];
                    foreach ($names as $name) {
                        if (isset($users[$name])) {
                            $user_ids[] = $users[$name];
                        }
                    }
                    $data['business_user_ids'] = !empty($user_ids) ? implode(',', $user_ids) : null;
                } else {
                    $data['business_user_ids'] = null;
                }
                break;
            case 'business_assistant_names':
                // 处理多个业务助理（逗号分隔）
                if (!empty($value)) {
                    $names = array_map('trim', explode(',', $value));
                    $user_ids = [];
                    foreach ($names as $name) {
                        if (isset($users[$name])) {
                            $user_ids[] = $users[$name];
                        }
                    }
                    $data['business_assistant_ids'] = !empty($user_ids) ? implode(',', $user_ids) : null;
                } else {
                    $data['business_assistant_ids'] = null;
                }
                break;

                // 新格式：直接使用ID字段
            case 'business_dept_id':
            case 'client_id':
            case 'handler_id':
            case 'project_leader_id':
                $data[$field_name] = !empty($value) && is_numeric($value) ? intval($value) : null;
                break;
            case 'business_user_ids':
            case 'business_assistant_ids':
                // 处理多个ID（逗号分隔）
                if (!empty($value)) {
                    $ids = array_map('trim', explode(',', $value));
                    $valid_ids = [];
                    foreach ($ids as $id) {
                        if (is_numeric($id)) {
                            $valid_ids[] = intval($id);
                        }
                    }
                    $data[$field_name] = !empty($valid_ids) ? implode(',', $valid_ids) : null;
                } else {
                    $data[$field_name] = null;
                }
                break;

                // 特殊字段处理
            case 'is_main_case':
                if (is_numeric($value)) {
                    $data[$field_name] = intval($value);
                } else {
                    $data[$field_name] = ($value === '是') ? 1 : 0;
                }
                break;
            case 'application_date':
            case 'initial_publication_date':
            case 'registration_publication_date':
            case 'approval_date':
            case 'open_date':
            case 'entrust_date':
            case 'renewal_date':
            case 'expire_date':
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

    // 处理客户ID的优先级：如果同时有client_id和client_id_from_name，优先使用用户直接填写的client_id
    if (isset($data['client_id']) && !empty($data['client_id'])) {
        // 用户填写了客户ID，使用客户ID
        unset($data['client_id_from_name']);
    } elseif (isset($data['client_id_from_name'])) {
        // 用户没填客户ID但填了客户名称，使用从客户名称获取的ID
        $data['client_id'] = $data['client_id_from_name'];
        unset($data['client_id_from_name']);
    }

    return $data;
}

/**
 * 验证商标数据
 */
function validateTrademarkData($data, $line_number)
{
    // 根据商标案件的必填字段验证
    $required_fields = [
        'case_name' => '商标名称',
        'business_dept_id' => '承办部门ID',
        'process_item' => '处理事项'
    ];

    foreach ($required_fields as $field => $label) {
        if (empty($data[$field]) || $data[$field] === null || trim($data[$field]) === '') {
            return [
                'valid' => false,
                'message' => "必填字段 {$label} 不能为空（当前值：'" . ($data[$field] ?? 'null') . "'）"
            ];
        }
    }

    // 特殊验证：客户ID和客户名称必须至少填写一个
    if (empty($data['client_id']) || $data['client_id'] === null) {
        return [
            'valid' => false,
            'message' => "客户ID和客户名称(中)必须至少填写一个"
        ];
    }

    // 验证案件编号格式（如果提供了的话）
    if (!empty($data['case_code']) && !preg_match('/^[A-Za-z0-9\-_]+$/', $data['case_code'])) {
        return [
            'valid' => false,
            'message' => '案件编号格式不正确'
        ];
    }

    // 验证数值字段
    $numeric_fields = ['business_dept_id', 'client_id'];
    foreach ($numeric_fields as $field) {
        if (!empty($data[$field]) && !is_numeric($data[$field])) {
            return [
                'valid' => false,
                'message' => "字段 {$field} 必须是数字"
            ];
        }
    }

    return ['valid' => true];
}

/**
 * 检查案件是否已存在
 */
function checkExistingCase($pdo, $case_code)
{
    $stmt = $pdo->prepare("SELECT id FROM trademark_case_info WHERE case_code = ?");
    $stmt->execute([$case_code]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : null;
}

/**
 * 插入新商标案件
 */
function insertTrademarkCase($pdo, $data, $user_id)
{
    // 生成案件编号（如果为空）
    if (empty($data['case_code'])) {
        $data['case_code'] = generateCaseCode($pdo);
    }

    $fields = array_keys($data);
    $placeholders = array_fill(0, count($fields), '?');

    $sql = "INSERT INTO trademark_case_info (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));

    return $pdo->lastInsertId();
}

/**
 * 生成案件编号
 */
function generateCaseCode($pdo)
{
    $prefix = 'TM' . date('Ymd');
    $sql = "SELECT COUNT(*) FROM trademark_case_info WHERE case_code LIKE :prefix";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':prefix' => $prefix . '%']);
    $count = $stmt->fetchColumn();
    $serial = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    return $prefix . $serial;
}

/**
 * 根据客户名称获取或创建客户（商标模块）
 */
function getOrCreateCustomer($pdo, $customer_name)
{
    if (empty($customer_name)) {
        return null;
    }

    // 首先检查客户是否已存在，同时获取商标案件类型标识
    $stmt = $pdo->prepare("SELECT id, case_type_trademark FROM customer WHERE customer_name_cn = ?");
    $stmt->execute([$customer_name]);
    $existing_customer = $stmt->fetch();

    if ($existing_customer) {
        $customer_id = $existing_customer['id'];

        // 如果客户存在但没有商标案件类型，则添加商标案件类型
        if ($existing_customer['case_type_trademark'] == 0) {
            try {
                $stmt = $pdo->prepare("UPDATE customer SET case_type_trademark = 1 WHERE id = ?");
                $stmt->execute([$customer_id]);
                error_log("为现有客户添加商标案件类型: ID={$customer_id}, 名称={$customer_name}");
            } catch (Exception $e) {
                error_log("更新客户商标案件类型失败: " . $e->getMessage());
                // 不抛出异常，因为客户已存在，只是案件类型更新失败
            }
        }

        return $customer_id;
    }

    // 客户不存在，创建新客户（自动设置商标案件类型为1）
    try {
        // 生成客户编号
        $customer_code = generateCustomerCode($pdo);

        $stmt = $pdo->prepare("INSERT INTO customer (customer_code, customer_name_cn, case_type_trademark, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->execute([$customer_code, $customer_name]);

        $new_customer_id = $pdo->lastInsertId();
        error_log("自动创建新客户: ID={$new_customer_id}, 名称={$customer_name}, 编号={$customer_code}, 商标案件类型=1");

        return $new_customer_id;
    } catch (Exception $e) {
        error_log("创建客户失败: " . $e->getMessage());
        throw new Exception("创建客户失败: " . $e->getMessage());
    }
}

/**
 * 生成客户编号
 */
function generateCustomerCode($pdo)
{
    $prefix = 'KH' . date('Ymd');
    $sql = "SELECT COUNT(*) FROM customer WHERE customer_code LIKE :prefix";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':prefix' => $prefix . '%']);
    $count = $stmt->fetchColumn();
    $serial = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    return $prefix . $serial;
}
