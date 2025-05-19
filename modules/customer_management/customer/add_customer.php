<?php
// 新增/编辑客户功能 - 客户管理/客户模块下的客户管理功能

include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 在文件顶部添加调试信息
error_log("Request to add_customer.php");

// 检查是否为编辑模式（从会话变量中获取ID）
$is_edit_mode = false;
$customer = null;
$customer_id = 0;

// 优先从URL参数获取ID
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $customer_id = intval($_GET['id']);
    error_log("Customer ID from URL: $customer_id");
}
// 如果URL参数中没有ID，则从会话变量中获取
elseif (isset($_SESSION['edit_customer_id']) && intval($_SESSION['edit_customer_id']) > 0) {
    $customer_id = intval($_SESSION['edit_customer_id']);
    error_log("Customer ID from session: $customer_id");
    // 使用后清除会话变量，避免下次访问时仍然是编辑模式
    unset($_SESSION['edit_customer_id']);
}

if ($customer_id > 0) {
    // 编辑模式 - 查询客户信息
    $is_edit_mode = true;
    error_log("Edit mode activated for customer ID: $customer_id");
    $customer_stmt = $pdo->prepare("SELECT * FROM customer WHERE id = ?");
    $customer_stmt->execute([$customer_id]);
    $customer = $customer_stmt->fetch();

    if (!$customer) {
        $error = '未找到该客户信息';
        $is_edit_mode = false;
        error_log("Customer not found for ID: $customer_id");
    } else {
        error_log("Customer found: " . $customer['customer_name_cn']);
    }
}

// 调试输出
error_log("Final edit mode: " . ($is_edit_mode ? "true" : "false") . ", Customer ID: $customer_id");

// 查询所有在职用户用于下拉
$user_stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name ASC");
$user_stmt->execute();
$users = $user_stmt->fetchAll();

// 客户等级、案件类型、成交状态等选项
$customer_levels = ['一般客户', '重要客户', '潜在客户', '个人', '企业', '中介'];
$deal_statuses = ['否', '是'];
$case_types = ['patent' => '专利', 'trademark' => '商标', 'copyright' => '版权'];

// 客户来源
$customer_sources = [
    '电话来访',
    '客户介绍',
    '客户',
    '立项开发',
    '媒体宣传',
    '代理商',
    '合作伙伴',
    '公开招标',
    '直邮',
    '网站',
    '回单',
    '其他',
    '2022年度商标局品牌导站（实员）建设项目'
];

