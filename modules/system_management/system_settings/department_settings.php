<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 查询所有用户（负责人下拉用）
$user_stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name ASC");
$user_stmt->execute();
$users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

// 递归获取部门树
function get_department_tree($parent_id = 0, $all = null)
{
    global $pdo;
    static $all_depts = null;
    if ($all === null) {
        if ($all_depts === null) {
            $stmt = $pdo->query("SELECT * FROM department ORDER BY sort_order ASC, id ASC");
            $all_depts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $all = $all_depts;
    }
    $tree = [];
    foreach ($all as $row) {
        if ((int)$row['parent_id'] === (int)$parent_id) {
            $children = get_department_tree($row['id'], $all);
            if ($children) $row['children'] = $children;
            $tree[] = $row;
        }
    }
    return $tree;
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    if ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM department WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => !!$row, 'data' => $row]);
        exit;
    } elseif ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $data = [
            'dept_name' => trim($_POST['dept_name'] ?? ''),
            'dept_short_name' => trim($_POST['dept_short_name'] ?? ''),
            'parent_id' => intval($_POST['parent_id'] ?? 0),
            'leader_id' => intval($_POST['leader_id'] ?? 0),
            'is_main' => intval($_POST['is_main'] ?? 0),
            'is_active' => intval($_POST['is_active'] ?? 1),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'dept_code' => trim($_POST['dept_code'] ?? ''),
        ];
        if ($data['dept_name'] === '') {
            echo json_encode(['success' => false, 'msg' => '请填写部门名称']);
            exit;
        }
        try {
            if ($id > 0) {
                $set = '';
                foreach ($data as $k => $v) $set .= "$k=:$k,";
                $set = rtrim($set, ',');
                $sql = "UPDATE department SET $set WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                foreach ($data as $k => $v) $stmt->bindValue(":$k", $v);
                $stmt->bindValue(":id", $id, PDO::PARAM_INT);
                $ok = $stmt->execute();
                echo json_encode(['success' => $ok]);
            } else {
                $fields = implode(',', array_keys($data));
                $placeholders = ':' . implode(', :', array_keys($data));
                $sql = "INSERT INTO department ($fields) VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                foreach ($data as $k => $v) $stmt->bindValue(":$k", $v);
                $ok = $stmt->execute();
                echo json_encode(['success' => $ok]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '数据库异常:' . $e->getMessage()]);
        }
        exit;
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            // 检查是否有子部门
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM department WHERE parent_id=?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'msg' => '请先删除子部门']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM department WHERE id=?");
            $ok = $stmt->execute([$id]);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    exit;
}

