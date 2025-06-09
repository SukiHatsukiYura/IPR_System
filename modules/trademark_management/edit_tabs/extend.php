<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php'); // 引入通用函数库
check_access_via_framework();
session_start();

if (!isset($_GET['trademark_id']) || intval($_GET['trademark_id']) <= 0) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未指定商标ID</div>';
    exit;
}
$trademark_id = intval($_GET['trademark_id']);

// 查询商标信息确认存在
$trademark_stmt = $pdo->prepare("SELECT id FROM trademark_case_info WHERE id = ?");
$trademark_stmt->execute([$trademark_id]);
if (!$trademark_stmt->fetch()) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未找到该商标信息</div>';
    exit;
}

// 定义下拉选项
$color_forms = ['无', '指定颜色', '颜色组合', '指定颜色与颜色组合'];
$specified_colors = ['黑', '蓝', '红', '黄', '绿', '白', '紫', '橙', '棕', '灰', '粉', '青'];

// 检查是否已有扩展信息，没有则插入空白
$extend_stmt = $pdo->prepare("SELECT * FROM trademark_case_extend_info WHERE trademark_case_info_id = ?");
$extend_stmt->execute([$trademark_id]);
$extend = $extend_stmt->fetch(PDO::FETCH_ASSOC);
if (!$extend) {
    $stmt = $pdo->prepare("INSERT INTO trademark_case_extend_info (trademark_case_info_id) VALUES (?)");
    $stmt->execute([$trademark_id]);
    $extend_stmt->execute([$trademark_id]); // 重新查询
    $extend = $extend_stmt->fetch(PDO::FETCH_ASSOC);
}

// 处理声音文件上传
function handle_sound_upload($trademark_id, $old_sound_path = null)
{
    if (!isset($_FILES['sound_file']) || $_FILES['sound_file']['error'] !== UPLOAD_ERR_OK) {
        return $old_sound_path; // 没有新文件上传，返回原路径
    }

    $file = $_FILES['sound_file'];
    $allowed_types = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a'];
    $max_size = 10 * 1024 * 1024; // 10MB

    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('只支持MP3、WAV、OGG、M4A格式的音频文件');
    }

    if ($file['size'] > $max_size) {
        throw new Exception('音频文件大小不能超过10MB');
    }

    // 创建上传目录
    $upload_dir = __DIR__ . '/../../../uploads/trademark_sounds/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // 删除旧文件
    if ($old_sound_path && file_exists(__DIR__ . '/../../../' . $old_sound_path)) {
        unlink(__DIR__ . '/../../../' . $old_sound_path);
    }

    // 生成唯一文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'TM' . $trademark_id . '_sound_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('声音文件上传失败');
    }

    return 'uploads/trademark_sounds/' . $filename;
}

