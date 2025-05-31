<?php
// 专利编辑-基本信息tab
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();

if (!isset($_GET['patent_id']) || intval($_GET['patent_id']) <= 0) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未指定专利ID</div>';
    exit;
}
$patent_id = intval($_GET['patent_id']);

// 查询专利信息
$patent_stmt = $pdo->prepare("SELECT * FROM patent_case_info WHERE id = ?");
$patent_stmt->execute([$patent_id]);
$patent = $patent_stmt->fetch();
if (!$patent) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未找到该专利信息</div>';
    exit;
}

// 静态下拉选项
$business_types = ['无效案件', '普通新申请', '专利转让', '著泉项目变更', 'PCT国际阶段', '复审', '香港登记案', '申请香港', '临时申请', '公众意见', '翻译', '专利检索案件', '代缴年费案件', '诉讼案件', '顾问', '专利许可备案', '海关备案', '其他', 'PCT国家阶段', '办理副本案件'];
$case_statuses = ['请选择', '未递交', '已递交', '暂缓申请', '受理', '初审合格', '初审', '公开', '实审', '补正', '审查', '一通', '二通', '三通', '四通', '五通', '六通', '七通', '八通', '一补', '九通', '二补', '三补', '视为撤回', '主动撤回', '驳回', '复审', '无效', '视为放弃', '主动放弃', '授权', '待领证', '维持', '终止', '结案', '届满', 'PCT国际检索', '中止', '保全', '诉讼', '办理登记手续', '复审受理', 'Advisory Action', 'Appeal', 'Election Action', 'Final Action', 'Non Final Action', 'Petition', 'RCE', '公告', '视为未提出'];
$process_items = ['请求优先审查', '开卷', '放弃', '更正', '无效答辩', '不予受理', '官文转达', '缴年费', '民事诉讼上诉', '主动补正', '专利权评价报告', '驳回', '取得检索报告', '请求无效', '翻译', '审查高速公路', '资助监控', '赔偿请求', '请求检索报告', '许可备案', '诉讼', '取得副本', '请求加速审查', '民事诉讼答辩', '取得申请号', '请求中止', '办理登记手续', '复审决定', '避免重复授权', '民事诉讼上诉答辩', '确认通知书', '请求保密审查', '结案', '补正', '请求恢复权利', '视为未提出', '手续补正', '取得证书', '年费滞纳金', '复审意见陈述', '提交IDS', '复审受理', '请求延长期限', '撤回', '请求提前公开', '处理审查意见', '口审', '诉讼举证', '项目申报', '办理DAS', '行政诉讼上诉答辩', '请求复审', '无效行政诉讼答辩', '请求退款', '提出行政诉讼', '缴费', '终止', '无效诉讼', '公众意见', '保密决定', '变更代理人申请补正通知', '请求实审', '提出民事诉讼', '请求副本', '新申请', '复议申请', '无效请求补充意见', '著泉项目变更', '行政诉讼上诉', '请求费用减缓', '视为未要求'];
$application_types = ['请选择', '发明', '实用新型', '外观设计', '临时申请', '再公告', '植物', '集成电路布图设计', '年费', '无效', '其他'];
$application_modes = ['电子申请(事务所)', '纸件申请', '其他'];
$countries = ['中国', '美国', '日本', '韩国', '德国', '法国', '英国', '其他'];
$case_flows = ['内-内', '内-外', '外-内', '外-外'];
$start_stages = ['无', '新申请', '答辩', '缴费'];
$client_statuses = ['请选择', '放弃指示', '新申请指示递交', '补充申请信息资料', '修改意见', '著录项目变更指示', 'OA指示递交', '结案指示'];
$source_countries = ['中国', '美国', '日本', '其他'];
$other_options = ['同步提交', '提前公布', '请求保密审查', '预审案件', '优先审查', '同时请求DAS码', '请求提前公开', '请求费用减缓'];
$departments = $pdo->query("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();
$users = $pdo->query("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name")->fetchAll();
$customers = $pdo->query("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn")->fetchAll();

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
function renderUserSearch($name, $data, $multi = false, $post_val = '')
{
    $val = $post_val;
    $display = '';
    $is_assoc = false;
    if (!empty($data) && is_array($data[0])) {
        $is_assoc = true;
    }
    if ($multi) {
        $selected = $val ? explode(',', $val) : [];
        $names = [];
        foreach ($data as $d) {
            if ($is_assoc) {
                $id = $d['id'];
                $label = isset($d['real_name']) ? $d['real_name'] : (isset($d['dept_name']) ? $d['dept_name'] : (isset($d['customer_name_cn']) ? $d['customer_name_cn'] : $id));
            } else {
                $id = $d;
                $label = $d;
            }
            if (in_array($id, $selected)) {
                $names[] = $label;
            }
        }
        $display = implode(',', $names);
    } else {
        foreach ($data as $d) {
            if ($is_assoc) {
                $id = $d['id'];
                $label = isset($d['real_name']) ? $d['real_name'] : (isset($d['dept_name']) ? $d['dept_name'] : (isset($d['customer_name_cn']) ? $d['customer_name_cn'] : $id));
            } else {
                $id = $d;
                $label = $d;
            }
            if ($id == $val) {
                $display = $label;
                break;
            }
        }
    }
    $class = $multi ? 'module-select-search-multi' : 'module-select-search';
    $html = '<div class="' . $class . '-box">';
    $html .= '<input type="text" class="module-input ' . $class . '-input" name="' . $name . '_display" value="' . htmlspecialchars($display) . '" readonly placeholder="点击选择">';
    $html .= '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($val) . '">';
    $html .= '<div class="' . $class . '-list" style="display:none;">';
    if ($multi) {
        $html .= '<div class="multi-ops">';
        $html .= '<button type="button" class="btn-select-all">全选</button>';
        $html .= '<button type="button" class="btn-clear">清除</button>';
        $html .= '</div>';
    }
    $html .= '<input type="text" class="' . $class . '-list-input" placeholder="搜索">';
    $html .= '<div class="' . $class . '-list-items"></div>';
    $html .= '</div>';
    $html .= '</div>';
    // 省略JS，实际页面已包含
    return $html;
}

// 保存逻辑：仅处理本tab字段
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) && $_POST['action'] === 'save'
) {
    header('Content-Type: application/json');
    $patent_id = intval($_GET['patent_id'] ?? 0);
    if ($patent_id <= 0) {
        echo json_encode(['success' => false, 'msg' => '缺少专利ID']);
        exit;
    }
    // 只收集本tab字段
    $data = [
        'case_name' => trim($_POST['case_name'] ?? ''),
        'case_name_en' => trim($_POST['case_name_en'] ?? ''),
        'business_dept_id' => intval($_POST['business_dept_id'] ?? 0),
        'open_date' => trim($_POST['open_date'] ?? ''),
        'client_case_code' => trim($_POST['client_case_code'] ?? ''),
        'process_item' => trim($_POST['process_item'] ?? ''),
        'client_id' => intval($_POST['client_id'] ?? 0),
        'business_type' => trim($_POST['business_type'] ?? ''),
        'entrust_date' => trim($_POST['entrust_date'] ?? ''),
        'case_status' => trim($_POST['case_status'] ?? ''),
        'same_day_apply' => trim($_POST['same_day_apply'] ?? ''),
        'same_day_submit' => trim($_POST['same_day_submit'] ?? ''),
        'agent_rule' => trim($_POST['agent_rule'] ?? ''),
        'remarks' => trim($_POST['remarks'] ?? ''),
        'application_no' => trim($_POST['application_no'] ?? ''),
        'application_date' => trim($_POST['application_date'] ?? ''),
        'publication_no' => trim($_POST['publication_no'] ?? ''),
        'publication_date' => trim($_POST['publication_date'] ?? ''),
        'handler_id' => intval($_POST['handler_id'] ?? 0),
        'announcement_no' => trim($_POST['announcement_no'] ?? ''),
        'announcement_date' => trim($_POST['announcement_date'] ?? ''),
        'certificate_no' => trim($_POST['certificate_no'] ?? ''),
        'expire_date' => trim($_POST['expire_date'] ?? ''),
        'enter_substantive_date' => trim($_POST['enter_substantive_date'] ?? ''),
        'application_mode' => trim($_POST['application_mode'] ?? ''),
        'business_user_ids' => trim($_POST['business_user_ids'] ?? ''),
        'business_assistant_ids' => trim($_POST['business_assistant_ids'] ?? ''),
        'application_type' => trim($_POST['application_type'] ?? ''),
        'is_allocated' => intval($_POST['is_allocated'] ?? 1),
        'country' => trim($_POST['country'] ?? ''),
        'case_flow' => trim($_POST['case_flow'] ?? ''),
        'start_stage' => trim($_POST['start_stage'] ?? ''),
        'client_status' => trim($_POST['client_status'] ?? ''),
        'source_country' => trim($_POST['source_country'] ?? ''),
        'other_options' => is_array($_POST['other_options'] ?? null) ? implode(',', $_POST['other_options']) : trim($_POST['other_options'] ?? ''),
    ];
    // 必填校验
    if ($data['case_name'] === '' || $data['business_dept_id'] <= 0 || $data['process_item'] === '' || $data['client_id'] <= 0 || $data['application_type'] === '' || !isset($_POST['is_allocated'])) {
        echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
        exit;
    }
    // 日期字段处理
    foreach (
        [
            'open_date',
            'entrust_date',
            'application_date',
            'publication_date',
            'announcement_date',
            'expire_date',
            'enter_substantive_date'
        ] as $field
    ) {
        if ($data[$field] === '') $data[$field] = null;
    }
    // 外键处理
    foreach (['handler_id', 'business_dept_id', 'client_id'] as $field) {
        if ($data[$field] <= 0) $data[$field] = null;
    }
    // 构造SQL
    $fields = [];
    foreach ($data as $k => $v) {
        $fields[] = "$k = :$k";
    }
    $sql = "UPDATE patent_case_info SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    foreach ($data as $k => $v) {
        $stmt->bindValue(":$k", $v);
    }
    $stmt->bindValue(":id", $patent_id);
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok]);
    exit;
}
?>
<form id="edit-patent-basic-form" class="module-form" autocomplete="off">
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
            <td><input type="text" name="case_code" class="module-input" value="<?= h($patent['case_code']) ?>" readonly></td>
            <td class="module-label module-req">*承办部门</td>
            <td>
                <?= renderUserSearch('business_dept_id', $departments, false, $patent['business_dept_id']) ?>
            </td>
            <td class="module-label">开卷日期</td>
            <td><input type="date" name="open_date" class="module-input" value="<?= h($patent['open_date']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">客户文号</td>
            <td><input type="text" name="client_case_code" class="module-input" value="<?= h($patent['client_case_code']) ?>"></td>
            <td class="module-label module-req">*案件名称</td>
            <td><input type="text" name="case_name" class="module-input" value="<?= h($patent['case_name']) ?>" required></td>
            <td class="module-label">英文名称</td>
            <td><input type="text" name="case_name_en" class="module-input" value="<?= h($patent['case_name_en']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label module-req">*处理事项</td>
            <td>
                <?= renderUserSearch('process_item', $process_items, false, $patent['process_item']) ?>
            </td>
            <td class="module-label module-req">*客户名称</td>
            <td>
                <?= renderUserSearch('client_id', $customers, false, $patent['client_id']) ?>
            </td>
            <td class="module-label">业务类型</td>
            <td>
                <?= renderUserSearch('business_type', $business_types, false, $patent['business_type']) ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">委案日期</td>
            <td><input type="date" name="entrust_date" class="module-input" value="<?= h($patent['entrust_date']) ?>"></td>
            <td class="module-label">案件状态</td>
            <td>
                <?= render_select('case_status', $case_statuses, $patent['case_status']) ?>
            </td>
            <td class="module-label">同日申请</td>
            <td><input type="text" name="same_day_apply" class="module-input" value="<?= h($patent['same_day_apply']) ?>" placeholder="逗号分隔"></td>
        </tr>
        <tr>
            <td class="module-label">同日递交</td>
            <td><input type="text" name="same_day_submit" class="module-input" value="<?= h($patent['same_day_submit']) ?>" placeholder="逗号分隔"></td>
            <td class="module-label">代理费规则</td>
            <td colspan="3">
                <label><input type="radio" name="agent_rule" value="自定义" <?= $patent['agent_rule'] === '自定义' ? 'checked' : '' ?>>自定义</label>
                <label><input type="radio" name="agent_rule" value="纯包" <?= $patent['agent_rule'] === '纯包' ? 'checked' : '' ?>>纯包</label>
                <label><input type="radio" name="agent_rule" value="按项" <?= $patent['agent_rule'] === '按项' ? 'checked' : '' ?>>按项</label>
            </td>
        </tr>
        <tr>
            <td class="module-label">业务人员</td>
            <td colspan="5">
                <?= renderUserSearch('business_user_ids', $users, true, $patent['business_user_ids']) ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">业务助理</td>
            <td colspan="5">
                <?= renderUserSearch('business_assistant_ids', $users, true, $patent['business_assistant_ids']) ?>
            </td>
        </tr>
        <tr>
            <td class="module-label module-req">*申请类型</td>
            <td>
                <?= render_select('application_type', $application_types, $patent['application_type']) ?>
            </td>
            <td class="module-label module-req">*是否配案</td>
            <td>
                <label><input type="radio" name="is_allocated" value="1" <?= $patent['is_allocated'] == 1 ? 'checked' : '' ?>>是</label>
                <label><input type="radio" name="is_allocated" value="0" <?= $patent['is_allocated'] == 0 ? 'checked' : '' ?>>否</label>
            </td>
            <td class="module-label">国家(地区)</td>
            <td>
                <?= render_select('country', $countries, $patent['country']) ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">案件流向</td>
            <td>
                <?= render_select('case_flow', $case_flows, $patent['case_flow']) ?>
            </td>
            <td class="module-label">起始阶段</td>
            <td>
                <?= render_select('start_stage', $start_stages, $patent['start_stage']) ?>
            </td>
            <td class="module-label">客户状态</td>
            <td>
                <?= render_select('client_status', $client_statuses, $patent['client_status']) ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">案源国</td>
            <td>
                <?= render_select('source_country', $source_countries, $patent['source_country']) ?>
            </td>
            <td class="module-label">其他</td>
            <td colspan="3">
                <?php foreach ($other_options as $v): ?>
                    <label style="margin-right:12px;"><input type="checkbox" name="other_options[]" value="<?= h($v) ?>" <?= strpos("," . $patent['other_options'] . ",", "," . h($v) . ",") !== false ? 'checked' : '' ?>> <?= h($v) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">申请号</td>
            <td><input type="text" name="application_no" class="module-input" value="<?= h($patent['application_no']) ?>"></td>
            <td class="module-label">申请日</td>
            <td><input type="date" name="application_date" class="module-input" value="<?= h($patent['application_date']) ?>"></td>
            <td class="module-label">公开号</td>
            <td><input type="text" name="publication_no" class="module-input" value="<?= h($patent['publication_no']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">公开日</td>
            <td><input type="date" name="publication_date" class="module-input" value="<?= h($patent['publication_date']) ?>"></td>
            <td class="module-label">处理人</td>
            <td>
                <?= renderUserSearch('handler_id', $users, false, $patent['handler_id']) ?>
            </td>
            <td class="module-label">公告号</td>
            <td><input type="text" name="announcement_no" class="module-input" value="<?= h($patent['announcement_no']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">公告日</td>
            <td><input type="date" name="announcement_date" class="module-input" value="<?= h($patent['announcement_date']) ?>"></td>
            <td class="module-label">证书号</td>
            <td><input type="text" name="certificate_no" class="module-input" value="<?= h($patent['certificate_no']) ?>"></td>
            <td class="module-label">属满日</td>
            <td><input type="date" name="expire_date" class="module-input" value="<?= h($patent['expire_date']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">进入实审日</td>
            <td><input type="date" name="enter_substantive_date" class="module-input" value="<?= h($patent['enter_substantive_date']) ?>"></td>
            <td class="module-label">申请方式</td>
            <td colspan="3">
                <?= render_select('application_mode', $application_modes, $patent['application_mode']) ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">案件备注</td>
            <td colspan="5" style="width:100%"><textarea name="remarks" class="module-input" style="min-height:48px;width:100%;resize:vertical;"><?= h($patent['remarks']) ?></textarea></td>
        </tr>
    </table>
