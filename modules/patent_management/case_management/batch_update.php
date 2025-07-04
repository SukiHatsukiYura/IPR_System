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

        // 检查是否为表头行（包含"id"或"我方文号"等关键字段）
        $row_text = implode('', $row);
        if (
            (strpos($row_text, 'id') !== false && strpos($row_text, '我方文号') !== false) ||
            (strpos($row_text, '案件名称') !== false && strpos($row_text, '承办部门') !== false) ||
            (strpos($row_text, '客户ID') !== false && strpos($row_text, '处理事项') !== false)
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
        echo json_encode(['success' => false, 'message' => '批量修改必须包含id字段，请使用"下载当前案件信息"功能获取包含id的文件']);
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
    $result = batchUpdatePatent($pdo, $rows, $header_map, $_SESSION['user_id']);

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

    // 定义表头映射关系（专利案件字段）
    $header_mapping = [
        'id' => 'id',
        '我方文号' => 'case_code',
        '我方文号(可选，留空则自动生成)' => 'case_code',
        '案件名称' => 'case_name',
        '英文名称' => 'case_name_en',
        '承办部门ID' => 'business_dept_id',
        '开卷日期' => 'open_date',
        '开卷日期(YYYY-MM-DD)' => 'open_date',
        '客户文号' => 'client_case_code',
        '处理事项' => 'process_item',
        '客户ID' => 'client_id',
        '客户名称(中)' => 'client_name',
        '客户名称(中)*' => 'client_name',
        '业务类型' => 'business_type',
        '委案日期' => 'entrust_date',
        '委案日期(YYYY-MM-DD)' => 'entrust_date',
        '案件状态' => 'case_status',
        '同日申请' => 'same_day_apply',
        '同日递交' => 'same_day_submit',
        '代理费规则' => 'agent_rule',
        '案件备注' => 'remarks',
        '申请号' => 'application_no',
        '申请日' => 'application_date',
        '申请日(YYYY-MM-DD)' => 'application_date',
        '公开号' => 'publication_no',
        '公开日' => 'publication_date',
        '公开日(YYYY-MM-DD)' => 'publication_date',
        '处理人ID' => 'handler_id',
        '公告号' => 'announcement_no',
        '公告日' => 'announcement_date',
        '公告日(YYYY-MM-DD)' => 'announcement_date',
        '证书号' => 'certificate_no',
        '届满日' => 'expire_date',
        '届满日(YYYY-MM-DD)' => 'expire_date',
        '进入实审日' => 'enter_substantive_date',
        '进入实审日(YYYY-MM-DD)' => 'enter_substantive_date',
        '申请方式' => 'application_mode',
        '业务人员ID' => 'business_user_ids',
        '业务人员ID(多个用逗号分隔)' => 'business_user_ids',
        '业务助理ID' => 'business_assistant_ids',
        '业务助理ID(多个用逗号分隔)' => 'business_assistant_ids',
        '项目负责人ID' => 'project_leader_id',
        '申请类型' => 'application_type',
        '是否配案' => 'is_allocated',
        '国家(地区)' => 'country',
        '案件流向' => 'case_flow',
        '起始阶段' => 'start_stage',
        '客户状态' => 'client_status',
        '案源国' => 'source_country',
        '其他复选项' => 'other_options'
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
 * 批量修改专利案件
 */
function batchUpdatePatent($pdo, $rows, $header_map, $user_id)
{
    $success_count = 0;  // 实际更新的案件数量
    $processed_count = 0;  // 处理的案件总数量（包括无需更新的）
    $error_count = 0;
    $errors = [];

    // 预加载部门和客户数据
    $departments = getDepartmentMap($pdo);
    $customers = getCustomerMap($pdo);
    $users = getUserMap($pdo);

    // 调试信息：记录字段映射
    error_log("开始批量修改，字段映射: " . json_encode($header_map, JSON_UNESCAPED_UNICODE));

    // 预加载所有需要更新的案件数据（性能优化）
    $case_ids = [];
    foreach ($rows as $row) {
        $temp_data = [];
        foreach ($header_map as $col_index => $field_name) {
            if ($field_name === 'id') {
                $value = isset($row[$col_index]) ? trim($row[$col_index]) : '';
                if (!empty($value) && is_numeric($value)) {
                    $case_ids[] = intval($value);
                }
                break;
            }
        }
    }

    $current_cases_data = [];
    if (!empty($case_ids)) {
        $current_cases_data = getCurrentPatentDataBatch($pdo, $case_ids);
        error_log("预加载了 " . count($current_cases_data) . " 个案件的当前数据");
    }

    $pdo->beginTransaction();

    try {
        foreach ($rows as $row_index => $row) {
            $line_number = $row_index + 2; // Excel行号（从2开始，因为第1行是表头）

            try {
                // 调试信息：记录原始行数据
                error_log("第{$line_number}行原始数据: " . json_encode($row, JSON_UNESCAPED_UNICODE));

                // 解析行数据
                $patent_data = parsePatentRow($pdo, $row, $header_map, $departments, $customers, $users);

                // 调试信息：记录解析后的数据
                error_log("第{$line_number}行解析后数据: " . json_encode($patent_data, JSON_UNESCAPED_UNICODE));

                // 验证必须包含id字段
                if (empty($patent_data['id']) || !is_numeric($patent_data['id'])) {
                    $errors[] = "第{$line_number}行: 缺少有效的id字段，无法确定要修改的案件";
                    $error_count++;
                    continue;
                }

                $case_id = intval($patent_data['id']);

                // 检查案件是否存在
                $stmt = $pdo->prepare("SELECT id FROM patent_case_info WHERE id = ?");
                $stmt->execute([$case_id]);
                if (!$stmt->fetch()) {
                    $errors[] = "第{$line_number}行(案件ID:{$case_id}): 该专利案件不存在";
                    $error_count++;
                    continue;
                }

                // 如果用户填写了客户ID，验证客户ID是否存在并检查专利案件类型
                if (isset($patent_data['client_id']) && !empty($patent_data['client_id']) && is_numeric($patent_data['client_id'])) {
                    $stmt = $pdo->prepare("SELECT id, case_type_patent FROM customer WHERE id = ?");
                    $stmt->execute([$patent_data['client_id']]);
                    $customer = $stmt->fetch();

                    if (!$customer) {
                        $errors[] = "第{$line_number}行(案件ID:{$case_id}): 客户ID {$patent_data['client_id']} 不存在，请填写有效的客户ID";
                        $error_count++;
                        continue;
                    }

                    // 如果客户存在但没有专利案件类型，则添加专利案件类型
                    if ($customer['case_type_patent'] == 0) {
                        try {
                            $stmt = $pdo->prepare("UPDATE customer SET case_type_patent = 1 WHERE id = ?");
                            $stmt->execute([$patent_data['client_id']]);
                            error_log("为客户ID {$patent_data['client_id']} 添加专利案件类型");
                        } catch (Exception $e) {
                            error_log("更新客户ID {$patent_data['client_id']} 专利案件类型失败: " . $e->getMessage());
                        }
                    }
                }

                // 验证必填字段（只验证有值的字段）
                $validation_result = validatePatentData($patent_data, $line_number, $case_id);
                if (!$validation_result['valid']) {
                    $errors[] = "第{$line_number}行(案件ID:{$case_id}): " . $validation_result['message'];
                    $error_count++;
                    error_log("第{$line_number}行(案件ID:{$case_id})验证失败: " . $validation_result['message']);
                    continue;
                }

                // 更新案件（只更新发生变化的字段）
                $current_data = isset($current_cases_data[$case_id]) ? $current_cases_data[$case_id] : null;
                $update_result = updatePatentCaseOptimized($pdo, $patent_data, $case_id, $current_data);
                $processed_count++; // 无论是否更新，都算作已处理

                if ($update_result['updated']) {
                    $success_count++;
                    error_log("第{$line_number}行(案件ID:{$case_id}): 成功更新了 {$update_result['changed_fields']} 个字段");
                } else {
                    // 没有字段需要更新，不计入成功数量，但记录处理状态
                    error_log("第{$line_number}行(案件ID:{$case_id}): 没有字段需要更新");
                }
            } catch (Exception $e) {
                $case_id_text = isset($case_id) ? "(案件ID:{$case_id})" : "";
                $errors[] = "第{$line_number}行{$case_id_text}: " . $e->getMessage();
                $error_count++;
                error_log("第{$line_number}行{$case_id_text}处理异常: " . $e->getMessage());
            }
        }

        $pdo->commit();

        // 统计性能信息
        $no_change_count = $processed_count - $success_count; // 无需更新的案件数量
        $total_processed = $processed_count + $error_count;
        $performance_info = "性能统计: 处理 {$total_processed} 行数据，实际更新 {$success_count} 个案件，无需更新 {$no_change_count} 个案件，预加载 " . count($current_cases_data) . " 个案件数据";
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
 * 解析专利行数据
 */
function parsePatentRow($pdo, $row, $header_map, $departments, $customers, $users)
{
    $data = [];
    $has_client_id_column = false;
    $has_client_name_column = false;

    foreach ($header_map as $col_index => $field_name) {
        $value = isset($row[$col_index]) ? trim($row[$col_index]) : '';

        switch ($field_name) {
            case 'id':
                $data[$field_name] = !empty($value) && is_numeric($value) ? intval($value) : null;
                break;
            case 'client_id':
                $has_client_id_column = true;
                $data[$field_name] = !empty($value) && is_numeric($value) ? intval($value) : null;
                break;
            case 'client_name':
                $has_client_name_column = true;
                // 支持客户名称，如果不存在则自动创建
                if (!empty($value)) {
                    $data['client_id_from_name'] = getOrCreateCustomer($pdo, $value);
                } else {
                    // 即使为空也要标记，用于后续验证
                    $data['client_id_from_name'] = null;
                }
                break;
            case 'business_dept_id':
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
            case 'is_allocated':
                if (!empty($value)) {
                    if (is_numeric($value)) {
                        $data[$field_name] = intval($value);
                    } else {
                        $data[$field_name] = ($value === '是') ? 1 : 0;
                    }
                } else {
                    $data[$field_name] = null;
                }
                break;
            case 'entrust_date':
            case 'open_date':
            case 'application_date':
            case 'publication_date':
            case 'announcement_date':
            case 'enter_substantive_date':
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
                    // 空值表示要清空日期字段
                    $data[$field_name] = null;
                }
                break;
            default:
                // 对于文本字段，直接使用Excel中的值，空值就是要清空
                $data[$field_name] = !empty($value) ? $value : null;
                break;
        }
    }

    // 记录用户是否提供了客户信息列（用于验证）- 只要Excel中存在客户相关列就需要验证
    $data['_has_client_info'] = $has_client_id_column || $has_client_name_column;

    // 处理客户ID的优先级：如果同时有client_id和client_id_from_name，优先使用用户直接填写的client_id
    if (isset($data['client_id']) && !empty($data['client_id'])) {
        // 用户填写了客户ID，使用客户ID
        unset($data['client_id_from_name']);
    } elseif (isset($data['client_id_from_name']) && !empty($data['client_id_from_name'])) {
        // 用户没填客户ID但填了客户名称，使用从客户名称获取的ID
        $data['client_id'] = $data['client_id_from_name'];
        unset($data['client_id_from_name']);
    } else {
        // 清理空的client_id_from_name字段
        unset($data['client_id_from_name']);
    }

    return $data;
}

/**
 * 验证专利数据
 */
function validatePatentData($data, $line_number, $case_id = null)
{
    $errors = []; // 收集所有错误信息

    // 定义必填字段
    $required_fields = [
        'case_name' => '案件名称',
        'business_dept_id' => '承办部门ID',
        'process_item' => '处理事项',
        'application_type' => '申请类型'
    ];

    // 验证必填字段不能为空
    foreach ($required_fields as $field => $label) {
        // 字段不存在或为空都视为无效
        if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
            $errors[] = "必填字段 {$label} 不能为空";
        }
    }

    // 客户字段验证：如果用户提供了客户信息但最终客户ID为空，则报错
    if (isset($data['_has_client_info']) && $data['_has_client_info'] === true) {
        // 用户提供了客户信息，检查最终是否有有效的客户ID
        $has_valid_client = isset($data['client_id']) && !empty($data['client_id']) && is_numeric($data['client_id']);

        if (!$has_valid_client) {
            $errors[] = "客户ID和客户名称不能同时为空，请至少填写其中一个";
        }
    }

    // 验证数值字段
    $numeric_fields = ['business_dept_id', 'client_id', 'handler_id', 'project_leader_id'];
    foreach ($numeric_fields as $field) {
        if (isset($data[$field]) && !empty($data[$field]) && !is_numeric($data[$field])) {
            $errors[] = "字段 {$field} 必须是数字";
        }
    }

    // 如果有错误，返回所有错误信息
    if (!empty($errors)) {
        return [
            'valid' => false,
            'message' => implode('；', $errors) // 用分号连接多个错误信息
        ];
    }

    return ['valid' => true];
}

/**
 * 更新专利案件（简化版：直接对比新旧值进行更新）
 */
function updatePatentCase($pdo, $data, $case_id)
{
    // 移除id字段和临时标记字段，因为不能更新主键且临时标记字段不是数据库字段
    unset($data['id']);
    unset($data['_has_client_info']);

    // 首先获取当前数据库中的数据
    $current_data = getCurrentPatentData($pdo, $case_id);
    if (!$current_data) {
        throw new Exception("案件不存在");
    }

    // 比较数据，只更新发生变化的字段
    $changed_fields = [];
    $update_values = [];

    foreach ($data as $field => $new_value) {
        $current_value = isset($current_data[$field]) ? $current_data[$field] : null;

        // 创建比较用的值（进行格式化）
        $current_compare_value = $current_value;
        $new_compare_value = $new_value;

        // 特殊处理日期字段的比较
        if (in_array($field, ['entrust_date', 'open_date', 'application_date', 'publication_date', 'announcement_date', 'enter_substantive_date', 'expire_date'])) {
            $current_compare_value = $current_value ? date('Y-m-d', strtotime($current_value)) : null;
            $new_compare_value = $new_value ? date('Y-m-d', strtotime($new_value)) : null;
        }

        // 特殊处理布尔字段的比较
        if (in_array($field, ['is_allocated'])) {
            $current_compare_value = intval($current_value);
            $new_compare_value = intval($new_value);
        }

        // 直接对比新旧值，有变化就更新（包括设置为null）
        if ($current_compare_value != $new_compare_value) {
            $changed_fields[] = "`{$field}` = ?";
            $update_values[] = $new_value;
            error_log("字段 {$field} 发生变化: '{$current_compare_value}' -> '{$new_compare_value}'");
        }
    }

    // 如果没有字段发生变化，直接返回
    if (empty($changed_fields)) {
        return ['updated' => false, 'message' => '没有字段发生变化'];
    }

    // 添加更新时间
    $changed_fields[] = "`updated_at` = NOW()";
    $update_values[] = $case_id;

    $sql = "UPDATE patent_case_info SET " . implode(', ', $changed_fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($update_values);

    if (!$result) {
        throw new Exception("更新案件失败");
    }

    $affected_rows = $stmt->rowCount();
    $field_count = count($changed_fields) - 1; // 减去updated_at字段
    error_log("案件ID {$case_id} 更新了 {$field_count} 个字段，影响行数: {$affected_rows}");

    return ['updated' => true, 'changed_fields' => $field_count];
}

/**
 * 获取当前案件数据
 */
function getCurrentPatentData($pdo, $case_id)
{
    $sql = "SELECT * FROM patent_case_info WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$case_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 批量获取当前案件数据（性能优化）
 */
function getCurrentPatentDataBatch($pdo, $case_ids)
{
    if (empty($case_ids)) {
        return [];
    }

    $placeholders = str_repeat('?,', count($case_ids) - 1) . '?';
    $sql = "SELECT * FROM patent_case_info WHERE id IN ({$placeholders})";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($case_ids);

    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[$row['id']] = $row;
    }

    return $result;
}

/**
 * 优化版更新函数（使用预加载的数据，直接对比新旧值）
 */
function updatePatentCaseOptimized($pdo, $data, $case_id, $current_data = null)
{
    // 移除id字段和临时标记字段，因为不能更新主键且临时标记字段不是数据库字段
    unset($data['id']);
    unset($data['_has_client_info']);

    // 如果没有提供当前数据，则查询获取
    if ($current_data === null) {
        $current_data = getCurrentPatentData($pdo, $case_id);
    }

    if (!$current_data) {
        throw new Exception("案件不存在");
    }

    // 比较数据，只更新发生变化的字段
    $changed_fields = [];
    $update_values = [];

    foreach ($data as $field => $new_value) {
        $current_value = isset($current_data[$field]) ? $current_data[$field] : null;

        // 创建比较用的值（进行格式化）
        $current_compare_value = $current_value;
        $new_compare_value = $new_value;

        // 特殊处理日期字段的比较
        if (in_array($field, ['entrust_date', 'open_date', 'application_date', 'publication_date', 'announcement_date', 'enter_substantive_date', 'expire_date'])) {
            $current_compare_value = $current_value ? date('Y-m-d', strtotime($current_value)) : null;
            $new_compare_value = $new_value ? date('Y-m-d', strtotime($new_value)) : null;
        }

        // 特殊处理布尔字段的比较
        if (in_array($field, ['is_allocated'])) {
            $current_compare_value = intval($current_value);
            $new_compare_value = intval($new_value);
        }

        // 直接对比新旧值，有变化就更新（包括设置为null）
        if ($current_compare_value != $new_compare_value) {
            $changed_fields[] = "`{$field}` = ?";
            $update_values[] = $new_value;
            error_log("字段 {$field} 发生变化: '{$current_compare_value}' -> '{$new_compare_value}'");
        }
    }

    // 如果没有字段发生变化，直接返回
    if (empty($changed_fields)) {
        return ['updated' => false, 'message' => '没有字段发生变化'];
    }

    // 添加更新时间
    $changed_fields[] = "`updated_at` = NOW()";
    $update_values[] = $case_id;

    $sql = "UPDATE patent_case_info SET " . implode(', ', $changed_fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($update_values);

    if (!$result) {
        throw new Exception("更新案件失败");
    }

    $affected_rows = $stmt->rowCount();
    $field_count = count($changed_fields) - 1; // 减去updated_at字段
    error_log("案件ID {$case_id} 更新了 {$field_count} 个字段，影响行数: {$affected_rows}");

    return ['updated' => true, 'changed_fields' => $field_count];
}

/**
 * 根据客户名称获取或创建客户（专利模块）
 */
function getOrCreateCustomer($pdo, $customer_name)
{
    if (empty($customer_name)) {
        return null;
    }

    // 首先检查客户是否已存在，同时获取专利案件类型标识
    $stmt = $pdo->prepare("SELECT id, case_type_patent FROM customer WHERE customer_name_cn = ?");
    $stmt->execute([$customer_name]);
    $existing_customer = $stmt->fetch();

    if ($existing_customer) {
        $customer_id = $existing_customer['id'];

        // 如果客户存在但没有专利案件类型，则添加专利案件类型
        if ($existing_customer['case_type_patent'] == 0) {
            try {
                $stmt = $pdo->prepare("UPDATE customer SET case_type_patent = 1 WHERE id = ?");
                $stmt->execute([$customer_id]);
                error_log("为现有客户添加专利案件类型: ID={$customer_id}, 名称={$customer_name}");
            } catch (Exception $e) {
                error_log("更新客户专利案件类型失败: " . $e->getMessage());
                // 不抛出异常，因为客户已存在，只是案件类型更新失败
            }
        }

        return $customer_id;
    }

    // 客户不存在，创建新客户（自动设置专利案件类型为1）
    try {
        // 生成客户编号
        $customer_code = generateCustomerCode($pdo);

        $stmt = $pdo->prepare("INSERT INTO customer (customer_code, customer_name_cn, case_type_patent, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->execute([$customer_code, $customer_name]);

        $new_customer_id = $pdo->lastInsertId();
        error_log("自动创建新客户: ID={$new_customer_id}, 名称={$customer_name}, 编号={$customer_code}, 专利案件类型=1");

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
