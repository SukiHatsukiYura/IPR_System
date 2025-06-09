<?php
// 版权编辑-申请人tab
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
            <tr class="module-table-header">
                <th class="col-40 text-center">序号</th>
                <th class="col-120">申请人(中文)</th>
                <th class="col-100">申请人类型</th>
                <th class="col-80">实体类型</th>
                <th class="col-120">所属地区</th>
                <th class="col-100">联系电话</th>
                <th class="col-80">第一联系人</th>
                <th class="col-90">操作</th>
            </tr>
        </thead>
        <tbody id="applicant-list">
            <tr>
                <td colspan="8" class="text-center module-loading">正在加载数据...</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 申请人编辑弹窗 -->
<div id="edit-applicant-modal" class="module-modal">
    <div class="module-modal-content">
        <div class="module-modal-close" id="edit-applicant-modal-close">×</div>
        <h3 class="module-modal-title" id="modal-title">编辑申请人</h3>
        <div class="module-modal-body">
            <form id="edit-applicant-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <table class="module-table module-table-fixed">
                    <colgroup>
                        <col class="col-120">
                        <col class="col-320">
                        <col class="col-120">
                        <col class="col-320">
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
                            <label class="module-checkbox-label"><input type="checkbox" name="case_type_patent" value="专利"> 专利</label>
                            <label class="module-checkbox-label"><input type="checkbox" name="case_type_trademark" value="商标"> 商标</label>
                            <label class="module-checkbox-label"><input type="checkbox" name="case_type_copyright" value="版权"> 版权</label>
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
                        <td class="module-date-range">
                            <input type="date" name="fee_reduction_start" class="module-input"> -
                            <input type="date" name="fee_reduction_end" class="module-input">
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
                    <tr id="receipt_title_row" class="hidden">
                        <td class="module-label">申请人收据抬头</td>
                        <td><input type="text" name="receipt_title" class="module-input"></td>
                        <td class="module-label">申请人统一社会信用代码</td>
                        <td><input type="text" name="credit_code" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">备注</td>
                        <td colspan="3"><textarea name="remark" class="module-textarea"></textarea></td>
                    </tr>
                    <tr>
                        <td class="module-label">上传文件</td>
                        <td colspan="3">
                            <div class="module-file-upload">
                                <label>费减证明：</label>
                                <input type="text" id="file-name-fee-reduction" placeholder="文件命名（可选）">
                                <input type="file" id="file-feijian">
                                <button type="button" class="btn-mini" id="btn-upload-feijian">上传</button>
                                <div id="feijian-file-list" class="module-file-list"></div>
                            </div>
                            <div class="module-file-upload">
                                <label>总委托书：</label>
                                <input type="text" id="file-name-power" placeholder="文件命名（可选）">
                                <input type="file" id="file-weituoshu">
                                <button type="button" class="btn-mini" id="btn-upload-weituoshu">上传</button>
                                <div id="weituoshu-file-list" class="module-file-list"></div>
                            </div>
                            <div class="module-file-upload">
                                <label>附件：</label>
                                <input type="text" id="file-name-attach" placeholder="文件命名（可选，所有文件同名）">
                                <input type="file" id="file-fujian" multiple>
                                <button type="button" class="btn-mini" id="btn-upload-fujian">上传</button>
                                <div id="fujian-file-list" class="module-file-list"></div>
                            </div>
                        </td>
                    </tr>
                </table>
                <div class="module-form-buttons">
                    <button type="button" class="btn-save-edit-applicant btn-mini">保存</button>
                    <button type="button" class="btn-cancel-edit-applicant btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 作者管理区域 -->
<div class="module-panel mt-20">
    <div class="module-btns">

        <button type="button" class="btn-add-author"><i class="icon-add"></i> 新增作者</button>
    </div>

    <table class="module-table">
        <thead>
            <tr class="module-table-header">
                <th class="col-40 text-center">序号</th>
                <th class="col-120">中文名</th>
                <th class="col-120">英文名</th>
                <th class="col-80">国籍</th>
                <th class="col-120">所属地区</th>
                <th class="col-80">主要作者</th>
                <th class="col-90">操作</th>
            </tr>
        </thead>
        <tbody id="author-list">
            <tr>
                <td colspan="7" class="text-center module-loading">正在加载数据...</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 作者编辑弹窗 -->
