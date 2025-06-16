<?php
// 合同编辑-扩展信息
include_once(__DIR__ . '/../../../../database.php');
include_once(__DIR__ . '/../../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['ajax']) || isset($_POST['ajax']) || (isset($_POST['action']) && $_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => '未登录或会话超时']);
        exit;
    } else {
        header('Location: /login.php');
        exit;
    }
}

if (!isset($_GET['contract_id']) || intval($_GET['contract_id']) <= 0) {
    echo '<div class="module-error">未指定合同ID</div>';
    exit;
}
$contract_id = intval($_GET['contract_id']);

// 验证合同是否存在
$contract_stmt = $pdo->prepare("SELECT * FROM contract WHERE id = ?");
$contract_stmt->execute([$contract_id]);
$contract_info = $contract_stmt->fetch();
if (!$contract_info) {
    echo '<div class="module-error">未找到该合同信息</div>';
    exit;
}

// 查询扩展信息
$extend_stmt = $pdo->prepare("SELECT * FROM contract_extend_info WHERE contract_id = ?");
$extend_stmt->execute([$contract_id]);
$extend_info = $extend_stmt->fetch();

// 如果没有扩展信息记录，创建一个空的数组
if (!$extend_info) {
    $extend_info = [
        'contract_id' => $contract_id,
        'department_branch' => '',
        'importance_level' => '',
        'party_a_email' => '',
        'total_official_fee' => '',
        'first_official_fee' => '',
        'first_agency_fee' => '',
        'first_total_amount' => '',
        'long_term_payment_method' => '',
        'advance_payment' => '',
        'long_term_payment_note' => '',
        'invoice_method' => '',
        'invoice_title' => '',
        'invention_count' => '',
        'invention_agency_fee' => '',
        'other_count' => '',
        'other_agency_fee' => '',
        'other_note' => '',
        'contract_summary' => '',
        'party_b_email' => '',
        'total_agency_fee' => '',
        'middle_official_fee' => '',
        'middle_agency_fee' => '',
        'middle_total_amount' => '',
        'application_fee_payment_method' => '',
        'is_deferred_examination_fee' => 0,
        'agency_fee_settlement_method' => '',
        'dual_report_count' => '',
        'dual_report_total_agency_fee' => '',
        'utility_model_count' => '',
        'utility_model_agency_fee' => '',
        'application_region' => '',
        'application_deadline' => '',
        'application_requirements' => '',
        'payment_account' => '',
        'service_fee_standard' => '',
        'final_official_fee' => '',
        'final_agency_fee' => '',
        'final_total_amount' => '',
        'authorization_fee_payment_method' => '',
        'first_three_years_fee_payment_method' => '',
        'dual_report_invention_agency_fee' => '',
        'dual_report_utility_model_agency_fee' => '',
        'design_count' => '',
        'design_agency_fee' => '',
        'annual_fee_supervision_requirements' => ''
    ];
}

// 查询所有部门用于下拉
$dept_stmt = $pdo->prepare("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY dept_name ASC");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll();

// 格式化部门数据为下拉框所需格式
$dept_options = [];
foreach ($departments as $dept) {
    $dept_options[$dept['id']] = $dept['dept_name'];
}

// 静态下拉选项
$importance_levels = ['一般', '重要', '非常重要', '紧急'];

// 根据图片1：代理费结算方式
$agency_fee_settlement_methods = [
    '按合同正常结算',
    '资助后结算',
    '大客户结算',
    '不清款',
    '其他后结算',
    '在期结算',
    '实用授权后支付'
];

// 根据图片2：授权费缴费方式
$authorization_fee_payment_methods = [
    '条款后收款',
    '条文后正常结算',
    '条文后资助结算',
    '客户自己收款',
    '本公司承担',
    '代理人承担',
    '不收款',
    '定期结算'
];

// 根据图片3：申请费缴费方式
$application_fee_payment_methods = [
    '条款后收款',
    '条文后正常结算',
    '条文后资助结算',
    '客户自己收款',
    '本公司承担',
    '代理人承担',
    '不收款',
    '定期结算'
];

// 根据图片4：前三年年费缴费方式
$first_three_years_fee_payment_methods = [
    '条款后收款',
    '条文后正常结算',
    '条文后资助结算',
    '客户自己收款',
    '本公司承担',
    '代理人承担',
    '不收款',
    '定期结算'
];

// 根据图片5：长期付款方式
$long_term_payment_methods = [
    '交底后一半，受理后付一半',
    '分期付款',
    '其他交费',
    '预付结算',
    '定期结算',
    '资助后付款',
    '受理后5个工作日付全款',
    '满足数量到付费，受理后付代理费',
    '满足数量到付费，资助后付代理费',
    '满足数量到3件付费，受理后付代理费及官费',
    '交底后一次性付款5个工作日付款'
];

