<?php
// 主框架逻辑部分
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>鸿鼎知识产权系统 - 首页</title>
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <!-- 顶部导航栏 -->
    <div class="top-nav">
        <div class="logo-area">            
            <div class="logo-text">鸿鼎知识产权系统</div>
        </div>
        <div class="nav-menu">
            <div class="nav-item active">客户管理</div>
            <div class="nav-item">专利管理</div>
            <div class="nav-item">商标管理</div>
            <div class="nav-item">版权管理</div>
            <div class="nav-item">交义管理</div>
            <div class="nav-item">批量管理</div>
            <div class="nav-item">账款管理</div>
            <div class="nav-item">系统管理</div>
        </div>
        <div class="user-area">
            <div class="phone-number">18028146647</div>
            <div class="logout-btn">退出</div>
        </div>
    </div>

    <!-- 主体内容区域 -->
    <div class="main-container">
        <!-- 左侧边栏 -->
        <div class="sidebar">
            <ul class="sidebar-menu" id="sidebar-menu">
                <li class="menu-item" data-module="crm">
                    <i>👤</i> CRM
                    <span class="arrow">›</span>
                </li>
                <ul></ul>
                <li class="menu-item active" data-module="customer">
                    <i>👥</i> 客户
                    <span class="arrow">›</span>
                </li>
                <ul class="sub-menu" id="customer-submenu">
                    <li class="sub-menu-item">新增客户</li>
                    <li class="sub-menu-item">客户列表</li>
                    <li class="sub-menu-item">申请人列表</li>
                    <li class="sub-menu-item">发明人列表</li>
                    <li class="sub-menu-item">联系记录</li>
                </ul>
                <li class="menu-item" data-module="agency">
                    <i>🏢</i> 代理机构
                    <span class="arrow">›</span>
                </li>
                <ul class="sub-menu" id="agency-submenu">
                    <li class="sub-menu-item">新增代理机构</li>
                    <li class="sub-menu-item">代理机构列表</li>
                </ul>
                <li class="menu-item" data-module="contract">
                    <i>📝</i> 合同管理
                    <span class="arrow">›</span>
                </li>
                <ul class="sub-menu" id="contract-submenu">
                    <li class="sub-menu-item">新建合同</li>
                    <li class="sub-menu-item">草稿</li>
                    <li class="sub-menu-item">待处理</li>
                    <li class="sub-menu-item">已完成</li>
                    <li class="sub-menu-item">合同列表</li>
                </ul>
            </ul>
        </div>

        <!-- 右侧内容区域 -->
        <div class="content-area">
            <!-- 菜单栏 -->
            <div class="menu-bar">
                <a href="#" class="active">首页</a>
            </div>

            <!-- 主页面内容 -->
            <div class="homepage">
                <!-- 待办事项面板 -->
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">
                            <i>📋</i> 待办事项
                        </h3>
                    </div>
                    <div class="panel-content">
                        <div class="panel-empty">暂无待办事项</div>
                    </div>
                </div>

                <!-- 进行中的任务面板 -->
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">
                            <i>🔄</i> 进行中的任务
                        </h3>
                    </div>
                    <div class="panel-content">
                        <div class="task-cards">
                            <div class="task-card">
                                <div class="task-icon orange">1</div>
                                <div class="task-name">内部期限3天内</div>
                                <div class="task-count">(0)</div>
                            </div>
                            <div class="task-card">
                                <div class="task-icon orange-dark">1</div>
                                <div class="task-name">内部期限7天内</div>
                                <div class="task-count">(0)</div>
                            </div>
                            <div class="task-card">
                                <div class="task-icon red">3</div>
                                <div class="task-name">客户期限3天内</div>
                                <div class="task-count">(0)</div>
                            </div>
                            <div class="task-card">
                                <div class="task-icon purple">7</div>
                                <div class="task-name">客户期限7天内</div>
                                <div class="task-count">(0)</div>
                            </div>
                            <div class="task-card">
                                <div class="task-icon green">3</div>
                                <div class="task-name">官方期限3天内</div>
                                <div class="task-count">(0)</div>
                            </div>
                            <div class="task-card">
                                <div class="task-icon blue">7</div>
                                <div class="task-name">官方期限7天内</div>
                                <div class="task-count">(0)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 统计报表面板 -->
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">
                            <i>📊</i> 统计报表
                        </h3>
                    </div>
                    <div class="panel-content">
                        <div class="panel-empty">暂无相关报表</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 底部版权信息 -->
    <div class="footer">
        Copyright© 2025 广州市鸿鼎知识产权信息有限公司 | <a href="#">选文常用文档</a> | <a href="#">快速开始</a> | <a href="#">工单提交</a> | <a href="#">更新日志</a>
    </div>

    <!-- JavaScript 代码 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 左侧导航菜单交互
            const menuItems = document.querySelectorAll('.menu-item');

            menuItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    // 处理主菜单项的激活状态
                    menuItems.forEach(function(mi) {
                        mi.classList.remove('active');
                        mi.classList.remove('open');
                    });

                    // 隐藏所有子菜单
                    const subMenus = document.querySelectorAll('.sub-menu');
                    subMenus.forEach(function(submenu) {
                        submenu.style.display = 'none';
                    });

                    // 激活当前菜单项
                    this.classList.add('active');
                    this.classList.add('open');

                    // 显示对应的子菜单
                    const moduleId = this.getAttribute('data-module');
                    const subMenu = document.getElementById(moduleId + '-submenu');
                    if (subMenu) {
                        subMenu.style.display = 'block';
                    }
                });
            });

            // 子菜单项交互
            const subMenuItems = document.querySelectorAll('.sub-menu-item');
            subMenuItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    // 处理子菜单项的激活状态
                    subMenuItems.forEach(function(smi) {
                        smi.classList.remove('active');
                    });

                    // 激活当前子菜单项
                    this.classList.add('active');
                });
            });

            // 顶部导航菜单交互
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    // 处理导航项的激活状态
                    navItems.forEach(function(ni) {
                        ni.classList.remove('active');
                    });

                    // 激活当前导航项
                    this.classList.add('active');
                });
            });

            // 面板折叠功能
            const panelHeaders = document.querySelectorAll('.panel-header');
            panelHeaders.forEach(function(header) {
                header.addEventListener('click', function() {
                    const panel = this.parentElement;
                    const content = panel.querySelector('.panel-content');

                    if (content.style.display === 'none') {
                        content.style.display = 'block';
                    } else {
                        content.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>

</html>