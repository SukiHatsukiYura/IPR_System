<?php
// 专利编辑-文件管理
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
    echo '<div class="module-error">未指定专利ID</div>';
    exit;
}
$patent_id = intval($_GET['patent_id']);

// 验证专利是否存在
$patent_stmt = $pdo->prepare("SELECT id, case_name FROM patent_case_info WHERE id = ?");
$patent_stmt->execute([$patent_id]);
$patent_info = $patent_stmt->fetch();
if (!$patent_info) {
    echo '<div class="module-error">未找到该专利信息</div>';
    exit;
}

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<div class="module-panel">
    <!-- 文件上传区域 -->
    <div class="module-file-upload">
        <label>文件类型：</label>
        <select id="file-type-select" class="module-input" style="width:120px; background-color: #fff;">
            <option value="">--请选择--</option>
            <option value="申请书">申请书</option>
            <option value="说明书">说明书</option>
            <option value="权利要求书">权利要求书</option>
            <option value="附图">附图</option>
            <option value="审查意见">审查意见</option>
            <option value="答复意见">答复意见</option>
            <option value="其他">其他</option>
        </select>
        <input type="text" id="file-name-input" placeholder="文件命名（可选）" class="module-input" style="width:200px; background-color: #fff;">
        <input type="file" id="file-input" multiple>
        <button type="button" class="btn-mini" id="btn-upload">上传</button>
    </div>

    <!-- 文件类型筛选按钮 -->
    <div class="module-btns" style="margin-top: 15px;">
        <button type="button" class="btn-mini filter-btn active" data-type="">全部文件</button>
        <button type="button" class="btn-mini filter-btn" data-type="申请书">申请书</button>
        <button type="button" class="btn-mini filter-btn" data-type="说明书">说明书</button>
        <button type="button" class="btn-mini filter-btn" data-type="权利要求书">权利要求书</button>
        <button type="button" class="btn-mini filter-btn" data-type="附图">附图</button>
        <button type="button" class="btn-mini filter-btn" data-type="审查意见">审查意见</button>
        <button type="button" class="btn-mini filter-btn" data-type="答复意见">答复意见</button>
        <button type="button" class="btn-mini filter-btn" data-type="其他">其他</button>
    </div>

    <!-- 文件列表 -->
    <div id="file-list-container" class="module-file-list"></div>
</div>

<script>
    (function() {
        var patentId = <?= $patent_id ?>;
        var FILE_API_URL = 'modules/patent_management/edit_tabs/file_api.php?patent_id=' + patentId;
        var currentFileType = '';

        // 通用文件上传函数
        function uploadFile(fileType, fileInputId, fileNameInputId) {
            var fileInput = document.getElementById(fileInputId);
            var fileNameInput = document.getElementById(fileNameInputId);

            if (!fileType) {
                alert('请选择文件类型');
                return;
            }

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
                formData.append('patent_case_info_id', patentId);
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
                        document.getElementById('file-type-select').value = '';
                        renderFileList(currentFileType);
                    }
                };
                xhr.send(formData);
            });
        }

        // 渲染文件列表
        function renderFileList(fileType) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', FILE_API_URL, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                try {
                    var response = JSON.parse(xhr.responseText);
                    var html = '<table style="width:100%; border-collapse:collapse; margin-top:10px;">';
                    html += '<tr style="background:#f5f5f5;"><th style="border:1px solid #ddd; padding:5px;">文件类型</th><th style="border:1px solid #ddd; padding:5px;">文件名</th><th style="border:1px solid #ddd; padding:5px;">大小</th><th style="border:1px solid #ddd; padding:5px;">上传时间</th><th style="border:1px solid #ddd; padding:5px;">操作</th></tr>';

                    if (response.success && response.files && response.files.length > 0) {
                        response.files.forEach(function(file) {
                            var fileSize = file.file_size ? (file.file_size / 1024).toFixed(1) + ' KB' : '未知';
                            var uploadTime = file.created_at ? file.created_at.substring(0, 16) : '';
                            html += '<tr>';
                            html += '<td style="border:1px solid #ddd; padding:5px;">' + (file.file_type || '') + '</td>';
                            html += '<td style="border:1px solid #ddd; padding:5px;">' + (file.file_name || '') + '</td>';
                            html += '<td style="border:1px solid #ddd; padding:5px;">' + fileSize + '</td>';
                            html += '<td style="border:1px solid #ddd; padding:5px;">' + uploadTime + '</td>';
                            html += '<td style="border:1px solid #ddd; padding:5px;">';
                            html += '<a href="' + file.file_path + '" target="_blank" download="' + (file.file_name || '') + '" style="margin-right:10px;" class="btn-mini">下载</a>';
                            html += '<a href="javascript:void(0)" onclick="deleteFile(' + file.id + ')" style="color:red;" class="btn-mini">删除</a>';
                            html += '</td></tr>';
                        });
                    } else {
                        html += '<tr><td colspan="5" style="border:1px solid #ddd; padding:10px; text-align:center; color:#999;">暂无文件</td></tr>';
                    }
                    html += '</table>';
                    document.getElementById('file-list-container').innerHTML = html;
                } catch (e) {
                    document.getElementById('file-list-container').innerHTML = '<div style="color:red; padding:10px;">文件列表解析错误</div>';
                }
            };

            var params = 'action=list&patent_case_info_id=' + patentId;
            if (fileType) {
                params += '&file_type=' + encodeURIComponent(fileType);
            }
            xhr.send(params);
        }

        // 全局删除文件函数
        window.deleteFile = function(fileId) {
            if (confirm('确定要删除这个文件吗？')) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', FILE_API_URL, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('文件删除成功');
                            renderFileList(currentFileType);
                        } else {
                            alert('删除失败：' + (response.message || '未知错误'));
                        }
                    } catch (e) {
                        alert('删除失败：响应解析错误');
                    }
                };
                xhr.send('action=delete&file_id=' + fileId);
            }
        };

        // 初始化
        document.getElementById('btn-upload').onclick = function() {
            var fileType = document.getElementById('file-type-select').value;
            uploadFile(fileType, 'file-input', 'file-name-input');
        };

        // 筛选按钮事件
        document.querySelectorAll('.filter-btn').forEach(function(btn) {
            btn.onclick = function() {
                document.querySelectorAll('.filter-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');
                currentFileType = btn.getAttribute('data-type');
                renderFileList(currentFileType);
            };
        });

        // 加载文件列表
        renderFileList('');
    })();
</script>

<style>
    .filter-btn.active {
        background: #29b6b0;
        color: white;
    }

    .filter-btn {
        margin-right: 8px;
        margin-bottom: 8px;
    }
</style>