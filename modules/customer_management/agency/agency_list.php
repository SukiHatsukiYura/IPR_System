<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 代理机构列表功能 - 客户管理/代理机构模块下的代理机构列表功能

// 统一选项声明
$options = [
    'is_active_options' => ['' => '--全部--', '1' => '是', '0' => '否'],
    'is_customer_options' => ['' => '--全部--', '1' => '是', '0' => '否'],
    'agency_types' => ['专利', '商标', '版权'],
];

// 查询所有客户用于下拉
$customer_stmt = $pdo->prepare("SELECT id, customer_name_cn FROM customer ORDER BY customer_name_cn ASC");
$customer_stmt->execute();
$customers = $customer_stmt->fetchAll();

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
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
        'agency_name_cn' => 'LIKE',
        'country' => 'LIKE',
        'responsible_person' => 'LIKE',
        'is_active' => '=',
        'is_customer' => '=',
        'agency_type' => 'FIND_IN_SET',
    ];
    foreach ($search_fields as $field => $op) {
        if (!empty($_GET[$field])) {
            if ($op == 'LIKE') {
                $where[] = "$field LIKE :$field";
                $params[$field] = '%' . $_GET[$field] . '%';
            } elseif ($op == 'FIND_IN_SET') {
                $where[] = "FIND_IN_SET(:$field, agency_types)";
                $params[$field] = $_GET[$field];
            } else {
                $where[] = "$field = :$field";
                $params[$field] = $_GET[$field];
            }
        }
    }
    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count_sql = "SELECT COUNT(*) FROM agency" . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);
    $sql = "SELECT a.*, c.customer_name_cn as customer_name FROM agency a LEFT JOIN customer c ON a.customer_id = c.id" . $sql_where . " ORDER BY a.id DESC LIMIT :offset, :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $page_size, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    $agencies = $stmt->fetchAll();
    $html = '';
    if (empty($agencies)) {
        $html = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($agencies as $index => $agency) {
            $html .= '<tr data-id="' . $agency['id'] . '">';
            $html .= '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>';
            $html .= '<td>' . h($agency['agency_name_cn']) . '</td>';
            $html .= '<td>' . h($agency['country']) . '</td>';
            $html .= '<td>' . h($agency['responsible_person']) . '</td>';
            $html .= '<td>' . h($agency['is_active'] ? '是' : '否') . '</td>';
            $html .= '<td>' . h($agency['is_customer'] ? '是' : '否') . '</td>';
            $html .= '<td>' . h($agency['customer_name'] ?? '') . '</td>';
            $html .= '<td>' . h($agency['agency_types']) . '</td>';
            $html .= '<td>' . ($agency['created_at'] ? date('Y-m-d', strtotime($agency['created_at'])) : '') . '</td>';
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
?>
<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
        <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
        <button type="button" class="btn-add" onclick="window.parent.openTab ? window.parent.openTab(0, 2, 0) : window.location.href='modules/customer_management/agency/add_agency.php'"><i class="icon-add"></i> 新增代理机构</button>
        <button type="button" class="btn-edit" disabled><i class="icon-edit"></i> 修改</button>
    </div>
    <form id="search-form" class="module-form" autocomplete="off">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="page_size" value="10">
        <table class="module-table" style="margin-bottom:15px;">
            <tr>
                <td class="module-label">代理机构名称：</td>
                <td><input type="text" name="agency_name_cn" class="module-input"></td>
                <td class="module-label">国家：</td>
                <td><input type="text" name="country" class="module-input"></td>
                <td class="module-label">负责人：</td>
                <td><input type="text" name="responsible_person" class="module-input"></td>
            </tr>
            <tr>
                <td class="module-label">是否有效：</td>
                <td><select name="is_active" class="module-input">
                        <?php foreach ($options['is_active_options'] as $k => $v): ?><option value="<?= h($k) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">是否为客户：</td>
                <td><select name="is_customer" class="module-input">
                        <?php foreach ($options['is_customer_options'] as $k => $v): ?><option value="<?= h($k) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
                <td class="module-label">类型：</td>
                <td><select name="agency_type" class="module-input">
                        <option value="">--全部--</option><?php foreach ($options['agency_types'] as $v): ?><option value="<?= h($v) ?>"><?= h($v) ?></option><?php endforeach; ?>
                    </select></td>
            </tr>
        </table>
    </form>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:60px;text-align:center;">序号</th>
                <th style="width:200px;">代理机构名称</th>
                <th style="width:100px;">国家</th>
                <th style="width:100px;">负责人</th>
                <th style="width:80px;">是否有效</th>
                <th style="width:80px;">是否为客户</th>
                <th style="width:150px;">关联客户</th>
                <th style="width:120px;">类型</th>
                <th style="width:100px;">创建时间</th>
            </tr>
        </thead>
        <tbody id="agency-list">
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
            agencyList = document.getElementById('agency-list'),
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

        function loadAgencyData() {
            agencyList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
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
            var requestUrl = baseUrl.replace('index.php', '') + 'modules/customer_management/agency/agency_list.php';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                agencyList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                            } else {
                                agencyList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            agencyList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        agencyList.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            agencyList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.onclick = function() {
                    agencyList.querySelectorAll('tr[data-id]').forEach(r => r.classList.remove('module-selected'));
                    this.classList.add('module-selected');
                    selectedId = this.getAttribute('data-id');
                    btnEdit.disabled = false;
                }
            });
        }
        btnEdit.onclick = function() {
            if (!selectedId) {
                alert('请先选择要修改的代理机构');
                return;
            }
            var xhr = new XMLHttpRequest();
            var fd = new FormData();
            fd.append('id', selectedId);
            xhr.open('POST', 'modules/customer_management/agency/set_edit_agency.php', true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        window.parent.openTab ? window.parent.openTab(0, 2, 0) : window.location.href = 'modules/customer_management/agency/add_agency.php';
                    } else {
                        alert(res.msg || '设置编辑ID失败');
                    }
                } catch (e) {
                    alert('设置编辑ID失败');
                }
            };
            xhr.send(fd);
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
            loadAgencyData();
        };
        btnReset.onclick = function() {
            form.reset();
            currentPage = 1;
            loadAgencyData();
        };
        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            loadAgencyData();
        };
        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    loadAgencyData();
                }
            };
        });
        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            loadAgencyData();
        };
        loadAgencyData();
    })();
</script>