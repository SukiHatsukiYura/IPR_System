<?php
// 版权编辑-费用信息
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['ajax']) || isset($_POST['ajax']) || (isset($_POST['action']) && $_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => '未登录或会话超时']);
        exit;
    } else {
        header('Location: /login.php');
        exit;
    }
}

if (!isset($_GET['copyright_id']) || intval($_GET['copyright_id']) <= 0) {
    echo '<div class="module-error">未指定版权ID</div>';
    exit;
}
$copyright_id = intval($_GET['copyright_id']);

// 验证版权是否存在
$copyright_stmt = $pdo->prepare("SELECT id, case_name, process_item FROM copyright_case_info WHERE id = ?");
$copyright_stmt->execute([$copyright_id]);
$copyright_info = $copyright_stmt->fetch();
if (!$copyright_info) {
    echo '<div class="module-error">未找到该版权信息</div>';
    exit;
}

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-select-fees"><i class="icon-search"></i> 选择官费</button>
        <button type="button" class="btn-set-review"><i class="icon-edit"></i> 设置核查</button>
    </div>

    <table class="module-table">
        <colgroup>
            <col style="width:40px;">
            <col style="width:60px;">
            <col style="width:180px;">
            <col style="width:80px;">
            <col style="width:60px;">
            <col style="width:80px;">
            <col style="width:60px;">
            <col style="width:80px;">
            <col style="width:80px;">
            <col style="width:100px;">
            <col style="width:100px;">
            <col style="width:80px;">
        </colgroup>
        <thead>
            <tr class="module-table-header">
                <th>选择</th>
                <th>序号</th>
                <th>费用名称</th>
                <th>费减类型</th>
                <th>币别</th>
                <th>金额</th>
                <th>数量</th>
                <th>实际币别</th>
                <th>实际金额</th>
                <th>应收日期</th>
                <th>实收日期</th>
                <th>是否核查</th>
            </tr>
        </thead>
        <tbody id="fee-list">
            <tr>
                <td colspan="12" class="text-center module-loading">正在加载数据...</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 选择官费模态框 -->
<div id="select-fees-modal" class="module-modal">
    <div class="module-modal-content" style="max-width: 1400px; width: 90vw;">
        <div class="module-modal-close" id="select-fees-modal-close">&times;</div>
        <h3 class="module-modal-title">选择官费</h3>

        <div class="module-modal-body">
            <div style="margin-bottom: 10px;">
                <input type="text" id="fee-search-keyword" placeholder="搜索费用名称..." class="module-input" style="width: 300px; display: inline-block;">
                <button type="button" class="btn-mini btn-search-fees">查询</button>
                <button type="button" class="btn-mini btn-select-all-fees">全选</button>
                <button type="button" class="btn-mini btn-clear-all-fees">清空</button>
            </div>

            <div style="max-height: 400px; overflow-y: auto;">
                <table class="module-table">
                    <thead>
                        <tr class="module-table-header">
                            <th style="width:40px;">选择</th>
                            <th style="width:40px;">序号</th>
                            <th style="width:200px;">费用名称</th>
                            <th style="width:80px;">费减类型</th>
                            <th style="width:80px;">币别</th>
                            <th style="width:80px;">金额</th>
                            <th style="width:80px;">数量</th>
                            <th style="width:80px;">实际币别</th>
                            <th style="width:100px;">实际金额</th>
                            <th style="width:100px;">应收日期</th>
                            <th style="width:100px;">实收日期</th>
                        </tr>
                    </thead>
                    <tbody id="official-fees-list">
                        <tr>
                            <td colspan="11" class="text-center module-loading">正在加载...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="module-form-buttons">
            <button type="button" class="btn-mini btn-add-selected-fees">确定</button>
            <button type="button" class="btn-mini btn-cancel-select-fees">取消</button>
        </div>
    </div>
</div>

