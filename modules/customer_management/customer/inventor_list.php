<?php
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
session_start();
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => '未登录或会话超时']);
        exit;
    } else {
        header('Location: /login.php');
        exit;
    }
}
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
    // 查询条件
    if (!empty($_GET['name_cn'])) {
        $where[] = 'i.name_cn LIKE :name_cn';
        $params['name_cn'] = '%' . $_GET['name_cn'] . '%';
    }
    if (!empty($_GET['job_no'])) {
        $where[] = 'i.job_no LIKE :job_no';
        $params['job_no'] = '%' . $_GET['job_no'] . '%';
    }
    if (!empty($_GET['country'])) {
        $where[] = 'i.country LIKE :country';
        $params['country'] = '%' . $_GET['country'] . '%';
    }
    if (!empty($_GET['customer_id'])) {
        $where[] = 'i.customer_id = :customer_id';
        $params['customer_id'] = $_GET['customer_id'];
    }
    if (!empty($_GET['created_from'])) {
        $where[] = 'i.created_at >= :created_from';
        $params['created_from'] = $_GET['created_from'] . ' 00:00:00';
    }
    if (!empty($_GET['created_to'])) {
        $where[] = 'i.created_at <= :created_to';
        $params['created_to'] = $_GET['created_to'] . ' 23:59:59';
    }
    if (isset($_GET['is_tech_contact']) && $_GET['is_tech_contact'] !== '') {
        $where[] = 'i.is_tech_contact = :is_tech_contact';
        $params['is_tech_contact'] = $_GET['is_tech_contact'];
    }
    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count_sql = "SELECT COUNT(*) FROM inventor i $sql_where";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $page_size);
    $sql = "SELECT i.*, c.customer_name_cn FROM inventor i LEFT JOIN customer c ON i.customer_id = c.id $sql_where ORDER BY i.id DESC LIMIT :offset, :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $page_size, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $html = '';
    if (empty($rows)) {
        $html = '<tr><td colspan="10" style="text-align:center;padding:20px 0;">暂无数据</td></tr>';
    } else {
        foreach ($rows as $index => $a) {
            $html .= '<tr data-id="' . $a['id'] . '" data-customer-id="' . $a['customer_id'] . '">' .
                '<td style="text-align:center;">' . ($offset + $index + 1) . '</td>' .
                '<td>' . htmlspecialchars($a['customer_name_cn'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($a['name_cn'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($a['job_no'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($a['country'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($a['province'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($a['mobile'] ?? '') . '</td>' .
                '<td>' . htmlspecialchars($a['email'] ?? '') . '</td>' .
                '<td style="text-align:center;">' . ($a['is_tech_contact'] ? '是' : '否') . '</td>' .
                '<td style="text-align:center;">' .
                '<button type="button" class="btn-mini btn-edit">✎</button>' .
                '<button type="button" class="btn-mini btn-del" style="color:#f44336;">✖</button>' .
                '</td>' .
                '</tr>';
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
$yesno = ['' => '--请选择--', '0' => '否', '1' => '是'];
?>
<div class="module-panel">
    <form id="search-form" class="module-form" autocomplete="off" style="margin-bottom:12px;">
        <div class="module-btns">
            <button type="button" class="btn-search"><i class="icon-search"></i> 搜索</button>
            <button type="button" class="btn-reset"><i class="icon-cancel"></i> 重置</button>
        </div>
        <table class="module-table" style="margin-bottom:10px;width:100%;min-width:0;">
            <tr>
                <td class="module-label" style="width:80px;">发明人：</td>
                <td style="width:220px;"><input type="text" name="name_cn" class="module-input" style="width:200px;"></td>
                <td class="module-label" style="width:80px;">工号：</td>
                <td style="width:220px;"><input type="text" name="job_no" class="module-input" style="width:200px;"></td>
            </tr>
            <tr>
                <td class="module-label">客户名称：</td>
                <td>
                    <select name="customer_id" class="module-input" style="width:200px;">
                        <option value="">--全部--</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= h($c['id']) ?>"><?= h($c['customer_name_cn']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="module-label">国家(地区)：</td>
                <td><input type="text" name="country" class="module-input" style="width:200px;"></td>
            </tr>
            <tr>
                <td class="module-label">创建日期：</td>
                <td>
                    <input type="date" name="created_from" class="module-input" style="width:93.5px;display:inline-block;"> -
                    <input type="date" name="created_to" class="module-input" style="width:93.5px;display:inline-block;">
                </td>
                <td class="module-label">是否技术联系人：</td>
                <td>
                    <select name="is_tech_contact" class="module-input" style="width:200px;">
                        <?php foreach ($yesno as $k => $v): ?>
                            <option value="<?= h($k) ?>"><?= h($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
    </form>
    <table class="module-table">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="width:40px;text-align:center;">序号</th>
                <th style="width:140px;">客户名称</th>
                <th style="width:120px;">发明人(中文)</th>
                <th style="width:100px;">工号</th>
                <th style="width:100px;">国家(地区)</th>
                <th style="width:100px;">省份</th>
                <th style="width:100px;">手机</th>
                <th style="width:140px;">邮件</th>
                <th style="width:60px;">技术联系人</th>
                <th style="width:90px;">操作</th>
            </tr>
        </thead>
        <tbody id="inventor-list">
            <tr>
                <td colspan="11" style="text-align:center;padding:20px 0;">正在加载数据...</td>
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
            btnSearch = form.querySelector('.btn-search'),
            btnReset = form.querySelector('.btn-reset'),
            inventorList = document.getElementById('inventor-list'),
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

        function loadInventorData() {
            inventorList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';
            var formData = new FormData(form),
                params = new URLSearchParams();
            params.append('ajax', 1);
            params.append('page', currentPage);
            params.append('page_size', pageSize);
            for (var pair of formData.entries()) {
                if (pair[0] !== 'page' && pair[0] !== 'page_size') params.append(pair[0], pair[1]);
            }
            var requestUrl = 'modules/customer_management/customer/inventor_list.php';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', requestUrl + '?' + params.toString(), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                inventorList.innerHTML = response.html;
                                totalRecordsEl.textContent = response.total_records;
                                currentPageEl.textContent = response.current_page;
                                totalPagesEl.textContent = response.total_pages;
                                currentPage = parseInt(response.current_page);
                                totalPages = parseInt(response.total_pages) || 1;
                                updatePaginationButtons();
                                bindTableRowClick();
                            } else {
                                inventorList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                            }
                        } catch (e) {
                            inventorList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败</td></tr>';
                        }
                    } else {
                        inventorList.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px 0;">加载数据失败，请稍后重试</td></tr>';
                    }
                }
            };
            xhr.send();
        }

        function bindTableRowClick() {
            inventorList.querySelectorAll('tr[data-id]').forEach(function(row) {
                row.querySelector('.btn-del').onclick = function() {
                    if (!confirm('确定删除该发明人？')) return;
                    var id = row.getAttribute('data-id');
                    var customerId = row.getAttribute('data-customer-id');
                    var xhr = new XMLHttpRequest();
                    var fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    fd.append('customer_id', customerId);
                    xhr.open('POST', 'modules/customer_management/customer/customer_tabs/inventor.php', true);
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                loadInventorData();
                            } else {
                                alert('删除失败');
                            }
                        } catch (e) {
                            alert('删除失败');
                        }
                    };
                    xhr.send(fd);
                };
                row.querySelector('.btn-edit').onclick = function() {
                    var id = row.getAttribute('data-id');
                    var customerId = row.getAttribute('data-customer-id');
                    showEditInventorModal(id, customerId);
                };
            });
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
            loadInventorData();
        };
        btnReset.onclick = function() {
            form.reset();
            currentPage = 1;
            loadInventorData();
        };
        pageSizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            loadInventorData();
        };
        [btnFirstPage, btnPrevPage, btnNextPage, btnLastPage].forEach(function(btn) {
            btn.onclick = function() {
                if (!this.disabled) {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    loadInventorData();
                }
            };
        });
        btnPageJump.onclick = function() {
            var page = parseInt(pageInput.value);
            if (isNaN(page) || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            loadInventorData();
        };
        // 弹窗相关
        function showEditInventorModal(id, customerId) {
            // 这里可后续抽取为 inventor_modal.php
            var modal = document.getElementById('edit-inventor-modal');
            var form = document.getElementById('edit-inventor-form');
            form.reset();
            if (!id) {
                form.id.value = 0;
                form.customer_id.value = customerId;
                modal.style.display = 'flex';
                return;
            }
            var fd = new FormData();
            fd.append('action', 'get');
            fd.append('id', id);
            fd.append('customer_id', customerId);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/customer/customer_tabs/inventor.php', true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.data) {
                        for (var k in res.data) {
                            if (form[k] !== undefined) form[k].value = res.data[k] !== null ? res.data[k] : '';
                        }
                        form.customer_id.value = customerId;
                        modal.style.display = 'flex';
                    } else {
                        alert('获取数据失败');
                    }
                } catch (e) {
                    alert('获取数据失败');
                }
            };
            xhr.send(fd);
        }
        document.getElementById('edit-inventor-modal-close').onclick = function() {
            document.getElementById('edit-inventor-modal').style.display = 'none';
        };
        document.querySelector('.btn-cancel-edit-inventor').onclick = function() {
            document.getElementById('edit-inventor-modal').style.display = 'none';
        };
        document.querySelector('.btn-save-edit-inventor').onclick = function() {
            var form = document.getElementById('edit-inventor-form');
            var fd = new FormData(form);
            fd.append('action', 'save');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'modules/customer_management/customer/customer_tabs/inventor.php', true);
            xhr.onload = function() {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        document.getElementById('edit-inventor-modal').style.display = 'none';
                        loadInventorData();
                    } else {
                        alert(res.msg || '保存失败');
                    }
                } catch (e) {
                    alert('保存失败');
                }
            };
            xhr.send(fd);
        };
        loadInventorData();
    })();