<div id="edit-author-modal" class="module-modal">
    <div class="module-modal-content">
        <div class="module-modal-close" id="edit-author-modal-close">×</div>
        <h3 class="module-modal-title" id="author-modal-title">编辑作者</h3>
        <div class="module-modal-body">
            <form id="edit-author-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <table class="module-table module-table-fixed">
                    <colgroup>
                        <col class="col-120">
                        <col class="col-320">
                        <col class="col-120">
                        <col class="col-320">
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
                        <td class="module-label">是否为主要作者</td>
                        <td>
                            <select name="is_main_author" class="module-input">
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
                        <td><textarea name="remark" class="module-textarea"></textarea></td>
                    </tr>
                </table>
                <div class="module-form-buttons">
                    <button type="button" class="btn-save-edit-author btn-mini">保存</button>
                    <button type="button" class="btn-cancel-edit-author btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 代理机构管理区域 -->
<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-select-agency" id="btn-select-agency"><i class="icon-add"></i> 选择代理机构</button>
    </div>

    <!-- 代理机构信息显示区域 -->
    <div id="agency-info-area" class="hidden">
        <table class="module-table">
            <thead>
                <tr class="module-table-header">
                    <th class="col-150">代理机构名称</th>
                    <th class="col-100">代理机构代码</th>
                    <th class="col-200">备注</th>
                    <th class="col-80">操作</th>
                </tr>
            </thead>
            <tbody id="agency-basic-info">
                <!-- 代理机构基本信息 -->
            </tbody>
        </table>

        <!-- 代理人信息 -->
        <div class="module-agency-section">
            <h4 class="module-agency-title">
                <span class="theme-color">👤</span> 代理人及联系人列表
                <button type="button" class="btn-edit-agency-details module-agency-button">选择代理人及联系人</button>
            </h4>
            <!-- 把代理人和联系人列表分开成两行 -->
            <div class="module-agency-flex">
                <!-- 代理人列表 -->
                <div class="module-agency-flex-item">
                    <h5 class="module-agency-subtitle">代理人</h5>
                    <table class="module-table">
                        <thead>
                            <tr class="module-table-header-light">
                                <th class="col-80">序号</th>
                                <th class="col-100">姓名</th>
                                <th class="col-100">执业证号</th>
                                <th class="col-80">电话</th>
                            </tr>
                        </thead>
                        <tbody id="agency-agents-list">
                            <tr>
                                <td colspan="4" class="text-center module-loading-small">请先选择代理人</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- 联系人列表 -->
            <div class="module-agency-flex">
                <div class="module-agency-flex-item">
                    <h5 class="module-agency-subtitle">联系人</h5>
                    <table class="module-table">
                        <thead>
                            <tr class="module-table-header-light">
                                <th class="col-80">序号</th>
                                <th class="col-100">姓名</th>
                                <th class="col-100">手机</th>
                                <th class="col-120">邮箱</th>
                            </tr>
                        </thead>
                        <tbody id="agency-contacts-list">
                            <tr>
                                <td colspan="4" class="text-center module-loading-small">请先选择联系人</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 代理机构选择/编辑弹窗 -->
<div id="agency-modal" class="module-modal">
    <div class="module-modal-content">
        <div class="module-modal-close btn-close-agency-modal">&times;</div>
        <h3 id="agency-modal-title" class="module-modal-title">选择代理机构</h3>
        <div class="module-modal-body">
            <form id="agency-form" class="module-form">
                <input type="hidden" name="id" value="0">

                <table class="module-table mb-20">
                    <tr>
                        <td class="module-label module-req">代理机构</td>
                        <td>
                            <select name="agency_id" class="module-input" required>
                                <option value="">--请选择代理机构--</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">备注</td>
                        <td>
                            <textarea name="remark" class="module-textarea" placeholder="请输入备注信息" rows="3"></textarea>
                        </td>
                    </tr>
                </table>

                <!-- 代理人选择区域 -->
                <div class="mb-20">
                    <h4 class="module-agency-title">选择代理人</h4>
                    <div id="agent-selection" class="module-agency-selection">
                        <span class="module-loading-small">请先选择代理机构</span>
                    </div>
                </div>

                <!-- 联系人选择区域 -->
                <div class="mb-20">
                    <h4 class="module-agency-title">选择联系人</h4>
                    <div id="contact-selection" class="module-agency-selection">
                        <span class="module-loading-small">请先选择代理机构</span>
                    </div>
                </div>
            </form>
        </div>

        <div class="module-form-buttons">
            <button type="button" class="btn-save-agency btn-mini">保存</button>
            <button type="button" class="btn-cancel-agency btn-mini">取消</button>
        </div>
    </div>
