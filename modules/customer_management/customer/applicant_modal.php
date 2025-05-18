<!-- applicant_modal.php：申请人编辑弹窗及相关JS，通用复用 -->
<div id="applicant-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:950px;max-width:98vw;max-height:80vh;position:relative;display:flex;flex-direction:column;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="applicant-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">申请人信息</h3>
        <div style="flex:1 1 auto;overflow-y:auto;">
            <form id="applicant-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <input type="hidden" name="customer_id" value="">
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
                        <td colspan="3">
                            <label style="margin-right:18px;"><input type="checkbox" name="case_type_patent" value="专利"> 专利</label>
                            <label style="margin-right:18px;"><input type="checkbox" name="case_type_trademark" value="商标"> 商标</label>
                            <label style="margin-right:18px;"><input type="checkbox" name="case_type_copyright" value="版权"> 版权</label>
                            <input type="hidden" name="case_type" value="">
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">名称/姓(中文)</td>
                        <td><input type="text" name="name_xing_cn" class="module-input"></td>
                        <td class="module-label">名称/姓(英文)</td>
                        <td><input type="text" name="name_xing_en" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">电话</td>
                        <td><input type="text" name="phone" class="module-input"></td>
                        <td class="module-label">邮件</td>
                        <td><input type="email" name="email" class="module-input"></td>
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
                        <td class="module-label">行政区划</td>
                        <td><input type="text" name="district" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">邮编</td>
                        <td><input type="text" name="postcode" class="module-input"></td>
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
                    </tr>
                    <tr>
                        <td class="module-label module-req">*证件号</td>
                        <td><input type="text" name="id_number" class="module-input" required></td>
                        <td class="module-label">费用减案</td>
                        <td>
                            <select name="is_fee_reduction" class="module-input">
                                <option value="0">否</option>
                                <option value="1">是</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">费用减案有效期</td>
                        <td>
                            <input type="date" name="fee_reduction_start" class="module-input" style="width:48%;display:inline-block;"> -
                            <input type="date" name="fee_reduction_end" class="module-input" style="width:48%;display:inline-block;">
                        </td>
                        <td class="module-label">备案证件号</td>
                        <td><input type="text" name="fee_reduction_code" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">中国总委托编号</td>
                        <td><input type="text" name="cn_agent_code" class="module-input"></td>
                        <td class="module-label">PCT总委托编号</td>
                        <td><input type="text" name="pct_agent_code" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">监控年费</td>
                        <td>
                            <select name="is_fee_monitor" class="module-input">
                                <option value="0">否</option>
                                <option value="1">是</option>
                            </select>
                        </td>
                        <td class="module-label">国家(地区)</td>
                        <td><input type="text" name="country" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">国籍</td>
                        <td><input type="text" name="nationality" class="module-input"></td>
                        <td class="module-label">营业执照</td>
                        <td><input type="text" name="business_license" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">是否第一联系人</td>
                        <td>
                            <label><input type="checkbox" name="is_first_contact" value="1"> 是</label>
                        </td>
                        <td class="module-label">作为收据抬头</td>
                        <td>
                            <label><input type="checkbox" name="is_receipt_title" value="1" id="is_receipt_title_cb"> 是</label>
                        </td>
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
                                <input type="file" id="file-fee-reduction" style="display:inline-block;width:auto;">
                                <button type="button" class="btn-mini" id="btn-upload-fee-reduction">上传</button>
                                <div id="list-fee-reduction" style="margin-top:4px;"></div>
                            </div>
                            <div style="margin-bottom:8px;">
                                <label>总委托书：</label>
                                <input type="text" id="file-name-power" placeholder="文件命名（可选）" style="width:120px;">
                                <input type="file" id="file-power" style="display:inline-block;width:auto;">
                                <button type="button" class="btn-mini" id="btn-upload-power">上传</button>
                                <div id="list-power" style="margin-top:4px;"></div>
                            </div>
                            <div>
                                <label>附件：</label>
                                <input type="text" id="file-name-attach" placeholder="文件命名（可选，所有文件同名）" style="width:120px;">
                                <input type="file" id="file-attach" multiple style="display:inline-block;width:auto;">
                                <button type="button" class="btn-mini" id="btn-upload-attach">上传</button>
                                <div id="list-attach" style="margin-top:4px;"></div>
                            </div>
                        </td>
                    </tr>
                </table>
                <div style="text-align:center;margin-top:12px;">
                    <button type="button" class="btn-save-applicant btn-mini" style="margin-right:16px;">保存</button>
                    <button type="button" class="btn-cancel-applicant btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // applicantModal：通用申请人弹窗对象
    window.applicantModal = (function() {
        var modal, form, closeBtn, cancelBtn, saveBtn, receiptCb;
        var onSuccessCallback = null;
        // 初始化事件
        function init() {
            modal = document.getElementById('applicant-modal');
            form = document.getElementById('applicant-form');
            closeBtn = document.getElementById('applicant-modal-close');
            cancelBtn = form.querySelector('.btn-cancel-applicant');
            saveBtn = form.querySelector('.btn-save-applicant');
            receiptCb = document.getElementById('is_receipt_title_cb');
            if (closeBtn) closeBtn.onclick = hide;
            if (cancelBtn) cancelBtn.onclick = hide;
            if (receiptCb) {
                receiptCb.addEventListener('change', function() {
                    document.getElementById('receipt_title_row').style.display = receiptCb.checked ? '' : 'none';
                });
            }
            if (saveBtn) {
                saveBtn.onclick = function() {
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
                    var fd = new FormData(form);
                    fd.append('action', 'save');
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                hide();
                                if (typeof onSuccessCallback === 'function') onSuccessCallback();
                            } else {
                                alert(res.msg || '保存失败');
                            }
                        } catch (e) {
                            alert('保存失败');
                        }
                    };
                    xhr.send(fd);
                };
            }
        }
        // 显示弹窗，data: {id, customer_id, mode, onSuccess}
        function show(data) {
            if (!modal) init();
            onSuccessCallback = typeof data.onSuccess === 'function' ? data.onSuccess : null;
            form.reset();
            // 新增
            if (!data.id || data.id == 0) {
                form.id.value = 0;
                form.customer_id.value = data.customer_id || '';
                document.getElementById('receipt_title_row').style.display = 'none';
                modal.style.display = 'flex';
                bindFileUpload(0); // 新增时无文件
                return;
            }
            // 编辑，拉取数据
            var fd = new FormData();
            fd.append('action', 'get');
            fd.append('id', data.id);
            fd.append('customer_id', data.customer_id);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant.php', true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.data) {
                        for (var k in res.data) {
                            if (form[k] !== undefined && form[k].type !== 'checkbox') form[k].value = res.data[k] !== null ? res.data[k] : '';
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
                        form.customer_id.value = data.customer_id;
                        modal.style.display = 'flex';
                        bindFileUpload(data.id);
                    } else {
                        alert('获取数据失败');
                    }
                } catch (e) {
                    alert('获取数据失败');
                }
            };
            xhr.send(fd);
        }
        // 关闭弹窗
        function hide() {
            if (modal) modal.style.display = 'none';
        }
        // 文件上传/删除/回显逻辑
        function renderFileList(applicantId, fileType, listDivId) {
            var listDiv = document.getElementById(listDivId);
            listDiv.innerHTML = '加载中...';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php?action=list&applicant_id=' + applicantId + '&file_type=' + encodeURIComponent(fileType), true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.files.length > 0) {
                        var html = '<table class="module-table" style="margin:0;width:100%;"><thead><tr>' +
                            '<th style="width:180px;">文件名</th>' +
                            '<th style="width:180px;">原文件名</th>' +
                            '<th style="width:140px;">上传时间</th>' +
                            '<th style="width:120px;">操作</th>' +
                            '</tr></thead><tbody>';
                        res.files.forEach(function(f) {
                            html += '<tr>' +
                                '<td>' + (f.file_name || '') + '</td>' +
                                '<td>' + (f.origin_name || f.file_name || '') + '</td>' +
                                '<td>' + (f.created_at ? f.created_at.substr(0, 16) : '') + '</td>' +
                                '<td>' +
                                '<a href="' + f.file_path + '" download class="btn-mini" style="margin-right:8px;">下载</a>' +
                                '<button type="button" class="btn-mini file-del" data-id="' + f.id + '" style="color:#f44336;">删除</button>' +
                                '</td>' +
                                '</tr>';
                        });
                        html += '</tbody></table>';
                        listDiv.innerHTML = html;
                        listDiv.querySelectorAll('.file-del').forEach(function(btn) {
                            btn.onclick = function(e) {
                                e.preventDefault();
                                if (!confirm('确定删除该文件？')) return;
                                var id = this.getAttribute('data-id');
                                var xhr2 = new XMLHttpRequest();
                                var fd = new FormData();
                                fd.append('action', 'delete');
                                fd.append('id', id);
                                xhr2.open('POST', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php', true);
                                xhr2.onload = function() {
                                    renderFileList(applicantId, fileType, listDivId);
                                };
                                xhr2.send(fd);
                            };
                        });
                    } else {
                        listDiv.innerHTML = '<span style="color:#888;">暂无文件</span>';
                    }
                } catch (e) {
                    listDiv.innerHTML = '<span style="color:#888;">加载失败</span>';
                }
            };
            xhr.onerror = function(e) {
                listDiv.innerHTML = '<span style="color:#888;">加载失败</span>';
            };
            xhr.send();
        }

        function bindFileUpload(applicantId) {
            // 先解绑旧事件，防止重复绑定
            var btnFee = document.getElementById('btn-upload-fee-reduction');
            var btnPower = document.getElementById('btn-upload-power');
            var btnAttach = document.getElementById('btn-upload-attach');
            if (btnFee) btnFee.onclick = null;
            if (btnPower) btnPower.onclick = null;
            if (btnAttach) btnAttach.onclick = null;
            // 费减证明
            document.getElementById('btn-upload-fee-reduction').onclick = function() {
                var fileInput = document.getElementById('file-fee-reduction');
                var nameInput = document.getElementById('file-name-fee-reduction');
                if (!fileInput.files[0]) {
                    alert('请选择文件');
                    return;
                }
                var fd = new FormData();
                fd.append('action', 'upload');
                fd.append('applicant_id', applicantId);
                fd.append('file_type', '费减证明');
                fd.append('file', fileInput.files[0]);
                fd.append('file_name', nameInput ? nameInput.value : '');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php', true);
                xhr.onload = function() {
                    fileInput.value = '';
                    if (nameInput) nameInput.value = '';
                    renderFileList(applicantId, '费减证明', 'list-fee-reduction');
                };
                xhr.onerror = function(e) {
                    alert('上传失败');
                };
                xhr.send(fd);
            };
            // 总委托书
            document.getElementById('btn-upload-power').onclick = function() {
                var fileInput = document.getElementById('file-power');
                var nameInput = document.getElementById('file-name-power');
                if (!fileInput.files[0]) {
                    alert('请选择文件');
                    return;
                }
                var fd = new FormData();
                fd.append('action', 'upload');
                fd.append('applicant_id', applicantId);
                fd.append('file_type', '总委托书');
                fd.append('file', fileInput.files[0]);
                fd.append('file_name', nameInput ? nameInput.value : '');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php', true);
                xhr.onload = function() {
                    fileInput.value = '';
                    if (nameInput) nameInput.value = '';
                    renderFileList(applicantId, '总委托书', 'list-power');
                };
                xhr.onerror = function(e) {
                    alert('上传失败');
                };
                xhr.send(fd);
            };
            // 附件（多文件）
            document.getElementById('btn-upload-attach').onclick = function() {
                var fileInput = document.getElementById('file-attach');
                var nameInput = document.getElementById('file-name-attach');
                if (!fileInput.files.length) {
                    alert('请选择文件');
                    return;
                }
                var files = Array.from(fileInput.files);
                var uploadNext = function(idx) {
                    if (idx >= files.length) {
                        fileInput.value = '';
                        if (nameInput) nameInput.value = '';
                        renderFileList(applicantId, '附件', 'list-attach');
                        return;
                    }
                    var fd = new FormData();
                    fd.append('action', 'upload');
                    fd.append('applicant_id', applicantId);
                    fd.append('file_type', '附件');
                    fd.append('file', files[idx]);
                    fd.append('file_name', nameInput ? nameInput.value : '');
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/applicant_file_upload.php', true);
                    xhr.onload = function() {
                        uploadNext(idx + 1);
                    };
                    xhr.onerror = function(e) {
                        alert('上传失败');
                    };
                    xhr.send(fd);
                };
                uploadNext(0);
            };
            // 初始加载
            renderFileList(applicantId, '费减证明', 'list-fee-reduction');
            renderFileList(applicantId, '总委托书', 'list-power');
            renderFileList(applicantId, '附件', 'list-attach');
        }
        // 页面加载时自动初始化
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        // 对外暴露 show 方法
        return {
            show: show
        };
    })();
</script>