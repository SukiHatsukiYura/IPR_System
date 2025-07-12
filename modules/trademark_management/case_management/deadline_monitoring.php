<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 期限监控功能 - 商标管理/案件管理模块下的期限监控功能

// 查询所有在职用户用于下拉
$user_stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name ASC");
$user_stmt->execute();
$users = $user_stmt->fetchAll();

// 处理关注功能AJAX请求
if (isset($_POST['action']) && $_POST['action'] == 'add_to_follow') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => '用户未登录']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $case_ids = $_POST['case_ids'] ?? '';

    if (empty($case_ids)) {
        echo json_encode(['success' => false, 'msg' => '请选择要关注的案件']);
        exit;
    }

    try {
        // 查询用户当前关注的案件
        $stmt = $pdo->prepare("SELECT followed_case_ids FROM user_trademark_follow WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current_follow = $stmt->fetch();

        $new_case_ids = explode(',', $case_ids);
        $existing_case_ids = [];

        if ($current_follow && !empty($current_follow['followed_case_ids'])) {
            $existing_case_ids = explode(',', $current_follow['followed_case_ids']);
        }

        // 分析新增和重复的案件
        $duplicate_case_ids = array_intersect($existing_case_ids, $new_case_ids);
        $really_new_case_ids = array_diff($new_case_ids, $existing_case_ids);

        // 合并案件ID，去重
        $all_case_ids = array_unique(array_merge($existing_case_ids, $new_case_ids));
        $all_case_ids = array_filter($all_case_ids); // 移除空值

        $followed_case_ids_str = implode(',', $all_case_ids);
        $follow_count = count($all_case_ids);

        if ($current_follow) {
            // 更新现有记录
            $stmt = $pdo->prepare("UPDATE user_trademark_follow SET followed_case_ids = ?, follow_count = ?, last_follow_time = NOW() WHERE user_id = ?");
            $stmt->execute([$followed_case_ids_str, $follow_count, $user_id]);
        } else {
            // 插入新记录
            $stmt = $pdo->prepare("INSERT INTO user_trademark_follow (user_id, followed_case_ids, follow_count, last_follow_time) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, $followed_case_ids_str, $follow_count]);
        }

        // 构建详细的提示信息
        $msg_parts = [];
        if (count($really_new_case_ids) > 0) {
            $msg_parts[] = "成功添加 " . count($really_new_case_ids) . " 个新案件到我的关注";
        }
        if (count($duplicate_case_ids) > 0) {
            $msg_parts[] = count($duplicate_case_ids) . " 个案件已在关注列表中";
        }

        $final_msg = implode("，", $msg_parts);
        echo json_encode(['success' => true, 'msg' => $final_msg]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '添加关注失败: ' . $e->getMessage()]);
    }
    exit;
}

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
        'case_name' => 'LIKE'
    ];

    foreach ($search_fields as $field => $op) {
        if (!empty($_GET[$field])) {
            $where[] = "$field " . ($op == 'LIKE' ? "LIKE :$field" : "= :$field");
            $params[$field] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
        }
    }

    // 处理日期范围查询
    $date_fields = [
        'open_date' => '开卷日期',
        'entrust_date' => '委案日期',
        'application_date' => '申请日',
        'initial_publication_date' => '初审公告日',
        'registration_publication_date' => '注册公告日',
        'approval_date' => '获批日',
        'renewal_date' => '续展日',
        'expire_date' => '终止日'
    ];

    foreach ($date_fields as $field => $label) {
        if (!empty($_GET[$field . '_start'])) {
            $where[] = "$field >= :" . $field . "_start";
            $params[$field . '_start'] = $_GET[$field . '_start'];
        }
        if (!empty($_GET[$field . '_end'])) {
            $where[] = "$field <= :" . $field . "_end";
            $params[$field . '_end'] = $_GET[$field . '_end'];
        }
    }

    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count_sql = "SELECT COUNT(*) FROM trademark_case_info" . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);

    $sql = "SELECT t.*
            FROM trademark_case_info t" . $sql_where . " ORDER BY t.id DESC LIMIT :offset, :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $page_size, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    $trademarks = $stmt->fetchAll();

    // 日期颜色计算函数
    function getDateColor($date)
    {
        if (!$date) return null; // 无日期返回null，不设置背景色

        $now = time();
        $dateTime = strtotime($date);
        $diffDays = ceil(($dateTime - $now) / (24 * 60 * 60));

        if ($diffDays < 0) {
            return '#f44336'; // 已过期 - 红色
        } elseif ($diffDays <= 7) {
            return '#ff9800'; // 7天内 - 橙色
        } elseif ($diffDays <= 30) {
            return '#ffeb3b'; // 30天内 - 黄色
        } elseif ($diffDays <= 60) {
            return '#8bc34a'; // 60天内 - 浅绿色
        } elseif ($diffDays <= 180) {
            return '#4caf50'; // 180天内 - 绿色
        } else {
            return '#c0c0c0'; // 180天后 - 浅灰色（避免与边框色冲突）
        }
    }

    // 日期单元格样式生成函数
    function getDateCellStyle($date, $color)
    {
        // 如果没有日期，使用默认样式（白色背景）
        if (!$date || $color === null) {
            return 'text-align:center;color:#333;';
        }

        // 所有彩色背景都使用黑色字体
        $textColor = '#333';
        $fontWeight = 'normal';

        return 'background-color:' . $color . ';color:' . $textColor . ';text-align:center;font-weight:' . $fontWeight . ';border-radius:3px;padding:2px 4px;font-size:14px;border:1px solid #e0e0e0;';
    }

    $html = '';
    if (empty($trademarks)) {
        $html = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($trademarks as $index => $trademark) {
            $html .= '<tr data-id="' . $trademark['id'] . '">';
            $html .= '<td style="text-align:center;"><input type="checkbox" class="case-checkbox" value="' . $trademark['id'] . '"></td>';
            $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($trademark['case_code'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($trademark['case_name'] ?? '') . '</td>';

            // 带颜色的日期单元格
            $openDateColor = getDateColor($trademark['open_date']);
            $html .= '<td style="' . getDateCellStyle($trademark['open_date'], $openDateColor) . '">' . ($trademark['open_date'] ? date('Y-m-d', strtotime($trademark['open_date'])) : '-') . '</td>';

            $entrustDateColor = getDateColor($trademark['entrust_date']);
            $html .= '<td style="' . getDateCellStyle($trademark['entrust_date'], $entrustDateColor) . '">' . ($trademark['entrust_date'] ? date('Y-m-d', strtotime($trademark['entrust_date'])) : '-') . '</td>';

            $applicationDateColor = getDateColor($trademark['application_date']);
            $html .= '<td style="' . getDateCellStyle($trademark['application_date'], $applicationDateColor) . '">' . ($trademark['application_date'] ? date('Y-m-d', strtotime($trademark['application_date'])) : '-') . '</td>';

            $initialPublicationDateColor = getDateColor($trademark['initial_publication_date']);
            $html .= '<td style="' . getDateCellStyle($trademark['initial_publication_date'], $initialPublicationDateColor) . '">' . ($trademark['initial_publication_date'] ? date('Y-m-d', strtotime($trademark['initial_publication_date'])) : '-') . '</td>';

            $registrationPublicationDateColor = getDateColor($trademark['registration_publication_date']);
            $html .= '<td style="' . getDateCellStyle($trademark['registration_publication_date'], $registrationPublicationDateColor) . '">' . ($trademark['registration_publication_date'] ? date('Y-m-d', strtotime($trademark['registration_publication_date'])) : '-') . '</td>';

            $approvalDateColor = getDateColor($trademark['approval_date']);
            $html .= '<td style="' . getDateCellStyle($trademark['approval_date'], $approvalDateColor) . '">' . ($trademark['approval_date'] ? date('Y-m-d', strtotime($trademark['approval_date'])) : '-') . '</td>';

            $renewalDateColor = getDateColor($trademark['renewal_date']);
            $html .= '<td style="' . getDateCellStyle($trademark['renewal_date'], $renewalDateColor) . '">' . ($trademark['renewal_date'] ? date('Y-m-d', strtotime($trademark['renewal_date'])) : '-') . '</td>';

            $expireDateColor = getDateColor($trademark['expire_date']);
            $html .= '<td style="' . getDateCellStyle($trademark['expire_date'], $expireDateColor) . '">' . ($trademark['expire_date'] ? date('Y-m-d', strtotime($trademark['expire_date'])) : '-') . '</td>';

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
$users_options = [];
foreach ($users as $user) {
    $users_options[$user['id']] = $user['real_name'];
}

// 引入搜索下拉框资源
render_select_search_assets();
?>
<div class="module-panel">


    <div class="module-btns" style="display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
            <button type="button" class="btn-add" onclick="window.parent.openTab ? window.parent.openTab(2, 0, null) : alert('框架导航功能不可用')"><i class="icon-add"></i> 新增商标</button>
            <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
            <button type="button" class="btn-add-follow btn-mini" disabled><i class="icon-add"></i> 添加到我的关注</button>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-download-template"><i class="icon-save"></i> 下载模板</button>
            <button type="button" class="btn-batch-import"><i class="icon-add"></i> 批量导入</button>
            <button type="button" class="btn-download-current"><i class="icon-list"></i> 下载当前案件信息</button>
            <button type="button" class="btn-batch-update"><i class="icon-edit"></i> 批量修改</button>
        </div>
    </div>
    <?php render_info_notice("期限监控：显示所有商标案件的重要日期信息，帮助您及时跟进案件进度", 'info', 'icon-search'); ?>

    <!-- 颜色对照说明 -->
    <div style="background:#f9f9f9;border:1px solid #e0e0e0;border-radius:4px;padding:12px;margin-bottom:15px;">
        <div style="font-weight:bold;margin-bottom:8px;color:#333;">📅 日期颜色说明：</div>
        <div style="display:flex;flex-wrap:wrap;gap:15px;font-size:12px;">
            <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:16px;height:16px;background:#f44336;border-radius:2px;"></div>
                <span>已过期（早于当前时间）</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:16px;height:16px;background:#ff9800;border-radius:2px;"></div>
                <span>7天内到期</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:16px;height:16px;background:#ffeb3b;border-radius:2px;"></div>
                <span>30天内到期</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:16px;height:16px;background:#8bc34a;border-radius:2px;"></div>
                <span>60天内到期</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:16px;height:16px;background:#4caf50;border-radius:2px;"></div>
                <span>180天内到期</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:16px;height:16px;background:#c0c0c0;border-radius:2px;"></div>
                <span>180天后到期</span>
            </div>
        </div>
    </div>
    <form id="search-form" class="module-form" autocomplete="off">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="page_size" value="10">
        <table class="module-table" style="margin-bottom:15px;">
            <tr>
                <td class="module-label">我方文号：</td>
                <td><input type="text" name="case_code" class="module-input"></td>
                <td class="module-label">商标名称：</td>
                <td><input type="text" name="case_name" class="module-input"></td>
            </tr>
            <tr>
                <td class="module-label">开卷日期：</td>
                <td>
                    <input type="date" name="open_date_start" class="module-input" style="width:120px;"> 至
                    <input type="date" name="open_date_end" class="module-input" style="width:120px;">
                </td>
                <td class="module-label">委案日期：</td>
                <td>
                    <input type="date" name="entrust_date_start" class="module-input" style="width:120px;"> 至
                    <input type="date" name="entrust_date_end" class="module-input" style="width:120px;">
                </td>
            </tr>
            <tr>
                <td class="module-label">申请日期：</td>
                <td>
                    <input type="date" name="application_date_start" class="module-input" style="width:120px;"> 至
                    <input type="date" name="application_date_end" class="module-input" style="width:120px;">
                </td>
                <td class="module-label">初审公告日：</td>
                <td>
                    <input type="date" name="initial_publication_date_start" class="module-input" style="width:120px;"> 至
                    <input type="date" name="initial_publication_date_end" class="module-input" style="width:120px;">
                </td>
            </tr>
            <tr>
                <td class="module-label">注册公告日：</td>
                <td>
                    <input type="date" name="registration_publication_date_start" class="module-input" style="width:120px;"> 至
                    <input type="date" name="registration_publication_date_end" class="module-input" style="width:120px;">
                </td>
                <td class="module-label">获批日期：</td>
                <td>
                    <input type="date" name="approval_date_start" class="module-input" style="width:120px;"> 至
                    <input type="date" name="approval_date_end" class="module-input" style="width:120px;">
                </td>
            </tr>
            <tr>
                <td class="module-label">续展日期：</td>
                <td>
                    <input type="date" name="renewal_date_start" class="module-input" style="width:120px;"> 至
                    <input type="date" name="renewal_date_end" class="module-input" style="width:120px;">
                </td>
                <td class="module-label">终止日期：</td>
                <td>
                    <input type="date" name="expire_date_start" class="module-input" style="width:120px;"> 至
                    <input type="date" name="expire_date_end" class="module-input" style="width:120px;">
                </td>
            </tr>
        </table>
    </form>

    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:40px;text-align:center;"><input type="checkbox" id="select-all"></th>
                <th style="width:50px;text-align:center;">序号</th>
                <th style="width:100px;text-align:center;">我方文号</th>
                <th style="width:150px;">商标名称</th>
                <th style="width:90px;">开卷日期</th>
                <th style="width:90px;">委案日期</th>
                <th style="width:90px;">申请日</th>
                <th style="width:90px;">初审公告日</th>
                <th style="width:90px;">注册公告日</th>
                <th style="width:90px;">获批日</th>
                <th style="width:90px;">续展日</th>
                <th style="width:90px;">终止日</th>
            </tr>
        </thead>
        <tbody id="trademark-list">
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

<!-- 批量导入模态框 -->
<div id="batch-import-modal" class="module-modal" style="display:none;">
    <div class="module-modal-content" style="width:600px;">
        <div class="module-modal-header">
            <h3 class="module-modal-title">批量导入商标案件</h3>
            <button class="module-modal-close">&times;</button>
        </div>
        <div class="module-modal-body" style="padding:20px;">
            <div style="margin-bottom:20px;">
                <h4>导入说明：</h4>
                <ul style="margin:10px 0;padding-left:20px;color:#666;">
                    <li>请先下载Excel模板文件，使用模板文件填写数据，然后上传文件进行导入</li>
                    <li>必填字段：商标名称、承办部门ID、客户ID/客户名称、处理事项、案件类型</li>
                    <li>日期格式：YYYY-MM-DD（如：2025-01-01）</li>
                    <li>支持的文件格式：.xlsx, .xls, .csv</li>
                    <li>最大文件大小：10MB</li>
                </ul>
            </div>
            <form id="import-form" enctype="multipart/form-data">
                <table class="module-table">
                    <tr>
                        <td class="module-label module-req">*选择文件</td>
                        <td>
                            <input type="file" name="import_file" id="import-file" accept=".xlsx,.xls,.csv" class="module-input" required>
                        </td>
                    </tr>

                </table>
            </form>
            <div id="import-progress" style="display:none;margin-top:20px;">
                <div style="background:#f0f0f0;border-radius:10px;overflow:hidden;">
                    <div id="progress-bar" style="height:20px;background:#29b6b0;width:0%;transition:width 0.3s;"></div>
                </div>
                <div id="progress-text" style="text-align:center;margin-top:10px;">准备导入...</div>
            </div>
            <div id="import-result" style="display:none;margin-top:20px;"></div>
        </div>
        <div class="module-modal-footer">
            <button type="button" class="btn-theme" id="btn-start-import">开始导入</button>
            <button type="button" class="btn-cancel" id="btn-cancel-import">取消</button>
        </div>
    </div>
</div>

<!-- 批量修改模态框 -->
<div id="batch-update-modal" class="module-modal" style="display:none;">
    <div class="module-modal-content" style="width:600px;">
        <div class="module-modal-header">
            <h3 class="module-modal-title">批量修改商标案件</h3>
            <button class="module-modal-close">&times;</button>
        </div>
        <div class="module-modal-body" style="padding:20px;">
            <div style="margin-bottom:20px;">
                <h4>修改说明：</h4>
                <ul style="margin:10px 0;padding-left:20px;color:#666;">
                    <li>请先使用"下载当前案件信息"功能获取现有案件数据</li>
                    <li>在Excel文件中修改需要更新的字段，保持id列不变</li>
                    <li>灰色表头的id字段禁止修改，用于定位要更新的案件</li>
                    <li>必填字段：商标名称、承办部门ID、客户ID/客户名称、处理事项、案件类型</li>
                    <li>日期格式：YYYY-MM-DD（如：2025-01-01）</li>
                    <li>支持的文件格式：.xlsx, .xls, .csv</li>
                    <li>最大文件大小：10MB</li>
                </ul>
            </div>
            <form id="update-form" enctype="multipart/form-data">
                <table class="module-table">
                    <tr>
                        <td class="module-label module-req">*选择文件</td>
                        <td>
                            <input type="file" name="update_file" id="update-file" accept=".xlsx,.xls,.csv" class="module-input" required>
                        </td>
                    </tr>
                </table>
            </form>
            <div id="update-progress" style="display:none;margin-top:20px;">
                <div style="background:#f0f0f0;border-radius:10px;overflow:hidden;">
                    <div id="update-progress-bar" style="height:20px;background:#29b6b0;width:0%;transition:width 0.3s;"></div>
                </div>
                <div id="update-progress-text" style="text-align:center;margin-top:10px;">准备修改...</div>
            </div>
            <div id="update-result" style="display:none;margin-top:20px;"></div>
        </div>
        <div class="module-modal-footer">
            <button type="button" class="btn-theme" id="btn-start-update">开始修改</button>
            <button type="button" class="btn-cancel" id="btn-cancel-update">取消</button>
        </div>
    </div>
</div>

<script>
    (function() {
        var form = document.getElementById('search-form'),
            btnSearch = document.querySelector('.btn-search'),
            btnReset = document.querySelector('.btn-reset'),
            btnEdit = document.querySelector('.btn-edit'),
            btnAddFollow = document.querySelector('.btn-add-follow'),
            btnDownloadTemplate = document.querySelector('.btn-download-template'),
            btnDownloadCurrent = document.querySelector('.btn-download-current'),
            btnBatchImport = document.querySelector('.btn-batch-import'),
            btnBatchUpdate = document.querySelector('.btn-batch-update'),
            batchImportModal = document.getElementById('batch-import-modal'),
            batchUpdateModal = document.getElementById('batch-update-modal'),
            btnStartImport = document.getElementById('btn-start-import'),
            btnCancelImport = document.getElementById('btn-cancel-import'),
            btnStartUpdate = document.getElementById('btn-start-update'),
            btnCancelUpdate = document.getElementById('btn-cancel-update'),
            modalClose = batchImportModal.querySelector('.module-modal-close'),
            updateModalClose = batchUpdateModal.querySelector('.module-modal-close'),
            trademarkList = document.getElementById('trademark-list'),
            totalRecordsEl = document.getElementById('total-records'),
            currentPageEl = document.getElementById('current-page'),
            totalPagesEl = document.getElementById('total-pages'),
            btnFirstPage = document.getElementById('btn-first-page'),
            btnPrevPage = document.getElementById('btn-prev-page'),
            btnNextPage = document.getElementById('btn-next-page'),
            btnLastPage = document.getElementById('btn-last-page'),
            pageInput = document.getElementById('page-input'),
            btnPageJump = document.getElementById('btn-page-jump'),
            pageSizeSelect = document.getElementById('page-size-select'),
            selectAllCheckbox = document.getElementById('select-all');
        var currentPage = 1,
            pageSize = 10,
            totalPages = 1,
            selectedId = null;

        window.loadTrademarkData = function() {
            trademarkList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            selectedId = null;
            btnEdit.disabled = true;
            btnAddFollow.disabled = true;
            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);
            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') params.append(pair[0], pair[1]);
            }
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/deadline_monitoring.php';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                trademarkList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                                bindCheckboxEvents();
                            } else {
                                trademarkList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            trademarkList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        trademarkList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            trademarkList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.onclick = function(e) {
                    // 如果点击的是复选框，不触发行选择
                    if (e.target.type === 'checkbox') return;

                    trademarkList.querySelectorAll('tr[data-id]').forEach(r => r.classList.remove('module-selected'));
                    this.classList.add('module-selected');
                    selectedId = this.getAttribute('data-id');
                    btnEdit.disabled = false;
                }
            });
        }

        function bindCheckboxEvents() {
            // 全选/取消全选
            selectAllCheckbox.onchange = function() {
                var checkboxes = trademarkList.querySelectorAll('.case-checkbox');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateFollowButtonState();
            };

            // 单个复选框变化
            trademarkList.querySelectorAll('.case-checkbox').forEach(function(checkbox) {
                checkbox.onchange = function() {
                    updateSelectAllState();
                    updateFollowButtonState();
                };
            });
        }

        function updateSelectAllState() {
            var checkboxes = trademarkList.querySelectorAll('.case-checkbox');
            var checkedCount = trademarkList.querySelectorAll('.case-checkbox:checked').length;

            if (checkedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === checkboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        function updateFollowButtonState() {
            var checkedCount = trademarkList.querySelectorAll('.case-checkbox:checked').length;
            btnAddFollow.disabled = checkedCount === 0;
        }
        btnEdit.onclick = function() {
            if (!selectedId) {
                alert('请先选择要修改的商标');
                return;
            }
            // 记录来源页面信息
            sessionStorage.setItem('trademark_edit_source_module', '2');
            sessionStorage.setItem('trademark_edit_source_menu', '3');
            sessionStorage.setItem('trademark_edit_source_submenu', '4');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/trademark_management/case_management/set_edit_trademark.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    if (window.parent.openTab) {
                        // 商标管理模块索引为2，商标编辑菜单索引为4，subIndex必须为null
                        window.parent.openTab(2, 4, null);
                    } else {
                        alert('框架导航功能不可用');
                    }
                }
            };
            xhr.send('trademark_id=' + selectedId);
        };

        btnAddFollow.onclick = function() {
            var checkedBoxes = trademarkList.querySelectorAll('.case-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('请先选择要关注的案件');
                return;
            }

            var caseIds = Array.from(checkedBoxes).map(function(checkbox) {
                return checkbox.value;
            }).join(',');

            if (confirm('确定要将选中的 ' + checkedBoxes.length + ' 个案件添加到我的关注吗？')) {
                var xhr = new XMLHttpRequest();
                var baseUrl = window.location.href.split('?')[0];
                var followUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/deadline_monitoring.php';

                xhr.open('POST', followUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    alert(response.msg);
                                    // 清除选择状态
                                    selectAllCheckbox.checked = false;
                                    selectAllCheckbox.indeterminate = false;
                                    checkedBoxes.forEach(function(checkbox) {
                                        checkbox.checked = false;
                                    });
                                    updateFollowButtonState();
                                } else {
                                    alert('添加关注失败：' + response.msg);
                                }
                            } catch (e) {
                                alert('添加关注失败：服务器响应错误');
                            }
                        } else {
                            alert('添加关注失败：网络错误');
                        }
                    }
                };
                xhr.send('action=add_to_follow&case_ids=' + encodeURIComponent(caseIds));
            }
        };

        function updatePaginationButtons() {
            btnFirstPage.disabled = currentPage <= 1;
            btnPrevPage.disabled = currentPage <= 1;
            btnNextPage.disabled = currentPage >= totalPages;
            btnLastPage.disabled = currentPage >= totalPages;
            btnPrevPage.setAttribute('data-page', currentPage - 1);
            btnNextPage.setAttribute('data-page', currentPage + 1);
            btnLastPage.setAttribute('data-page', totalPages);
            pageInput.max = totalPages;
            pageInput.value = currentPage;
        }
        btnSearch.onclick = function() {
            currentPage = 1;
            loadTrademarkData();
        };
        btnReset.onclick = function() {
            form.reset();
            document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
            document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
            // 重置复选框状态
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            currentPage = 1;
            loadTrademarkData();
        };
        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            loadTrademarkData();
        };
        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    loadTrademarkData();
                }
            };
        });
        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            loadTrademarkData();
        };



        // 下载模板按钮事件
        btnDownloadTemplate.onclick = function() {
            var baseUrl = window.location.href.split('?')[0];
            var downloadUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/download_template.php';
            window.open(downloadUrl, '_blank');
        };

        // 下载当前案件信息按钮事件
        btnDownloadCurrent.onclick = function() {
            var formData = new FormData(form),
                params = new URLSearchParams();

            // 添加当前搜索条件
            for (var pair of formData.entries()) {
                if (pair[1] && pair[1].trim() !== '') {
                    params.append(pair[0], pair[1]);
                }
            }

            var baseUrl = window.location.href.split('?')[0];
            var downloadUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/download_current_cases.php';

            if (params.toString()) {
                downloadUrl += '?' + params.toString();
            }

            window.open(downloadUrl, '_blank');
        };

        // 批量导入按钮事件
        btnBatchImport.onclick = function() {
            batchImportModal.style.display = 'flex';
            // 重置表单
            document.getElementById('import-form').reset();
            document.getElementById('import-progress').style.display = 'none';
            document.getElementById('import-result').style.display = 'none';
            btnStartImport.disabled = false;
            btnStartImport.textContent = '开始导入';
        };

        // 关闭模态框
        window.closeBatchImportModal = function() {
            batchImportModal.style.display = 'none';
        };
        btnCancelImport.onclick = closeBatchImportModal;
        modalClose.onclick = closeBatchImportModal;

        // 开始导入按钮事件
        btnStartImport.onclick = function() {
            var fileInput = document.getElementById('import-file');
            var file = fileInput.files[0];

            if (!file) {
                alert('请选择要导入的Excel文件');
                return;
            }

            // 检查文件类型
            var allowedTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'text/csv',
                'text/plain',
                'application/csv'
            ];
            if (!allowedTypes.includes(file.type)) {
                alert('请选择Excel或CSV文件（.xlsx、.xls或.csv格式）');
                return;
            }

            // 检查文件大小（10MB）
            if (file.size > 10 * 1024 * 1024) {
                alert('文件大小不能超过10MB');
                return;
            }

            // 显示进度条
            document.getElementById('import-progress').style.display = 'block';
            document.getElementById('import-result').style.display = 'none';
            btnStartImport.disabled = true;
            btnStartImport.textContent = '导入中...';

            // 准备表单数据
            var formData = new FormData(document.getElementById('import-form'));
            formData.append('action', 'batch_import');

            // 发送请求
            var xhr = new XMLHttpRequest();
            var baseUrl = window.location.href.split('?')[0];
            var importUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/batch_import.php';
            xhr.open('POST', importUrl, true);

            // 监听上传进度
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percentComplete = (e.loaded / e.total) * 100;
                    document.getElementById('progress-bar').style.width = percentComplete + '%';
                    document.getElementById('progress-text').textContent = '上传中... ' + Math.round(percentComplete) + '%';
                }
            };

            xhr.onload = function() {
                btnStartImport.disabled = false;
                btnStartImport.textContent = '开始导入';

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        document.getElementById('progress-bar').style.width = '100%';
                        document.getElementById('progress-text').textContent = '导入完成';

                        // 显示结果
                        var resultDiv = document.getElementById('import-result');
                        resultDiv.style.display = 'block';

                        if (response.success) {
                            resultDiv.innerHTML = '<div style="color:#388e3c;"><strong>导入成功！</strong><br>' +
                                '成功导入：' + response.success_count + ' 条<br>' +
                                (response.error_count > 0 ? '导入失败：' + response.error_count + ' 条<br>' : '') +
                                (response.errors && response.errors.length > 0 ? '<br>错误详情：<br>' + response.errors.join('<br>') : '') +
                                '<br><br><button class="btn-theme" onclick="loadTrademarkData(); closeBatchImportModal();">刷新列表并关闭</button>' +
                                '</div>';
                        } else {
                            resultDiv.innerHTML = '<div style="color:#f44336;"><strong>导入失败：</strong><br>' +
                                (response.message || '未知错误') +
                                '<br><br><button class="btn-cancel" onclick="closeBatchImportModal();">关闭</button>' +
                                '</div>';
                        }
                    } catch (e) {
                        document.getElementById('import-result').innerHTML = '<div style="color:#f44336;">导入失败：服务器响应错误</div>';
                        document.getElementById('import-result').style.display = 'block';
                    }
                } else {
                    document.getElementById('import-result').innerHTML = '<div style="color:#f44336;">导入失败：网络错误</div>';
                    document.getElementById('import-result').style.display = 'block';
                }
            };

            xhr.onerror = function() {
                btnStartImport.disabled = false;
                btnStartImport.textContent = '开始导入';
                document.getElementById('import-result').innerHTML = '<div style="color:#f44336;">导入失败：网络连接错误</div>';
                document.getElementById('import-result').style.display = 'block';
            };

            xhr.send(formData);
        };

        // 批量修改按钮事件
        btnBatchUpdate.onclick = function() {
            batchUpdateModal.style.display = 'flex';
            // 重置表单
            document.getElementById('update-form').reset();
            document.getElementById('update-progress').style.display = 'none';
            document.getElementById('update-result').style.display = 'none';
            btnStartUpdate.disabled = false;
            btnStartUpdate.textContent = '开始修改';
        };

        // 关闭批量修改模态框
        window.closeBatchUpdateModal = function() {
            batchUpdateModal.style.display = 'none';
        };
        btnCancelUpdate.onclick = closeBatchUpdateModal;
        updateModalClose.onclick = closeBatchUpdateModal;

        // 开始修改按钮事件
        btnStartUpdate.onclick = function() {
            var fileInput = document.getElementById('update-file');
            var file = fileInput.files[0];

            if (!file) {
                alert('请选择要修改的Excel文件');
                return;
            }

            // 检查文件类型
            var allowedTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'text/csv',
                'text/plain',
                'application/csv'
            ];
            if (!allowedTypes.includes(file.type)) {
                alert('请选择Excel或CSV文件（.xlsx、.xls或.csv格式）');
                return;
            }

            // 检查文件大小（10MB）
            if (file.size > 10 * 1024 * 1024) {
                alert('文件大小不能超过10MB');
                return;
            }

            // 显示进度条
            document.getElementById('update-progress').style.display = 'block';
            document.getElementById('update-result').style.display = 'none';
            btnStartUpdate.disabled = true;
            btnStartUpdate.textContent = '修改中...';

            // 准备表单数据
            var formData = new FormData(document.getElementById('update-form'));
            formData.append('action', 'batch_update');

            // 发送请求
            var xhr = new XMLHttpRequest();
            var baseUrl = window.location.href.split('?')[0];
            var updateUrl = baseUrl.replace('index.php', '') + 'modules/trademark_management/case_management/batch_update.php';
            xhr.open('POST', updateUrl, true);

            // 监听上传进度
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percentComplete = (e.loaded / e.total) * 100;
                    document.getElementById('update-progress-bar').style.width = percentComplete + '%';
                    document.getElementById('update-progress-text').textContent = '上传中... ' + Math.round(percentComplete) + '%';
                }
            };
            xhr.onload = function() {
                btnStartUpdate.disabled = false;
                btnStartUpdate.textContent = '开始修改';

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        document.getElementById('update-progress-bar').style.width = '100%';
                        document.getElementById('update-progress-text').textContent = '修改完成';

                        // 显示结果
                        var resultDiv = document.getElementById('update-result');
                        resultDiv.style.display = 'block';

                        if (response.success) {
                            var resultHtml = '<div style="color:#388e3c;"><strong>修改完成！</strong><br>' +
                                '处理案件总数：' + (response.processed_count + response.error_count) + ' 个<br>' +
                                '实际更新：' + response.success_count + ' 个案件<br>';

                            if (response.no_change_count > 0) {
                                resultHtml += '无需更新：' + response.no_change_count + ' 个案件<br>';
                            }

                            if (response.error_count > 0) {
                                resultHtml += '<span style="color:#f44336;">处理失败：' + response.error_count + ' 个案件</span><br>';
                            }

                            if (response.performance_info) {
                                resultHtml += '<br><small style="color:#666;">' + response.performance_info + '</small><br>';
                            }

                            if (response.errors && response.errors.length > 0) {
                                resultHtml += '<br><details style="margin-top:10px;"><summary style="cursor:pointer;color:#f44336;">查看错误详情</summary>' +
                                    '<div style="margin-top:5px;color:#f44336;font-size:12px;">' + response.errors.join('<br>') + '</div></details>';
                            }

                            resultHtml += '<br><br><button class="btn-theme" onclick="loadTrademarkData(); closeBatchUpdateModal();">刷新列表并关闭</button></div>';
                            resultDiv.innerHTML = resultHtml;
                        } else {
                            resultDiv.innerHTML = '<div style="color:#f44336;"><strong>修改失败：</strong><br>' +
                                (response.message || '未知错误') +
                                '<br><br><button class="btn-cancel" onclick="closeBatchUpdateModal();">关闭</button>' +
                                '</div>';
                        }
                    } catch (e) {
                        document.getElementById('update-result').innerHTML = '<div style="color:#f44336;">修改失败：服务器响应错误</div>';
                        document.getElementById('update-result').style.display = 'block';
                    }
                } else {
                    document.getElementById('update-result').innerHTML = '<div style="color:#f44336;">修改失败：网络错误</div>';
                    document.getElementById('update-result').style.display = 'block';
                }
            };

            xhr.onerror = function() {
                btnStartUpdate.disabled = false;
                btnStartUpdate.textContent = '开始修改';
                document.getElementById('update-result').innerHTML = '<div style="color:#f44336;">修改失败：网络连接错误</div>';
                document.getElementById('update-result').style.display = 'block';
            };

            xhr.send(formData);
        };


        loadTrademarkData();
    })();
</script>

<style>
    /* 复选框样式优化 */
    .case-checkbox,
    #select-all {
        cursor: pointer;
        transform: scale(1.1);
    }

    /* 表格行悬停效果 */
    .module-table tbody tr:hover {
        background-color: #f5f5f5;
    }

    /* 选中行样式 */
    .module-table tbody tr.module-selected {
        background-color: #e3f2fd !important;
    }

    /* 全选复选框的半选状态样式 */
    #select-all:indeterminate {
        background-color: #29b6b0;
    }

    /* 日期单元格悬停效果 */
    .module-table td[style*="background-color"]:hover {
        transform: scale(1.02);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
        cursor: default;
    }

    /* 日期单元格通用样式 */
    .module-table td[style*="background-color"] {
        transition: all 0.2s ease;
        position: relative;
    }
</style>