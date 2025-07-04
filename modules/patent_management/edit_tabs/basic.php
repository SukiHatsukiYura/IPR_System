<?php
// 专利编辑-基本信息tab
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php'); // 引入通用函数库
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
$case_statuses = ['未递交', '已递交', '暂缓申请', '受理', '初审合格', '初审', '公开', '实审', '补正', '审查', '一通', '二通', '三通', '四通', '五通', '六通', '七通', '八通', '一补', '九通', '二补', '三补', '视为撤回', '主动撤回', '驳回', '复审', '无效', '视为放弃', '主动放弃', '授权', '待领证', '维持', '终止', '结案', '届满', 'PCT国际检索', '中止', '保全', '诉讼', '办理登记手续', '复审受理', 'Advisory Action', 'Appeal', 'Election Action', 'Final Action', 'Non Final Action', 'Petition', 'RCE', '公告', '视为未提出'];
$process_items = ['请求优先审查', '开卷', '放弃', '更正', '无效答辩', '不予受理', '官文转达', '缴年费', '民事诉讼上诉', '主动补正', '专利权评价报告', '驳回', '取得检索报告', '请求无效', '翻译', '审查高速公路', '资助监控', '赔偿请求', '请求检索报告', '许可备案', '诉讼', '取得副本', '请求加速审查', '民事诉讼答辩', '取得申请号', '请求中止', '办理登记手续', '复审决定', '避免重复授权', '民事诉讼上诉答辩', '确认通知书', '请求保密审查', '结案', '补正', '请求恢复权利', '视为未提出', '手续补正', '取得证书', '年费滞纳金', '复审意见陈述', '提交IDS', '复审受理', '请求延长期限', '撤回', '请求提前公开', '处理审查意见', '口审', '诉讼举证', '项目申报', '办理DAS', '行政诉讼上诉答辩', '请求复审', '无效行政诉讼答辩', '请求退款', '提出行政诉讼', '缴费', '终止', '无效诉讼', '公众意见', '保密决定', '变更代理人申请补正通知', '请求实审', '提出民事诉讼', '请求副本', '新申请', '复议申请', '无效请求补充意见', '著泉项目变更', '行政诉讼上诉', '请求费用减缓', '视为未要求'];
$application_types = ['发明', '实用新型', '外观设计', '临时申请', '再公告', '植物', '集成电路布图设计', '年费', '无效', '其他'];
$application_modes = ['电子申请(事务所)', '纸件申请', '其他'];
$countries = ['中国', '美国', '日本', '韩国', '德国', '法国', '英国', '其他'];
$case_flows = ['内-内', '内-外', '外-内', '外-外'];
$start_stages = ['无', '新申请', '答辩', '缴费'];
$client_statuses = ['放弃指示', '新申请指示递交', '补充申请信息资料', '修改意见', '著录项目变更指示', 'OA指示递交', '结案指示'];
$source_countries = ['中国', '美国', '日本', '其他'];
$other_options = ['同步提交', '提前公布', '请求保密审查', '预审案件', '优先审查', '同时请求DAS码', '请求提前公开', '请求费用减缓'];

