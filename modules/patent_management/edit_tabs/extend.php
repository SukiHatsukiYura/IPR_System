<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php'); // 引入通用函数库
check_access_via_framework();
session_start();

if (!isset($_GET['patent_id']) || intval($_GET['patent_id']) <= 0) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未指定专利ID</div>';
    exit;
}
$patent_id = intval($_GET['patent_id']);

// 查询专利信息确认存在
$patent_stmt = $pdo->prepare("SELECT id FROM patent_case_info WHERE id = ?");
$patent_stmt->execute([$patent_id]);
if (!$patent_stmt->fetch()) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未找到该专利信息</div>';
    exit;
}

// 查询部门
$departments = $pdo->query("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();

// 定义下拉选项
$pct_languages = ['请选择', '中文', '英文', '日文', '德文', '法文', '韩文', '俄文', '西班牙语', '意大利语', '葡萄牙语', '荷兰语', '瑞典语', '挪威语', '丹麦语', '芬兰语', '其他'];
$procedure_stages = ['请选择', '第1年年费', '第2年年费', '第3年年费', '第4年年费', '第5年年费', '第6年年费', '第7年年费', '第8年年费', '第9年年费', '第10年年费', '第11年年费', '第12年年费', '第13年年费', '第14年年费', '第15年年费', '第16年年费', '第17年年费', '第18年年费', '第19年年费', '第20年年费'];
$deferred_options = ['请选择', '提供延迟期限为1年', '提供延迟期限为2年', '提供延迟期限为3年'];
$case_coefficient_options = ['请选择', '重要', '一般', '简单'];

// 格式化数据以适应通用下拉框函数
$departments_options = [];
foreach ($departments as $dept) {
    $departments_options[$dept['id']] = $dept['dept_name'];
}

// 检查是否已有扩展信息，没有则插入空白
$extend_stmt = $pdo->prepare("SELECT * FROM patent_case_extend_info WHERE patent_case_info_id = ?");
$extend_stmt->execute([$patent_id]);
$extend = $extend_stmt->fetch(PDO::FETCH_ASSOC);
if (!$extend) {
    $stmt = $pdo->prepare("INSERT INTO patent_case_extend_info (patent_case_info_id) VALUES (?)");
    $stmt->execute([$patent_id]);
    $extend_stmt->execute([$patent_id]); // 重新查询
    $extend = $extend_stmt->fetch(PDO::FETCH_ASSOC);
}

// 处理AJAX保存
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_extend') {
    header('Content-Type: application/json');
    $fields = [
        'original_application_no',
        'original_application_date',
        'reexamination_invalid_case_no',
        'temporary_application_no',
        'temporary_application_date',
        'applicant_reference_no',
        'art_unit',
        'enter_national_phase_date',
        'grant_notice_date',
        'cooperation_agency',
        'external_source_person',
        'cost',
        'pct_application_no',
        'pct_application_date',
        'pct_publication_no',
        'pct_publication_date',
        'pct_publication_language',
        'international_search_unit',
        'confirmation_number',
        'registration_procedure_stage',
        'new_application_submit_date',
        'cooperation_agency_case_no',
        'internal_source_person',
        'budget',
        'independent_claim_count',
        'claim_count',
        'specification_page_count',
        'design_image_count',
        'specification_word_count',
        'international_search_complete_date',
        'is_first_application',
        'das_access_code',
        'case_coefficient',
        'grant_date',
        'deferred_examination',
        'department_id'
    ];
    $data = [];
    $set = [];

    // 处理日期字段
    $date_fields = [
        'original_application_date',
        'temporary_application_date',
        'enter_national_phase_date',
        'grant_notice_date',
        'pct_application_date',
        'pct_publication_date',
        'international_search_complete_date',
        'new_application_submit_date',
        'grant_date'
    ];

    // 处理数字字段
    $number_fields = [
        'cost',
        'budget',
        'independent_claim_count',
        'claim_count',
        'specification_page_count',
        'design_image_count',
        'specification_word_count',
        'is_first_application',
        'department_id'
    ];

    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $value = $_POST[$f];

            // 日期处理
            if (in_array($f, $date_fields) && $value === '') {
                $value = null;
            }

            // 数字字段处理
            if (in_array($f, $number_fields)) {
                if ($value === '' || $value === null) {
                    $value = null;
                } else {
                    // 对于小数字段，确保格式正确
                    if (in_array($f, ['cost', 'budget'])) {
                        $value = is_numeric($value) ? floatval($value) : null;
                    } else {
                        // 对于整数字段
                        $value = is_numeric($value) ? intval($value) : null;
                    }
                }
            }

            // 处理下拉框的"请选择"选项
            if ($value === '请选择') {
                $value = null;
            }

            $data[$f] = $value;
            $set[] = "$f = :$f";
        }
    }

    if (empty($set)) {
        echo json_encode(['success' => false, 'msg' => '无可更新字段']);
        exit;
    }

    $data['patent_case_info_id'] = $patent_id;
    $sql = "UPDATE patent_case_extend_info SET " . implode(',', $set) . " WHERE patent_case_info_id = :patent_case_info_id";

    try {
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
<form id="edit-patent-extend-form" class="module-form" autocomplete="off">
    <table class="module-table" style="width:100%;max-width:1800px;table-layout:fixed;">
        <colgroup>
            <col style="width:180px;">
            <col style="width:220px;">
            <col style="width:180px;">
            <col style="width:220px;">
            <col style="width:180px;">
            <col style="width:220px;">
        </colgroup>
        <tr>
            <td class="module-label">原案申请号</td>
            <td><input type="text" name="original_application_no" class="module-input" value="<?= h($extend['original_application_no']) ?>"></td>
            <td class="module-label">PCT申请号</td>
            <td><input type="text" name="pct_application_no" class="module-input" value="<?= h($extend['pct_application_no']) ?>"></td>
            <td class="module-label">独立权利要求项数</td>
            <td><input type="number" name="independent_claim_count" class="module-input" style="background:#fff;" value="<?= h($extend['independent_claim_count']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">原案申请日</td>
            <td><input type="date" name="original_application_date" class="module-input" value="<?= h($extend['original_application_date']) ?>"></td>
            <td class="module-label">PCT申请日</td>
            <td><input type="date" name="pct_application_date" class="module-input" value="<?= h($extend['pct_application_date']) ?>"></td>
            <td class="module-label">权利要求项数</td>
            <td><input type="number" name="claim_count" class="module-input" style="background:#fff;" value="<?= h($extend['claim_count']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">复审无效案件编号</td>
            <td><input type="text" name="reexamination_invalid_case_no" class="module-input" value="<?= h($extend['reexamination_invalid_case_no']) ?>"></td>
            <td class="module-label">PCT公开号</td>
            <td><input type="text" name="pct_publication_no" class="module-input" value="<?= h($extend['pct_publication_no']) ?>"></td>
            <td class="module-label">说明书(包括附图)页数</td>
            <td><input type="number" name="specification_page_count" class="module-input" style="background:#fff;" value="<?= h($extend['specification_page_count']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">临时申请号</td>
            <td><input type="text" name="temporary_application_no" class="module-input" value="<?= h($extend['temporary_application_no']) ?>"></td>
            <td class="module-label">PCT公布日</td>
            <td><input type="date" name="pct_publication_date" class="module-input" value="<?= h($extend['pct_publication_date']) ?>"></td>
            <td class="module-label">外观设计图片幅数</td>
            <td><input type="number" name="design_image_count" class="module-input" style="background:#fff;" value="<?= h($extend['design_image_count']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">临时申请日</td>
            <td><input type="date" name="temporary_application_date" class="module-input" value="<?= h($extend['temporary_application_date']) ?>"></td>
            <td class="module-label">PCT公布语言</td>
            <td>
                <select name="pct_publication_language" class="module-input">
                    <?php foreach ($pct_languages as $lang): ?>
                        <option value="<?= h($lang) ?>" <?= $extend['pct_publication_language'] == $lang ? 'selected' : '' ?>><?= h($lang) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="module-label">说明书字数</td>
            <td><input type="number" name="specification_word_count" class="module-input" style="background:#fff;" value="<?= h($extend['specification_word_count']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">申请人文号</td>
            <td><input type="text" name="applicant_reference_no" class="module-input" value="<?= h($extend['applicant_reference_no']) ?>"></td>
            <td class="module-label">国际检索单位</td>
            <td><input type="text" name="international_search_unit" class="module-input" value="<?= h($extend['international_search_unit']) ?>"></td>
            <td class="module-label">国际检索完成日</td>
            <td><input type="date" name="international_search_complete_date" class="module-input" value="<?= h($extend['international_search_complete_date']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">Art Unit</td>
            <td><input type="text" name="art_unit" class="module-input" value="<?= h($extend['art_unit']) ?>"></td>
            <td class="module-label">Confirmation Number</td>
            <td><input type="text" name="confirmation_number" class="module-input" value="<?= h($extend['confirmation_number']) ?>"></td>
            <td class="module-label">是否首次申请</td>
            <td>
                <label><input type="radio" name="is_first_application" value="1" <?= $extend['is_first_application'] == 1 ? 'checked' : '' ?>> 是</label>
                <label><input type="radio" name="is_first_application" value="0" <?= ($extend['is_first_application'] === '0' || $extend['is_first_application'] === null) ? 'checked' : '' ?>> 否</label>
            </td>
        </tr>
        <tr>
            <td class="module-label">进入国家阶段日期</td>
            <td><input type="date" name="enter_national_phase_date" class="module-input" value="<?= h($extend['enter_national_phase_date']) ?>"></td>
            <td class="module-label">办登手续阶段</td>
            <td>
                <select name="registration_procedure_stage" class="module-input">
                    <?php foreach ($procedure_stages as $stage): ?>
                        <option value="<?= h($stage) ?>" <?= $extend['registration_procedure_stage'] == $stage ? 'selected' : '' ?>><?= h($stage) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="module-label">DAS接入码</td>
            <td><input type="text" name="das_access_code" class="module-input" value="<?= h($extend['das_access_code']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">授权发文日</td>
            <td><input type="date" name="grant_notice_date" class="module-input" value="<?= h($extend['grant_notice_date']) ?>"></td>
            <td class="module-label">新申请递交日</td>
            <td><input type="date" name="new_application_submit_date" class="module-input" value="<?= h($extend['new_application_submit_date']) ?>"></td>
            <td class="module-label">案件系数</td>
            <td>
                <select name="case_coefficient" class="module-input">
                    <?php foreach ($case_coefficient_options as $option): ?>
                        <option value="<?= h($option) ?>" <?= $extend['case_coefficient'] == $option ? 'selected' : '' ?>><?= h($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="module-label">协办所</td>
            <td><input type="text" name="cooperation_agency" class="module-input" value="<?= h($extend['cooperation_agency']) ?>"></td>
            <td class="module-label">协办所案号</td>
            <td><input type="text" name="cooperation_agency_case_no" class="module-input" value="<?= h($extend['cooperation_agency_case_no']) ?>"></td>
            <td class="module-label">授权日</td>
            <td><input type="date" name="grant_date" class="module-input" value="<?= h($extend['grant_date']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">外部案源人</td>
            <td><input type="text" name="external_source_person" class="module-input" value="<?= h($extend['external_source_person']) ?>"></td>
            <td class="module-label">内部案源人</td>
            <td><input type="text" name="internal_source_person" class="module-input" value="<?= h($extend['internal_source_person']) ?>"></td>
            <td class="module-label">所属分部</td>
            <td><?php render_select_search('department_id', $departments_options, $extend['department_id']); ?></td>
        </tr>
        <tr>
            <td class="module-label">成本</td>
            <td><input type="number" step="0.01" name="cost" class="module-input" style="background:#fff;" value="<?= h($extend['cost']) ?>"></td>
            <td class="module-label">预算</td>
            <td><input type="number" step="0.01" name="budget" class="module-input" style="background:#fff;" value="<?= h($extend['budget']) ?>"></td>
            <td class="module-label">延迟审查</td>
            <td>
                <select name="deferred_examination" class="module-input">
                    <?php foreach ($deferred_options as $option): ?>
                        <option value="<?= h($option) ?>" <?= $extend['deferred_examination'] == $option ? 'selected' : '' ?>><?= h($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
</form>
<script>
    function initPatentTabEvents() {
        // 保存按钮
        document.querySelectorAll('#patent-tab-content .btn-save').forEach(function(btnSave) {
            btnSave.onclick = function() {
                var form = document.getElementById('edit-patent-extend-form');
                var fd = new FormData(form);
                fd.append('action', 'save_extend');

                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/patent_management/edit_tabs/extend.php?patent_id=<?= $patent_id ?>', true);
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
                    xhr.open('GET', 'modules/patent_management/edit_tabs/extend.php?patent_id=<?= $patent_id ?>', true);
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