// 处理AJAX保存
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_extend') {
    header('Content-Type: application/json');

    try {
        // 处理声音文件上传
        $sound_file_path = handle_sound_upload($trademark_id, $extend['sound_file_path']);

        $fields = [
            'is_3d_mark',
            'color_form',
            'specified_color',
            'case_nature',
            'trademark_form_type',
            'second_source_person',
            'external_source_person',
            'internal_source_person',
            'difficulty_type',
            'difficulty_description',
            'opponent_name',
            'supplementary_reason',
            'cost',
            'budget',
            'madrid_application_language',
            'madrid_application_no',
            'madrid_application_date',
            'madrid_registration_no',
            'madrid_registration_date',
            'is_famous_trademark'
        ];

        $data = [];
        $set = [];

        // 处理日期字段
        $date_fields = [
            'madrid_application_date',
            'madrid_registration_date'
        ];

        // 处理数字字段
        $number_fields = [
            'cost',
            'budget',
            'is_3d_mark',
            'is_famous_trademark'
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

        // 添加声音文件路径
        if ($sound_file_path !== $extend['sound_file_path']) {
            $data['sound_file_path'] = $sound_file_path;
            $set[] = "sound_file_path = :sound_file_path";
        }

        if (empty($set)) {
            echo json_encode(['success' => false, 'msg' => '无可更新字段']);
            exit;
        }

        $data['trademark_case_info_id'] = $trademark_id;
        $sql = "UPDATE trademark_case_extend_info SET " . implode(',', $set) . " WHERE trademark_case_info_id = :trademark_case_info_id";

        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute($data);
        echo json_encode(['success' => $ok, 'msg' => $ok ? null : '数据库更新失败']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 处理删除声音文件的AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_sound') {
    header('Content-Type: application/json');

    try {
        // 获取当前声音文件路径
        $current_sound_path = $extend['sound_file_path'];

        // 删除物理文件
        if ($current_sound_path && file_exists(__DIR__ . '/../../../' . $current_sound_path)) {
            unlink(__DIR__ . '/../../../' . $current_sound_path);
        }

        // 更新数据库，将声音文件路径设为NULL
        $stmt = $pdo->prepare("UPDATE trademark_case_extend_info SET sound_file_path = NULL WHERE trademark_case_info_id = ?");
        $ok = $stmt->execute([$trademark_id]);

        echo json_encode(['success' => $ok, 'msg' => $ok ? null : '删除失败']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 输出下拉框所需JS资源
render_select_search_assets();

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
?>
<div class="module-btns" style="margin-bottom:10px;">
    <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
    <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
</div>
<form id="edit-trademark-extend-form" class="module-form" autocomplete="off" enctype="multipart/form-data">
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
            <td class="module-label">是否三维标志</td>
            <td>
                <label><input type="radio" name="is_3d_mark" value="1" <?= ($extend['is_3d_mark'] == '1') ? 'checked' : '' ?>> 是</label>
                <label><input type="radio" name="is_3d_mark" value="0" <?= ($extend['is_3d_mark'] === '0' || $extend['is_3d_mark'] === 0 || $extend['is_3d_mark'] === null) ? 'checked' : '' ?>> 否</label>
            </td>
            <td class="module-label">疑难类型</td>
            <td><input type="text" name="difficulty_type" class="module-input" value="<?= h($extend['difficulty_type']) ?>"></td>
            <td class="module-label">马德里申请语言</td>
            <td><input type="text" name="madrid_application_language" class="module-input" value="<?= h($extend['madrid_application_language']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">颜色形式</td>
            <td><?= render_select('color_form', $color_forms, $extend['color_form'] ?? '无') ?></td>
            <td class="module-label">疑难</td>
            <td><textarea name="difficulty_description" class="module-textarea" rows="2"><?= h($extend['difficulty_description']) ?></textarea></td>
            <td class="module-label">马德里申请号</td>
            <td><input type="text" name="madrid_application_no" class="module-input" value="<?= h($extend['madrid_application_no']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">指定颜色</td>
            <td><?= render_select('specified_color', $specified_colors, $extend['specified_color']) ?></td>
            <td class="module-label">对方当事人名称</td>
            <td><input type="text" name="opponent_name" class="module-input" value="<?= h($extend['opponent_name']) ?>"></td>
            <td class="module-label">马德里申请日</td>
            <td><input type="date" name="madrid_application_date" class="module-input" value="<?= h($extend['madrid_application_date']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">声音文件</td>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <input type="file" id="sound-file" name="sound_file" accept="audio/*" style="display:none;">
                    <button type="button" id="upload-sound-btn" class="btn-mini" style="background:#29b6b0;color:#fff;border:none;padding:6px 12px;">上传</button>
                    <?php if ($extend['sound_file_path']): ?>
                        <audio controls style="height:32px;" id="sound-player">
                            <source src="<?= h($extend['sound_file_path']) ?>" type="audio/mpeg">
                            您的浏览器不支持音频播放
                        </audio>
                        <button type="button" id="remove-sound-btn" class="btn-mini">删除</button>
                    <?php else: ?>
                        <span id="no-sound-text" style="color:#999;font-size:12px;">暂无声音文件</span>
                    <?php endif; ?>
                </div>
                <div style="font-size:12px;color:#666;margin-top:5px;">支持MP3、WAV、OGG、M4A格式，最大10MB</div>
            </td>
            <td class="module-label">补充理由</td>
            <td><textarea name="supplementary_reason" class="module-textarea" rows="2"><?= h($extend['supplementary_reason']) ?></textarea></td>
            <td class="module-label">马德里注册号</td>
            <td><input type="text" name="madrid_registration_no" class="module-input" value="<?= h($extend['madrid_registration_no']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">案件性质</td>
            <td><input type="text" name="case_nature" class="module-input" value="<?= h($extend['case_nature']) ?>"></td>
            <td class="module-label">成本</td>
            <td><input type="number" step="0.01" name="cost" class="module-input" style="background:#fff;" value="<?= h($extend['cost']) ?>"></td>
            <td class="module-label">马德里注册日</td>
            <td><input type="date" name="madrid_registration_date" class="module-input" value="<?= h($extend['madrid_registration_date']) ?>"></td>
        </tr>
        <tr>
            <td class="module-label">商标形式类型</td>
            <td><input type="text" name="trademark_form_type" class="module-input" value="<?= h($extend['trademark_form_type']) ?>"></td>
            <td class="module-label">预算</td>
            <td><input type="number" step="0.01" name="budget" class="module-input" style="background:#fff;" value="<?= h($extend['budget']) ?>"></td>
            <td class="module-label">是否认定驰名商标</td>
            <td>
                <label><input type="radio" name="is_famous_trademark" value="1" <?= ($extend['is_famous_trademark'] == '1') ? 'checked' : '' ?>> 是</label>
                <label><input type="radio" name="is_famous_trademark" value="0" <?= ($extend['is_famous_trademark'] === '0' || $extend['is_famous_trademark'] === 0 || $extend['is_famous_trademark'] === null) ? 'checked' : '' ?>> 否</label>
            </td>
        </tr>
        <tr>
            <td class="module-label">第二案源人</td>
            <td><input type="text" name="second_source_person" class="module-input" value="<?= h($extend['second_source_person']) ?>"></td>
            <td class="module-label">外部案源人</td>
            <td><input type="text" name="external_source_person" class="module-input" value="<?= h($extend['external_source_person']) ?>"></td>
            <td class="module-label">内部案源人</td>
            <td><input type="text" name="internal_source_person" class="module-input" value="<?= h($extend['internal_source_person']) ?>"></td>
        </tr>

    </table>
</form>
<script>
    function initTrademarkTabEvents() {
        // 声音文件上传功能
        var uploadSoundBtn = document.getElementById('upload-sound-btn');
        var soundFileInput = document.getElementById('sound-file');
        var removeSoundBtn = document.getElementById('remove-sound-btn');
        var noSoundText = document.getElementById('no-sound-text');
        var soundPlayer = document.getElementById('sound-player');

        if (uploadSoundBtn && soundFileInput) {
            uploadSoundBtn.onclick = function() {
                soundFileInput.click();
            };

            soundFileInput.onchange = function() {
                var file = this.files[0];
                if (file) {
                    // 验证文件类型
                    var allowedTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('只支持MP3、WAV、OGG、M4A格式的音频文件');
                        this.value = '';
                        return;
                    }

                    // 验证文件大小
                    if (file.size > 10 * 1024 * 1024) {
                        alert('音频文件大小不能超过10MB');
                        this.value = '';
                        return;
                    }

                    // 显示文件名
                    if (noSoundText) {
                        noSoundText.textContent = '已选择: ' + file.name;
                    }
                }
            };
        }

        if (removeSoundBtn) {
            removeSoundBtn.onclick = function() {
                if (confirm('确定要删除声音文件吗？')) {
                    // 发送删除请求
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'modules/trademark_management/edit_tabs/extend.php?trademark_id=<?= $trademark_id ?>', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            try {
                                var res = JSON.parse(xhr.responseText);
                                if (res.success) {
                                    alert('声音文件删除成功');
                                    // 隐藏音频控件和删除按钮
                                    if (soundPlayer) {
                                        soundPlayer.style.display = 'none';
                                    }
                                    removeSoundBtn.style.display = 'none';
                                    // 显示"暂无声音文件"文本
                                    if (!noSoundText) {
                                        var span = document.createElement('span');
                                        span.id = 'no-sound-text';
                                        span.style.cssText = 'color:#999;font-size:12px;';
                                        span.textContent = '暂无声音文件';
                                        removeSoundBtn.parentNode.appendChild(span);
                                    } else {
                                        noSoundText.style.display = 'inline';
                                        noSoundText.textContent = '暂无声音文件';
                                    }
                                    // 清空文件输入框
                                    if (soundFileInput) {
                                        soundFileInput.value = '';
                                    }
                                } else {
                                    alert('删除失败: ' + (res.msg || '未知错误'));
                                }
                            } catch (e) {
                                alert('删除失败，服务器返回无效响应');
                            }
                        }
                    };
                    xhr.send('action=delete_sound');
                }
            };
        }

        // 保存按钮
        document.querySelectorAll('#trademark-tab-content .btn-save').forEach(function(btnSave) {
            btnSave.onclick = function() {
                var form = document.getElementById('edit-trademark-extend-form');
                var fd = new FormData(form);
                fd.append('action', 'save_extend');

                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/trademark_management/edit_tabs/extend.php?trademark_id=<?= $trademark_id ?>', true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                alert('保存成功');
                                // 不刷新整个页面，只显示成功消息
                            } else {
                                alert('保存失败: ' + (res.msg || '未知错误'));
                            }
                        } catch (e) {
                            alert('保存失败，服务器返回无效响应');
                        }
                    }
                };
                xhr.send(fd);
            };
        });

        // 取消按钮
        document.querySelectorAll('#trademark-tab-content .btn-cancel').forEach(function(btnCancel) {
            btnCancel.onclick = function() {
                if (confirm('确定要取消吗？未保存的内容将丢失。')) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'modules/trademark_management/edit_tabs/extend.php?trademark_id=<?= $trademark_id ?>', true);
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            var tabContent = document.querySelector('#trademark-tab-content');
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
                                    if (typeof initTrademarkTabEvents === 'function') {
                                        initTrademarkTabEvents();
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
        document.addEventListener('DOMContentLoaded', initTrademarkTabEvents);
    } else {
        initTrademarkTabEvents();
    }
</script>