<script>
    (function() {
        var copyrightId = <?= $copyright_id ?>;
        var API_URL = 'modules/copyright_management/edit_tabs/fee_api.php?copyright_id=' + copyrightId;

        // 通用AJAX请求函数
        function makeRequest(action, data, callback, errorMsg) {
            var formData = new FormData();
            formData.append('action', action);

            if (data) {
                Object.keys(data).forEach(function(key) {
                    formData.append(key, data[key]);
                });
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', API_URL, true);
            xhr.onload = function() {
                try {
                    var response = JSON.parse(xhr.responseText);
                    callback(response);
                } catch (e) {
                    alert((errorMsg || '操作失败') + '：响应解析错误');
                }
            };
            xhr.send(formData);
        }

        // 通用模态框操作
        function toggleModal(modalId, show) {
            document.getElementById(modalId).style.display = show ? 'flex' : 'none';
        }

        // 通用确认删除
        function confirmDelete(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        // 费用管理模块
        var FeeManager = {
            elements: {
                btnSelectFees: document.querySelector('.btn-select-fees'),
                btnSetReview: document.querySelector('.btn-set-review'),
                list: document.getElementById('fee-list'),
                selectModal: document.getElementById('select-fees-modal'),
                officialFeesList: document.getElementById('official-fees-list')
            },

            init: function() {
                this.bindEvents();
                this.loadData();
            },

            bindEvents: function() {
                var self = this;

                // 主按钮事件
                this.elements.btnSelectFees.onclick = function() {
                    self.openSelectModal();
                };
                this.elements.btnSetReview.onclick = function() {
                    self.setReviewStatus();
                };

                // 选择官费模态框事件
                document.getElementById('select-fees-modal-close').onclick = function() {
                    toggleModal('select-fees-modal', false);
                };
                document.querySelector('.btn-cancel-select-fees').onclick = function() {
                    toggleModal('select-fees-modal', false);
                };
                document.querySelector('.btn-search-fees').onclick = function() {
                    self.searchOfficialFees();
                };
                document.querySelector('.btn-select-all-fees').onclick = function() {
                    self.selectAllFees();
                };
                document.querySelector('.btn-clear-all-fees').onclick = function() {
                    self.clearAllFees();
                };
                document.querySelector('.btn-add-selected-fees').onclick = function() {
                    self.addSelectedFees();
                };

                // 搜索框回车事件
                document.getElementById('fee-search-keyword').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        self.searchOfficialFees();
                    }
                });
            },

            loadData: function() {
                var self = this;
                this.elements.list.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">正在加载数据...</td></tr>';

                makeRequest('get_fee_list', null, function(response) {
                    if (response.success) {
                        self.elements.list.innerHTML = response.html;
                        self.bindTableEvents();
                    } else {
                        self.elements.list.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px 0;">加载数据失败：' + (response.msg || '') + '</td></tr>';
                    }
                }, '加载费用数据失败');
            },

            bindTableEvents: function() {
                // 费用列表不需要编辑和删除功能，所以这里不绑定任何事件
            },

            openSelectModal: function() {
                toggleModal('select-fees-modal', true);
                this.searchOfficialFees();
            },

            searchOfficialFees: function() {
                var self = this;
                var keyword = document.getElementById('fee-search-keyword').value;

                makeRequest('search_official_fees', {
                    keyword: keyword
                }, function(response) {
                    if (response.success) {
                        self.elements.officialFeesList.innerHTML = response.html;
                    } else {
                        self.elements.officialFeesList.innerHTML = '<tr><td colspan="11" style="text-align:center;color:#f44336;">' + (response.msg || '搜索失败') + '</td></tr>';
                    }
                }, '搜索官费失败');
            },

            selectAllFees: function() {
                document.querySelectorAll('.fee-checkbox').forEach(function(cb) {
                    cb.checked = true;
                });
            },

            clearAllFees: function() {
                document.querySelectorAll('.fee-checkbox').forEach(function(cb) {
                    cb.checked = false;
                });
            },

            addSelectedFees: function() {
                var self = this;
                var checkedBoxes = document.querySelectorAll('.fee-checkbox:checked');

                var selectedIds = Array.from(checkedBoxes).map(function(cb) {
                    return cb.value;
                }).join(',');

                // 收集弹窗中修改的费用数据
                var updatedFeesData = {};
                document.querySelectorAll('#official-fees-list tr[data-template-id]').forEach(function(row) {
                    var templateId = row.getAttribute('data-template-id');
                    var checkbox = row.querySelector('.fee-checkbox');

                    // 只收集已勾选费用的数据
                    if (checkbox && checkbox.checked) {
                        var feeData = {};

                        // 安全获取元素值
                        var feeReductionTypeEl = row.querySelector('.fee-reduction-type-cell');
                        var feeQuantityEl = row.querySelector('.fee-quantity-cell');
                        var feeActualCurrencyEl = row.querySelector('.fee-actual-currency-cell');
                        var feeActualAmountEl = row.querySelector('.fee-actual-amount-cell');
                        var feeReceivableDateEl = row.querySelector('.fee-receivable-date-cell');
                        var feeReceivedDateEl = row.querySelector('.fee-received-date-cell');

                        if (feeReductionTypeEl) feeData.fee_reduction_type = feeReductionTypeEl.value;
                        if (feeQuantityEl) feeData.quantity = parseInt(feeQuantityEl.value) || 1;
                        if (feeActualCurrencyEl) feeData.actual_currency = feeActualCurrencyEl.value;
                        if (feeActualAmountEl) feeData.actual_amount = parseFloat(feeActualAmountEl.value) || 0;
                        if (feeReceivableDateEl) feeData.receivable_date = feeReceivableDateEl.value;
                        if (feeReceivedDateEl) feeData.received_date = feeReceivedDateEl.value;

                        updatedFeesData[templateId] = feeData;
                    }
                });

                makeRequest('add_selected_fees', {
                    selected_fees: selectedIds,
                    updated_fees_data: JSON.stringify(updatedFeesData)
                }, function(response) {
                    if (response.success) {
                        alert(response.msg || '操作成功');
                        toggleModal('select-fees-modal', false);
                        self.loadData();
                    } else {
                        alert('操作失败：' + (response.msg || '未知错误'));
                    }
                }, '操作失败');
            },

            setReviewStatus: function() {
                var self = this;
                var checkedBoxes = document.querySelectorAll('#fee-list input[type="checkbox"]:checked');

                if (checkedBoxes.length === 0) {
                    alert('请选择要设置核查状态的费用');
                    return;
                }

                var feeIds = Array.from(checkedBoxes).map(function(cb) {
                    return cb.value;
                }).join(',');

                makeRequest('set_review_status', {
                    fee_ids: feeIds
                }, function(response) {
                    if (response.success) {
                        alert(response.msg || '设置成功');
                        self.loadData();
                    } else {
                        alert('设置失败：' + (response.msg || '未知错误'));
                    }
                }, '设置核查状态失败');
            }
        };

        // 初始化费用管理模块
        FeeManager.init();
    })();
</script>
</rewritten_file>