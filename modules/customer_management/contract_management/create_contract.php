<?php
session_start();
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();

// 检查是否通过框架访问
if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'index.php') === false) {
    header('Location: /index.php');
    exit;
}

// 检查用户权限
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 静态下拉选项
// 合同类型
$contract_types = [
    '代理合同',
    '采购合同',
    '服务合同',
    '直销合同',
    '其他'
];

// 付款方式
$payment_methods = [
    '支票',
    '现金',
    '银行转账',
    '微信',
    '支付宝',
    '其他'
];

// 合同状态
$contract_statuses = [
    '未开始',
    '执行中',
    '成功结束',
    '意外终止'
];

// 货币类型
$currencies = [
    '人民币',
    '美元',
    '瑞士法郎',
    '欧元',
    '港元',
    '日元',
    '英镑',
    '荷兰盾',
    '加元',
    '新台币',
    '比索'
];

// 查询动态数据
$departments = $pdo->query("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();
$users = $pdo->query("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name")->fetchAll();
$customers = $pdo->query("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn")->fetchAll();

// 格式化数据为通用下拉框函数所需格式
$departments_options = [];
$users_options = [];
$customers_options = [];
$contract_types_options = [];
$payment_methods_options = [];
$contract_statuses_options = [];
$currencies_options = [];

foreach ($departments as $dept) {
    $departments_options[$dept['id']] = $dept['dept_name'];
}

foreach ($users as $user) {
    $users_options[$user['id']] = $user['real_name'];
}

foreach ($customers as $customer) {
    $customers_options[$customer['id']] = $customer['customer_name_cn'];
}

foreach ($contract_types as $type) {
    $contract_types_options[$type] = $type;
}

foreach ($payment_methods as $method) {
    $payment_methods_options[$method] = $method;
}

foreach ($contract_statuses as $status) {
    $contract_statuses_options[$status] = $status;
}

foreach ($currencies as $currency) {
    $currencies_options[$currency] = $currency;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json');

    // 自动生成唯一合同编号
    function generate_contract_no($pdo)
    {
        $prefix = 'HT' . date('Ymd');
        $sql = "SELECT COUNT(*) FROM contract WHERE contract_no LIKE :prefix";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);
        $count = $stmt->fetchColumn();
        $serial = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        return $prefix . $serial;
    }

    $data = [
        'contract_name' => trim($_POST['contract_name'] ?? ''),
        'customer_id' => intval($_POST['customer_id'] ?? 0),
        'opportunity_id' => intval($_POST['opportunity_id'] ?? 0),
        'contract_amount' => floatval($_POST['contract_amount'] ?? 0),
        'currency' => trim($_POST['currency'] ?? ''),
        'valid_start_date' => trim($_POST['valid_start_date'] ?? ''),
        'valid_end_date' => trim($_POST['valid_end_date'] ?? ''),
        'case_count' => intval($_POST['case_count'] ?? 0),
        'party_a_signer' => trim($_POST['party_a_signer'] ?? ''),
        'party_a_signer_mobile' => trim($_POST['party_a_signer_mobile'] ?? ''),
        'business_user_id' => intval($_POST['business_user_id'] ?? 0),
        'contract_type' => trim($_POST['contract_type'] ?? ''),
        'payment_method' => trim($_POST['payment_method'] ?? ''),
        'party_b_company' => trim($_POST['party_b_company'] ?? ''),
        'party_b_signer' => trim($_POST['party_b_signer'] ?? ''),
        'party_b_signer_mobile' => trim($_POST['party_b_signer_mobile'] ?? ''),
        'sign_date' => trim($_POST['sign_date'] ?? ''),
        'contract_receive_date' => trim($_POST['contract_receive_date'] ?? ''),
        'remarks' => trim($_POST['remarks'] ?? ''),
        'contract_status' => trim($_POST['contract_status'] ?? ''),
        'next_follow_date' => trim($_POST['next_follow_date'] ?? ''),
        'responsible_user_id' => intval($_POST['responsible_user_id'] ?? 0),
        'collaborator_user_ids' => trim($_POST['collaborator_user_ids'] ?? ''),
        'leader_user_id' => intval($_POST['leader_user_id'] ?? 0),
        'department_id' => intval($_POST['department_id'] ?? 0),
        'previous_responsible_user_id' => intval($_POST['previous_responsible_user_id'] ?? 0),
        'creator_user_id' => $_SESSION['user_id']
    ];

    // 修正：所有DATE类型字段为空字符串时转为null，避免MySQL日期类型报错
    $date_fields = [
        'valid_start_date',
        'valid_end_date',
        'sign_date',
        'contract_receive_date',
        'next_follow_date'
    ];
    foreach ($date_fields as $field) {
        if (isset($data[$field]) && $data[$field] === '') {
            $data[$field] = null;
        }
    }

    // 修正：所有外键字段为0或小于0时转为null，避免外键约束报错
    $fk_fields = [
        'customer_id',
        'opportunity_id',
        'business_user_id',
        'responsible_user_id',
        'leader_user_id',
        'department_id',
        'previous_responsible_user_id'
    ];
    foreach ($fk_fields as $field) {
        if (isset($data[$field]) && $data[$field] <= 0) {
            $data[$field] = null;
        }
    }

    // 验证必填字段
    if (
        $data['contract_name'] === '' || $data['customer_id'] <= 0 || $data['contract_amount'] <= 0 ||
        $data['case_count'] <= 0 || $data['business_user_id'] <= 0 || $data['contract_type'] === '' ||
        $data['payment_method'] === ''
    ) {
        echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
        exit;
    }

    try {
        // 新增模式 - 自动生成唯一合同编号并执行INSERT操作
        $data['contract_no'] = generate_contract_no($pdo);

        $fields = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO contract ($fields) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        foreach ($data as $k => $v) {
            $stmt->bindValue(":$k", $v);
        }
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok, 'contract_no' => $data['contract_no']]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常:' . $e->getMessage()]);
    }
    exit;
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

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>新增合同</title>
    <link rel="stylesheet" href="../../../css/module.css">
    <?php render_select_search_assets(); ?>
