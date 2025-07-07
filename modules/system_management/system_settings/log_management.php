<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 统一选项声明
$options = [
    'login_statuses' => ['1' => '成功', '0' => '失败'],
    'device_types' => ['PC', 'Mobile', 'Tablet'],
    'browsers' => ['Chrome', 'Firefox', 'Safari', 'Edge', 'IE', 'Opera', 'Other']
];

// 查询所有在职用户用于下拉
$user_stmt = $pdo->prepare("SELECT id, real_name, username FROM user WHERE is_active=1 ORDER BY real_name ASC");
$user_stmt->execute();
$users = $user_stmt->fetchAll();

// 处理AJAX请求
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');

    $tab = $_GET['tab'] ?? 'logs';

    if ($tab === 'logs') {
        // 登录日志查询
        $page = max(1, intval($_GET['page'] ?? 1));
        $page_size = min(max(10, intval($_GET['page_size'] ?? 10)), 100);
        $offset = ($page - 1) * $page_size;

        $search_fields = [
            'username' => 'LIKE',
            'user_id' => '=',
            'login_ip' => 'LIKE',
            'login_status' => '=',
            'device_type' => 'LIKE',
            'browser_name' => 'LIKE',
            'os_name' => 'LIKE'
        ];

        $where = [];
        $params = [];

        foreach ($search_fields as $field => $op) {
            if (isset($_GET[$field]) && $_GET[$field] !== '') {
                $where[] = "l.$field " . ($op == 'LIKE' ? "LIKE :$field" : "= :$field");
                $params[$field] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
            }
        }

        if (!empty($_GET['login_date_start'])) {
            $where[] = "DATE(l.login_time) >= :login_date_start";
            $params['login_date_start'] = $_GET['login_date_start'];
        }
        if (!empty($_GET['login_date_end'])) {
            $where[] = "DATE(l.login_time) <= :login_date_end";
            $params['login_date_end'] = $_GET['login_date_end'];
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $count_sql = "SELECT COUNT(*) FROM user_login_log l $where_sql";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();

        $sql = "SELECT l.*, u.real_name 
                FROM user_login_log l 
                LEFT JOIN user u ON l.user_id = u.id 
                $where_sql 
                ORDER BY l.login_time DESC 
                LIMIT $page_size OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $html = '';
        if ($logs) {
            foreach ($logs as $i => $log) {
                $seq = ($page - 1) * $page_size + $i + 1;
                $status = $log['login_status'] ? '<span class="status-success">成功</span>' : '<span class="status-failed">失败</span>';
                $logout_time = $log['logout_time'] ? date('Y-m-d H:i:s', strtotime($log['logout_time'])) : '-';

                $html .= '<tr>';
                $html .= '<td style="text-align:center;">' . $seq . '</td>';
                $html .= '<td>' . h($log['username']) . '</td>';
                $html .= '<td>' . h($log['real_name'] ?? '-') . '</td>';
                $html .= '<td>' . date('Y-m-d H:i:s', strtotime($log['login_time'])) . '</td>';
                $html .= '<td>' . h($log['login_ip']) . '</td>';
                $html .= '<td style="text-align:center;">' . $status . '</td>';
                $html .= '<td>' . h($log['device_type'] ?? '-') . '</td>';
                $html .= '<td>' . h($log['browser_name'] ?? '-') . '</td>';
                $html .= '<td>' . h($log['os_name'] ?? '-') . '</td>';
                $html .= '<td>' . $logout_time . '</td>';
                $html .= '</tr>';
            }
        } else {
            $html = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
        }

        $total_pages = ceil($total / $page_size);
        echo json_encode([
            'success' => true,
            'html' => $html,
            'total_records' => $total,
            'current_page' => $page,
            'total_pages' => $total_pages
        ]);
    } elseif ($tab === 'stats') {
        // 登录次数统计查询
        $page = max(1, intval($_GET['page'] ?? 1));
        $page_size = min(max(10, intval($_GET['page_size'] ?? 10)), 100);
        $offset = ($page - 1) * $page_size;

        $search_fields = [
            'username' => 'LIKE',
            'user_id' => '='
        ];

        $where = [];
        $params = [];

        foreach ($search_fields as $field => $op) {
            if (isset($_GET[$field]) && $_GET[$field] !== '') {
                $where[] = "s.$field " . ($op == 'LIKE' ? "LIKE :$field" : "= :$field");
                $params[$field] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
            }
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $count_sql = "SELECT COUNT(*) FROM user_login_stats s $where_sql";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();

        $sql = "SELECT s.*, u.real_name 
                FROM user_login_stats s 
                LEFT JOIN user u ON s.user_id = u.id 
                $where_sql 
                ORDER BY s.total_login_count DESC 
                LIMIT $page_size OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stats = $stmt->fetchAll();

        $html = '';
        if ($stats) {
            foreach ($stats as $i => $stat) {
                $seq = ($page - 1) * $page_size + $i + 1;
                $last_login = $stat['last_login_time'] ? date('Y-m-d H:i:s', strtotime($stat['last_login_time'])) : '-';
                $last_success = $stat['last_success_login_time'] ? date('Y-m-d H:i:s', strtotime($stat['last_success_login_time'])) : '-';
                $last_failed = $stat['last_failed_login_time'] ? date('Y-m-d H:i:s', strtotime($stat['last_failed_login_time'])) : '-';

                $html .= '<tr>';
                $html .= '<td style="text-align:center;">' . $seq . '</td>';
                $html .= '<td>' . h($stat['username']) . '</td>';
                $html .= '<td>' . h($stat['real_name'] ?? '-') . '</td>';
                $html .= '<td style="text-align:center;">' . intval($stat['total_login_count']) . '</td>';
                $html .= '<td style="text-align:center;">' . intval($stat['success_login_count']) . '</td>';
                $html .= '<td style="text-align:center;">' . intval($stat['failed_login_count']) . '</td>';
                $html .= '<td style="text-align:center;">' . intval($stat['today_login_count']) . '</td>';
                $html .= '<td style="text-align:center;">' . intval($stat['this_month_login_count']) . '</td>';
                $html .= '<td>' . $last_login . '</td>';
                $html .= '<td>' . h($stat['last_login_ip'] ?? '-') . '</td>';
                $html .= '<td style="text-align:center;">' . intval($stat['consecutive_failed_count']) . '</td>';
                $html .= '</tr>';
            }
        } else {
            $html = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
        }

        $total_pages = ceil($total / $page_size);
        echo json_encode([
            'success' => true,
            'html' => $html,
            'total_records' => $total,
            'current_page' => $page,
            'total_pages' => $total_pages
        ]);
    }
    exit;
}

// 辅助函数
function h($v)
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function render_user_search($name, $users, $get_val)
{
    $val = isset($_GET[$name]) ? intval($_GET[$name]) : 0;
    $display = '';
    foreach ($users as $u) {
        if ($u['id'] == $val) {
            $display = htmlspecialchars($u['real_name'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
    return '<div class="module-select-search-box">'
        . '<input type="text" class="module-input module-select-search-input" name="' . $name . '_display" value="' . $display . '" readonly placeholder="点击选择" data-realname="' . $display . '">'
        . '<input type="hidden" name="' . $name . '" value="' . ($val ? $val : '') . '">'
        . '<div class="module-select-search-list" style="display:none;">'
        .   '<input type="text" class="module-select-search-list-input" placeholder="搜索姓名">'
        .   '<div class="module-select-search-list-items"></div>'
        . '</div>'
        . '</div>';
}
?>
<div class="module-panel">
    <!-- 选项卡导航 -->
    <div class="tab-nav">
        <button type="button" class="tab-btn active" data-tab="logs">登录日志</button>
        <button type="button" class="tab-btn" data-tab="stats">登录次数统计</button>
    </div>

    <!-- 登录日志选项卡 -->
    <div class="tab-content active" id="tab-logs">
        <div class="module-btns" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
        </div>

        <form id="search-form-logs" class="module-form" autocomplete="off">
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="page_size" value="10">
            <table class="module-table" style="margin-bottom:15px;">
                <tr>
                    <td class="module-label">用户名：</td>
                    <td><input type="text" name="username" class="module-input"></td>
                    <td class="module-label">用户：</td>
                    <td><?= render_user_search('user_id', $users, '') ?></td>
                    <td class="module-label">登录IP：</td>
                    <td><input type="text" name="login_ip" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">登录状态：</td>
                    <td><select name="login_status" class="module-input">
                            <option value="">--全部--</option>
                            <?php foreach ($options['login_statuses'] as $k => $v): ?>
                                <option value="<?= h($k) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select></td>
                    <td class="module-label">设备类型：</td>
                    <td><select name="device_type" class="module-input">
                            <option value="">--全部--</option>
                            <?php foreach ($options['device_types'] as $v): ?>
                                <option value="<?= h($v) ?>"><?= h($v) ?></option>
                            <?php endforeach; ?>
                        </select></td>
                    <td class="module-label">浏览器：</td>
                    <td><input type="text" name="browser_name" class="module-input"></td>
                </tr>
                <tr>
                    <td class="module-label">操作系统：</td>
                    <td><input type="text" name="os_name" class="module-input"></td>
                    <td class="module-label">登录日期起：</td>
                    <td><input type="date" name="login_date_start" class="module-input"></td>
                    <td class="module-label">登录日期止：</td>
                    <td><input type="date" name="login_date_end" class="module-input"></td>
                </tr>
            </table>
        </form>

        <table class="module-table">
            <thead>
                <tr style="background:#f2f2f2;">
                    <th style="width:60px;text-align:center;">序号</th>
                    <th style="width:120px;">用户名</th>
                    <th style="width:100px;">姓名</th>
                    <th style="width:150px;">登录时间</th>
                    <th style="width:120px;">登录IP</th>
                    <th style="width:80px;text-align:center;">状态</th>
                    <th style="width:100px;">设备类型</th>
                    <th style="width:120px;">浏览器</th>
                    <th style="width:120px;">操作系统</th>
                    <th style="width:150px;">退出时间</th>
                </tr>
            </thead>
            <tbody id="log-list">
                <tr>
                    <td colspan="10" style="text-align:center;padding:20px 0;">正在加载数据...</td>
                </tr>
            </tbody>
        </table>

        <div class="module-pagination">
            <span>共 <span id="total-records-logs">0</span> 条记录，每页</span>
            <select id="page-size-select-logs">
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>条，当前 <span id="current-page-logs">1</span>/<span id="total-pages-logs">1</span> 页</span>
            <button type="button" class="btn-page-go" data-page="1" id="btn-first-page-logs">首页</button>
            <button type="button" class="btn-page-go" data-page="" id="btn-prev-page-logs">上一页</button>
            <button type="button" class="btn-page-go" data-page="" id="btn-next-page-logs">下一页</button>
            <button type="button" class="btn-page-go" data-page="" id="btn-last-page-logs">末页</button>
            <span>跳转到</span>
            <input type="number" id="page-input-logs" min="1" value="1">
            <span>页</span>
            <button type="button" id="btn-page-jump-logs" class="btn-page-go">确定</button>
        </div>
    </div>

    <!-- 登录次数统计选项卡 -->
    <div class="tab-content" id="tab-stats">
        <div class="module-btns" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-search-stats"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset-stats"><i class="icon-cancel"></i> 重置</button>
        </div>

        <form id="search-form-stats" class="module-form" autocomplete="off">
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="page_size" value="10">
            <table class="module-table" style="margin-bottom:15px;">
                <tr>
                    <td class="module-label">用户名：</td>
                    <td><input type="text" name="username" class="module-input"></td>
                    <td class="module-label">用户：</td>
                    <td><?= render_user_search('user_id_stats', $users, '') ?></td>
                    <td colspan="2"></td>
                </tr>
            </table>
        </form>

        <table class="module-table">
            <thead>
                <tr style="background:#f2f2f2;">
                    <th style="width:60px;text-align:center;">序号</th>
                    <th style="width:120px;">用户名</th>
                    <th style="width:100px;">姓名</th>
                    <th style="width:80px;text-align:center;">总登录次数</th>
                    <th style="width:80px;text-align:center;">成功次数</th>
                    <th style="width:80px;text-align:center;">失败次数</th>
                    <th style="width:80px;text-align:center;">今日登录</th>
                    <th style="width:80px;text-align:center;">本月登录</th>
                    <th style="width:150px;">最后登录时间</th>
                    <th style="width:120px;">最后登录IP</th>
                    <th style="width:80px;text-align:center;">连续失败</th>
                </tr>
            </thead>
            <tbody id="stats-list">
                <tr>
                    <td colspan="11" style="text-align:center;padding:20px 0;">正在加载数据...</td>
                </tr>
            </tbody>
        </table>

        <div class="module-pagination">
            <span>共 <span id="total-records-stats">0</span> 条记录，每页</span>
            <select id="page-size-select-stats">
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>条，当前 <span id="current-page-stats">1</span>/<span id="total-pages-stats">1</span> 页</span>
            <button type="button" class="btn-page-go" data-page="1" id="btn-first-page-stats">首页</button>
            <button type="button" class="btn-page-go" data-page="" id="btn-prev-page-stats">上一页</button>
            <button type="button" class="btn-page-go" data-page="" id="btn-next-page-stats">下一页</button>
            <button type="button" class="btn-page-go" data-page="" id="btn-last-page-stats">末页</button>
            <span>跳转到</span>
            <input type="number" id="page-input-stats" min="1" value="1">
            <span>页</span>
            <button type="button" id="btn-page-jump-stats" class="btn-page-go">确定</button>
        </div>
    </div>
</div>

<style>
    .tab-nav {
        display: flex;
        gap: 2px;
        margin-bottom: 20px;
        border-bottom: 2px solid #e0e0e0;
    }

    .tab-btn {
        padding: 12px 24px;
        background: #f8f9fa;
        border: none;
        border-radius: 4px 4px 0 0;
        cursor: pointer;
        font-size: 14px;
        color: #666;
        transition: all 0.2s;
    }

    .tab-btn.active {
        background: #29b6b0;
        color: white;
        border-bottom: 2px solid #29b6b0;
    }

    .tab-btn:hover:not(.active) {
        background: #e9ecef;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .status-success {
        color: #28a745;
        font-weight: 500;
    }

    .status-failed {
        color: #dc3545;
        font-weight: 500;
    }
</style>

<script>
    (function() {
        var currentTab = 'logs';
        var tabData = {
            logs: {
                currentPage: 1,
                pageSize: 10,
                totalPages: 1
            },
            stats: {
                currentPage: 1,
                pageSize: 10,
                totalPages: 1
            }
        };

        // 选项卡切换
        document.querySelectorAll('.tab-btn').forEach(function(btn) {
            btn.onclick = function() {
                var tab = this.getAttribute('data-tab');
                switchTab(tab);
            };
        });

        function switchTab(tab) {
            currentTab = tab;

            // 更新选项卡按钮状态
            document.querySelectorAll('.tab-btn').forEach(function(btn) {
                btn.classList.remove('active');
            });
            document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.add('active');

            // 更新内容区域
            document.querySelectorAll('.tab-content').forEach(function(content) {
                content.classList.remove('active');
            });
            document.getElementById('tab-' + tab).classList.add('active');

            // 加载对应数据
            if (tab === 'logs') {
                loadLogData();
            } else if (tab === 'stats') {
                loadStatsData();
            }
        }

        // 登录日志相关
        var formLogs = document.getElementById('search-form-logs'),
            btnSearchLogs = document.querySelector('.btn-search'),
            btnResetLogs = document.querySelector('.btn-reset'),
            logList = document.getElementById('log-list'),
            totalRecordsLogsEl = document.getElementById('total-records-logs'),
            currentPageLogsEl = document.getElementById('current-page-logs'),
            totalPagesLogsEl = document.getElementById('total-pages-logs'),
            btnFirstPageLogs = document.getElementById('btn-first-page-logs'),
            btnPrevPageLogs = document.getElementById('btn-prev-page-logs'),
            btnNextPageLogs = document.getElementById('btn-next-page-logs'),
            btnLastPageLogs = document.getElementById('btn-last-page-logs'),
            pageInputLogs = document.getElementById('page-input-logs'),
            btnPageJumpLogs = document.getElementById('btn-page-jump-logs'),
            pageSizeSelectLogs = document.getElementById('page-size-select-logs');

        function loadLogData() {
            logList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';

            var formData = new FormData(formLogs),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('tab', 'logs');
            params.append('page', tabData.logs.currentPage);
            params.append('page_size', tabData.logs.pageSize);

            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') {
                    params.append(pair[0], pair[1]);
                }
            }

            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/system_management/system_settings/log_management.php';

            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                logList.innerHTML = response.html;
                                totalRecordsLogsEl.textContent = response.total_records;
                                currentPageLogsEl.textContent = response.current_page;
                                totalPagesLogsEl.textContent = response.total_pages;
                                tabData.logs.currentPage = parseInt(response.current_page);
                                tabData.logs.totalPages = parseInt(response.total_pages) || 1;
                                updateLogsPaginationButtons();
                            } else {
                                logList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            logList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        logList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function updateLogsPaginationButtons() {
            btnFirstPageLogs.disabled = tabData.logs.currentPage <= 1;
            btnPrevPageLogs.disabled = tabData.logs.currentPage <= 1;
            btnNextPageLogs.disabled = tabData.logs.currentPage >= tabData.logs.totalPages;
            btnLastPageLogs.disabled = tabData.logs.currentPage >= tabData.logs.totalPages;
            btnPrevPageLogs.setAttribute('data-page', tabData.logs.currentPage - 1);
            btnNextPageLogs.setAttribute('data-page', tabData.logs.currentPage + 1);
            btnLastPageLogs.setAttribute('data-page', tabData.logs.totalPages);
            pageInputLogs.max = tabData.logs.totalPages;
            pageInputLogs.value = tabData.logs.currentPage;
        }

        btnSearchLogs.onclick = function() {
            tabData.logs.currentPage = 1;
            loadLogData();
        };

        btnResetLogs.onclick = function() {
            formLogs.reset();
            document.querySelectorAll('#tab-logs .module-select-search-input').forEach(i => i.value = '');
            document.querySelectorAll('#tab-logs .module-select-search-box input[type=hidden]').forEach(i => i.value = '');
            tabData.logs.currentPage = 1;
            loadLogData();
        };

        pageSizeSelectLogs.onchange = function() {
            tabData.logs.pageSize = parseInt(this.value);
            tabData.logs.currentPage = 1;
            loadLogData();
        };

        [btnFirstPageLogs, btnPrevPageLogs, btnNextPageLogs, btnLastPageLogs].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    tabData.logs.currentPage = parseInt(this.getAttribute('data-page'));
                    loadLogData();
                }
            };
        });

        btnPageJumpLogs.onclick = function() {
            var page = parseInt(pageInputLogs.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > tabData.logs.totalPages) page = tabData.logs.totalPages;
            tabData.logs.currentPage = page;
            loadLogData();
        };

        // 登录次数统计相关
        var formStats = document.getElementById('search-form-stats'),
            btnSearchStats = document.querySelector('.btn-search-stats'),
            btnResetStats = document.querySelector('.btn-reset-stats'),
            statsList = document.getElementById('stats-list'),
            totalRecordsStatsEl = document.getElementById('total-records-stats'),
            currentPageStatsEl = document.getElementById('current-page-stats'),
            totalPagesStatsEl = document.getElementById('total-pages-stats'),
            btnFirstPageStats = document.getElementById('btn-first-page-stats'),
            btnPrevPageStats = document.getElementById('btn-prev-page-stats'),
            btnNextPageStats = document.getElementById('btn-next-page-stats'),
            btnLastPageStats = document.getElementById('btn-last-page-stats'),
            pageInputStats = document.getElementById('page-input-stats'),
            btnPageJumpStats = document.getElementById('btn-page-jump-stats'),
            pageSizeSelectStats = document.getElementById('page-size-select-stats');

        function loadStatsData() {
            statsList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';

            var formData = new FormData(formStats),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('tab', 'stats');
            params.append('page', tabData.stats.currentPage);
            params.append('page_size', tabData.stats.pageSize);

            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') {
                    // 将user_id_stats转换为user_id
                    var fieldName = pair[0] === 'user_id_stats' ? 'user_id' : pair[0];
                    params.append(fieldName, pair[1]);
                }
            }

            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/system_management/system_settings/log_management.php';

            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                statsList.innerHTML = response.html;
                                totalRecordsStatsEl.textContent = response.total_records;
                                currentPageStatsEl.textContent = response.current_page;
                                totalPagesStatsEl.textContent = response.total_pages;
                                tabData.stats.currentPage = parseInt(response.current_page);
                                tabData.stats.totalPages = parseInt(response.total_pages) || 1;
                                updateStatsPaginationButtons();
                            } else {
                                statsList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            statsList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        statsList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function updateStatsPaginationButtons() {
            btnFirstPageStats.disabled = tabData.stats.currentPage <= 1;
            btnPrevPageStats.disabled = tabData.stats.currentPage <= 1;
            btnNextPageStats.disabled = tabData.stats.currentPage >= tabData.stats.totalPages;
            btnLastPageStats.disabled = tabData.stats.currentPage >= tabData.stats.totalPages;
            btnPrevPageStats.setAttribute('data-page', tabData.stats.currentPage - 1);
            btnNextPageStats.setAttribute('data-page', tabData.stats.currentPage + 1);
            btnLastPageStats.setAttribute('data-page', tabData.stats.totalPages);
            pageInputStats.max = tabData.stats.totalPages;
            pageInputStats.value = tabData.stats.currentPage;
        }

        btnSearchStats.onclick = function() {
            tabData.stats.currentPage = 1;
            loadStatsData();
        };

        btnResetStats.onclick = function() {
            formStats.reset();
            document.querySelectorAll('#tab-stats .module-select-search-input').forEach(i => i.value = '');
            document.querySelectorAll('#tab-stats .module-select-search-box input[type=hidden]').forEach(i => i.value = '');
            tabData.stats.currentPage = 1;
            loadStatsData();
        };

        pageSizeSelectStats.onchange = function() {
            tabData.stats.pageSize = parseInt(this.value);
            tabData.stats.currentPage = 1;
            loadStatsData();
        };

        [btnFirstPageStats, btnPrevPageStats, btnNextPageStats, btnLastPageStats].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    tabData.stats.currentPage = parseInt(this.getAttribute('data-page'));
                    loadStatsData();
                }
            };
        });

        btnPageJumpStats.onclick = function() {
            var page = parseInt(pageInputStats.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > tabData.stats.totalPages) page = tabData.stats.totalPages;
            tabData.stats.currentPage = page;
            loadStatsData();
        };

        // 用户搜索下拉
        var userData = <?php echo json_encode($users, JSON_UNESCAPED_UNICODE); ?>;

        function bindUserSearch(box) {
            var input = box.querySelector('.module-select-search-input');
            var hidden = box.querySelector('input[type=hidden]');
            var list = box.querySelector('.module-select-search-list');
            var searchInput = list.querySelector('.module-select-search-list-input');
            var itemsDiv = list.querySelector('.module-select-search-list-items');
            var data = userData;

            function renderList(filter) {
                var html = '<div class="module-select-search-item" data-id="">--全部--</div>',
                    found = false;
                data.forEach(function(item) {
                    var displayName = item.real_name;
                    if (!filter || displayName.indexOf(filter) !== -1) {
                        html += '<div class="module-select-search-item" data-id="' + item.id + '">' + displayName + '</div>';
                        found = true;
                    }
                });
                if (!found && filter) html += '<div class="no-match">无匹配</div>';
                itemsDiv.innerHTML = html;
            }

            input.onclick = function() {
                renderList('');
                list.style.display = 'block';
                searchInput.value = '';
                searchInput.focus();
            };

            searchInput.oninput = function() {
                renderList(searchInput.value.trim());
            };

            document.addEventListener('click', function(e) {
                if (!box.contains(e.target)) list.style.display = 'none';
            });

            itemsDiv.onmousedown = function(e) {
                var item = e.target.closest('.module-select-search-item');
                if (item) {
                    input.value = item.textContent === '--全部--' ? '' : item.textContent;
                    hidden.value = item.getAttribute('data-id');
                    list.style.display = 'none';
                }
            };
        }

        document.querySelectorAll('.module-select-search-box').forEach(bindUserSearch);

        // 初始加载数据
        loadLogData();
    })();
</script>