// 所属行业
$industry_options = ['地产', '制造业', '互联网', '金融', '教育', '医疗', '能源', '交通', '物流', '建筑', '传媒', '农业', '旅游', '政府', '军工', '其他'];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json');
    $data = [
        'customer_name_cn' => trim($_POST['customer_name_cn'] ?? ''),
        'customer_name_en' => trim($_POST['customer_name_en'] ?? ''),
        'company_leader' => trim($_POST['company_leader'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'business_staff_id' => intval($_POST['business_staff_id'] ?? (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0)),
        'internal_signer' => trim($_POST['internal_signer'] ?? ''),
        'external_signer' => trim($_POST['external_signer'] ?? ''),
        'process_staff_id' => intval($_POST['process_staff_id'] ?? (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0)),
        'customer_level' => trim($_POST['customer_level'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'bank_name' => trim($_POST['bank_name'] ?? ''),
        'deal_status' => trim($_POST['deal_status'] ?? ''),
        'project_leader_id' => intval($_POST['project_leader_id'] ?? 0),
        'remark' => trim($_POST['remark'] ?? ''),
        'case_type_patent' => isset($_POST['case_type_patent']) ? 1 : 0,
        'case_type_trademark' => isset($_POST['case_type_trademark']) ? 1 : 0,
        'case_type_copyright' => isset($_POST['case_type_copyright']) ? 1 : 0,
        'phone' => trim($_POST['phone'] ?? ''),
        'industry' => trim($_POST['industry'] ?? ''),
        'creator' => $_SESSION['username'],
        'internal_signer_phone' => trim($_POST['internal_signer_phone'] ?? ''),
        'external_signer_phone' => trim($_POST['external_signer_phone'] ?? ''),
        'billing_address' => trim($_POST['billing_address'] ?? ''),
        'credit_level' => trim($_POST['credit_level'] ?? ''),
        'address_en' => trim($_POST['address_en'] ?? ''),
        'bank_account' => trim($_POST['bank_account'] ?? ''),
        'customer_id_code' => trim($_POST['customer_id_code'] ?? ''),
        'new_case_manager_id' => intval($_POST['new_case_manager_id'] ?? 0),
        'fax' => trim($_POST['fax'] ?? ''),
        'customer_source' => trim($_POST['customer_source'] ?? ''),
        'internal_signer_email' => trim($_POST['internal_signer_email'] ?? ''),
        'external_signer_email' => trim($_POST['external_signer_email'] ?? ''),
        'delivery_address' => trim($_POST['delivery_address'] ?? ''),
        'sign_date' => trim($_POST['sign_date'] ?? ''),
        'public_email' => trim($_POST['public_email'] ?? ''),
        'tax_id' => trim($_POST['tax_id'] ?? ''),
    ];
    // 必填校验
    if ($data['customer_name_cn'] === '' || (!$data['case_type_patent'] && !$data['case_type_trademark'] && !$data['case_type_copyright'])) {
        echo json_encode(['success' => false, 'msg' => '请填写所有必填项（客户名称、案件类型）']);
        exit;
    }
    $data['sign_date'] = ($data['sign_date'] === '' ? null : $data['sign_date']);
    try {
        if ($is_edit_mode && $customer_id > 0) {
            // 编辑模式 - 只保留UPDATE语句用到的字段
            $data_update = [
                'customer_name_cn' => $data['customer_name_cn'],
                'customer_name_en' => $data['customer_name_en'],
                'company_leader' => $data['company_leader'],
                'email' => $data['email'],
                'business_staff_id' => $data['business_staff_id'],
                'internal_signer' => $data['internal_signer'],
                'external_signer' => $data['external_signer'],
                'process_staff_id' => $data['process_staff_id'],
                'customer_level' => $data['customer_level'],
                'address' => $data['address'],
                'bank_name' => $data['bank_name'],
                'deal_status' => $data['deal_status'],
                'project_leader_id' => $data['project_leader_id'],
                'remark' => $data['remark'],
                'case_type_patent' => $data['case_type_patent'],
                'case_type_trademark' => $data['case_type_trademark'],
                'case_type_copyright' => $data['case_type_copyright'],
                'phone' => $data['phone'],
                'industry' => $data['industry'],
                'internal_signer_phone' => $data['internal_signer_phone'],
                'external_signer_phone' => $data['external_signer_phone'],
                'billing_address' => $data['billing_address'],
                'credit_level' => $data['credit_level'],
                'address_en' => $data['address_en'],
                'bank_account' => $data['bank_account'],
                'customer_id_code' => $data['customer_id_code'],
                'new_case_manager_id' => $data['new_case_manager_id'],
                'fax' => $data['fax'],
                'customer_source' => $data['customer_source'],
                'internal_signer_email' => $data['internal_signer_email'],
                'external_signer_email' => $data['external_signer_email'],
                'delivery_address' => $data['delivery_address'],
                'sign_date' => $data['sign_date'],
                'public_email' => $data['public_email'],
                'tax_id' => $data['tax_id'],
                'id' => $customer_id
            ];
            $sql = "UPDATE customer SET 
                customer_name_cn = :customer_name_cn,
                customer_name_en = :customer_name_en,
                company_leader = :company_leader,
                email = :email,
                business_staff_id = :business_staff_id,
                internal_signer = :internal_signer,
                external_signer = :external_signer,
                process_staff_id = :process_staff_id,
                customer_level = :customer_level,
                address = :address,
                bank_name = :bank_name,
                deal_status = :deal_status,
                project_leader_id = :project_leader_id,
                remark = :remark,
                case_type_patent = :case_type_patent,
                case_type_trademark = :case_type_trademark,
                case_type_copyright = :case_type_copyright,
                phone = :phone,
                industry = :industry,
                internal_signer_phone = :internal_signer_phone,
                external_signer_phone = :external_signer_phone,
                billing_address = :billing_address,
                credit_level = :credit_level,
                address_en = :address_en,
                bank_account = :bank_account,
                customer_id_code = :customer_id_code,
                new_case_manager_id = :new_case_manager_id,
                fax = :fax,
                customer_source = :customer_source,
                internal_signer_email = :internal_signer_email,
                external_signer_email = :external_signer_email,
                delivery_address = :delivery_address,
                sign_date = :sign_date,
                public_email = :public_email,
                tax_id = :tax_id
            WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute($data_update);
            if ($ok) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'msg' => '保存失败']);
            }
        } else {
            // 新增模式 - 执行INSERT操作
            $sql = "INSERT INTO customer (
                customer_name_cn, customer_name_en, company_leader, email, business_staff_id, internal_signer, external_signer, process_staff_id, customer_level, address, bank_name, deal_status, project_leader_id, remark, case_type_patent, case_type_trademark, case_type_copyright, phone, industry, creator, internal_signer_phone, external_signer_phone, billing_address, credit_level, address_en, bank_account, customer_id_code, new_case_manager_id, fax, customer_source, internal_signer_email, external_signer_email, delivery_address, sign_date, public_email, tax_id
            ) VALUES (
                :customer_name_cn, :customer_name_en, :company_leader, :email, :business_staff_id, :internal_signer, :external_signer, :process_staff_id, :customer_level, :address, :bank_name, :deal_status, :project_leader_id, :remark, :case_type_patent, :case_type_trademark, :case_type_copyright, :phone, :industry, :creator, :internal_signer_phone, :external_signer_phone, :billing_address, :credit_level, :address_en, :bank_account, :customer_id_code, :new_case_manager_id, :fax, :customer_source, :internal_signer_email, :external_signer_email, :delivery_address, :sign_date, :public_email, :tax_id
            )";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute($data);
            if ($ok) {
                $new_id = $pdo->lastInsertId();
                $code = 'KH' . date('Ymd') . str_pad($new_id, 3, '0', STR_PAD_LEFT);
                $pdo->prepare('UPDATE customer SET customer_code=? WHERE id=?')->execute([$code, $new_id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'msg' => '保存失败']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    }
    exit;
}

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
function render_user_search($name, $users, $post_val)
{
    global $_SESSION;
    if ($name === 'business_staff_id' || $name === 'process_staff_id') {
        $val = isset($_POST[$name]) ? intval($_POST[$name]) : (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0);
    } else {
        $val = isset($_POST[$name]) ? intval($_POST[$name]) : 0;
    }
    $display = '';
    foreach ($users as $u) {
        if ($u['id'] == $val) {
            $display = htmlspecialchars($u['real_name'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
    return '<div class="module-select-search-box">'
        . '<input type="text" class="module-input module-select-search-input" name="' . $name . '_display' . '" value="' . $display . '" readonly placeholder="点击选择" data-realname="' . $display . '">'
        . '<input type="hidden" name="' . $name . '" value="' . ($val ? $val : '') . '">'
        . '<div class="module-select-search-list" style="display:none;">'
        .   '<input type="text" class="module-select-search-list-input" placeholder="搜索姓名">'
        .   '<div class="module-select-search-list-items"></div>'
        . '</div>'
        . '</div>';
}
?>
<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
        <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
    </div>
    <?php if (isset($error)): ?>
        <div style="color:#f44336;text-align:center;margin-bottom:12px;"><?= h($error) ?></div>
    <?php endif; ?>

    <h3 style="text-align:center;margin-bottom:15px;"><?= $is_edit_mode ? '编辑客户' : '新增客户' ?></h3>

    <form class="module-form" method="post" autocomplete="off">
        <table class="module-table">
            <tr>
                <td class="module-label">客户编号：</td>
                <td><input type="text" class="module-input" value="<?= $is_edit_mode ? h($customer['customer_code']) : '不填写，系统将自动生成' ?>" readonly></td>
                <td class="module-label module-req">*客户名称(中)：</td>
                <td><input type="text" name="customer_name_cn" class="module-input" value="<?= $is_edit_mode ? h($customer['customer_name_cn']) : h($_POST['customer_name_cn'] ?? '') ?>" required></td>
                <td class="module-label">客户代码：</td>
                <td><input type="text" name="customer_id_code" class="module-input" value="<?= $is_edit_mode ? h($customer['customer_id_code']) : h($_POST['customer_id_code'] ?? '') ?>"></td>
            </tr>
            <tr>
                <td class="module-label">公司负责人：</td>
                <td><input type="text" name="company_leader" class="module-input" value="<?= $is_edit_mode ? h($customer['company_leader']) : h($_POST['company_leader'] ?? '') ?>"></td>
                <td class="module-label">客户名称(英)：</td>
                <td><input type="text" name="customer_name_en" class="module-input" value="<?= $is_edit_mode ? h($customer['customer_name_en']) : h($_POST['customer_name_en'] ?? '') ?>"></td>
                <td class="module-label">新申请配案主管：</td>
                <td>
                    <?= render_user_search('new_case_manager_id', $users, $is_edit_mode ? $customer['new_case_manager_id'] : ($_POST['new_case_manager_id'] ?? '')) ?>
                </td>
            </tr>
            <tr>
                <td class="module-label">邮件：</td>
                <td><input type="email" name="email" class="module-input" value="<?= h($_POST['email'] ?? '') ?>"></td>
                <td class="module-label">电话：</td>
                <td><input type="text" name="phone" class="module-input" value="<?= h($_POST['phone'] ?? '') ?>"></td>
                <td class="module-label">传真：</td>
                <td><input type="text" name="fax" class="module-input" value="<?= h($_POST['fax'] ?? '') ?>"></td>
            </tr>
            <tr>
                <td class="module-label">业务人员：</td>
                <td>
                    <?= render_user_search('business_staff_id', $users, $_POST['business_staff_id'] ?? '') ?>
                </td>
                <td class="module-label">所属行业：</td>
                <td>
                    <div class="module-select-search-multi-box">
                        <input type="text" class="module-input module-select-search-multi-input" name="industry_display" readonly placeholder="请选择/搜索行业" value="<?= h($_POST['industry'] ?? '') ?>">
                        <input type="hidden" name="industry" value="<?= h($_POST['industry'] ?? '') ?>">
                        <div class="module-select-search-multi-list" style="display:none;">
                            <input type="text" class="module-select-search-multi-list-input" placeholder="搜索行业">
                            <div class="module-select-search-multi-list-ops"></div>
                            <div class="module-select-search-multi-list-items"></div>
                        </div>
                    </div>
                </td>
                <td class="module-label">客户来源：</td>
                <td>
                    <select name="customer_source" class="module-input">
                        <option value="">--请选择--</option>
                        <?php foreach ($customer_sources as $v): ?>
                            <option value="<?= h($v) ?>" <?= (isset($_POST['customer_source']) && $_POST['customer_source'] == $v) ? 'selected' : '' ?>><?= h($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="module-label">内部签署人：</td>
                <td><input type="text" name="internal_signer" class="module-input" value="<?= h($_POST['internal_signer'] ?? '') ?>"></td>
                <td class="module-label">创建人：</td>
                <td><input type="text" class="module-input" value="<?= h($_SESSION['username']) ?>" readonly></td>
                <td class="module-label">创建日期：</td>
                <td><input type="text" class="module-input" value="<?= date('Y-m-d H:i:s') ?>" readonly></td>
            </tr>
            <tr>
                <td class="module-label">外部签署人：</td>
                <td><input type="text" name="external_signer" class="module-input" value="<?= h($_POST['external_signer'] ?? '') ?>"></td>
                <td class="module-label">内部签署人电话：</td>
                <td><input type="text" name="internal_signer_phone" class="module-input" value="<?= h($_POST['internal_signer_phone'] ?? '') ?>"></td>
                <td class="module-label">内部签署人邮箱：</td>
                <td><input type="email" name="internal_signer_email" class="module-input" value="<?= h($_POST['internal_signer_email'] ?? '') ?>"></td>
            </tr>
            <tr>
                <td class="module-label">流程人员：</td>
                <td>
                    <?= render_user_search('process_staff_id', $users, $_POST['process_staff_id'] ?? '') ?>
                </td>
                <td class="module-label">外部签署人电话：</td>
                <td><input type="text" name="external_signer_phone" class="module-input" value="<?= h($_POST['external_signer_phone'] ?? '') ?>"></td>
                <td class="module-label">外部签署人邮箱：</td>
                <td><input type="email" name="external_signer_email" class="module-input" value="<?= h($_POST['external_signer_email'] ?? '') ?>"></td>
            </tr>
            <tr>
                <td class="module-label">客户等级：</td>
                <td>
                    <select name="customer_level" class="module-input" required>
                        <option value="">--请选择--</option>
                        <?php foreach ($customer_levels as $v): ?>
                            <option value="<?= h($v) ?>" <?= (isset($_POST['customer_level']) && $_POST['customer_level'] == $v) ? 'selected' : '' ?>><?= h($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="module-label">账单地址：</td>
                <td><input type="text" name="billing_address" class="module-input" value="<?= h($_POST['billing_address'] ?? '') ?>"></td>
                <td class="module-label">收货地址：</td>
                <td><input type="text" name="delivery_address" class="module-input" value="<?= h($_POST['delivery_address'] ?? '') ?>"></td>
            </tr>
            <tr>
                <td class="module-label">地址：</td>
                <td><input type="text" name="address" class="module-input" value="<?= h($_POST['address'] ?? '') ?>"></td>
                <td class="module-label">信誉等级：</td>
                <td>
                    <select name="credit_level" class="module-input">
                        <option value="">--请选择--</option>
                        <option value="高度信誉" <?= (isset($_POST['credit_level']) && $_POST['credit_level'] == '高度信誉') ? 'selected' : '' ?>>高度信誉</option>
                        <option value="中度信誉" <?= (isset($_POST['credit_level']) && $_POST['credit_level'] == '中度信誉') ? 'selected' : '' ?>>中度信誉</option>
                        <option value="低度信誉" <?= (isset($_POST['credit_level']) && $_POST['credit_level'] == '低度信誉') ? 'selected' : '' ?>>低度信誉</option>
                    </select>
                </td>
                <td class="module-label">客户签约日期：</td>
                <td><input type="date" name="sign_date" class="module-input" value="<?= h($_POST['sign_date'] ?? '') ?>"></td>
            </tr>
            <tr>
                <td class="module-label">开户银行：</td>
                <td><input type="text" name="bank_name" class="module-input" value="<?= h($_POST['bank_name'] ?? '') ?>"></td>
                <td class="module-label">英文地址：</td>
                <td><input type="text" name="address_en" class="module-input" value="<?= h($_POST['address_en'] ?? '') ?>"></td>
                <td class="module-label">本所业务公共邮箱：</td>
                <td><input type="email" name="public_email" class="module-input" value="<?= h($_POST['public_email'] ?? '') ?>"></td>
            </tr>
            <tr>
                <td class="module-label">成交状态：</td>
                <td>
                    <select name="deal_status" class="module-input">
                        <option value="">--请选择--</option>
                        <?php foreach ($deal_statuses as $v): ?>
                            <option value="<?= h($v) ?>" <?= (isset($_POST['deal_status']) && $_POST['deal_status'] == $v) ? 'selected' : '' ?>><?= h($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="module-label">银行账号：</td>
                <td><input type="text" name="bank_account" class="module-input" value="<?= h($_POST['bank_account'] ?? '') ?>"></td>
                <td class="module-label">纳税人识别号：</td>
                <td><input type="text" name="tax_id" class="module-input" value="<?= h($_POST['tax_id'] ?? '') ?>"></td>
            </tr>
            <tr>
                <td class="module-label">项目负责人：</td>
                <td>
                    <?= render_user_search('project_leader_id', $users, $is_edit_mode ? $customer['project_leader_id'] : ($_POST['project_leader_id'] ?? '')) ?>
                </td>
                <td class="module-label module-req">*案件类型：</td>
                <td colspan="3">
                    <?php foreach ($case_types as $k => $v): ?>
                        <label style="margin-right:12px;">
                            <input type="checkbox" name="case_type_<?= $k ?>" value="1"
                                <?php if ($is_edit_mode): ?>
                                <?= $customer['case_type_' . $k] ? 'checked' : '' ?>
                                <?php else: ?>
                                <?= isset($_POST['case_type_' . $k]) ? 'checked' : '' ?>
                                <?php endif; ?>> <?= h($v) ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <td class="module-label">备注：</td>
                <td colspan="5"><textarea name="remark" class="module-input" style="min-height:48px;"><?= $is_edit_mode ? h($customer['remark']) : h($_POST['remark'] ?? '') ?></textarea></td>
            </tr>
        </table>
    </form>
</div>
<?php if ($is_edit_mode && $customer_id > 0): ?>

    <div class="module-btn">
        <div id="customer-tabs-bar" style="margin-bottom:10px;">
            <button type="button" class="btn-mini tab-btn active" data-tab="contact">联系人</button>
            <button type="button" class="btn-mini tab-btn" data-tab="applicant">申请人</button>
            <button type="button" class="btn-mini tab-btn" data-tab="inventor">发明人</button>
            <button type="button" class="btn-mini tab-btn" data-tab="requirement">客户要求</button>
            <button type="button" class="btn-mini tab-btn" data-tab="contact_record">联系记录</button>
            <button type="button" class="btn-mini tab-btn" data-tab="customer_file">客户文件</button>
            <!-- 以后可加更多tab按钮 -->
        </div>
        <div id="customer-tab-content" style="min-height:180px;"></div>
    </div>
    <script>
        (function() {
            var customerId = <?= $customer_id ?>;

            function loadTab(tab) {
                var content = document.getElementById('customer-tab-content');
                content.innerHTML = '<div style="padding:40px;text-align:center;color:#888;">加载中...</div>';
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'modules/customer_management/customer/customer_tabs/' + tab + '.php?customer_id=' + customerId, true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            content.innerHTML = xhr.responseText;
                            // 提取并执行所有<script>标签
                            var scripts = content.querySelectorAll('script');
                            scripts.forEach(function(script) {
                                var newScript = document.createElement('script');
                                if (script.src) {
                                    newScript.src = script.src;
                                } else {
                                    newScript.text = script.textContent;
                                }
                                document.body.appendChild(newScript);
                            });
                        } else {
                            content.innerHTML = '<div style="padding:40px;text-align:center;color:#f44336;">加载失败</div>';
                        }
                    }
                };
                xhr.send();
            }
            // tab切换
            document.querySelectorAll('.tab-btn').forEach(function(btn) {
                btn.onclick = function() {
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    loadTab(btn.getAttribute('data-tab'));
                };
            });
            // 默认加载联系人tab
            loadTab('contact');
        })();
    </script>
<?php endif; ?>
<script>
    (function() {
        var form = document.querySelector('.module-form');
        var btnSave = document.querySelector('.btn-save');
        var btnCancel = document.querySelector('.btn-cancel');
        // 保存按钮AJAX提交
        btnSave.onclick = function() {
            var required = ['customer_name_cn'];
            var hasType = form.querySelector('[name="case_type_patent"]').checked || form.querySelector('[name="case_type_trademark"]').checked || form.querySelector('[name="case_type_copyright"]').checked;
            for (var i = 0; i < required.length; i++) {
                var el = form.querySelector('[name="' + required[i] + '"]');
                if (!el || !el.value.trim()) {
                    alert('请填写所有必填项（客户名称、案件类型）');
                    el && el.focus();
                    return;
                }
            }
            if (!hasType) {
                alert('请至少选择一个案件类型');
                return;
            }
            var fd = new FormData(form);
            fd.append('action', 'save');
            var xhr = new XMLHttpRequest();
            // 始终使用绝对路径
            var url = 'modules/customer_management/customer/add_customer.php<?= $is_edit_mode ? "?id={$customer_id}" : "" ?>';
            xhr.open('POST', url, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            alert('保存成功');
                            <?php if (!$is_edit_mode): ?>
                                form.reset();
                            <?php else: ?>
                                // 编辑模式成功后返回列表页
                                if (confirm('编辑成功，是否返回客户列表？')) {
                                    if (window.parent.openTab) {
                                        window.parent.openTab(0, 1, 1);
                                    } else {
                                        window.location.href = 'customer_list.php';
                                    }
                                }
                            <?php endif; ?>
                        } else {
                            alert(res.msg || '保存失败');
                        }
                    } catch (e) {
                        console.error('保存失败，响应内容不是JSON');
                        console.log('xhr.status:', xhr.status);
                        console.log('xhr.responseURL:', xhr.responseURL);
                        console.log('xhr.responseText:', xhr.responseText);
                        console.log('异常信息:', e);
                        alert('保存失败：' + xhr.responseText);
                    }
                }
            };
            xhr.send(fd);
        };
        btnCancel.onclick = function() {
            <?php if ($is_edit_mode): ?>
                // 编辑模式取消返回列表页
                if (confirm('确定取消编辑并返回客户列表？')) {
                    if (window.parent.openTab) {
                        window.parent.openTab(0, 1, 1);
                    } else {
                        window.location.href = 'customer_list.php';
                    }
                }
            <?php else: ?>
                form.reset();
            <?php endif; ?>
        };
        // 用户搜索下拉
        var userData = <?php echo json_encode($users, JSON_UNESCAPED_UNICODE); ?>;

        function bindUserSearch(box) {
            var input = box.querySelector('.module-select-search-input');
            var hidden = box.querySelector('input[type=hidden]');
            var list = box.querySelector('.module-select-search-list');
            var searchInput = list.querySelector('.module-select-search-list-input');
            var itemsDiv = list.querySelector('.module-select-search-list-items');

            function renderList(filter) {
                var html = '';
                var found = false;
                userData.forEach(function(u) {
                    if (!filter || u.real_name.indexOf(filter) !== -1) {
                        html += '<div class="module-select-search-item" data-id="' + u.id + '">' + u.real_name + '</div>';
                        found = true;
                    }
                });
                if (!found) html = '<div class="no-match">无匹配</div>';
                itemsDiv.innerHTML = html;
            }
            input.addEventListener('click', function() {
                renderList('');
                list.style.display = 'block';
                searchInput.value = '';
                searchInput.focus();
            });
            searchInput.addEventListener('input', function() {
                renderList(searchInput.value.trim());
            });
            document.addEventListener('click', function(e) {
                if (!box.contains(e.target)) list.style.display = 'none';
            });
            itemsDiv.addEventListener('mousedown', function(e) {
                var item = e.target.closest('.module-select-search-item');
                if (item) {
                    input.value = item.textContent;
                    hidden.value = item.getAttribute('data-id');
                    list.style.display = 'none';
                }
            });
        }
        document.querySelectorAll('.module-select-search-box').forEach(bindUserSearch);

        // 多选行业下拉
        var industryOptions = <?php echo json_encode($industry_options, JSON_UNESCAPED_UNICODE); ?>;
        var multiBox = document.querySelector('.module-select-search-multi-box');
        if (multiBox) {
            var input = multiBox.querySelector('.module-select-search-multi-input');
            var hidden = multiBox.querySelector('input[type=hidden]');
            var list = multiBox.querySelector('.module-select-search-multi-list');
            var searchInput = list.querySelector('.module-select-search-multi-list-input');
            var opsDiv = list.querySelector('.module-select-search-multi-list-ops');
            var itemsDiv = list.querySelector('.module-select-search-multi-list-items');
            var selected = (hidden.value ? hidden.value.split(',') : []);

            function renderList(filter) {
                var htmlOps = '<div class="multi-ops"><button type="button" class="multi-all">全选</button><button type="button" class="multi-clear">清除</button></div>';
                var htmlItems = '';
                var found = false;
                industryOptions.forEach(function(opt) {
                    if (!filter || opt.indexOf(filter) !== -1) {
                        htmlItems += '<div class="module-select-search-multi-item"><label><input type="checkbox" value="' + opt + '"' + (selected.indexOf(opt) !== -1 ? ' checked' : '') + '> ' + opt + '</label></div>';
                        found = true;
                    }
                });
                if (!found) htmlItems = '<div class="no-match">无匹配</div>';
                opsDiv.innerHTML = htmlOps;
                itemsDiv.innerHTML = htmlItems;
            }
            input.addEventListener('click', function() {
                renderList('');
                list.style.display = 'block';
                searchInput.value = '';
                searchInput.focus();
            });
            searchInput.addEventListener('input', function() {
                renderList(searchInput.value.trim());
            });
            document.addEventListener('click', function(e) {
                if (!multiBox.contains(e.target)) list.style.display = 'none';
            });
            itemsDiv.addEventListener('change', function(e) {
                if (e.target.type === 'checkbox') {
                    var vals = Array.from(itemsDiv.querySelectorAll('input[type=checkbox]:checked')).map(function(cb) {
                        return cb.value;
                    });
                    selected = vals;
                    input.value = vals.join(',');
                    hidden.value = vals.join(',');
                }
            });
            list.addEventListener('click', function(e) {
                if (e.target.classList.contains('multi-all')) {
                    e.stopPropagation();
                    selected = industryOptions.slice();
                    input.value = selected.join(',');
                    hidden.value = selected.join(',');
                    renderList(searchInput.value.trim());
                } else if (e.target.classList.contains('multi-clear')) {
                    e.stopPropagation();
                    selected = [];
                    input.value = '';
                    hidden.value = '';
                    renderList(searchInput.value.trim());
                }
            });
        }
    })();
</script>