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
            strpos($first_cell, '关联表') !== false ||
            strpos($first_cell, '说明') !== false ||
            strpos($first_cell, '提示') !== false ||
            strpos($first_cell, '请在下方') !== false
        ) {
            continue;
        }

        // 检查是否为表头行（包含"我方文号"或"案件名称"等关键字段）
        $row_text = implode('', $row);
        if (
            strpos($row_text, '我方文号') !== false ||
            strpos($row_text, '案件名称') !== false ||
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
    $result = batchImportCopyrights($pdo, $rows, $header_map, $_SESSION['user_id']);

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

    // 定义表头映射关系（版权相关）
    $header_mapping = [
        '我方文号' => 'case_code',
        '案件名称' => 'case_name',
        '案件类型' => 'case_type',
        '客户文号' => 'client_case_code',
        '客户ID' => 'client_id',
        '业务类型' => 'business_type',
        '处理事项' => 'process_item',
        '案件状态' => 'case_status',
        '委案日期' => 'entrust_date',
        '承办部门ID' => 'business_dept_id',
        '业务人员ID' => 'business_user_ids',
        '申请方式' => 'application_mode',
        '申请类型' => 'application_type',
        '国家' => 'country',
        '国家(地区)' => 'country',
        '案件流向' => 'case_flow',
        '案源国' => 'source_country',
        '开卷日期' => 'open_date',
        '受理号' => 'application_no',
        '受理日' => 'application_date',
        '登记号' => 'registration_no',
        '登记日' => 'registration_date',
        '证书号' => 'certificate_no',
        '届满日' => 'expire_date',
        '起始阶段' => 'start_stage',
        '加快级别' => 'is_expedited',
        '是否代办资助' => 'is_subsidy_agent',
        '有无材料' => 'is_material_available',
        '案件备注' => 'remarks'
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
 * 批量导入版权案件
 */
function batchImportCopyrights($pdo, $rows, $header_map, $user_id)
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
                $data = parseCopyrightRow($row, $header_map, $departments, $customers, $users);

                // 调试信息：记录解析后的数据
                error_log("第{$line_number}行解析后数据: " . json_encode($data, JSON_UNESCAPED_UNICODE));

                // 验证数据
                $validation_result = validateCopyrightData($data, $line_number);
                if (!$validation_result['valid']) {
                    $errors[] = "第{$line_number}行: " . $validation_result['message'];
                    $error_count++;
                    error_log("第{$line_number}行验证失败: " . $validation_result['message']);
                    continue;
                }

                // 检查是否已存在相同案件
                if (!empty($data['case_code']) && checkExistingCase($pdo, $data['case_code'])) {
                    $errors[] = "第{$line_number}行: 我方文号 '{$data['case_code']}' 已存在";
                    $error_count++;
                    continue;
                }

                // 插入版权案件
                if (insertCopyrightCase($pdo, $data, $user_id)) {
                    $success_count++;
                } else {
                    $errors[] = "第{$line_number}行: 数据库插入失败";
                    $error_count++;
                }
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
            'message' => '批量导入失败: ' . $e->getMessage(),
            'success_count' => 0,
            'error_count' => count($rows),
            'errors' => ['事务回滚: ' . $e->getMessage()]
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
    $departments = $stmt->fetchAll();

    $map = [];
    foreach ($departments as $dept) {
        $map[$dept['id']] = $dept['dept_name'];
        $map[$dept['dept_name']] = $dept['id'];
    }

    return $map;
}

/**
 * 获取客户映射
 */
function getCustomerMap($pdo)
{
    $stmt = $pdo->prepare("SELECT id, customer_name_cn FROM customer");
    $stmt->execute();
    $customers = $stmt->fetchAll();

    $map = [];
    foreach ($customers as $customer) {
        $map[$customer['id']] = $customer['customer_name_cn'];
        $map[$customer['customer_name_cn']] = $customer['id'];
    }

    return $map;
}

/**
 * 获取用户映射
 */
function getUserMap($pdo)
{
    $stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active = 1");
    $stmt->execute();
    $users = $stmt->fetchAll();

    $map = [];
    foreach ($users as $user) {
        $map[$user['id']] = $user['real_name'];
        $map[$user['real_name']] = $user['id'];
    }

    return $map;
}

/**
 * 解析版权案件行数据
 */
function parseCopyrightRow($row, $header_map, $departments, $customers, $users)
{
    $data = [];

    foreach ($header_map as $col_index => $field_name) {
        $value = isset($row[$col_index]) ? trim($row[$col_index]) : '';

        switch ($field_name) {
            case 'case_code':
                $data['case_code'] = !empty($value) ? $value : null;
                break;

            case 'case_name':
                $data['case_name'] = $value;
                break;

            case 'case_type':
                $data['case_type'] = !empty($value) ? $value : '版权';
                break;

            case 'client_case_code':
                $data['client_case_code'] = !empty($value) ? $value : null;
                break;

            case 'client_id':
                if (is_numeric($value)) {
                    $data['client_id'] = intval($value);
                } elseif (!empty($value) && isset($customers[$value])) {
                    $data['client_id'] = $customers[$value];
                } else {
                    $data['client_id'] = null;
                }
                break;

            case 'business_type':
                $data['business_type'] = !empty($value) ? $value : null;
                break;

            case 'process_item':
                $data['process_item'] = !empty($value) ? $value : null;
                break;

            case 'case_status':
                $data['case_status'] = !empty($value) ? $value : null;
                break;

            case 'entrust_date':
                $data['entrust_date'] = !empty($value) && $value !== '0000-00-00' ? $value : null;
                break;

            case 'business_dept_id':
                if (is_numeric($value)) {
                    $data['business_dept_id'] = intval($value);
                } elseif (!empty($value) && isset($departments[$value])) {
                    $data['business_dept_id'] = $departments[$value];
                } else {
                    $data['business_dept_id'] = null;
                }
                break;

            case 'business_user_ids':
                if (!empty($value)) {
                    $user_ids = [];
                    $user_parts = explode(',', $value);
                    foreach ($user_parts as $part) {
                        $part = trim($part);
                        if (is_numeric($part)) {
                            $user_ids[] = intval($part);
                        } elseif (isset($users[$part])) {
                            $user_ids[] = $users[$part];
                        }
                    }
                    $data['business_user_ids'] = !empty($user_ids) ? implode(',', $user_ids) : null;
                } else {
                    $data['business_user_ids'] = null;
                }
                break;

            case 'application_mode':
                $data['application_mode'] = !empty($value) ? $value : null;
                break;

            case 'application_type':
                $data['application_type'] = !empty($value) ? $value : null;
                break;

            case 'country':
                $data['country'] = !empty($value) ? $value : null;
                break;

            case 'case_flow':
                $data['case_flow'] = !empty($value) ? $value : null;
                break;

            case 'source_country':
                $data['source_country'] = !empty($value) ? $value : null;
                break;

            case 'open_date':
                $data['open_date'] = !empty($value) && $value !== '0000-00-00' ? $value : null;
                break;

            case 'application_no':
                $data['application_no'] = !empty($value) ? $value : null;
                break;

            case 'application_date':
                $data['application_date'] = !empty($value) && $value !== '0000-00-00' ? $value : null;
                break;

            case 'registration_no':
                $data['registration_no'] = !empty($value) ? $value : null;
                break;

            case 'registration_date':
                $data['registration_date'] = !empty($value) && $value !== '0000-00-00' ? $value : null;
                break;

            case 'certificate_no':
                $data['certificate_no'] = !empty($value) ? $value : null;
                break;

            case 'expire_date':
                $data['expire_date'] = !empty($value) && $value !== '0000-00-00' ? $value : null;
                break;

            case 'start_stage':
                $data['start_stage'] = !empty($value) ? $value : null;
                break;

            case 'is_expedited':
                $data['is_expedited'] = !empty($value) ? $value : null;
                break;

            case 'is_subsidy_agent':
                $data['is_subsidy_agent'] = ($value === '1' || $value === 1) ? 1 : 0;
                break;

            case 'is_material_available':
                $data['is_material_available'] = ($value === '1' || $value === 1) ? 1 : 0;
                break;

            case 'remarks':
                $data['remarks'] = !empty($value) ? $value : null;
                break;
        }
    }

    return $data;
}

/**
 * 验证版权案件数据
 */
function validateCopyrightData($data, $line_number)
{
    $errors = [];

    // 必填字段验证
    if (empty($data['case_name'])) {
        $errors[] = '案件名称不能为空';
    }

    if (empty($data['client_id'])) {
        $errors[] = '客户ID不能为空';
    }

    if (empty($data['process_item'])) {
        $errors[] = '处理事项不能为空';
    }

    if (empty($data['business_dept_id'])) {
        $errors[] = '承办部门ID不能为空';
    }

    // 日期格式验证
    $date_fields = ['entrust_date', 'open_date', 'application_date', 'registration_date', 'expire_date'];
    foreach ($date_fields as $field) {
        if (!empty($data[$field]) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data[$field])) {
            $errors[] = "{$field}日期格式错误，应为YYYY-MM-DD";
        }
    }

    return [
        'valid' => empty($errors),
        'message' => implode('; ', $errors)
    ];
}

