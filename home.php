<?php
// 首页内容，仿照原系统布局
?>
<div class="homepage-panel">
    <!-- 功能更新通知 -->
    <div class="panel collapsible-panel">
        <div class="panel-header collapsible-header" data-target="update-content">
            <span class="collapse-arrow">&#9660;</span>
            <h3 class="panel-title"><i class="icon-notification"></i> 功能更新通知</h3>
        </div>
        <div class="panel-content collapsible-content" id="update-content">
            <div class="update-notification">
                <div class="update-item">
                    <span class="update-badge new">NEW</span>
                    <span class="update-text">批量导入功能已开放：客户列表、专利查询、商标查询、版权查询现已支持Excel批量导入功能，可大幅提升数据录入效率。</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 已开放功能 -->
    <div class="panel collapsible-panel">
        <div class="panel-header collapsible-header" data-target="features-content">
            <span class="collapse-arrow">&#9660;</span>
            <h3 class="panel-title"><i class="icon-features"></i> 已开放功能</h3>
        </div>
        <!-- 改成显示多行，一行3个模块 -->
        <div class="panel-content collapsible-content" id="features-content">
            <div class="features-grid">
                <div class="feature-category">
                    <h4 class="category-title">系统管理</h4>
                    <div class="feature-links">
                        <a href="javascript:void(0)" onclick="openSystemTab('personal_settings', '个人设置')" class="feature-link">
                            <i class="icon-user"></i>个人设置
                        </a>
                        <a href="javascript:void(0)" onclick="openSystemTab('department_settings', '部门设置')" class="feature-link">
                            <i class="icon-department"></i>部门设置
                        </a>
                    </div>
                </div>

                <div class="feature-category">
                    <h4 class="category-title">客户管理</h4>
                    <div class="feature-links">
                        <a href="javascript:void(0)" onclick="openCustomerTab('customer', '客户管理')" class="feature-link">
                            <i class="icon-customer"></i>客户
                        </a>
                        <a href="javascript:void(0)" onclick="openCustomerTab('agency', '代理机构')" class="feature-link">
                            <i class="icon-agency"></i>代理机构
                        </a>
                    </div>
                </div>

                <div class="feature-category">
                    <h4 class="category-title">专利管理</h4>
                    <div class="feature-links">
                        <a href="javascript:void(0)" onclick="window.parent.openTab ? window.parent.openTab(1, 0, null) : alert('框架导航功能不可用')" class="feature-link">
                            <i class="icon-add"></i>新增专利
                        </a>
                        <a href="javascript:void(0)" onclick="window.parent.openTab ? window.parent.openTab(1, 5, 0) : alert('框架导航功能不可用')" class="feature-link">
                            <i class="icon-search"></i>专利查询
                        </a>
                    </div>
                </div>

                <div class="feature-category">
                    <h4 class="category-title">商标管理</h4>
                    <div class="feature-links">
                        <a href="javascript:void(0)" onclick="window.parent.openTab ? window.parent.openTab(2, 0, null) : alert('框架导航功能不可用')" class="feature-link">
                            <i class="icon-add"></i>新增商标
                        </a>
                        <a href="javascript:void(0)" onclick="window.parent.openTab ? window.parent.openTab(2, 3, 0) : alert('框架导航功能不可用')" class="feature-link">
                            <i class="icon-search"></i>商标查询
                        </a>
                    </div>
                </div>

                <div class="feature-category">
                    <h4 class="category-title">版权管理</h4>
                    <div class="feature-links">
                        <a href="javascript:void(0)" onclick="window.parent.openTab ? window.parent.openTab(3, 0, null) : alert('框架导航功能不可用')" class="feature-link">
                            <i class="icon-add"></i>新增版权
                        </a>
                        <a href="javascript:void(0)" onclick="window.parent.openTab ? window.parent.openTab(3, 1, 0) : alert('框架导航功能不可用')" class="feature-link">
                            <i class="icon-search"></i>版权查询
                        </a>
                    </div>
                </div>
                <div class="feature-category">
                    <h4 class="category-title">合同管理</h4>
                    <div class="feature-links">
                        <a href="javascript:void(0)" onclick="window.parent.openTab ? window.parent.openTab(0, 3, 0) : alert('框架导航功能不可用')" class="feature-link">
                            <i class="icon-add"></i>新增合同
                        </a>
                        <a href="javascript:void(0)" onclick="window.parent.openTab ? window.parent.openTab(0, 3, 4) : alert('框架导航功能不可用')" class="feature-link">
                            <i class="icon-list"></i>合同列表
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>



    <!-- 待办事项 -->
    <div class="panel collapsible-panel">
        <div class="panel-header collapsible-header" data-target="todo-content">
            <span class="collapse-arrow">&#9660;</span>
            <h3 class="panel-title"><i class="icon-todo"></i> 待办事项</h3>
        </div>
        <div class="panel-content collapsible-content" id="todo-content">
            <div class="panel-empty">暂无待办事项</div>
        </div>
    </div>

    <!-- 进行中的任务 -->
    <div class="panel collapsible-panel">
        <div class="panel-header collapsible-header" data-target="task-content">
            <span class="collapse-arrow">&#9660;</span>
            <h3 class="panel-title"><i class="icon-task"></i> 进行中的任务</h3>
        </div>
        <div class="panel-content collapsible-content" id="task-content">
            <div class="task-cards">
                <div class="task-card">
                    <div class="task-icon orange"><span class="icon-clock"></span>1</div>
                    <div class="task-name">内部期限3天内</div>
                    <div class="task-count">(0)</div>
                </div>
                <div class="task-card">
                    <div class="task-icon orange-dark"><span class="icon-clock"></span>1</div>
                    <div class="task-name">内部期限7天内</div>
                    <div class="task-count">(0)</div>
                </div>
                <div class="task-card">
                    <div class="task-icon red"><span class="icon-clock"></span>3</div>
                    <div class="task-name">客户期限3天内</div>
                    <div class="task-count">(0)</div>
                </div>
                <div class="task-card">
                    <div class="task-icon purple"><span class="icon-clock"></span>7</div>
                    <div class="task-name">客户期限7天内</div>
                    <div class="task-count">(0)</div>
                </div>
                <div class="task-card">
                    <div class="task-icon green"><span class="icon-clock"></span>3</div>
                    <div class="task-name">官方期限3天内</div>
                    <div class="task-count">(0)</div>
                </div>
                <div class="task-card">
                    <div class="task-icon blue"><span class="icon-clock"></span>7</div>
                    <div class="task-name">官方期限7天内</div>
                    <div class="task-count">(0)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 统计报表 -->
    <div class="panel collapsible-panel">
        <div class="panel-header collapsible-header" data-target="report-content">
            <span class="collapse-arrow">&#9660;</span>
            <h3 class="panel-title"><i class="icon-report"></i> 统计报表</h3>
        </div>
        <div class="panel-content collapsible-content" id="report-content">
            <div class="panel-empty">暂无相关报表</div>
        </div>
    </div>
