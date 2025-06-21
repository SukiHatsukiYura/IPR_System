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

    // 使用简单的正则表达式解析XML，避免DOMDocument依赖
    // 匹配所有Row元素
    if (preg_match_all('/<Row[^>]*>(.*?)<\/Row>/s', $xml_content, $row_matches)) {
        foreach ($row_matches[1] as $row_content) {
            // 匹配Row中的所有Cell元素
            if (preg_match_all('/<Cell[^>]*>.*?<Data[^>]*>(.*?)<\/Data>.*?<\/Cell>/s', $row_content, $cell_matches)) {
                $row_data = [];
                foreach ($cell_matches[1] as $cell_value) {
                    // 解码HTML实体
                    $row_data[] = html_entity_decode(trim($cell_value), ENT_QUOTES, 'UTF-8');
                }
                if (!empty($row_data)) {
                    $data[] = $row_data;
                }
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
                $trademark_data = parseTrademarkRow($row, $header_map, $departments, $customers, $users);

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
function parseTrademarkRow($row, $header_map, $departments, $customers, $users)
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
                $data['client_id'] = isset($customers[$value]) ? $customers[$value] : null;
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
        'process_item' => '处理事项',
        'client_id' => '客户ID'
    ];

    foreach ($required_fields as $field => $label) {
        if (empty($data[$field]) || $data[$field] === null || trim($data[$field]) === '') {
            return [
                'valid' => false,
                'message' => "必填字段 {$label} 不能为空（当前值：'" . ($data[$field] ?? 'null') . "'）"
            ];
        }
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
