<?php
// 版权编辑-基本信息
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
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

if (!isset($_GET['copyright_id']) || intval($_GET['copyright_id']) <= 0) {
    echo '<div class="module-error">未指定版权ID</div>';
    exit;
}
$copyright_id = intval($_GET['copyright_id']);

// 验证版权是否存在
$copyright_stmt = $pdo->prepare("SELECT * FROM copyright_case_info WHERE id = ?");
$copyright_stmt->execute([$copyright_id]);
$copyright_info = $copyright_stmt->fetch();
if (!$copyright_info) {
    echo '<div class="module-error">未找到该版权信息</div>';
    exit;
}

// 处理POST请求（保存数据）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json');

    $data = [
        'case_name' => trim($_POST['case_name'] ?? ''),
        'business_dept_id' => intval($_POST['business_dept_id'] ?? 0),
        'case_type' => '版权', // 固定值
        'client_case_code' => trim($_POST['client_case_code'] ?? ''),
        'client_id' => intval($_POST['client_id'] ?? 0),
        'business_type' => trim($_POST['business_type'] ?? ''),
        'process_item' => trim($_POST['process_item'] ?? ''),
        'case_status' => trim($_POST['case_status'] ?? ''),
        'entrust_date' => trim($_POST['entrust_date'] ?? ''),
        'application_mode' => trim($_POST['application_mode'] ?? ''),
        'business_user_ids' => trim($_POST['business_user_ids'] ?? ''),
        'application_type' => trim($_POST['application_type'] ?? ''),
        'country' => trim($_POST['country'] ?? ''),
        'case_flow' => trim($_POST['case_flow'] ?? ''),
        'start_stage' => trim($_POST['start_stage'] ?? ''),
        'is_subsidy_agent' => intval($_POST['is_subsidy_agent'] ?? 0),
        'is_expedited' => trim($_POST['is_expedited'] ?? ''),
        'open_date' => trim($_POST['open_date'] ?? ''),
        'is_material_available' => intval($_POST['is_material_available'] ?? 0),
        'application_no' => trim($_POST['application_no'] ?? ''),
        'application_date' => trim($_POST['application_date'] ?? ''),
        'registration_no' => trim($_POST['registration_no'] ?? ''),
        'registration_date' => trim($_POST['registration_date'] ?? ''),
        'certificate_no' => trim($_POST['certificate_no'] ?? ''),
        'expire_date' => trim($_POST['expire_date'] ?? ''),
        'source_country' => trim($_POST['source_country'] ?? ''),
        'remarks' => trim($_POST['remarks'] ?? ''),
    ];

    // 修正：所有DATE类型字段为空字符串时转为null
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

    // 修正：所有外键字段为0或小于0时转为null
    $fk_fields = ['business_dept_id', 'client_id'];
    foreach ($fk_fields as $field) {
        if (isset($data[$field]) && $data[$field] <= 0) {
            $data[$field] = null;
        }
    }

    // 服务器端验证必填字段
    $required_fields = [
        'case_name' => '案件名称',
        'business_dept_id' => '承办部门',
        'process_item' => '处理事项',
        'client_id' => '客户名称'
    ];

    $validation_errors = [];
    foreach ($required_fields as $field => $label) {
        if (empty($data[$field])) {
            $validation_errors[] = $label;
        }
    }

    // 特殊验证是否代办资助字段
    if (!isset($_POST['is_subsidy_agent']) || ($_POST['is_subsidy_agent'] !== '0' && $_POST['is_subsidy_agent'] !== '1')) {
        $validation_errors[] = '是否代办资助';
    }

    if (!empty($validation_errors)) {
        echo json_encode(['success' => false, 'msg' => '请填写以下必填项：' . implode('、', $validation_errors)]);
        exit;
    }

    try {
        $set = [];
        foreach ($data as $k => $v) {
            $set[] = "$k = :$k";
        }
        $data['id'] = $copyright_id;
        $sql = "UPDATE copyright_case_info SET " . implode(',', $set) . " WHERE id = :id";
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

// 处理事项 - 根据图片更新
$process_items = [
    '新申请',
    '开卷',
    '软件著作权证明通知书',
    '软件著作权证书',
    '作品登记证书'
];

// 案件状态
$case_statuses = ['未递交', '已递交', '已登记', '届满', '结案'];

// 申请方式
$application_modes = ['电子申请', '纸本申请', '其他'];

// 申请类型 - 根据图片扩展
$application_types = [
    '软件',
    '文字作品',
    '美术作品',
    '摄影作品',
    '音乐作品',
    '影视作品',
    '图形',
    '视听',
    '建筑',
    '汇编作品',
    '口述作品',
    '曲艺作品',
    '舞蹈作品',
    '杂技艺术',
    '电影作品',
    '以类似摄制电影的方法创作的作品',
    '工程设计图产品设计图',
    '地图示意图',
    '模型作品',
    '其他作品',
    '集成电路布图设计'
];

// 国家(地区)
$countries = ['中国', '美国', '日本', '韩国', '德国', '法国', '英国', '其他'];

// 案件流向
$case_flows = ['内-内', '内-外', '外-内', '外-外'];

// 起始阶段
$start_stages = ['无', '新申请', '缴费'];

// 案源国
$source_countries = ['中国', '美国', '日本', '其他'];

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

// 格式化数据为通用下拉框函数所需格式
$departments_options = [];
$users_options = [];
$customers_options = [];
$process_items_options = [];
$business_types_options = [];
$case_statuses_options = [];
$application_modes_options = [];
$application_types_options = [];
$countries_options = [];
$case_flows_options = [];
$start_stages_options = [];
$source_countries_options = [];
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

foreach ($process_items as $item) {
    $process_items_options[$item] = $item;
}

foreach ($business_types as $type) {
    $business_types_options[$type] = $type;
}

foreach ($case_statuses as $status) {
    $case_statuses_options[$status] = $status;
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

foreach ($start_stages as $stage) {
    $start_stages_options[$stage] = $stage;
}

foreach ($source_countries as $country) {
    $source_countries_options[$country] = $country;
}

foreach ($expedited_levels as $level) {
    $expedited_levels_options[$level] = $level;
}

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

render_select_search_assets();
?>

<!-- <div class="module-panel"> -->
<div class="module-btns">
    <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
    <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
</div>

<form id="edit-copyright-form" class="module-form" autocomplete="off">
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
            <td><input type="text" name="case_code" class="module-input" value="<?= h($copyright_info['case_code']) ?>" readonly></td>
            <td class="module-label module-req">*案件名称</td>
            <td><input type="text" name="case_name" class="module-input" value="<?= h($copyright_info['case_name']) ?>" required></td>
            <td class="module-label">客户文号</td>
            <td><input type="text" name="client_case_code" class="module-input" value="<?= h($copyright_info['client_case_code']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label module-req">*承办部门</td>
            <td>
                <?php render_select_search('business_dept_id', $departments_options, $copyright_info['business_dept_id']); ?>
            </td>
            <td class="module-label">案件类型</td>
            <td>
                <input type="text" name="case_type" class="module-input" value="<?= h($case_type_fixed) ?>" readonly>
            </td>
            <td class="module-label">业务类型</td>
            <td>
                <?php render_select_search('business_type', $business_types_options, $copyright_info['business_type']); ?>
            </td>
        </tr>
        <tr>
            <td class="module-label module-req">*客户名称</td>
            <td>
                <?php render_select_search('client_id', $customers_options, $copyright_info['client_id']); ?>
            </td>
            <td class="module-label module-req">*处理事项</td>
            <td>
                <?php render_select_search('process_item', $process_items_options, $copyright_info['process_item']); ?>
            </td>
            <td class="module-label">案件状态</td>
            <td>
                <?php echo render_select('case_status', $case_statuses, $copyright_info['case_status']); ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">委案日期</td>
            <td><input type="date" name="entrust_date" class="module-input" value="<?= h($copyright_info['entrust_date']) ?>"></td>
            <td class="module-label">起始阶段</td>
            <td>
                <?php echo render_select('start_stage', $start_stages, $copyright_info['start_stage']); ?>
            </td>
            <td class="module-label">是否代办资助</td>
            <td>
                <label><input type="radio" name="is_subsidy_agent" value="1" <?= $copyright_info['is_subsidy_agent'] ? 'checked' : '' ?>>是</label>
                <label><input type="radio" name="is_subsidy_agent" value="0" <?= !$copyright_info['is_subsidy_agent'] ? 'checked' : '' ?>>否</label>
            </td>
        </tr>
        <tr>
            <td class="module-label">加快</td>
            <td>
                <?php echo render_select('is_expedited', $expedited_levels, $copyright_info['is_expedited']); ?>
            </td>
            <td class="module-label">有无材料</td>
            <td>
                <label><input type="radio" name="is_material_available" value="1" <?= $copyright_info['is_material_available'] ? 'checked' : '' ?>>有</label>
                <label><input type="radio" name="is_material_available" value="0" <?= !$copyright_info['is_material_available'] ? 'checked' : '' ?>>无</label>
            </td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td class="module-label">受理号</td>
            <td><input type="text" name="application_no" class="module-input" value="<?= h($copyright_info['application_no']) ?>"></td>
            <td class="module-label">受理日</td>
            <td><input type="date" name="application_date" class="module-input" value="<?= h($copyright_info['application_date']) ?>"></td>
            <td class="module-label">登记号</td>
            <td><input type="text" name="registration_no" class="module-input" value="<?= h($copyright_info['registration_no']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">登记日</td>
            <td><input type="date" name="registration_date" class="module-input" value="<?= h($copyright_info['registration_date']) ?>"></td>
            <td class="module-label">证书号</td>
            <td><input type="text" name="certificate_no" class="module-input" value="<?= h($copyright_info['certificate_no']) ?>"></td>
            <td class="module-label">届满日</td>
            <td><input type="date" name="expire_date" class="module-input" value="<?= h($copyright_info['expire_date']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">案件备注</td>
            <td colspan="5"><textarea name="remarks" class="module-textarea" rows="3" style="width:100%;"><?= h($copyright_info['remarks']) ?></textarea></td>
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
                    <?php render_select_search_multi('business_user_ids', $users_options, $copyright_info['business_user_ids']); ?>
                </td>
                <td class="module-label">申请方式</td>
                <td>
                    <?php echo render_select('application_mode', $application_modes, $copyright_info['application_mode']); ?>
                </td>
                <td class="module-label">申请类型</td>
                <td>
                    <?php echo render_select('application_type', $application_types, $copyright_info['application_type']); ?>
                </td>
            </tr>
            <tr>
                <td class="module-label">国家(地区)</td>
                <td>
                    <?php echo render_select('country', $countries, $copyright_info['country']); ?>
                </td>
                <td class="module-label">案件流向</td>
                <td>
                    <?php echo render_select('case_flow', $case_flows, $copyright_info['case_flow']); ?>
                </td>
                <td class="module-label">案源国</td>
                <td>
                    <?php echo render_select('source_country', $source_countries, $copyright_info['source_country']); ?>
                </td>
            </tr>
            <tr>
                <td class="module-label">开卷日</td>
                <td><input type="date" name="open_date" class="module-input" value="<?= h($copyright_info['open_date']) ?>"></td>
                <td colspan="4"></td>
            </tr>
        </table>
    </div>
</form>
<!-- </div> -->

<script>
    window.initCopyrightTabEvents = function() {
        var form = document.getElementById('edit-copyright-form'),
            btnSave = document.querySelector('#copyright-tab-content .btn-save'),
            btnCancel = document.querySelector('#copyright-tab-content .btn-cancel');

        // 展开/隐藏高级字段功能
        var toggleBtn = document.getElementById('toggle-advanced-fields');
        var advancedFields = document.getElementById('advanced-fields');
        var toggleIcon = toggleBtn.querySelector('.icon-down');
        var toggleText = toggleBtn.querySelector('.toggle-text');

        if (toggleBtn) {
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
        }

        // 保存按钮AJAX提交
        if (btnSave) {
            btnSave.onclick = function() {
                // 完整的必填字段验证
                var requiredFields = [{
                        name: 'case_name',
                        label: '案件名称',
                        type: 'input'
                    },
                    {
                        name: 'business_dept_id',
                        label: '承办部门',
                        type: 'select'
                    },
                    {
                        name: 'process_item',
                        label: '处理事项',
                        type: 'select'
                    },
                    {
                        name: 'client_id',
                        label: '客户名称',
                        type: 'select'
                    },

                    {
                        name: 'is_subsidy_agent',
                        label: '是否代办资助',
                        type: 'radio'
                    }
                ];

                var missingFields = [];
                var firstErrorField = null;

                for (var i = 0; i < requiredFields.length; i++) {
                    var field = requiredFields[i];
                    var isValid = false;

                    if (field.type === 'input' || field.type === 'select') {
                        var el = form.querySelector('[name="' + field.name + '"]');
                        if (el && el.value && el.value.trim()) {
                            isValid = true;
                        }
                        if (!isValid && !firstErrorField) {
                            firstErrorField = el;
                        }
                    } else if (field.type === 'radio') {
                        var radioEls = form.querySelectorAll('[name="' + field.name + '"]');
                        for (var j = 0; j < radioEls.length; j++) {
                            if (radioEls[j].checked) {
                                isValid = true;
                                break;
                            }
                        }
                        if (!isValid && !firstErrorField) {
                            firstErrorField = radioEls[0];
                        }
                    }

                    if (!isValid) {
                        missingFields.push(field.label);
                    }
                }

                if (missingFields.length > 0) {
                    alert('请填写以下必填项：' + missingFields.join('、'));
                    if (firstErrorField) {
                        firstErrorField.focus();
                    }
                    return;
                }
                var fd = new FormData(form);
                fd.append('action', 'save');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/copyright_management/edit_tabs/basic.php?copyright_id=<?= $copyright_id ?>', true);
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
                    xhr.open('GET', 'modules/copyright_management/edit_tabs/basic.php?copyright_id=<?= $copyright_id ?>', true);
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            var tabContent = document.querySelector('#copyright-tab-content');
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
                                    if (typeof window.initCopyrightTabEvents === 'function') {
                                        window.initCopyrightTabEvents();
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
        document.addEventListener('DOMContentLoaded', window.initCopyrightTabEvents);
    } else {
        window.initCopyrightTabEvents();
    }
</script>