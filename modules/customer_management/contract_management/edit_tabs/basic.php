<?php
// 合同编辑-基本信息
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

// 处理POST请求（保存数据）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json');

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
    ];

    // 修正：所有DATE类型字段为空字符串时转为null
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

    // 修正：所有外键字段为0或小于0时转为null
    $fk_fields = ['customer_id', 'opportunity_id', 'business_user_id', 'responsible_user_id', 'leader_user_id', 'department_id', 'previous_responsible_user_id'];
    foreach ($fk_fields as $field) {
        if (isset($data[$field]) && $data[$field] <= 0) {
            $data[$field] = null;
        }
    }

    try {
        $set = [];
        foreach ($data as $k => $v) {
            $set[] = "$k = :$k";
        }
        $data['id'] = $contract_id;
        $sql = "UPDATE contract SET " . implode(',', $set) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($data);
        echo json_encode(['success' => $result, 'msg' => $result ? null : '更新失败']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    }
    exit;
}

// 查询动态数据
$departments = $pdo->query("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();
$users = $pdo->query("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name")->fetchAll();
$customers = $pdo->query("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn")->fetchAll();

// 静态下拉选项
$contract_types = ['代理合同', '采购合同', '服务合同', '直销合同', '其他'];
$payment_methods = ['支票', '现金', '银行转账', '微信', '支付宝', '其他'];
$contract_statuses = ['未开始', '执行中', '成功结束', '意外终止'];
$currencies = ['人民币', '美元', '瑞士法郎', '欧元', '港元', '日元', '英镑', '荷兰盾', '加元', '新台币', '比索'];

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

// 格式化数据为下拉框所需格式
$customer_options = [];
foreach ($customers as $customer) {
    $customer_options[$customer['id']] = $customer['customer_name_cn'];
}

$user_options = [];
foreach ($users as $user) {
    $user_options[$user['id']] = $user['real_name'];
}

$dept_options = [];
foreach ($departments as $dept) {
    $dept_options[$dept['id']] = $dept['dept_name'];
}

// 输出下拉搜索所需的JS和CSS
render_select_search_assets();
?>

<div class="module-btns">
    <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
    <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
</div>

<form id="edit-contract-form" class="module-form" autocomplete="off">
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
            <td><input type="text" name="contract_no" class="module-input" value="<?= h($contract_info['contract_no']) ?>" readonly></td>
            <td class="module-label module-req">*合同名称</td>
            <td><input type="text" name="contract_name" class="module-input" value="<?= h($contract_info['contract_name']) ?>" required></td>
            <td class="module-label module-req">*对应客户</td>
            <td>
                <?php render_select_search('customer_id', $customer_options, $contract_info['customer_id']); ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">对应的商机</td>
            <td><input type="text" name="opportunity_id" class="module-input" value="<?= h($contract_info['opportunity_id']) ?>" placeholder="商机ID（可选）"></td>
            <td class="module-label module-req">*合同总金额</td>
            <td>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="number" name="contract_amount" class="module-input" step="1" min="0" required style="background-color:white;width:60%;" value="<?= h($contract_info['contract_amount']) ?>">
                    <select name="currency" class="module-input" style="width:38%;">
                        <?php foreach ($currencies as $currency): ?>
                            <option value="<?= h($currency) ?>" <?= $contract_info['currency'] === $currency ? 'selected' : '' ?>><?= h($currency) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </td>
            <td class="module-label">合同有效时间</td>
            <td class="module-date-range">
                <input type="date" name="valid_start_date" class="module-input" value="<?= h($contract_info['valid_start_date']) ?>"> 至
                <input type="date" name="valid_end_date" class="module-input" value="<?= h($contract_info['valid_end_date']) ?>">
            </td>
        </tr>
        <tr>
            <td class="module-label module-req">*案件数量</td>
            <td><input type="number" name="case_count" class="module-input" min="0" required value="<?= h($contract_info['case_count']) ?>" style="background-color:white;"></td>
            <td class="module-label">甲方签约人</td>
            <td><input type="text" name="party_a_signer" class="module-input" value="<?= h($contract_info['party_a_signer']) ?>"></td>
            <td class="module-label">甲方签约人手机</td>
            <td><input type="text" name="party_a_signer_mobile" class="module-input" value="<?= h($contract_info['party_a_signer_mobile']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label module-req">*业务人员</td>
            <td>
                <?php render_select_search('business_user_id', $user_options, $contract_info['business_user_id']); ?>
            </td>
            <td class="module-label module-req">*合同类型</td>
            <td>
                <?php echo render_select('contract_type', $contract_types, $contract_info['contract_type']); ?>
            </td>
            <td class="module-label module-req">*付款方式</td>
            <td>
                <?php echo render_select('payment_method', $payment_methods, $contract_info['payment_method']); ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">乙方签约公司</td>
            <td><input type="text" name="party_b_company" class="module-input" value="<?= h($contract_info['party_b_company']) ?>"></td>
            <td class="module-label">乙方签约人</td>
            <td><input type="text" name="party_b_signer" class="module-input" value="<?= h($contract_info['party_b_signer']) ?>"></td>
            <td class="module-label">乙方签约人手机</td>
            <td><input type="text" name="party_b_signer_mobile" class="module-input" value="<?= h($contract_info['party_b_signer_mobile']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">签约日期</td>
            <td><input type="date" name="sign_date" class="module-input" value="<?= h($contract_info['sign_date']) ?>"></td>
            <td class="module-label">合同领用日期</td>
            <td><input type="date" name="contract_receive_date" class="module-input" value="<?= h($contract_info['contract_receive_date']) ?>"></td>
            <td class="module-label">备注</td>
            <td><textarea name="remarks" class="module-textarea" rows="2"><?= h($contract_info['remarks']) ?></textarea></td>
        </tr>

        <!-- 跟进信息 -->
        <tr>
            <td colspan="6" style="background:#f8f9fa;padding:8px;font-weight:bold;color:#29b6b0;">📞 跟进信息</td>
        </tr>
        <tr>
            <td class="module-label">合同状态</td>
            <td>
                <?php echo render_select('contract_status', $contract_statuses, $contract_info['contract_status']); ?>
            </td>
            <td class="module-label">下次跟进时间</td>
            <td><input type="date" name="next_follow_date" class="module-input" value="<?= h($contract_info['next_follow_date']) ?>"></td>
            <td colspan="2"></td>
        </tr>

        <!-- 人员信息 -->
        <tr>
            <td colspan="6" style="background:#f8f9fa;padding:8px;font-weight:bold;color:#29b6b0;">👥 人员信息</td>
        </tr>
        <tr>
            <td class="module-label">负责人</td>
            <td>
                <?php render_select_search('responsible_user_id', $user_options, $contract_info['responsible_user_id']); ?>
            </td>
            <td class="module-label">协作人</td>
            <td>
                <?php
                $selected_collaborators = $contract_info['collaborator_user_ids'] ? explode(',', $contract_info['collaborator_user_ids']) : [];
                render_select_search_multi('collaborator_user_ids', $user_options, $selected_collaborators);
                ?>
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
                <?php render_select_search('leader_user_id', $user_options, $contract_info['leader_user_id']); ?>
            </td>
            <td class="module-label">所属部门</td>
            <td>
                <?php render_select_search('department_id', $dept_options, $contract_info['department_id']); ?>
            </td>
            <td class="module-label">前负责人</td>
            <td>
                <?php render_select_search('previous_responsible_user_id', $user_options, $contract_info['previous_responsible_user_id']); ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">创建人</td>
            <td>
                <?php
                $creator_name = '';
                if ($contract_info['creator_user_id']) {
                    $creator_stmt = $pdo->prepare("SELECT real_name FROM user WHERE id = ?");
                    $creator_stmt->execute([$contract_info['creator_user_id']]);
                    $creator = $creator_stmt->fetch();
                    $creator_name = $creator ? $creator['real_name'] : '';
                }
                ?>
                <input type="text" class="module-input" value="<?= h($creator_name) ?>" readonly>
            </td>
            <td class="module-label">创建时间</td>
            <td><input type="text" class="module-input" value="<?= h($contract_info['created_at']) ?>" readonly></td>
            <td class="module-label">更新时间</td>
            <td><input type="text" class="module-input" value="<?= h($contract_info['updated_at']) ?>" readonly></td>                       
        </tr>
    </table>
</form>

<script>
    window.initContractTabEvents = function() {
        var form = document.getElementById('edit-contract-form'),
            btnSave = document.querySelector('#contract-tab-content .btn-save'),
            btnCancel = document.querySelector('#contract-tab-content .btn-cancel');

        // 保存按钮AJAX提交
        if (btnSave) {
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
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/basic.php?contract_id=<?= $contract_id ?>', true);
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
                    xhr.open('GET', 'modules/customer_management/contract_management/edit_tabs/basic.php?contract_id=<?= $contract_id ?>', true);
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
                                    if (typeof window.initContractTabEvents === 'function') {
                                        window.initContractTabEvents();
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

        // 初始化下拉搜索框
        if (typeof initSelectSearchBoxes === 'function') {
            initSelectSearchBoxes();
        }
    };

    // 初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initContractTabEvents);
    } else {
        window.initContractTabEvents();
    }
</script>