// 获取部门树
$dept_tree = get_department_tree();
function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<div class="module-panel" style="display:flex;gap:24px;min-width:900px;">
    <!-- 左侧部门树 -->
    <div style="width:320px;min-width:220px;border-right:1px solid #e0e0e0;padding-right:18px;">
        <div style="font-weight:bold;margin-bottom:10px;">部门结构</div>
        <div id="dept-tree">
            <style>
                /* 优化树引导线样式 */
                .dept-tree-list {
                    margin: 0;
                    padding-left: 16px;
                }

                .dept-tree-node {
                    position: relative;
                    white-space: nowrap;
                    margin-bottom: 2px;
                }

                .tree-vline {
                    display: inline-block;
                    width: 16px;
                    height: 22px;
                    position: relative;
                    vertical-align: middle;
                }

                .tree-vline span {
                    position: absolute;
                    left: 50%;
                    top: 0;
                    bottom: 0;
                    border-left: 1px solid #bdbdbd;
                    width: 0;
                }

                .tree-branch {
                    display: inline-block;
                    width: 16px;
                    height: 22px;
                    position: relative;
                    vertical-align: middle;
                }

                .tree-branch .hline {
                    position: absolute;
                    top: 11px;
                    left: 0;
                    width: 12px;
                    border-bottom: 1px solid #bdbdbd;
                }

                .tree-branch .vline {
                    position: absolute;
                    left: 50%;
                    width: 0;
                    border-left: 1px solid #bdbdbd;
                }
            </style>
            <?php
            // 只优化树结构引导线
            function render_dept_tree($tree, $level = 0, $parent_last = [])
            {
                if (!$tree) return;
                echo '<ul class="dept-tree-list">';
                $count = count($tree);
                foreach ($tree as $i => $node) {
                    $is_last = ($i === $count - 1);
                    // 生成前导线
                    $prefix = '';
                    for ($j = 0; $j < $level; $j++) {
                        $prefix .= '<span class="tree-vline">' .
                            (empty($parent_last[$j]) ? '<span></span>' : '') . '</span>';
                    }
                    $prefix .= '<span class="tree-branch">' .
                        ($level > 0 ? ($is_last ? '<span class="vline" style="top:0;height:11px;"></span><span class="hline"></span>' : '<span class="vline" style="top:0;height:22px;"></span><span class="hline"></span>') : '') . '</span>';
                    echo '<li data-id="' . h($node['id']) . '" class="dept-tree-node">';
                    echo $prefix;
                    echo '<span class="dept-tree-label">' . h($node['dept_name']) . '</span>';
                    if (!empty($node['children'])) render_dept_tree($node['children'], $level + 1, array_merge($parent_last, [$is_last]));
                    echo '</li>';
                }
                echo '</ul>';
            }
            render_dept_tree($dept_tree);
            ?>
        </div>
        <div style="margin-top:18px;">
            <button type="button" class="btn-mini" id="btn-add-root-dept"><i class="icon-add"></i> 新建顶级部门</button>
        </div>
    </div>
    <!-- 右侧详情 -->
    <div style="flex:1 1 auto;min-width:320px;">
        <div style="font-weight:bold;margin-bottom:10px;">部门详情</div>
        <div id="dept-detail-area" style="min-height:180px;color:#888;text-align:center;padding:40px 0;">
            请选择左侧部门节点
        </div>
    </div>