</head>

<body>
    <div class="module-panel">
        <div class="module-btns">
            <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
            <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
        </div>
        <h3 style="text-align:center;margin-bottom:15px;">新增合同</h3>
        <form id="add-contract-form" class="module-form" autocomplete="off">
            <table class="module-table" style="width:100%;max-width:1800px;table-layout:fixed;">
                <colgroup>
                    <col style="width:120px;">
                    <col style="width:220px;">
                    <col style="width:120px;">
                    <col style="width:220px;">
                    <col style="width:120px;">
                    <col style="width:220px;">
                </colgroup>

                <!-- 合同信息 -->
                <tr>
                    <td colspan="6" style="background:#f8f9fa;padding:8px;font-weight:bold;color:#29b6b0;">📋 合同信息</td>
                </tr>
                <tr>
                    <td class="module-label">合同编号</td>
                    <td><input type="text" name="contract_no" class="module-input" value="系统自动生成" readonly></td>
                    <td class="module-label module-req">*合同名称</td>
                    <td><input type="text" name="contract_name" class="module-input" value="" required></td>
                    <td class="module-label module-req">*对应客户</td>
                    <td>
                        <?php render_select_search('customer_id', $customers_options, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">对应的商机</td>
                    <td><input type="text" name="opportunity_id" class="module-input" placeholder="商机ID（可选）"></td>
                    <td class="module-label module-req">*合同总金额</td>
                    <td>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="number" name="contract_amount" class="module-input" step="1" min="0" required style="background-color:white;width:60%;">
                            <select name="currency" class="module-input" style="width:38%;">
                                <option value="人民币" selected>人民币</option>
                                <?php foreach ($currencies as $currency): ?>
                                    <?php if ($currency != '人民币'): ?>
                                        <option value="<?= h($currency) ?>"><?= h($currency) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </td>
                    <td class="module-label">合同有效时间</td>
                    <td class="module-date-range">
                        <input type="date" name="valid_start_date" class="module-input"> 至
                        <input type="date" name="valid_end_date" class="module-input">
                    </td>
                </tr>
                <tr>
                    <td class="module-label module-req">*案件数量</td>
                    <td><input type="number" name="case_count" class="module-input" min="1" required style="background-color:white;"></td>
                    <td class="module-label">甲方签约人</td>
                    <td><input type="text" name="party_a_signer" class="module-input"></td>
                    <td class="module-label">甲方签约人手机</td>
                    <td><input type="text" name="party_a_signer_mobile" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*业务人员</td>
                    <td>
                        <?php render_select_search('business_user_id', $users_options, ''); ?>
                    </td>
                    <td class="module-label module-req">*合同类型</td>
                    <td>
                        <?php echo render_select('contract_type', $contract_types, ''); ?>
                    </td>
                    <td class="module-label module-req">*付款方式</td>
                    <td>
                        <?php echo render_select('payment_method', $payment_methods, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">乙方签约公司</td>
                    <td><input type="text" name="party_b_company" class="module-input"></td>
                    <td class="module-label">乙方签约人</td>
                    <td><input type="text" name="party_b_signer" class="module-input"></td>
                    <td class="module-label">乙方签约人手机</td>
                    <td><input type="text" name="party_b_signer_mobile" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">签约日期</td>
                    <td><input type="date" name="sign_date" class="module-input"></td>
                    <td class="module-label">合同领用日期</td>
                    <td><input type="date" name="contract_receive_date" class="module-input"></td>
                    <td class="module-label">备注</td>
                    <td><textarea name="remarks" class="module-textarea" rows="2"></textarea></td>
                </tr>

                <!-- 跟进信息 -->
                <tr>
                    <td colspan="6" style="background:#f8f9fa;padding:8px;font-weight:bold;color:#29b6b0;">📞 跟进信息</td>
                </tr>
                <tr>
                    <td class="module-label">合同状态</td>
                    <td>
                        <?php echo render_select('contract_status', $contract_statuses, '未开始'); ?>
                    </td>
                    <td class="module-label">下次跟进时间</td>
                    <td><input type="date" name="next_follow_date" class="module-input"></td>
                    <td colspan="2"></td>
                </tr>

                <!-- 人员信息 -->
                <tr>
                    <td colspan="6" style="background:#f8f9fa;padding:8px;font-weight:bold;color:#29b6b0;">👥 人员信息</td>
                </tr>
                <tr>
                    <td class="module-label">负责人</td>
                    <td>
                        <?php render_select_search('responsible_user_id', $users_options, ''); ?>
                    </td>
                    <td class="module-label">协作人</td>
                    <td>
                        <?php render_select_search_multi('collaborator_user_ids', $users_options, ''); ?>
                    </td>
                    <td colspan="2"></td>
                </tr>

                <!-- 其他信息 -->
                <tr>
                    <td colspan="6" style="background:#f8f9fa;padding:8px;font-weight:bold;color:#29b6b0;">ℹ️ 其他信息</td>
                </tr>
                <tr>
                    <td class="module-label">负责人</td>
                    <td>
                        <?php render_select_search('leader_user_id', $users_options, ''); ?>
                    </td>
                    <td class="module-label">所属部门</td>
                    <td>
                        <?php render_select_search('department_id', $departments_options, ''); ?>
                    </td>
                    <td class="module-label">前负责人</td>
                    <td>
                        <?php render_select_search('previous_responsible_user_id', $users_options, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">创建人</td>
                    <td><input type="text" class="module-input" value="<?= h($_SESSION['real_name'] ?? '当前用户') ?>" readonly></td>
                    <td class="module-label">创建时间</td>
                    <td><input type="text" class="module-input" value="<?= date('Y-m-d H:i:s') ?>" readonly></td>
                    <td class="module-label">更新时间</td>
                    <td><input type="text" class="module-input" value="<?= date('Y-m-d H:i:s') ?>" readonly></td>                                        
                </tr>
            </table>
        </form>
    </div>

    <script>
        (function() {
            var form = document.getElementById('add-contract-form'),
                btnSave = document.querySelector('.btn-save'),
                btnCancel = document.querySelector('.btn-cancel');

            // 保存按钮AJAX提交
            btnSave.onclick = function() {
                var required = ['contract_name', 'customer_id', 'contract_amount', 'case_count', 'business_user_id', 'contract_type', 'payment_method'];
                for (var i = 0; i < required.length; i++) {
                    var el = form.querySelector('[name="' + required[i] + '"]');
                    if (!el || !el.value.trim()) {
                        alert('请填写所有必填项');
                        el && el.focus();
                        return;
                    }
                }

                // 验证金额和数量
                var amount = parseFloat(form.contract_amount.value);
                var count = parseInt(form.case_count.value);
                if (amount <= 0) {
                    alert('合同总金额必须大于0');
                    form.contract_amount.focus();
                    return;
                }
                if (count <= 0) {
                    alert('案件数量必须大于0');
                    form.case_count.focus();
                    return;
                }

                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/contract_management/create_contract.php', true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                alert('保存成功！合同编号：' + res.contract_no);
                                form.reset();
                                // 重置所有下拉搜索框
                                document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
                                document.querySelectorAll('.module-select-search-multi-input').forEach(i => i.value = '');
                                // 重置合同状态为未开始
                                form.contract_status.value = '未开始';
                            } else {
                                alert('保存失败：' + (res.msg || '未知错误'));
                            }
                        } catch (e) {
                            alert('保存失败：响应解析错误');
                        }
                    }
                };
                xhr.send(fd);
            };

            // 取消按钮
            btnCancel.onclick = function() {
                if (confirm('确定要取消吗？未保存的内容将丢失')) {
                    form.reset();
                    // 重置所有下拉搜索框
                    document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
                    document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
                    document.querySelectorAll('.module-select-search-multi-input').forEach(i => i.value = '');
                    document.querySelectorAll('.module-select-search-multi-box input[type=hidden]').forEach(i => i.value = '');
                    // 重置合同状态为未开始
                    form.contract_status.value = '未开始';
                }
            };
        })();
    </script>
</body>

</html>