</div>

<style>
    .homepage-panel {
        padding: 18px 18px 0 18px;
    }

    .panel {
        background: #fff;
        margin-bottom: 18px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    .panel-header {
        padding: 12px 18px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
        flex-direction: row;
        justify-content: flex-start;
        background: #f5f5f5;
    }

    .panel-title {
        margin: 0;
        font-size: 17px;
        /* color: #29b6b0; */
        color: #000;
        display: flex;
        align-items: center;
    }

    .panel-title i {
        margin-right: 7px;
        font-size: 18px;
    }

    .collapse-arrow {
        margin-left: 0;
        margin-right: 10px;
        font-size: 16px;
        color: #bbb;
        transition: transform 0.2s;
    }

    .panel-header.collapsed .collapse-arrow {
        transform: rotate(-90deg);
    }

    .panel-content {
        padding: 18px;
        transition: max-height 0.2s, padding 0.2s;
        overflow: hidden;
    }

    .panel-content.collapsed {
        max-height: 0;
        padding-top: 0;
        padding-bottom: 0;
        display: none;
    }

    .panel-empty {
        text-align: center;
        color: #bbb;
        padding: 28px 0;
    }

    .task-cards {
        display: flex;
        justify-content: flex-start;
        flex-wrap: wrap;
        gap: 18px;
    }

    .task-card {
        text-align: center;
        width: 120px;
        margin-bottom: 10px;
    }

    .task-icon {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 10px;
        color: #fff;
        font-size: 20px;
        position: relative;
    }

    .task-icon .icon-clock {
        display: inline-block;
        margin-right: 2px;
        font-size: 18px;
    }

    .orange {
        background: #ff9800;
    }

    .orange-dark {
        background: #f57c00;
    }

    .red {
        background: #f44336;
    }

    .purple {
        background: #9c27b0;
    }

    .green {
        background: #4caf50;
    }

    .blue {
        background: #2196f3;
    }

    /* 功能更新通知样式 */
    .update-notification {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 16px;
    }

    .update-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        line-height: 1.6;
    }

    .update-badge {
        background: #29b6b0;
        color: #fff;
        font-size: 12px;
        font-weight: bold;
        padding: 3px 8px;
        border-radius: 12px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .update-badge.new {
        background: #4caf50;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }

        100% {
            opacity: 1;
        }
    }

    .update-text {
        color: #333;
        font-size: 17px;
    }

    /* 已开放功能样式 */
    /* 改成不限制行数，但是一行最多显示3个模块 */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        grid-template-rows: repeat(auto-fill, minmax(100px, 1fr));
    }

    .feature-category {
        background: #f9f9f9;
        border-radius: 6px;
        padding: 16px;
        border: 1px solid #e0e0e0;
    }

    .category-title {
        margin: 0 0 12px 0;
        font-size: 16px;
        font-weight: 600;
        color: #29b6b0;
        border-bottom: 2px solid #29b6b0;
        padding-bottom: 6px;
    }

    .feature-links {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .feature-link {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #333;
        transition: all 0.2s;
        font-size: 14px;
    }

    .feature-link:hover {
        background: #29b6b0;
        color: #fff;
        border-color: #29b6b0;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(41, 182, 176, 0.2);
    }

    .feature-link i {
        margin-right: 8px;
        font-size: 16px;
        width: 18px;
        text-align: center;
    }

    /* 图标样式 */
    .icon-features::before {
        content: "🚀";
    }

    .icon-user::before {
        content: "👤";
    }

    .icon-department::before {
        content: "🏢";
    }

    .icon-customer::before {
        content: "👥";
    }

    .icon-agency::before {
        content: "🏛️";
    }

    .icon-add::before {
        content: "➕";
    }

    .icon-search::before {
        content: "🔍";
    }

    .icon-todo::before {
        content: "📋";
    }

    .icon-task::before {
        content: "⚡";
    }

    .icon-report::before {
        content: "📊";
    }

    .icon-clock::before {
        content: "⏰";
    }
</style>

<script>
    // 面板折叠功能
    document.addEventListener('DOMContentLoaded', function() {
        // 绑定所有可折叠面板的点击事件
        document.querySelectorAll('.collapsible-header').forEach(function(header) {
            header.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var content = document.getElementById(targetId);
                var arrow = this.querySelector('.collapse-arrow');

                if (content.classList.contains('collapsed')) {
                    // 展开
                    content.classList.remove('collapsed');
                    content.style.display = 'block';
                    arrow.style.transform = 'rotate(0deg)';
                } else {
                    // 折叠
                    content.classList.add('collapsed');
                    content.style.display = 'none';
                    arrow.style.transform = 'rotate(-90deg)';
                }
            });
        });
    });

    // 系统管理跳转函数
    function openSystemTab(subModule, title) {
        if (window.parent.openTab) {
            // 系统管理模块索引为7
            if (subModule === 'personal_settings') {
                // 个人设置为索引0
                window.parent.openTab(7, 0, null);
            } else if (subModule === 'department_settings') {
                // 部门设置在系统设置(索引2)下的子项(索引1)
                window.parent.openTab(7, 2, 1);
            }
        } else {
            alert('框架导航功能不可用');
        }
    }

    // 客户管理跳转函数
    function openCustomerTab(subModule, title) {
        if (window.parent.openTab) {
            // 客户管理模块索引为0
            if (subModule === 'customer') {
                // 客户为索引1
                window.parent.openTab(0, 1, null);
            } else if (subModule === 'agency') {
                // 代理机构为索引2
                window.parent.openTab(0, 2, null);
            }
        } else {
            alert('框架导航功能不可用');
        }
    }
</script>