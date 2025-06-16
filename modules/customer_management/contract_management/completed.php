<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 已完成功能 - 客户管理/合同管理模块下的已完成管理功能

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

    // 已完成条件：合同状态为"成功结束"
    $where[] = "contract_status = '成功结束'";

    // 合并查询条件
    $search_fields = [
        'contract_name' => 'LIKE',
        'customer_id' => '='
    ];

    foreach ($search_fields as $field => $op) {
        if (!empty($_GET[$field])) {
            $where[] = "$field " . ($op == 'LIKE' ? "LIKE :$field" : "= :$field");
            $params[$field] = $op == 'LIKE' ? '%' . $_GET[$field] . '%' : $_GET[$field];
        }
    }

    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count_sql = "SELECT COUNT(*) FROM contract" . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);

    $sql = "SELECT c.*, 
            (SELECT customer_name_cn FROM customer WHERE id = c.customer_id) as customer_name,
            (SELECT real_name FROM user WHERE id = c.business_user_id) as business_user_name
            FROM contract c" . $sql_where . " ORDER BY c.id DESC LIMIT :offset, :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $page_size, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    $contracts = $stmt->fetchAll();

    $html = '';
    if (empty($contracts)) {
        $html = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">暂无已完成数据</td></tr>';
    } else {
        foreach ($contracts as $index => $contract) {
            $html .= '<tr data-id="' . $contract['id'] . '">';
            $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($contract['contract_no'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($contract['contract_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($contract['customer_name'] ?? '') . '</td>';
            $html .= '<td style="text-align:right;">' . number_format($contract['contract_amount'] ?? 0, 2) . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($contract['currency'] ?? '') . '</td>';
            $html .= '<td style="text-align:center;">' . htmlspecialchars($contract['contract_status'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($contract['business_user_name'] ?? '') . '</td>';
            $html .= '<td style="text-align:center;">' . ($contract['created_at'] ? date('Y-m-d', strtotime($contract['created_at'])) : '') . '</td>';
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
        <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
    </div>

    <form id="search-form" class="module-form" autocomplete="off">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="page_size" value="10">
        <table class="module-table" style="margin-bottom:15px;">
            <tr>
                <td class="module-label">合同名称：</td>
                <td><input type="text" name="contract_name" class="module-input" placeholder="请输入合同名称"></td>
                <td class="module-label">客户名称：</td>
                <td><?= render_customer_search('customer_id', $customers, '') ?></td>
                <td class="module-label"></td>
                <td></td>
            </tr>
        </table>
    </form>

    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:50px;text-align:center;">序号</th>
                <th style="width:120px;text-align:center;">合同编号</th>
                <th style="width:180px;">合同名称</th>
                <th style="width:150px;">客户名称</th>
                <th style="width:100px;text-align:center;">合同金额</th>
                <th style="width:80px;text-align:center;">币别</th>
                <th style="width:80px;text-align:center;">合同状态</th>
                <th style="width:100px;">业务人员</th>
                <th style="width:100px;text-align:center;">创建时间</th>
            </tr>
        </thead>
        <tbody id="contract-list">
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

<script>
    (function() {
        var form = document.getElementById('search-form'),
            btnSearch = document.querySelector('.btn-search'),
            btnReset = document.querySelector('.btn-reset'),
            btnEdit = document.querySelector('.btn-edit'),
            contractList = document.getElementById('contract-list'),
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

        function loadContractData() {
            contractList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            selectedId = null;
            btnEdit.disabled = true;

            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);

            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') {
                    params.append(pair[0], pair[1]);
                }
            }

            var baseUrl = window.location.href.split('?')[0];
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/customer_management/contract_management/completed.php';

            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                contractList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                            } else {
                                contractList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            contractList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">数据解析失败</td></tr>';
                        }
                    } else {
                        contractList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">网络请求失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            contractList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.onclick = function() {
                    contractList.querySelectorAll('tr[data-id]').forEach(r => r.classList.remove('module-selected'));
                    this.classList.add('module-selected');
                    selectedId = this.getAttribute('data-id');
                    btnEdit.disabled = false;
                }
            });
        }

        // 修改按钮
        btnEdit.onclick = function() {
            if (!selectedId) {
                alert('请先选择要修改的合同');
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/contract_management/set_edit_contract.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    if (window.parent.openTab) {
                        // 编辑合同的索引是0,4,null
                        window.parent.openTab(0, 4, null);
                    } else {
                        alert('框架导航功能不可用');
                    }
                }
            };
            xhr.send('contract_id=' + selectedId);
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

        // 搜索按钮
        btnSearch.onclick = function() {
            currentPage = 1;
            loadContractData();
        };

        // 重置按钮
        btnReset.onclick = function() {
            form.reset();
            document.querySelectorAll('.module-select-search-input').forEach(i => i.value = '');
            document.querySelectorAll('.module-select-search-box input[type=hidden]').forEach(i => i.value = '');
            currentPage = 1;
            loadContractData();
        };

        // 分页大小改变
        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            loadContractData();
        };

        // 分页按钮
        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    loadContractData();
                }
            };
        });

        // 跳转按钮
        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            loadContractData();
        };

        // 客户搜索下拉
        var customerData = <?php echo json_encode($customers, JSON_UNESCAPED_UNICODE); ?>;

        function bindCustomerSearch(box) {
            var input = box.querySelector('.module-select-search-input');
            var hidden = box.querySelector('input[type=hidden]');
            var list = box.querySelector('.module-select-search-list');
            var searchInput = list.querySelector('.module-select-search-list-input');
            var itemsDiv = list.querySelector('.module-select-search-list-items');

            function renderList(filter) {
                var html = '<div class="module-select-search-item" data-id="">--全部--</div>',
                    found = false;
                customerData.forEach(function(item) {
                    var displayName = item.customer_name_cn;
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
        document.querySelectorAll('.module-select-search-box').forEach(bindCustomerSearch);

        // 初始加载数据
        loadContractData();
    })();
</script>