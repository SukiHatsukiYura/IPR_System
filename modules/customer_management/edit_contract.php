<?php
session_start();
include_once(__DIR__ . '/../../database.php');
include_once(__DIR__ . '/../../common/functions.php');
check_access_via_framework();

// 获取contract_id
$contract_id = 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $contract_id = intval($_GET['id']);
} elseif (isset($_SESSION['edit_contract_id']) && intval($_SESSION['edit_contract_id']) > 0) {
    $contract_id = intval($_SESSION['edit_contract_id']);
    unset($_SESSION['edit_contract_id']);
}

if ($contract_id <= 0) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未指定要编辑的合同ID</div>';
    exit;
}

// 查询合同信息
$contract_stmt = $pdo->prepare("SELECT * FROM contract WHERE id = ?");
$contract_stmt->execute([$contract_id]);
$contract = $contract_stmt->fetch();
if (!$contract) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未找到该合同信息</div>';
    exit;
}

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// 定义tab列表
$tabs = [
    'basic' => '基本信息',
    'extend' => '扩展信息',
    'payment' => '回款信息',
    'file' => '附件',
    // 后续可扩展更多tab
];

// 输出下拉框所需JS资源
render_select_search_assets();
?>
<div class="module-panel">
    <div class="module-btns">
        <button type="button" class="btn-return" onclick="returnToList()"><i class="icon-cancel"></i> 返回列表</button>
    </div>
    <h3 style="text-align:center;margin-bottom:15px;">编辑合同</h3>
    <div id="contract-tabs-bar" style="margin-bottom:10px;">
        <?php foreach ($tabs as $key => $label): ?>
            <button type="button" class="btn-mini tab-btn<?= $key === 'basic' ? ' active' : '' ?>" data-tab="<?= $key ?>"><?= $label ?></button>
        <?php endforeach; ?>
    </div>
    <div id="contract-tab-content" style="min-height:320px;"></div>
</div>

<script>
    (function() {
        var contractId = <?= $contract_id ?>;
        var currentTab = 'basic';

        // 加载tab内容
        function loadTab(tabName) {
            var contentDiv = document.getElementById('contract-tab-content');
            contentDiv.innerHTML = '<div style="text-align:center;padding:40px;color:#888;">正在加载...</div>';

            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'modules/customer_management/contract_management/edit_tabs/' + tabName + '.php?contract_id=' + contractId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        contentDiv.innerHTML = xhr.responseText;
                        // 执行返回内容中的script标签
                        var scripts = contentDiv.querySelectorAll('script');
                        scripts.forEach(function(script) {
                            var newScript = document.createElement('script');
                            if (script.src) {
                                newScript.src = script.src;
                            } else {
                                newScript.text = script.textContent;
                            }
                            document.body.appendChild(newScript);
                        });
                    } else {
                        contentDiv.innerHTML = '<div style="text-align:center;padding:40px;color:#f44336;">加载失败</div>';
                    }
                }
            };
            xhr.send();
        }

        // tab切换
        function switchTab(tabName) {
            // 更新按钮状态
            document.querySelectorAll('.tab-btn').forEach(function(btn) {
                btn.classList.remove('active');
            });
            document.querySelector('.tab-btn[data-tab="' + tabName + '"]').classList.add('active');

            currentTab = tabName;
            loadTab(tabName);
        }

        // 绑定tab点击事件
        document.querySelectorAll('.tab-btn').forEach(function(btn) {
            btn.onclick = function() {
                var tabName = this.getAttribute('data-tab');
                switchTab(tabName);
            };
        });

        // 返回列表
        window.returnToList = function() {
            if (window.parent.openTab) {
                // 客户管理模块索引为0，合同管理菜单索引为3，合同列表子菜单索引为4
                window.parent.openTab(0, 3, 4);
            } else {
                alert('框架导航功能不可用');
            }
        };

        // 初始加载基本信息tab
        loadTab('basic');
    })();
</script>