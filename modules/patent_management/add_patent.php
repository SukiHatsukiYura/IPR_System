<?php
session_start();
include_once(__DIR__ . '/../../database.php');
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
// 业务类型
// 无效案件，普通新申请，专利转让，著泉项目变更， PCT国际阶段，复审，香港登记案，申请香港，临时申请，公众意见，翻译，专利检索案件，代缴年费案件，诉讼案件，顾问，专利许可备案，海关备案，其他，PCT国家阶段，办理副本案件
$business_types = ['无效案件', '普通新申请', '专利转让', '著泉项目变更', 'PCT国际阶段', '复审', '香港登记案', '申请香港', '临时申请', '公众意见', '翻译', '专利检索案件', '代缴年费案件', '诉讼案件', '顾问', '专利许可备案', '海关备案', '其他', 'PCT国家阶段', '办理副本案件'];
// 案件状态
$case_statuses = ['请选择', '未递交', '已递交', '暂缓申请', '受理', '初审合格', '初审', '公开', '实审', '补正', '审查', '一通', '二通', '三通', '四通', '五通', '六通', '七通', '八通', '一补', '九通', '二补', '三补', '视为撤回', '主动撤回', '驳回', '复审', '无效', '视为放弃', '主动放弃', '授权', '待领证', '维持', '终止', '结案', '届满', 'PCT国际检索', '中止', '保全', '诉讼', '办理登记手续', '复审受理', 'Advisory Action', 'Appeal', 'Election Action', 'Final Action', 'Non Final Action', 'Petition', 'RCE', '公告', '视为未提出'];
// 处理事项
// 请求优先审查，开卷，放弃，更正，无效答辩，不予受理，官文转达，缴年费，民事诉讼上诉，主动补正，专利权评价报告，驳回，取得检索报告，请求无效，翻译，审查高速公路，资助监控，赔偿请求，请求检索报告，许可备案，诉讼，取得副本，请求加速审查，民事诉讼答辩，取得申请号，请求中止，办理登记手续，复审决定，避免重复授权，民事诉讼上诉答辩，确认通知书，请求保密审查，结案，补正，请求恢复权利，视为未提出，手续补正，取得证书，年费滞纳金，复审意见陈述，提交IDS，复审受理，请求延长期限，撤回，请求提前公开，处理审查意见，口审，诉讼举证，项目申报，办理DAS，行政诉讼上诉答辩，请求复审，无效行政诉讼答辩，请求退款，提出行政诉讼，缴费，终止，无效诉讼，公众意见，保密决定，变更代理人申请补正通知，请求实审，提出民事诉讼，请求副本，新申请，复议申请，无效请求补充意见，著泉项目变更，行政诉讼上诉，请求费用减缓，视为未要求
$process_items = ['请求优先审查', '开卷', '放弃', '更正', '无效答辩', '不予受理', '官文转达', '缴年费', '民事诉讼上诉', '主动补正', '专利权评价报告', '驳回', '取得检索报告', '请求无效', '翻译', '审查高速公路', '资助监控', '赔偿请求', '请求检索报告', '许可备案', '诉讼', '取得副本', '请求加速审查', '民事诉讼答辩', '取得申请号', '请求中止', '办理登记手续', '复审决定', '避免重复授权', '民事诉讼上诉答辩', '确认通知书', '请求保密审查', '结案', '补正', '请求恢复权利', '视为未提出', '手续补正', '取得证书', '年费滞纳金', '复审意见陈述', '提交IDS', '复审受理', '请求延长期限', '撤回', '请求提前公开', '处理审查意见', '口审', '诉讼举证', '项目申报', '办理DAS', '行政诉讼上诉答辩', '请求复审', '无效行政诉讼答辩', '请求退款', '提出行政诉讼', '缴费', '终止', '无效诉讼', '公众意见', '保密决定', '变更代理人申请补正通知', '请求实审', '提出民事诉讼', '请求副本', '新申请', '复议申请', '无效请求补充意见', '著泉项目变更', '行政诉讼上诉', '请求费用减缓', '视为未要求'];
// 申请类型
$application_types = ['请选择', '发明', '实用新型', '外观设计', '临时申请', '再公告', '植物', '集成电路布图设计', '年费', '无效', '其他'];
// 申请方式
$application_modes = ['电子申请(事务所)', '纸件申请', '其他'];
// 国家(地区)
$countries = ['中国', '美国', '日本', '韩国', '德国', '法国', '英国', '其他'];
// 案件流向
$case_flows = ['内-内', '内-外', '外-内', '外-外'];
// 起始阶段
$start_stages = ['无', '新申请', '答辩', '缴费'];
// 客户状态
$client_statuses = ['请选择', '放弃指示', '新申请指示递交', '补充申请信息资料', '修改意见', '著录项目变更指示', 'OA指示递交', '结案指示'];
// 案源国
$source_countries = ['中国', '美国', '日本', '其他'];
// 其他选项
$other_options = ['同步提交', '提前公布', '请求保密审查', '预审案件', '优先审查', '同时请求DAS码', '请求提前公开', '请求费用减缓'];

