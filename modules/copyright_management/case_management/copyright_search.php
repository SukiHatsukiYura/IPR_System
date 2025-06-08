<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 版权查询功能 - 版权管理/案件管理模块下的版权查询功能

// 案件类型
$case_types = ['软件著作权登记', '作品著作权登记', '版权转让', '版权许可', '版权变更', '版权续展', '版权撤销', '版权无效', '其他'];

// 业务类型
$business_types = ['软件著作权登记申请', '作品著作权登记申请', '著作权转让申请', '著作权许可备案', '著作权变更申请', '著作权续展申请', '著作权撤销申请', '著作权无效申请', '其他业务'];

// 案件状态
$case_statuses = ['受理', '审查中', '补正', '登记', '驳回', '撤回', '结案', '不予受理', '处理中', '已递交', '未递交', '客户放弃', '终止'];

// 处理事项
$process_items = ['开卷', '新申请', '补正', '缴费', '取得受理号', '取得登记号', '取得证书', '官文转达', '结案', '撤回', '放弃', '变更', '转让', '许可备案', '续展', '撤销', '无效', '其他'];

// 申请方式
$application_modes = ['电子申请', '纸本申请', '其他'];

// 申请类型
$application_types = ['软件', '文字作品', '美术作品', '摄影作品', '音乐作品', '影视作品', '其他作品'];

// 国家(地区)
$countries = ['中国', '美国', '日本', '韩国', '德国', '法国', '英国', '其他'];

// 案件流向
$case_flows = ['内-内', '内-外', '外-内', '外-外'];

// 案源国
$source_countries = ['中国', '美国', '日本', '其他'];

// 起始阶段
$start_stages = ['申请阶段', '审查阶段', '登记阶段', '证书阶段', '其他'];