</div>

<script>
    (function() {
        var copyrightId = <?= $copyright_id ?>;
        var API_URL = 'modules/copyright_management/edit_tabs/applicant_api.php?copyright_id=' + copyrightId;
        var FILE_API_URL = 'modules/copyright_management/edit_tabs/applicant_file_upload.php?copyright_id=' + copyrightId;

        // 通用AJAX请求函数
        function makeRequest(action, data, callback, errorMsg) {
            var formData = new FormData();
            formData.append('action', action);

            if (data) {
                Object.keys(data).forEach(function(key) {
                    formData.append(key, data[key]);
                });
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', API_URL, true);
            xhr.onload = function() {
                try {
                    var response = JSON.parse(xhr.responseText);
                    callback(response);
                } catch (e) {
                    alert((errorMsg || '操作失败') + '：响应解析错误');
                }
            };
            xhr.send(formData);
        }

        // 通用模态框操作
        function toggleModal(modalId, show) {
            document.getElementById(modalId).style.display = show ? 'flex' : 'none';
        }

        // 通用确认删除
        function confirmDelete(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        // 通用文件上传函数
        function uploadFile(fileType, fileInputId, fileNameInputId, listDivId, applicantId) {
            if (applicantId === 0) {
                alert('请先保存申请人信息后再上传文件');
                return;
            }

            var fileInput = document.getElementById(fileInputId);
            var fileNameInput = document.getElementById(fileNameInputId);

            if (!fileInput.files.length) {
                alert('请选择文件');
                return;
            }

            var files = Array.from(fileInput.files);
            var uploadCount = 0;
            var successCount = 0;
            var errorMessages = [];

            files.forEach(function(file, index) {
                var formData = new FormData();
                formData.append('action', 'upload');
                formData.append('copyright_case_applicant_id', applicantId);
                formData.append('file_type', fileType);
                formData.append('file', file);

                if (fileNameInput.value.trim()) {
                    var customName = fileNameInput.value.trim();
                    if (files.length > 1) {
                        var ext = file.name.split('.').pop();
                        customName = customName + '_' + (index + 1) + '.' + ext;
                    } else if (!customName.includes('.')) {
                        var ext = file.name.split('.').pop();
                        customName = customName + '.' + ext;
                    }
                    formData.append('custom_filename', customName);
                }

                var xhr = new XMLHttpRequest();
                xhr.open('POST', FILE_API_URL, true);
                xhr.onload = function() {
                    uploadCount++;
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            successCount++;
                        } else {
                            errorMessages.push('文件 ' + file.name + ' 上传失败：' + (response.message || '未知错误'));
                        }
                    } catch (e) {
                        errorMessages.push('文件 ' + file.name + ' 上传失败：响应解析错误');
                    }

                    if (uploadCount === files.length) {
                        if (successCount === files.length) {
                            alert('上传成功');
                        } else if (successCount > 0) {
                            alert('部分文件上传成功 (' + successCount + '/' + files.length + ')：\n' + errorMessages.join('\n'));
                        } else {
                            alert('上传失败：\n' + errorMessages.join('\n'));
                        }
                        fileInput.value = '';
                        fileNameInput.value = '';
                        renderFileList(applicantId, fileType, listDivId);
                    }
                };
                xhr.send(formData);
            });
        }

        // 渲染文件列表
        function renderFileList(applicantId, fileType, listDivId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', FILE_API_URL, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                try {
                    var response = JSON.parse(xhr.responseText);
                    var html = '<table style="width:100%; border-collapse:collapse; margin-top:10px;">';
                    html += '<tr style="background:#f5f5f5;"><th style="border:1px solid #ddd; padding:5px;">文件名</th><th style="border:1px solid #ddd; padding:5px;">大小</th><th style="border:1px solid #ddd; padding:5px;">上传时间</th><th style="border:1px solid #ddd; padding:5px;">操作</th></tr>';

                    if (response.success && response.files && response.files.length > 0) {
                        response.files.forEach(function(file) {
                            var fileSize = file.file_size ? (file.file_size / 1024).toFixed(1) + ' KB' : '未知';
                            var uploadTime = file.created_at ? file.created_at.substring(0, 16) : '';
                            html += '<tr>';
                            html += '<td style="border:1px solid #ddd; padding:5px;">' + (file.file_name || '') + '</td>';
                            html += '<td style="border:1px solid #ddd; padding:5px;">' + fileSize + '</td>';
                            html += '<td style="border:1px solid #ddd; padding:5px;">' + uploadTime + '</td>';
                            html += '<td style="border:1px solid #ddd; padding:5px;">';
                            html += '<a href="' + file.file_path + '" target="_blank" download="' + (file.file_name || '') + '" style="margin-right:10px;" class="btn-mini">下载</a>';
                            html += '<a href="javascript:void(0)" onclick="deleteFile(' + file.id + ', \'' + fileType + '\', ' + applicantId + ', \'' + listDivId + '\')" style="color:red;" class="btn-mini">删除</a>';
                            html += '</td></tr>';
                        });
                    } else {
                        html += '<tr><td colspan="4" style="border:1px solid #ddd; padding:10px; text-align:center; color:#999;">暂无文件</td></tr>';
                    }
                    html += '</table>';
                    document.getElementById(listDivId).innerHTML = html;
                } catch (e) {
                    document.getElementById(listDivId).innerHTML = '<div style="color:red; padding:10px;">文件列表解析错误</div>';
                }
            };
            xhr.send('action=list&copyright_case_applicant_id=' + applicantId + '&file_type=' + encodeURIComponent(fileType));
        }

        // 全局删除文件函数
        window.deleteFile = function(fileId, fileType, applicantId, listDivId) {
            confirmDelete('确定要删除这个文件吗？', function() {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', FILE_API_URL, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('文件删除成功');
                            renderFileList(applicantId, fileType, listDivId);
                        } else {
                            alert('删除失败：' + (response.message || '未知错误'));
                        }
                    } catch (e) {
                        alert('删除失败：响应解析错误');
                    }
                };
                xhr.send('action=delete&file_id=' + fileId);
            });
        };

        // 申请人管理模块
        var ApplicantManager = {
            elements: {
                btnAdd: document.querySelector('.btn-add-applicant'),
                list: document.getElementById('applicant-list'),
                modal: document.getElementById('edit-applicant-modal'),
                form: document.getElementById('edit-applicant-form'),
                modalTitle: document.getElementById('modal-title')
            },

            init: function() {
                this.bindEvents();
                this.loadData();
            },

            bindEvents: function() {
                var self = this;
                this.elements.btnAdd.onclick = function() {
                    self.openModal(0);
                };
                document.getElementById('edit-applicant-modal-close').onclick = function() {
                    toggleModal('edit-applicant-modal', false);
                };
                document.querySelector('.btn-cancel-edit-applicant').onclick = function() {
                    toggleModal('edit-applicant-modal', false);
                };
                document.querySelector('.btn-save-edit-applicant').onclick = function() {
                    self.save();
                };

                var receiptCb = document.getElementById('is_receipt_title_cb');
                if (receiptCb) {
                    receiptCb.addEventListener('change', function() {
                        document.getElementById('receipt_title_row').style.display = receiptCb.checked ? '' : 'none';
                    });
                }
            },

            loadData: function() {
                var self = this;
                this.elements.list.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';

                makeRequest('get_applicants', null, function(response) {
                    if (response.success) {
                        self.elements.list.innerHTML = response.html;
                        self.bindTableEvents();
                    } else {
                        self.elements.list.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px 0;">加载数据失败：' + (response.msg || '') + '</td></tr>';
                    }
                }, '加载申请人数据失败');
            },

            bindTableEvents: function() {
                var self = this;
                this.elements.list.querySelectorAll('tr[data-id]').forEach(function(row) {
                    var id = row.getAttribute('data-id');
                    row.querySelector('.btn-del').onclick = function() {
                        confirmDelete('确定删除该申请人？', function() {
                            makeRequest('delete_applicant', {
                                id: id
                            }, function(response) {
                                if (response.success) {
                                    self.loadData();
                                } else {
                                    alert('删除失败：' + (response.msg || ''));
                                }
                            }, '删除申请人失败');
                        });
                    };
                    row.querySelector('.btn-edit').onclick = function() {
                        self.openModal(id);
                    };
                });
            },

            openModal: function(id) {
                var self = this;
                this.elements.form.reset();
                this.clearFileList();

                if (id && id !== '0') {
                    this.elements.modalTitle.textContent = '编辑申请人';
                    makeRequest('get_applicant', {
                        id: id
                    }, function(response) {
                        if (response.success && response.data) {
                            self.fillForm(response.data);
                            toggleModal('edit-applicant-modal', true);
                            self.bindFileUpload(id, true);
                        } else {
                            alert('获取数据失败：' + (response.msg || ''));
                        }
                    }, '获取申请人数据失败');
                } else {
                    this.elements.modalTitle.textContent = '新增申请人';
                    this.elements.form.id.value = '0';
                    toggleModal('edit-applicant-modal', true);
                    this.bindFileUpload(0, false);
                }
            },

            fillForm: function(data) {
                var form = this.elements.form;
                for (var k in data) {
                    if (form[k] !== undefined && form[k].type !== 'checkbox') {
                        form[k].value = data[k] !== null ? data[k] : '';
                    }
                }

                // 处理案件类型多选
                if (data.case_type) {
                    var arr = data.case_type.split(',');
                    form.querySelectorAll('input[type=checkbox][name^=case_type_]').forEach(function(cb) {
                        cb.checked = arr.indexOf(cb.value) !== -1;
                    });
                }

                form.is_first_contact.checked = data.is_first_contact == 1;
                form.is_receipt_title.checked = data.is_receipt_title == 1;
                document.getElementById('receipt_title_row').style.display = form.is_receipt_title.checked ? '' : 'none';
            },

            save: function() {
                var self = this;
                var form = this.elements.form;

                // 处理案件类型
                var checkedTypes = Array.from(form.querySelectorAll('input[type=checkbox][name^=case_type_]:checked')).map(function(cb) {
                    return cb.value;
                });
                form.case_type.value = checkedTypes.join(',');

                if (!form.is_receipt_title.checked) {
                    form.receipt_title.value = '';
                    form.credit_code.value = '';
                }

                var formData = new FormData(form);
                formData.append('action', 'save_applicant');

                // 手动添加复选框的值
                formData.append('is_first_contact', form.is_first_contact.checked ? '1' : '0');
                formData.append('is_receipt_title', form.is_receipt_title.checked ? '1' : '0');

                var xhr = new XMLHttpRequest();
                xhr.open('POST', API_URL, true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            toggleModal('edit-applicant-modal', false);
                            self.loadData();
                        } else {
                            alert('保存失败：' + (res.msg || ''));
                        }
                    } catch (e) {
                        alert('保存失败：响应解析错误');
                    }
                };
                xhr.send(formData);
            },

            clearFileList: function() {
                ['feijian-file-list', 'weituoshu-file-list', 'fujian-file-list'].forEach(function(id) {
                    document.getElementById(id).innerHTML = '';
                });
                ['file-name-fee-reduction', 'file-name-power', 'file-name-attach'].forEach(function(id) {
                    document.getElementById(id).value = '';
                });
                ['file-feijian', 'file-weituoshu', 'file-fujian'].forEach(function(id) {
                    document.getElementById(id).value = '';
                });
            },

            bindFileUpload: function(applicantId, loadFileList) {
                var fileTypes = [{
                        type: '费减证明',
                        btnId: 'btn-upload-feijian',
                        inputId: 'file-feijian',
                        nameId: 'file-name-fee-reduction',
                        listId: 'feijian-file-list'
                    },
                    {
                        type: '总委托书',
                        btnId: 'btn-upload-weituoshu',
                        inputId: 'file-weituoshu',
                        nameId: 'file-name-power',
                        listId: 'weituoshu-file-list'
                    },
                    {
                        type: '附件',
                        btnId: 'btn-upload-fujian',
                        inputId: 'file-fujian',
                        nameId: 'file-name-attach',
                        listId: 'fujian-file-list'
                    }
                ];

                fileTypes.forEach(function(fileType) {
                    document.getElementById(fileType.btnId).onclick = function() {
                        uploadFile(fileType.type, fileType.inputId, fileType.nameId, fileType.listId, applicantId);
                    };

                    if (loadFileList && applicantId > 0) {
                        renderFileList(applicantId, fileType.type, fileType.listId);
                    }
                });
            }
        };

        // 作者管理模块
        var AuthorManager = {
            elements: {
                btnAdd: document.querySelector('.btn-add-author'),
                list: document.getElementById('author-list'),
                modal: document.getElementById('edit-author-modal'),
                form: document.getElementById('edit-author-form'),
                modalTitle: document.getElementById('author-modal-title')
            },

            init: function() {
                this.bindEvents();
                this.loadData();
            },

            bindEvents: function() {
                var self = this;
                this.elements.btnAdd.onclick = function() {
                    self.openModal(0);
                };
                document.getElementById('edit-author-modal-close').onclick = function() {
                    toggleModal('edit-author-modal', false);
                };
                document.querySelector('.btn-cancel-edit-author').onclick = function() {
                    toggleModal('edit-author-modal', false);
                };
                document.querySelector('.btn-save-edit-author').onclick = function() {
                    self.save();
                };
            },

            loadData: function() {
                var self = this;
                this.elements.list.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';

                makeRequest('get_authors', null, function(response) {
                    if (response.success) {
                        self.elements.list.innerHTML = response.html;
                        self.bindTableEvents();
                    } else {
                        self.elements.list.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px 0;">加载数据失败：' + (response.msg || '') + '</td></tr>';
                    }
                }, '加载作者数据失败');
            },

            bindTableEvents: function() {
                var self = this;
                this.elements.list.querySelectorAll('tr[data-id]').forEach(function(row) {
                    var id = row.getAttribute('data-id');
                    row.querySelector('.btn-del').onclick = function() {
                        confirmDelete('确定删除该作者？', function() {
                            makeRequest('delete_author', {
                                id: id
                            }, function(response) {
                                if (response.success) {
                                    self.loadData();
                                } else {
                                    alert('删除失败：' + (response.msg || ''));
                                }
                            }, '删除作者失败');
                        });
                    };
                    row.querySelector('.btn-edit').onclick = function() {
                        self.openModal(id);
                    };
                });
            },

            openModal: function(id) {
                var self = this;
                this.elements.form.reset();

                if (id && id !== '0') {
                    this.elements.modalTitle.textContent = '编辑作者';
                    makeRequest('get_author', {
                        id: id
                    }, function(response) {
                        if (response.success && response.data) {
                            self.fillForm(response.data);
                            toggleModal('edit-author-modal', true);
                        } else {
                            alert('获取数据失败：' + (response.msg || ''));
                        }
                    }, '获取作者数据失败');
                } else {
                    this.elements.modalTitle.textContent = '新增作者';
                    this.elements.form.id.value = '0';
                    toggleModal('edit-author-modal', true);
                }
            },

            fillForm: function(data) {
                var form = this.elements.form;
                for (var k in data) {
                    if (form[k] !== undefined) {
                        form[k].value = data[k] !== null ? data[k] : '';
                    }
                }
            },

            save: function() {
                var self = this;
                var form = this.elements.form;

                var formData = new FormData(form);
                formData.append('action', 'save_author');

                var xhr = new XMLHttpRequest();
                xhr.open('POST', API_URL, true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            toggleModal('edit-author-modal', false);
                            self.loadData();
                        } else {
                            alert('保存失败：' + (res.msg || ''));
                        }
                    } catch (e) {
                        alert('保存失败：响应解析错误');
                    }
                };
                xhr.send(formData);
            }
        };

        // 代理机构管理模块
        var AgencyManager = {
            elements: {
                btnSelect: document.querySelector('.btn-select-agency'),
                infoArea: document.getElementById('agency-info-area'),
                basicInfo: document.getElementById('agency-basic-info'),
                agentsList: document.getElementById('agency-agents-list'),
                contactsList: document.getElementById('agency-contacts-list'),
                modal: document.getElementById('agency-modal'),
                form: document.getElementById('agency-form'),
                modalTitle: document.getElementById('agency-modal-title')
            },
            currentData: null,

            init: function() {
                this.bindEvents();
                this.loadData();
            },

            bindEvents: function() {
                var self = this;
                this.elements.btnSelect.onclick = function() {
                    self.openModal(0);
                };
                document.querySelector('.btn-close-agency-modal').onclick = function() {
                    toggleModal('agency-modal', false);
                };
                document.querySelector('.btn-cancel-agency').onclick = function() {
                    toggleModal('agency-modal', false);
                };
                document.querySelector('.btn-save-agency').onclick = function() {
                    self.save();
                };

                // 代理机构选择变化事件
                document.addEventListener('change', function(e) {
                    if (e.target.name === 'agency_id') {
                        var agencyId = e.target.value;
                        if (agencyId) {
                            var tempCurrentData = self.currentData;
                            self.currentData = null;
                            self.loadAgencyAgents(agencyId);
                            self.loadAgencyContacts(agencyId);
                            if (tempCurrentData && tempCurrentData.agency_id == agencyId) {
                                self.currentData = tempCurrentData;
                            }
                        } else {
                            self.currentData = null;
                            document.getElementById('agent-selection').innerHTML = '<span style="color:#999;">请先选择代理机构</span>';
                            document.getElementById('contact-selection').innerHTML = '<span style="color:#999;">请先选择代理机构</span>';
                        }
                    }
                });
            },

            loadData: function() {
                var self = this;
                makeRequest('load_agency', {
                    copyright_id: copyrightId
                }, function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        self.currentData = response.data[0];
                        self.showAgencyInfo(self.currentData);
                    } else {
                        self.showSelectButton();
                    }
                }, '加载代理机构数据失败');
            },

            showSelectButton: function() {
                this.elements.btnSelect.style.display = 'inline-block';
                this.elements.infoArea.style.display = 'none';
                this.currentData = null;
            },

            showAgencyInfo: function(data) {
                var self = this;
                this.elements.btnSelect.style.display = 'none';
                this.elements.infoArea.style.display = 'block';

                // 显示基本信息
                this.elements.basicInfo.innerHTML =
                    '<tr data-id="' + data.id + '">' +
                    '<td>' + (data.agency_name_cn || '') + '</td>' +
                    '<td>' + (data.agency_code || '') + '</td>' +
                    '<td>' + (data.remark || '') + '</td>' +
                    '<td>' +
                    '<button type="button" class="btn-mini btn-edit">编辑</button> ' +
                    '<button type="button" class="btn-mini btn-delete">删除</button>' +
                    '</td>' +
                    '</tr>';

                // 显示代理人和联系人列表
                this.renderList(data.agents, this.elements.agentsList, ['序号', '姓名', '执业证号', '电话'], ['name_cn', 'license_no', 'phone']);
                this.renderList(data.contacts, this.elements.contactsList, ['序号', '姓名', '手机', '邮箱'], ['name', 'mobile', 'work_email']);

                // 绑定事件
                var editBtn = this.elements.basicInfo.querySelector('.btn-edit');
                var deleteBtn = this.elements.basicInfo.querySelector('.btn-delete');
                var editDetailsBtn = document.querySelector('.btn-edit-agency-details');

                if (editBtn) editBtn.onclick = function() {
                    self.openModal(self.currentData.id);
                };
                if (deleteBtn) {
                    deleteBtn.onclick = function() {
                        confirmDelete('确定删除该代理机构？删除后将清空所有相关信息。', function() {
                            makeRequest('delete_agency', {
                                id: self.currentData.id
                            }, function(response) {
                                if (response.success) {
                                    self.showSelectButton();
                                } else {
                                    alert('删除失败：' + (response.msg || ''));
                                }
                            }, '删除代理机构失败');
                        });
                    };
                }
                if (editDetailsBtn) editDetailsBtn.onclick = function() {
                    self.openModal(self.currentData.id);
                };
            },

            renderList: function(data, container, headers, fields) {
                var html = '';
                if (data && data.length > 0) {
                    data.forEach(function(item, index) {
                        html += '<tr><td>' + (index + 1) + '</td>';
                        fields.forEach(function(field) {
                            html += '<td>' + (item[field] || '') + '</td>';
                        });
                        html += '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="' + (headers.length) + '" style="text-align:center;color:#999;padding:15px 0;">暂无数据</td></tr>';
                }
                container.innerHTML = html;
            },

            openModal: function(id) {
                var self = this;
                this.elements.modalTitle.textContent = id && id > 0 ? '编辑代理机构' : '选择代理机构';
                this.elements.form.reset();
                this.elements.form.querySelector('input[name="id"]').value = id || 0;

                document.getElementById('agent-selection').innerHTML = '<span style="color:#999;">请先选择代理机构</span>';
                document.getElementById('contact-selection').innerHTML = '<span style="color:#999;">请先选择代理机构</span>';

                this.loadAllAgencies(function() {
                    if (id && id > 0 && self.currentData) {
                        self.elements.form.querySelector('select[name="agency_id"]').value = self.currentData.agency_id || '';
                        self.elements.form.querySelector('textarea[name="remark"]').value = self.currentData.remark || '';

                        if (self.currentData.agency_id) {
                            self.loadAgencyAgents(self.currentData.agency_id);
                            self.loadAgencyContacts(self.currentData.agency_id);
                        }
                    }
                });

                toggleModal('agency-modal', true);
            },

            loadAllAgencies: function(callback) {
                makeRequest('get_all_agencies', null, function(response) {
                    if (response.success) {
                        var agencySelect = document.querySelector('select[name="agency_id"]');
                        agencySelect.innerHTML = '<option value="">--请选择代理机构--</option>';
                        response.data.forEach(function(agency) {
                            agencySelect.innerHTML += '<option value="' + agency.id + '">' + agency.agency_name_cn + ' (' + agency.agency_code + ')</option>';
                        });
                        if (callback) callback();
                    }
                }, '加载代理机构列表失败');
            },

            loadAgencyAgents: function(agencyId) {
                var self = this;
                makeRequest('get_agency_agents', {
                    agency_id: agencyId
                }, function(response) {
                    if (response.success) {
                        var html = '';
                        if (response.data.length > 0) {
                            response.data.forEach(function(agent) {
                                var checked = '';
                                if (self.currentData && self.currentData.agents) {
                                    var found = self.currentData.agents.find(function(a) {
                                        return a.id == agent.id;
                                    });
                                    if (found) checked = 'checked';
                                }
                                html += '<label style="display:block;margin:5px 0;"><input type="checkbox" name="agent_ids[]" value="' + agent.id + '" ' + checked + '> ' + agent.name_cn + ' (' + agent.license_no + ')</label>';
                            });
                        } else {
                            html = '<span style="color:#999;">该代理机构暂无代理人</span>';
                        }
                        document.getElementById('agent-selection').innerHTML = html;
                    }
                }, '加载代理人失败');
            },

            loadAgencyContacts: function(agencyId) {
                var self = this;
                makeRequest('get_agency_contacts', {
                    agency_id: agencyId
                }, function(response) {
                    if (response.success) {
                        var html = '';
                        if (response.data.length > 0) {
                            response.data.forEach(function(contact) {
                                var checked = '';
                                if (self.currentData && self.currentData.contacts) {
                                    var found = self.currentData.contacts.find(function(c) {
                                        return c.id == contact.id;
                                    });
                                    if (found) checked = 'checked';
                                }
                                html += '<label style="display:block;margin:5px 0;"><input type="checkbox" name="contact_ids[]" value="' + contact.id + '" ' + checked + '> ' + contact.name + ' (' + contact.mobile + ')</label>';
                            });
                        } else {
                            html = '<span style="color:#999;">该代理机构暂无联系人</span>';
                        }
                        document.getElementById('contact-selection').innerHTML = html;
                    }
                }, '加载联系人失败');
            },

            save: function() {
                var self = this;
                var form = this.elements.form;
                var agencyId = form.querySelector('select[name="agency_id"]').value;

                if (!agencyId) {
                    alert('请选择代理机构');
                    return;
                }

                var formData = new FormData(form);
                formData.append('action', 'save_agency');
                formData.append('copyright_id', copyrightId);

                var agentIds = [];
                var contactIds = [];

                form.querySelectorAll('input[name="agent_ids[]"]:checked').forEach(function(input) {
                    agentIds.push(input.value);
                });

                form.querySelectorAll('input[name="contact_ids[]"]:checked').forEach(function(input) {
                    contactIds.push(input.value);
                });

                formData.append('agent_ids', agentIds.join(','));
                formData.append('contact_ids', contactIds.join(','));

                var xhr = new XMLHttpRequest();
                xhr.open('POST', API_URL, true);
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            toggleModal('agency-modal', false);
                            self.loadData();
                        } else {
                            alert('保存失败：' + (res.msg || ''));
                        }
                    } catch (e) {
                        alert('保存失败：响应解析错误');
                    }
                };
                xhr.send(formData);
            }
        };

        // 初始化所有模块
        ApplicantManager.init();
        AuthorManager.init();
        AgencyManager.init();
    })();
</script>