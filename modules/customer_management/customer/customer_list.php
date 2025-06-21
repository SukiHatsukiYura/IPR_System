<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 统一选项声明
$options = [
    'customer_levels' => ['一般客户', '重要客户', '潜在客户', '个人', '企业', '中介'],
    'deal_statuses' => ['否', '是'],
    'case_types' => ['patent' => '专利', 'trademark' => '商标', 'copyright' => '版权'],
    'customer_sources' => [
        '电话来访',
        '客户介绍',
        '客户',
        '立项开发',
        '媒体宣传',
        '代理商',
        '合作伙伴',
        '公开招标',
        '直邮',
        '网站',
        '回单',
        '其他',
        '2022年度商标局品牌导站（实员）建设项目'
    ],
    'industry_options' => ['地产', '制造业', '互联网', '金融', '教育', '医疗', '能源', '交通', '物流', '建筑', '传媒', '农业', '旅游', '政府', '军工', '其他']
];

// 查询所有在职用户用于下拉
$user_stmt = $pdo->prepare("SELECT id, real_name FROM user WHERE is_active=1 ORDER BY real_name ASC");
$user_stmt->execute();
$users = $user_stmt->fetchAll();

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
        'customer_code' => 'LIKE',
        'customer_name_cn' => 'LIKE',
        'customer_name_en' => 'LIKE',
        'company_leader' => 'LIKE',
        'customer_level' => '=',
        'business_staff_id' => '=',
        'deal_status' => '=',
        'customer_source' => '='
    ];
    foreach ($search_fields as $field => $op) {
        if (!empty($_GET[$field])) {
            $where[] = "$field " . ($op == 'LIKE' ? "LIKE :$field" : "= :$field");
            $params[$field] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
        }
    }
    if (!empty($_GET['case_type'])) {
        $map = ['patent' => 'case_type_patent', 'trademark' => 'case_type_trademark', 'copyright' => 'case_type_copyright'];
        if (isset($map[$_GET['case_type']])) $where[] = "{$map[$_GET['case_type']]} = 1";
    }
    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count_sql = "SELECT COUNT(*) FROM customer" . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);
    $sql = "SELECT c.*, 
            (SELECT real_name FROM user WHERE id = c.business_staff_id) as business_staff_name,
            (SELECT real_name FROM user WHERE id = c.process_staff_id) as process_staff_name,
            (SELECT real_name FROM user WHERE id = c.project_leader_id) as project_leader_name,
            (SELECT real_name FROM user WHERE id = c.new_case_manager_id) as new_case_manager_name
            FROM customer c" . $sql_where . " ORDER BY c.id DESC LIMIT :offset, :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $page_size, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    $customers = $stmt->fetchAll();
    $html = '';
    if (empty($customers)) {
        $html = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($customers as $index => $customer) {
            $types = [];
            if ($customer['case_type_patent']) $types[] = '专利';
            if ($customer['case_type_trademark']) $types[] = '商标';
            if ($customer['case_type_copyright']) $types[] = '版权';
            $html .= '<tr data-id="' . $customer['id'] . '">';
            $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($customer['customer_code'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($customer['customer_name_cn'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($customer['customer_level'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($customer['business_staff_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($customer['process_staff_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($customer['deal_status'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars(implode(', ', $types)) . '</td>';
            $html .= '<td>' . ($customer['created_at'] ? date('Y-m-d', strtotime($customer['created_at'])) : '') . '</td>';
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
?>
<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
        <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
        <button type="button" class="btn-add" onclick="window.parent.openTab ? window.parent.openTab(0, 1, 0) : alert('框架导航功能不可用')"><i class="icon-add"></i> 新增客户</button>
        <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
        <button type="button" class="btn-download-template"><i class="icon-list"></i> 下载模板</button>
        <button type="button" class="btn-batch-import"><i class="icon-add"></i> 批量导入</button>
    </div>
    <form id="search-form" class="module-form" autocomplete="off">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="page_size" value="10">
        <table class="module-table" style="margin-bottom:15px;">
            <tr>
                <td class="module-label">客户编号：</td>
                <td><input type="text" name="customer_code" class="module-input"></td>
                <td class="module-label">客户名称(中)：</td>
                <td><input type="text" name="customer_name_cn" class="module-input"></td>
                <td class="module-label">客户名称(英)：</td>
                <td><input type="text" name="customer_name_en" class="module-input"></td>
            </tr>
            <tr>
                <td class="module-label">公司负责人：</td>
                <td><input type="text" name="company_leader" class="module-input"></td>
                <td class="module-label">客户等级：</td>
                <td><select name="customer_level" class="module-input">
                        <option value="">--全部--</option><?php foreach ($options['customer_levels'] as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">业务人员：</td>
                <td><?= render_user_search('business_staff_id', $users, '') ?></td>
            </tr>
            <tr>
                <td class="module-label">成交状态：</td>
                <td><select name="deal_status" class="module-input">
                        <option value="">--全部--</option><?php foreach ($options['deal_statuses'] as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">案件类型：</td>
                <td><select name="case_type" class="module-input">
                        <option value="">--全部--</option><?php foreach ($options['case_types'] as $k => $v): ?><option value="<?= h($k) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">客户来源：</td>
                <td><select name="customer_source" class="module-input">
                        <option value="">--全部--</option><?php foreach ($options['customer_sources'] as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
            </tr>
        </table>
    </form>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:60px;text-align:center;">序号</th>
                <th style="width:120px;text-align:center;">客户编号</th>
                <th style="width:200px;">客户名称</th>
                <th style="width:100px;">客户等级</th>
                <th style="width:100px;">业务人员</th>
                <th style="width:100px;">流程人员</th>
                <th style="width:100px;">成交状态</th>
                <th style="width:150px;">案件类型</th>
                <th style="width:100px;">创建日期</th>
            </tr>
        </thead>
        <tbody id="customer-list">
            <tr>
                <td colspan="9" style="text-align:center;padding:20px 0;">正在加载数据...</td>
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
            <h3 class="module-modal-title">批量导入客户</h3>
            <button class="module-modal-close">&times;</button>
        </div>
        <div class="module-modal-body" style="padding:20px;">
            <div style="margin-bottom:20px;">
                <h4>导入说明：</h4>
                <ul style="margin:10px 0;padding-left:20px;color:#666;">
                    <li>请先下载Excel模板文件，使用模板文件填写数据，然后上传文件进行导入</li>
                    <li>必填字段：客户名称(中)</li>
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

<script>
    (function() {
        var form = document.getElementById('search-form'),
            btnSearch = document.querySelector('.btn-search'),
            btnReset = document.querySelector('.btn-reset'),
            btnEdit = document.querySelector('.btn-edit'),
            btnDownloadTemplate = document.querySelector('.btn-download-template'),
            btnBatchImport = document.querySelector('.btn-batch-import'),
            batchImportModal = document.getElementById('batch-import-modal'),
            btnStartImport = document.getElementById('btn-start-import'),
            btnCancelImport = document.getElementById('btn-cancel-import'),
            modalClose = batchImportModal.querySelector('.module-modal-close'),
            customerList = document.getElementById('customer-list'),
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
            totalPages = 1,
            selectedId = null;

        window.loadCustomerData = function() {
            customerList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            selectedId = null;
            btnEdit.disabled = true;
            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);
            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') params.append(pair[0], pair[1]);
            }
            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/customer_management/customer/customer_list.php';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                customerList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                            } else {
                                customerList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            customerList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        customerList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            customerList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.onclick = function() {
                    customerList.querySelectorAll('tr[data-id]').forEach(r => r.classList.remove('module-selected'));
                    this.classList.add('module-selected');
                    selectedId = this.getAttribute('data-id');
                    btnEdit.disabled = false;
                }
            });
        }
        btnEdit.onclick = function() {
            if (!selectedId) {
                alert('请先选择要修改的客户');
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/customer/set_edit_customer.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    if (window.parent.openTab) {
                        // 客户管理模块索引为0，客户菜单索引为1，客户列表subIndex为1
                        window.parent.openTab(0, 1, 0);
                    } else {
                        alert('框架导航功能不可用');
                    }
                }
            };
            xhr.send('customer_id=' + selectedId);
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
            window.loadCustomerData();
        };
        btnReset.onclick = function() {
            form.reset();
            document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
            document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
            currentPage = 1;
            window.loadCustomerData();
        };
        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            window.loadCustomerData();
        };
        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    window.loadCustomerData();
                }
            };
        });
        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            window.loadCustomerData();
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

        // 下载模板按钮事件
        btnDownloadTemplate.onclick = function() {
            var baseUrl = window.location.href.split('?')[0];
            var downloadUrl = baseUrl.replace('index.php', '') + 'modules/customer_management/customer/download_template.php';
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
            xhr.open('POST', 'modules/customer_management/customer/batch_import.php', true);

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
                                '<br><br><button class="btn-theme" onclick="loadCustomerData(); closeBatchImportModal();">刷新列表并关闭</button>' +
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
        window.loadCustomerData();
    })();
</script>