// 查询动态下拉选项
$departments = $pdo->query("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();
$users = $pdo->query("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name")->fetchAll();
$customers = $pdo->query("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn")->fetchAll();

// 格式化数据以适应通用下拉框函数
$departments_options = [];
$users_options = [];
$customers_options = [];
$process_items_options = [];
$business_types_options = [];
$case_statuses_options = [];
$application_types_options = [];
$countries_options = [];
$case_flows_options = [];
$start_stages_options = [];
$client_statuses_options = [];
$source_countries_options = [];
$application_modes_options = [];

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

foreach ($client_statuses as $status) {
    $client_statuses_options[$status] = $status;
}

foreach ($source_countries as $country) {
    $source_countries_options[$country] = $country;
}

foreach ($application_modes as $mode) {
    $application_modes_options[$mode] = $mode;
}

// 处理POST保存请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_basic') {
    header('Content-Type: application/json');

    // 字段列表
    $fields = [
        'case_name',
        'case_name_en',
        'business_dept_id',
        'open_date',
        'client_case_code',
        'process_item',
        'client_id',
        'business_type',
        'entrust_date',
        'case_status',
        'same_day_apply',
        'same_day_submit',
        'agent_rule',
        'remarks',
        'application_no',
        'application_date',
        'publication_no',
        'publication_date',
        'handler_id',
        'announcement_no',
        'announcement_date',
        'certificate_no',
        'expire_date',
        'enter_substantive_date',
        'application_mode',
        'business_user_ids',
        'business_assistant_ids',
        'project_leader_id',
        'application_type',
        'is_allocated',
        'country',
        'case_flow',
        'start_stage',
        'client_status',
        'source_country',
        'other_options'
    ];

    $data = [];
    $set = [];

    // 构建更新数据
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $value = $_POST[$f];

            // 日期处理
            $date_fields = [
                'open_date',
                'entrust_date',
                'application_date',
                'publication_date',
                'announcement_date',
                'expire_date',
                'enter_substantive_date'
            ];
            if (in_array($f, $date_fields) && $value === '') {
                $value = null;
            }

            // 外键处理
            $fk_fields = ['business_dept_id', 'client_id', 'handler_id', 'project_leader_id'];
            if (in_array($f, $fk_fields) && (empty($value) || intval($value) <= 0)) {
                $value = null;
            }

            // 特殊处理is_allocated
            if ($f === 'is_allocated') {
                $value = intval($value) ? 1 : 0;
            }

            $data[$f] = $value;
            $set[] = "$f = :$f";
        }
    }

    if (empty($set)) {
        echo json_encode(['success' => false, 'msg' => '无可更新字段']);
        exit;
    }

    // 必填字段验证
    $required_fields = [
        'case_name' => '案件名称',
        'business_dept_id' => '承办部门',
        'process_item' => '处理事项',
        'client_id' => '客户名称',
        'application_type' => '申请类型',
        'is_allocated' => '是否配案'
    ];

    $validation_errors = [];
    foreach ($required_fields as $field => $label) {
        if ($field === 'is_allocated') {
            // 特殊处理is_allocated字段，它是radio按钮，值为0或1
            if (!isset($data[$field]) || ($data[$field] !== '0' && $data[$field] !== '1' && $data[$field] !== 0 && $data[$field] !== 1)) {
                $validation_errors[] = $label;
            }
        } else {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '' || (is_numeric($data[$field]) && intval($data[$field]) <= 0)) {
                $validation_errors[] = $label;
            }
        }
    }

    if (!empty($validation_errors)) {
        echo json_encode([
            'success' => false,
            'msg' => '请填写所有必填项：' . implode('、', $validation_errors)
        ]);
        exit;
    }

    // 执行更新
    try {
        $data['id'] = $patent_id;
        $sql = "UPDATE patent_case_info SET " . implode(',', $set) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute($data);

        echo json_encode(['success' => $ok, 'msg' => $ok ? null : '数据库更新失败']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    }
    exit;
}

// 输出下拉框所需JS资源
render_select_search_assets();
?>
<div class="module-btns" style="margin-bottom:10px;">
    <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
    <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
