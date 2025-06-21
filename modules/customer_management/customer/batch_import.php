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

        // 检查是否为表头行（包含"客户编号"或"客户名称"等关键字段）
        $row_text = implode('', $row);
        if (
            strpos($row_text, '客户编号') !== false ||
            strpos($row_text, '客户名称') !== false ||
            strpos($row_text, '公司负责人') !== false
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
    $result = batchImportCustomers($pdo, $rows, $header_map, $_SESSION['user_id']);

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

    // 定义表头映射关系（客户相关）
    $header_mapping = [
        '客户编号' => 'customer_code',
        '客户名称(中)' => 'customer_name_cn',
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
        '案件类型-专利(1是0否)' => 'case_type_patent',
        '案件类型-商标(1是0否)' => 'case_type_trademark',
        '案件类型-版权(1是0否)' => 'case_type_copyright',
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
        '本所业务公共邮箱' => 'public_email',
        '纳税人识别号' => 'tax_id'
    ];

    foreach ($headers as $index => $header) {
        $clean_header = trim($header);

        // 移除星号
        $clean_header = str_replace('*', '', $clean_header);
        $clean_header = trim($clean_header);

        // 直接匹配，不移除括号内容
        if (isset($header_mapping[$clean_header])) {
            $map[$index] = $header_mapping[$clean_header];
        }
    }

    return $map;
}

/**
 * 批量导入客户
 */
