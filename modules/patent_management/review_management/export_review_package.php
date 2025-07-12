<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();

// 导出核稿包功能 - 专利管理/核稿管理模块下的导出核稿包功能

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 统一选项声明
$options = [
    'case_types' => ['发明专利', '实用新型专利', '外观设计专利', 'PCT国际专利'],
    'case_statuses' => ['待提交', '已提交', '已受理', '审查中', '已授权', '已驳回', '已放弃', '已失效'],
    'application_modes' => ['普通申请', '优先审查', '加快审查', '提前公开', '秘密申请', '国防专利'],
    'is_allocated' => ['是', '否'],
    'client_statuses' => ['正常', '关注', '重要', '紧急'],
    'business_types' => ['国内发明', '国内实用新型', '国内外观设计', '国际发明', '国际实用新型', '国际外观设计']
];

// 替换为与add_patent.php一致的选项声明
// 业务类型
$business_types = ['无效案件', '普通新申请', '专利转让', '著泉项目变更', 'PCT国际阶段', '复审', '香港登记案', '申请香港', '临时申请', '公众意见', '翻译', '专利检索案件', '代缴年费案件', '诉讼案件', '顾问', '专利许可备案', '海关备案', '其他', 'PCT国家阶段', '办理副本案件'];
// 案件状态
$case_statuses = ['请选择', '未递交', '已递交', '暂缓申请', '受理', '初审合格', '初审', '公开', '实审', '补正', '审查', '一通', '二通', '三通', '四通', '五通', '六通', '七通', '八通', '一补', '九通', '二补', '三补', '视为撤回', '主动撤回', '驳回', '复审', '无效', '视为放弃', '主动放弃', '授权', '待领证', '维持', '终止', '结案', '届满', 'PCT国际检索', '中止', '保全', '诉讼', '办理登记手续', '复审受理', 'Advisory Action', 'Appeal', 'Election Action', 'Final Action', 'Non Final Action', 'Petition', 'RCE', '公告', '视为未提出'];
// 处理事项
$process_items = ['请求优先审查', '开卷', '放弃', '更正', '无效答辩', '不予受理', '官文转达', '缴年费', '民事诉讼上诉', '主动补正', '专利权评价报告', '驳回', '取得检索报告', '请求无效', '翻译', '审查高速公路', '资助监控', '赔偿请求', '请求检索报告', '许可备案', '诉讼', '取得副本', '请求加速审查', '民事诉讼答辩', '取得申请号', '请求中止', '办理登记手续', '复审决定', '避免重复授权', '民事诉讼上诉答辩', '确认通知书', '请求保密审查', '结案', '补正', '请求恢复权利', '视为未提出', '手续补正', '取得证书', '年费滞纳金', '复审意见陈述', '提交IDS', '复审受理', '请求延长期限', '撤回', '请求提前公开', '处理审查意见', '口审', '诉讼举证', '项目申报', '办理DAS', '行政诉讼上诉答辩', '请求复审', '无效行政诉讼答辩', '请求退款', '提出行政诉讼', '缴费', '终止', '无效诉讼', '公众意见', '保密决定', '变更代理人申请补正通知', '请求实审', '提出民事诉讼', '请求副本', '新申请', '复议申请', '无效请求补充意见', '著泉项目变更', '行政诉讼上诉', '请求费用减缓', '视为未要求'];
// 申请类型
$application_types = ['请选择', '发明', '实用新型', '外观设计', '临时申请', '再公告', '植物', '集成电路布图设计', '年费', '无效', '其他'];
// 申请方式
$application_modes = ['电子申请(事务所)', '纸件申请', '其他'];
// 国家(地区)
$countries = ['中国', '美国', '日本', '韩国', '德国', '法国', '英国', '其他'];
// 案件流向
$case_flows = ['内-内', '内-外', '外-内', '外-外'];
// 起始阶段
$start_stages = ['无', '新申请', '答辩', '缴费'];
// 客户状态
$client_statuses = ['请选择', '放弃指示', '新申请指示递交', '补充申请信息资料', '修改意见', '著录项目变更指示', 'OA指示递交', '结案指示'];
// 案源国
$source_countries = ['中国', '美国', '日本', '其他'];
// 其他选项
$other_options = ['同步提交', '提前公布', '请求保密审查', '预审案件', '优先审查', '同时请求DAS码', '请求提前公开', '请求费用减缓'];