</form>
<script>
    (function() {
        var form = document.getElementById('edit-patent-basic-form');
        var btnSave = document.querySelector('.btn-save');
        var btnCancel = document.querySelector('.btn-cancel');
        btnSave.onclick = function() {
            // 必填校验
            var required = ['case_name', 'business_dept_id', 'process_item', 'client_id', 'application_type', 'is_allocated'];
            for (var i = 0; i < required.length; i++) {
                var el = form.querySelector('[name=\"' + required[i] + '\"]');
                if (!el || !el.value.trim()) {
                    alert('请填写所有必填项');
                    el && el.focus();
                    return;
                }
            }
            var fd = new FormData(form);
            fd.append('action', 'save');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/edit_tabs/basic.php?patent_id=<?= $patent_id ?>', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            alert('保存成功');
                            // 询问是否返回专利查询页面
                            if (confirm('保存成功，是否返回专利查询页面？')) {
                                if (window.parent.openTab) {
                                    window.parent.openTab(1, 5, 0); // 跳转回专利查询
                                }
                            }
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
        btnCancel.onclick = function() {
            if (confirm('确定取消编辑并返回专利查询页面？')) {
                if (window.parent.openTab) {
                    window.parent.openTab(1, 5, 0);
                }
            }
        };
    })();
</script>

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
        <td><input type="text" name="case_code" class="module-input" value="<?= h($patent['case_code']) ?>" readonly></td>
        <td class="module-label module-req">*承办部门</td>
        <td>
            <?= renderUserSearch('business_dept_id', $departments, false, $patent['business_dept_id']) ?>
        </td>
        <td class="module-label">开卷日期</td>
        <td><input type="date" name="open_date" class="module-input" value="<?= h($patent['open_date']) ?>"></td>
    </tr>
    <tr>
        <td class="module-label">客户文号</td>
        <td><input type="text" name="client_case_code" class="module-input" value="<?= h($patent['client_case_code']) ?>"></td>
        <td class="module-label module-req">*案件名称</td>
        <td><input type="text" name="case_name" class="module-input" value="<?= h($patent['case_name']) ?>" required></td>
        <td class="module-label">英文名称</td>
        <td><input type="text" name="case_name_en" class="module-input" value="<?= h($patent['case_name_en']) ?>"></td>
    </tr>
    <tr>
        <td class="module-label module-req">*处理事项</td>
        <td>
            <?= renderUserSearch('process_item', $process_items, false, $patent['process_item']) ?>
        </td>
        <td class="module-label module-req">*客户名称</td>
        <td>
            <?= renderUserSearch('client_id', $customers, false, $patent['client_id']) ?>
        </td>
        <td class="module-label">业务类型</td>
        <td>
            <?= renderUserSearch('business_type', $business_types, false, $patent['business_type']) ?>
        </td>
    </tr>
    <tr>
        <td class="module-label">委案日期</td>
        <td><input type="date" name="entrust_date" class="module-input" value="<?= h($patent['entrust_date']) ?>"></td>
        <td class="module-label">案件状态</td>
        <td>
            <?= render_select('case_status', $case_statuses, $patent['case_status']) ?>
        </td>
        <td class="module-label">同日申请</td>
        <td><input type="text" name="same_day_apply" class="module-input" value="<?= h($patent['same_day_apply']) ?>" placeholder="逗号分隔"></td>
    </tr>
    <tr>
        <td class="module-label">同日递交</td>
        <td><input type="text" name="same_day_submit" class="module-input" value="<?= h($patent['same_day_submit']) ?>" placeholder="逗号分隔"></td>
        <td class="module-label">代理费规则</td>
        <td colspan="3">
            <label><input type="radio" name="agent_rule" value="自定义" <?= $patent['agent_rule'] === '自定义' ? 'checked' : '' ?>>自定义</label>
            <label><input type="radio" name="agent_rule" value="纯包" <?= $patent['agent_rule'] === '纯包' ? 'checked' : '' ?>>纯包</label>
            <label><input type="radio" name="agent_rule" value="按项" <?= $patent['agent_rule'] === '按项' ? 'checked' : '' ?>>按项</label>
        </td>
    </tr>
    <tr>
        <td class="module-label">业务人员</td>
        <td colspan="5">
            <?= renderUserSearch('business_user_ids', $users, true, $patent['business_user_ids']) ?>
        </td>
    </tr>
    <tr>
        <td class="module-label">业务助理</td>
        <td colspan="5">
            <?= renderUserSearch('business_assistant_ids', $users, true, $patent['business_assistant_ids']) ?>
        </td>
    </tr>
    <tr>
        <td class="module-label module-req">*申请类型</td>
        <td>
            <?= render_select('application_type', $application_types, $patent['application_type']) ?>
        </td>
        <td class="module-label module-req">*是否配案</td>
        <td>
            <label><input type="radio" name="is_allocated" value="1" <?= $patent['is_allocated'] == 1 ? 'checked' : '' ?>>是</label>
            <label><input type="radio" name="is_allocated" value="0" <?= $patent['is_allocated'] == 0 ? 'checked' : '' ?>>否</label>
        </td>
        <td class="module-label">国家(地区)</td>
        <td>
            <?= render_select('country', $countries, $patent['country']) ?>
        </td>
    </tr>
    <tr>
        <td class="module-label">案件流向</td>
        <td>
            <?= render_select('case_flow', $case_flows, $patent['case_flow']) ?>
        </td>
        <td class="module-label">起始阶段</td>
        <td>
            <?= render_select('start_stage', $start_stages, $patent['start_stage']) ?>
        </td>
        <td class="module-label">客户状态</td>
        <td>
            <?= render_select('client_status', $client_statuses, $patent['client_status']) ?>
        </td>
    </tr>
    <tr>
        <td class="module-label">案源国</td>
        <td>
            <?= render_select('source_country', $source_countries, $patent['source_country']) ?>
        </td>
        <td class="module-label">其他</td>
        <td colspan="3">
            <?php foreach ($other_options as $v): ?>
                <label style="margin-right:12px;"><input type="checkbox" name="other_options[]" value="<?= h($v) ?>" <?= strpos("," . $patent['other_options'] . ",", "," . h($v) . ",") !== false ? 'checked' : '' ?>> <?= h($v) ?></label>
            <?php endforeach; ?>
        </td>
    </tr>
    <tr>
        <td class="module-label">申请号</td>
        <td><input type="text" name="application_no" class="module-input" value="<?= h($patent['application_no']) ?>"></td>
        <td class="module-label">申请日</td>
        <td><input type="date" name="application_date" class="module-input" value="<?= h($patent['application_date']) ?>"></td>
        <td class="module-label">公开号</td>
        <td><input type="text" name="publication_no" class="module-input" value="<?= h($patent['publication_no']) ?>"></td>
    </tr>
    <tr>
        <td class="module-label">公开日</td>
        <td><input type="date" name="publication_date" class="module-input" value="<?= h($patent['publication_date']) ?>"></td>
        <td class="module-label">处理人</td>
        <td>
            <?= renderUserSearch('handler_id', $users, false, $patent['handler_id']) ?>
        </td>
        <td class="module-label">公告号</td>
        <td><input type="text" name="announcement_no" class="module-input" value="<?= h($patent['announcement_no']) ?>"></td>
    </tr>
    <tr>
        <td class="module-label">公告日</td>
        <td><input type="date" name="announcement_date" class="module-input" value="<?= h($patent['announcement_date']) ?>"></td>
        <td class="module-label">证书号</td>
        <td><input type="text" name="certificate_no" class="module-input" value="<?= h($patent['certificate_no']) ?>"></td>
        <td class="module-label">属满日</td>
        <td><input type="date" name="expire_date" class="module-input" value="<?= h($patent['expire_date']) ?>"></td>
    </tr>
    <tr>
        <td class="module-label">进入实审日</td>
        <td><input type="date" name="enter_substantive_date" class="module-input" value="<?= h($patent['enter_substantive_date']) ?>"></td>
        <td class="module-label">申请方式</td>
        <td colspan="3">
            <?= render_select('application_mode', $application_modes, $patent['application_mode']) ?>
        </td>
    </tr>
    <tr>
        <td class="module-label">案件备注</td>
        <td colspan="5" style="width:100%"><textarea name="remarks" class="module-input" style="min-height:48px;width:100%;resize:vertical;"><?= h($patent['remarks']) ?></textarea></td>
    </tr>
</table>