/**
 * 检查是否存在相同案件
 */
function checkExistingCase($pdo, $case_code)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM copyright_case_info WHERE case_code = ?");
    $stmt->execute([$case_code]);
    return $stmt->fetchColumn() > 0;
}

/**
 * 插入版权案件
 */
function insertCopyrightCase($pdo, $data, $user_id)
{
    // 如果没有我方文号，自动生成
    if (empty($data['case_code'])) {
        $data['case_code'] = generateCaseCode($pdo);
    }

    // 固定案件类型为版权
    $data['case_type'] = '版权';

    // 确保所有字段都有默认值
    $default_data = [
        'case_code' => null,
        'case_name' => null,
        'case_type' => '版权',
        'client_case_code' => null,
        'client_id' => null,
        'business_type' => null,
        'process_item' => null,
        'case_status' => null,
        'entrust_date' => null,
        'business_dept_id' => null,
        'business_user_ids' => null,
        'application_mode' => null,
        'application_type' => null,
        'country' => null,
        'case_flow' => null,
        'source_country' => null,
        'open_date' => null,
        'application_no' => null,
        'application_date' => null,
        'registration_no' => null,
        'registration_date' => null,
        'certificate_no' => null,
        'expire_date' => null,
        'start_stage' => null,
        'is_expedited' => null,
        'is_subsidy_agent' => 0,
        'is_material_available' => 0,
        'remarks' => null
    ];

    // 合并数据，确保所有字段都存在
    $insert_data = array_merge($default_data, $data);

    $sql = "INSERT INTO copyright_case_info (
        case_code, case_name, case_type, client_case_code, client_id, business_type, 
        process_item, case_status, entrust_date, business_dept_id, business_user_ids,
        application_mode, application_type, country, case_flow, source_country,
        open_date, application_no, application_date, registration_no, registration_date,
        certificate_no, expire_date, start_stage, is_expedited, is_subsidy_agent,
        is_material_available, remarks, created_at, updated_at
    ) VALUES (
        :case_code, :case_name, :case_type, :client_case_code, :client_id, :business_type,
        :process_item, :case_status, :entrust_date, :business_dept_id, :business_user_ids,
        :application_mode, :application_type, :country, :case_flow, :source_country,
        :open_date, :application_no, :application_date, :registration_no, :registration_date,
        :certificate_no, :expire_date, :start_stage, :is_expedited, :is_subsidy_agent,
        :is_material_available, :remarks, NOW(), NOW()
    )";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute($insert_data);
}

/**
 * 生成版权案件编号
 */
function generateCaseCode($pdo)
{
    $prefix = 'CR' . date('Ymd');
    $sql = "SELECT COUNT(*) FROM copyright_case_info WHERE case_code LIKE :prefix";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':prefix' => $prefix . '%']);
    $count = $stmt->fetchColumn();
    $serial = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    return $prefix . $serial;
}
