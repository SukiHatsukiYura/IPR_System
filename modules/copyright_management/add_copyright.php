<?php
session_start();
include_once(__DIR__ . '/../../database.php');
include_once(__DIR__ . '/../../common/functions.php'); // 引入通用函数库
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
// 案件类型 - 固定为"版权"
$case_type_fixed = '版权';

// 业务类型 - 根据图片更新
$business_types = [
    '版权登记',
    '软件登记',
    '双软',
    '软件测评',
    '海关备案',
    '著作权取证登记'
];

// 案件状态
$case_statuses = ['未递交', '已递交', '已登记', '届满', '结案'];

// 处理事项 - 根据图片更新
$process_items = [
    '新申请',
    '开卷',
    '软件著作权证明通知书',
    '软件著作权证书',
    '作品登记证书'
];

// 申请方式
$application_modes = ['电子申请', '纸本申请', '其他'];

// 申请类型 - 根据图片更新，包含更多详细选项
$application_types = [
    '软件',
    '文字作品',
    '美术作品',
    '摄影作品',
    '图形',
    '视听',
    '建筑',
    '汇编作品',
    '口述作品',
    '曲艺作品',
    '舞蹈作品',
    '杂技艺术',
    '电影作品',
    '电影作品',
    '以类似摄制电影的方法创作的作品',
    '工程设计图、产品设计图',
    '地图、示意图',
    '模型作品',
    '其他作品',
    '集成电路布图设计'
];

// 国家(地区)
$countries = ['中国', '美国', '日本', '韩国', '德国', '法国', '英国', '其他'];

// 案件流向
$case_flows = ['内-内', '内-外', '外-内', '外-外'];

// 案源国
$source_countries = ['中国', '美国', '日本', '其他'];

// 起始阶段
$start_stages = ['无', '新申请', '缴费'];

// 加快级别 - 根据图片中的"加快"选项更新
$expedited_levels = [
    '无',
    '大厅',
    '1个工作日',
    '2个工作日',
    '3个工作日',
    '4个工作日',
    '5个工作日',
    '6-10个工作日',
    '11-15个工作日',
    '16-20个工作日',
    '21-25个工作日',
    '26-30个工作日',
    '31-35个工作日',
    '36-40个工作日',
    '35个工作日',
    '90个工作日'
];

// 查询动态数据
$departments = $pdo->query("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();
$users = $pdo->query("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name")->fetchAll();
$customers = $pdo->query("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn")->fetchAll();

// 格式化数据为通用下拉框函数所需格式
$departments_options = [];
$users_options = [];
$customers_options = [];
$business_types_options = [];
$case_statuses_options = [];
$process_items_options = [];
$application_modes_options = [];
$application_types_options = [];
$countries_options = [];
$case_flows_options = [];
$source_countries_options = [];
$start_stages_options = [];
$expedited_levels_options = [];

foreach ($departments as $dept) {
    $departments_options[$dept['id']] = $dept['dept_name'];
}

foreach ($users as $user) {
    $users_options[$user['id']] = $user['real_name'];
}

foreach ($customers as $customer) {
    $customers_options[$customer['id']] = $customer['customer_name_cn'];
}

foreach ($business_types as $type) {
    $business_types_options[$type] = $type;
}

foreach ($case_statuses as $status) {
    $case_statuses_options[$status] = $status;
}

foreach ($process_items as $item) {
    $process_items_options[$item] = $item;
}

foreach ($application_modes as $mode) {
    $application_modes_options[$mode] = $mode;
}

foreach ($application_types as $type) {
    $application_types_options[$type] = $type;
}

foreach ($countries as $country) {
    $countries_options[$country] = $country;
}

foreach ($case_flows as $flow) {
    $case_flows_options[$flow] = $flow;
}

foreach ($source_countries as $country) {
    $source_countries_options[$country] = $country;
}

foreach ($start_stages as $stage) {
    $start_stages_options[$stage] = $stage;
}

foreach ($expedited_levels as $level) {
    $expedited_levels_options[$level] = $level;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json');

    // 自动生成唯一文号
    function generate_case_code($pdo)
    {
        $prefix = 'CR' . date('Ymd');
        $sql = "SELECT COUNT(*) FROM copyright_case_info WHERE case_code LIKE :prefix";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);
        $count = $stmt->fetchColumn();
        $serial = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        return $prefix . $serial;
    }

    $data = [
        'case_name' => trim($_POST['case_name'] ?? ''),
        'case_type' => $case_type_fixed, // 固定为"版权"
        'client_case_code' => trim($_POST['client_case_code'] ?? ''),
        'client_id' => intval($_POST['client_id'] ?? 0),
        'business_type' => trim($_POST['business_type'] ?? ''),
        'process_item' => trim($_POST['process_item'] ?? ''),
        'case_status' => trim($_POST['case_status'] ?? ''),
        'entrust_date' => trim($_POST['entrust_date'] ?? ''),
        'business_dept_id' => intval($_POST['business_dept_id'] ?? 0),
        'business_user_ids' => trim($_POST['business_user_ids'] ?? ''),
        'application_mode' => trim($_POST['application_mode'] ?? ''),
        'application_type' => trim($_POST['application_type'] ?? ''),
        'country' => trim($_POST['country'] ?? ''),
        'case_flow' => trim($_POST['case_flow'] ?? ''),
        'source_country' => trim($_POST['source_country'] ?? ''),
        'is_expedited' => trim($_POST['is_expedited'] ?? ''),
        'open_date' => trim($_POST['open_date'] ?? ''),
        'application_no' => trim($_POST['application_no'] ?? ''),
        'application_date' => trim($_POST['application_date'] ?? ''),
        'registration_no' => trim($_POST['registration_no'] ?? ''),
        'registration_date' => trim($_POST['registration_date'] ?? ''),
        'certificate_no' => trim($_POST['certificate_no'] ?? ''),
        'expire_date' => trim($_POST['expire_date'] ?? ''),
        'start_stage' => trim($_POST['start_stage'] ?? ''),
        'is_subsidy_agent' => intval($_POST['is_subsidy_agent'] ?? 0),
        'is_material_available' => intval($_POST['is_material_available'] ?? 0),
        'remarks' => trim($_POST['remarks'] ?? ''),
    ];

    // 修正：所有DATE类型字段为空字符串时转为null，避免MySQL日期类型报错
    $date_fields = [
        'entrust_date',
        'open_date',
        'application_date',
        'registration_date',
        'expire_date'
    ];
    foreach ($date_fields as $field) {
        if (isset($data[$field]) && $data[$field] === '') {
            $data[$field] = null;
        }
    }

    // 修正：所有外键字段为0或小于0时转为null，避免外键约束报错
    $fk_fields = ['business_dept_id', 'client_id'];
    foreach ($fk_fields as $field) {
        if (isset($data[$field]) && $data[$field] <= 0) {
            $data[$field] = null;
        }
    }

    if ($data['case_name'] === '' || $data['business_dept_id'] <= 0 || $data['client_id'] <= 0) {
        echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
        exit;
    }

    try {
        // 新增模式 - 自动生成唯一文号并执行INSERT操作
        $data['case_code'] = generate_case_code($pdo);

        $fields = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO copyright_case_info ($fields) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        foreach ($data as $k => $v) {
            $stmt->bindValue(":$k", $v);
        }
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok]);
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
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>新增版权</title>
    <link rel="stylesheet" href="../../css/module.css">
    <?php render_select_search_assets(); ?>
