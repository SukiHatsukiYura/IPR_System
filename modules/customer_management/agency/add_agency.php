<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 新增/编辑代理机构功能
$agency_types = ['专利', '商标', '版权'];
$is_active_options = ['1' => '是', '0' => '否'];
$is_customer_options = ['1' => '是', '0' => '否'];
$is_default_options = ['1' => '是', '0' => '否'];

// 查询所有客户用于下拉
$customer_stmt = $pdo->prepare("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn ASC");
$customer_stmt->execute();
$customers = $customer_stmt->fetchAll();

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// 优先从session读取编辑id
$edit_agency_id = 0;
if (isset($_SESSION['edit_agency_id'])) {
    $edit_agency_id = intval($_SESSION['edit_agency_id']);
    unset($_SESSION['edit_agency_id']);
} else if (isset($_GET['id'])) {
    $edit_agency_id = intval($_GET['id']);
}
$id = $edit_agency_id;
$edit_data = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM agency WHERE id=?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$edit_data) {
        echo '<div style="color:#f44336;text-align:center;">未找到该代理机构</div>';
        exit;
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'save') {
        header('Content-Type: application/json');
        $data = [
            'agency_name_cn' => trim($_POST['agency_name_cn'] ?? ''),
            'agency_name_en' => trim($_POST['agency_name_en'] ?? ''),
            'country' => trim($_POST['country'] ?? ''),
            'province' => trim($_POST['province'] ?? ''),
            'city_cn' => trim($_POST['city_cn'] ?? ''),
            'city_en' => trim($_POST['city_en'] ?? ''),
            'street_address_cn' => trim($_POST['street_address_cn'] ?? ''),
            'street_address_en' => trim($_POST['street_address_en'] ?? ''),
            'department_cn' => trim($_POST['department_cn'] ?? ''),
            'department_en' => trim($_POST['department_en'] ?? ''),
            'responsible_person' => trim($_POST['responsible_person'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'fax' => trim($_POST['fax'] ?? ''),
            'agency_code' => trim($_POST['agency_code'] ?? ''),
            'establish_date' => trim($_POST['establish_date'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'mail' => trim($_POST['mail'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'is_active' => intval($_POST['is_active'] ?? 1),
            'is_customer' => intval($_POST['is_customer'] ?? 0),
            'customer_id' => ($_POST['is_customer'] ?? '0') == '1' ? intval($_POST['customer_id'] ?? 0) : null,
            'is_default' => intval($_POST['is_default'] ?? 0),
            'credit_code' => trim($_POST['credit_code'] ?? ''),
            'agency_types' => isset($_POST['agency_types']) ? implode(',', (array)$_POST['agency_types']) : '',
            'remark' => trim($_POST['remark'] ?? ''),
        ];
        if ($data['agency_name_cn'] === '') {
            echo json_encode(['success' => false, 'msg' => '请填写代理机构(中文)']);
            exit;
        }
        if ($data['is_customer'] && !$data['customer_id']) {
            echo json_encode(['success' => false, 'msg' => '请选择关联客户']);
            exit;
        }
        if ($data['establish_date'] === '') $data['establish_date'] = null;
        try {
            if (intval($_POST['id'] ?? 0) > 0) {
                // 编辑
                $id = intval($_POST['id']);
                $set = '';
                foreach ($data as $k => $v) {
                    $set .= "$k=:$k,";
                }
                $set = rtrim($set, ',');
                $sql = "UPDATE agency SET $set WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                foreach ($data as $k => $v) {
                    $stmt->bindValue(":$k", $v);
                }
                $stmt->bindValue(":id", $id, PDO::PARAM_INT);
                $ok = $stmt->execute();
                echo json_encode(['success' => $ok]);
            } else {
                // 新增
                $fields = implode(',', array_keys($data));
                $placeholders = ':' . implode(', :', array_keys($data));
                $sql = "INSERT INTO agency ($fields) VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                foreach ($data as $k => $v) {
                    $stmt->bindValue(":$k", $v);
                }
                $ok = $stmt->execute();
                echo json_encode(['success' => $ok]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
        }
        exit;
    } elseif ($action === 'get') {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM agency WHERE id=?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                echo json_encode(['success' => false, 'msg' => '未找到数据']);
            }
        } else {
            echo json_encode(['success' => false, 'msg' => '参数错误']);
        }
        exit;
    }
}
?>
<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-save"><i class="icon-save"></i> 保存</button>
        <button type="button" class="btn-cancel"><i class="icon-cancel"></i> 取消</button>
    </div>
    <h3 style="text-align:center;margin-bottom:15px;"><?php echo $id > 0 ? '编辑代理机构' : '新增代理机构'; ?></h3>
    <form class="module-form" id="agency-form" autocomplete="off">
        <input type="hidden" name="id" value="<?php echo $id > 0 ? h($id) : 0; ?>">
        <table class="module-table">
            <tr>
                <td class="module-label module-req">*代理机构(中文)：</td>
                <td><input type="text" name="agency_name_cn" class="module-input" required value="<?php echo h($edit_data['agency_name_cn'] ?? ''); ?>"></td>
                <td class="module-label">代理机构(英文)：</td>
                <td><input type="text" name="agency_name_en" class="module-input" value="<?php echo h($edit_data['agency_name_en'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td class="module-label">所属国家(地区)：</td>
                <td><input type="text" name="country" class="module-input" value="<?php echo h($edit_data['country'] ?? ''); ?>"></td>
                <td class="module-label">代理机构代码：</td>
                <td><input type="text" name="agency_code" class="module-input" value="<?php echo h($edit_data['agency_code'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td class="module-label">负责人：</td>
                <td><input type="text" name="responsible_person" class="module-input" value="<?php echo h($edit_data['responsible_person'] ?? ''); ?>"></td>
                <td class="module-label">成立时间：</td>
                <td><input type="date" name="establish_date" class="module-input" value="<?php echo h($edit_data['establish_date'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td class="module-label">邮箱：</td>
                <td><input type="email" name="email" class="module-input" value="<?php echo h($edit_data['email'] ?? ''); ?>"></td>
                <td class="module-label">传真：</td>
                <td><input type="text" name="fax" class="module-input" value="<?php echo h($edit_data['fax'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td class="module-label">联系电话：</td>
                <td><input type="text" name="phone" class="module-input" value="<?php echo h($edit_data['phone'] ?? ''); ?>"></td>
                <td class="module-label">邮件：</td>
                <td><input type="email" name="mail" class="module-input" value="<?php echo h($edit_data['mail'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td class="module-label">省份：</td>
                <td><input type="text" name="province" class="module-input" value="<?php echo h($edit_data['province'] ?? ''); ?>"></td>
                <td class="module-label">城市(中文)：</td>
                <td><input type="text" name="city_cn" class="module-input" value="<?php echo h($edit_data['city_cn'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td class="module-label">城市(英文)：</td>
                <td><input type="text" name="city_en" class="module-input" value="<?php echo h($edit_data['city_en'] ?? ''); ?>"></td>
                <td class="module-label">街道地址(中文)：</td>
                <td><input type="text" name="street_address_cn" class="module-input" value="<?php echo h($edit_data['street_address_cn'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td class="module-label">街道地址(英文)：</td>
                <td><input type="text" name="street_address_en" class="module-input" value="<?php echo h($edit_data['street_address_en'] ?? ''); ?>"></td>
                <td class="module-label">部门/楼层(中文)：</td>
                <td><input type="text" name="department_cn" class="module-input" value="<?php echo h($edit_data['department_cn'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td class="module-label">部门/楼层(英文)：</td>
                <td><input type="text" name="department_en" class="module-input" value="<?php echo h($edit_data['department_en'] ?? ''); ?>"></td>
                <td class="module-label">网址：</td>
                <td><input type="text" name="website" class="module-input" value="<?php echo h($edit_data['website'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td class="module-label">是否有效：</td>
                <td>
                    <select name="is_active" class="module-input">
                        <option value="1" <?php if (($edit_data['is_active'] ?? '1') == '1') echo 'selected'; ?>>是</option>
                        <option value="0" <?php if (($edit_data['is_active'] ?? '1') == '0') echo 'selected'; ?>>否</option>
                    </select>
                </td>
                <td class="module-label">是否为客户：</td>
                <td>
                    <select name="is_customer" class="module-input" id="is_customer_select">
                        <option value="0" <?php if (($edit_data['is_customer'] ?? '0') == '0') echo 'selected'; ?>>否</option>
                        <option value="1" <?php if (($edit_data['is_customer'] ?? '0') == '1') echo 'selected'; ?>>是</option>
                    </select>
                    <span id="customer-select-box" style="display:<?php echo ($edit_data['is_customer'] ?? '0') == '1' ? '' : 'none'; ?>;margin-left:8px;">
                        <div class="module-select-search-box" style="width:180px;display:inline-block;">
                            <input type="text" class="module-input module-select-search-input" name="customer_display" value="<?php
                                                                                                                                if (($edit_data['is_customer'] ?? '0') == '1' && $edit_data['customer_id']) {
                                                                                                                                    foreach ($customers as $c) {
                                                                                                                                        if ($c['id'] == $edit_data['customer_id']) echo h($c['customer_name_cn']);
                                                                                                                                    }
                                                                                                                                }
                                                                                                                                ?>" readonly placeholder="点击选择客户">
                            <input type="hidden" name="customer_id" value="<?php echo h($edit_data['customer_id'] ?? ''); ?>">
                            <div class="module-select-search-list" style="display:none;">
                                <input type="text" class="module-select-search-list-input" placeholder="搜索客户名称">
                                <div class="module-select-search-list-items"></div>
                            </div>
                        </div>
                    </span>
                </td>
            </tr>
            <tr>
                <td class="module-label">是否默认本所：</td>
                <td>
                    <select name="is_default" class="module-input">
                        <option value="0" <?php if (($edit_data['is_default'] ?? '0') == '0') echo 'selected'; ?>>否</option>
                        <option value="1" <?php if (($edit_data['is_default'] ?? '0') == '1') echo 'selected'; ?>>是</option>
                    </select>
                </td>
                <td class="module-label">统一社会信用代码：</td>
                <td><input type="text" name="credit_code" class="module-input" value="<?php echo h($edit_data['credit_code'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td class="module-label">默认代理机构类型：</td>
                <td colspan="3">
                    <?php foreach ($agency_types as $v): ?>
                        <label style="margin-right:18px;"><input type="checkbox" name="agency_types[]" value="<?= h($v) ?>" <?php
                                                                                                                            if (!empty($edit_data['agency_types'])) {
                                                                                                                                $arr = explode(',', $edit_data['agency_types']);
                                                                                                                                if (in_array($v, $arr)) echo 'checked';
                                                                                                                            }
                                                                                                                            ?>> <?= h($v) ?></label>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <td class="module-label">备注：</td>
                <td colspan="3"><textarea name="remark" class="module-input" style="min-height:48px;width:100%;"><?php echo h($edit_data['remark'] ?? ''); ?></textarea></td>
            </tr>
        </table>
    </form>
</div>
<?php if ($id > 0): ?>
    <div class="module-btn">
        <div id="agency-tabs-bar" style="margin-bottom:10px;">
            <button type="button" class="btn-mini tab-btn active" data-tab="contact">联系人</button>
            <button type="button" class="btn-mini tab-btn" data-tab="agent">代理人</button>
            <button type="button" class="btn-mini tab-btn" data-tab="requirement">代理机构要求</button>
            <button type="button" class="btn-mini tab-btn" data-tab="contact_record">联系记录</button>
            <button type="button" class="btn-mini tab-btn" data-tab="agency_file">代理机构文件</button>
            <button type="button" class="btn-mini tab-btn" data-tab="bank_info">银行信息</button>
        </div>
        <div id="agency-tab-content" style="min-height:180px;"></div>
    </div>
    <script>
        (function() {
            var agencyId = <?= $id ?>;

            function loadTab(tab) {
                var content = document.getElementById('agency-tab-content');
                content.innerHTML = '<div style="padding:40px;text-align:center;color:#888;">加载中...</div>';
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'modules/customer_management/agency/agency_tabs/' + tab + '.php?agency_id=' + agencyId, true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            content.innerHTML = xhr.responseText;
                            var scripts = content.querySelectorAll('script');
                            scripts.forEach(function(script) {
                                var newScript = document.createElement('script');
                                if (script.src) {
                                    newScript.src = script.src;
                                } else {
                                    newScript.text = script.textContent;
                                }
                                document.body.appendChild(newScript);
                            });
                        } else {
                            content.innerHTML = '<div style="padding:40px;text-align:center;color:#f44336;">加载失败</div>';
                        }
                    }
                };
                xhr.send();
            }
            document.querySelectorAll('.tab-btn').forEach(function(btn) {
                btn.onclick = function() {
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    loadTab(btn.getAttribute('data-tab'));
                };
            });
            loadTab('contact');
        })();
    </script>