</script>
<!-- 发明人编辑弹窗（字段与inventor.php一致） -->
<div id="edit-inventor-modal" style="display:none;position:fixed;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:24px 32px;width:950px;max-width:98vw;max-height:80vh;position:relative;display:flex;flex-direction:column;">
        <div style="position:absolute;right:18px;top:10px;cursor:pointer;font-size:22px;color:#888;" id="edit-inventor-modal-close">×</div>
        <h3 style="text-align:center;margin-bottom:18px;">编辑发明人</h3>
        <div style="flex:1 1 auto;overflow-y:auto;">
            <form id="edit-inventor-form" class="module-form">
                <input type="hidden" name="id" value="0">
                <input type="hidden" name="customer_id" value="">
                <table class="module-table" style="table-layout:fixed;width:100%;min-width:0;">
                    <colgroup>
                        <col style="width:120px;">
                        <col style="width:320px;">
                        <col style="width:120px;">
                        <col style="width:320px;">
                    </colgroup>
                    <tr>
                        <td class="module-label module-req">*中文名</td>
                        <td><input type="text" name="name_cn" class="module-input" required></td>
                        <td class="module-label">英文名</td>
                        <td><input type="text" name="name_en" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">工号</td>
                        <td><input type="text" name="job_no" class="module-input"></td>
                        <td class="module-label">名称/姓(中文)</td>
                        <td><input type="text" name="xing_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">名称/姓(英文)</td>
                        <td><input type="text" name="xing_en" class="module-input"></td>
                        <td class="module-label">名(中文)</td>
                        <td><input type="text" name="ming_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">名(英文)</td>
                        <td><input type="text" name="ming_en" class="module-input"></td>
                        <td class="module-label">国籍</td>
                        <td><input type="text" name="nationality" class="module-input" value="中国"></td>
                    </tr>
                    <tr>
                        <td class="module-label">国家(地区)</td>
                        <td><input type="text" name="country" class="module-input" value="中国"></td>
                        <td class="module-label">是否为技术联系人</td>
                        <td>
                            <select name="is_tech_contact" class="module-input">
                                <option value="0">否</option>
                                <option value="1">是</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="module-label">省份</td>
                        <td><input type="text" name="province" class="module-input"></td>
                        <td class="module-label">城市(中文)</td>
                        <td><input type="text" name="city_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">城市(英文)</td>
                        <td><input type="text" name="city_en" class="module-input"></td>
                        <td class="module-label">街道地址(中文)</td>
                        <td><input type="text" name="address_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">街道地址(英文)</td>
                        <td><input type="text" name="address_en" class="module-input"></td>
                        <td class="module-label">部门/楼层(中文)</td>
                        <td><input type="text" name="department_cn" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">部门/楼层(英文)</td>
                        <td><input type="text" name="department_en" class="module-input"></td>
                        <td class="module-label">邮件</td>
                        <td><input type="email" name="email" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">证件号码</td>
                        <td><input type="text" name="id_number" class="module-input"></td>
                        <td class="module-label">座机</td>
                        <td><input type="text" name="phone" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">QQ</td>
                        <td><input type="text" name="qq" class="module-input"></td>
                        <td class="module-label">手机</td>
                        <td><input type="text" name="mobile" class="module-input"></td>
                    </tr>
                    <tr>
                        <td class="module-label">邮编</td>
                        <td><input type="text" name="postcode" class="module-input"></td>
                        <td class="module-label">备注</td>
                        <td rowspan="2"><textarea name="remark" class="module-input" style="min-height:48px;"></textarea></td>
                    </tr>
                </table>
                <div style="text-align:center;margin-top:12px;">
                    <button type="button" class="btn-save-edit-inventor btn-mini" style="margin-right:16px;">保存</button>
                    <button type="button" class="btn-cancel-edit-inventor btn-mini">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>