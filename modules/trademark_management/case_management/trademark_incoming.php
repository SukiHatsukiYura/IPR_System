<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 商标来文功能 - 商标管理/案件管理模块下的商标来文功能

// 统一选项声明
// 业务类型
$business_types = ['商标注册申请', '商标续展', '商标变更', '商标转让', '商标许可备案', '商标撤销', '商标无效', '商标异议', '商标复审', '马德里国际注册', '其他'];
// 案件状态
$case_statuses = ['请选择', '未递交', '已递交', '暂缓申请', '受理', '初审合格', '初审', '公开', '实审', '补正', '审查', '一通', '二通', '三通', '四通', '五通', '六通', '七通', '八通', '一补', '九通', '二补', '三补', '视为撤回', '主动撤回', '驳回', '复审', '无效', '视为放弃', '主动放弃', '授权', '待领证', '维持', '终止', '结案', '届满', '中止', '保全', '诉讼', '办理登记手续', '复审受理', '公告', '视为未提出'];
// 来文类型
$incoming_types = ['审查意见通知书', '授权通知书', '驳回决定', '补正通知书', '缴费通知书', '年费缴费通知书', '复审通知书', '无效宣告通知书', '商标证书', '登记手续通知书', '视为撤回通知书', '恢复权利通知书', '其他官方文件'];
// 来文状态
$incoming_statuses = ['待处理', '处理中', '已处理', '已归档'];
// 紧急程度
$urgency_levels = ['普通', '紧急', '特急'];

// 查询所有在职用户用于下拉
$user_stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name ASC");
$user_stmt->execute();
$users = $user_stmt->fetchAll();

// 查询所有部门用于下拉
$dept_stmt = $pdo->prepare("SELECT id, dept_name FROM department WHERE is_active=1 ORDER BY dept_name ASC");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll();

// 查询所有客户用于下拉
$customer_stmt = $pdo->prepare("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn ASC");
$customer_stmt->execute();
$customers = $customer_stmt->fetchAll();

// 查询所有商标案件用于下拉（限制数量以提高性能）
$trademark_stmt = $pdo->prepare("SELECT id, case_code, case_name FROM trademark_case_info ORDER BY case_code ASC LIMIT 1000");
$trademark_stmt->execute();
$trademarks = $trademark_stmt->fetchAll();