</div>
<!-- 部门编辑弹窗 -->
<div id="dept-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:520px;max-width:98vw;max-height:80vh;position:relative;display:flex;flex-direction:column;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="dept-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;" id="dept-modal-title">部门信息</h3>
        <div style="flex:1 1 auto;overflow-y:auto;">
            <form id="dept-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <input type="hidden" name="parent_id" value="0">
                <table class="module-table" style="table-layout:fixed;width:100%;min-width:0;">
                    <tr>
                        <td class="module-label module-req">*部门名称</td>
                        <td><input type="text" name="dept_name" class="module-input" required></td>
                    </tr>
                    <tr>
                        <td class="module-label">简称</td>
                        <td><input type="text" name="dept_short_name" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">上级部门</td>
                        <td>
                            <select name="parent_id" class="module-input" id="parent-dept-select">
                                <option value="0">--无--</option>
                                <?php
                                // 扁平化所有部门
                                function render_dept_options($tree, $level = 0, $skip_id = 0)
                                {
                                    foreach ($tree as $node) {
                                        if ($node['id'] == $skip_id) continue;
                                        echo '<option value="' . h($node['id']) . '">' . str_repeat('—', $level) . h($node['dept_name']) . '</option>';
                                        if (!empty($node['children'])) render_dept_options($node['children'], $level + 1, $skip_id);
                                    }
                                }
                                render_dept_options($dept_tree);
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">负责人</td>
                        <td>
                            <select name="leader_id" class="module-input">
                                <option value="0">--无--</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= h($u['id']) ?>"><?= h($u['real_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">是否主部门</td>
                        <td>
                            <select name="is_main" class="module-input">
                                <option value="0">否</option>
                                <option value="1">是</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">是否有效</td>
                        <td>
                            <select name="is_active" class="module-input">
                                <option value="1">是</option>
                                <option value="0">否</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">排序号</td>
                        <td><input type="number" name="sort_order" class="module-input" value="0"></td>
                    </tr>
                    <tr>
                        <td class="module-label">部门代码</td>
                        <td><input type="text" name="dept_code" class="module-input"></td>
                    </tr>
                </table>
                <div style="text-align:center;margin-top:12px;">
                    <button type="button" class="btn-save-dept btn-mini" style="margin-right:16px;">保存</button>
                    <button type="button" class="btn-cancel-dept btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function() {
        // 工具函数
        function h(v) {
            if (v == null) return '';
            return String(v).replace(/[&<>\"]/g, function(s) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;'
                } [s];
            });
        }
        // 选中节点高亮
        var treeArea = document.getElementById('dept-tree');
        var detailArea = document.getElementById('dept-detail-area');
        var modal = document.getElementById('dept-modal');
        var modalClose = document.getElementById('dept-modal-close');
        var modalTitle = document.getElementById('dept-modal-title');
        var form = document.getElementById('dept-form');
        var btnAddRoot = document.getElementById('btn-add-root-dept');
        var currentDeptId = null;

        // 绑定树节点点击
        treeArea.onclick = function(e) {
            var li = e.target.closest('.dept-tree-node');
            if (!li) return;
            // 高亮
            treeArea.querySelectorAll('.dept-tree-node').forEach(function(n) {
                n.style.background = '';
            });
            li.style.background = '#e0f7fa';
            var id = li.getAttribute('data-id');
            currentDeptId = id;
            // 加载详情
            loadDeptDetail(id);
        };

        // 加载部门详情
        function loadDeptDetail(id) {
            detailArea.innerHTML = '<div style="color:#888;padding:40px 0;">加载中...</div>';
            var xhr = new XMLHttpRequest();
            var fd = new FormData();
            fd.append('action', 'get');
            fd.append('id', id);
            xhr.open('POST', 'modules/system_management/system_settings/department_settings.php', true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.data) {
                        var d = res.data;
                        var html = '<table class="module-table" style="width:100%;max-width:600px;margin:0 auto;">' +
                            '<tr><td class="module-label">分部名称：</td><td>' + h(d.dept_name) + '</td></tr>' +
                            '<tr><td class="module-label">分部简称：</td><td>' + h(d.dept_short_name) + '</td></tr>' +
                            '<tr><td class="module-label">顶级负责人：</td><td>' + (d.leader_id > 0 ? getUserName(d.leader_id) : '') + '</td></tr>' +
                            '<tr><td class="module-label">部门代码：</td><td>' + h(d.dept_code) + '</td></tr>' +
                            '<tr><td class="module-label">是否有效：</td><td>' + (d.is_active == 1 ? '是' : '否') + '</td></tr>' +
                            '</table>' +
                            '<div style="margin-top:18px;text-align:center;">' +
                            '<button type="button" class="btn-mini btn-edit-dept" style="margin-right:12px;">编辑</button>' +
                            '<button type="button" class="btn-mini btn-add-child-dept" style="margin-right:12px;">新增下级部门</button>' +
                            '<button type="button" class="btn-mini btn-del-dept" style="color:#f44336;">删除</button>' +
                            '</div>';
                        detailArea.innerHTML = html;
                        // 绑定按钮
                        detailArea.querySelector('.btn-edit-dept').onclick = function() {
                            showDeptModal('edit', d);
                        };
                        detailArea.querySelector('.btn-add-child-dept').onclick = function() {
                            showDeptModal('add', {
                                parent_id: d.id
                            });
                        };
                        detailArea.querySelector('.btn-del-dept').onclick = function() {
                            if (!confirm('确定删除该部门？')) return;
                            var xhr2 = new XMLHttpRequest();
                            var fd2 = new FormData();
                            fd2.append('action', 'delete');
                            fd2.append('id', d.id);
                            xhr2.open('POST', 'modules/system_management/system_settings/department_settings.php', true);
                            xhr2.onload = function() {
                                try {
                                    var res2 = JSON.parse(xhr2.responseText);
                                    if (res2.success) {
                                        alert('删除成功');
                                        location.reload();
                                    } else {
                                        alert(res2.msg || '删除失败');
                                    }
                                } catch (e) {
                                    alert('删除失败');
                                }
                            };
                            xhr2.send(fd2);
                        };
                    } else {
                        detailArea.innerHTML = '<div style="color:#f44336;padding:40px 0;">未找到部门信息</div>';
                    }
                } catch (e) {
                    detailArea.innerHTML = '<div style="color:#f44336;padding:40px 0;">加载失败</div>';
                }
            };
            xhr.send(fd);
        }

        // 获取用户名
        var userMap = {};
        <?php foreach ($users as $u): ?>
            userMap[<?= (int)$u['id'] ?>] = <?= json_encode($u['real_name'], JSON_UNESCAPED_UNICODE) ?>;
        <?php endforeach; ?>

        function getUserName(id) {
            return userMap[id] || '';
        }

        // 新建顶级部门
        btnAddRoot.onclick = function() {
            showDeptModal('add', {
                parent_id: 0
            });
        };

        // 弹窗相关
        function showDeptModal(mode, data) {
            form.reset();
            if (mode === 'edit') {
                modalTitle.textContent = '编辑部门';
                for (var k in data) {
                    if (form[k] !== undefined) form[k].value = data[k] !== null ? data[k] : '';
                }
                // 上级部门不能选自己
                var parentSelect = document.getElementById('parent-dept-select');
                for (var i = 0; i < parentSelect.options.length; i++) {
                    parentSelect.options[i].disabled = (parentSelect.options[i].value == data.id);
                }
            } else {
                modalTitle.textContent = '新增部门';
                form.id.value = 0;
                // 新增：如果有parent_id，自动选中
                form.parent_id.value = data.parent_id || currentDeptId || 0;
                var parentSelect = document.getElementById('parent-dept-select');
                for (var i = 0; i < parentSelect.options.length; i++) {
                    parentSelect.options[i].disabled = false;
                    if (parentSelect.options[i].value == (data.parent_id || currentDeptId || 0)) {
                        parentSelect.options[i].selected = true;
                    } else {
                        parentSelect.options[i].selected = false;
                    }
                }
            }
            modal.style.display = 'flex';
        }
        modalClose.onclick = function() {
            modal.style.display = 'none';
        };
        form.querySelector('.btn-cancel-dept').onclick = function() {
            modal.style.display = 'none';
        };

        // 保存
        form.querySelector('.btn-save-dept').onclick = function() {
            var fd = new FormData(form);
            fd.append('action', 'save');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/system_management/system_settings/department_settings.php', true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        alert('保存成功');
                        modal.style.display = 'none';
                        // 新增：只刷新部门树和右侧详情
                        refreshDeptTreeAndDetail();
                    } else {
                        alert(res.msg || '保存失败');
                    }
                } catch (e) {
                    alert('保存失败');
                }
            };
            xhr.send(fd);
        };

        // 新增：刷新部门树和右侧详情
        function refreshDeptTreeAndDetail() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'modules/system_management/system_settings/department_settings.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = xhr.responseText;
                    // 替换部门树
                    var newTree = tempDiv.querySelector('#dept-tree');
                    if (newTree) {
                        treeArea.innerHTML = newTree.innerHTML;
                    }
                    // 替换右侧详情
                    var newDetail = tempDiv.querySelector('#dept-detail-area');
                    if (newDetail) {
                        detailArea.innerHTML = newDetail.innerHTML;
                    } else {
                        detailArea.innerHTML = '请选择左侧部门节点';
                    }
                    // 重新绑定事件
                    // 选中节点高亮
                    treeArea.onclick = function(e) {
                        var li = e.target.closest('.dept-tree-node');
                        if (!li) return;
                        treeArea.querySelectorAll('.dept-tree-node').forEach(function(n) {
                            n.style.background = '';
                        });
                        li.style.background = '#e0f7fa';
                        var id = li.getAttribute('data-id');
                        currentDeptId = id;
                        loadDeptDetail(id);
                    };
                }
            };
            xhr.send();
        }
    })();
</script>