</head>

<body>
    <div class="module-panel">
        <div class="module-btns">
            <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
            <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
        </div>
        <h3 style="text-align:center;margin-bottom:15px;">新增版权</h3>
        <form id="add-copyright-form" class="module-form" autocomplete="off">
            <table class="module-table" style="width:100%;max-width:1800px;table-layout:fixed;">
                <colgroup>
                    <col style="width:120px;">
                    <col style="width:220px;">
                    <col style="width:120px;">
                    <col style="width:220px;">
                    <col style="width:120px;">
                    <col style="width:220px;">
                </colgroup>
                <tr>
                    <td class="module-label">我方文号</td>
                    <td><input type="text" name="case_code" class="module-input" value="系统自动生成" readonly></td>
                    <td class="module-label module-req">*案件名称</td>
                    <td><input type="text" name="case_name" class="module-input" value="" required></td>
                    <td class="module-label">客户文号</td>
                    <td><input type="text" name="client_case_code" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*承办部门</td>
                    <td>
                        <?php render_select_search('business_dept_id', $departments_options, ''); ?>
                    </td>
                    <td class="module-label">案件类型</td>
                    <td>
                        <input type="text" name="case_type" class="module-input" value="<?= h($case_type_fixed) ?>" readonly>
                    </td>
                    <td class="module-label">业务类型</td>
                    <td>
                        <?php render_select_search('business_type', $business_types_options, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label module-req">*客户名称</td>
                    <td>
                        <?php render_select_search('client_id', $customers_options, ''); ?>
                    </td>
                    <td class="module-label module-req">*处理事项</td>
                    <td>
                        <?php render_select_search('process_item', $process_items_options, ''); ?>
                    </td>
                    <td class="module-label">案件状态</td>
                    <td>
                        <?php echo render_select('case_status', $case_statuses, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">委案日期</td>
                    <td><input type="date" name="entrust_date" class="module-input" value=""></td>
                    <td class="module-label">起始阶段</td>
                    <td>
                        <?php echo render_select('start_stage', $start_stages, ''); ?>
                    </td>
                    <td class="module-label">是否代办资助</td>
                    <td>
                        <label><input type="radio" name="is_subsidy_agent" value="1">是</label>
                        <label><input type="radio" name="is_subsidy_agent" value="0" checked>否</label>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">加快</td>
                    <td>
                        <?php echo render_select('is_expedited', $expedited_levels, ''); ?>
                    </td>
                    <td class="module-label">有无材料</td>
                    <td>
                        <label><input type="radio" name="is_material_available" value="1">有</label>
                        <label><input type="radio" name="is_material_available" value="0" checked>无</label>
                    </td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td class="module-label">受理号</td>
                    <td><input type="text" name="application_no" class="module-input" value=""></td>
                    <td class="module-label">受理日</td>
                    <td><input type="date" name="application_date" class="module-input" value=""></td>
                    <td class="module-label">登记号</td>
                    <td><input type="text" name="registration_no" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label">登记日</td>
                    <td><input type="date" name="registration_date" class="module-input" value=""></td>
                    <td class="module-label">证书号</td>
                    <td><input type="text" name="certificate_no" class="module-input" value=""></td>
                    <td class="module-label">届满日</td>
                    <td><input type="date" name="expire_date" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label">案件备注</td>
                    <td colspan="5"><textarea name="remarks" class="module-textarea" rows="3" style="width:100%;"></textarea></td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align:center;padding:15px;">
                        <button type="button" id="toggle-advanced-fields" class="btn-mini" style="background:#f0f0f0;border:1px solid #ddd;padding:8px 16px;cursor:pointer;">
                            <i class="icon-down" style="margin-right:5px;">▼</i>
                            <span class="toggle-text">展开</span>
                        </button>
                    </td>
                </tr>
            </table>

            <!-- 可展开/隐藏的高级字段区域 -->
            <div id="advanced-fields" style="display:none;margin-top:15px;">
                <!-- <h4 style="margin-bottom:10px;color:#666;border-bottom:1px solid #eee;padding-bottom:5px;">高级选项</h4> -->
                <table class="module-table" style="width:100%;max-width:1800px;table-layout:fixed;">
                    <colgroup>
                        <col style="width:120px;">
                        <col style="width:220px;">
                        <col style="width:120px;">
                        <col style="width:220px;">
                        <col style="width:120px;">
                        <col style="width:220px;">
                    </colgroup>
                    <tr>
                        <td class="module-label">业务人员</td>
                        <td>
                            <?php render_select_search_multi('business_user_ids', $users_options, ''); ?>
                        </td>
                        <td class="module-label">申请方式</td>
                        <td>
                            <?php echo render_select('application_mode', $application_modes, ''); ?>
                        </td>
                        <td class="module-label">申请类型</td>
                        <td>
                            <?php echo render_select('application_type', $application_types, ''); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">国家(地区)</td>
                        <td>
                            <?php echo render_select('country', $countries, ''); ?>
                        </td>
                        <td class="module-label">案件流向</td>
                        <td>
                            <?php echo render_select('case_flow', $case_flows, ''); ?>
                        </td>
                        <td class="module-label">案源国</td>
                        <td>
                            <?php echo render_select('source_country', $source_countries, '中国'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">开卷日</td>
                        <td><input type="date" name="open_date" class="module-input" value=""></td>
                        <td colspan="4"></td>
                    </tr>
                </table>
            </div>
        </form>
    </div>
    <script>
        (function() {
            var form = document.getElementById('add-copyright-form'),
                btnSave = document.querySelector('.btn-save'),
                btnCancel = document.querySelector('.btn-cancel');

            // 保存按钮AJAX提交
            btnSave.onclick = function() {
                var required = ['case_name', 'business_dept_id', 'client_id', 'process_item'];
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
                xhr.open('POST', 'modules/copyright_management/add_copyright.php', true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                alert('保存成功');
                                form.reset();
                                // 重置所有下拉搜索框
                                document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
                                document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
                                document.querySelectorAll('.module-select-search-multi-input').forEach(i => i.value = '');
                                document.querySelectorAll('.module-select-search-multi-box input[type=hidden]').forEach(i => i.value = '');
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

            // 取消按钮
            btnCancel.onclick = function() {
                if (confirm('确定要取消吗？未保存的内容将丢失')) {
                    form.reset();
                    // 重置所有下拉搜索框
                    document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
                    document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
                    document.querySelectorAll('.module-select-search-multi-input').forEach(i => i.value = '');
                    document.querySelectorAll('.module-select-search-multi-box input[type=hidden]').forEach(i => i.value = '');
                }
            };

            // 展开/隐藏高级字段功能
            var toggleBtn = document.getElementById('toggle-advanced-fields');
            var advancedFields = document.getElementById('advanced-fields');
            var toggleIcon = toggleBtn.querySelector('.icon-down');
            var toggleText = toggleBtn.querySelector('.toggle-text');

            toggleBtn.onclick = function() {
                if (advancedFields.style.display === 'none') {
                    // 展开
                    advancedFields.style.display = 'block';
                    toggleIcon.textContent = '▲';
                    toggleText.textContent = '隐藏';
                } else {
                    // 隐藏
                    advancedFields.style.display = 'none';
                    toggleIcon.textContent = '▼';
                    toggleText.textContent = '展开';
                }
            };
        })();
    </script>
</body>

</html>