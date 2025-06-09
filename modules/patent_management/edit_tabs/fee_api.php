<?php
// 专利编辑-费用信息API接口
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未登录或会话超时']);
    exit;
}

if (!isset($_GET['patent_id']) || intval($_GET['patent_id']) <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未指定专利ID']);
    exit;
}
$patent_id = intval($_GET['patent_id']);

// 验证专利是否存在
$patent_stmt = $pdo->prepare("SELECT id, case_name, process_item FROM patent_case_info WHERE id = ?");
$patent_stmt->execute([$patent_id]);
$patent_info = $patent_stmt->fetch();
if (!$patent_info) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未找到该专利信息']);
    exit;
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'get_fee_list') {
        try {
            // 获取费用列表
            $sql = "SELECT * FROM patent_case_official_fee WHERE patent_case_info_id = ? ORDER BY sequence_no ASC, id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$patent_id]);
            $rows = $stmt->fetchAll();

            $html = '';
            if (empty($rows)) {
                $html = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">暂无费用数据</td></tr>';
            } else {
                foreach ($rows as $index => $fee) {
                    $html .= '<tr data-id="' . $fee['id'] . '">' .
                        '<td style="text-align:center;"><input type="checkbox" value="' . $fee['id'] . '"></td>' .
                        '<td style="text-align:center;">' . ($fee['sequence_no'] ?? ($index + 1)) . '</td>' .
                        '<td>' . htmlspecialchars($fee['fee_name'] ?? '') . '</td>' .
                        '<td>' . htmlspecialchars($fee['fee_reduction_type'] ?? '') . '</td>' .
                        '<td>' . htmlspecialchars($fee['currency'] ?? '') . '</td>' .
                        '<td style="text-align:right;">' . number_format($fee['amount'] ?? 0, 2) . '</td>' .
                        '<td style="text-align:center;">' . ($fee['quantity'] ?? 1) . '</td>' .
                        '<td>' . htmlspecialchars($fee['actual_currency'] ?? '') . '</td>' .
                        '<td style="text-align:right;">' . number_format($fee['actual_amount'] ?? 0, 2) . '</td>' .
                        '<td>' . ($fee['receivable_date'] ?? '') . '</td>' .
                        '<td>' . ($fee['received_date'] ?? '') . '</td>' .
                        '<td style="text-align:center;">' . ($fee['is_verified'] ? '是' : '否') . '</td>' .
                        '</tr>';
                }
            }

            echo json_encode(['success' => true, 'html' => $html]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取费用列表失败: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'search_official_fees') {
        try {
            $keyword = $_POST['keyword'] ?? '';

            // 获取当前专利已添加的费用及其详细信息
            $existing_fees_sql = "SELECT fee_name, fee_reduction_type, currency, amount, quantity, 
                                         actual_currency, actual_amount, receivable_date, received_date 
                                  FROM patent_case_official_fee WHERE patent_case_info_id = ?";
            $existing_stmt = $pdo->prepare($existing_fees_sql);
            $existing_stmt->execute([$patent_id]);
            $existing_fees_data = $existing_stmt->fetchAll(PDO::FETCH_ASSOC);

            // 将已存在的费用按费用名称索引
            $existing_fees_map = [];
            foreach ($existing_fees_data as $fee) {
                $existing_fees_map[$fee['fee_name']] = $fee;
            }

            // 模拟官费模板数据
            $official_fees = [
                ['id' => 1, 'fee_name' => '布图设计登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 1000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 1000],
                ['id' => 2, 'fee_name' => '第一次延长期限请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                ['id' => 3, 'fee_name' => '发明专利申请审查费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 900, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 900],
                ['id' => 4, 'fee_name' => '发明专利复审费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 1000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 1000],
                ['id' => 5, 'fee_name' => '发明专利撤销请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                ['id' => 6, 'fee_name' => '发明专利权维持请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 30, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 30],
                ['id' => 7, 'fee_name' => '发明专利权无效宣告请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 3000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 3000],
                ['id' => 8, 'fee_name' => '发明专利申请费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 900, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 900],
                ['id' => 9, 'fee_name' => '发明专利申请实质审查费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 2500, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 2500],
                ['id' => 10, 'fee_name' => '复制文件费(加急)', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                ['id' => 11, 'fee_name' => '公布印刷费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 50, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 50],
                ['id' => 12, 'fee_name' => '优先权文件费的登记费和利用费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 1000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 1000],
                ['id' => 13, 'fee_name' => '优先权请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 1000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 1000],
                ['id' => 14, 'fee_name' => '检索报告传送费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 0, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 0],
                ['id' => 15, 'fee_name' => '检索费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 0, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 0],
                ['id' => 16, 'fee_name' => '年费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 0, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 0],
                ['id' => 17, 'fee_name' => '年费滞纳金', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 0, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 0],
                ['id' => 18, 'fee_name' => '强制许可使用费裁决请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                ['id' => 19, 'fee_name' => '权利要求附加费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                ['id' => 20, 'fee_name' => '说明书附加费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 50, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 50],
                ['id' => 21, 'fee_name' => '优先权要求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 80, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 80],
                ['id' => 22, 'fee_name' => '再次延长期限请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 2000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 2000],
                ['id' => 23, 'fee_name' => '在先申请文件副本费（加急）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                ['id' => 24, 'fee_name' => '中止程序请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 600, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 600],
                ['id' => 25, 'fee_name' => '著录事项变更费（发明人、申请人、专利权人）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 200, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 200],
                ['id' => 26, 'fee_name' => '著录事项变更手续费（集成电路布图设计）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                ['id' => 27, 'fee_name' => '专利申请权转让', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 200, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 200],
                ['id' => 28, 'fee_name' => '专利文件副本费（加急）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                ['id' => 29, 'fee_name' => '专利文件副本费（普通）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                ['id' => 30, 'fee_name' => '专利文件副本证明费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 30, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 30],
                ['id' => 31, 'fee_name' => '专利证书副本费（加急）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100]
            ];

            // 根据关键词过滤
            if (!empty($keyword)) {
                $official_fees = array_filter($official_fees, function ($fee) use ($keyword) {
                    return strpos($fee['fee_name'], $keyword) !== false;
                });
            }

            $html = '';
            if (empty($official_fees)) {
                $html = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">未找到匹配的费用</td></tr>';
            } else {
                $sequence_no = 1; // 使用独立的序号计数器
                foreach ($official_fees as $fee) {
                    // 检查该费用是否已添加，如果已添加则使用数据库中的数据
                    $is_checked = isset($existing_fees_map[$fee['fee_name']]) ? 'checked' : '';
                    $display_data = $fee; // 默认使用模板数据

                    if (isset($existing_fees_map[$fee['fee_name']])) {
                        // 如果已存在，使用数据库中的实际数据
                        $existing_data = $existing_fees_map[$fee['fee_name']];
                        $display_data = array_merge($fee, $existing_data);
                    }

                    // 生成下拉框选项
                    $fee_reduction_options = '';
                    foreach (['基础费用', '单位费减', '个人费减'] as $option) {
                        $selected = ($display_data['fee_reduction_type'] === $option) ? 'selected' : '';
                        $fee_reduction_options .= "<option value=\"{$option}\" {$selected}>{$option}</option>";
                    }

                    $actual_currency_options = '';
                    foreach (['CNY', 'USD', 'EUR'] as $option) {
                        $selected = ($display_data['actual_currency'] === $option) ? 'selected' : '';
                        $actual_currency_options .= "<option value=\"{$option}\" {$selected}>{$option}</option>";
                    }

                    $html .= '<tr data-template-id="' . $fee['id'] . '">' .
                        '<td style="text-align:center;"><input type="checkbox" class="fee-checkbox" value="' . $fee['id'] . '" ' . $is_checked . '></td>' .
                        '<td style="text-align:center;">' . $sequence_no . '</td>' .
                        '<td class="fee-name-cell">' . htmlspecialchars($fee['fee_name']) . '</td>' .
                        '<td><select class="fee-reduction-type-cell" style="width:80px;">' . $fee_reduction_options . '</select></td>' .
                        '<td style="text-align:center;">' . $display_data['currency'] . '</td>' .
                        '<td style="text-align:right;">' . $display_data['amount'] . '</td>' .
                        '<td><input type="number" class="fee-quantity-cell" value="' . $display_data['quantity'] . '" style="width:60px;" min="1"></td>' .
                        '<td><select class="fee-actual-currency-cell" style="width:60px;">' . $actual_currency_options . '</select></td>' .
                        '<td><input type="number" class="fee-actual-amount-cell" value="' . $display_data['actual_amount'] . '" style="width:80px;" step="0.01" min="0"></td>' .
                        '<td><input type="date" class="fee-receivable-date-cell" value="' . ($display_data['receivable_date'] ?? '') . '" style="width:100px;"></td>' .
                        '<td><input type="date" class="fee-received-date-cell" value="' . ($display_data['received_date'] ?? '') . '" style="width:100px;"></td>' .
                        '</tr>';
                    $sequence_no++; // 递增序号
                }
            }

            echo json_encode(['success' => true, 'html' => $html]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '搜索失败: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'add_selected_fees') {
        try {
            $selected_fees = $_POST['selected_fees'] ?? '';
            $selected_ids = !empty($selected_fees) ? explode(',', $selected_fees) : [];

            // 获取弹窗中修改的费用数据
            $updated_fees_data = $_POST['updated_fees_data'] ?? '';
            $updated_fees = [];
            if (!empty($updated_fees_data)) {
                $updated_fees = json_decode($updated_fees_data, true) ?: [];
            }

            // 模拟官费模板数据（与search_official_fees中的数据保持一致）
            $official_fees = [
                1 => ['fee_name' => '布图设计登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 1000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 1000],
                2 => ['fee_name' => '第一次延长期限请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                3 => ['fee_name' => '发明专利申请审查费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 900, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 900],
                4 => ['fee_name' => '发明专利复审费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 1000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 1000],
                5 => ['fee_name' => '发明专利撤销请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                6 => ['fee_name' => '发明专利权维持请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 30, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 30],
                7 => ['fee_name' => '发明专利权无效宣告请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 3000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 3000],
                8 => ['fee_name' => '发明专利申请费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 900, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 900],
                9 => ['fee_name' => '发明专利申请实质审查费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 2500, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 2500],
                10 => ['fee_name' => '复制文件费(加急)', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                11 => ['fee_name' => '公布印刷费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 50, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 50],
                12 => ['fee_name' => '优先权文件费的登记费和利用费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 1000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 1000],
                13 => ['fee_name' => '优先权请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 1000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 1000],
                14 => ['fee_name' => '检索报告传送费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 0, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 0],
                15 => ['fee_name' => '检索费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 0, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 0],
                16 => ['fee_name' => '年费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 0, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 0],
                17 => ['fee_name' => '年费滞纳金', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 0, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 0],
                18 => ['fee_name' => '强制许可使用费裁决请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                19 => ['fee_name' => '权利要求附加费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                20 => ['fee_name' => '说明书附加费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 50, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 50],
                21 => ['fee_name' => '优先权要求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 80, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 80],
                22 => ['fee_name' => '再次延长期限请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 2000, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 2000],
                23 => ['fee_name' => '在先申请文件副本费（加急）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                24 => ['fee_name' => '中止程序请求费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 600, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 600],
                25 => ['fee_name' => '著录事项变更费（发明人、申请人、专利权人）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 200, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 200],
                26 => ['fee_name' => '著录事项变更手续费（集成电路布图设计）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                27 => ['fee_name' => '专利申请权转让', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 200, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 200],
                28 => ['fee_name' => '专利文件副本费（加急）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                29 => ['fee_name' => '专利文件副本费（普通）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                30 => ['fee_name' => '专利文件副本证明费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 30, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 30],
                31 => ['fee_name' => '专利证书副本费（加急）', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100]
            ];

            // 获取当前已添加的费用
            $existing_fees_sql = "SELECT id, fee_name FROM patent_case_official_fee WHERE patent_case_info_id = ?";
            $existing_stmt = $pdo->prepare($existing_fees_sql);
            $existing_stmt->execute([$patent_id]);
            $existing_fees = $existing_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // id => fee_name

            // 构建应该存在的费用名称列表
            $should_exist_fee_names = [];
            foreach ($selected_ids as $fee_id) {
                $fee_id = intval($fee_id);
                if (isset($official_fees[$fee_id])) {
                    $should_exist_fee_names[] = $official_fees[$fee_id]['fee_name'];
                }
            }

            $pdo->beginTransaction();

            $added_count = 0;
            $updated_count = 0;
            $deleted_count = 0;

            // 1. 处理选中的费用（添加新的或更新已存在的）
            foreach ($selected_ids as $fee_id) {
                $fee_id = intval($fee_id);
                if (!isset($official_fees[$fee_id])) {
                    continue;
                }

                $fee_template = $official_fees[$fee_id];
                $fee_name = $fee_template['fee_name'];

                // 获取弹窗中修改的数据
                $updated_data = isset($updated_fees[$fee_id]) ? $updated_fees[$fee_id] : [];

                // 合并模板数据和用户修改的数据
                $fee_data = array_merge($fee_template, $updated_data);

                // 检查是否已存在
                $existing_fee_id = null;
                foreach ($existing_fees as $existing_id => $existing_fee_name) {
                    if ($existing_fee_name === $fee_name) {
                        $existing_fee_id = $existing_id;
                        break;
                    }
                }

                if ($existing_fee_id) {
                    // 更新已存在的费用
                    $update_sql = "UPDATE patent_case_official_fee SET 
                        fee_reduction_type = ?, currency = ?, amount = ?, quantity = ?, 
                        actual_currency = ?, actual_amount = ?, receivable_date = ?, received_date = ?, 
                        updated_at = NOW() 
                        WHERE id = ? AND patent_case_info_id = ?";

                    $update_stmt = $pdo->prepare($update_sql);
                    $result = $update_stmt->execute([
                        $fee_data['fee_reduction_type'],
                        $fee_data['currency'],
                        $fee_data['amount'],
                        $fee_data['quantity'],
                        $fee_data['actual_currency'],
                        $fee_data['actual_amount'],
                        !empty($fee_data['receivable_date']) ? $fee_data['receivable_date'] : null,
                        !empty($fee_data['received_date']) ? $fee_data['received_date'] : null,
                        $existing_fee_id,
                        $patent_id
                    ]);

                    if ($result) {
                        $updated_count++;
                    }
                } else {
                    // 添加新费用
                    // 获取下一个序号
                    $seq_sql = "SELECT COALESCE(MAX(sequence_no), 0) + 1 as next_seq FROM patent_case_official_fee WHERE patent_case_info_id = ?";
                    $seq_stmt = $pdo->prepare($seq_sql);
                    $seq_stmt->execute([$patent_id]);
                    $next_seq = $seq_stmt->fetchColumn();

                    // 插入费用记录
                    $insert_sql = "INSERT INTO patent_case_official_fee (
                        patent_case_info_id, sequence_no, fee_name, fee_reduction_type, 
                        currency, amount, quantity, actual_currency, actual_amount, 
                        receivable_date, received_date, is_verified, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())";

                    $insert_stmt = $pdo->prepare($insert_sql);
                    $result = $insert_stmt->execute([
                        $patent_id,
                        $next_seq,
                        $fee_data['fee_name'],
                        $fee_data['fee_reduction_type'],
                        $fee_data['currency'],
                        $fee_data['amount'],
                        $fee_data['quantity'],
                        $fee_data['actual_currency'],
                        $fee_data['actual_amount'],
                        !empty($fee_data['receivable_date']) ? $fee_data['receivable_date'] : null,
                        !empty($fee_data['received_date']) ? $fee_data['received_date'] : null
                    ]);

                    if ($result) {
                        $added_count++;
                    }
                }
            }

            // 2. 删除未选中的费用
            foreach ($existing_fees as $existing_id => $existing_fee_name) {
                if (!in_array($existing_fee_name, $should_exist_fee_names)) {
                    $delete_sql = "DELETE FROM patent_case_official_fee WHERE id = ? AND patent_case_info_id = ?";
                    $delete_stmt = $pdo->prepare($delete_sql);
                    $result = $delete_stmt->execute([$existing_id, $patent_id]);

                    if ($result) {
                        $deleted_count++;
                    }
                }
            }

            $pdo->commit();

            // 构建返回消息
            $messages = [];
            if ($added_count > 0) {
                $messages[] = "新增 {$added_count} 项费用";
            }
            if ($updated_count > 0) {
                $messages[] = "更新 {$updated_count} 项费用";
            }
            if ($deleted_count > 0) {
                $messages[] = "删除 {$deleted_count} 项费用";
            }

            if (empty($messages)) {
                $msg = '费用列表无变化';
            } else {
                $msg = implode('，', $messages);
            }

            echo json_encode(['success' => true, 'msg' => $msg]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'set_review_status') {
        try {
            $fee_ids = $_POST['fee_ids'] ?? '';
            if (empty($fee_ids)) {
                echo json_encode(['success' => false, 'msg' => '未选择任何费用']);
                exit;
            }

            $ids = explode(',', $fee_ids);
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, function ($id) {
                return $id > 0;
            });

            if (empty($ids)) {
                echo json_encode(['success' => false, 'msg' => '无效的费用ID']);
                exit;
            }

            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "UPDATE patent_case_official_fee SET is_verified = 1, updated_at = NOW() 
                    WHERE id IN ($placeholders) AND patent_case_info_id = ?";

            $params = array_merge($ids, [$patent_id]);
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                $affected_rows = $stmt->rowCount();
                echo json_encode(['success' => true, 'msg' => "成功设置 {$affected_rows} 项费用为已核查"]);
            } else {
                echo json_encode(['success' => false, 'msg' => '设置失败']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '设置失败: ' . $e->getMessage()]);
        }
        exit;
    }

    // 未知操作
    echo json_encode(['success' => false, 'msg' => '未知操作']);
    exit;
}
