<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 版权文件管理功能 - 版权管理/案件管理模块下的文件管理功能

// 文件类型选项（与版权编辑页面保持一致）
$file_types = ['申请书', '作品样本', '权利证明', '身份证明', '委托书', '说明文档', '其他'];

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

// 批量下载功能已移至独立的download_file.php处理

// 处理删除文件请求
if (isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    header('Content-Type: application/json');

    try {
        if (!isset($_POST['file_id']) || !intval($_POST['file_id'])) {
            echo json_encode(['success' => false, 'message' => '未指定要删除的文件']);
            exit;
        }

        $file_id = intval($_POST['file_id']);

        // 查询文件信息
        $stmt = $pdo->prepare("SELECT * FROM copyright_case_file WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch();

        if (!$file) {
            echo json_encode(['success' => false, 'message' => '文件不存在']);
            exit;
        }

        // 删除物理文件
        $file_path = __DIR__ . '/../../../' . $file['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // 删除数据库记录
        $delete_stmt = $pdo->prepare("DELETE FROM copyright_case_file WHERE id = ?");
        $result = $delete_stmt->execute([$file_id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => '文件删除成功']);
        } else {
            echo json_encode(['success' => false, 'message' => '删除失败']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '删除文件时发生错误: ' . $e->getMessage()]);
    }
    exit;
}

// 处理批量删除文件请求
if (isset($_POST['action']) && $_POST['action'] === 'batch_delete') {
    header('Content-Type: application/json');

    try {
        if (!isset($_POST['file_ids']) || !is_array($_POST['file_ids']) || empty($_POST['file_ids'])) {
            echo json_encode(['success' => false, 'message' => '请选择要删除的文件']);
            exit;
        }

        $file_ids = array_map('intval', $_POST['file_ids']);
        $file_ids = array_filter($file_ids, function ($id) {
            return $id > 0;
        });

        if (empty($file_ids)) {
            echo json_encode(['success' => false, 'message' => '无效的文件ID']);
            exit;
        }

        if (count($file_ids) > 50) {
            echo json_encode(['success' => false, 'message' => '一次最多只能删除50个文件']);
            exit;
        }

        // 查询文件信息
        $placeholders = str_repeat('?,', count($file_ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT * FROM copyright_case_file WHERE id IN ($placeholders)");
        $stmt->execute($file_ids);
        $files = $stmt->fetchAll();

        $deleted_count = 0;
        $failed_files = [];

        foreach ($files as $file) {
            try {
                // 删除物理文件
                $file_path = __DIR__ . '/../../../' . $file['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }

                // 删除数据库记录
                $delete_stmt = $pdo->prepare("DELETE FROM copyright_case_file WHERE id = ?");
                $result = $delete_stmt->execute([$file['id']]);

                if ($result) {
                    $deleted_count++;
                } else {
                    $failed_files[] = $file['file_name'];
                }
            } catch (Exception $e) {
                $failed_files[] = $file['file_name'] . ' (错误: ' . $e->getMessage() . ')';
            }
        }

        if ($deleted_count > 0) {
            $message = "成功删除 {$deleted_count} 个文件";
            if (!empty($failed_files)) {
                $message .= "，失败的文件: " . implode(', ', $failed_files);
            }
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => '删除失败: ' . implode(', ', $failed_files)]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '批量删除文件时发生错误: ' . $e->getMessage()]);
    }
    exit;
}

// 处理AJAX请求
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');

    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $page_size = min(max(1, intval($_GET['page_size'] ?? 10)), 100);
        $offset = ($page - 1) * $page_size;
        $where = [];
        $params = [];

        // 构建查询条件
        if (!empty($_GET['case_code'])) {
            $where[] = "c.case_code LIKE :case_code";
            $params['case_code'] = '%' . $_GET['case_code'] . '%';
        }

        if (!empty($_GET['case_name'])) {
            $where[] = "c.case_name LIKE :case_name";
            $params['case_name'] = '%' . $_GET['case_name'] . '%';
        }

        if (!empty($_GET['file_name'])) {
            $where[] = "f.file_name LIKE :file_name";
            $params['file_name'] = '%' . $_GET['file_name'] . '%';
        }

        if (!empty($_GET['file_type'])) {
            $where[] = "f.file_type = :file_type";
            $params['file_type'] = $_GET['file_type'];
        }

        if (!empty($_GET['business_dept_id'])) {
            $where[] = "c.business_dept_id = :business_dept_id";
            $params['business_dept_id'] = $_GET['business_dept_id'];
        }

        if (!empty($_GET['client_id'])) {
            $where[] = "c.client_id = :client_id";
            $params['client_id'] = $_GET['client_id'];
        }

        if (!empty($_GET['upload_user_id'])) {
            $where[] = "f.upload_user_id = :upload_user_id";
            $params['upload_user_id'] = $_GET['upload_user_id'];
        }

        // 处理上传日期范围
        if (!empty($_GET['upload_date_start'])) {
            $where[] = "DATE(f.created_at) >= :upload_date_start";
            $params['upload_date_start'] = $_GET['upload_date_start'];
        }
        if (!empty($_GET['upload_date_end'])) {
            $where[] = "DATE(f.created_at) <= :upload_date_end";
            $params['upload_date_end'] = $_GET['upload_date_end'];
        }

        $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // 统计总数
        $count_sql = "SELECT COUNT(*) FROM copyright_case_file f 
                      LEFT JOIN copyright_case_info c ON f.copyright_case_info_id = c.id" . $sql_where;
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetchColumn();
        $total_pages = ceil($total_records / $page_size);

        // 查询文件列表
        $sql = "SELECT f.*, c.case_code, c.case_name, c.business_dept_id, c.client_id,
                (SELECT dept_name FROM department WHERE id = c.business_dept_id) as business_dept_name,
                (SELECT customer_name_cn FROM customer WHERE id = c.client_id) as client_name,
                (SELECT real_name FROM user WHERE id = f.upload_user_id) as upload_user_name
                FROM copyright_case_file f 
                LEFT JOIN copyright_case_info c ON f.copyright_case_info_id = c.id" . $sql_where . " 
                ORDER BY f.created_at DESC LIMIT :offset, :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $page_size, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        $files = $stmt->fetchAll();

        $html = '';
        if (empty($files)) {
            $html = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">暂无文件数据</td></tr>';
        } else {
            foreach ($files as $index => $file) {
                $file_size_mb = $file['file_size'] ? round($file['file_size'] / 1024 / 1024, 2) : 0;

                $html .= '<tr data-id="' . $file['id'] . '">';
                $html .= '<td style="text-align:center;"><input type="checkbox" class="file-checkbox" value="' . $file['id'] . '"></td>';
                $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
                $html .= '<td style="text-align:center;">' . htmlspecialchars($file['case_code'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($file['case_name'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($file['business_dept_name'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($file['client_name'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($file['file_type'] ?? '') . '</td>';
                $html .= '<td><a href="javascript:void(0)" onclick="downloadFile(' . $file['id'] . ')" title="点击下载">' . htmlspecialchars($file['file_name'] ?? '') . '</a></td>';
                $html .= '<td style="text-align:center;">' . $file_size_mb . ' MB</td>';
                $html .= '<td>' . htmlspecialchars($file['upload_user_name'] ?? '') . '</td>';
                $html .= '<td style="text-align:center;">' . ($file['created_at'] ? date('Y-m-d H:i', strtotime($file['created_at'])) : '') . '</td>';
                $html .= '<td style="text-align:center;"><button type="button" class="btn-mini" style="color:red" onclick="deleteFile(' . $file['id'] . ')" title="删除文件">删除</button></td>';
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
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'PHP错误: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
    exit;
}

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
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

function render_dept_search($name, $departments, $get_val)
{
    $val = isset($_GET[$name]) ? intval($_GET[$name]) : 0;
    $display = '';
    foreach ($departments as $d) {
        if ($d['id'] == $val) {
            $display = htmlspecialchars($d['dept_name'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
    return '<div class="module-select-search-box">'
        . '<input type="text" class="module-input module-select-search-input" name="' . $name . '_display" value="' . $display . '" readonly placeholder="点击选择" data-deptname="' . $display . '">'
        . '<input type="hidden" name="' . $name . '" value="' . ($val ? $val : '') . '">'
        . '<div class="module-select-search-list" style="display:none;">'
        .   '<input type="text" class="module-select-search-list-input" placeholder="搜索部门">'
        .   '<div class="module-select-search-list-items"></div>'
        . '</div>'
        . '</div>';
}

function render_customer_search($name, $customers, $get_val)
{
    $val = isset($_GET[$name]) ? intval($_GET[$name]) : 0;
    $display = '';
    foreach ($customers as $c) {
        if ($c['id'] == $val) {
            $display = htmlspecialchars($c['customer_name_cn'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
    return '<div class="module-select-search-box">'
        . '<input type="text" class="module-input module-select-search-input" name="' . $name . '_display" value="' . $display . '" readonly placeholder="点击选择" data-customername="' . $display . '">'
        . '<input type="hidden" name="' . $name . '" value="' . ($val ? $val : '') . '">'
        . '<div class="module-select-search-list" style="display:none;">'
        .   '<input type="text" class="module-select-search-list-input" placeholder="搜索客户">'
        .   '<div class="module-select-search-list-items"></div>'
        . '</div>'
        . '</div>';
}
?>
<div class="module-panel">
    <div class="module-btns" style="display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
            <button type="button" class="btn-batch-download"><i class="icon-save"></i> 批量下载</button>
            <button type="button" class="btn-batch-delete"><i class="icon-cancel"></i> 批量删除</button>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-select-all"><i class="icon-list"></i> 全选</button>
            <button type="button" class="btn-unselect-all"><i class="icon-cancel"></i> 取消全选</button>
            <span id="selected-count" style="margin-left: 10px; color: #666;">已选择 0 个文件</span>
        </div>
    </div>
    <form id="search-form" class="module-form" autocomplete="off">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="page_size" value="10">
        <table class="module-table" style="margin-bottom:15px;">
            <tr>
                <td class="module-label">我方文号：</td>
                <td><input type="text" name="case_code" class="module-input"></td>
                <td class="module-label">案件名称：</td>
                <td><input type="text" name="case_name" class="module-input"></td>
                <td class="module-label">文件名称：</td>
                <td><input type="text" name="file_name" class="module-input"></td>
            </tr>
            <tr>
                <td class="module-label">文件类型：</td>
                <td><select name="file_type" class="module-input">
                        <option value="">--全部--</option><?php foreach ($file_types as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">承办部门：</td>
                <td><?= render_dept_search('business_dept_id', $departments, '') ?></td>
                <td class="module-label">客户名称：</td>
                <td><?= render_customer_search('client_id', $customers, '') ?></td>
            </tr>
            <tr>
                <td class="module-label">上传人：</td>
                <td><?= render_user_search('upload_user_id', $users, '') ?></td>
                <td class="module-label">上传日期：</td>
                <td colspan="3">
                    <input type="date" name="upload_date_start" class="module-input" style="width:200px;"> 至
                    <input type="date" name="upload_date_end" class="module-input" style="width:200px;">
                </td>
            </tr>
        </table>
    </form>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:40px;text-align:center;">选择</th>
                <th style="width:50px;text-align:center;">序号</th>
                <th style="width:100px;text-align:center;">我方文号</th>
                <th style="width:180px;">案件名称</th>
                <th style="width:100px;">承办部门</th>
                <th style="width:120px;">客户名称</th>
                <th style="width:100px;">文件类型</th>
                <th style="width:200px;">文件名称</th>
                <th style="width:80px;text-align:center;">文件大小</th>
                <th style="width:100px;">上传人</th>
                <th style="width:120px;text-align:center;">上传时间</th>
                <th style="width:80px;text-align:center;">操作</th>
            </tr>
        </thead>
        <tbody id="file-list">
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

<script>
    (function() {
        var form = document.getElementById('search-form'),
            btnSearch = document.querySelector('.btn-search'),
            btnReset = document.querySelector('.btn-reset'),
            btnBatchDownload = document.querySelector('.btn-batch-download'),
            btnBatchDelete = document.querySelector('.btn-batch-delete'),
            btnSelectAll = document.querySelector('.btn-select-all'),
            btnUnselectAll = document.querySelector('.btn-unselect-all'),
            selectedCount = document.getElementById('selected-count'),
            fileList = document.getElementById('file-list'),
            totalRecordsEl = document.getElementById('total-records'),
            currentPageEl = document.getElementById('current-page'),
            totalPagesEl = document.getElementById('total-pages'),
            btnFirstPage = document.getElementById('btn-first-page'),
            btnPrevPage = document.getElementById('btn-prev-page'),
            btnNextPage = document.getElementById('btn-next-page'),
            btnLastPage = document.getElementById('btn-last-page'),
            pageInput = document.getElementById('page-input'),
            btnPageJump = document.getElementById('btn-page-jump'),
            pageSizeSelect = document.getElementById('page-size-select');
        var currentPage = 1,
            pageSize = 10,
            totalPages = 1;

        window.loadFileData = function() {
            fileList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);
            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') params.append(pair[0], pair[1]);
            }
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/copyright_management/case_management/file_management.php';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log('AJAX Response Status:', xhr.status);
                    console.log('AJAX Response Text:', xhr.responseText);

                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            console.log('Parsed Response:', response);

                            if (response.success) {
                                fileList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindCheckboxEvents();
                            } else {
                                console.error('Server returned error:', response.message || 'Unknown error');
                                fileList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">加载数据失败: ' + (response.message || '未知错误') + '</td></tr>';
                            }
                        } catch (e) {
                            console.error('JSON Parse Error:', e);
                            console.error('Response Text:', xhr.responseText);
                            fileList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">数据解析错误，请查看控制台</td></tr>';
                        }
                    } else {
                        console.error('HTTP Error:', xhr.status);
                        fileList.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindCheckboxEvents() {
            var checkboxes = document.querySelectorAll('.file-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.onchange = updateSelectedCount;
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            var checked = document.querySelectorAll('.file-checkbox:checked');
            selectedCount.textContent = '已选择 ' + checked.length + ' 个文件';
        }

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
            window.loadFileData();
        };

        btnReset.onclick = function() {
            form.reset();
            document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
            document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
            currentPage = 1;
            window.loadFileData();
        };

        btnSelectAll.onclick = function() {
            var checkboxes = document.querySelectorAll('.file-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = true;
            });
            updateSelectedCount();
        };

        btnUnselectAll.onclick = function() {
            var checkboxes = document.querySelectorAll('.file-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
            updateSelectedCount();
        };

        btnBatchDownload.onclick = function() {
            var checked = document.querySelectorAll('.file-checkbox:checked');
            if (checked.length === 0) {
                alert('请选择要下载的文件');
                return;
            }

            if (checked.length > 50) {
                alert('一次最多只能下载50个文件');
                return;
            }

            var fileIds = [];
            checked.forEach(function(checkbox) {
                fileIds.push(checkbox.value);
            });

            // 直接通过URL下载批量文件
            var baseUrl = window.location.href.split('?')[0];
            var downloadUrl = baseUrl.replace('index.php', '') + 'modules/copyright_management/case_management/download_file.php?action=batch&ids=' + fileIds.join(',');
            window.open(downloadUrl, '_blank');
        };

        btnBatchDelete.onclick = function() {
            var checked = document.querySelectorAll('.file-checkbox:checked');
            if (checked.length === 0) {
                alert('请选择要删除的文件');
                return;
            }

            if (checked.length > 50) {
                alert('一次最多只能删除50个文件');
                return;
            }

            if (!confirm('确定要删除选中的 ' + checked.length + ' 个文件吗？删除后无法恢复！')) {
                return;
            }

            var fileIds = [];
            checked.forEach(function(checkbox) {
                fileIds.push(checkbox.value);
            });

            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/copyright_management/case_management/file_management.php';

            var formData = new FormData();
            formData.append('action', 'batch_delete');
            fileIds.forEach(function(id) {
                formData.append('file_ids[]', id);
            });

            var xhr = new XMLHttpRequest();
            xhr.open('POST', requestUrl, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            alert(response.message);
                            if (response.success) {
                                window.loadFileData(); // 重新加载数据
                            }
                        } catch (e) {
                            alert('批量删除请求处理失败');
                        }
                    } else {
                        alert('批量删除请求失败，请稍后重试');
                    }
                }
            };
            xhr.send(formData);
        };

        window.downloadFile = function(fileId) {
            var baseUrl = window.location.href.split('?')[0];
            var downloadUrl = baseUrl.replace('index.php', '') + 'modules/copyright_management/case_management/download_file.php?id=' + fileId;
            window.open(downloadUrl, '_blank');
        };

        // 删除单个文件
        window.deleteFile = function(fileId) {
            if (!confirm('确定要删除这个文件吗？删除后无法恢复！')) {
                return;
            }

            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/copyright_management/case_management/file_management.php';

            var formData = new FormData();
            formData.append('action', 'delete_file');
            formData.append('file_id', fileId);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', requestUrl, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                alert(response.message);
                                window.loadFileData(); // 重新加载数据
                            } else {
                                alert('删除失败: ' + response.message);
                            }
                        } catch (e) {
                            alert('删除请求处理失败');
                        }
                    } else {
                        alert('删除请求失败，请稍后重试');
                    }
                }
            };
            xhr.send(formData);
        };

        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            window.loadFileData();
        };

        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    window.loadFileData();
                }
            };
        });

        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            window.loadFileData();
        };

        // 用户搜索下拉
        var userData = <?php echo json_encode($users, JSON_UNESCAPED_UNICODE); ?>;
        var deptData = <?php echo json_encode($departments, JSON_UNESCAPED_UNICODE); ?>;
        var customerData = <?php echo json_encode($customers, JSON_UNESCAPED_UNICODE); ?>;

        function bindUserSearch(box) {
            var input = box.querySelector('.module-select-search-input');
            var hidden = box.querySelector('input[type=hidden]');
            var list = box.querySelector('.module-select-search-list');
            var searchInput = list.querySelector('.module-select-search-list-input');
            var itemsDiv = list.querySelector('.module-select-search-list-items');
            var data = [];

            if (input.hasAttribute('data-realname')) {
                data = userData;
            } else if (input.hasAttribute('data-deptname')) {
                data = deptData;
            } else if (input.hasAttribute('data-customername')) {
                data = customerData;
            }

            function renderList(filter) {
                var html = '<div class="module-select-search-item" data-id="">--全部--</div>',
                    found = false;
                data.forEach(function(item) {
                    var displayName = item.real_name || item.dept_name || item.customer_name_cn;
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

        // 初始加载
        window.loadFileData();
    })();
</script>