function batchImportCustomers($pdo, $rows, $header_map, $user_id)
{
    $success_count = 0;
    $error_count = 0;
    $errors = [];

    // 预加载用户数据
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
                $data = parseCustomerRow($row, $header_map, $users);

                // 调试信息：记录解析后的数据
                error_log("第{$line_number}行解析后数据: " . json_encode($data, JSON_UNESCAPED_UNICODE));

                // 验证数据
                $validation_result = validateCustomerData($data, $line_number);
                if (!$validation_result['valid']) {
                    $errors[] = "第{$line_number}行: " . $validation_result['message'];
                    $error_count++;
                    error_log("第{$line_number}行验证失败: " . $validation_result['message']);
                    continue;
                }

                // 检查是否已存在相同客户
                if (!empty($data['customer_code']) && checkExistingCustomer($pdo, $data['customer_code'])) {
                    $errors[] = "第{$line_number}行: 客户编号 '{$data['customer_code']}' 已存在";
                    $error_count++;
                    continue;
                }

                // 插入客户
                if (insertCustomer($pdo, $data, $user_id)) {
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
 * 获取用户映射
 */
function getUserMap($pdo)
{
    $map = [];
    $stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active = 1");
    $stmt->execute();
    $users = $stmt->fetchAll();

    foreach ($users as $user) {
        $map[$user['id']] = $user['real_name'];
        $map[$user['real_name']] = $user['id'];
    }

    return $map;
}

/**
 * 解析客户行数据
 */
function parseCustomerRow($row, $header_map, $users)
{
    $data = [];

    foreach ($header_map as $col_index => $field_name) {
        $value = isset($row[$col_index]) ? trim($row[$col_index]) : '';

        switch ($field_name) {
            case 'customer_code':
                $data['customer_code'] = !empty($value) ? $value : null;
                break;

            case 'customer_name_cn':
                $data['customer_name_cn'] = $value;
                break;

            case 'customer_name_en':
                $data['customer_name_en'] = !empty($value) ? $value : null;
                break;

            case 'company_leader':
                $data['company_leader'] = !empty($value) ? $value : null;
                break;

            case 'email':
                $data['email'] = !empty($value) ? $value : null;
                break;

            case 'business_staff_id':
            case 'process_staff_id':
            case 'project_leader_id':
            case 'new_case_manager_id':
                if (is_numeric($value)) {
                    $data[$field_name] = intval($value);
                } elseif (!empty($value) && isset($users[$value])) {
                    $data[$field_name] = $users[$value];
                } else {
                    $data[$field_name] = null;
                }
                break;

            case 'internal_signer':
            case 'external_signer':
            case 'customer_level':
            case 'address':
            case 'bank_name':
            case 'deal_status':
            case 'remark':
            case 'phone':
            case 'creator':
            case 'internal_signer_phone':
            case 'external_signer_phone':
            case 'billing_address':
            case 'credit_level':
            case 'address_en':
            case 'bank_account':
            case 'customer_id_code':
            case 'fax':
            case 'customer_source':
            case 'internal_signer_email':
            case 'external_signer_email':
            case 'delivery_address':
            case 'public_email':
            case 'tax_id':
                $data[$field_name] = !empty($value) ? $value : null;
                break;

            case 'case_type_patent':
            case 'case_type_trademark':
            case 'case_type_copyright':
                $data[$field_name] = ($value === '1' || $value === 1) ? 1 : 0;
                break;

            case 'industry':
                $data['industry'] = !empty($value) ? $value : null;
                break;

            case 'sign_date':
                if (!empty($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $data['sign_date'] = $value;
                } else {
                    $data['sign_date'] = null;
                }
                break;

            default:
                // 其他字段直接赋值
                $data[$field_name] = !empty($value) ? $value : null;
                break;
        }
    }

    return $data;
}

/**
 * 验证客户数据
 */
function validateCustomerData($data, $line_number)
{
    $errors = [];

    // 必填字段验证
    if (empty($data['customer_name_cn'])) {
        $errors[] = '客户名称(中)不能为空';
    }

    // 案件类型验证：至少要有一个案件类型为1
    $case_type_patent = intval($data['case_type_patent'] ?? 0);
    $case_type_trademark = intval($data['case_type_trademark'] ?? 0);
    $case_type_copyright = intval($data['case_type_copyright'] ?? 0);

    if ($case_type_patent === 0 && $case_type_trademark === 0 && $case_type_copyright === 0) {
        $errors[] = '案件类型-专利、案件类型-商标、案件类型-版权至少要有一个为1';
    }

    // 日期格式验证
    $date_fields = ['sign_date'];
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
 * 检查是否存在相同客户
 */
function checkExistingCustomer($pdo, $customer_code)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer WHERE customer_code = ?");
    $stmt->execute([$customer_code]);
    return $stmt->fetchColumn() > 0;
}

/**
 * 插入客户
 */
function insertCustomer($pdo, $data, $user_id)
{
    // 如果没有客户编号，自动生成
    if (empty($data['customer_code'])) {
        $data['customer_code'] = generateCustomerCode($pdo);
    }

    // 确保所有字段都有默认值
    $default_data = [
        'customer_code' => null,
        'customer_name_cn' => null,
        'customer_name_en' => null,
        'company_leader' => null,
        'email' => null,
        'business_staff_id' => null,
        'internal_signer' => null,
        'external_signer' => null,
        'process_staff_id' => null,
        'customer_level' => null,
        'address' => null,
        'bank_name' => null,
        'deal_status' => null,
        'project_leader_id' => null,
        'remark' => null,
        'case_type_patent' => 0,
        'case_type_trademark' => 0,
        'case_type_copyright' => 0,
        'phone' => null,
        'industry' => null,
        'creator' => null,
        'internal_signer_phone' => null,
        'external_signer_phone' => null,
        'billing_address' => null,
        'credit_level' => null,
        'address_en' => null,
        'bank_account' => null,
        'customer_id_code' => null,
        'new_case_manager_id' => null,
        'fax' => null,
        'customer_source' => null,
        'internal_signer_email' => null,
        'external_signer_email' => null,
        'delivery_address' => null,
        'sign_date' => null,
        'public_email' => null,
        'tax_id' => null
    ];

    // 合并数据
    $final_data = array_merge($default_data, $data);

    // 构建SQL语句
    $fields = array_keys($final_data);
    $placeholders = ':' . implode(', :', $fields);
    $sql = "INSERT INTO customer (" . implode(', ', $fields) . ") VALUES ($placeholders)";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute($final_data);
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
