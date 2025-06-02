<?php
// 专利编辑-申请人tab
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

if (!isset($_GET['patent_id']) || intval($_GET['patent_id']) <= 0) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未指定专利ID</div>';
    exit;
}
$patent_id = intval($_GET['patent_id']);

// 验证专利是否存在
$patent_stmt = $pdo->prepare("SELECT id FROM patent_case_info WHERE id = ?");
$patent_stmt->execute([$patent_id]);
if (!$patent_stmt->fetch()) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未找到该专利信息</div>';
    exit;
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'get_applicant_list') {
        try {
            // 获取申请人列表
            $sql = "SELECT * FROM patent_case_applicant WHERE patent_case_info_id = ? ORDER BY id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$patent_id]);
            $rows = $stmt->fetchAll();

            $html = '';
            if (empty($rows)) {
                $html = '<tr><td colspan="8" style="text-align:center;padding:20px 0;">暂无申请人数据</td></tr>';
            } else {
                foreach ($rows as $index => $a) {
                    $area = htmlspecialchars(($a['province'] ?? '') . ($a['city_cn'] ? ' ' . $a['city_cn'] : '') . ($a['district'] ? ' ' . $a['district'] : ''));
                    $html .= '<tr data-id="' . $a['id'] . '">' .
                        '<td style="text-align:center;">' . ($index + 1) . '</td>' .
                        '<td>' . htmlspecialchars($a['name_cn'] ?? '') . '</td>' .
                        '<td>' . htmlspecialchars($a['applicant_type'] ?? '') . '</td>' .
                        '<td>' . htmlspecialchars($a['entity_type'] ?? '') . '</td>' .
                        '<td>' . $area . '</td>' .
                        '<td>' . htmlspecialchars($a['phone'] ?? '') . '</td>' .
                        '<td style="text-align:center;">' . ($a['is_first_contact'] ? '是' : '否') . '</td>' .
                        '<td style="text-align:center;">' .
                        '<button type="button" class="btn-mini btn-edit">✎</button>' .
                        '<button type="button" class="btn-mini btn-del" style="color:#f44336;">✖</button>' .
                        '</td>' .
                        '</tr>';
                }
            }

            echo json_encode([
                'success' => true,
                'html' => $html
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_applicant') {
        try {
            // 获取单个申请人信息
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的申请人ID']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM patent_case_applicant WHERE id = ? AND patent_case_info_id = ?");
            $stmt->execute([$id, $patent_id]);
            $data = $stmt->fetch();

            if (!$data) {
                echo json_encode(['success' => false, 'msg' => '未找到申请人信息']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'save_applicant') {
        try {
            // 保存申请人信息
            $id = intval($_POST['id'] ?? 0);

            // 字段列表
            $fields = [
                'case_type',
                'applicant_type',
                'entity_type',
                'name_cn',
                'name_en',
                'name_xing_cn',
                'name_xing_en',
                'is_first_contact',
                'is_receipt_title',
                'receipt_title',
                'credit_code',
                'contact_person',
                'phone',
                'email',
                'province',
                'city_cn',
                'city_en',
                'district',
                'postcode',
                'address_cn',
                'address_en',
                'department_cn',
                'department_en',
                'id_type',
                'id_number',
                'is_fee_reduction',
                'fee_reduction_start',
                'fee_reduction_end',
                'fee_reduction_code',
                'cn_agent_code',
                'pct_agent_code',
                'is_fee_monitor',
                'country',
                'nationality',
                'business_license',
                'remark'
            ];

            $data = ['patent_case_info_id' => $patent_id];

            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $value = $_POST[$field];

                    // 日期字段处理
                    $date_fields = ['fee_reduction_start', 'fee_reduction_end'];
                    if (in_array($field, $date_fields) && $value === '') {
                        $value = null;
                    }

                    // 布尔字段处理
                    $bool_fields = ['is_first_contact', 'is_receipt_title', 'is_fee_reduction', 'is_fee_monitor'];
                    if (in_array($field, $bool_fields)) {
                        $value = intval($value) ? 1 : 0;
                    }

                    $data[$field] = $value;
                }
            }

            if ($id > 0) {
                // 更新
                $set = [];
                foreach ($data as $key => $value) {
                    if ($key !== 'patent_case_info_id') {
                        $set[] = "$key = :$key";
                    }
                }
                $data['id'] = $id;
                $sql = "UPDATE patent_case_applicant SET " . implode(',', $set) . " WHERE id = :id AND patent_case_info_id = :patent_case_info_id";
            } else {
                // 新增
                $keys = array_keys($data);
                $placeholders = ':' . implode(', :', $keys);
                $sql = "INSERT INTO patent_case_applicant (" . implode(',', $keys) . ") VALUES ($placeholders)";
            }

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($data);

            if ($result) {
                echo json_encode(['success' => true, 'msg' => '保存成功']);
            } else {
                echo json_encode(['success' => false, 'msg' => '保存失败']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '保存失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_applicant') {
        try {
            // 删除申请人
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的申请人ID']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM patent_case_applicant WHERE id = ? AND patent_case_info_id = ?");
            $result = $stmt->execute([$id, $patent_id]);

            if ($result) {
                echo json_encode(['success' => true, 'msg' => '删除成功']);
            } else {
                echo json_encode(['success' => false, 'msg' => '删除失败']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '删除失败：' . $e->getMessage()]);
        }
        exit;
    }

    // 发明人相关操作
    if ($action === 'get_inventor_list') {
        try {
            // 获取发明人列表
            $sql = "SELECT * FROM patent_case_inventor WHERE patent_case_info_id = ? ORDER BY id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$patent_id]);
            $rows = $stmt->fetchAll();

            $html = '';
            if (empty($rows)) {
                $html = '<tr><td colspan="7" style="text-align:center;padding:20px 0;">暂无发明人数据</td></tr>';
            } else {
                foreach ($rows as $index => $i) {
                    $area = htmlspecialchars(($i['province'] ?? '') . ($i['city_cn'] ? ' ' . $i['city_cn'] : ''));
                    $html .= '<tr data-id="' . $i['id'] . '">' .
                        '<td style="text-align:center;">' . ($index + 1) . '</td>' .
                        '<td>' . htmlspecialchars($i['name_cn'] ?? '') . '</td>' .
                        '<td>' . htmlspecialchars($i['name_en'] ?? '') . '</td>' .
                        '<td>' . htmlspecialchars($i['nationality'] ?? '') . '</td>' .
                        '<td>' . $area . '</td>' .
                        '<td style="text-align:center;">' . ($i['is_tech_contact'] ? '是' : '否') . '</td>' .
                        '<td style="text-align:center;">' .
                        '<button type="button" class="btn-mini btn-edit">✎</button>' .
                        '<button type="button" class="btn-mini btn-del" style="color:#f44336;">✖</button>' .
                        '</td>' .
                        '</tr>';
                }
            }

            echo json_encode([
                'success' => true,
                'html' => $html
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_inventor') {
        try {
            // 获取单个发明人信息
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的发明人ID']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM patent_case_inventor WHERE id = ? AND patent_case_info_id = ?");
            $stmt->execute([$id, $patent_id]);
            $data = $stmt->fetch();

            if (!$data) {
                echo json_encode(['success' => false, 'msg' => '未找到发明人信息']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'save_inventor') {
        try {
            // 保存发明人信息
            $id = intval($_POST['id'] ?? 0);

            // 字段列表
            $fields = [
                'name_cn',
                'name_en',
                'job_no',
                'xing_cn',
                'xing_en',
                'ming_cn',
                'ming_en',
                'nationality',
                'country',
                'is_tech_contact',
                'province',
                'city_cn',
                'city_en',
                'address_cn',
                'address_en',
                'department_cn',
                'department_en',
                'email',
                'id_number',
                'phone',
                'qq',
                'mobile',
                'postcode',
                'remark'
            ];

            $data = ['patent_case_info_id' => $patent_id];

            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $value = $_POST[$field];

                    // 布尔字段处理
                    if ($field === 'is_tech_contact') {
                        $value = intval($value) ? 1 : 0;
                    }

                    $data[$field] = $value;
                }
            }

            if ($id > 0) {
                // 更新
                $set = [];
                foreach ($data as $key => $value) {
                    if ($key !== 'patent_case_info_id') {
                        $set[] = "$key = :$key";
                    }
                }
                $data['id'] = $id;
                $sql = "UPDATE patent_case_inventor SET " . implode(',', $set) . " WHERE id = :id AND patent_case_info_id = :patent_case_info_id";
            } else {
                // 新增
                $keys = array_keys($data);
                $placeholders = ':' . implode(', :', $keys);
                $sql = "INSERT INTO patent_case_inventor (" . implode(',', $keys) . ") VALUES ($placeholders)";
            }

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($data);

            if ($result) {
                echo json_encode(['success' => true, 'msg' => '保存成功']);
            } else {
                echo json_encode(['success' => false, 'msg' => '保存失败']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '保存失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_inventor') {
        try {
            // 删除发明人
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的发明人ID']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM patent_case_inventor WHERE id = ? AND patent_case_info_id = ?");
            $result = $stmt->execute([$id, $patent_id]);

            if ($result) {
                echo json_encode(['success' => true, 'msg' => '删除成功']);
            } else {
                echo json_encode(['success' => false, 'msg' => '删除失败']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '删除失败：' . $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'msg' => '未知操作']);
    exit;
}

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-add-applicant"><i class="icon-add"></i> 新增申请人</button>
    </div>

    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:40px;text-align:center;">序号</th>
                <th style="width:120px;">申请人(中文)</th>
                <th style="width:100px;">申请人类型</th>
                <th style="width:80px;">实体类型</th>
                <th style="width:120px;">所属地区</th>
                <th style="width:100px;">联系电话</th>
                <th style="width:80px;">第一联系人</th>
                <th style="width:90px;">操作</th>
            </tr>
        </thead>
        <tbody id="applicant-list">
            <tr>
                <td colspan="8" style="text-align:center;padding:20px 0;">正在加载数据...</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 申请人编辑弹窗 -->
<div id="edit-applicant-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:950px;max-width:98vw;max-height:80vh;position:relative;display:flex;flex-direction:column;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="edit-applicant-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;" id="modal-title">编辑申请人</h3>
        <div style="flex:1 1 auto;overflow-y:auto;">
            <form id="edit-applicant-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <table class="module-table" style="table-layout:fixed;width:100%;min-width:0;">
                    <colgroup>
                        <col style="width:120px;">
                        <col style="width:320px;">
                        <col style="width:120px;">
                        <col style="width:320px;">
                    </colgroup>
                    <tr>
                        <td class="module-label module-req">*名称(中文)</td>
                        <td><input type="text" name="name_cn" class="module-input" required></td>
                        <td class="module-label">名称(英文)</td>
                        <td><input type="text" name="name_en" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label module-req">*申请人类型</td>
                        <td>
                            <select name="applicant_type" class="module-input" required>
                                <option value="">--请选择--</option>
                                <option value="大专院校">大专院校</option>
                                <option value="科研单位">科研单位</option>
                                <option value="事业单位">事业单位</option>
                                <option value="工矿企业">工矿企业</option>
                                <option value="个人">个人</option>
                            </select>
                        </td>
                        <td class="module-label module-req">*实体类型</td>
                        <td>
                            <select name="entity_type" class="module-input" required>
                                <option value="">--请选择--</option>
                                <option value="大实体">大实体</option>
                                <option value="小实体">小实体</option>
                                <option value="微实体">微实体</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">案件类型</td>
                        <td>
                            <label style="margin-right:18px;"><input type="checkbox" name="case_type_patent" value="专利"> 专利</label>
                            <label style="margin-right:18px;"><input type="checkbox" name="case_type_trademark" value="商标"> 商标</label>
                            <label style="margin-right:18px;"><input type="checkbox" name="case_type_copyright" value="版权"> 版权</label>
                            <input type="hidden" name="case_type" value="">
                        </td>
                        <td class="module-label">名称/姓(中文)</td>
                        <td><input type="text" name="name_xing_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">名称/姓(英文)</td>
                        <td><input type="text" name="name_xing_en" class="module-input"></td>
                        <td class="module-label">电话</td>
                        <td><input type="text" name="phone" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">邮件</td>
                        <td><input type="email" name="email" class="module-input"></td>
                        <td class="module-label">省份</td>
                        <td><input type="text" name="province" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">城市(中文)</td>
                        <td><input type="text" name="city_cn" class="module-input"></td>
                        <td class="module-label">城市(英文)</td>
                        <td><input type="text" name="city_en" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">行政区划</td>
                        <td><input type="text" name="district" class="module-input"></td>
                        <td class="module-label">邮编</td>
                        <td><input type="text" name="postcode" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">街道地址(中文)</td>
                        <td><input type="text" name="address_cn" class="module-input"></td>
                        <td class="module-label">街道地址(英文)</td>
                        <td><input type="text" name="address_en" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">部门/楼层(中文)</td>
                        <td><input type="text" name="department_cn" class="module-input"></td>
                        <td class="module-label">部门/楼层(英文)</td>
                        <td><input type="text" name="department_en" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">证件类型</td>
                        <td>
                            <select name="id_type" class="module-input">
                                <option value="">--请选择--</option>
                                <option value="居民身份证">居民身份证</option>
                                <option value="护照">护照</option>
                                <option value="营业执照">营业执照</option>
                                <option value="其他">其他</option>
                            </select>
                        </td>
                        <td class="module-label">证件号</td>
                        <td><input type="text" name="id_number" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">费用减案</td>
                        <td>
                            <select name="is_fee_reduction" class="module-input">
                                <option value="0">否</option>
                                <option value="1">是</option>
                            </select>
                        </td>
                        <td class="module-label">费用减案有效期</td>
                        <td>
                            <input type="date" name="fee_reduction_start" class="module-input" style="width:48%;display:inline-block;"> -
                            <input type="date" name="fee_reduction_end" class="module-input" style="width:48%;display:inline-block;">
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">备案证件号</td>
                        <td><input type="text" name="fee_reduction_code" class="module-input"></td>
                        <td class="module-label">中国总委托编号</td>
                        <td><input type="text" name="cn_agent_code" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">PCT总委托编号</td>
                        <td><input type="text" name="pct_agent_code" class="module-input"></td>
                        <td class="module-label">监控年费</td>
                        <td>
                            <select name="is_fee_monitor" class="module-input">
                                <option value="0">否</option>
                                <option value="1">是</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">国家(地区)</td>
                        <td><input type="text" name="country" class="module-input"></td>
                        <td class="module-label">国籍</td>
                        <td><input type="text" name="nationality" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">营业执照</td>
                        <td><input type="text" name="business_license" class="module-input"></td>
                        <td class="module-label">是否第一联系人</td>
                        <td>
                            <label><input type="checkbox" name="is_first_contact" value="1"> 是</label>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">作为收据抬头</td>
                        <td>
                            <label><input type="checkbox" name="is_receipt_title" value="1" id="is_receipt_title_cb"> 是</label>
                        </td>
                        <td class="module-label">联系人</td>
                        <td><input type="text" name="contact_person" class="module-input"></td>
                    </tr>
                    <tr id="receipt_title_row" style="display:none;">
                        <td class="module-label">申请人收据抬头</td>
                        <td><input type="text" name="receipt_title" class="module-input"></td>
                        <td class="module-label">申请人统一社会信用代码</td>
                        <td><input type="text" name="credit_code" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">备注</td>
                        <td colspan="3"><textarea name="remark" class="module-input" style="min-height:48px;width:100%;"></textarea></td>
                    </tr>
                    <tr>
                        <td class="module-label">上传文件</td>
                        <td colspan="3">
                            <div style="margin-bottom:8px;">
                                <label>费减证明：</label>
                                <input type="text" id="file-name-fee-reduction" placeholder="文件命名（可选）" style="width:120px;">
                                <input type="file" id="file-feijian" style="display:inline-block;width:auto;">
                                <button type="button" class="btn-mini" id="btn-upload-feijian">上传</button>
                                <div id="feijian-file-list" style="margin-top:4px;"></div>
                            </div>
                            <div style="margin-bottom:8px;">
                                <label>总委托书：</label>
                                <input type="text" id="file-name-power" placeholder="文件命名（可选）" style="width:120px;">
                                <input type="file" id="file-weituoshu" style="display:inline-block;width:auto;">
                                <button type="button" class="btn-mini" id="btn-upload-weituoshu">上传</button>
                                <div id="weituoshu-file-list" style="margin-top:4px;"></div>
                            </div>
                            <div>
                                <label>附件：</label>
                                <input type="text" id="file-name-attach" placeholder="文件命名（可选，所有文件同名）" style="width:120px;">
                                <input type="file" id="file-fujian" multiple style="display:inline-block;width:auto;">
                                <button type="button" class="btn-mini" id="btn-upload-fujian">上传</button>
                                <div id="fujian-file-list" style="margin-top:4px;"></div>
                            </div>
                        </td>
                    </tr>
                </table>
                <div style="text-align:center;margin-top:12px;">
                    <button type="button" class="btn-save-edit-applicant btn-mini" style="margin-right:16px;">保存</button>
                    <button type="button" class="btn-cancel-edit-applicant btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 发明人管理区域 -->
<div class="module-panel" style="margin-top:20px;">
    <div class="module-btns">
        <button type="button" class="btn-add-inventor"><i class="icon-add"></i> 新增发明人</button>
    </div>

    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:40px;text-align:center;">序号</th>
                <th style="width:120px;">中文名</th>
                <th style="width:120px;">英文名</th>
                <th style="width:80px;">国籍</th>
                <th style="width:120px;">所属地区</th>
                <th style="width:80px;">技术联系人</th>
                <th style="width:90px;">操作</th>
            </tr>
        </thead>
        <tbody id="inventor-list">
            <tr>
                <td colspan="7" style="text-align:center;padding:20px 0;">正在加载数据...</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 发明人编辑弹窗 -->
<div id="edit-inventor-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:950px;max-width:98vw;max-height:80vh;position:relative;display:flex;flex-direction:column;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="edit-inventor-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;" id="inventor-modal-title">编辑发明人</h3>
        <div style="flex:1 1 auto;overflow-y:auto;">
            <form id="edit-inventor-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <table class="module-table" style="table-layout:fixed;width:100%;min-width:0;">
                    <colgroup>
                        <col style="width:120px;">
                        <col style="width:320px;">
                        <col style="width:120px;">
                        <col style="width:320px;">
                    </colgroup>
                    <tr>
                        <td class="module-label module-req">*中文名</td>
                        <td><input type="text" name="name_cn" class="module-input" required></td>
                        <td class="module-label">英文名</td>
                        <td><input type="text" name="name_en" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">工号</td>
                        <td><input type="text" name="job_no" class="module-input"></td>
                        <td class="module-label">名称/姓(中文)</td>
                        <td><input type="text" name="xing_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">名称/姓(英文)</td>
                        <td><input type="text" name="xing_en" class="module-input"></td>
                        <td class="module-label">名(中文)</td>
                        <td><input type="text" name="ming_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">名(英文)</td>
                        <td><input type="text" name="ming_en" class="module-input"></td>
                        <td class="module-label">国籍</td>
                        <td><input type="text" name="nationality" class="module-input" value="中国"></td>
                    </tr>
                    <tr>
                        <td class="module-label">国家(地区)</td>
                        <td><input type="text" name="country" class="module-input" value="中国"></td>
                        <td class="module-label">是否为技术联系人</td>
                        <td>
                            <select name="is_tech_contact" class="module-input">
                                <option value="0">否</option>
                                <option value="1">是</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">省份</td>
                        <td><input type="text" name="province" class="module-input"></td>
                        <td class="module-label">城市(中文)</td>
                        <td><input type="text" name="city_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">城市(英文)</td>
                        <td><input type="text" name="city_en" class="module-input"></td>
                        <td class="module-label">街道地址(中文)</td>
                        <td><input type="text" name="address_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">街道地址(英文)</td>
                        <td><input type="text" name="address_en" class="module-input"></td>
                        <td class="module-label">部门/楼层(中文)</td>
                        <td><input type="text" name="department_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">部门/楼层(英文)</td>
                        <td><input type="text" name="department_en" class="module-input"></td>
                        <td class="module-label">邮件</td>
                        <td><input type="email" name="email" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">证件号码</td>
                        <td><input type="text" name="id_number" class="module-input"></td>
                        <td class="module-label">座机</td>
                        <td><input type="text" name="phone" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">QQ</td>
                        <td><input type="text" name="qq" class="module-input"></td>
                        <td class="module-label">手机</td>
                        <td><input type="text" name="mobile" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">邮编</td>
                        <td><input type="text" name="postcode" class="module-input"></td>
                        <td class="module-label">备注</td>
                        <td><textarea name="remark" class="module-input" style="min-height:48px;"></textarea></td>
                    </tr>
                </table>
                <div style="text-align:center;margin-top:12px;">
                    <button type="button" class="btn-save-edit-inventor btn-mini" style="margin-right:16px;">保存</button>
                    <button type="button" class="btn-cancel-edit-inventor btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function() {
        var patentId = <?= $patent_id ?>;
        var btnAddApplicant = document.querySelector('.btn-add-applicant'),
            applicantList = document.getElementById('applicant-list');

        // 将deleteFile函数移到全局作用域
        window.deleteFile = function(fileId, fileType, applicantId, listDivId) {
            if (!confirm('确定要删除这个文件吗？')) {
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/edit_tabs/applicant_file_upload.php?patent_id=' + patentId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert('文件删除成功');
                        renderFileList(applicantId, fileType, listDivId);
                    } else {
                        alert('删除失败：' + (response.message || '未知错误'));
                    }
                } catch (e) {
                    console.error('删除响应解析错误:', e);
                    alert('删除失败：响应解析错误');
                }
            };
            xhr.send('action=delete&file_id=' + fileId);
        };

        function loadApplicantData() {
            applicantList.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';

            var formData = new FormData();
            formData.append('action', 'get_applicant_list');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/edit_tabs/applicant.php?patent_id=' + patentId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                applicantList.innerHTML = response.html;
                                bindTableRowClick();
                            } else {
                                applicantList.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px 0;">加载数据失败：' + (response.msg || '') + '</td></tr>';
                            }
                        } catch (e) {
                            applicantList.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px 0;">加载数据失败：解析响应错误</td></tr>';
                        }
                    } else {
                        applicantList.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send(formData);
        }

        function bindTableRowClick() {
            applicantList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.querySelector('.btn-del').onclick = function() {
                    if (!confirm('确定删除该申请人？')) return;
                    var id = row.getAttribute('data-id');
                    var formData = new FormData();
                    formData.append('action', 'delete_applicant');
                    formData.append('id', id);
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'modules/patent_management/edit_tabs/applicant.php?patent_id=' + patentId, true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                loadApplicantData();
                            } else {
                                alert('删除失败：' + (res.msg || ''));
                            }
                        } catch (e) {
                            alert('删除失败：响应解析错误');
                        }
                    };
                    xhr.send(formData);
                };

                row.querySelector('.btn-edit').onclick = function() {
                    var id = row.getAttribute('data-id');
                    openApplicantModal(id);
                };
            });
        }

        function openApplicantModal(id) {
            var modal = document.getElementById('edit-applicant-modal');
            var form = document.getElementById('edit-applicant-form');
            var modalTitle = document.getElementById('modal-title');

            form.reset();

            // 清空文件列表
            clearFileList();

            if (id && id !== '0') {
                // 编辑模式
                modalTitle.textContent = '编辑申请人';
                var formData = new FormData();
                formData.append('action', 'get_applicant');
                formData.append('id', id);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/patent_management/edit_tabs/applicant.php?patent_id=' + patentId, true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success && res.data) {
                            for (var k in res.data) {
                                if (form[k] !== undefined && form[k].type !== 'checkbox') {
                                    form[k].value = res.data[k] !== null ? res.data[k] : '';
                                }
                            }

                            // 多选案件类型
                            if (res.data.case_type) {
                                var arr = res.data.case_type.split(',');
                                form.querySelectorAll('input[type=checkbox][name^=case_type_]').forEach(function(cb) {
                                    cb.checked = arr.indexOf(cb.value) !== -1;
                                });
                            } else {
                                form.querySelectorAll('input[type=checkbox][name^=case_type_]').forEach(function(cb) {
                                    cb.checked = false;
                                });
                            }

                            form.is_first_contact.checked = res.data.is_first_contact == 1;
                            form.is_receipt_title.checked = res.data.is_receipt_title == 1;
                            document.getElementById('receipt_title_row').style.display = form.is_receipt_title.checked ? '' : 'none';

                            modal.style.display = 'flex';

                            // 绑定文件上传功能并加载文件列表
                            bindFileUpload(id, true);
                        } else {
                            alert('获取数据失败：' + (res.msg || ''));
                        }
                    } catch (e) {
                        alert('获取数据失败：响应解析错误');
                    }
                };
                xhr.send(formData);
            } else {
                // 新增模式
                modalTitle.textContent = '新增申请人';
                form.id.value = '0';
                modal.style.display = 'flex';

                // 绑定文件上传功能但不加载文件列表
                bindFileUpload(0, false);
            }
        }

        // 清空文件列表
        function clearFileList() {
            document.getElementById('feijian-file-list').innerHTML = '';
            document.getElementById('weituoshu-file-list').innerHTML = '';
            document.getElementById('fujian-file-list').innerHTML = '';

            // 清空文件命名输入框
            document.getElementById('file-name-fee-reduction').value = '';
            document.getElementById('file-name-power').value = '';
            document.getElementById('file-name-attach').value = '';

            // 清空文件选择框
            document.getElementById('file-feijian').value = '';
            document.getElementById('file-weituoshu').value = '';
            document.getElementById('file-fujian').value = '';
        }

        // 渲染文件列表
        function renderFileList(applicantId, fileType, listDivId) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/edit_tabs/applicant_file_upload.php?patent_id=' + patentId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        let html = '<table style="width:100%; border-collapse:collapse; margin-top:10px;">';
                        html += '<tr style="background:#f5f5f5;"><th style="border:1px solid #ddd; padding:5px;">文件名</th><th style="border:1px solid #ddd; padding:5px;">大小</th><th style="border:1px solid #ddd; padding:5px;">上传时间</th><th style="border:1px solid #ddd; padding:5px;">操作</th></tr>';

                        if (response.files && response.files.length > 0) {
                            response.files.forEach(function(file) {
                                const fileSize = file.file_size ? (file.file_size / 1024).toFixed(1) + ' KB' : '未知';
                                const uploadTime = file.created_at ? file.created_at.substring(0, 16) : '';
                                html += '<tr>';
                                html += '<td style="border:1px solid #ddd; padding:5px;">' + (file.file_name || '') + '</td>';
                                html += '<td style="border:1px solid #ddd; padding:5px;">' + fileSize + '</td>';
                                html += '<td style="border:1px solid #ddd; padding:5px;">' + uploadTime + '</td>';
                                html += '<td style="border:1px solid #ddd; padding:5px;">';
                                html += '<a href="' + file.file_path + '" target="_blank" download="' + (file.file_name || '') + '" style="margin-right:10px;" class="btn-mini">下载</a>';
                                html += '<a href="javascript:void(0)" onclick="deleteFile(' + file.id + ', \'' + fileType + '\', ' + applicantId + ', \'' + listDivId + '\')" style="color:red;" class="btn-mini">删除</a>';
                                html += '</td>';
                                html += '</tr>';
                            });
                        } else {
                            html += '<tr><td colspan="4" style="border:1px solid #ddd; padding:10px; text-align:center; color:#999;">暂无文件</td></tr>';
                        }
                        html += '</table>';
                        document.getElementById(listDivId).innerHTML = html;
                    } else {
                        console.error('文件列表加载失败:', response.message || '未知错误');
                        document.getElementById(listDivId).innerHTML = '<div style="color:red; padding:10px;">文件列表加载失败</div>';
                    }
                } catch (e) {
                    console.error('文件列表解析错误:', e, '响应内容:', xhr.responseText);
                    document.getElementById(listDivId).innerHTML = '<div style="color:red; padding:10px;">文件列表解析错误</div>';
                }
            };
            xhr.send('action=list&patent_case_applicant_id=' + applicantId + '&file_type=' + encodeURIComponent(fileType));
        }

        // 绑定文件上传功能
        function bindFileUpload(applicantId, loadFileList) {
            // 费减证明上传
            document.getElementById('btn-upload-feijian').onclick = function() {
                if (applicantId === 0) {
                    alert('请先保存申请人信息后再上传文件');
                    return;
                }

                const fileInput = document.getElementById('file-feijian');
                const fileNameInput = document.getElementById('file-name-fee-reduction');
                if (!fileInput.files.length) {
                    alert('请选择文件');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'upload');
                formData.append('patent_case_applicant_id', applicantId);
                formData.append('file_type', '费减证明');
                formData.append('file', fileInput.files[0]);

                // 如果用户输入了自定义文件名，则使用自定义文件名
                if (fileNameInput.value.trim()) {
                    formData.append('custom_filename', fileNameInput.value.trim());
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/patent_management/edit_tabs/applicant_file_upload.php?patent_id=' + patentId, true);
                xhr.onload = function() {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('上传成功');
                            fileInput.value = '';
                            fileNameInput.value = ''; // 清空文件命名输入框
                            renderFileList(applicantId, '费减证明', 'feijian-file-list');
                        } else {
                            alert('上传失败：' + (response.message || '未知错误'));
                        }
                    } catch (e) {
                        console.error('上传响应解析错误:', e);
                        alert('上传失败：响应解析错误');
                    }
                };
                xhr.send(formData);
            };

            // 总委托书上传
            document.getElementById('btn-upload-weituoshu').onclick = function() {
                if (applicantId === 0) {
                    alert('请先保存申请人信息后再上传文件');
                    return;
                }

                const fileInput = document.getElementById('file-weituoshu');
                const fileNameInput = document.getElementById('file-name-power');
                if (!fileInput.files.length) {
                    alert('请选择文件');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'upload');
                formData.append('patent_case_applicant_id', applicantId);
                formData.append('file_type', '总委托书');
                formData.append('file', fileInput.files[0]);

                // 如果用户输入了自定义文件名，则使用自定义文件名
                if (fileNameInput.value.trim()) {
                    formData.append('custom_filename', fileNameInput.value.trim());
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/patent_management/edit_tabs/applicant_file_upload.php?patent_id=' + patentId, true);
                xhr.onload = function() {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('上传成功');
                            fileInput.value = '';
                            fileNameInput.value = ''; // 清空文件命名输入框
                            renderFileList(applicantId, '总委托书', 'weituoshu-file-list');
                        } else {
                            alert('上传失败：' + (response.message || '未知错误'));
                        }
                    } catch (e) {
                        console.error('上传响应解析错误:', e);
                        alert('上传失败：响应解析错误');
                    }
                };
                xhr.send(formData);
            };

            // 附件上传
            document.getElementById('btn-upload-fujian').onclick = function() {
                if (applicantId === 0) {
                    alert('请先保存申请人信息后再上传文件');
                    return;
                }

                const fileInput = document.getElementById('file-fujian');
                const fileNameInput = document.getElementById('file-name-attach');
                if (!fileInput.files.length) {
                    alert('请选择文件');
                    return;
                }

                // 处理多文件上传
                const files = Array.from(fileInput.files);
                let uploadCount = 0;
                let successCount = 0;
                let errorMessages = [];

                files.forEach(function(file, index) {
                    const formData = new FormData();
                    formData.append('action', 'upload');
                    formData.append('patent_case_applicant_id', applicantId);
                    formData.append('file_type', '附件');
                    formData.append('file', file);

                    // 如果用户输入了自定义文件名，则使用自定义文件名
                    // 对于多文件，如果有自定义文件名，会在文件名后加上序号
                    if (fileNameInput.value.trim()) {
                        let customName = fileNameInput.value.trim();
                        if (files.length > 1) {
                            // 多文件时在文件名后加序号
                            const ext = file.name.split('.').pop();
                            customName = customName + '_' + (index + 1) + '.' + ext;
                        } else {
                            // 单文件时保持原扩展名
                            const ext = file.name.split('.').pop();
                            if (!customName.includes('.')) {
                                customName = customName + '.' + ext;
                            }
                        }
                        formData.append('custom_filename', customName);
                    }

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'modules/patent_management/edit_tabs/applicant_file_upload.php?patent_id=' + patentId, true);
                    xhr.onload = function() {
                        uploadCount++;
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                successCount++;
                            } else {
                                errorMessages.push('文件 ' + file.name + ' 上传失败：' + (response.message || '未知错误'));
                            }
                        } catch (e) {
                            errorMessages.push('文件 ' + file.name + ' 上传失败：响应解析错误');
                        }

                        // 所有文件上传完成后显示结果
                        if (uploadCount === files.length) {
                            if (successCount === files.length) {
                                alert('所有文件上传成功');
                            } else if (successCount > 0) {
                                alert('部分文件上传成功 (' + successCount + '/' + files.length + ')：\n' + errorMessages.join('\n'));
                            } else {
                                alert('所有文件上传失败：\n' + errorMessages.join('\n'));
                            }

                            fileInput.value = '';
                            fileNameInput.value = ''; // 清空文件命名输入框
                            renderFileList(applicantId, '附件', 'fujian-file-list');
                        }
                    };
                    xhr.send(formData);
                });
            };

            // 如果是编辑模式且需要加载文件列表，则初始加载文件列表
            if (loadFileList && applicantId > 0) {
                renderFileList(applicantId, '费减证明', 'feijian-file-list');
                renderFileList(applicantId, '总委托书', 'weituoshu-file-list');
                renderFileList(applicantId, '附件', 'fujian-file-list');
            }
        }

        // 事件绑定
        btnAddApplicant.onclick = function() {
            openApplicantModal(0);
        };

        // 弹窗关闭
        document.getElementById('edit-applicant-modal-close').onclick = function() {
            document.getElementById('edit-applicant-modal').style.display = 'none';
        };

        document.querySelector('.btn-cancel-edit-applicant').onclick = function() {
            document.getElementById('edit-applicant-modal').style.display = 'none';
        };

        // 弹窗保存
        document.querySelector('.btn-save-edit-applicant').onclick = function() {
            var form = document.getElementById('edit-applicant-form');

            // 多选案件类型
            var checkedTypes = Array.from(form.querySelectorAll('input[type=checkbox][name^=case_type_]:checked')).map(function(cb) {
                return cb.value;
            });
            form.case_type.value = checkedTypes.join(',');

            form.is_first_contact.value = form.is_first_contact.checked ? 1 : 0;
            form.is_receipt_title.value = form.is_receipt_title.checked ? 1 : 0;

            if (!form.is_receipt_title.checked) {
                form.receipt_title.value = '';
                form.credit_code.value = '';
            }

            var formData = new FormData(form);
            formData.append('action', 'save_applicant');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/edit_tabs/applicant.php?patent_id=' + patentId, true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        document.getElementById('edit-applicant-modal').style.display = 'none';
                        loadApplicantData();
                    } else {
                        alert('保存失败：' + (res.msg || ''));
                    }
                } catch (e) {
                    alert('保存失败：响应解析错误');
                }
            };
            xhr.send(formData);
        };

        // 控制"作为收据抬头"显示隐藏
        var receiptCb = document.getElementById('is_receipt_title_cb');
        if (receiptCb) {
            receiptCb.addEventListener('change', function() {
                document.getElementById('receipt_title_row').style.display = receiptCb.checked ? '' : 'none';
            });
        }

        // 初始加载数据
        loadApplicantData();
    })();

    // 发明人管理功能
    (function() {
        var patentId = <?= $patent_id ?>;
        var btnAddInventor = document.querySelector('.btn-add-inventor'),
            inventorList = document.getElementById('inventor-list');

        function loadInventorData() {
            inventorList.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';

            var formData = new FormData();
            formData.append('action', 'get_inventor_list');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/edit_tabs/applicant.php?patent_id=' + patentId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                inventorList.innerHTML = response.html;
                                bindInventorTableRowClick();
                            } else {
                                inventorList.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px 0;">加载数据失败：' + (response.msg || '') + '</td></tr>';
                            }
                        } catch (e) {
                            inventorList.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px 0;">加载数据失败：解析响应错误</td></tr>';
                        }
                    } else {
                        inventorList.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send(formData);
        }

        function bindInventorTableRowClick() {
            inventorList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.querySelector('.btn-del').onclick = function() {
                    if (!confirm('确定删除该发明人？')) return;
                    var id = row.getAttribute('data-id');
                    var formData = new FormData();
                    formData.append('action', 'delete_inventor');
                    formData.append('id', id);
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'modules/patent_management/edit_tabs/applicant.php?patent_id=' + patentId, true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                loadInventorData();
                            } else {
                                alert('删除失败：' + (res.msg || ''));
                            }
                        } catch (e) {
                            alert('删除失败：响应解析错误');
                        }
                    };
                    xhr.send(formData);
                };

                row.querySelector('.btn-edit').onclick = function() {
                    var id = row.getAttribute('data-id');
                    openInventorModal(id);
                };
            });
        }

        function openInventorModal(id) {
            var modal = document.getElementById('edit-inventor-modal');
            var form = document.getElementById('edit-inventor-form');
            var modalTitle = document.getElementById('inventor-modal-title');

            form.reset();

            if (id && id !== '0') {
                // 编辑模式
                modalTitle.textContent = '编辑发明人';
                var formData = new FormData();
                formData.append('action', 'get_inventor');
                formData.append('id', id);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/patent_management/edit_tabs/applicant.php?patent_id=' + patentId, true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success && res.data) {
                            for (var k in res.data) {
                                if (form[k] !== undefined) {
                                    form[k].value = res.data[k] !== null ? res.data[k] : '';
                                }
                            }
                            modal.style.display = 'flex';
                        } else {
                            alert('获取数据失败：' + (res.msg || ''));
                        }
                    } catch (e) {
                        alert('获取数据失败：响应解析错误');
                    }
                };
                xhr.send(formData);
            } else {
                // 新增模式
                modalTitle.textContent = '新增发明人';
                form.id.value = '0';
                modal.style.display = 'flex';
            }
        }

        // 事件绑定
        btnAddInventor.onclick = function() {
            openInventorModal(0);
        };

        // 弹窗关闭
        document.getElementById('edit-inventor-modal-close').onclick = function() {
            document.getElementById('edit-inventor-modal').style.display = 'none';
        };

        document.querySelector('.btn-cancel-edit-inventor').onclick = function() {
            document.getElementById('edit-inventor-modal').style.display = 'none';
        };

        // 弹窗保存
        document.querySelector('.btn-save-edit-inventor').onclick = function() {
            var form = document.getElementById('edit-inventor-form');
            var formData = new FormData(form);
            formData.append('action', 'save_inventor');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/edit_tabs/applicant.php?patent_id=' + patentId, true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        document.getElementById('edit-inventor-modal').style.display = 'none';
                        loadInventorData();
                    } else {
                        alert('保存失败：' + (res.msg || ''));
                    }
                } catch (e) {
                    alert('保存失败：响应解析错误');
                }
            };
            xhr.send(formData);
        };

        // 初始加载发明人数据
        loadInventorData();
    })();
</script>