// 开票方式
$invoice_methods = ['先来款后开票', '先开票后来款'];
$service_fee_standards = ['标准', '优惠', '特殊', '其他'];

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function render_select($name, $options, $val = '', $placeholder = '--请选择--')
{
    $html = "<select name=\"$name\" class=\"module-input\">";
    $html .= "<option value=\"\">$placeholder</option>";
    foreach ($options as $o) {
        $selected = ($val == $o) ? 'selected' : '';
        $html .= "<option value=\"" . h($o) . "\" $selected>" . h($o) . "</option>";
    }
    $html .= "</select>";
    return $html;
}



// 处理POST请求（保存数据）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json');

    $data = [
        'contract_id' => $contract_id,
        'department_branch' => trim($_POST['department_branch'] ?? ''),
        'importance_level' => trim($_POST['importance_level'] ?? ''),
        'party_a_email' => trim($_POST['party_a_email'] ?? ''),
        'total_official_fee' => floatval($_POST['total_official_fee'] ?? 0) ?: null,
        'first_official_fee' => floatval($_POST['first_official_fee'] ?? 0) ?: null,
        'first_agency_fee' => floatval($_POST['first_agency_fee'] ?? 0) ?: null,
        'first_total_amount' => floatval($_POST['first_total_amount'] ?? 0) ?: null,
        'long_term_payment_method' => trim($_POST['long_term_payment_method'] ?? ''),
        'advance_payment' => floatval($_POST['advance_payment'] ?? 0) ?: null,
        'long_term_payment_note' => trim($_POST['long_term_payment_note'] ?? ''),
        'invoice_method' => trim($_POST['invoice_method'] ?? ''),
        'invoice_title' => trim($_POST['invoice_title'] ?? ''),
        'invention_count' => intval($_POST['invention_count'] ?? 0) ?: null,
        'invention_agency_fee' => floatval($_POST['invention_agency_fee'] ?? 0) ?: null,
        'other_count' => intval($_POST['other_count'] ?? 0) ?: null,
        'other_agency_fee' => floatval($_POST['other_agency_fee'] ?? 0) ?: null,
        'other_note' => trim($_POST['other_note'] ?? ''),
        'contract_summary' => trim($_POST['contract_summary'] ?? ''),
        'party_b_email' => trim($_POST['party_b_email'] ?? ''),
        'total_agency_fee' => floatval($_POST['total_agency_fee'] ?? 0) ?: null,
        'middle_official_fee' => floatval($_POST['middle_official_fee'] ?? 0) ?: null,
        'middle_agency_fee' => floatval($_POST['middle_agency_fee'] ?? 0) ?: null,
        'middle_total_amount' => floatval($_POST['middle_total_amount'] ?? 0) ?: null,
        'application_fee_payment_method' => trim($_POST['application_fee_payment_method'] ?? ''),
        'is_deferred_examination_fee' => intval($_POST['is_deferred_examination_fee'] ?? 0),
        'agency_fee_settlement_method' => trim($_POST['agency_fee_settlement_method'] ?? ''),
        'dual_report_count' => intval($_POST['dual_report_count'] ?? 0) ?: null,
        'dual_report_total_agency_fee' => floatval($_POST['dual_report_total_agency_fee'] ?? 0) ?: null,
        'utility_model_count' => intval($_POST['utility_model_count'] ?? 0) ?: null,
        'utility_model_agency_fee' => floatval($_POST['utility_model_agency_fee'] ?? 0) ?: null,
        'application_region' => trim($_POST['application_region'] ?? ''),
        'application_deadline' => trim($_POST['application_deadline'] ?? ''),
        'application_requirements' => trim($_POST['application_requirements'] ?? ''),
        'payment_account' => trim($_POST['payment_account'] ?? ''),
        'service_fee_standard' => trim($_POST['service_fee_standard'] ?? ''),
        'final_official_fee' => floatval($_POST['final_official_fee'] ?? 0) ?: null,
        'final_agency_fee' => floatval($_POST['final_agency_fee'] ?? 0) ?: null,
        'final_total_amount' => floatval($_POST['final_total_amount'] ?? 0) ?: null,
        'authorization_fee_payment_method' => trim($_POST['authorization_fee_payment_method'] ?? ''),
        'first_three_years_fee_payment_method' => trim($_POST['first_three_years_fee_payment_method'] ?? ''),
        'dual_report_invention_agency_fee' => floatval($_POST['dual_report_invention_agency_fee'] ?? 0) ?: null,
        'dual_report_utility_model_agency_fee' => floatval($_POST['dual_report_utility_model_agency_fee'] ?? 0) ?: null,
        'design_count' => intval($_POST['design_count'] ?? 0) ?: null,
        'design_agency_fee' => floatval($_POST['design_agency_fee'] ?? 0) ?: null,
        'annual_fee_supervision_requirements' => trim($_POST['annual_fee_supervision_requirements'] ?? '')
    ];

    try {
        // 检查是否已存在扩展信息记录
        $check_stmt = $pdo->prepare("SELECT id FROM contract_extend_info WHERE contract_id = ?");
        $check_stmt->execute([$contract_id]);
        $existing = $check_stmt->fetch();

        if ($existing) {
            // 更新现有记录
            $set = [];
            foreach ($data as $k => $v) {
                if ($k !== 'contract_id') {
                    $set[] = "$k = :$k";
                }
            }
            $sql = "UPDATE contract_extend_info SET " . implode(',', $set) . " WHERE contract_id = :contract_id";
        } else {
            // 插入新记录
            $fields = array_keys($data);
            $placeholders = array_map(function ($field) {
                return ":$field";
            }, $fields);
            $sql = "INSERT INTO contract_extend_info (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        }

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($data);
        echo json_encode(['success' => $result, 'msg' => $result ? null : '保存失败']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    }
    exit;
}

// 输出下拉搜索所需的JS和CSS
render_select_search_assets();
?>

<div class="module-btns">
    <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
    <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
</div>

<form id="edit-contract-extend-form" class="module-form editing" autocomplete="off">
    <table class="module-table" style="width:100%;max-width:1800px;table-layout:fixed;">
        <colgroup>
            <col style="width:120px;">
            <col style="width:220px;">
            <col style="width:120px;">
            <col style="width:220px;">
            <col style="width:120px;">
            <col style="width:220px;">
        </colgroup>

        <!-- 第一行 -->
        <tr>
            <td class="module-label">所属分部</td>
            <td>
                <?php render_select_search('department_branch', $dept_options, $extend_info['department_branch'] ?? ''); ?>
            </td>
            <td class="module-label">合同摘要</td>
            <td><textarea name="contract_summary" class="module-textarea" rows="2"><?= h($extend_info['contract_summary']) ?></textarea></td>
            <td class="module-label">收款账户</td>
            <td><input type="text" name="payment_account" class="module-input" value="<?= h($extend_info['payment_account']) ?>"></td>
        </tr>

        <!-- 第二行 -->
        <tr>
            <td class="module-label">重要程度</td>
            <td><?= render_select('importance_level', $importance_levels, $extend_info['importance_level']) ?></td>
            <td class="module-label">乙方合同邮箱</td>
            <td><input type="email" name="party_b_email" class="module-input" value="<?= h($extend_info['party_b_email']) ?>"></td>
            <td class="module-label">服务费标准</td>
            <td><?= render_select('service_fee_standard', $service_fee_standards, $extend_info['service_fee_standard']) ?></td>
        </tr>

        <!-- 第三行 -->
        <tr>
            <td class="module-label">甲方合同邮箱</td>
            <td><input type="email" name="party_a_email" class="module-input" value="<?= h($extend_info['party_a_email']) ?>"></td>
            <td class="module-label">合同代理费总额</td>
            <td><input type="number" name="total_agency_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['total_agency_fee']) ?>"></td>
            <td class="module-label">尾款官费</td>
            <td><input type="number" name="final_official_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['final_official_fee']) ?>"></td>
        </tr>

        <!-- 第四行 -->
        <tr>
            <td class="module-label">合同官费总额</td>
            <td><input type="number" name="total_official_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['total_official_fee']) ?>"></td>
            <td class="module-label">中间款官费</td>
            <td><input type="number" name="middle_official_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['middle_official_fee']) ?>"></td>
            <td class="module-label">尾款代理费</td>
            <td><input type="number" name="final_agency_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['final_agency_fee']) ?>"></td>
        </tr>

        <!-- 第五行 -->
        <tr>
            <td class="module-label">首付官费</td>
            <td><input type="number" name="first_official_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['first_official_fee']) ?>"></td>
            <td class="module-label">中间款代理费</td>
            <td><input type="number" name="middle_agency_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['middle_agency_fee']) ?>"></td>
            <td class="module-label">尾款总额</td>
            <td><input type="number" name="final_total_amount" class="module-input" step="0.01" min="0" value="<?= h($extend_info['final_total_amount']) ?>"></td>
        </tr>

        <!-- 第六行 -->
        <tr>
            <td class="module-label">首付代理费</td>
            <td><input type="number" name="first_agency_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['first_agency_fee']) ?>"></td>
            <td class="module-label">中间款总额</td>
            <td><input type="number" name="middle_total_amount" class="module-input" step="0.01" min="0" value="<?= h($extend_info['middle_total_amount']) ?>"></td>
            <td class="module-label">授权费缴费方式</td>
            <td><?= render_select('authorization_fee_payment_method', $authorization_fee_payment_methods, $extend_info['authorization_fee_payment_method']) ?></td>
        </tr>

        <!-- 第七行 -->
        <tr>
            <td class="module-label">首付总额</td>
            <td><input type="number" name="first_total_amount" class="module-input" step="0.01" min="0" value="<?= h($extend_info['first_total_amount']) ?>"></td>
            <td class="module-label">申请费缴费方式</td>
            <td><?= render_select('application_fee_payment_method', $application_fee_payment_methods, $extend_info['application_fee_payment_method']) ?></td>
            <td class="module-label">前三年年费缴费方式</td>
            <td><?= render_select('first_three_years_fee_payment_method', $first_three_years_fee_payment_methods, $extend_info['first_three_years_fee_payment_method']) ?></td>
        </tr>

        <!-- 第八行 -->
        <tr>
            <td class="module-label">长期付款方式</td>
            <td><?= render_select('long_term_payment_method', $long_term_payment_methods, $extend_info['long_term_payment_method']) ?></td>
            <td class="module-label">是否缓交实审费</td>
            <td>
                <select name="is_deferred_examination_fee" class="module-input">
                    <option value="0" <?= $extend_info['is_deferred_examination_fee'] == 0 ? 'selected' : '' ?>>否</option>
                    <option value="1" <?= $extend_info['is_deferred_examination_fee'] == 1 ? 'selected' : '' ?>>是</option>
                </select>
            </td>
            <td class="module-label">双报发明代理费</td>
            <td><input type="number" name="dual_report_invention_agency_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['dual_report_invention_agency_fee']) ?>"></td>
        </tr>

        <!-- 第九行 -->
        <tr>
            <td class="module-label">预付款</td>
            <td><input type="number" name="advance_payment" class="module-input" step="0.01" min="0" value="<?= h($extend_info['advance_payment']) ?>"></td>
            <td class="module-label">代理费结算方式</td>
            <td><?= render_select('agency_fee_settlement_method', $agency_fee_settlement_methods, $extend_info['agency_fee_settlement_method']) ?></td>
            <td class="module-label">双报新型代理费</td>
            <td><input type="number" name="dual_report_utility_model_agency_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['dual_report_utility_model_agency_fee']) ?>"></td>
        </tr>

        <!-- 第十行 -->
        <tr>
            <td class="module-label">长期付款说明</td>
            <td><textarea name="long_term_payment_note" class="module-textarea" rows="2"><?= h($extend_info['long_term_payment_note']) ?></textarea></td>
            <td class="module-label">双报件数</td>
            <td><input type="number" name="dual_report_count" class="module-input" min="0" value="<?= h($extend_info['dual_report_count']) ?>"></td>
            <td class="module-label">外观件数</td>
            <td><input type="number" name="design_count" class="module-input" min="0" value="<?= h($extend_info['design_count']) ?>"></td>
        </tr>

        <!-- 第十一行 -->
        <tr>
            <td class="module-label">开票方式</td>
            <td><?= render_select('invoice_method', $invoice_methods, $extend_info['invoice_method']) ?></td>
            <td class="module-label">双报总代理费</td>
            <td><input type="number" name="dual_report_total_agency_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['dual_report_total_agency_fee']) ?>"></td>
            <td class="module-label">外观代理费</td>
            <td><input type="number" name="design_agency_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['design_agency_fee']) ?>"></td>
        </tr>

        <!-- 第十二行 -->
        <tr>
            <td class="module-label">发票抬头</td>
            <td><input type="text" name="invoice_title" class="module-input" value="<?= h($extend_info['invoice_title']) ?>"></td>
            <td class="module-label">新型件数</td>
            <td><input type="number" name="utility_model_count" class="module-input" min="0" value="<?= h($extend_info['utility_model_count']) ?>"></td>
            <td class="module-label">年费监管要求</td>
            <td><textarea name="annual_fee_supervision_requirements" class="module-textarea" rows="2"><?= h($extend_info['annual_fee_supervision_requirements']) ?></textarea></td>
        </tr>

        <!-- 第十三行 -->
        <tr>
            <td class="module-label">发明件数</td>
            <td><input type="number" name="invention_count" class="module-input" min="0" value="<?= h($extend_info['invention_count']) ?>"></td>
            <td class="module-label">新型代理费</td>
            <td><input type="number" name="utility_model_agency_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['utility_model_agency_fee']) ?>"></td>
            <td class="module-label" style="border:none;"></td>
            <td style="border:none;"></td>
        </tr>

        <!-- 第十四行 -->
        <tr>
            <td class="module-label">发明代理费</td>
            <td><input type="number" name="invention_agency_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['invention_agency_fee']) ?>"></td>
            <td class="module-label">申报区域</td>
            <td><input type="text" name="application_region" class="module-input" value="<?= h($extend_info['application_region']) ?>"></td>
            <td class="module-label" style="border:none;"></td>
            <td style="border:none;"></td>
        </tr>

        <!-- 第十五行 -->
        <tr>
            <td class="module-label">其他件数</td>
            <td><input type="number" name="other_count" class="module-input" min="0" value="<?= h($extend_info['other_count']) ?>"></td>
            <td class="module-label">申报期限</td>
            <td><input type="text" name="application_deadline" class="module-input" value="<?= h($extend_info['application_deadline']) ?>"></td>
            <td class="module-label" style="border:none;"></td>
            <td style="border:none;"></td>
        </tr>

        <!-- 第十六行 -->
        <tr>
            <td class="module-label">其他代理费</td>
            <td><input type="number" name="other_agency_fee" class="module-input" step="0.01" min="0" value="<?= h($extend_info['other_agency_fee']) ?>"></td>
            <td class="module-label">申报要求</td>
            <td><textarea name="application_requirements" class="module-textarea" rows="2"><?= h($extend_info['application_requirements']) ?></textarea></td>
            <td class="module-label" style="border:none;"></td>
            <td style="border:none;"></td>
        </tr>

        <!-- 第十七行 -->
        <tr>
            <td class="module-label">其他说明</td>
            <td colspan="5"><textarea name="other_note" class="module-textarea" rows="2"><?= h($extend_info['other_note']) ?></textarea></td>
        </tr>
    </table>
</form>

<script>
    window.initContractExtendTabEvents = function() {
        var form = document.getElementById('edit-contract-extend-form'),
            btnSave = document.querySelector('#contract-tab-content .btn-save'),
            btnCancel = document.querySelector('#contract-tab-content .btn-cancel');

        // 保存按钮AJAX提交
        if (btnSave) {
            btnSave.onclick = function() {
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/extend.php?contract_id=<?= $contract_id ?>', true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                alert('保存成功');
                            } else {
                                alert(res.msg || '保存失败');
                            }
                        } catch (e) {
                            alert('保存失败：' + xhr.responseText);
                        }
                    }
                };
                xhr.send(fd);
            };
        }

        // 取消按钮
        if (btnCancel) {
            btnCancel.onclick = function() {
                if (confirm('确定要取消吗？未保存的内容将丢失。')) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'modules/customer_management/contract_management/edit_tabs/extend.php?contract_id=<?= $contract_id ?>', true);
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            var tabContent = document.querySelector('#contract-tab-content');
                            if (tabContent) {
                                // 创建临时容器
                                var tempDiv = document.createElement('div');
                                tempDiv.innerHTML = xhr.responseText;

                                // 将所有脚本提取出来
                                var scripts = [];
                                tempDiv.querySelectorAll('script').forEach(function(script) {
                                    scripts.push(script);
                                    script.parentNode.removeChild(script);
                                });

                                // 更新内容
                                tabContent.innerHTML = tempDiv.innerHTML;

                                // 执行脚本
                                scripts.forEach(function(script) {
                                    var newScript = document.createElement('script');
                                    if (script.src) {
                                        newScript.src = script.src;
                                    } else {
                                        newScript.textContent = script.textContent;
                                    }
                                    document.body.appendChild(newScript);
                                });

                                // 延迟初始化下拉框
                                setTimeout(function() {
                                    if (typeof window.initSelectSearchControls === 'function') {
                                        window.initSelectSearchControls();
                                    }

                                    // 初始化其他事件处理
                                    if (typeof window.initContractExtendTabEvents === 'function') {
                                        window.initContractExtendTabEvents();
                                    }
                                }, 200);
                            }
                        } else {
                            alert('重置表单失败，请刷新页面重试');
                        }
                    };
                    xhr.send();
                }
            };
        }
    };

    // 初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initContractExtendTabEvents);
    } else {
        window.initContractExtendTabEvents();
    }
</script>