</div>
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
                <?php render_select_search('business_dept_id', $departments_options, $patent['business_dept_id']); ?>
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
                <?php render_select_search('process_item', $process_items_options, $patent['process_item']); ?>
            </td>
            <td class="module-label module-req">*客户名称</td>
            <td>
                <?php render_select_search('client_id', $customers_options, $patent['client_id']); ?>
            </td>
            <td class="module-label">业务类型</td>
            <td>
                <?php render_select_search('business_type', $business_types_options, $patent['business_type']); ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">委案日期</td>
            <td><input type="date" name="entrust_date" class="module-input" value="<?= h($patent['entrust_date']) ?>"></td>
            <td class="module-label">案件状态</td>
            <td>
                <select name="case_status" class="module-input">
                    <option value="">--请选择--</option>
                    <?php foreach ($case_statuses as $status): ?>
                        <option value="<?= h($status) ?>" <?= $patent['case_status'] == $status ? 'selected' : '' ?>><?= h($status) ?></option>
                    <?php endforeach; ?>
                </select>
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
                <label><input type="radio" name="agent_rule" value="按项" <?= ($patent['agent_rule'] === '按项' || !$patent['agent_rule']) ? 'checked' : '' ?>>按项</label>
            </td>
        </tr>
        <tr>
            <td class="module-label">业务人员</td>
            <td colspan="5">
                <?php render_select_search_multi('business_user_ids', $users_options, $patent['business_user_ids']); ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">业务助理</td>
            <td colspan="5">
                <?php render_select_search_multi('business_assistant_ids', $users_options, $patent['business_assistant_ids']); ?>
            </td>
        </tr>
        <tr>
            <td class="module-label module-req">*申请类型</td>
            <td>
                <select name="application_type" class="module-input">
                    <option value="">--请选择--</option>
                    <?php foreach ($application_types as $type): ?>
                        <option value="<?= h($type) ?>" <?= $patent['application_type'] == $type ? 'selected' : '' ?>><?= h($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="module-label module-req">*是否配案</td>
            <td>
                <label><input type="radio" name="is_allocated" value="1" <?= ($patent['is_allocated'] == 1) ? 'checked' : '' ?>>是</label>
                <label><input type="radio" name="is_allocated" value="0" <?= ($patent['is_allocated'] == 0) ? 'checked' : '' ?>>否</label>
            </td>
            <td class="module-label">国家(地区)</td>
            <td>
                <select name="country" class="module-input">
                    <option value="">--请选择--</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= h($country) ?>" <?= $patent['country'] == $country ? 'selected' : '' ?>><?= h($country) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="module-label">案件流向</td>
            <td>
                <select name="case_flow" class="module-input">
                    <option value="">--请选择--</option>
                    <?php foreach ($case_flows as $flow): ?>
                        <option value="<?= h($flow) ?>" <?= $patent['case_flow'] == $flow ? 'selected' : '' ?>><?= h($flow) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="module-label">起始阶段</td>
            <td>
                <select name="start_stage" class="module-input">
                    <option value="">--请选择--</option>
                    <?php foreach ($start_stages as $stage): ?>
                        <option value="<?= h($stage) ?>" <?= $patent['start_stage'] == $stage ? 'selected' : '' ?>><?= h($stage) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="module-label">客户状态</td>
            <td>
                <select name="client_status" class="module-input">
                    <option value="">--请选择--</option>
                    <?php foreach ($client_statuses as $status): ?>
                        <option value="<?= h($status) ?>" <?= $patent['client_status'] == $status ? 'selected' : '' ?>><?= h($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="module-label">案源国</td>
            <td>
                <select name="source_country" class="module-input">
                    <option value="">--请选择--</option>
                    <?php foreach ($source_countries as $country): ?>
                        <option value="<?= h($country) ?>" <?= $patent['source_country'] == $country ? 'selected' : '' ?>><?= h($country) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="module-label">其他</td>
            <td colspan="3">
                <?php
                $current_other_options = !empty($patent['other_options']) ? explode(',', $patent['other_options']) : [];
                foreach ($other_options as $v): ?>
                    <label style="margin-right:12px;"><input type="checkbox" name="other_options[]" value="<?= h($v) ?>" <?= in_array(h($v), $current_other_options) ? 'checked' : '' ?>> <?= h($v) ?></label>
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
                <?php render_select_search('handler_id', $users_options, $patent['handler_id']); ?>
            </td>
            <td class="module-label">项目负责人</td>
            <td>
                <?php render_select_search('project_leader_id', $users_options, $patent['project_leader_id']); ?>
            </td>
        </tr>
        <tr>
            <td class="module-label">公告号</td>
            <td><input type="text" name="announcement_no" class="module-input" value="<?= h($patent['announcement_no']) ?>"></td>
            <td class="module-label">公告日</td>
            <td><input type="date" name="announcement_date" class="module-input" value="<?= h($patent['announcement_date']) ?>"></td>
            <td class="module-label">证书号</td>
            <td><input type="text" name="certificate_no" class="module-input" value="<?= h($patent['certificate_no']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">属满日</td>
            <td><input type="date" name="expire_date" class="module-input" value="<?= h($patent['expire_date']) ?>"></td>
            <td class="module-label">进入实审日</td>
            <td><input type="date" name="enter_substantive_date" class="module-input" value="<?= h($patent['enter_substantive_date']) ?>"></td>
            <td class="module-label">申请方式</td>
            <td>
                <select name="application_mode" class="module-input">
                    <option value="">--请选择--</option>
                    <?php foreach ($application_modes as $mode): ?>
                        <option value="<?= h($mode) ?>" <?= $patent['application_mode'] == $mode ? 'selected' : '' ?>><?= h($mode) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <tr>
            <td class="module-label">案件备注</td>
            <td colspan="5" style="width:100%"><textarea name="remarks" class="module-input" style="min-height:48px;width:100%;resize:vertical;"><?= h($patent['remarks']) ?></textarea></td>
        </tr>
    </table>
</form>
<script>
    function initPatentTabEvents() {
        // 保存按钮
        document.querySelectorAll('#patent-tab-content .btn-save').forEach(function(btnSave) {
            btnSave.onclick = function() {
                var form = btnSave.closest('.module-panel').querySelector('form.module-form');
                if (!form) return;

                // 定义必填字段
                var required = ['case_name', 'business_dept_id', 'process_item', 'client_id', 'application_type', 'is_allocated'];
                var errors = [];

                // 验证所有必填字段
                for (var i = 0; i < required.length; i++) {
                    var fieldName = required[i];
                    var el = form.querySelector('[name="' + fieldName + '"]');
                    var val = '';

                    // 特殊处理radio按钮
                    if (fieldName === 'is_allocated') {
                        var checkedRadio = form.querySelector('[name="' + fieldName + '"]:checked');
                        val = checkedRadio ? checkedRadio.value : '';
                    } else {
                        val = el ? el.value.trim() : '';
                        // 处理下拉框的隐藏字段
                        if (el && el.type === 'hidden' && form.querySelector('[name="' + fieldName + '_display"]')) {
                            val = el.value;
                        }
                    }

                    if (!val) {
                        // 获取字段显示名称
                        var labelText = '';
                        if (form.querySelector('[name="' + fieldName + '_display"]')) {
                            labelText = form.querySelector('[name="' + fieldName + '_display"]').closest('td').previousElementSibling.textContent.replace('*', '').trim();
                        } else if (el) {
                            var labelTd = el.closest('td').previousElementSibling;
                            if (labelTd) {
                                labelText = labelTd.textContent.replace('*', '').trim();
                            }
                        }

                        if (!labelText) {
                            // 备用显示名称
                            var displayNames = {
                                'case_name': '案件名称',
                                'business_dept_id': '承办部门',
                                'process_item': '处理事项',
                                'client_id': '客户名称',
                                'application_type': '申请类型',
                                'is_allocated': '是否配案'
                            };
                            labelText = displayNames[fieldName] || fieldName;
                        }

                        errors.push(labelText);
                    }
                }

                // 如果有错误，显示所有错误
                if (errors.length > 0) {
                    alert('请填写所有必填项：' + errors.join('、'));
                    // 聚焦到第一个错误字段
                    var firstErrorField = required.find(function(field) {
                        var val = '';
                        if (field === 'is_allocated') {
                            var checkedRadio = form.querySelector('[name="' + field + '"]:checked');
                            val = checkedRadio ? checkedRadio.value : '';
                        } else {
                            var el = form.querySelector('[name="' + field + '"]');
                            val = el ? el.value.trim() : '';
                            if (el && el.type === 'hidden' && form.querySelector('[name="' + field + '_display"]')) {
                                val = el.value;
                            }
                        }
                        return !val;
                    });

                    if (firstErrorField) {
                        if (firstErrorField === 'is_allocated') {
                            // radio按钮聚焦到第一个选项
                            var firstRadio = form.querySelector('[name="' + firstErrorField + '"]');
                            if (firstRadio && firstRadio.focus) firstRadio.focus();
                        } else {
                            var el = form.querySelector('[name="' + firstErrorField + '"]');
                            if (el && el.type !== 'hidden' && el.focus) {
                                el.focus();
                            } else if (form.querySelector('[name="' + firstErrorField + '_display"]')) {
                                form.querySelector('[name="' + firstErrorField + '_display"]').focus();
                            }
                        }
                    }
                    return;
                }

                var fd = new FormData(form);
                fd.append('action', 'save_basic');

                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/patent_management/edit_tabs/basic.php?patent_id=<?= $patent_id ?>', true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            alert(res.success ? '保存成功' : ('保存失败: ' + (res.msg || '未知错误')));
                        } catch (e) {
                            alert('保存失败，服务器返回无效响应');
                        }
                    }
                };
                xhr.send(fd);
            };
        });

        // 取消按钮
        document.querySelectorAll('#patent-tab-content .btn-cancel').forEach(function(btnCancel) {
            btnCancel.onclick = function() {
                if (confirm('确定要取消吗？未保存的内容将丢失。')) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'modules/patent_management/edit_tabs/basic.php?patent_id=<?= $patent_id ?>', true);
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            var tabContent = document.querySelector('#patent-tab-content');
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
                                    if (typeof initPatentTabEvents === 'function') {
                                        initPatentTabEvents();
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
        });
    }

    // 初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPatentTabEvents);
    } else {
        initPatentTabEvents();
    }
</script>