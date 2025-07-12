<?php
session_start();
include_once(__DIR__ . '/../../database.php');
include_once(__DIR__ . '/../../common/functions.php');
check_access_via_framework();

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_task') {
        // 保存处理事项
        $patent_id = intval($_POST['patent_case_info_id']);
        $task_id = intval($_POST['task_id'] ?? 0);
        $current_user_id = $_SESSION['user_id'] ?? 1; // 当前登录用户ID
        $current_date = date('Y-m-d');

        $fields = [
            'task_item',
            'task_status',
            'case_stage',
            'internal_deadline',
            'client_deadline',
            'official_deadline',
            'handler_id',
            'external_handler_id',
            'supervisor_id',
            'first_draft_date',
            'final_draft_date',
            'return_date',
            'completion_date',
            'send_to_firm_date',
            'internal_final_date',
            'is_urgent',
            'task_rule_count',
            'translation_word_count',
            'contract_number',
            'remarks'
        ];

        $data = [];
        $set = [];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                if ($value === '' || $value === '请选择') {
                    $value = null;
                }
                // 处理数字字段
                if (in_array($field, ['handler_id', 'external_handler_id', 'supervisor_id', 'translation_word_count', 'is_urgent'])) {
                    $value = $value === null ? null : intval($value);
                }
                $data[$field] = $value;
                $set[] = "$field = :$field";
            }
        }

        try {
            if ($task_id > 0) {
                // 编辑处理事项
                $data['modifier_id'] = $current_user_id;
                $data['modification_date'] = $current_date;
                $data['task_id'] = $task_id;
                $set[] = "modifier_id = :modifier_id";
                $set[] = "modification_date = :modification_date";
                $sql = "UPDATE patent_case_task SET " . implode(',', $set) . " WHERE id = :task_id";

                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($data);

                if ($result) {
                    // 更新对应的核稿状态记录中的核稿人
                    $reviewer_id = !empty($data['supervisor_id']) ? $data['supervisor_id'] : null;
                    $review_update_sql = "UPDATE patent_case_review_status SET reviewer_id = ? WHERE patent_case_task_id = ?";
                    $review_update_stmt = $pdo->prepare($review_update_sql);
                    $review_update_stmt->execute([$reviewer_id, $task_id]);
                }

                echo json_encode(['success' => $result, 'msg' => $result ? null : '保存失败']);
            } else {
                // 新增处理事项
                $data['patent_case_info_id'] = $patent_id;
                $data['creator_id'] = $current_user_id;
                $data['creation_date'] = $current_date;
                $set[] = "patent_case_info_id = :patent_case_info_id";
                $set[] = "creator_id = :creator_id";
                $set[] = "creation_date = :creation_date";
                $sql = "INSERT INTO patent_case_task SET " . implode(',', $set);

                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($data);

                if ($result) {
                    // 获取新插入的处理事项ID
                    $new_task_id = $pdo->lastInsertId();

                    // 同时向patent_case_review_status表插入一条草稿状态记录
                    $review_data = [
                        'patent_case_info_id' => $patent_id,
                        'patent_case_task_id' => $new_task_id,
                        'review_status' => '草稿',
                        'reviewer_id' => null
                    ];

                    // 如果有核稿人，则关联核稿人
                    if (!empty($data['supervisor_id'])) {
                        $review_data['reviewer_id'] = $data['supervisor_id'];
                    }

                    $review_sql = "INSERT INTO patent_case_review_status (patent_case_info_id, patent_case_task_id, review_status, reviewer_id) VALUES (:patent_case_info_id, :patent_case_task_id, :review_status, :reviewer_id)";
                    $review_stmt = $pdo->prepare($review_sql);
                    $review_stmt->execute($review_data);
                }

                echo json_encode(['success' => $result, 'msg' => $result ? null : '保存失败']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '系统异常: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_task') {
        // 删除处理事项
        $task_id = intval($_POST['task_id']);
        try {
            // 先删除对应的核稿状态记录
            $review_stmt = $pdo->prepare("DELETE FROM patent_case_review_status WHERE patent_case_task_id = ?");
            $review_stmt->execute([$task_id]);

            // 再删除处理事项
            $stmt = $pdo->prepare("DELETE FROM patent_case_task WHERE id = ?");
            $result = $stmt->execute([$task_id]);
            echo json_encode(['success' => $result, 'msg' => $result ? null : '删除失败']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_task') {
        // 获取处理事项详情
        $task_id = intval($_POST['task_id']);
        try {
            $stmt = $pdo->prepare("
                SELECT t.*, 
                       h.real_name as handler_name,
                       eh.real_name as external_handler_name,
                       s.real_name as supervisor_name
                FROM patent_case_task t
                LEFT JOIN user h ON t.handler_id = h.id
                LEFT JOIN user eh ON t.external_handler_id = eh.id
                LEFT JOIN user s ON t.supervisor_id = s.id
                WHERE t.id = ?
            ");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();
            if ($task) {
                echo json_encode(['success' => true, 'data' => $task]);
            } else {
                echo json_encode(['success' => false, 'msg' => '任务不存在']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_task_list') {
        // 获取处理事项列表（POST方式）
        $patent_id = intval($_POST['patent_id']);

        // 查询处理事项列表
        $tasks_stmt = $pdo->prepare("
            SELECT t.*, 
                   h.real_name as handler_name,
                   eh.real_name as external_handler_name,
                   s.real_name as supervisor_name,
                   c.real_name as creator_name,
                   m.real_name as modifier_name
            FROM patent_case_task t
            LEFT JOIN user h ON t.handler_id = h.id
            LEFT JOIN user eh ON t.external_handler_id = eh.id
            LEFT JOIN user s ON t.supervisor_id = s.id
            LEFT JOIN user c ON t.creator_id = c.id
            LEFT JOIN user m ON t.modifier_id = m.id
            WHERE t.patent_case_info_id = ?
            ORDER BY t.id ASC
        ");
        $tasks_stmt->execute([$patent_id]);
        $tasks = $tasks_stmt->fetchAll();

        // 输出表格行HTML
        if (empty($tasks)) {
            echo '<tr><td colspan="13" style="text-align:center;color:#888;padding:20px;">暂无处理事项</td></tr>';
        } else {
            foreach ($tasks as $index => $task) {
                echo '<tr>';
                echo '<td>' . ($index + 1) . '</td>';
                echo '<td>' . h($task['task_item']) . '</td>';
                echo '<td>' . h($task['case_stage']) . '</td>';
                echo '<td>' . h($task['task_status']) . '</td>';
                echo '<td>' . h($task['internal_deadline']) . '</td>';
                echo '<td>' . h($task['client_deadline']) . '</td>';
                echo '<td>' . h($task['official_deadline']) . '</td>';
                echo '<td>' . h($task['completion_date']) . '</td>';
                echo '<td>' . h($task['handler_name']) . '</td>';
                echo '<td>' . h($task['external_handler_name']) . '</td>';
                echo '<td>' . h($task['supervisor_name']) . '</td>';
                echo '<td>' . h($task['remarks']) . '</td>';
                echo '<td>';
                echo '<button type="button" class="btn-mini" onclick="editTask(' . $task['id'] . ')">编辑</button>';
                echo '<button type="button" class="btn-mini" onclick="deleteTask(' . $task['id'] . ')">删除</button>';
                echo '</td>';
                echo '</tr>';
            }
        }
        exit;
    }
}

// 处理GET请求的get_task_list
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_task_list') {
    $patent_id = intval($_GET['patent_id']);

    // 查询处理事项列表
    $tasks_stmt = $pdo->prepare("
        SELECT t.*, 
               h.real_name as handler_name,
               eh.real_name as external_handler_name,
               s.real_name as supervisor_name,
               c.real_name as creator_name,
               m.real_name as modifier_name
        FROM patent_case_task t
        LEFT JOIN user h ON t.handler_id = h.id
        LEFT JOIN user eh ON t.external_handler_id = eh.id
        LEFT JOIN user s ON t.supervisor_id = s.id
        LEFT JOIN user c ON t.creator_id = c.id
        LEFT JOIN user m ON t.modifier_id = m.id
        WHERE t.patent_case_info_id = ?
        ORDER BY t.id ASC
    ");
    $tasks_stmt->execute([$patent_id]);
    $tasks = $tasks_stmt->fetchAll();

    // 输出表格行HTML
    if (empty($tasks)) {
        echo '<tr><td colspan="13" style="text-align:center;color:#888;padding:20px;">暂无处理事项</td></tr>';
    } else {
        foreach ($tasks as $index => $task) {
            echo '<tr>';
            echo '<td>' . ($index + 1) . '</td>';
            echo '<td>' . h($task['task_item']) . '</td>';
            echo '<td>' . h($task['case_stage']) . '</td>';
            echo '<td>' . h($task['task_status']) . '</td>';
            echo '<td>' . h($task['internal_deadline']) . '</td>';
            echo '<td>' . h($task['client_deadline']) . '</td>';
            echo '<td>' . h($task['official_deadline']) . '</td>';
            echo '<td>' . h($task['completion_date']) . '</td>';
            echo '<td>' . h($task['handler_name']) . '</td>';
            echo '<td>' . h($task['external_handler_name']) . '</td>';
            echo '<td>' . h($task['supervisor_name']) . '</td>';
            echo '<td>' . h($task['remarks']) . '</td>';
            echo '<td>';
            echo '<button type="button" class="btn-mini" onclick="editTask(' . $task['id'] . ')">编辑</button>';
            echo '<button type="button" class="btn-mini" onclick="deleteTask(' . $task['id'] . ')">删除</button>';
            echo '</td>';
            echo '</tr>';
        }
    }
    exit;
}

// 获取patent_id
$patent_id = 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $patent_id = intval($_GET['id']);
} elseif (isset($_SESSION['edit_patent_id']) && intval($_SESSION['edit_patent_id']) > 0) {
    $patent_id = intval($_SESSION['edit_patent_id']);
    unset($_SESSION['edit_patent_id']);
}

if ($patent_id <= 0) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未指定要编辑的专利ID</div>';
    exit;
}

// 查询专利信息
$patent_stmt = $pdo->prepare("SELECT * FROM patent_case_info WHERE id = ?");
$patent_stmt->execute([$patent_id]);
$patent = $patent_stmt->fetch();
if (!$patent) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未找到该专利信息</div>';
    exit;
}

// 查询用户列表（用于下拉框）
$users = $pdo->query("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name")->fetchAll();
$users_options = [];
foreach ($users as $user) {
    $users_options[$user['id']] = $user['real_name'];
}

// 查询处理事项列表
$tasks_stmt = $pdo->prepare("
    SELECT t.*, 
           h.real_name as handler_name,
           eh.real_name as external_handler_name,
           s.real_name as supervisor_name,
           c.real_name as creator_name,
           m.real_name as modifier_name
    FROM patent_case_task t
    LEFT JOIN user h ON t.handler_id = h.id
    LEFT JOIN user eh ON t.external_handler_id = eh.id
    LEFT JOIN user s ON t.supervisor_id = s.id
    LEFT JOIN user c ON t.creator_id = c.id
    LEFT JOIN user m ON t.modifier_id = m.id
    WHERE t.patent_case_info_id = ?
    ORDER BY t.id ASC
");
$tasks_stmt->execute([$patent_id]);
$tasks = $tasks_stmt->fetchAll();

// 定义tab列表
$tabs = [
    'basic' => '基本信息',
    'extend' => '扩展信息',
    'applicant' => '著录项目',
    'fee' => '费用信息',
    'file' => '文件列表',
];

// 输出下拉框所需JS资源
render_select_search_assets();
?>
<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-return" onclick="returnToList()"><i class="icon-cancel"></i> 返回列表</button>
    </div>
    <h3 style="text-align:center;margin-bottom:15px;">编辑专利</h3>
    <div id="patent-tabs-bar" style="margin-bottom:10px;">
        <?php foreach ($tabs as $key => $label): ?>
            <button type="button" class="btn-mini tab-btn<?= $key === 'basic' ? ' active' : '' ?>" data-tab="<?= $key ?>"><?= $label ?></button>
        <?php endforeach; ?>
    </div>
    <div id="patent-tab-content" style="min-height:320px;"></div>

    <!-- 案件处理事项部分 -->
    <div style="margin-top:20px;border-top:2px solid #e0e0e0;padding-top:15px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <h4 style="margin:0;color:#333;">案件处理事项</h4>
            <button type="button" class="btn-mini" onclick="openAddTaskModal()"><i class="icon-add"></i> 新增处理事项</button>
        </div>

        <table class="module-table" style="width:100%;">
            <thead>
                <tr>
                    <th style="width:40px;">序号</th>
                    <th style="width:120px;">处理事项</th>
                    <th style="width:80px;">案件阶段</th>
                    <th style="width:80px;">处理状态</th>
                    <th style="width:90px;">内部期限</th>
                    <th style="width:90px;">客户期限</th>
                    <th style="width:90px;">官方期限</th>
                    <th style="width:90px;">完成日</th>
                    <th style="width:80px;">处理人</th>
                    <th style="width:80px;">对外处理人</th>
                    <th style="width:80px;">核稿人</th>
                    <th style="width:100px;">备注</th>
                    <th style="width:80px;">操作</th>
                </tr>
            </thead>
            <tbody id="task-list-tbody">
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="13" style="text-align:center;color:#888;padding:20px;">暂无处理事项</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $index => $task): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= h($task['task_item']) ?></td>
                            <td><?= h($task['case_stage']) ?></td>
                            <td><?= h($task['task_status']) ?></td>
                            <td><?= h($task['internal_deadline']) ?></td>
                            <td><?= h($task['client_deadline']) ?></td>
                            <td><?= h($task['official_deadline']) ?></td>
                            <td><?= h($task['completion_date']) ?></td>
                            <td><?= h($task['handler_name']) ?></td>
                            <td><?= h($task['external_handler_name']) ?></td>
                            <td><?= h($task['supervisor_name']) ?></td>
                            <td><?= h($task['remarks']) ?></td>
                            <td>
                                <button type="button" class="btn-mini" onclick="editTask(<?= $task['id'] ?>)">编辑</button>
                                <button type="button" class="btn-mini" onclick="deleteTask(<?= $task['id'] ?>)">删除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 新建处理事项模态框 -->
<div id="add-task-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:8px;padding:20px;width:90%;max-width:800px;max-height:90%;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;border-bottom:1px solid #eee;padding-bottom:10px;">
            <h4 style="margin:0;">新增处理事项</h4>
            <button type="button" onclick="closeAddTaskModal()" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>

        <form id="add-task-form">
            <input type="hidden" name="action" value="save_task">
            <input type="hidden" name="patent_case_info_id" value="<?= $patent_id ?>">

            <table class="module-table" style="width:100%;">
                <tr>
                    <td class="module-label module-req">处理事项</td>
                    <td><input type="text" name="task_item" class="module-input" style="background:#fff;" required></td>
                    <td class="module-label">案件阶段</td>
                    <td><input type="text" name="case_stage" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">处理状态</td>
                    <td>
                        <select name="task_status" class="module-input" style="background:#fff;">
                            <option value="">请选择</option>
                            <option value="完成">完成</option>
                            <option value="配案中">配案中</option>
                            <option value="撰写中">撰写中</option>
                            <option value="递交中">递交中</option>
                            <option value="内部审核">内部审核</option>
                            <option value="外部审核">外部审核</option>
                            <option value="暂停/客户延期">暂停/客户延期</option>
                            <option value="结案">结案</option>
                        </select>
                    </td>
                    <td class="module-label">处理事项系数</td>
                    <td>
                        <select name="task_rule_count" class="module-input" style="background:#fff;">
                            <option value="">请选择</option>
                            <option value="实质">实质</option>
                            <option value="非实质">非实质</option>
                            <option value="形式">形式</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">内部期限</td>
                    <td><input type="date" name="internal_deadline" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">客户期限</td>
                    <td><input type="date" name="client_deadline" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">官方期限</td>
                    <td><input type="date" name="official_deadline" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">处理人</td>
                    <td><?php render_select_search('handler_id', $users_options, ''); ?></td>
                </tr>
                <tr>
                    <td class="module-label">对外处理人</td>
                    <td><?php render_select_search('external_handler_id', $users_options, ''); ?></td>
                    <td class="module-label">核稿人</td>
                    <td><?php render_select_search('supervisor_id', $users_options, ''); ?></td>
                </tr>
                <tr>
                    <td class="module-label">初稿日</td>
                    <td><input type="date" name="first_draft_date" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">定稿日</td>
                    <td><input type="date" name="final_draft_date" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">返稿日</td>
                    <td><input type="date" name="return_date" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">完成日</td>
                    <td><input type="date" name="completion_date" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">送合作所日</td>
                    <td><input type="date" name="send_to_firm_date" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">内部定稿日</td>
                    <td><input type="date" name="internal_final_date" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">翻译字数</td>
                    <td><input type="number" name="translation_word_count" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">合同编号</td>
                    <td><input type="text" name="contract_number" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">是否紧急</td>
                    <td>
                        <label><input type="radio" name="is_urgent" value="1"> 是</label>
                        <label><input type="radio" name="is_urgent" value="0" checked> 否</label>
                    </td>
                    <td class="module-label">备注</td>
                    <td><textarea name="remarks" class="module-textarea" style="background:#fff;"></textarea></td>
                </tr>
            </table>

            <div style="text-align:center;margin-top:20px;">
                <button type="submit" class="btn-mini">保存</button>
                <button type="button" onclick="closeAddTaskModal()" class="btn-mini">取消</button>
            </div>
        </form>
    </div>
</div>

<!-- 编辑处理事项模态框 -->
<div id="edit-task-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:8px;padding:20px;width:90%;max-width:800px;max-height:90%;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;border-bottom:1px solid #eee;padding-bottom:10px;">
            <h4 style="margin:0;">编辑处理事项</h4>
            <button type="button" onclick="closeEditTaskModal()" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>

        <form id="edit-task-form">
            <input type="hidden" name="action" value="save_task">
            <input type="hidden" name="patent_case_info_id" value="<?= $patent_id ?>">
            <input type="hidden" name="task_id" id="edit-task-id">

            <table class="module-table" style="width:100%;">
                <tr>
                    <td class="module-label module-req">处理事项</td>
                    <td><input type="text" name="task_item" class="module-input" style="background:#fff;" required></td>
                    <td class="module-label">案件阶段</td>
                    <td><input type="text" name="case_stage" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">处理状态</td>
                    <td>
                        <select name="task_status" class="module-input" style="background:#fff;">
                            <option value="">请选择</option>
                            <option value="完成">完成</option>
                            <option value="配案中">配案中</option>
                            <option value="撰写中">撰写中</option>
                            <option value="递交中">递交中</option>
                            <option value="内部审核">内部审核</option>
                            <option value="外部审核">外部审核</option>
                            <option value="暂停/客户延期">暂停/客户延期</option>
                            <option value="结案">结案</option>
                        </select>
                    </td>
                    <td class="module-label">处理事项系数</td>
                    <td>
                        <select name="task_rule_count" class="module-input" style="background:#fff;">
                            <option value="">请选择</option>
                            <option value="实质">实质</option>
                            <option value="非实质">非实质</option>
                            <option value="形式">形式</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="module-label">内部期限</td>
                    <td><input type="date" name="internal_deadline" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">客户期限</td>
                    <td><input type="date" name="client_deadline" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">官方期限</td>
                    <td><input type="date" name="official_deadline" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">处理人</td>
                    <td><?php render_select_search('handler_id', $users_options, ''); ?></td>
                </tr>
                <tr>
                    <td class="module-label">对外处理人</td>
                    <td><?php render_select_search('external_handler_id', $users_options, ''); ?></td>
                    <td class="module-label">核稿人</td>
                    <td><?php render_select_search('supervisor_id', $users_options, ''); ?></td>
                </tr>
                <tr>
                    <td class="module-label">初稿日</td>
                    <td><input type="date" name="first_draft_date" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">定稿日</td>
                    <td><input type="date" name="final_draft_date" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">返稿日</td>
                    <td><input type="date" name="return_date" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">完成日</td>
                    <td><input type="date" name="completion_date" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">送合作所日</td>
                    <td><input type="date" name="send_to_firm_date" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">内部定稿日</td>
                    <td><input type="date" name="internal_final_date" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">翻译字数</td>
                    <td><input type="number" name="translation_word_count" class="module-input" style="background:#fff;"></td>
                    <td class="module-label">合同编号</td>
                    <td><input type="text" name="contract_number" class="module-input" style="background:#fff;"></td>
                </tr>
                <tr>
                    <td class="module-label">是否紧急</td>
                    <td>
                        <label><input type="radio" name="is_urgent" value="1"> 是</label>
                        <label><input type="radio" name="is_urgent" value="0" checked> 否</label>
                    </td>
                    <td class="module-label">备注</td>
                    <td><textarea name="remarks" class="module-textarea" style="background:#fff;"></textarea></td>
                </tr>
                <tr id="file-upload-row">
                    <td class="module-label">上传附件</td>
                    <td colspan="3">
                        <div style="margin-bottom:8px;">
                            <label>申请书：</label>
                            <input type="text" id="file-name-application" placeholder="文件命名（可选）" style="width:120px;">
                            <input type="file" id="file-application" style="display:inline-block;width:auto;">
                            <button type="button" class="btn-mini" id="btn-upload-application">上传</button>
                            <div id="list-application" style="margin-top:4px;"></div>
                        </div>
                        <div style="margin-bottom:8px;">
                            <label>说明书：</label>
                            <input type="text" id="file-name-specification" placeholder="文件命名（可选）" style="width:120px;">
                            <input type="file" id="file-specification" style="display:inline-block;width:auto;">
                            <button type="button" class="btn-mini" id="btn-upload-specification">上传</button>
                            <div id="list-specification" style="margin-top:4px;"></div>
                        </div>
                        <div style="margin-bottom:8px;">
                            <label>权利要求书：</label>
                            <input type="text" id="file-name-claims" placeholder="文件命名（可选）" style="width:120px;">
                            <input type="file" id="file-claims" style="display:inline-block;width:auto;">
                            <button type="button" class="btn-mini" id="btn-upload-claims">上传</button>
                            <div id="list-claims" style="margin-top:4px;"></div>
                        </div>
                        <div style="margin-bottom:8px;">
                            <label>附图：</label>
                            <input type="text" id="file-name-drawings" placeholder="文件命名（可选）" style="width:120px;">
                            <input type="file" id="file-drawings" style="display:inline-block;width:auto;">
                            <button type="button" class="btn-mini" id="btn-upload-drawings">上传</button>
                            <div id="list-drawings" style="margin-top:4px;"></div>
                        </div>
                        <div>
                            <label>其他：</label>
                            <input type="text" id="file-name-other" placeholder="文件命名（可选，所有文件同名）" style="width:120px;">
                            <input type="file" id="file-other" multiple style="display:inline-block;width:auto;">
                            <button type="button" class="btn-mini" id="btn-upload-other">上传</button>
                            <div id="list-other" style="margin-top:4px;"></div>
                        </div>
                    </td>
                </tr>
            </table>

            <div style="text-align:center;margin-top:20px;">
                <button type="submit" class="btn-mini">保存</button>
                <button type="button" onclick="closeEditTaskModal()" class="btn-mini">取消</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        var patentId = <?= $patent_id ?>;

        function loadTab(tabName) {
            var content = document.getElementById('patent-tab-content');
            content.innerHTML = '<div style="padding:40px;text-align:center;color:#888;">正在加载...</div>';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'modules/patent_management/edit_tabs/' + tabName + '.php?patent_id=' + patentId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
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
                        content.innerHTML = tempDiv.innerHTML;

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

                            // 加载完tab内容后，自动绑定事件
                            if (typeof window.initPatentTabEvents === 'function') {
                                window.initPatentTabEvents();
                            }
                        }, 300);
                    } else {
                        content.innerHTML = '<div style="padding:40px;text-align:center;color:#f44336;">加载失败</div>';
                    }
                }
            };
            xhr.send();
        }

        // tab切换
        document.querySelectorAll('.tab-btn').forEach(function(btn) {
            btn.onclick = function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                loadTab(btn.getAttribute('data-tab'));
            };
        });

        // 默认加载基本信息tab
        loadTab('basic');

        // 初始化下拉框
        setTimeout(function() {
            if (typeof window.initSelectSearchControls === 'function') {
                window.initSelectSearchControls();
            }
        }, 500);
    })();

    // 处理事项相关函数
    function openAddTaskModal() {
        document.getElementById('add-task-modal').style.display = 'flex';
        // 清空新建表单
        document.getElementById('add-task-form').reset();
    }

    function closeAddTaskModal() {
        document.getElementById('add-task-modal').style.display = 'none';
    }

    function openEditTaskModal(taskId) {
        document.getElementById('edit-task-modal').style.display = 'flex';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'modules/patent_management/edit_patent.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            var task = response.data;

                            // 安全地设置字段值的函数（针对编辑表单）
                            function setEditFieldValue(selector, value) {
                                var element = document.querySelector('#edit-task-form ' + selector);
                                if (element) {
                                    element.value = value || '';
                                }
                            }

                            // 填充基本字段
                            setEditFieldValue('input[name="task_id"]', task.id);
                            setEditFieldValue('input[name="task_item"]', task.task_item);
                            setEditFieldValue('input[name="case_stage"]', task.case_stage);
                            setEditFieldValue('select[name="task_status"]', task.task_status);
                            setEditFieldValue('select[name="task_rule_count"]', task.task_rule_count);
                            setEditFieldValue('input[name="internal_deadline"]', task.internal_deadline);
                            setEditFieldValue('input[name="client_deadline"]', task.client_deadline);
                            setEditFieldValue('input[name="official_deadline"]', task.official_deadline);
                            setEditFieldValue('input[name="first_draft_date"]', task.first_draft_date);
                            setEditFieldValue('input[name="final_draft_date"]', task.final_draft_date);
                            setEditFieldValue('input[name="return_date"]', task.return_date);
                            setEditFieldValue('input[name="completion_date"]', task.completion_date);
                            setEditFieldValue('input[name="send_to_firm_date"]', task.send_to_firm_date);
                            setEditFieldValue('input[name="internal_final_date"]', task.internal_final_date);
                            setEditFieldValue('input[name="translation_word_count"]', task.translation_word_count);
                            setEditFieldValue('input[name="contract_number"]', task.contract_number);
                            setEditFieldValue('textarea[name="remarks"]', task.remarks);

                            // 处理下拉搜索框 - 使用查询返回的用户名称
                            if (task.handler_id) {
                                setEditFieldValue('input[name="handler_id"]', task.handler_id);
                                setEditFieldValue('input[name="handler_id_display"]', task.handler_name || '');
                            }
                            if (task.external_handler_id) {
                                setEditFieldValue('input[name="external_handler_id"]', task.external_handler_id);
                                setEditFieldValue('input[name="external_handler_id_display"]', task.external_handler_name || '');
                            }
                            if (task.supervisor_id) {
                                setEditFieldValue('input[name="supervisor_id"]', task.supervisor_id);
                                setEditFieldValue('input[name="supervisor_id_display"]', task.supervisor_name || '');
                            }

                            // 处理单选框
                            var urgentRadios = document.querySelectorAll('#edit-task-form input[name="is_urgent"]');
                            urgentRadios.forEach(function(radio) {
                                radio.checked = (radio.value == (task.is_urgent || '0'));
                            });

                            // 绑定文件上传功能
                            bindTaskFileUpload(task.id);

                        } else {
                            alert('加载任务数据失败: ' + (response.msg || '未知错误'));
                        }
                    } catch (e) {
                        alert('加载任务数据失败，服务器响应格式错误');
                    }
                } else {
                    alert('加载任务数据失败，网络错误');
                }
            }
        };

        xhr.onerror = function() {
            alert('加载任务数据失败，网络请求失败');
        };

        xhr.send('action=get_task&task_id=' + taskId);
    }

    function closeEditTaskModal() {
        document.getElementById('edit-task-modal').style.display = 'none';
    }

    function editTask(taskId) {
        openEditTaskModal(taskId);
    }

    function deleteTask(taskId) {
        if (!confirm('确定要删除这个处理事项吗？')) return;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'modules/patent_management/edit_patent.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    alert(res.success ? '删除成功' : ('删除失败: ' + (res.msg || '未知错误')));
                    if (res.success) {
                        // 重新加载处理事项列表
                        loadTaskList();
                    }
                } catch (e) {
                    alert('删除失败，服务器返回无效响应');
                }
            }
        };
        xhr.send('action=delete_task&task_id=' + taskId);
    }

    // 新建表单提交处理
    document.getElementById('add-task-form').onsubmit = function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'modules/patent_management/edit_patent.php', true);

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('保存成功');
                            closeAddTaskModal();
                            loadTaskList();
                        } else {
                            alert('保存失败: ' + (response.msg || '未知错误'));
                        }
                    } catch (e) {
                        alert('保存失败，服务器响应格式错误');
                    }
                } else {
                    alert('保存失败，网络错误');
                }
            }
        };

        xhr.onerror = function() {
            alert('保存失败，网络请求失败');
        };

        xhr.send(formData);
    };

    // 编辑表单提交处理
    document.getElementById('edit-task-form').onsubmit = function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'modules/patent_management/edit_patent.php', true);

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('保存成功');
                            closeEditTaskModal();
                            loadTaskList();
                        } else {
                            alert('保存失败: ' + (response.msg || '未知错误'));
                        }
                    } catch (e) {
                        alert('保存失败，服务器响应格式错误');
                    }
                } else {
                    alert('保存失败，网络错误');
                }
            }
        };

        xhr.onerror = function() {
            alert('保存失败，网络请求失败');
        };

        xhr.send(formData);
    };

    // 加载任务列表
    function loadTaskList() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'modules/patent_management/edit_patent.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    document.getElementById('task-list-tbody').innerHTML = xhr.responseText;
                } else {
                    alert('加载任务列表失败，HTTP错误: ' + xhr.status);
                }
            }
        };

        xhr.onerror = function() {
            alert('加载任务列表失败，网络请求失败');
        };

        xhr.send('action=get_task_list&patent_id=<?php echo $patent_id; ?>');
    }

    // 返回列表功能
    function returnToList() {
        if (window.parent && window.parent.openTab && window.parent.closeTab) {
            // 获取当前tab的ID（专利编辑tab）
            var currentTabId = getCurrentTabId();

            // 获取来源页面信息，如果没有则默认返回专利查询页面
            var sourceModule = sessionStorage.getItem('patent_edit_source_module') || '1';
            var sourceMenu = sessionStorage.getItem('patent_edit_source_menu') || '5';
            var sourceSubMenu = sessionStorage.getItem('patent_edit_source_submenu') || '0';

            // 清除来源页面信息
            sessionStorage.removeItem('patent_edit_source_module');
            sessionStorage.removeItem('patent_edit_source_menu');
            sessionStorage.removeItem('patent_edit_source_submenu');

            // 根据来源页面返回到对应页面
            window.parent.openTab(parseInt(sourceModule), parseInt(sourceMenu), sourceSubMenu === 'null' ? null : parseInt(sourceSubMenu));

            // 关闭当前的专利编辑tab
            if (currentTabId) {
                window.parent.closeTab(currentTabId);
            }
        } else {
            // 如果不在框架中，直接跳转
            window.location.href = 'modules/patent_management/case_management/patent_search.php';
        }
    }

    // 获取当前tab的ID
    function getCurrentTabId() {
        // 专利编辑tab的ID格式：1_6_null（专利管理-专利编辑-无子菜单）
        return '1_6_n';
    }

    // 获取下载文件名，确保包含正确的扩展名
    function getDownloadFileName(customName, originalName) {
        // 如果没有自定义文件名，使用原文件名
        if (!customName || customName.trim() === '') {
            return originalName || '未知文件';
        }

        // 如果没有原文件名，直接返回自定义文件名
        if (!originalName || originalName.trim() === '') {
            return customName;
        }

        // 获取原文件的扩展名
        var originalExt = '';
        var dotIndex = originalName.lastIndexOf('.');
        if (dotIndex > 0 && dotIndex < originalName.length - 1) {
            originalExt = originalName.substring(dotIndex);
        }

        // 检查自定义文件名是否已有扩展名
        var customDotIndex = customName.lastIndexOf('.');
        var hasCustomExt = customDotIndex > 0 && customDotIndex < customName.length - 1;

        // 如果自定义文件名没有扩展名，且原文件有扩展名，则补上扩展名
        if (!hasCustomExt && originalExt) {
            return customName + originalExt;
        }

        // 否则直接返回自定义文件名
        return customName;
    }

    // 文件上传/删除/回显逻辑（复制自申请人列表页面）
    function renderTaskFileList(taskId, fileType, listDivId) {
        var listDiv = document.getElementById(listDivId);
        listDiv.innerHTML = '加载中...';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'modules/patent_management/task_file_upload.php?action=list&task_id=' + taskId + '&file_type=' + encodeURIComponent(fileType), true);
        xhr.onload = function() {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success && res.files.length > 0) {
                    var html = '<table class="module-table" style="margin:0;width:100%;border-collapse:collapse;"><thead><tr>' +
                        '<th style="width:180px;border:1px solid #e0e0e0;">文件名</th>' +
                        '<th style="width:180px;border:1px solid #e0e0e0;">原文件名</th>' +
                        '<th style="width:140px;border:1px solid #e0e0e0;">上传时间</th>' +
                        '<th style="width:120px;border:1px solid #e0e0e0;">操作</th>' +
                        '</tr></thead><tbody>';
                    res.files.forEach(function(f) {
                        // 确定下载文件名：优先使用用户自定义文件名，否则使用原文件名
                        var downloadName = getDownloadFileName(f.file_name, f.original_file_name);
                        html += '<tr>' +
                            '<td style="border:1px solid #e0e0e0;">' + (f.file_name || '') + '</td>' +
                            '<td style="border:1px solid #e0e0e0;">' + (f.original_file_name || f.file_name || '') + '</td>' +
                            '<td style="border:1px solid #e0e0e0;">' + (f.created_at ? f.created_at.substr(0, 16) : '') + '</td>' +
                            '<td style="border:1px solid #e0e0e0;white-space:nowrap;">' +
                            '<a href="' + f.file_path + '" download="' + downloadName + '" class="btn-mini" style="margin-right:8px;">下载</a>' +
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
                            xhr2.open('POST', 'modules/patent_management/task_file_upload.php', true);
                            xhr2.onload = function() {
                                renderTaskFileList(taskId, fileType, listDivId);
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

    function bindTaskFileUpload(taskId) {
        // 先解绑旧事件，防止重复绑定
        var btnApplication = document.getElementById('btn-upload-application');
        var btnSpecification = document.getElementById('btn-upload-specification');
        var btnClaims = document.getElementById('btn-upload-claims');
        var btnDrawings = document.getElementById('btn-upload-drawings');
        var btnOther = document.getElementById('btn-upload-other');
        if (btnApplication) btnApplication.onclick = null;
        if (btnSpecification) btnSpecification.onclick = null;
        if (btnClaims) btnClaims.onclick = null;
        if (btnDrawings) btnDrawings.onclick = null;
        if (btnOther) btnOther.onclick = null;

        // 申请书
        document.getElementById('btn-upload-application').onclick = function() {
            var fileInput = document.getElementById('file-application');
            var nameInput = document.getElementById('file-name-application');
            if (!fileInput.files[0]) {
                alert('请选择文件');
                return;
            }
            var fd = new FormData();
            fd.append('action', 'upload');
            fd.append('task_id', taskId);
            fd.append('file_type', '申请书');
            fd.append('file', fileInput.files[0]);
            fd.append('file_name', nameInput ? nameInput.value : '');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/task_file_upload.php', true);
            xhr.onload = function() {
                fileInput.value = '';
                if (nameInput) nameInput.value = '';
                renderTaskFileList(taskId, '申请书', 'list-application');
            };
            xhr.onerror = function(e) {
                alert('上传失败');
            };
            xhr.send(fd);
        };

        // 说明书
        document.getElementById('btn-upload-specification').onclick = function() {
            var fileInput = document.getElementById('file-specification');
            var nameInput = document.getElementById('file-name-specification');
            if (!fileInput.files[0]) {
                alert('请选择文件');
                return;
            }
            var fd = new FormData();
            fd.append('action', 'upload');
            fd.append('task_id', taskId);
            fd.append('file_type', '说明书');
            fd.append('file', fileInput.files[0]);
            fd.append('file_name', nameInput ? nameInput.value : '');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/task_file_upload.php', true);
            xhr.onload = function() {
                fileInput.value = '';
                if (nameInput) nameInput.value = '';
                renderTaskFileList(taskId, '说明书', 'list-specification');
            };
            xhr.onerror = function(e) {
                alert('上传失败');
            };
            xhr.send(fd);
        };

        // 权利要求书
        document.getElementById('btn-upload-claims').onclick = function() {
            var fileInput = document.getElementById('file-claims');
            var nameInput = document.getElementById('file-name-claims');
            if (!fileInput.files[0]) {
                alert('请选择文件');
                return;
            }
            var fd = new FormData();
            fd.append('action', 'upload');
            fd.append('task_id', taskId);
            fd.append('file_type', '权利要求书');
            fd.append('file', fileInput.files[0]);
            fd.append('file_name', nameInput ? nameInput.value : '');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/task_file_upload.php', true);
            xhr.onload = function() {
                fileInput.value = '';
                if (nameInput) nameInput.value = '';
                renderTaskFileList(taskId, '权利要求书', 'list-claims');
            };
            xhr.onerror = function(e) {
                alert('上传失败');
            };
            xhr.send(fd);
        };

        // 附图
        document.getElementById('btn-upload-drawings').onclick = function() {
            var fileInput = document.getElementById('file-drawings');
            var nameInput = document.getElementById('file-name-drawings');
            if (!fileInput.files[0]) {
                alert('请选择文件');
                return;
            }
            var fd = new FormData();
            fd.append('action', 'upload');
            fd.append('task_id', taskId);
            fd.append('file_type', '附图');
            fd.append('file', fileInput.files[0]);
            fd.append('file_name', nameInput ? nameInput.value : '');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/patent_management/task_file_upload.php', true);
            xhr.onload = function() {
                fileInput.value = '';
                if (nameInput) nameInput.value = '';
                renderTaskFileList(taskId, '附图', 'list-drawings');
            };
            xhr.onerror = function(e) {
                alert('上传失败');
            };
            xhr.send(fd);
        };

        // 其他（多文件）
        document.getElementById('btn-upload-other').onclick = function() {
            var fileInput = document.getElementById('file-other');
            var nameInput = document.getElementById('file-name-other');
            if (!fileInput.files.length) {
                alert('请选择文件');
                return;
            }
            var files = Array.from(fileInput.files);
            var uploadNext = function(idx) {
                if (idx >= files.length) {
                    fileInput.value = '';
                    if (nameInput) nameInput.value = '';
                    renderTaskFileList(taskId, '其他', 'list-other');
                    return;
                }
                var fd = new FormData();
                fd.append('action', 'upload');
                fd.append('task_id', taskId);
                fd.append('file_type', '其他');
                fd.append('file', files[idx]);
                fd.append('file_name', nameInput ? nameInput.value : '');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'modules/patent_management/task_file_upload.php', true);
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
        renderTaskFileList(taskId, '申请书', 'list-application');
        renderTaskFileList(taskId, '说明书', 'list-specification');
        renderTaskFileList(taskId, '权利要求书', 'list-claims');
        renderTaskFileList(taskId, '附图', 'list-drawings');
        renderTaskFileList(taskId, '其他', 'list-other');
    }
</script>