<?php endif; ?>
<script>
    (function() {
        var form = document.querySelector('.module-form');
        var btnSave = document.querySelector('.btn-save');
        var btnCancel = document.querySelector('.btn-cancel');
        var isCustomerSelect = document.getElementById('is_customer_select');
        var customerSelectBox = document.getElementById('customer-select-box');
        // 切换是否为客户时显示/隐藏客户下拉
        isCustomerSelect.onchange = function() {
            if (this.value == '1') {
                customerSelectBox.style.display = '';
            } else {
                customerSelectBox.style.display = 'none';
                var input = customerSelectBox.querySelector('.module-select-search-input');
                var hidden = customerSelectBox.querySelector('input[type=hidden]');
                if (input) input.value = '';
                if (hidden) hidden.value = '';
            }
        };
        // 客户数据
        var customerData = <?php echo json_encode($customers, JSON_UNESCAPED_UNICODE); ?>;

        function bindCustomerSearch(box) {
            var input = box.querySelector('.module-select-search-input');
            var hidden = box.querySelector('input[type=hidden]');
            var list = box.querySelector('.module-select-search-list');
            var searchInput = list.querySelector('.module-select-search-list-input');
            var itemsDiv = list.querySelector('.module-select-search-list-items');

            function renderList(filter) {
                var html = '';
                var found = false;
                customerData.forEach(function(c) {
                    if (!filter || c.customer_name_cn.indexOf(filter) !== -1) {
                        html += '<div class="module-select-search-item" data-id="' + c.id + '">' + c.customer_name_cn + '</div>';
                        found = true;
                    }
                });
                if (!found) html = '<div class="no-match">无匹配</div>';
                itemsDiv.innerHTML = html;
            }
            input.addEventListener('click', function() {
                renderList('');
                list.style.display = 'block';
                searchInput.value = '';
                searchInput.focus();
            });
            searchInput.addEventListener('input', function() {
                renderList(searchInput.value.trim());
            });
            document.addEventListener('click', function(e) {
                if (!box.contains(e.target)) list.style.display = 'none';
            });
            itemsDiv.addEventListener('mousedown', function(e) {
                var item = e.target.closest('.module-select-search-item');
                if (item) {
                    input.value = item.textContent;
                    hidden.value = item.getAttribute('data-id');
                    list.style.display = 'none';
                }
            });
        }
        document.querySelectorAll('.module-select-search-box').forEach(bindCustomerSearch);
        // 保存按钮AJAX提交
        btnSave.onclick = function() {
            var required = ['agency_name_cn'];
            for (var i = 0; i < required.length; i++) {
                var el = form.querySelector('[name="' + required[i] + '"]');
                if (!el || !el.value.trim()) {
                    alert('请填写所有必填项（代理机构名称）');
                    el && el.focus();
                    return;
                }
            }
            var fd = new FormData(form);
            fd.append('action', 'save');
            var url = 'modules/customer_management/agency/add_agency.php<?= $id > 0 ? "?id={$id}" : "" ?>';
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            alert('保存成功');
                            <?php if ($id <= 0): ?>
                                form.reset();
                            <?php else: ?>
                                // 编辑模式成功后返回列表页
                                if (confirm('编辑成功，是否返回代理机构列表？')) {
                                    if (window.parent.openTab) {
                                        window.parent.openTab(0, 2, 1);
                                    } else {
                                        window.location.href = 'agency_list.php';
                                    }
                                }
                            <?php endif; ?>
                        } else {
                            alert(res.msg || '保存失败');
                        }
                    } catch (e) {
                        console.error('保存失败，响应内容不是JSON');
                        console.log('xhr.status:', xhr.status);
                        console.log('xhr.responseURL:', xhr.responseURL);
                        console.log('xhr.responseText:', xhr.responseText);
                        console.log('异常信息:', e);
                        alert('保存失败：' + xhr.responseText);
                    }
                }
            };
            xhr.send(fd);
        };
        btnCancel.onclick = function() {
            <?php if ($id > 0): ?>
                // 编辑模式取消返回列表页
                if (confirm('确定取消编辑并返回代理机构列表？')) {
                    if (window.parent.openTab) {
                        window.parent.openTab(0, 2, 1);
                    } else {
                        window.location.href = 'agency_list.php';
                    }
                }
            <?php else: ?>
                form.reset();
            <?php endif; ?>
        };
    })();
</script>