// 处理AJAX请求
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    $page = max(1, intval($_GET['page'] ?? 1));
    $page_size = min(max(1, intval($_GET['page_size'] ?? 10)), 100);
    $offset = ($page - 1) * $page_size;
    $where = [];
    $params = [];

    // 合并查询条件
    $search_fields = [
        'case_code' => 'LIKE',
        'case_name' => 'LIKE',
        'application_no' => 'LIKE',
        'business_dept_id' => '=',
        'client_id' => '=',
        'handler_id' => '=',
        'incoming_type' => '=',
        'status' => '=',
        'urgency' => '='
    ];

    foreach ($search_fields as $field => $op) {
        if (!empty($_GET[$field])) {
            if (in_array($field, ['incoming_type', 'status', 'urgency'])) {
                $where[] = "d.$field " . ($op == 'LIKE' ? "LIKE ?" : "= ?");
            } else {
                $where[] = "t.$field " . ($op == 'LIKE' ? "LIKE ?" : "= ?");
            }
            $params[] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
        }
    }

    // 处理来文日期范围
    if (!empty($_GET['incoming_date_start'])) {
        $where[] = "d.incoming_date >= ?";
        $params[] = $_GET['incoming_date_start'];
    }
    if (!empty($_GET['incoming_date_end'])) {
        $where[] = "d.incoming_date <= ?";
        $params[] = $_GET['incoming_date_end'];
    }

    // 处理期限日期范围
    if (!empty($_GET['deadline_start'])) {
        $where[] = "d.deadline >= ?";
        $params[] = $_GET['deadline_start'];
    }
    if (!empty($_GET['deadline_end'])) {
        $where[] = "d.deadline <= ?";
        $params[] = $_GET['deadline_end'];
    }

    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    // 构建查询SQL
    $count_sql = "SELECT COUNT(*) FROM trademark_incoming_document d
                  LEFT JOIN trademark_case_info t ON d.trademark_case_info_id = t.id" . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);

    $sql = "SELECT d.*, t.case_code, t.case_name, t.application_no,
            (SELECT dept_name FROM department WHERE id = t.business_dept_id) as business_dept_name,
            (SELECT customer_name_cn FROM customer WHERE id = t.client_id) as client_name,
            (SELECT real_name FROM user WHERE id = d.handler_id) as handler_name,
            (SELECT real_name FROM user WHERE id = d.creator_id) as creator_name
            FROM trademark_incoming_document d
            LEFT JOIN trademark_case_info t ON d.trademark_case_info_id = t.id" . $sql_where .
        " ORDER BY d.incoming_date DESC, d.id DESC LIMIT ?, ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$offset, $page_size]));
    $documents = $stmt->fetchAll();

    $html = '';
    if (empty($documents)) {
        $html = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($documents as $index => $doc) {
            $html .= '<tr data-id="' . $doc['id'] . '">';
            $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($doc['case_code'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc['case_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc['incoming_type'] ?? '') . '</td>';
            $html .= '<td>' . ($doc['incoming_date'] ? date('Y-m-d', strtotime($doc['incoming_date'])) : '') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc['official_number'] ?? '') . '</td>';

            // 期限日期显示（带颜色提醒）
            $deadline_html = '';
            if ($doc['deadline']) {
                $deadline_date = strtotime($doc['deadline']);
                $now = time();
                $days_diff = ceil(($deadline_date - $now) / (24 * 60 * 60));

                $color = '#000';
                if ($days_diff < 0) {
                    $color = '#f44336'; // 已过期 - 红色
                } elseif ($days_diff <= 7) {
                    $color = '#ff9800'; // 7天内 - 橙色
                } elseif ($days_diff <= 30) {
                    $color = '#ffeb3b'; // 30天内 - 黄色
                    $color = '#333';
                }

                $deadline_html = '<span style="color:' . $color . ';">' . date('Y-m-d', $deadline_date) . '</span>';
            }
            $html .= '<td>' . $deadline_html . '</td>';

            // 紧急程度显示
            $urgency_color = '#666';
            if ($doc['urgency'] === '紧急') {
                $urgency_color = '#ff9800';
            } elseif ($doc['urgency'] === '特急') {
                $urgency_color = '#f44336';
            }
            $html .= '<td><span style="color:' . $urgency_color . ';">' . htmlspecialchars($doc['urgency'] ?? '') . '</span></td>';

            // 状态显示
            $status_color = '#666';
            if ($doc['status'] === '待处理') {
                $status_color = '#ff9800';
            } elseif ($doc['status'] === '处理中') {
                $status_color = '#2196f3';
            } elseif ($doc['status'] === '已处理') {
                $status_color = '#4caf50';
            } elseif ($doc['status'] === '已归档') {
                $status_color = '#9e9e9e';
            }
            $html .= '<td><span style="color:' . $status_color . ';">' . htmlspecialchars($doc['status'] ?? '') . '</span></td>';

            $html .= '<td>' . htmlspecialchars($doc['handler_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc['creator_name'] ?? '') . '</td>';
            $html .= '<td>' . ($doc['created_at'] ? date('Y-m-d', strtotime($doc['created_at'])) : '') . '</td>';
            $html .= '</tr>';
        }
    }
    echo json_encode([
        'success' => true,
        'html' => $html,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);
    exit;
}

// 格式化数据为通用下拉框函数所需的关联数组格式
$departments_options = [];
foreach ($departments as $dept) {
    $departments_options[$dept['id']] = $dept['dept_name'];
}

$customers_options = [];
foreach ($customers as $customer) {
    $customers_options[$customer['id']] = $customer['customer_name_cn'];
}

$users_options = [];
foreach ($users as $user) {
    $users_options[$user['id']] = $user['real_name'];
}

$trademarks_options = [];
foreach ($trademarks as $trademark) {
    $trademarks_options[$trademark['id']] = $trademark['case_code'] . ' - ' . $trademark['case_name'];
}

// 引入搜索下拉框资源
render_select_search_assets();
?>
<div class="module-panel">
    <div class="module-btns" style="display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
            <button type="button" class="btn-add"><i class="icon-add"></i> 新增来文</button>
            <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
            <button type="button" class="btn-upload" disabled><i class="icon-add"></i> 上传附件</button>
            <button type="button" class="btn-delete" disabled><i class="icon-cancel"></i> 删除</button>
        </div>
    </div>

    <?php render_info_notice("商标来文管理：记录和跟踪从商标局等官方机构收到的各类文件和通知，包括审查意见、授权通知、缴费通知等。支持按来文类型、状态、紧急程度等条件查询", 'info', 'icon-list'); ?>

    <form id="search-form" class="module-form" autocomplete="off">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="page_size" value="10">
        <table class="module-table" style="margin-bottom:15px;">
            <tr>
                <td class="module-label">我方文号：</td>
                <td><input type="text" name="case_code" class="module-input"></td>
                <td class="module-label">案件名称：</td>
                <td><input type="text" name="case_name" class="module-input"></td>
                <td class="module-label">申请号：</td>
                <td><input type="text" name="application_no" class="module-input"></td>
            </tr>
            <tr>
                <td class="module-label">承办部门：</td>
                <td><?php render_select_search('business_dept_id', $departments_options, $_GET['business_dept_id'] ?? ''); ?></td>
                <td class="module-label">客户名称：</td>
                <td><?php render_select_search('client_id', $customers_options, $_GET['client_id'] ?? ''); ?></td>
                <td class="module-label">处理人：</td>
                <td><?php render_select_search('handler_id', $users_options, $_GET['handler_id'] ?? ''); ?></td>
            </tr>
            <tr>
                <td class="module-label">来文类型：</td>
                <td><select name="incoming_type" class="module-input">
                        <option value="">--全部--</option><?php foreach ($incoming_types as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">来文状态：</td>
                <td><select name="status" class="module-input">
                        <option value="">--全部--</option><?php foreach ($incoming_statuses as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">紧急程度：</td>
                <td><select name="urgency" class="module-input">
                        <option value="">--全部--</option><?php foreach ($urgency_levels as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
            </tr>
            <tr>
                <td class="module-label">来文日期：</td>
                <td>
                    <input type="date" name="incoming_date_start" class="module-input" style="width:120px;"> 至
                    <input type="date" name="incoming_date_end" class="module-input" style="width:120px;">
                </td>
                <td class="module-label">期限日期：</td>
                <td>
                    <input type="date" name="deadline_start" class="module-input" style="width:120px;"> 至
                    <input type="date" name="deadline_end" class="module-input" style="width:120px;">
                </td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </form>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:50px;text-align:center;">序号</th>
                <th style="width:100px;text-align:center;">我方文号</th>
                <th style="width:180px;">案件名称</th>
                <th style="width:120px;">来文类型</th>
                <th style="width:100px;">来文日期</th>
                <th style="width:120px;">官方文号</th>
                <th style="width:100px;">期限日期</th>
                <th style="width:80px;">紧急程度</th>
                <th style="width:80px;">状态</th>
                <th style="width:100px;">处理人</th>
                <th style="width:100px;">创建人</th>
                <th style="width:100px;">创建时间</th>
            </tr>
        </thead>
        <tbody id="incoming-list">
            <tr>
                <td colspan="12" style="text-align:center;padding:20px 0;">正在加载数据...</td>
            </tr>
        </tbody>
    </table>
    <div class="module-pagination">
        <span>共 <span id="total-records">0</span> 条记录，每页</span>
        <select id="page-size-select">
            <option value="10" selected>10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        <span>条，当前 <span id="current-page">1</span>/<span id="total-pages">1</span> 页</span>
        <button type="button" class="btn-page-go" data-page="1" id="btn-first-page">首页</button>
        <button type="button" class="btn-page-go" data-page="" id="btn-prev-page">上一页</button>
        <button type="button" class="btn-page-go" data-page="" id="btn-next-page">下一页</button>
        <button type="button" class="btn-page-go" data-page="" id="btn-last-page">末页</button>
        <span>跳转到</span>
        <input type="number" id="page-input" min="1" value="1">
        <span>页</span>
        <button type="button" id="btn-page-jump" class="btn-page-go">确定</button>
    </div>
</div>

<!-- 新增/编辑来文模态框 -->
<div id="incoming-modal" class="module-modal" style="display:none;">
    <div class="module-modal-content" style="width:800px;">
        <div class="module-modal-header">
            <h3 class="module-modal-title" id="incoming-modal-title">新增来文记录</h3>
            <button class="module-modal-close">&times;</button>
        </div>
        <div class="module-modal-body" style="padding:20px;">
            <form id="incoming-form">
                <input type="hidden" name="id" id="incoming-id">
                <input type="hidden" name="action" id="incoming-action" value="add_incoming">
                <table class="module-table">
                    <tr>
                        <td class="module-label module-req">*商标案件：</td>
                        <td colspan="3">
                            <?php render_select_search('trademark_id', $trademarks_options, $_GET['trademark_id'] ?? ''); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label module-req">*来文类型：</td>
                        <td>
                            <select name="incoming_type" id="incoming-type" class="module-input" required>
                                <option value="">请选择</option>
                                <?php foreach ($incoming_types as $v): ?>
                                    <option value="<?= h($v) ?>"><?= h($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="module-label module-req">*来文日期：</td>
                        <td><input type="date" name="incoming_date" id="incoming-date" class="module-input" required></td>
                    </tr>
                    <tr>
                        <td class="module-label">官方文号：</td>
                        <td><input type="text" name="official_number" id="official-number" class="module-input"></td>
                        <td class="module-label">期限日期：</td>
                        <td><input type="date" name="deadline" id="deadline" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">紧急程度：</td>
                        <td>
                            <select name="urgency" id="urgency" class="module-input">
                                <?php foreach ($urgency_levels as $v): ?>
                                    <option value="<?= h($v) ?>" <?= $v === '普通' ? 'selected' : '' ?>><?= h($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="module-label">状态：</td>
                        <td>
                            <select name="status" id="status" class="module-input">
                                <?php foreach ($incoming_statuses as $v): ?>
                                    <option value="<?= h($v) ?>" <?= $v === '待处理' ? 'selected' : '' ?>><?= h($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">处理人：</td>
                        <td colspan="3">
                            <?php render_select_search('handler_id', $users_options, ''); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">来文内容：</td>
                        <td colspan="3">
                            <textarea name="content" id="content" class="module-textarea" rows="4" style="width:100%;"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">备注：</td>
                        <td colspan="3">
                            <textarea name="remarks" id="remarks" class="module-textarea" rows="3" style="width:100%;"></textarea>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="module-modal-footer">
            <button type="button" class="btn-theme" id="btn-save-incoming">保存</button>
            <button type="button" class="btn-cancel" id="btn-cancel-incoming">取消</button>
        </div>
    </div>
</div>

<!-- 文件上传模态框 -->
<div id="upload-modal" class="module-modal" style="display:none;">
    <div class="module-modal-content" style="width:700px;">
        <div class="module-modal-header">
            <h3 class="module-modal-title">上传来文附件</h3>
            <button class="module-modal-close">&times;</button>
        </div>
        <div class="module-modal-body" style="padding:20px;">
            <div style="margin-bottom:20px;">
                <strong>来文信息：</strong>
                <span id="upload-document-info" style="color:#666;"></span>
            </div>

            <!-- 文件上传区域 -->
            <div class="module-file-upload" style="margin-bottom:20px;">
                <table class="module-table">
                    <tr>
                        <!-- 标记为必填 -->
                        <td class="module-label module-req" style="width:100px;">*文件类型：</td>
                        <td>
                            <select id="upload-file-type" class="module-input" style="width:150px;">
                                <option value="">请选择</option>
                                <option value="官方文件">官方文件</option>
                                <option value="附件">附件</option>
                                <option value="其他">其他</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <!-- 标记为必填 -->
                        <td class="module-label module-req">*选择文件：</td>
                        <td>
                            <input type="file" id="upload-file-input" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.zip,.rar" class="module-input">
                            <small style="color:#666;display:block;margin-top:5px;">
                                支持格式：PDF、Word、图片、压缩包等，最大10MB
                            </small>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">备注说明：</td>
                        <td>
                            <textarea id="upload-remarks" class="module-textarea" rows="3" style="width:100%;" placeholder="可选，文件说明"></textarea>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 上传进度 -->
            <div id="upload-progress" style="display:none;margin-bottom:20px;">
                <div style="background:#f0f0f0;border-radius:10px;overflow:hidden;">
                    <div id="upload-progress-bar" style="height:20px;background:#29b6b0;width:0%;transition:width 0.3s;"></div>
                </div>
                <div id="upload-progress-text" style="text-align:center;margin-top:10px;">准备上传...</div>
            </div>

            <!-- 已上传文件列表 -->
            <div style="margin-top:20px;">
                <h4>已上传文件：</h4>
                <div id="uploaded-files-list" style="max-height:200px;overflow-y:auto;border:1px solid #e0e0e0;border-radius:4px;padding:10px;">
                    <div style="text-align:center;color:#999;padding:20px;">正在加载...</div>
                </div>
            </div>
        </div>
        <div class="module-modal-footer">
            <button type="button" class="btn-theme" id="btn-start-upload">开始上传</button>
            <button type="button" class="btn-cancel" id="btn-cancel-upload">关闭</button>
        </div>
    </div>
</div>

<script>
    (function() {
        var form = document.getElementById('search-form'),
            incomingList = document.getElementById('incoming-list'),
            btnSearch = document.querySelector('.btn-search'),
            btnReset = document.querySelector('.btn-reset'),
            btnAdd = document.querySelector('.btn-add'),
            btnEdit = document.querySelector('.btn-edit'),
            btnUpload = document.querySelector('.btn-upload'),
            btnDelete = document.querySelector('.btn-delete'),
            totalRecordsEl = document.getElementById('total-records'),
            currentPageEl = document.getElementById('current-page'),
            totalPagesEl = document.getElementById('total-pages'),
            pageSizeSelect = document.getElementById('page-size-select'),
            btnFirstPage = document.getElementById('btn-first-page'),
            btnPrevPage = document.getElementById('btn-prev-page'),
            btnNextPage = document.getElementById('btn-next-page'),
            btnLastPage = document.getElementById('btn-last-page'),
            pageInput = document.getElementById('page-input'),
            btnPageJump = document.getElementById('btn-page-jump'),
            incomingModal = document.getElementById('incoming-modal'),
            incomingForm = document.getElementById('incoming-form'),
            incomingModalTitle = document.getElementById('incoming-modal-title');

        var currentPage = 1,
            pageSize = 10,
            totalPages = 1,
            selectedId = null;

        // 通用模态框操作
        function toggleModal(show) {
            incomingModal.style.display = show ? 'flex' : 'none';
        }

        // 文件上传模态框操作
        function toggleUploadModal(show) {
            var uploadModal = document.getElementById('upload-modal');
            uploadModal.style.display = show ? 'flex' : 'none';
        }

        // 加载来文数据
        window.loadIncomingData = function() {
            incomingList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            selectedId = null;
            btnEdit.disabled = true;
            btnUpload.disabled = true;
            btnDelete.disabled = true;

            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);
            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') params.append(pair[0], pair[1]);
            }

            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/trademark_incoming.php';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                incomingList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                            } else {
                                incomingList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            incomingList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        incomingList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        };

        // 绑定表格行点击事件
        function bindTableRowClick() {
            incomingList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.onclick = function() {
                    incomingList.querySelectorAll('tr').forEach(function(r) {
                        r.classList.remove('module-selected');
                    });
                    this.classList.add('module-selected');
                    selectedId = parseInt(this.getAttribute('data-id'));
                    btnEdit.disabled = false;
                    btnUpload.disabled = false;
                    btnDelete.disabled = false;
                };
            });
        }

        // 更新分页按钮状态
        function updatePaginationButtons() {
            btnFirstPage.disabled = currentPage <= 1;
            btnPrevPage.disabled = currentPage <= 1;
            btnNextPage.disabled = currentPage >= totalPages;
            btnLastPage.disabled = currentPage >= totalPages;

            btnPrevPage.setAttribute('data-page', currentPage - 1);
            btnNextPage.setAttribute('data-page', currentPage + 1);
            btnLastPage.setAttribute('data-page', totalPages);

            pageInput.value = currentPage;
            pageInput.max = totalPages;
        }

        // 搜索
        btnSearch.onclick = function() {
            currentPage = 1;
            form.querySelector('input[name="page"]').value = currentPage;
            loadIncomingData();
        };

        // 重置
        btnReset.onclick = function() {
            form.reset();
            document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
            document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
            currentPage = 1;
            form.querySelector('input[name="page"]').value = currentPage;
            loadIncomingData();
        };

        // 新增来文
        btnAdd.onclick = function() {
            document.getElementById('incoming-id').value = '';
            document.getElementById('incoming-action').value = 'add_incoming';
            incomingModalTitle.textContent = '新增来文记录';
            incomingForm.reset();

            // 重置商标下拉框
            var trademarkInput = incomingForm.querySelector('input[name="trademark_id_display"]');
            var trademarkHidden = incomingForm.querySelector('input[name="trademark_id"]');
            if (trademarkInput) trademarkInput.value = '';
            if (trademarkHidden) trademarkHidden.value = '';

            // 重置处理人下拉框
            var handlerInput = incomingForm.querySelector('input[name="handler_id_display"]');
            var handlerHidden = incomingForm.querySelector('input[name="handler_id"]');
            if (handlerInput) handlerInput.value = '';
            if (handlerHidden) handlerHidden.value = '';

            toggleModal(true);
        };

        // 编辑来文
        btnEdit.onclick = function() {
            if (!selectedId) {
                alert('请先选择要修改的来文记录');
                return;
            }

            document.getElementById('incoming-id').value = selectedId;
            document.getElementById('incoming-action').value = 'update_incoming';
            incomingModalTitle.textContent = '编辑来文记录';

            // 通过AJAX获取详细数据来填充表单
            var xhr = new XMLHttpRequest();
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/trademark_incoming_api.php';
            xhr.open('GET', requestUrl + '?action=get_incoming_detail&id=' + selectedId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            var data = response.data;
                            // 填充表单数据
                            // 设置商标下拉框（通用下拉框组件）
                            var trademarkHidden = incomingForm.querySelector('input[name="trademark_id"]');
                            var trademarkDisplay = incomingForm.querySelector('input[name="trademark_id_display"]');
                            if (trademarkHidden && trademarkDisplay) {
                                trademarkHidden.value = data.trademark_case_info_id;
                                // 从商标选项中找到对应的显示文本
                                var trademarkOptions = <?php echo json_encode($trademarks_options); ?>;
                                if (trademarkOptions[data.trademark_case_info_id]) {
                                    trademarkDisplay.value = trademarkOptions[data.trademark_case_info_id];
                                }
                            }

                            document.getElementById('incoming-type').value = data.incoming_type;
                            document.getElementById('incoming-date').value = data.incoming_date;
                            document.getElementById('official-number').value = data.official_number || '';
                            document.getElementById('deadline').value = data.deadline || '';
                            document.getElementById('urgency').value = data.urgency;
                            document.getElementById('status').value = data.status;
                            document.getElementById('content').value = data.content || '';
                            document.getElementById('remarks').value = data.remarks || '';

                            // 设置处理人下拉框（通用下拉框组件）
                            if (data.handler_id) {
                                var handlerHidden = incomingForm.querySelector('input[name="handler_id"]');
                                var handlerDisplay = incomingForm.querySelector('input[name="handler_id_display"]');
                                if (handlerHidden && handlerDisplay) {
                                    handlerHidden.value = data.handler_id;
                                    // 从用户选项中找到对应的显示文本
                                    var userOptions = <?php echo json_encode($users_options); ?>;
                                    if (userOptions[data.handler_id]) {
                                        handlerDisplay.value = userOptions[data.handler_id];
                                    }
                                }
                            }

                            toggleModal(true);
                        } else {
                            alert('获取数据失败：' + response.msg);
                        }
                    } catch (e) {
                        alert('获取数据失败');
                    }
                }
            };
            xhr.send();
        };

        // 删除来文
        btnDelete.onclick = function() {
            if (!selectedId) {
                alert('请先选择要删除的来文记录');
                return;
            }

            if (confirm('确定要删除这条来文记录吗？')) {
                var xhr = new XMLHttpRequest();
                var baseUrl = window.location.href.split('?')[0];
                var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/trademark_incoming_api.php';
                xhr.open('POST', requestUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                alert('删除成功');
                                loadIncomingData();
                            } else {
                                alert('删除失败：' + response.msg);
                            }
                        } catch (e) {
                            alert('删除失败');
                        }
                    }
                };
                xhr.send('action=delete_incoming&id=' + selectedId);
            }
        };

        // 上传附件
        btnUpload.onclick = function() {
            if (!selectedId) {
                alert('请先选择要上传附件的来文记录');
                return;
            }

            // 获取选中记录的信息
            var selectedRow = incomingList.querySelector('tr[data-id="' + selectedId + '"]');
            if (!selectedRow) {
                alert('未找到选中的记录');
                return;
            }

            // 从表格行中获取基本信息显示
            var cells = selectedRow.getElementsByTagName('td');
            var caseCode = cells[1].textContent.trim();
            var caseName = cells[2].textContent.trim();
            var incomingType = cells[3].textContent.trim();
            var incomingDate = cells[4].textContent.trim();

            // 设置上传模态框的信息
            document.getElementById('upload-document-info').textContent =
                caseCode + ' - ' + caseName + ' (' + incomingType + ', ' + incomingDate + ')';

            // 重置上传表单
            document.getElementById('upload-file-type').value = '';
            document.getElementById('upload-file-input').value = '';
            document.getElementById('upload-remarks').value = '';
            document.getElementById('upload-progress').style.display = 'none';

            // 加载已上传文件列表
            loadUploadedFiles(selectedId);

            // 显示上传模态框
            toggleUploadModal(true);
        };

        // 加载已上传文件列表
        function loadUploadedFiles(documentId) {
            var filesList = document.getElementById('uploaded-files-list');
            filesList.innerHTML = '<div style="text-align:center;color:#999;padding:20px;">正在加载...</div>';

            var xhr = new XMLHttpRequest();
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/trademark_incoming_file_handler.php';
            xhr.open('GET', requestUrl + '?action=get_files&document_id=' + documentId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            renderFilesList(response.data);
                        } else {
                            filesList.innerHTML = '<div style="text-align:center;color:#f44336;padding:20px;">加载失败：' + response.msg + '</div>';
                        }
                    } catch (e) {
                        filesList.innerHTML = '<div style="text-align:center;color:#f44336;padding:20px;">加载失败</div>';
                    }
                }
            };
            xhr.send();
        }

        // 渲染文件列表
        function renderFilesList(files) {
            var filesList = document.getElementById('uploaded-files-list');

            if (files.length === 0) {
                filesList.innerHTML = '<div style="text-align:center;color:#999;padding:20px;">暂无上传文件</div>';
                return;
            }

            var html = '';
            files.forEach(function(file) {
                var fileSize = (file.file_size / 1024).toFixed(1) + ' KB';
                if (file.file_size > 1024 * 1024) {
                    fileSize = (file.file_size / (1024 * 1024)).toFixed(1) + ' MB';
                }

                html += '<div style="display:flex;align-items:center;padding:8px;border-bottom:1px solid #eee;">';
                html += '<div style="flex:1;">';
                html += '<div style="font-weight:bold;color:#333;">' + htmlspecialchars(file.file_name) + '</div>';
                html += '<div style="font-size:12px;color:#666;">';
                html += '类型：' + htmlspecialchars(file.file_type) + ' | ';
                html += '大小：' + fileSize + ' | ';
                html += '上传者：' + htmlspecialchars(file.uploader_name || '未知') + ' | ';
                html += '时间：' + file.created_at;
                html += '</div>';
                if (file.remarks) {
                    html += '<div style="font-size:12px;color:#888;margin-top:2px;">备注：' + htmlspecialchars(file.remarks) + '</div>';
                }
                html += '</div>';
                html += '<div style="margin-left:10px;">';
                html += '<button type="button" class="btn-mini" onclick="downloadFile(' + file.id + ')">下载</button>';
                html += '<button type="button" class="btn-mini" onclick="deleteFile(' + file.id + ')" style="margin-left:5px;background:#f44336;color:#fff;">删除</button>';
                html += '</div>';
                html += '</div>';
            });

            filesList.innerHTML = html;
        }

        // HTML转义函数
        function htmlspecialchars(str) {
            if (!str) return '';
            return str.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // 下载文件
        window.downloadFile = function(fileId) {
            var baseUrl = window.location.href.split('?')[0];
            var downloadUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/trademark_incoming_file_handler.php?action=download_file&file_id=' + fileId;
            window.open(downloadUrl, '_blank');
        };

        // 删除文件
        window.deleteFile = function(fileId) {
            if (!confirm('确定要删除这个文件吗？')) {
                return;
            }

            var xhr = new XMLHttpRequest();
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/trademark_incoming_file_handler.php';
            xhr.open('POST', requestUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('删除成功');
                            loadUploadedFiles(selectedId);
                        } else {
                            alert('删除失败：' + response.msg);
                        }
                    } catch (e) {
                        alert('删除失败');
                    }
                }
            };
            xhr.send('action=delete_file&file_id=' + fileId);
        };

        // 开始上传按钮事件
        document.getElementById('btn-start-upload').onclick = function() {
            var fileType = document.getElementById('upload-file-type').value;
            var fileInput = document.getElementById('upload-file-input');
            var remarks = document.getElementById('upload-remarks').value;

            if (!fileType) {
                alert('请选择文件类型');
                return;
            }

            if (!fileInput.files.length) {
                alert('请选择要上传的文件');
                return;
            }

            var files = Array.from(fileInput.files);
            var uploadCount = 0;
            var successCount = 0;
            var errorMessages = [];

            // 显示进度条
            document.getElementById('upload-progress').style.display = 'block';
            document.getElementById('upload-progress-text').textContent = '开始上传...';
            document.getElementById('btn-start-upload').disabled = true;

            files.forEach(function(file, index) {
                // 检查文件大小
                if (file.size > 10 * 1024 * 1024) {
                    uploadCount++;
                    errorMessages.push('文件 ' + file.name + ' 超过10MB限制');
                    checkUploadComplete();
                    return;
                }

                var formData = new FormData();
                formData.append('action', 'upload_file');
                formData.append('document_id', selectedId);
                formData.append('file_type', fileType);
                formData.append('remarks', remarks);
                formData.append('file', file);

                var xhr = new XMLHttpRequest();
                var baseUrl = window.location.href.split('?')[0];
                var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/trademark_incoming_file_handler.php';
                xhr.open('POST', requestUrl, true);

                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        var percentComplete = (e.loaded / e.total) * 100;
                        document.getElementById('upload-progress-bar').style.width = percentComplete + '%';
                        document.getElementById('upload-progress-text').textContent = '上传中... ' + Math.round(percentComplete) + '%';
                    }
                };

                xhr.onload = function() {
                    uploadCount++;
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            successCount++;
                        } else {
                            errorMessages.push('文件 ' + file.name + ' 上传失败：' + response.msg);
                        }
                    } catch (e) {
                        errorMessages.push('文件 ' + file.name + ' 上传失败：响应解析错误');
                    }
                    checkUploadComplete();
                };

                xhr.onerror = function() {
                    uploadCount++;
                    errorMessages.push('文件 ' + file.name + ' 上传失败：网络错误');
                    checkUploadComplete();
                };

                xhr.send(formData);
            });

            function checkUploadComplete() {
                if (uploadCount === files.length) {
                    document.getElementById('upload-progress').style.display = 'none';
                    document.getElementById('btn-start-upload').disabled = false;

                    if (successCount === files.length) {
                        alert('所有文件上传成功');
                        // 重置表单
                        document.getElementById('upload-file-type').value = '';
                        document.getElementById('upload-file-input').value = '';
                        document.getElementById('upload-remarks').value = '';
                        // 刷新文件列表
                        loadUploadedFiles(selectedId);
                    } else if (successCount > 0) {
                        alert('部分文件上传成功 (' + successCount + '/' + files.length + ')：\n' + errorMessages.join('\n'));
                        loadUploadedFiles(selectedId);
                    } else {
                        alert('上传失败：\n' + errorMessages.join('\n'));
                    }
                }
            }
        };

        // 取消按钮
        document.getElementById('btn-cancel-incoming').onclick = function() {
            toggleModal(false);
        };

        // 模态框关闭按钮
        document.querySelector('.module-modal-close').onclick = function() {
            toggleModal(false);
        };

        // 上传模态框关闭按钮
        document.querySelector('#upload-modal .module-modal-close').onclick = function() {
            toggleUploadModal(false);
        };

        // 上传模态框底部关闭按钮
        document.getElementById('btn-cancel-upload').onclick = function() {
            toggleUploadModal(false);
        };

        // 保存来文记录
        document.getElementById('btn-save-incoming').onclick = function() {
            // 验证必填字段
            var trademarkId = incomingForm.querySelector('input[name="trademark_id"]').value;
            var incomingType = document.getElementById('incoming-type').value;
            var incomingDate = document.getElementById('incoming-date').value;

            if (!trademarkId) {
                alert('请选择商标案件');
                var trademarkDisplay = incomingForm.querySelector('input[name="trademark_id_display"]');
                if (trademarkDisplay) trademarkDisplay.focus();
                return;
            }
            if (!incomingType) {
                alert('请选择来文类型');
                document.getElementById('incoming-type').focus();
                return;
            }
            if (!incomingDate) {
                alert('请选择来文日期');
                document.getElementById('incoming-date').focus();
                return;
            }

            var formData = new FormData(incomingForm);
            var xhr = new XMLHttpRequest();
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/trademark_incoming_api.php';
            xhr.open('POST', requestUrl, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('保存成功');
                            toggleModal(false);
                            loadIncomingData();
                        } else {
                            alert('保存失败：' + response.msg);
                        }
                    } catch (e) {
                        alert('保存失败');
                    }
                }
            };
            xhr.send(formData);
        };

        // 分页事件
        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            form.querySelector('input[name="page"]').value = currentPage;
            form.querySelector('input[name="page_size"]').value = pageSize;
            loadIncomingData();
        };

        // 分页按钮事件
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-page-go')) {
                var targetPage = parseInt(e.target.getAttribute('data-page'));
                if (targetPage && targetPage !== currentPage && targetPage >= 1 && targetPage <= totalPages) {
                    currentPage = targetPage;
                    form.querySelector('input[name="page"]').value = currentPage;
                    loadIncomingData();
                }
            }
        });

        // 页面跳转
        btnPageJump.onclick = function() {
            var targetPage = parseInt(pageInput.value);
            if (targetPage && targetPage !== currentPage && targetPage >= 1 && targetPage <= totalPages) {
                currentPage = targetPage;
                form.querySelector('input[name="page"]').value = currentPage;
                loadIncomingData();
            }
        };

        // 初始化加载数据
        loadIncomingData();
    })();
</script>

<style>
    /* 选中行样式 */
    .module-table tbody tr.module-selected {
        background-color: #e3f2fd !important;
    }

    /* 表格行悬停效果 */
    .module-table tbody tr:hover {
        background-color: #f5f5f5;
    }

    /* 模态框中的下拉框统一使用白色背景，与通用下拉框组件保持一致 */
    .module-modal .module-input {
        background: #fff;
    }

    /* 模态框中的文本域也使用白色背景 */
    .module-modal .module-textarea {
        background: #fff;
    }

    /* 模态框中的下拉框焦点状态 */
    .module-modal .module-input:focus {
        border-color: #29b6b0;
        background-color: #f8ffff;
    }

    /* 模态框中的文本域焦点状态 */
    .module-modal .module-textarea:focus {
        border-color: #29b6b0;
        background-color: #f8ffff;
    }
</style>