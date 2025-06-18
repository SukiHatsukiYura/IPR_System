<?php
// 合同编辑-回款管理
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

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    // 回款计划相关操作
    if ($action === 'save_plan') {
        try {
            $id = intval($_POST['id'] ?? 0);
            $data = [
                'contract_id' => $contract_id,
                'plan_date' => trim($_POST['plan_date'] ?? ''),
                'currency' => trim($_POST['currency'] ?? '人民币'),
                'payment_type' => trim($_POST['payment_type'] ?? '正常回款'),
                'remarks' => trim($_POST['remarks'] ?? '')
            ];

            if (empty($data['plan_date'])) {
                throw new Exception('计划回款日期不能为空');
            }

            if ($id > 0) {
                // 更新
                $stmt = $pdo->prepare("UPDATE contract_payment_plan SET plan_date=?, currency=?, payment_type=?, remarks=? WHERE id=? AND contract_id=?");
                $result = $stmt->execute([$data['plan_date'], $data['currency'], $data['payment_type'], $data['remarks'], $id, $contract_id]);
            } else {
                // 新增
                $stmt = $pdo->prepare("INSERT INTO contract_payment_plan (contract_id, plan_date, currency, payment_type, remarks) VALUES (?, ?, ?, ?, ?)");
                $result = $stmt->execute([$contract_id, $data['plan_date'], $data['currency'], $data['payment_type'], $data['remarks']]);
            }

            echo json_encode(['success' => $result, 'msg' => $result ? null : '保存失败']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_plan') {
        try {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('无效的ID');
            }

            $stmt = $pdo->prepare("SELECT * FROM contract_payment_plan WHERE id=? AND contract_id=?");
            $stmt->execute([$id, $contract_id]);
            $data = $stmt->fetch();

            if (!$data) {
                throw new Exception('记录不存在');
            }

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_plan') {
        try {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('无效的ID');
            }

            $stmt = $pdo->prepare("DELETE FROM contract_payment_plan WHERE id=? AND contract_id=?");
            $result = $stmt->execute([$id, $contract_id]);

            echo json_encode(['success' => $result, 'msg' => $result ? null : '删除失败']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // 回款明细相关操作
    if ($action === 'save_detail') {
        try {
            $id = intval($_POST['id'] ?? 0);
            $data = [
                'contract_id' => $contract_id,
                'payment_plan_id' => intval($_POST['payment_plan_id'] ?? 0) ?: null,
                'payment_date' => trim($_POST['payment_date'] ?? ''),
                'currency' => trim($_POST['currency'] ?? '人民币'),
                'payment_currency' => trim($_POST['payment_currency'] ?? '人民币'),
                'payment_amount' => floatval($_POST['payment_amount'] ?? 0) ?: null,
                'expense_currency' => trim($_POST['expense_currency'] ?? '人民币'),
                'expense_amount' => floatval($_POST['expense_amount'] ?? 0) ?: null,
                'invoice_number' => trim($_POST['invoice_number'] ?? ''),
                'invoice_date' => trim($_POST['invoice_date'] ?? '') ?: null,
                'payment_method' => trim($_POST['payment_method'] ?? '支票'),
                'payment_type_category' => trim($_POST['payment_type_category'] ?? '正常回款'),
                'payee' => trim($_POST['payee'] ?? ''),
                'payee_account' => trim($_POST['payee_account'] ?? ''),
                'remarks' => trim($_POST['remarks'] ?? '')
            ];

            if (empty($data['payment_date'])) {
                throw new Exception('回款日期不能为空');
            }

            if ($id > 0) {
                // 更新
                $stmt = $pdo->prepare("UPDATE contract_payment_detail SET payment_plan_id=?, payment_date=?, currency=?, payment_currency=?, payment_amount=?, expense_currency=?, expense_amount=?, invoice_number=?, invoice_date=?, payment_method=?, payment_type_category=?, payee=?, payee_account=?, remarks=? WHERE id=? AND contract_id=?");
                $result = $stmt->execute([
                    $data['payment_plan_id'],
                    $data['payment_date'],
                    $data['currency'],
                    $data['payment_currency'],
                    $data['payment_amount'],
                    $data['expense_currency'],
                    $data['expense_amount'],
                    $data['invoice_number'],
                    $data['invoice_date'],
                    $data['payment_method'],
                    $data['payment_type_category'],
                    $data['payee'],
                    $data['payee_account'],
                    $data['remarks'],
                    $id,
                    $contract_id
                ]);
            } else {
                // 新增
                $stmt = $pdo->prepare("INSERT INTO contract_payment_detail (contract_id, payment_plan_id, payment_date, currency, payment_currency, payment_amount, expense_currency, expense_amount, invoice_number, invoice_date, payment_method, payment_type_category, payee, payee_account, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    $contract_id,
                    $data['payment_plan_id'],
                    $data['payment_date'],
                    $data['currency'],
                    $data['payment_currency'],
                    $data['payment_amount'],
                    $data['expense_currency'],
                    $data['expense_amount'],
                    $data['invoice_number'],
                    $data['invoice_date'],
                    $data['payment_method'],
                    $data['payment_type_category'],
                    $data['payee'],
                    $data['payee_account'],
                    $data['remarks']
                ]);
            }

            echo json_encode(['success' => $result, 'msg' => $result ? null : '保存失败']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_detail') {
        try {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('无效的ID');
            }

            $stmt = $pdo->prepare("SELECT * FROM contract_payment_detail WHERE id=? AND contract_id=?");
            $stmt->execute([$id, $contract_id]);
            $data = $stmt->fetch();

            if (!$data) {
                throw new Exception('记录不存在');
            }

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_detail') {
        try {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('无效的ID');
            }

            $stmt = $pdo->prepare("DELETE FROM contract_payment_detail WHERE id=? AND contract_id=?");
            $result = $stmt->execute([$id, $contract_id]);

            echo json_encode(['success' => $result, 'msg' => $result ? null : '删除失败']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'msg' => '未知操作']);
    exit;
}

// 查询回款计划
$plan_stmt = $pdo->prepare("SELECT * FROM contract_payment_plan WHERE contract_id = ? ORDER BY plan_date ASC");
$plan_stmt->execute([$contract_id]);
$payment_plans = $plan_stmt->fetchAll();

// 查询回款明细
$detail_stmt = $pdo->prepare("SELECT cpd.*, cpp.plan_date as plan_date_ref FROM contract_payment_detail cpd LEFT JOIN contract_payment_plan cpp ON cpd.payment_plan_id = cpp.id WHERE cpd.contract_id = ? ORDER BY cpd.payment_date DESC");
$detail_stmt->execute([$contract_id]);
$payment_details = $detail_stmt->fetchAll();

// 静态下拉选项
$currencies = ['人民币', '美元', '瑞士法郎', '欧元', '港元', '日元', '英镑', '荷兰盾', '加元', '新台币', '比索'];
$payment_types = ['正常回款', '退款', '订金'];
$payment_methods = ['支票', '现金', '银行转账', '微信', '支付宝', '其他'];

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
?>

<div class="module-btns">
    <button type="button" class="btn-add-plan"><i class="icon-add"></i> 新增回款计划</button>
    <button type="button" class="btn-add-detail"><i class="icon-add"></i> 新增回款明细</button>
</div>

<!-- 回款计划部分 -->
<div class="module-panel" style="margin-bottom: 20px;">
    <h4 style="margin: 0 0 15px 0; color: #333; border-bottom: 1px solid #eee; padding-bottom: 5px;">回款计划</h4>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:40px;text-align:center;">序号</th>
                <th style="width:120px;">计划回款日期</th>
                <th style="width:100px;">币种</th>
                <th style="width:120px;">回款类型</th>
                <th style="width:200px;">备注</th>
                <th style="width:120px;">创建时间</th>
                <th style="width:90px;">操作</th>
            </tr>
        </thead>
        <tbody id="payment-plan-list">
            <?php if (empty($payment_plans)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:20px 0;">暂无回款计划</td>
                </tr>
            <?php else: ?>
                <?php foreach ($payment_plans as $index => $plan): ?>
                    <tr data-id="<?= $plan['id'] ?>">
                        <td style="text-align:center;"><?= $index + 1 ?></td>
                        <td><?= h($plan['plan_date']) ?></td>
                        <td><?= h($plan['currency']) ?></td>
                        <td><?= h($plan['payment_type']) ?></td>
                        <td><?= h($plan['remarks']) ?></td>
                        <td><?= $plan['created_at'] ? date('Y-m-d H:i', strtotime($plan['created_at'])) : '' ?></td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-mini btn-edit-plan">✎</button>
                            <button type="button" class="btn-mini btn-del-plan" style="color:#f44336;">✖</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- 回款明细部分 -->
<div class="module-panel">
    <h4 style="margin: 0 0 15px 0; color: #333; border-bottom: 1px solid #eee; padding-bottom: 5px;">回款明细</h4>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:40px;text-align:center;">序号</th>
                <th style="width:100px;">回款日期</th>
                <th style="width:80px;">币种</th>
                <th style="width:100px;">代理费金额</th>
                <th style="width:100px;">开票金额</th>
                <th style="width:100px;">发票号码</th>
                <th style="width:80px;">付款方式</th>
                <th style="width:80px;">回款类型</th>
                <th style="width:100px;">收款人</th>
                <th style="width:90px;">操作</th>
            </tr>
        </thead>
        <tbody id="payment-detail-list">
            <?php if (empty($payment_details)): ?>
                <tr>
                    <td colspan="10" style="text-align:center;padding:20px 0;">暂无回款明细</td>
                </tr>
            <?php else: ?>
                <?php foreach ($payment_details as $index => $detail): ?>
                    <tr data-id="<?= $detail['id'] ?>">
                        <td style="text-align:center;"><?= $index + 1 ?></td>
                        <td><?= h($detail['payment_date']) ?></td>
                        <td><?= h($detail['currency']) ?></td>
                        <td style="text-align:right;"><?= $detail['payment_amount'] ? number_format($detail['payment_amount'], 2) : '' ?></td>
                        <td style="text-align:right;"><?= $detail['expense_amount'] ? number_format($detail['expense_amount'], 2) : '' ?></td>
                        <td><?= h($detail['invoice_number']) ?></td>
                        <td><?= h($detail['payment_method']) ?></td>
                        <td><?= h($detail['payment_type_category']) ?></td>
                        <td><?= h($detail['payee']) ?></td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-mini btn-edit-detail">✎</button>
                            <button type="button" class="btn-mini btn-del-detail" style="color:#f44336;">✖</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- 回款计划编辑弹窗 -->
<div id="edit-plan-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:600px;max-width:98vw;max-height:80vh;position:relative;display:flex;flex-direction:column;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="edit-plan-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">编辑回款计划</h3>
        <div style="flex:1 1 auto;overflow-y:auto;">
            <form id="edit-plan-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <table class="module-table" style="table-layout:fixed;width:100%;min-width:0;">
                    <colgroup>
                        <col style="width:120px;">
                        <col style="width:400px;">
                    </colgroup>
                    <tr>
                        <td class="module-label module-req">*计划回款日期</td>
                        <td><input type="date" name="plan_date" class="module-input" required></td>
                    </tr>
                    <tr>
                        <td class="module-label">币种</td>
                        <td><?= render_select('currency', $currencies, '人民币') ?></td>
                    </tr>
                    <tr>
                        <td class="module-label">回款类型</td>
                        <td><?= render_select('payment_type', $payment_types, '正常回款') ?></td>
                    </tr>
                    <tr>
                        <td class="module-label">备注</td>
                        <td><textarea name="remarks" class="module-textarea" rows="3"></textarea></td>
                    </tr>
                </table>
                <div style="text-align:center;margin-top:12px;">
                    <button type="button" class="btn-save-plan btn-mini" style="margin-right:16px;">保存</button>
                    <button type="button" class="btn-cancel-plan btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 回款明细编辑弹窗 -->
<div id="edit-detail-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:950px;max-width:98vw;max-height:80vh;position:relative;display:flex;flex-direction:column;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="edit-detail-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">编辑回款明细</h3>
        <div style="flex:1 1 auto;overflow-y:auto;">
            <form id="edit-detail-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <table class="module-table" style="table-layout:fixed;width:100%;min-width:0;">
                    <colgroup>
                        <col style="width:120px;">
                        <col style="width:320px;">
                        <col style="width:120px;">
                        <col style="width:320px;">
                    </colgroup>
                    <tr>
                        <td class="module-label">关联回款计划</td>
                        <td>
                            <select name="payment_plan_id" class="module-input">
                                <option value="">--不关联--</option>
                                <?php foreach ($payment_plans as $plan): ?>
                                    <option value="<?= $plan['id'] ?>"><?= h($plan['plan_date']) ?> - <?= h($plan['payment_type']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="module-label module-req">*回款日期</td>
                        <td><input type="date" name="payment_date" class="module-input" required></td>
                    </tr>
                    <tr>
                        <td class="module-label">回款币种</td>
                        <td><?= render_select('currency', $currencies, '人民币') ?></td>
                        <td class="module-label">代理费币种</td>
                        <td><?= render_select('payment_currency', $currencies, '人民币') ?></td>
                    </tr>
                    <tr>
                        <td class="module-label">代理费金额</td>
                        <td><input type="number" name="payment_amount" class="module-input" step="0.01" min="0" style="background-color:white;"></td>
                        <td class=" module-label">开票金额币种</td>
                        <td><?= render_select('expense_currency', $currencies, '人民币') ?></td>
                    </tr>
                    <tr>
                        <td class="module-label">开票金额</td>
                        <td><input type="number" name="expense_amount" class="module-input" step="0.01" min="0" style="background-color:white;"></td>
                        <td class="module-label">发票号码</td>
                        <td><input type="text" name="invoice_number" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">开票时间</td>
                        <td><input type="date" name="invoice_date" class="module-input"></td>
                        <td class="module-label">付款方式</td>
                        <td><?= render_select('payment_method', $payment_methods, '支票') ?></td>
                    </tr>
                    <tr>
                        <td class="module-label">回款类型</td>
                        <td><?= render_select('payment_type_category', $payment_types, '正常回款') ?></td>
                        <td class="module-label">收款人</td>
                        <td><input type="text" name="payee" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">收款人账户</td>
                        <td><input type="text" name="payee_account" class="module-input"></td>
                        <td class="module-label">备注</td>
                        <td><textarea name="remarks" class="module-textarea" rows="2"></textarea></td>
                    </tr>
                </table>
                <div style="text-align:center;margin-top:12px;">
                    <button type="button" class="btn-save-detail btn-mini" style="margin-right:16px;">保存</button>
                    <button type="button" class="btn-cancel-detail btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.initContractPaymentTabEvents = function() {
        var contractId = <?= $contract_id ?>;

        // 回款计划相关函数
        function showEditPlanModal(id) {
            var modal = document.getElementById('edit-plan-modal');
            var form = document.getElementById('edit-plan-form');
            form.reset();
            form.id.value = id || 0;

            if (!id) {
                modal.style.display = 'flex';
                return;
            }

            var fd = new FormData();
            fd.append('action', 'get_plan');
            fd.append('id', id);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/payment.php?contract_id=' + contractId, true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.data) {
                        for (var k in res.data) {
                            if (form[k] !== undefined) form[k].value = res.data[k] !== null ? res.data[k] : '';
                        }
                        modal.style.display = 'flex';
                    } else {
                        alert('获取数据失败');
                    }
                } catch (e) {
                    alert('获取数据失败');
                }
            };
            xhr.send(fd);
        }

        function reloadPaymentTab() {
            // 重新加载当前选项卡内容
            if (window.parent && window.parent.document) {
                var currentTab = window.parent.document.querySelector('.tab-btn.active');
                if (currentTab) {
                    currentTab.click();
                }
            }
        }

        // 回款明细相关函数
        function showEditDetailModal(id) {
            var modal = document.getElementById('edit-detail-modal');
            var form = document.getElementById('edit-detail-form');
            form.reset();
            form.id.value = id || 0;

            if (!id) {
                modal.style.display = 'flex';
                return;
            }

            var fd = new FormData();
            fd.append('action', 'get_detail');
            fd.append('id', id);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/payment.php?contract_id=' + contractId, true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.data) {
                        for (var k in res.data) {
                            if (form[k] !== undefined) form[k].value = res.data[k] !== null ? res.data[k] : '';
                        }
                        modal.style.display = 'flex';
                    } else {
                        alert('获取数据失败');
                    }
                } catch (e) {
                    alert('获取数据失败');
                }
            };
            xhr.send(fd);
        }

        // 绑定事件
        document.querySelector('.btn-add-plan').onclick = function() {
            showEditPlanModal();
        };

        document.querySelector('.btn-add-detail').onclick = function() {
            showEditDetailModal();
        };

        // 回款计划表格事件
        document.querySelectorAll('#payment-plan-list .btn-edit-plan').forEach(function(btn) {
            btn.onclick = function() {
                var id = this.closest('tr').getAttribute('data-id');
                showEditPlanModal(id);
            };
        });

        document.querySelectorAll('#payment-plan-list .btn-del-plan').forEach(function(btn) {
            btn.onclick = function() {
                if (!confirm('确定删除该回款计划？')) return;
                var id = this.closest('tr').getAttribute('data-id');
                var fd = new FormData();
                fd.append('action', 'delete_plan');
                fd.append('id', id);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/payment.php?contract_id=' + contractId, true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            reloadPaymentTab();
                        } else {
                            alert('删除失败');
                        }
                    } catch (e) {
                        alert('删除失败');
                    }
                };
                xhr.send(fd);
            };
        });

        // 回款明细表格事件
        document.querySelectorAll('#payment-detail-list .btn-edit-detail').forEach(function(btn) {
            btn.onclick = function() {
                var id = this.closest('tr').getAttribute('data-id');
                showEditDetailModal(id);
            };
        });

        document.querySelectorAll('#payment-detail-list .btn-del-detail').forEach(function(btn) {
            btn.onclick = function() {
                if (!confirm('确定删除该回款明细？')) return;
                var id = this.closest('tr').getAttribute('data-id');
                var fd = new FormData();
                fd.append('action', 'delete_detail');
                fd.append('id', id);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/payment.php?contract_id=' + contractId, true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            reloadPaymentTab();
                        } else {
                            alert('删除失败');
                        }
                    } catch (e) {
                        alert('删除失败');
                    }
                };
                xhr.send(fd);
            };
        });

        // 模态框关闭事件
        document.getElementById('edit-plan-modal-close').onclick = function() {
            document.getElementById('edit-plan-modal').style.display = 'none';
        };

        document.querySelector('.btn-cancel-plan').onclick = function() {
            document.getElementById('edit-plan-modal').style.display = 'none';
        };

        document.getElementById('edit-detail-modal-close').onclick = function() {
            document.getElementById('edit-detail-modal').style.display = 'none';
        };

        document.querySelector('.btn-cancel-detail').onclick = function() {
            document.getElementById('edit-detail-modal').style.display = 'none';
        };

        // 保存事件
        document.querySelector('.btn-save-plan').onclick = function() {
            var form = document.getElementById('edit-plan-form');
            var fd = new FormData(form);
            fd.append('action', 'save_plan');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/payment.php?contract_id=' + contractId, true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        document.getElementById('edit-plan-modal').style.display = 'none';
                        reloadPaymentTab();
                    } else {
                        alert(res.msg || '保存失败');
                    }
                } catch (e) {
                    alert('保存失败');
                }
            };
            xhr.send(fd);
        };

        document.querySelector('.btn-save-detail').onclick = function() {
            var form = document.getElementById('edit-detail-form');
            var fd = new FormData(form);
            fd.append('action', 'save_detail');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/contract_management/edit_tabs/payment.php?contract_id=' + contractId, true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        document.getElementById('edit-detail-modal').style.display = 'none';
                        reloadPaymentTab();
                    } else {
                        alert(res.msg || '保存失败');
                    }
                } catch (e) {
                    alert('保存失败');
                }
            };
            xhr.send(fd);
        };
    };

    // 初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initContractPaymentTabEvents);
    } else {
        window.initContractPaymentTabEvents();
    }
</script>