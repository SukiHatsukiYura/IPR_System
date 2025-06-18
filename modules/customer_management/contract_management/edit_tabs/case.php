<?php
include_once(__DIR__ . '/../../../../database.php');
include_once(__DIR__ . '/../../../../common/functions.php');
check_access_via_framework();
session_start();

// 验证参数
if (!isset($_GET['contract_id']) || intval($_GET['contract_id']) <= 0) {
    echo '<div class="module-error">未指定合同ID</div>';
    exit;
}

$contract_id = intval($_GET['contract_id']);

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'save_case') {
        try {
            $id = intval($_POST['id'] ?? 0);

            // 处理已有案件信息
            $existing_case_id = null;
            $existing_case_code = '';
            $existing_case_name = '';

            // 如果选择了已有案件，从existing_case_search字段中解析信息
            $has_existing_case = intval($_POST['has_existing_case'] ?? 0);
            if ($has_existing_case && !empty($_POST['existing_case_search'])) {
                $existing_case_value = $_POST['existing_case_search'];
                // 解析格式：id|案件类型
                $parts = explode('|', $existing_case_value);
                if (count($parts) >= 2) {
                    $existing_case_id = intval($parts[0]);
                    $case_type = $parts[1];

                    // 从显示字段中解析案件编号和名称
                    $display_text = $_POST['existing_case_search_display'] ?? '';
                    if ($display_text) {
                        $text_parts = explode(' - ', $display_text);
                        $existing_case_code = $text_parts[0] ?? '';
                        if (count($text_parts) > 1) {
                            $remaining_text = implode(' - ', array_slice($text_parts, 1));
                            // 移除案件类型部分 (专利)、(商标)、(版权)
                            $existing_case_name = preg_replace('/\s*\([^)]+\)$/', '', $remaining_text);
                        }
                    }
                }
            }

            $data = [
                'contract_id' => $contract_id,
                'case_type' => trim($_POST['case_type'] ?? ''),
                'has_existing_case' => $has_existing_case,
                'existing_case_id' => $existing_case_id,
                'existing_case_code' => $existing_case_code,
                'existing_case_name' => $existing_case_name,
                'is_case_opened' => trim($_POST['is_case_opened'] ?? ''),
                'application_country' => trim($_POST['application_country'] ?? '中国'),
                'case_name' => trim($_POST['case_name'] ?? ''),
                'business_dept_id' => !empty($_POST['business_dept_id']) ? intval($_POST['business_dept_id']) : null,
                'official_fee' => !empty($_POST['official_fee']) ? floatval($_POST['official_fee']) : null,
                'contract_amount' => !empty($_POST['contract_amount']) ? floatval($_POST['contract_amount']) : null,
                'cost' => !empty($_POST['cost']) ? floatval($_POST['cost']) : null,
                'is_invoiced' => trim($_POST['is_invoiced'] ?? ''),
                'case_remarks' => trim($_POST['case_remarks'] ?? ''),
                'business_type' => trim($_POST['business_type'] ?? ''),
                'application_type' => trim($_POST['application_type'] ?? ''),
                'external_agent' => trim($_POST['external_agent'] ?? ''),
                'agency_fee' => !empty($_POST['agency_fee']) ? floatval($_POST['agency_fee']) : null,
                'cost_type' => trim($_POST['cost_type'] ?? ''),
                'page_count' => !empty($_POST['page_count']) ? intval($_POST['page_count']) : null,
                'invoice_amount' => !empty($_POST['invoice_amount']) ? floatval($_POST['invoice_amount']) : null
            ];

            if ($id > 0) {
                // 更新
                $set = [];
                $params = [];
                foreach ($data as $key => $value) {
                    if ($key !== 'contract_id') {
                        $set[] = "`{$key}` = ?";
                        $params[] = $value;
                    }
                }
                $params[] = $id;
                $params[] = $contract_id;

                $sql = "UPDATE contract_case_info SET " . implode(', ', $set) . " WHERE id = ? AND contract_id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($params);
            } else {
                // 新增
                $fields = array_keys($data);
                $placeholders = array_fill(0, count($fields), '?');
                $sql = "INSERT INTO contract_case_info (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute(array_values($data));
            }

            echo json_encode(['success' => $result, 'msg' => $result ? null : '保存失败']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_case') {
        try {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('无效的案件ID');
            }

            $stmt = $pdo->prepare("DELETE FROM contract_case_info WHERE id = ? AND contract_id = ?");
            $result = $stmt->execute([$id, $contract_id]);

            echo json_encode(['success' => $result, 'msg' => $result ? null : '删除失败']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '删除失败: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_case') {
        try {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('无效的案件ID');
            }

            $stmt = $pdo->prepare("SELECT * FROM contract_case_info WHERE id = ? AND contract_id = ?");
            $stmt->execute([$id, $contract_id]);
            $case_info = $stmt->fetch();

            if (!$case_info) {
                throw new Exception('案件信息不存在');
            }

            echo json_encode(['success' => true, 'data' => $case_info]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'search_existing_cases') {
        try {
            $case_type = trim($_POST['case_type'] ?? '');
            $search_text = trim($_POST['search_text'] ?? '');

            if (empty($case_type) || empty($search_text)) {
                echo json_encode(['success' => false, 'msg' => '请选择案件类型并输入搜索关键词']);
                exit;
            }

            $results = [];

            // 根据案件类型搜索不同的表
            if ($case_type === '专利') {
                $sql = "SELECT id, case_code, case_name, '专利' as case_type FROM patent_case_info 
                        WHERE (case_code LIKE ? OR case_name LIKE ?) 
                        ORDER BY created_at DESC LIMIT 50";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(["%{$search_text}%", "%{$search_text}%"]);
                $results = $stmt->fetchAll();
            } elseif ($case_type === '商标') {
                $sql = "SELECT id, case_code, case_name, '商标' as case_type FROM trademark_case_info 
                        WHERE (case_code LIKE ? OR case_name LIKE ?) 
                        ORDER BY created_at DESC LIMIT 50";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(["%{$search_text}%", "%{$search_text}%"]);
                $results = $stmt->fetchAll();
            } elseif ($case_type === '版权') {
                $sql = "SELECT id, case_code, case_name, '版权' as case_type FROM copyright_case_info 
                        WHERE (case_code LIKE ? OR case_name LIKE ?) 
                        ORDER BY created_at DESC LIMIT 50";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(["%{$search_text}%", "%{$search_text}%"]);
                $results = $stmt->fetchAll();
            } else {
                // 如果没有指定案件类型，搜索所有类型
                $patent_sql = "SELECT id, case_code, case_name, '专利' as case_type FROM patent_case_info 
                               WHERE (case_code LIKE ? OR case_name LIKE ?) 
                               ORDER BY created_at DESC LIMIT 20";
                $trademark_sql = "SELECT id, case_code, case_name, '商标' as case_type FROM trademark_case_info 
                                  WHERE (case_code LIKE ? OR case_name LIKE ?) 
                                  ORDER BY created_at DESC LIMIT 20";
                $copyright_sql = "SELECT id, case_code, case_name, '版权' as case_type FROM copyright_case_info 
                                  WHERE (case_code LIKE ? OR case_name LIKE ?) 
                                  ORDER BY created_at DESC LIMIT 20";

                $stmt = $pdo->prepare($patent_sql);
                $stmt->execute(["%{$search_text}%", "%{$search_text}%"]);
                $patent_results = $stmt->fetchAll();

                $stmt = $pdo->prepare($trademark_sql);
                $stmt->execute(["%{$search_text}%", "%{$search_text}%"]);
                $trademark_results = $stmt->fetchAll();

                $stmt = $pdo->prepare($copyright_sql);
                $stmt->execute(["%{$search_text}%", "%{$search_text}%"]);
                $copyright_results = $stmt->fetchAll();

                $results = array_merge($patent_results, $trademark_results, $copyright_results);
            }

            echo json_encode(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '搜索失败: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_existing_case_details') {
        try {
            $case_id = intval($_POST['case_id'] ?? 0);
            $case_type = trim($_POST['case_type'] ?? '');

            if ($case_id <= 0 || empty($case_type)) {
                throw new Exception('无效的案件ID或案件类型');
            }

            $case_data = null;

            // 根据案件类型查询不同的表
            if ($case_type === '专利') {
                $stmt = $pdo->prepare("SELECT case_name, country, application_type FROM patent_case_info WHERE id = ?");
                $stmt->execute([$case_id]);
                $case_data = $stmt->fetch();
            } elseif ($case_type === '商标') {
                $stmt = $pdo->prepare("SELECT case_name, country, trademark_type as application_type FROM trademark_case_info WHERE id = ?");
                $stmt->execute([$case_id]);
                $case_data = $stmt->fetch();
            } elseif ($case_type === '版权') {
                $stmt = $pdo->prepare("SELECT case_name, country, application_type FROM copyright_case_info WHERE id = ?");
                $stmt->execute([$case_id]);
                $case_data = $stmt->fetch();
                // 版权案件默认国家为中国（如果country字段为空）
                if ($case_data && empty($case_data['country'])) {
                    $case_data['country'] = '中国';
                }
            }

            if (!$case_data) {
                throw new Exception('案件信息不存在');
            }

            echo json_encode(['success' => true, 'data' => $case_data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取案件详情失败: ' . $e->getMessage()]);
        }
        exit;
    }
}

// 查询案件信息列表
$case_stmt = $pdo->prepare("
    SELECT c.*, d.dept_name 
    FROM contract_case_info c 
    LEFT JOIN department d ON c.business_dept_id = d.id 
    WHERE c.contract_id = ? 
    ORDER BY c.created_at DESC
");
$case_stmt->execute([$contract_id]);
$case_list = $case_stmt->fetchAll();

// 获取部门列表
$dept_stmt = $pdo->prepare("SELECT id, dept_name FROM department WHERE is_active = 1 ORDER BY sort_order, dept_name");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll();
$dept_options = [];
foreach ($departments as $dept) {
    $dept_options[$dept['id']] = $dept['dept_name'];
}

// 获取所有案件列表用于搜索下拉框
$existing_cases = [];
try {
    // 获取专利案件
    $stmt = $pdo->prepare("SELECT id, case_code, case_name, '专利' as case_type FROM patent_case_info ORDER BY created_at DESC");
    $stmt->execute();
    $patent_cases = $stmt->fetchAll();

    // 获取商标案件
    $stmt = $pdo->prepare("SELECT id, case_code, case_name, '商标' as case_type FROM trademark_case_info ORDER BY created_at DESC");
    $stmt->execute();
    $trademark_cases = $stmt->fetchAll();

    // 获取版权案件
    $stmt = $pdo->prepare("SELECT id, case_code, case_name, '版权' as case_type FROM copyright_case_info ORDER BY created_at DESC");
    $stmt->execute();
    $copyright_cases = $stmt->fetchAll();

    $existing_cases = array_merge($patent_cases, $trademark_cases, $copyright_cases);
} catch (Exception $e) {
    // 如果表不存在则忽略错误
}

// 为搜索下拉框准备数据
$case_options = [];
foreach ($existing_cases as $case) {
    $display_text = ($case['case_code'] ? $case['case_code'] . ' - ' : '') . $case['case_name'] . ' (' . $case['case_type'] . ')';
    $case_options[$case['id'] . '|' . $case['case_type']] = $display_text;
}

// 定义选项数据
$case_types = ['专利', '商标', '版权'];
$yes_no_options = ['是', '否'];
$application_types = [
    '发明专利',
    '实用新型',
    '外观设计',
    '商标注册',
    '商标续展',
    '商标变更',
    '软件著作权',
    '作品著作权',
    '其他'
];
$cost_types = [
    '发明费减',
    '实用新型费减',
    '外观设计费减',
    '发明基础',
    '实用新型基础',
    '外观设计基础',
    '商标基础',
    '版权基础'
];

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function render_select($name, $options, $val = '', $placeholder = '--请选择--')
{
    $html = "<select name=\"{$name}\" class=\"module-input\">";
    $html .= "<option value=\"\">{$placeholder}</option>";
    if (is_array($options)) {
        foreach ($options as $k => $v) {
            $selected = ($val == $k || $val == $v) ? ' selected' : '';
            $value = is_numeric($k) ? $v : $k;
            $html .= "<option value=\"{$value}\"{$selected}>{$v}</option>";
        }
    }
    $html .= "</select>";
    return $html;
}

render_select_search_assets();
?>

<style>
    /* 确保案件信息tab中的所有输入框都是白色背景 */
    #case-form .module-input:not([readonly]):not(:disabled),
    #case-form .module-textarea:not([readonly]):not(:disabled) {
        background: #fff !important;
    }

    /* 强制设置搜索下拉框为白色背景 - 最高优先级 */
    #case-modal .module-select-search-input,
    #case-form .module-select-search-input,
    .module-select-search-input {
        background: #fff !important;
        border: 1px solid #e0e0e0 !important;
        border-radius: 3px !important;
        padding: 5px 32px 5px 8px !important;
        font-size: 15px !important;
        color: #333 !important;
        outline: none !important;
        box-sizing: border-box !important;
        width: 100% !important;
        cursor: pointer !important;
    }

    /* 禁用状态的输入框保持灰色背景 */
    #case-form .module-input[readonly],
    #case-form .module-input:disabled {
        background: #f3f3f3 !important;
        color: #888;
        cursor: not-allowed;
    }

    /* 禁用状态的搜索下拉框 */
    #case-modal .module-select-search-input.disabled-state,
    #case-form .module-select-search-input.disabled-state,
    #case-modal .module-select-search-input[readonly],
    #case-form .module-select-search-input[readonly] {
        background: #f3f3f3 !important;
        color: #888 !important;
        cursor: not-allowed !important;
        border-color: #d0d0d0 !important;
    }

    /* 禁用状态下不显示下拉箭头 */
    #case-modal .module-select-search-input.disabled-state,
    #case-form .module-select-search-input.disabled-state,
    #case-modal .module-select-search-input[readonly],
    #case-form .module-select-search-input[readonly] {
        background: #f3f3f3 !important;
        padding-right: 8px !important;
        /* 移除下拉箭头的右边距 */
    }

    /* 确保下拉箭头正确显示 */
    #case-modal .module-select-search-input:not(.disabled-state),
    #case-form .module-select-search-input:not(.disabled-state) {
        background: #fff url('data:image/svg+xml;utf8,<svg fill="%23999" height="16" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 8px center/16px 16px !important;
    }

    /* 覆盖可能的全局样式 */
    #case-modal .module-select-search-box .module-select-search-input,
    #case-form .module-select-search-box .module-select-search-input {
        background-color: #fff !important;
    }
</style>

<div class="module-btns">
    <button type="button" class="btn-mini btn-add-case" style="background:#29b6b0;color:#fff;"><i class="icon-add"></i> 添加案件</button>
</div>

<!-- 案件信息列表 -->
<div style="max-height:500px;overflow-y:auto;border:1px solid #e0e0e0;">
    <table class="module-table" style="margin:0;border:none;width:100%;table-layout:fixed;">
        <colgroup>
            <col style="width:80px;">
            <col style="width:100px;">
            <col style="width:150px;">
            <col style="width:100px;">
            <col style="width:120px;">
            <col style="width:100px;">
            <col style="width:100px;">
            <col style="width:100px;">
            <col style="width:120px;">
        </colgroup>
        <thead>
            <tr style="background:#f8f9fa;">
                <th>案件类型</th>
                <th>是否已有案件</th>
                <th>案件名称</th>
                <th>申请国家</th>
                <th>承办部门</th>
                <th>官费</th>
                <th>代理费</th>
                <th>成本</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="case-list">
            <?php if (empty($case_list)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;padding:20px 0;color:#888;">暂无案件信息</td>
                </tr>
            <?php else: ?>
                <?php foreach ($case_list as $case): ?>
                    <tr data-id="<?= $case['id'] ?>">
                        <td><?= h($case['case_type']) ?></td>
                        <td><?= $case['has_existing_case'] ? '是' : '否' ?></td>
                        <td style="max-width:150px;word-break:break-all;" title="<?= h($case['case_name']) ?>">
                            <?= h(mb_substr($case['case_name'], 0, 20)) ?><?= mb_strlen($case['case_name']) > 20 ? '...' : '' ?>
                        </td>
                        <td><?= h($case['application_country']) ?></td>
                        <td><?= h($case['dept_name']) ?></td>
                        <td><?= $case['official_fee'] ? number_format($case['official_fee'], 2) : '' ?></td>
                        <td><?= $case['agency_fee'] ? number_format($case['agency_fee'], 2) : '' ?></td>
                        <td><?= $case['cost'] ? number_format($case['cost'], 2) : '' ?></td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-mini btn-edit-case">✎</button>
                            <button type="button" class="btn-mini btn-del-case" style="color:#f44336;">✖</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- 案件信息编辑模态框 -->
<div id="case-modal" class="module-modal">
    <div class="module-modal-content" style="width:1200px;">
        <div class="module-modal-header">
            <h3 class="module-modal-title">添加案件信息</h3>
            <button class="module-modal-close">&times;</button>
        </div>
        <div class="module-modal-body" style="padding:20px;">
            <form id="case-form">
                <input type="hidden" name="id" value="0">

                <table class="module-table" style="width:100%;table-layout:fixed;">
                    <colgroup>
                        <col style="width:120px;">
                        <col style="width:200px;">
                        <col style="width:120px;">
                        <col style="width:200px;">
                        <col style="width:120px;">
                        <col style="width:200px;">
                    </colgroup>

                    <!-- 第一行 -->
                    <tr>
                        <td class="module-label module-req">*案件类型</td>
                        <!-- 默认请选择 -->
                        <td><?= render_select('case_type', $case_types, '') ?></td>
                        <td class="module-label">是否已有案件</td>
                        <td>
                            <label><input type="radio" name="has_existing_case" value="1"> 是</label>
                            <label style="margin-left:20px;"><input type="radio" name="has_existing_case" value="0" checked> 否</label>
                        </td>
                        <td class="module-label">选择已有案件</td>
                        <td>
                            <?php render_select_search('existing_case_search', $case_options, '', '--请选择已有案件--'); ?>
                        </td>
                    </tr>

                    <!-- 第二行 -->
                    <tr>
                        <td class="module-label">是否开案</td>
                        <td><?= render_select('is_case_opened', $yes_no_options, '') ?></td>
                        <td class="module-label">申请国家</td>
                        <td><input type="text" name="application_country" class="module-input" value="中国"></td>
                        <td class="module-label">案件名称</td>
                        <td><input type="text" name="case_name" class="module-input"></td>
                    </tr>

                    <!-- 第三行 -->
                    <tr>
                        <td class="module-label">承办部门</td>
                        <td><?php render_select_search('business_dept_id', $dept_options, ''); ?></td>
                        <td class="module-label">官费</td>
                        <td><input type="number" name="official_fee" class="module-input" step="0.01"></td>
                        <td class="module-label">签单金额</td>
                        <td><input type="number" name="contract_amount" class="module-input" step="0.01"></td>
                    </tr>

                    <!-- 第四行 -->
                    <tr>
                        <td class="module-label">成本</td>
                        <td><input type="number" name="cost" class="module-input" step="0.01"></td>
                        <td class="module-label">是否开票</td>
                        <td><?= render_select('is_invoiced', $yes_no_options, '') ?></td>
                        <td class="module-label">业务类型</td>
                        <td><input type="text" name="business_type" class="module-input"></td>
                    </tr>

                    <!-- 第五行 -->
                    <tr>
                        <td class="module-label">申请类型</td>
                        <td><input type="text" name="application_type" class="module-input"></td>
                        <td class="module-label">外理人</td>
                        <td><input type="text" name="external_agent" class="module-input"></td>
                        <td class="module-label">代理费</td>
                        <td><input type="number" name="agency_fee" class="module-input" step="0.01"></td>
                    </tr>

                    <!-- 第六行 -->
                    <tr>
                        <td class="module-label">成本类型</td>
                        <td><?= render_select('cost_type', $cost_types, '') ?></td>
                        <td class="module-label">页数额</td>
                        <td><input type="number" name="page_count" class="module-input"></td>
                        <td class="module-label">开票金额</td>
                        <td><input type="number" name="invoice_amount" class="module-input" step="0.01"></td>
                    </tr>

                    <!-- 备注行 -->
                    <tr>
                        <td class="module-label">备注</td>
                        <td colspan="5"><textarea name="case_remarks" class="module-textarea" rows="3"></textarea></td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="module-modal-footer">
            <button type="button" class="btn-theme btn-save-case">保存</button>
            <button type="button" class="btn-cancel btn-cancel-case">取消</button>
        </div>
    </div>
</div>

<script>
    window.initCaseTabEvents = function() {
        var contractId = <?= $contract_id ?>;
        var caseModal = document.getElementById('case-modal');
        var caseForm = document.getElementById('case-form');
        var caseTitle = document.querySelector('#case-modal .module-modal-title');
        var btnAddCase = document.querySelector('.btn-add-case');
        var btnSaveCase = document.querySelector('.btn-save-case');
        var btnCancelCase = document.querySelector('.btn-cancel-case');
        var btnCloseCase = document.querySelector('#case-modal .module-modal-close');

        // 案件选项数据
        var allCaseOptions = <?= json_encode($case_options) ?>;

        // 案件数据缓存
        var caseDataCache = {};

        // 部门数据
        var deptOptions = <?= json_encode($dept_options) ?>;

        // 重新加载案件列表
        function reloadCaseList() {
            if (window.parent && window.parent.document) {
                var currentTab = window.parent.document.querySelector('.tab-btn.active');
                if (currentTab) {
                    currentTab.click();
                }
            }
        }

        // 切换已有案件字段显示状态
        function toggleExistingCaseFields() {
            var hasExisting = caseForm.querySelector('[name="has_existing_case"]:checked').value === '1';
            var existingCaseContainer = caseForm.querySelector('[name="existing_case_search"]').closest('.module-select-search-box');

            if (hasExisting) {
                // 显示搜索下拉框
                if (existingCaseContainer) {
                    existingCaseContainer.style.display = '';
                }
            } else {
                // 隐藏搜索下拉框并清空相关字段
                if (existingCaseContainer) {
                    existingCaseContainer.style.display = 'none';
                }
                // 清空搜索字段
                var existingCaseSearch = caseForm.querySelector('[name="existing_case_search"]');
                var existingCaseSearchDisplay = caseForm.querySelector('[name="existing_case_search_display"]');
                if (existingCaseSearch) existingCaseSearch.value = '';
                if (existingCaseSearchDisplay) existingCaseSearchDisplay.value = '';
            }
        }

        // 显示案件编辑模态框
        function showCaseModal(id) {
            var startTime = performance.now(); // 开始计时

            caseForm.reset();
            caseForm.id.value = id || 0;

            // 重置已有案件相关字段
            caseForm.querySelector('[name="has_existing_case"][value="0"]').checked = true;
            toggleExistingCaseFields();

            if (!id) {
                // 添加模式：直接显示弹窗
                caseTitle.textContent = '添加案件信息';
                caseModal.style.display = 'flex';
                console.log('添加弹窗打开耗时:', (performance.now() - startTime).toFixed(2) + 'ms');
                return;
            }

            // 编辑模式：先显示弹窗，再加载数据
            caseTitle.textContent = '编辑案件信息';
            caseModal.style.display = 'flex';

            // 检查缓存
            if (caseDataCache[id]) {
                // 使用缓存数据
                loadCaseData(caseDataCache[id]);
                console.log('编辑弹窗打开耗时(缓存):', (performance.now() - startTime).toFixed(2) + 'ms');
                return;
            }

            // 显示加载状态
            showLoadingState();

            // 获取案件详情
            var fd = new FormData();
            fd.append('action', 'get_case');
            fd.append('id', id);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/case.php?contract_id=' + contractId, true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        var data = res.data;

                        // 缓存数据
                        caseDataCache[id] = data;

                        // 加载数据
                        loadCaseData(data);
                        console.log('编辑弹窗打开耗时(AJAX):', (performance.now() - startTime).toFixed(2) + 'ms');
                    } else {
                        alert(res.msg || '获取案件信息失败');
                        hideCaseModal();
                    }
                } catch (e) {
                    alert('获取案件信息失败：' + xhr.responseText);
                    hideCaseModal();
                } finally {
                    // 隐藏加载状态
                    hideLoadingState();
                }
            };
            xhr.onerror = function() {
                alert('网络请求失败');
                hideCaseModal();
                hideLoadingState();
            };
            xhr.send(fd);
        }

        // 加载案件数据到表单
        function loadCaseData(data) {
            // 快速填充基本字段
            fillBasicFields(data);

            // 处理特殊字段
            handleSpecialFields(data);

            // 设置已有案件信息
            setExistingCaseInfo(data);

            toggleExistingCaseFields();
        }

        // 显示加载状态
        function showLoadingState() {
            // 禁用表单
            var formElements = caseForm.querySelectorAll('input, select, textarea, button');
            formElements.forEach(function(element) {
                element.disabled = true;
            });

            // 显示加载提示
            var loadingDiv = document.createElement('div');
            loadingDiv.id = 'case-loading';
            loadingDiv.style.cssText = 'position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.8);display:flex;align-items:center;justify-content:center;z-index:1000;';
            loadingDiv.innerHTML = '<div style="text-align:center;"><div style="width:40px;height:40px;border:3px solid #f3f3f3;border-top:3px solid #29b6b0;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 10px;"></div><div style="color:#666;font-size:14px;">正在加载案件信息...</div></div>';

            // 添加CSS动画
            if (!document.getElementById('loading-style')) {
                var style = document.createElement('style');
                style.id = 'loading-style';
                style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
                document.head.appendChild(style);
            }

            caseModal.querySelector('.module-modal-content').style.position = 'relative';
            caseModal.querySelector('.module-modal-content').appendChild(loadingDiv);
        }

        // 隐藏加载状态
        function hideLoadingState() {
            // 启用表单
            var formElements = caseForm.querySelectorAll('input, select, textarea, button');
            formElements.forEach(function(element) {
                element.disabled = false;
            });

            // 移除加载提示
            var loadingDiv = document.getElementById('case-loading');
            if (loadingDiv) {
                loadingDiv.remove();
            }
        }

        // 快速填充基本字段
        function fillBasicFields(data) {
            var basicFields = ['case_type', 'is_case_opened', 'application_country', 'case_name',
                'official_fee', 'contract_amount', 'cost', 'is_invoiced', 'business_type',
                'application_type', 'external_agent', 'agency_fee', 'cost_type',
                'page_count', 'invoice_amount', 'case_remarks'
            ];

            basicFields.forEach(function(key) {
                if (data[key] !== null && data[key] !== undefined) {
                    var element = caseForm.querySelector('[name="' + key + '"]');
                    if (element) {
                        if (element.type === 'checkbox') {
                            element.checked = data[key] == 1;
                        } else {
                            element.value = data[key];
                        }
                    }
                }
            });
        }

        // 处理特殊字段
        function handleSpecialFields(data) {
            // 处理单选框
            if (data.has_existing_case !== null) {
                var radioValue = data.has_existing_case == 1 ? '1' : '0';
                var radioElement = caseForm.querySelector('[name="has_existing_case"][value="' + radioValue + '"]');
                if (radioElement) {
                    radioElement.checked = true;
                }
            }

            // 处理承办部门搜索下拉框
            if (data.business_dept_id) {
                var deptId = data.business_dept_id;
                var deptName = deptOptions[deptId] || '';

                var deptHiddenField = caseForm.querySelector('[name="business_dept_id"]');
                var deptDisplayField = caseForm.querySelector('[name="business_dept_id_display"]');
                if (deptHiddenField) {
                    deptHiddenField.value = deptId;
                }
                if (deptDisplayField) {
                    deptDisplayField.value = deptName;
                }
            }
        }

        // 设置已有案件信息
        function setExistingCaseInfo(data) {
            if (data.existing_case_id && data.existing_case_code && data.existing_case_name) {
                var displayText = data.existing_case_code + ' - ' + data.existing_case_name;
                if (data.case_type) {
                    displayText += ' (' + data.case_type + ')';
                }

                var existingCaseSearchDisplay = caseForm.querySelector('[name="existing_case_search_display"]');
                var existingCaseSearchHidden = caseForm.querySelector('[name="existing_case_search"]');
                if (existingCaseSearchDisplay) {
                    existingCaseSearchDisplay.value = displayText;
                }
                if (existingCaseSearchHidden) {
                    existingCaseSearchHidden.value = data.existing_case_id + '|' + data.case_type;
                }
            }
        }

        // 隐藏案件模态框
        function hideCaseModal() {
            caseModal.style.display = 'none';
        }

        // 保存案件信息
        function saveCase() {
            var required = ['case_type'];
            for (var i = 0; i < required.length; i++) {
                var el = caseForm.querySelector('[name="' + required[i] + '"]');
                if (!el || !el.value.trim()) {
                    alert('请填写所有必填项');
                    el && el.focus();
                    return;
                }
            }

            var fd = new FormData(caseForm);
            fd.append('action', 'save_case');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/case.php?contract_id=' + contractId, true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        // 清除缓存
                        var caseId = caseForm.id.value;
                        if (caseId && caseDataCache[caseId]) {
                            delete caseDataCache[caseId];
                        }

                        hideCaseModal();
                        reloadCaseList();
                    } else {
                        alert(res.msg || '保存失败');
                    }
                } catch (e) {
                    alert('保存失败：' + xhr.responseText);
                }
            };
            xhr.send(fd);
        }

        // 删除案件信息
        function deleteCase(id) {
            var fd = new FormData();
            fd.append('action', 'delete_case');
            fd.append('id', id);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/case.php?contract_id=' + contractId, true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        reloadCaseList();
                    } else {
                        alert(res.msg || '删除失败');
                    }
                } catch (e) {
                    alert('删除失败：' + xhr.responseText);
                }
            };
            xhr.send(fd);
        }

        // 绑定事件
        if (btnAddCase) {
            btnAddCase.onclick = function() {
                showCaseModal();
            };
        }

        if (btnSaveCase) {
            btnSaveCase.onclick = saveCase;
        }

        if (btnCancelCase) {
            btnCancelCase.onclick = hideCaseModal;
        }

        if (btnCloseCase) {
            btnCloseCase.onclick = hideCaseModal;
        }

        // 绑定案件类型变化事件
        caseForm.querySelector('[name="case_type"]').onchange = function() {
            toggleExistingCaseFields();
        };

        // 绑定已有案件单选框变化事件
        caseForm.querySelectorAll('[name="has_existing_case"]').forEach(function(radio) {
            radio.onchange = toggleExistingCaseFields;
        });

        // 监听已有案件选择变化，自动填充案件信息
        function handleExistingCaseChange() {
            var existingCaseSearch = caseForm.querySelector('[name="existing_case_search"]');
            if (!existingCaseSearch) return;

            var selectedValue = existingCaseSearch.value;
            if (!selectedValue) return;

            // 解析选中的案件ID和类型
            var parts = selectedValue.split('|');
            if (parts.length < 2) return;

            var caseId = parts[0];
            var caseType = parts[1];

            // 自动设置案件类型
            var caseTypeSelect = caseForm.querySelector('[name="case_type"]');
            if (caseTypeSelect && caseType) {
                caseTypeSelect.value = caseType;
            }

            // 获取案件详细信息并自动填充
            var fd = new FormData();
            fd.append('action', 'get_existing_case_details');
            fd.append('case_id', caseId);
            fd.append('case_type', caseType);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/case.php?contract_id=' + contractId, true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.data) {
                        var data = res.data;

                        // 自动填充案件名称
                        if (data.case_name) {
                            var caseNameField = caseForm.querySelector('[name="case_name"]');
                            if (caseNameField) {
                                caseNameField.value = data.case_name;
                            }
                        }

                        // 自动填充申请国家
                        if (data.country) {
                            var countryField = caseForm.querySelector('[name="application_country"]');
                            if (countryField) {
                                countryField.value = data.country;
                            }
                        }

                        // 自动填充申请类型
                        if (data.application_type) {
                            var applicationTypeField = caseForm.querySelector('[name="application_type"]');
                            if (applicationTypeField) {
                                applicationTypeField.value = data.application_type;
                            }
                        }
                    }
                } catch (e) {
                    console.log('获取案件详情失败：', e);
                }
            };
            xhr.send(fd);
        }

        // 使用MutationObserver监听搜索下拉框的值变化
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                    var target = mutation.target;
                    if (target.name === 'existing_case_search') {
                        handleExistingCaseChange();
                    }
                }
            });
        });

        // 开始监听搜索下拉框
        var existingCaseSearch = caseForm.querySelector('[name="existing_case_search"]');
        if (existingCaseSearch) {
            observer.observe(existingCaseSearch, {
                attributes: true
            });

            // 也监听input事件
            existingCaseSearch.addEventListener('input', handleExistingCaseChange);

            // 监听搜索下拉框列表项的点击事件
            var existingCaseContainer = existingCaseSearch.closest('.module-select-search-box');
            if (existingCaseContainer) {
                // 使用事件委托监听列表项点击
                existingCaseContainer.addEventListener('click', function(e) {
                    if (e.target.classList.contains('module-select-search-item')) {
                        // 延迟执行，确保值已经被设置
                        setTimeout(handleExistingCaseChange, 100);
                    }
                });
            }

            // 定期检查值变化（备用方案）
            var lastValue = '';
            setInterval(function() {
                var currentValue = existingCaseSearch.value;
                if (currentValue !== lastValue) {
                    lastValue = currentValue;
                    if (currentValue) {
                        handleExistingCaseChange();
                    }
                }
            }, 500);
        }

        // 绑定案件列表的编辑和删除按钮
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-edit-case')) {
                var tr = e.target.closest('tr');
                var id = tr.getAttribute('data-id');
                showCaseModal(id);
            } else if (e.target.classList.contains('btn-del-case')) {
                var tr = e.target.closest('tr');
                var id = tr.getAttribute('data-id');
                deleteCase(id);
            }
        });
    };

    // 自动初始化
    if (typeof window.initCaseTabEvents === 'function') {
        window.initCaseTabEvents();
    }
</script>