// 超级分类
$super_categories = ['计算机软件', '文学作品', '艺术作品', '科学作品', '其他'];

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
        'business_type' => '=',
        'case_status' => '=',
        'application_type' => '='
    ];

    foreach ($search_fields as $field => $op) {
        if (!empty($_GET[$field])) {
            $where[] = "$field " . ($op == 'LIKE' ? "LIKE :$field" : "= :$field");
            $params[$field] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
        }
    }

    // 特殊处理"是否代办资助"字段 - 将"是"/"否"转换为1/0
    if (!empty($_GET['is_subsidy_agent'])) {
        $is_subsidy_agent_value = ($_GET['is_subsidy_agent'] === '是') ? 1 : 0;
        $where[] = "is_subsidy_agent = :is_subsidy_agent";
        $params['is_subsidy_agent'] = $is_subsidy_agent_value;
    }

    // 处理申请日期范围
    if (!empty($_GET['application_date_start'])) {
        $where[] = "application_date >= :application_date_start";
        $params['application_date_start'] = $_GET['application_date_start'];
    }
    if (!empty($_GET['application_date_end'])) {
        $where[] = "application_date <= :application_date_end";
        $params['application_date_end'] = $_GET['application_date_end'];
    }

    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count_sql = "SELECT COUNT(*) FROM copyright_case_info" . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);

    $sql = "SELECT c.*, 
            (SELECT dept_name FROM department WHERE id = c.business_dept_id) as business_dept_name,
            (SELECT customer_name_cn FROM customer WHERE id = c.client_id) as client_name
            FROM copyright_case_info c" . $sql_where . " ORDER BY c.id DESC LIMIT :offset, :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $page_size, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    $copyrights = $stmt->fetchAll();
    $html = '';
    if (empty($copyrights)) {
        $html = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($copyrights as $index => $copyright) {
            $html .= '<tr data-id="' . $copyright['id'] . '">';
            $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($copyright['case_code'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($copyright['case_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($copyright['business_dept_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($copyright['client_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($copyright['business_type'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($copyright['case_status'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($copyright['application_no'] ?? '') . '</td>';
            $html .= '<td>' . ($copyright['application_date'] ? date('Y-m-d', strtotime($copyright['application_date'])) : '') . '</td>';
            $html .= '<td>' . htmlspecialchars($copyright['application_type'] ?? '') . '</td>';
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
    <div class="module-btns">
        <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
        <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
        <button type="button" class="btn-add" onclick="window.parent.openTab ? window.parent.openTab(3, 0, null) : alert('框架导航功能不可用')"><i class="icon-add"></i> 新增版权</button>
        <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
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
                <td class="module-label">受理号：</td>
                <td><input type="text" name="application_no" class="module-input"></td>
            </tr>
            <tr>
                <td class="module-label">承办部门：</td>
                <td><?= render_dept_search('business_dept_id', $departments, '') ?></td>
                <td class="module-label">客户名称：</td>
                <td><?= render_customer_search('client_id', $customers, '') ?></td>
                <td class="module-label">申请类型：</td>
                <td><select name="application_type" class="module-input">
                        <option value="">--全部--</option><?php foreach ($application_types as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
            </tr>
            <tr>
                <td class="module-label">业务类型：</td>
                <td><select name="business_type" class="module-input">
                        <option value="">--全部--</option><?php foreach ($business_types as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">案件状态：</td>
                <td><select name="case_status" class="module-input">
                        <option value="">--全部--</option><?php foreach ($case_statuses as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">是否代办资助：</td>
                <td><select name="is_subsidy_agent" class="module-input">
                        <option value="">--全部--</option>
                        <option value="是">是</option>
                        <option value="否">否</option>
                    </select></td>
            </tr>
            <tr>
                <td class="module-label">申请日期：</td>
                <td colspan="5">
                    <input type="date" name="application_date_start" class="module-input" style="width:200px;"> 至
                    <input type="date" name="application_date_end" class="module-input" style="width:200px;">
                </td>
            </tr>
        </table>
    </form>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:50px;text-align:center;">序号</th>
                <th style="width:100px;text-align:center;">我方文号</th>
                <th style="width:180px;">案件名称</th>
                <th style="width:100px;">承办部门</th>
                <th style="width:120px;">客户名称</th>
                <th style="width:100px;">业务类型</th>
                <th style="width:80px;">案件状态</th>
                <th style="width:120px;">受理号</th>
                <th style="width:100px;">受理日</th>
                <th style="width:100px;">申请类型</th>
            </tr>
        </thead>
        <tbody id="copyright-list">
            <tr>
                <td colspan="10" style="text-align:center;padding:20px 0;">正在加载数据...</td>
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
            btnEdit = document.querySelector('.btn-edit'),
            copyrightList = document.getElementById('copyright-list'),
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

        function loadCopyrightData() {
            copyrightList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
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
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/copyright_management/case_management/copyright_search.php';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                copyrightList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                            } else {
                                copyrightList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            copyrightList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        copyrightList.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            copyrightList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.onclick = function() {
                    copyrightList.querySelectorAll('tr[data-id]').forEach(r => r.classList.remove('module-selected'));
                    this.classList.add('module-selected');
                    selectedId = this.getAttribute('data-id');
                    btnEdit.disabled = false;
                }
            });
        }
        btnEdit.onclick = function() {
            if (!selectedId) {
                alert('请先选择要修改的版权');
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/copyright_management/case_management/set_edit_copyright.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    if (window.parent.openTab) {
                        // 版权管理模块索引为3，版权编辑菜单索引为2，subIndex必须为null
                        window.parent.openTab(3, 2, null);
                    } else {
                        alert('框架导航功能不可用');
                    }
                }
            };
            xhr.send('copyright_id=' + selectedId);
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
            loadCopyrightData();
        };
        btnReset.onclick = function() {
            form.reset();
            document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
            document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
            currentPage = 1;
            loadCopyrightData();
        };
        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            loadCopyrightData();
        };
        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    loadCopyrightData();
                }
            };
        });
        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            loadCopyrightData();
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
        loadCopyrightData();
    })();
</script>