// 查询所有在职用户用于下拉
$user_stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name ASC");
$user_stmt->execute();
$users = $user_stmt->fetchAll();

// 查询所有部门用于下拉
$dept_stmt = $pdo->prepare("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY dept_name ASC");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll();

// 查询所有客户用于下拉
$customer_stmt = $pdo->prepare("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn ASC");
$customer_stmt->execute();
$customers = $customer_stmt->fetchAll();





// 处理下载核稿包请求
if (isset($_POST['action']) && $_POST['action'] === 'download_package') {
    if (empty($_POST['case_ids'])) {
        echo json_encode(['success' => false, 'msg' => '未选择任何案件']);
        exit;
    }

    $case_ids = explode(',', $_POST['case_ids']);
    $case_ids = array_map('intval', $case_ids);
    $case_ids = array_filter($case_ids, function ($id) {
        return $id > 0;
    });

    if (empty($case_ids)) {
        echo json_encode(['success' => false, 'msg' => '案件ID无效']);
        exit;
    }

    try {
        // 创建临时目录
        $temp_dir = sys_get_temp_dir() . '/patent_review_package_' . time() . '_' . rand(1000, 9999);
        if (!mkdir($temp_dir, 0755, true)) {
            throw new Exception('无法创建临时目录');
        }

        // 获取案件信息
        $placeholders = implode(',', array_fill(0, count($case_ids), '?'));
        $sql = "SELECT p.*, 
                (SELECT dept_name FROM department WHERE id = p.business_dept_id) as dept_name,
                (SELECT customer_name_cn FROM customer WHERE id = p.client_id) as customer_name_cn
                FROM patent_case_info p 
                WHERE p.id IN ($placeholders)
                ORDER BY p.case_code";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($case_ids);
        $cases = $stmt->fetchAll();

        if (empty($cases)) {
            throw new Exception('未找到任何案件');
        }

        // 为每个案件创建目录并收集文件
        foreach ($cases as $case) {
            // 案件目录名：文号_案件名称（只替换文件系统不允许的字符）
            $case_name_clean = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $case['case_name']);
            $case_dir_name = $case['case_code'] . '_' . $case_name_clean;
            $case_dir = $temp_dir . '/' . $case_dir_name;

            if (!mkdir($case_dir, 0755, true)) {
                continue;
            }

            // 创建案件信息文件
            $case_info = "案件信息\n";
            $case_info .= "================\n";
            $case_info .= "我方文号：" . ($case['case_code'] ?? '') . "\n";
            $case_info .= "案件名称：" . ($case['case_name'] ?? '') . "\n";
            $case_info .= "承办部门：" . ($case['dept_name'] ?? '') . "\n";
            $case_info .= "客户名称：" . ($case['customer_name_cn'] ?? '') . "\n";
            $case_info .= "业务类型：" . ($case['business_type'] ?? '') . "\n";
            $case_info .= "案件状态：" . ($case['case_status'] ?? '') . "\n";
            $case_info .= "申请号：" . ($case['application_no'] ?? '') . "\n";
            $case_info .= "申请日：" . ($case['application_date'] ?? '') . "\n";
            $case_info .= "生成时间：" . date('Y-m-d H:i:s') . "\n\n";

            file_put_contents($case_dir . '/案件信息.txt', $case_info);

            // 获取该案件的所有处理事项
            $task_sql = "SELECT t.*, 
                        (SELECT real_name FROM user WHERE id = t.handler_id) as handler_name,
                        (SELECT real_name FROM user WHERE id = t.supervisor_id) as supervisor_name,
                        r.review_comments
                        FROM patent_case_task t
                        LEFT JOIN patent_case_review_status r ON t.id = r.patent_case_task_id
                        WHERE t.patent_case_info_id = ?
                        ORDER BY t.id";
            $task_stmt = $pdo->prepare($task_sql);
            $task_stmt->execute([$case['id']]);
            $tasks = $task_stmt->fetchAll();

            // 处理每个处理事项，但不创建子目录，所有文件都放在案件目录下
            foreach ($tasks as $task) {
                // 处理事项名称清理（只替换文件系统不允许的字符）
                $task_name_clean = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $task['task_item']);

                // 创建处理事项信息文件：编号_处理事项名称_处理事项信息.txt
                $task_info_filename = $task['id'] . '_' . $task_name_clean . '_处理事项信息.txt';
                $task_info = "处理事项信息\n";
                $task_info .= "================\n";
                $task_info .= "处理事项：" . ($task['task_item'] ?? '') . "\n";
                $task_info .= "处理状态：" . ($task['task_status'] ?? '') . "\n";
                $task_info .= "案件阶段：" . ($task['case_stage'] ?? '') . "\n";
                $task_info .= "处理人：" . ($task['handler_name'] ?? '') . "\n";
                $task_info .= "核稿人：" . ($task['supervisor_name'] ?? '') . "\n";
                $task_info .= "内部期限：" . ($task['internal_deadline'] ?? '') . "\n";
                $task_info .= "客户期限：" . ($task['client_deadline'] ?? '') . "\n";
                $task_info .= "官方期限：" . ($task['official_deadline'] ?? '') . "\n";
                $task_info .= "创建时间：" . ($task['created_at'] ?? '') . "\n";
                $task_info .= "更新时间：" . ($task['updated_at'] ?? '') . "\n\n";

                file_put_contents($case_dir . '/' . $task_info_filename, $task_info);

                // 如果有核稿意见，创建核稿意见文件：编号_处理事项名称_核稿意见.txt
                if (!empty($task['review_comments'])) {
                    $review_filename = $task['id'] . '_' . $task_name_clean . '_核稿意见.txt';
                    $review_content = "核稿意见\n";
                    $review_content .= "================\n";
                    $review_content .= "处理事项：" . ($task['task_item'] ?? '') . "\n";
                    $review_content .= "核稿人：" . ($task['supervisor_name'] ?? '') . "\n";
                    $review_content .= "核稿意见：\n" . $task['review_comments'] . "\n\n";
                    $review_content .= "生成时间：" . date('Y-m-d H:i:s') . "\n";

                    file_put_contents($case_dir . '/' . $review_filename, $review_content);
                }

                // 获取该处理事项的附件
                $attachment_sql = "SELECT * FROM patent_task_attachment WHERE task_id = ? ORDER BY id";
                $attachment_stmt = $pdo->prepare($attachment_sql);
                $attachment_stmt->execute([$task['id']]);
                $attachments = $attachment_stmt->fetchAll();

                // 复制附件到案件目录下，文件名前加上处理事项编号和名称
                foreach ($attachments as $attachment) {
                    $source_file = $attachment['file_path'];

                    // 数据库存储的是相对路径，需要转换为绝对路径
                    if (substr($source_file, 0, 1) !== '/' && strpos($source_file, ':\\') === false) {
                        // 相对路径，转换为绝对路径
                        $source_file = __DIR__ . '/../../../' . $source_file;
                    }

                    if (file_exists($source_file)) {
                        $original_filename = getDownloadFileName($attachment['file_name'], $attachment['original_file_name']);
                        // 添加处理事项前缀：编号_处理事项名称_原文件名
                        $prefixed_filename = $task['id'] . '_' . $task_name_clean . '_' . $original_filename;
                        $dest_file = $case_dir . '/' . $prefixed_filename;

                        if (copy($source_file, $dest_file)) {
                            // 文件复制成功
                        }
                    }
                }
            }
        }

        // 创建ZIP文件
        $zip_file = $temp_dir . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('无法创建ZIP文件');
        }

        // 递归添加文件到ZIP
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $temp_dir_real = realpath($temp_dir);
        $temp_dir_len = strlen($temp_dir_real);

        foreach ($iterator as $file) {
            $file_path = $file->getRealPath();

            // 计算相对路径
            $relative_path = substr($file_path, $temp_dir_len + 1);

            // 统一使用正斜杠
            $relative_path = str_replace('\\', '/', $relative_path);

            // 跳过空的相对路径
            if (empty($relative_path)) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relative_path);
            } else {
                $zip->addFile($file_path, $relative_path);
            }
        }

        $zip->close();

        // 清理临时目录
        function deleteDir($dir)
        {
            if (!is_dir($dir)) return;
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? deleteDir($path) : unlink($path);
            }
            rmdir($dir);
        }
        deleteDir($temp_dir);

        // 检查ZIP文件是否生成成功
        if (!file_exists($zip_file) || filesize($zip_file) == 0) {
            throw new Exception('ZIP文件生成失败');
        }

        // 移动ZIP文件到uploads目录供下载
        $upload_dir = __DIR__ . '/../../../uploads/review_packages';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $final_filename = '核稿包_' . time() . '.zip';
        $final_path = $upload_dir . '/' . $final_filename;

        if (!rename($zip_file, $final_path)) {
            throw new Exception('无法移动ZIP文件');
        }

        // 直接输出文件供下载，而不是返回链接
        if (isset($_POST['direct_download']) && $_POST['direct_download'] == '1') {
            // 直接下载模式
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $final_filename . '"');
            header('Content-Length: ' . filesize($final_path));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            // 输出文件内容
            readfile($final_path);

            // 删除临时文件
            unlink($final_path);
            exit;
        } else {
            // 返回下载链接模式（保持兼容性）
            $download_url = 'uploads/review_packages/' . $final_filename;
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'download_url' => $download_url, 'filename' => $final_filename]);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 处理AJAX请求
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    $page = max(1, intval($_GET['page'] ?? 1));
    $page_size = min(max(1, intval($_GET['page_size'] ?? 10)), 100);
    $offset = ($page - 1) * $page_size;
    $where = [];
    $params = [];

    // 获取核稿状态为"已完成"的案件ID列表
    $completed_stmt = $pdo->prepare("SELECT DISTINCT patent_case_info_id FROM patent_case_review_status WHERE review_status = '已完成'");
    $completed_stmt->execute();
    $completed_cases = $completed_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($completed_cases)) {
        // 没有已完成状态的案件
        echo json_encode([
            'success' => true,
            'html' => '<tr><td colspan="11" style="text-align:center;padding:20px 0;">暂无已完成状态的案件</td></tr>',
            'total_records' => 0,
            'total_pages' => 1,
            'current_page' => 1
        ]);
        exit;
    }

    // 添加已完成案件的筛选条件
    $placeholders = str_repeat('?,', count($completed_cases) - 1) . '?';
    $where[] = "id IN ($placeholders)";
    $params = array_merge($params, $completed_cases);

    // 合并其他查询条件
    $search_fields = [
        'case_code' => 'LIKE',
        'case_name' => 'LIKE',
        'application_no' => 'LIKE',
        'business_dept_id' => '=',
        'client_id' => '=',
        'handler_id' => '=',
        'business_type' => '=',
        'case_status' => '='
    ];

    foreach ($search_fields as $field => $op) {
        if (!empty($_GET[$field])) {
            $where[] = "$field " . ($op == 'LIKE' ? "LIKE ?" : "= ?");
            $params[] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
        }
    }

    // 特殊处理"是否配案"字段 - 将"是"/"否"转换为1/0
    if (!empty($_GET['is_allocated'])) {
        $is_allocated_value = ($_GET['is_allocated'] === '是') ? 1 : 0;
        $where[] = "is_allocated = ?";
        $params[] = $is_allocated_value;
    }

    // 处理申请日期范围
    if (!empty($_GET['application_date_start'])) {
        $where[] = "application_date >= ?";
        $params[] = $_GET['application_date_start'];
    }
    if (!empty($_GET['application_date_end'])) {
        $where[] = "application_date <= ?";
        $params[] = $_GET['application_date_end'];
    }

    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count_sql = "SELECT COUNT(*) FROM patent_case_info" . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);

    $sql = "SELECT p.*, 
            (SELECT dept_name FROM department WHERE id = p.business_dept_id) as business_dept_name,
            (SELECT customer_name_cn FROM customer WHERE id = p.client_id) as client_name,
            (SELECT real_name FROM user WHERE id = p.handler_id) as handler_name
            FROM patent_case_info p" . $sql_where . " ORDER BY p.id DESC LIMIT ?, ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$offset, $page_size]));
    $patents = $stmt->fetchAll();
    $html = '';
    if (empty($patents)) {
        $html = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($patents as $index => $patent) {
            $html .= '<tr data-id="' . $patent['id'] . '">';
            $html .= '<td style="text-align:center;"><input type="checkbox" class="case-checkbox" value="' . $patent['id'] . '"></td>';
            $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($patent['case_code'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['case_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['business_dept_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['client_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['business_type'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['case_status'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['application_no'] ?? '') . '</td>';
            $html .= '<td>' . ($patent['application_date'] ? date('Y-m-d', strtotime($patent['application_date'])) : '') . '</td>';
            $html .= '<td>' . htmlspecialchars($patent['handler_name'] ?? '') . '</td>';
            $html .= '</tr>';
        }
    }
    echo json_encode([
        'success' => true,
        'html' => $html,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);
    exit;
}

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function getDownloadFileName($file_name, $original_file_name = null)
{
    // 优先使用自定义文件名，如果没有则使用原始文件名
    $display_name = !empty($file_name) ? $file_name : $original_file_name;

    if (empty($display_name)) {
        return '未命名文件';
    }

    // 如果自定义文件名没有扩展名，从原始文件名中提取扩展名
    if (!empty($file_name) && !empty($original_file_name)) {
        $display_ext = pathinfo($display_name, PATHINFO_EXTENSION);
        $original_ext = pathinfo($original_file_name, PATHINFO_EXTENSION);

        // 如果显示名称没有扩展名，但原始文件名有扩展名，则添加扩展名
        if (empty($display_ext) && !empty($original_ext)) {
            $display_name .= '.' . $original_ext;
        }
    }

    return $display_name;
}

function render_user_search($name, $users, $get_val)
{
    $val = isset($_GET[$name]) ? intval($_GET[$name]) : 0;
    $display = '';
    foreach ($users as $u) {
        if ($u['id'] == $val) {
            $display = htmlspecialchars($u['real_name'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
    return '<div class="module-select-search-box">'
        . '<input type="text" class="module-input module-select-search-input" name="' . $name . '_display" value="' . $display . '" readonly placeholder="点击选择" data-realname="' . $display . '">'
        . '<input type="hidden" name="' . $name . '" value="' . ($val ? $val : '') . '">'
        . '<div class="module-select-search-list" style="display:none;">'
        .   '<input type="text" class="module-select-search-list-input" placeholder="搜索姓名">'
        .   '<div class="module-select-search-list-items"></div>'
        . '</div>'
        . '</div>';
}

function render_dept_search($name, $departments, $get_val)
{
    $val = isset($_GET[$name]) ? intval($_GET[$name]) : 0;
    $display = '';
    foreach ($departments as $d) {
        if ($d['id'] == $val) {
            $display = htmlspecialchars($d['dept_name'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
    return '<div class="module-select-search-box">'
        . '<input type="text" class="module-input module-select-search-input" name="' . $name . '_display" value="' . $display . '" readonly placeholder="点击选择" data-deptname="' . $display . '">'
        . '<input type="hidden" name="' . $name . '" value="' . ($val ? $val : '') . '">'
        . '<div class="module-select-search-list" style="display:none;">'
        .   '<input type="text" class="module-select-search-list-input" placeholder="搜索部门">'
        .   '<div class="module-select-search-list-items"></div>'
        . '</div>'
        . '</div>';
}

function render_customer_search($name, $customers, $get_val)
{
    $val = isset($_GET[$name]) ? intval($_GET[$name]) : 0;
    $display = '';
    foreach ($customers as $c) {
        if ($c['id'] == $val) {
            $display = htmlspecialchars($c['customer_name_cn'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
    return '<div class="module-select-search-box">'
        . '<input type="text" class="module-input module-select-search-input" name="' . $name . '_display" value="' . $display . '" readonly placeholder="点击选择" data-customername="' . $display . '">'
        . '<input type="hidden" name="' . $name . '" value="' . ($val ? $val : '') . '">'
        . '<div class="module-select-search-list" style="display:none;">'
        .   '<input type="text" class="module-select-search-list-input" placeholder="搜索客户">'
        .   '<div class="module-select-search-list-items"></div>'
        . '</div>'
        . '</div>';
}
?>
<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
        <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
        <button type="button" class="btn-download" id="btn-download" disabled><i class="icon-save"></i> 下载核稿包</button>
    </div>
    <?php
    // 获取当前用户信息
    $current_user_stmt = $pdo->prepare("SELECT real_name FROM user WHERE id = ?");
    $current_user_stmt->execute([$_SESSION['user_id']]);
    $current_user = $current_user_stmt->fetch();
    $current_user_name = $current_user ? $current_user['real_name'] : '未知用户';
    ?>
    <?php render_info_notice("导出核稿包：可根据条件筛选并导出核稿案件包", 'info', 'icon-search'); ?>
    <form id="search-form" class="module-form" autocomplete="off">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="page_size" value="10">
        <table class="module-table" style="margin-bottom:15px;">
            <tr>
                <td class="module-label">我方文号：</td>
                <td><input type="text" name="case_code" class="module-input"></td>
                <td class="module-label">案件名称：</td>
                <td><input type="text" name="case_name" class="module-input"></td>
                <td class="module-label">申请号：</td>
                <td><input type="text" name="application_no" class="module-input"></td>
            </tr>
            <tr>
                <td class="module-label">承办部门：</td>
                <td><?= render_dept_search('business_dept_id', $departments, '') ?></td>
                <td class="module-label">客户名称：</td>
                <td><?= render_customer_search('client_id', $customers, '') ?></td>
                <td class="module-label">处理人：</td>
                <td><?= render_user_search('handler_id', $users, '') ?></td>
            </tr>
            <tr>
                <td class="module-label">业务类型：</td>
                <td><select name="business_type" class="module-input">
                        <option value="">--全部--</option><?php foreach ($business_types as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">案件状态：</td>
                <td><select name="case_status" class="module-input">
                        <option value="">--全部--</option><?php foreach ($case_statuses as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">是否配案：</td>
                <td><select name="is_allocated" class="module-input">
                        <option value="">--全部--</option>
                        <option value="是">是</option>
                        <option value="否">否</option>
                    </select></td>
            </tr>
            <tr>
                <td class="module-label">申请日期：</td>
                <td colspan="5">
                    <input type="date" name="application_date_start" class="module-input" style="width:200px;"> 至
                    <input type="date" name="application_date_end" class="module-input" style="width:200px;">
                </td>
            </tr>
        </table>
    </form>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:40px;text-align:center;"><input type="checkbox" id="select-all"></th>
                <th style="width:50px;text-align:center;">序号</th>
                <th style="width:100px;text-align:center;">我方文号</th>
                <th style="width:180px;">案件名称</th>
                <th style="width:100px;">承办部门</th>
                <th style="width:120px;">客户名称</th>
                <th style="width:100px;">业务类型</th>
                <th style="width:80px;">案件状态</th>
                <th style="width:120px;">申请号</th>
                <th style="width:100px;">申请日</th>
                <th style="width:100px;">处理人</th>
            </tr>
        </thead>
        <tbody id="patent-list">
            <tr>
                <td colspan="11" style="text-align:center;padding:20px 0;">正在加载数据...</td>
            </tr>
        </tbody>
    </table>
    <div class="module-pagination">
        <span>共 <span id="total-records">0</span> 条记录，每页</span>
        <select id="page-size-select">
            <option value="10" selected>10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        <span>条，当前 <span id="current-page">1</span>/<span id="total-pages">1</span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="" id="btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="" id="btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="" id="btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="page-input" min="1" value="1">
        <span>页</span>
        <button type="button" id="btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>

<script>
    (function() {
        var form = document.getElementById('search-form'),
            btnSearch = document.querySelector('.btn-search'),
            btnReset = document.querySelector('.btn-reset'),
            btnDownload = document.getElementById('btn-download'),
            patentList = document.getElementById('patent-list'),
            totalRecordsEl = document.getElementById('total-records'),
            currentPageEl = document.getElementById('current-page'),
            totalPagesEl = document.getElementById('total-pages'),
            btnFirstPage = document.getElementById('btn-first-page'),
            btnPrevPage = document.getElementById('btn-prev-page'),
            btnNextPage = document.getElementById('btn-next-page'),
            btnLastPage = document.getElementById('btn-last-page'),
            pageInput = document.getElementById('page-input'),
            btnPageJump = document.getElementById('btn-page-jump'),
            pageSizeSelect = document.getElementById('page-size-select'),
            selectAllCheckbox = document.getElementById('select-all');
        var currentPage = 1,
            pageSize = 10,
            totalPages = 1;

        window.loadPatentData = function() {
            patentList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);
            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') params.append(pair[0], pair[1]);
            }
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/patent_management/review_management/export_review_package.php';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                patentList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindCheckboxEvents();
                            } else {
                                patentList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            patentList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        patentList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }



        function bindCheckboxEvents() {
            // 全选/取消全选
            selectAllCheckbox.onchange = function() {
                var checkboxes = patentList.querySelectorAll('.case-checkbox');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateDownloadButtonState();
            };

            // 单个复选框变化
            patentList.querySelectorAll('.case-checkbox').forEach(function(checkbox) {
                checkbox.onchange = function() {
                    updateSelectAllState();
                    updateDownloadButtonState();
                };
            });
        }

        function updateSelectAllState() {
            var checkboxes = patentList.querySelectorAll('.case-checkbox');
            var checkedCount = patentList.querySelectorAll('.case-checkbox:checked').length;

            if (checkedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === checkboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        function updateDownloadButtonState() {
            var checkedCount = patentList.querySelectorAll('.case-checkbox:checked').length;
            btnDownload.disabled = checkedCount === 0;
        }



        function updatePaginationButtons() {
            btnFirstPage.disabled = currentPage <= 1;
            btnPrevPage.disabled = currentPage <= 1;
            btnNextPage.disabled = currentPage >= totalPages;
            btnLastPage.disabled = currentPage >= totalPages;
            btnPrevPage.setAttribute('data-page', currentPage - 1);
            btnNextPage.setAttribute('data-page', currentPage + 1);
            btnLastPage.setAttribute('data-page', totalPages);
            pageInput.max = totalPages;
            pageInput.value = currentPage;
        }

        btnSearch.onclick = function() {
            currentPage = 1;
            loadPatentData();
        };

        btnReset.onclick = function() {
            form.reset();
            document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
            document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
            // 重置复选框状态
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            updateDownloadButtonState();
            currentPage = 1;
            loadPatentData();
        };

        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            loadPatentData();
        };

        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    loadPatentData();
                }
            };
        });

        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            loadPatentData();
        };

        // 下载核稿包按钮事件
        btnDownload.onclick = function() {
            var checkedBoxes = patentList.querySelectorAll('.case-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('请先选择要下载的案件');
                return;
            }

            var caseIds = Array.from(checkedBoxes).map(function(checkbox) {
                return checkbox.value;
            }).join(',');

            if (confirm('确定要下载选中的 ' + checkedBoxes.length + ' 个案件的核稿包吗？')) {
                btnDownload.disabled = true;
                btnDownload.innerHTML = '<i class="icon-save"></i> 正在生成...';

                // 创建隐藏的表单来提交下载请求
                var form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                var baseUrl = window.location.href.split('?')[0];
                form.action = baseUrl.replace('index.php', '') + 'modules/patent_management/review_management/export_review_package.php';

                // 添加参数
                var actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'download_package';
                form.appendChild(actionInput);

                var directDownloadInput = document.createElement('input');
                directDownloadInput.type = 'hidden';
                directDownloadInput.name = 'direct_download';
                directDownloadInput.value = '1';
                form.appendChild(directDownloadInput);

                var caseIdsInput = document.createElement('input');
                caseIdsInput.type = 'hidden';
                caseIdsInput.name = 'case_ids';
                caseIdsInput.value = caseIds;
                form.appendChild(caseIdsInput);

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);

                // 延迟恢复按钮状态
                setTimeout(function() {
                    btnDownload.disabled = false;
                    btnDownload.innerHTML = '<i class="icon-save"></i> 下载核稿包';

                    // 清除选择状态
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                    patentList.querySelectorAll('.case-checkbox').forEach(function(checkbox) {
                        checkbox.checked = false;
                    });
                    updateDownloadButtonState();
                }, 2000);
            }
        };

        // 用户搜索下拉
        var userData = <?php echo json_encode($users, JSON_UNESCAPED_UNICODE); ?>;
        var deptData = <?php echo json_encode($departments, JSON_UNESCAPED_UNICODE); ?>;
        var customerData = <?php echo json_encode($customers, JSON_UNESCAPED_UNICODE); ?>;

        function bindUserSearch(box) {
            var input = box.querySelector('.module-select-search-input');
            var hidden = box.querySelector('input[type=hidden]');
            var list = box.querySelector('.module-select-search-list');
            var searchInput = list.querySelector('.module-select-search-list-input');
            var itemsDiv = list.querySelector('.module-select-search-list-items');
            var data = [];

            if (input.hasAttribute('data-realname')) {
                data = userData;
            } else if (input.hasAttribute('data-deptname')) {
                data = deptData;
            } else if (input.hasAttribute('data-customername')) {
                data = customerData;
            }

            function renderList(filter) {
                var html = '<div class="module-select-search-item" data-id="">--全部--</div>',
                    found = false;
                data.forEach(function(item) {
                    var displayName = item.real_name || item.dept_name || item.customer_name_cn;
                    if (!filter || displayName.indexOf(filter) !== -1) {
                        html += '<div class="module-select-search-item" data-id="' + item.id + '">' + displayName + '</div>';
                        found = true;
                    }
                });
                if (!found && filter) html += '<div class="no-match">无匹配</div>';
                itemsDiv.innerHTML = html;
            }
            input.onclick = function() {
                renderList('');
                list.style.display = 'block';
                searchInput.value = '';
                searchInput.focus();
            };
            searchInput.oninput = function() {
                renderList(searchInput.value.trim());
            };
            document.addEventListener('click', function(e) {
                if (!box.contains(e.target)) list.style.display = 'none';
            });
            itemsDiv.onmousedown = function(e) {
                var item = e.target.closest('.module-select-search-item');
                if (item) {
                    input.value = item.textContent === '--全部--' ? '' : item.textContent;
                    hidden.value = item.getAttribute('data-id');
                    list.style.display = 'none';
                }
            };
        }

        document.querySelectorAll('.module-select-search-box').forEach(bindUserSearch);
        loadPatentData();
    })();
</script>

<style>
    /* 复选框样式优化 */
    .case-checkbox,
    #select-all {
        cursor: pointer;
        transform: scale(1.1);
    }

    /* 表格行悬停效果 */
    .module-table tbody tr:hover {
        background-color: #f5f5f5;
    }

    /* 选中行样式 */
    .module-table tbody tr.module-selected {
        background-color: #e3f2fd !important;
    }

    /* 全选复选框的半选状态样式 */
    #select-all:indeterminate {
        background-color: #29b6b0;
    }

    /* 下载按钮样式 */
    .btn-download {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        margin-left: 8px;
    }

    .btn-download:hover:not(:disabled) {
        background-color: #45a049;
    }

    .btn-download:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }
</style>