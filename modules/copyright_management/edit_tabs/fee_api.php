<?php
// 版权编辑-费用信息API接口
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未登录或会话超时']);
    exit;
}

if (!isset($_GET['copyright_id']) || intval($_GET['copyright_id']) <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未指定版权ID']);
    exit;
}
$copyright_id = intval($_GET['copyright_id']);

// 验证版权是否存在
$copyright_stmt = $pdo->prepare("SELECT id, case_name, process_item FROM copyright_case_info WHERE id = ?");
$copyright_stmt->execute([$copyright_id]);
$copyright_info = $copyright_stmt->fetch();
if (!$copyright_info) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未找到该版权信息']);
    exit;
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'get_fee_list') {
        try {
            // 获取费用列表
            $sql = "SELECT * FROM copyright_case_official_fee WHERE copyright_case_info_id = ? ORDER BY sequence_no ASC, id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$copyright_id]);
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

            // 获取当前版权已添加的费用及其详细信息
            $existing_fees_sql = "SELECT fee_name, fee_reduction_type, currency, amount, quantity, 
                                         actual_currency, actual_amount, receivable_date, received_date 
                                  FROM copyright_case_official_fee WHERE copyright_case_info_id = ?";
            $existing_stmt = $pdo->prepare($existing_fees_sql);
            $existing_stmt->execute([$copyright_id]);
            $existing_fees_data = $existing_stmt->fetchAll(PDO::FETCH_ASSOC);

            // 将已存在的费用按费用名称索引
            $existing_fees_map = [];
            foreach ($existing_fees_data as $fee) {
                $existing_fees_map[$fee['fee_name']] = $fee;
            }

            // 版权官费模板数据
            $official_fees = [
                1 => ['id' => 1, 'fee_name' => '作品著作权登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                2 => ['id' => 2, 'fee_name' => '计算机软件著作权登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 250, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 250],
                3 => ['id' => 3, 'fee_name' => '作品著作权登记查询费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                4 => ['id' => 4, 'fee_name' => '软件著作权登记查询费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 120, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 120],
                5 => ['id' => 5, 'fee_name' => '著作权登记证书费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 50, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 50],
                6 => ['id' => 6, 'fee_name' => '著作权登记证书副本费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 20, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 20],
                7 => ['id' => 7, 'fee_name' => '著作权变更或补充登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                8 => ['id' => 8, 'fee_name' => '软件著作权变更或补充登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 320, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 320],
                9 => ['id' => 9, 'fee_name' => '著作权撤销登记申请费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 200, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 200],
                10 => ['id' => 10, 'fee_name' => '著作权异议申请费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                11 => ['id' => 11, 'fee_name' => '著作权复审申请费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                12 => ['id' => 12, 'fee_name' => '著作权登记档案复制费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 30, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 30],
                13 => ['id' => 13, 'fee_name' => '著作权登记鉴定费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 320, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 320],
                14 => ['id' => 14, 'fee_name' => '著作权合同登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                15 => ['id' => 15, 'fee_name' => '著作权质权登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                16 => ['id' => 16, 'fee_name' => '著作权专有许可合同登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                17 => ['id' => 17, 'fee_name' => '著作权转让合同登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                18 => ['id' => 18, 'fee_name' => '著作权继承登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 200, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 200],
                19 => ['id' => 19, 'fee_name' => '著作权登记加急费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                20 => ['id' => 20, 'fee_name' => '软件著作权登记加急费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 450, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 450],
                21 => ['id' => 21, 'fee_name' => '著作权登记延期费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                22 => ['id' => 22, 'fee_name' => '著作权登记恢复费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 200, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 200],
                23 => ['id' => 23, 'fee_name' => '著作权登记文件副本费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 30, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 30],
                24 => ['id' => 24, 'fee_name' => '著作权登记公告费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 50, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 50],
                25 => ['id' => 25, 'fee_name' => '著作权登记证明费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 80, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 80],
                26 => ['id' => 26, 'fee_name' => '著作权登记检索费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                27 => ['id' => 27, 'fee_name' => '著作权登记翻译费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                28 => ['id' => 28, 'fee_name' => '著作权登记邮寄费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 20, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 20],
                29 => ['id' => 29, 'fee_name' => '著作权登记其他费用', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                30 => ['id' => 30, 'fee_name' => '代理服务费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 500, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 500]
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

            // 版权官费模板数据（与search_official_fees中的数据保持一致）
            $official_fees = [
                1 => ['fee_name' => '作品著作权登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                2 => ['fee_name' => '计算机软件著作权登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 250, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 250],
                3 => ['fee_name' => '作品著作权登记查询费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                4 => ['fee_name' => '软件著作权登记查询费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 120, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 120],
                5 => ['fee_name' => '著作权登记证书费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 50, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 50],
                6 => ['fee_name' => '著作权登记证书副本费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 20, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 20],
                7 => ['fee_name' => '著作权变更或补充登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                8 => ['fee_name' => '软件著作权变更或补充登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 320, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 320],
                9 => ['fee_name' => '著作权撤销登记申请费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 200, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 200],
                10 => ['fee_name' => '著作权异议申请费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                11 => ['fee_name' => '著作权复审申请费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                12 => ['fee_name' => '著作权登记档案复制费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 30, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 30],
                13 => ['fee_name' => '著作权登记鉴定费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 320, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 320],
                14 => ['fee_name' => '著作权合同登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                15 => ['fee_name' => '著作权质权登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                16 => ['fee_name' => '著作权专有许可合同登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                17 => ['fee_name' => '著作权转让合同登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                18 => ['fee_name' => '著作权继承登记费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 200, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 200],
                19 => ['fee_name' => '著作权登记加急费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                20 => ['fee_name' => '软件著作权登记加急费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 450, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 450],
                21 => ['fee_name' => '著作权登记延期费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                22 => ['fee_name' => '著作权登记恢复费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 200, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 200],
                23 => ['fee_name' => '著作权登记文件副本费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 30, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 30],
                24 => ['fee_name' => '著作权登记公告费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 50, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 50],
                25 => ['fee_name' => '著作权登记证明费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 80, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 80],
                26 => ['fee_name' => '著作权登记检索费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 150, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 150],
                27 => ['fee_name' => '著作权登记翻译费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 300, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 300],
                28 => ['fee_name' => '著作权登记邮寄费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 20, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 20],
                29 => ['fee_name' => '著作权登记其他费用', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 100, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 100],
                30 => ['fee_name' => '代理服务费', 'fee_reduction_type' => '基础费用', 'currency' => 'CNY', 'amount' => 500, 'quantity' => 1, 'actual_currency' => 'CNY', 'actual_amount' => 500]
            ];

            // 获取当前已添加的费用
            $existing_fees_sql = "SELECT id, fee_name FROM copyright_case_official_fee WHERE copyright_case_info_id = ?";
            $existing_stmt = $pdo->prepare($existing_fees_sql);
            $existing_stmt->execute([$copyright_id]);
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
                    $update_sql = "UPDATE copyright_case_official_fee SET 
                        fee_reduction_type = ?, currency = ?, amount = ?, quantity = ?, 
                        actual_currency = ?, actual_amount = ?, receivable_date = ?, received_date = ?, 
                        updated_at = NOW() 
                        WHERE id = ? AND copyright_case_info_id = ?";

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
                        $copyright_id
                    ]);

                    if ($result) {
                        $updated_count++;
                    }
                } else {
                    // 添加新费用
                    // 获取下一个序号
                    $seq_sql = "SELECT COALESCE(MAX(sequence_no), 0) + 1 as next_seq FROM copyright_case_official_fee WHERE copyright_case_info_id = ?";
                    $seq_stmt = $pdo->prepare($seq_sql);
                    $seq_stmt->execute([$copyright_id]);
                    $next_seq = $seq_stmt->fetchColumn();

                    // 插入费用记录
                    $insert_sql = "INSERT INTO copyright_case_official_fee (
                        copyright_case_info_id, sequence_no, fee_name, fee_reduction_type, 
                        currency, amount, quantity, actual_currency, actual_amount, 
                        receivable_date, received_date, is_verified, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())";

                    $insert_stmt = $pdo->prepare($insert_sql);
                    $result = $insert_stmt->execute([
                        $copyright_id,
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
                    $delete_sql = "DELETE FROM copyright_case_official_fee WHERE id = ? AND copyright_case_info_id = ?";
                    $delete_stmt = $pdo->prepare($delete_sql);
                    $result = $delete_stmt->execute([$existing_id, $copyright_id]);

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
            $sql = "UPDATE copyright_case_official_fee SET is_verified = 1, updated_at = NOW() 
                    WHERE id IN ($placeholders) AND copyright_case_info_id = ?";

            $params = array_merge($ids, [$copyright_id]);
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