// 动态数据
$departments = $pdo->query("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();
$users = $pdo->query("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name")->fetchAll();
$customers = $pdo->query("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json');
    // 自动生成唯一文号
    function generate_case_code($pdo)
    {
        $prefix = 'ZL' . date('Ymd');
        $sql = "SELECT COUNT(*) FROM patent_case_info WHERE case_code LIKE :prefix";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);
        $count = $stmt->fetchColumn();
        $serial = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        return $prefix . $serial;
    }
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

    // 修正：所有DATE类型字段为空字符串时转为null，避免MySQL日期类型报错
    $date_fields = [
        'open_date',
        'entrust_date',
        'application_date',
        'publication_date',
        'announcement_date',
        'expire_date',
        'enter_substantive_date'
    ];
    foreach ($date_fields as $field) {
        if (isset($data[$field]) && $data[$field] === '') {
            $data[$field] = null;
        }
    }
    // 修正：所有外键字段为0或小于0时转为null，避免外键约束报错
    $fk_fields = ['handler_id', 'business_dept_id', 'client_id', 'project_leader_id'];
    foreach ($fk_fields as $field) {
        if (isset($data[$field]) && $data[$field] <= 0) {
            $data[$field] = null;
        }
    }
    if ($data['case_name'] === '' || $data['business_dept_id'] <= 0 || $data['process_item'] === '' || $data['client_id'] <= 0 || $data['application_type'] === '' || !isset($_POST['is_allocated'])) {
        echo json_encode(['success' => false, 'msg' => '请填写所有必填项']);
        exit;
    }
    try {
        // 新增模式 - 自动生成唯一文号并执行INSERT操作
        $data['case_code'] = generate_case_code($pdo);
        $fields = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO patent_case_info ($fields) VALUES ($placeholders)";
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
    $val = isset($_POST[$name]) ? $_POST[$name] : $post_val;
    $display = '';
    // 判断数据类型
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
    $script = '<script>
    (function() {
        var box = document.querySelector("[name=\'' . $name . '_display\']").closest(".' . $class . '-box"),
            input = box.querySelector(".' . $class . '-input"),
            hidden = box.querySelector("input[type=hidden]"),
            list = box.querySelector(".' . $class . '-list"),
            searchInput = list.querySelector(".' . $class . '-list-input"),
            itemsDiv = list.querySelector(".' . $class . '-list-items"),
            data = ' . json_encode($data) . ',
            isAssoc = ' . ($is_assoc ? 'true' : 'false') . ',
            selected = hidden.value ? hidden.value.split(",") : [];
        function getIdLabel(item) {
            if (isAssoc) {
                var id = item.id;
                var label = item.real_name || item.dept_name || item.customer_name_cn || id;
                return {id: String(id), label: label};
            } else {
                return {id: String(item), label: item};
            }
        }
        function renderList(filter) {
            var html = "";
            if (' . ($multi ? 'true' : 'false') . ') {
                data.forEach(function(item) {
                    var obj = getIdLabel(item);
                    if (!filter || obj.label.toLowerCase().indexOf(filter.toLowerCase()) !== -1) {
                        html += "<div class=\"' . $class . '-item\"><label><input type=\"checkbox\" value=\"" + obj.id + "\"" + 
                            (selected.indexOf(String(obj.id)) !== -1 ? " checked" : "") + ">" + obj.label + "</label></div>";
                    }
                });
            } else {
                html += "<div class=\"' . $class . '-item\" data-id=\"\">--请选择--</div>";
                data.forEach(function(item) {
                    var obj = getIdLabel(item);
                    if (!filter || obj.label.toLowerCase().indexOf(filter.toLowerCase()) !== -1) {
                        html += "<div class=\"' . $class . '-item\" data-id=\"" + obj.id + "\">" + obj.label + "</div>";
                    }
                });
            }
            if (!html) html = "<div class=\"no-match\">无匹配项</div>";
            itemsDiv.innerHTML = html;
        }
        input.onclick = function() {
            renderList("");
            list.style.display = "block";
            searchInput.value = "";
            searchInput.focus();
        };
        searchInput.oninput = function() {
            renderList(this.value.trim());
        };
        document.addEventListener("click", function(e) {
            if (!box.contains(e.target)) list.style.display = "none";
        });
        if (' . ($multi ? 'true' : 'false') . ') {
            itemsDiv.onchange = function(e) {
                if (e.target.type === "checkbox") {
                    var vals = Array.from(itemsDiv.querySelectorAll("input[type=checkbox]:checked")).map(function(cb) {
                        return cb.value;
                    });
                    selected = vals;
                    input.value = data.filter(function(u) {
                        var obj = getIdLabel(u);
                        return selected.indexOf(String(obj.id)) !== -1;
                    }).map(function(u) {
                        return getIdLabel(u).label;
                    }).join(",");
                    hidden.value = selected.join(",");
                }
            };
            var btnSelectAll = list.querySelector(".btn-select-all"),
                btnClear = list.querySelector(".btn-clear");
            btnSelectAll.onclick = function() {
                itemsDiv.querySelectorAll("input[type=checkbox]").forEach(function(cb) {
                    cb.checked = true;
                });
                var event = new Event("change");
                itemsDiv.dispatchEvent(event);
            };
            btnClear.onclick = function() {
                itemsDiv.querySelectorAll("input[type=checkbox]").forEach(function(cb) {
                    cb.checked = false;
                });
                var event = new Event("change");
                itemsDiv.dispatchEvent(event);
            };
        } else {
            itemsDiv.onmousedown = function(e) {
                var item = e.target.closest(".' . $class . '-item");
                if (item) {
                    input.value = item.textContent === "--请选择--" ? "" : item.textContent;
                    hidden.value = item.getAttribute("data-id");
                    list.style.display = "none";
                }
            };
        }
    })();
    </script>';
    return $html . $script;
}

include_once(__DIR__ . '/../../common/functions.php');
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>新增专利</title>
    <link rel="stylesheet" href="../../css/module.css">
    <?php render_select_search_assets(); ?>
</head>

<body>
    <div class="module-panel">
        <div class="module-btns">
            <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
            <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
        </div>
        <h3 style="text-align:center;margin-bottom:15px;">新增专利</h3>
        <form id="add-patent-form" class="module-form" autocomplete="off">
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
                    <td class="module-label module-req">*承办部门</td>
                    <td>
                        <?php echo renderUserSearch('business_dept_id', $departments, false, ''); ?>
                    </td>
                    <td class="module-label">开卷日期</td>
                    <td><input type="date" name="open_date" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label">客户文号</td>
                    <td><input type="text" name="client_case_code" class="module-input" value=""></td>
                    <td class="module-label module-req">*案件名称</td>
                    <td><input type="text" name="case_name" class="module-input" value="" required></td>
                    <td class="module-label">英文名称</td>
                    <td><input type="text" name="case_name_en" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label module-req">*处理事项</td>
                    <td>
                        <?php echo renderUserSearch('process_item', $process_items, false, ''); ?>
                    </td>
                    <td class="module-label module-req">*客户名称</td>
                    <td>
                        <?php echo renderUserSearch('client_id', $customers, false, ''); ?>
                    </td>
                    <td class="module-label">业务类型</td>
                    <td>
                        <?php echo renderUserSearch('business_type', $business_types, false, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">委案日期</td>
                    <td><input type="date" name="entrust_date" class="module-input" value=""></td>
                    <td class="module-label">案件状态</td>
                    <td>
                        <?php echo render_select('case_status', $case_statuses, ''); ?>
                    </td>
                    <td class="module-label">同日申请</td>
                    <td><input type="text" name="same_day_apply" class="module-input" value="" placeholder="逗号分隔"></td>
                </tr>
                <tr>
                    <td class="module-label">同日递交</td>
                    <td><input type="text" name="same_day_submit" class="module-input" value="" placeholder="逗号分隔"></td>
                    <td class="module-label">代理费规则</td>
                    <td colspan="3">
                        <label><input type="radio" name="agent_rule" value="自定义">自定义</label>
                        <label><input type="radio" name="agent_rule" value="纯包">纯包</label>
                        <label><input type="radio" name="agent_rule" value="按项" checked>按项</label>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">业务人员</td>
                    <td colspan="5">
                        <?php echo renderUserSearch('business_user_ids', $users, true, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">业务助理</td>
                    <td colspan="5">
                        <?php echo renderUserSearch('business_assistant_ids', $users, true, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label module-req">*申请类型</td>
                    <td>
                        <?php echo render_select('application_type', $application_types, ''); ?>
                    </td>
                    <td class="module-label module-req">*是否配案</td>
                    <td>
                        <label><input type="radio" name="is_allocated" value="1" checked>是</label>
                        <label><input type="radio" name="is_allocated" value="0">否</label>
                    </td>
                    <td class="module-label">国家(地区)</td>
                    <td>
                        <?php echo render_select('country', $countries, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">案件流向</td>
                    <td>
                        <?php echo render_select('case_flow', $case_flows, ''); ?>
                    </td>
                    <td class="module-label">起始阶段</td>
                    <td>
                        <?php echo render_select('start_stage', $start_stages, ''); ?>
                    </td>
                    <td class="module-label">客户状态</td>
                    <td>
                        <?php echo render_select('client_status', $client_statuses, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">案源国</td>
                    <td>
                        <?php echo render_select('source_country', $source_countries, ''); ?>
                    </td>
                    <td class="module-label">其他</td>
                    <td colspan="3">
                        <?php foreach ($other_options as $v): ?>
                            <label style="margin-right:12px;"><input type="checkbox" name="other_options[]" value="<?= h($v) ?>"> <?= h($v) ?></label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">申请号</td>
                    <td><input type="text" name="application_no" class="module-input" value=""></td>
                    <td class="module-label">申请日</td>
                    <td><input type="date" name="application_date" class="module-input" value=""></td>
                    <td class="module-label">公开号</td>
                    <td><input type="text" name="publication_no" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label">公开日</td>
                    <td><input type="date" name="publication_date" class="module-input" value=""></td>
                    <td class="module-label">处理人</td>
                    <td>
                        <?php echo renderUserSearch('handler_id', $users, false, ''); ?>
                    </td>
                    <td class="module-label">公告号</td>
                    <td><input type="text" name="announcement_no" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label">公告日</td>
                    <td><input type="date" name="announcement_date" class="module-input" value=""></td>
                    <td class="module-label">证书号</td>
                    <td><input type="text" name="certificate_no" class="module-input" value=""></td>
                    <td class="module-label">属满日</td>
                    <td><input type="date" name="expire_date" class="module-input" value=""></td>
                </tr>
                <tr>
                    <td class="module-label">进入实审日</td>
                    <td><input type="date" name="enter_substantive_date" class="module-input" value=""></td>
                    <td class="module-label">申请方式</td>
                    <td colspan="3">
                        <?php echo render_select('application_mode', $application_modes, ''); ?>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">案件备注</td>
                    <td colspan="5" style="width:100%"><textarea name="remarks" class="module-input" style="min-height:48px;width:100%;resize:vertical;"></textarea></td>
                </tr>
            </table>
        </form>
    </div>
    <script>
        (function() {
            var form = document.getElementById('add-patent-form'),
                btnSave = document.querySelector('.btn-save'),
                btnCancel = document.querySelector('.btn-cancel');

            // 保存按钮AJAX提交
            btnSave.onclick = function() {
                var required = ['case_name', 'business_dept_id', 'process_item', 'client_id', 'application_type', 'is_allocated'];
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
                xhr.open('POST', 'modules/patent_management/add_patent.php', true);
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
        })();
    </script>
</body>

</html>