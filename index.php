<?php
session_start();
// 主框架逻辑部分
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include 'database.php';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>鸿鼎知识产权系统</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/module.css">
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
            <div class="nav-item">发文管理</div>
            <div class="nav-item">批量管理</div>
            <div class="nav-item">账款管理</div>
            <div class="nav-item">系统管理</div>
        </div>
        <div class="user-area">
            <div class="user-name"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '未登录'; ?></div>
            <a class="logout-btn" href="logout.php">退出</a>
        </div>
    </div>

    <!-- 主体内容区域 -->
    <div class="main-container">
        <!-- 左侧边栏 -->
        <div class="sidebar">
            <ul class="sidebar-menu" id="sidebar-menu">
                <!-- 左侧菜单将由JS动态渲染 -->
            </ul>
        </div>

        <div class="main-content">
            <!-- 选项卡栏 -->
            <div class="tab-bar" id="tab-bar">
                <!-- 标签页由JS动态渲染 -->
            </div>
            <!-- 右侧内容区域 -->
            <div class="content-area">
                <!-- 动态内容区，后续通过切换加载不同模块功能页面 -->
            </div>
        </div>
    </div>

    <!-- 底部版权信息 -->
    <div class="footer">
        Copyright© 2025 广州市鸿鼎知识产权信息有限公司 V<?php echo $version; ?>
        <!-- | <a href="#">选文常用文档</a> | <a href="#">快速开始</a> | <a href="#">工单提交</a> | <a href="#">更新日志</a> -->
    </div>

    <!-- JavaScript 代码 -->
    <script>
        // 所有模块和功能结构
        const modules = [{
                name: '客户管理',
                key: 'customer',
                menus: [{
                        title: 'CRM',
                        icon: '👤',
                        sub: [
                            '线索', '线索池', '客户', '合同', '客户公海', '跟进记录'
                        ]
                    },
                    {
                        title: '客户',
                        icon: '👥',
                        sub: [
                            '新增客户', '客户列表', '申请人列表', '发明人列表', '联系记录'
                        ]
                    },
                    {
                        title: '代理机构',
                        icon: '🏢',
                        sub: [
                            '新增代理机构', '代理机构列表'
                        ]
                    },
                    {
                        title: '合同管理',
                        icon: '📝',
                        sub: [
                            '新建合同', '草稿', '待处理', '已完成', '合同列表'
                        ]
                    },
                    {
                        title: '合同编辑',
                        icon: '✏️',
                        sub: [],
                        hidden: true
                    }
                ]
            },
            {
                name: '专利管理',
                key: 'patent',
                menus: [{
                        title: '新增专利',
                        icon: '📄',
                        sub: []
                    },
                    {
                        title: '个人案件',
                        icon: '👤',
                        sub: ['进行中', '已完成', '己逾期', '我的关注', '部门案件', '专利查询']
                    },
                    {
                        title: '配案管理',
                        icon: '📦',
                        sub: ['待配案', '已配案']
                    },
                    {
                        title: '核稿管理',
                        icon: '📝',
                        sub: ['草稿', '待我核稿', '审核中', '已完成', '导出核稿包']
                    },
                    {
                        title: '递交管理',
                        icon: '📬',
                        sub: ['待处理', '审核中', '已完成']
                    },
                    {
                        title: '案件管理',
                        icon: '📁',
                        sub: ['专利查询', '期限监控', '流程监控', '专利来文', '文件管理']
                    },
                    {
                        title: '专利编辑',
                        icon: '✏️',
                        sub: [],
                        hidden: true
                    }
                ]
            },
            {
                name: '商标管理',
                key: 'trademark',
                menus: [{
                        title: '新增商标',
                        icon: '🆕',
                        sub: []
                    },
                    {
                        title: '个人案件',
                        icon: '👤',
                        sub: ['进行中', '已完成', '已逾期', '我的关注', '部门案件', '查询']
                    },
                    {
                        title: '递交管理',
                        icon: '📬',
                        sub: ['待处理', '审核中', '已完成']
                    },
                    {
                        title: '案件管理',
                        icon: '📁',
                        sub: ['商标查询', '商标来文', '流程监控', '文件管理', '期限监控']
                    },
                    {
                        title: '商标编辑',
                        icon: '✏️',
                        sub: [],
                        hidden: true
                    }
                ]
            },
            {
                name: '版权管理',
                key: 'copyright',
                menus: [{
                        title: '新增版权',
                        icon: '🆕',
                        sub: []
                    },
                    {
                        title: '案件管理',
                        icon: '📁',
                        sub: ['版权查询', '文件管理']
                    },
                    {
                        title: '版权编辑',
                        icon: '✏️',
                        sub: [],
                        hidden: true
                    }
                ]
            },
            {
                name: '发文管理',
                key: 'document',
                menus: [{
                        title: '发文管理',
                        icon: '📤',
                        sub: ['新建', '草稿', '待处理', '发文列表']
                    },
                    {
                        title: '邮箱管理',
                        icon: '📧',
                        sub: ['邮件分析']
                    }
                ]
            },
            {
                name: '批量管理',
                key: 'batch',
                menus: [{
                    title: '批处理',
                    icon: '🔄',
                    sub: ['案件更新', '处理事项更新', '处理事项完成', '处理事项添加', '导入案件']
                }]
            },
            {
                name: '账款管理',
                key: 'finance',
                menus: [{
                        title: '费用管理',
                        icon: '💰',
                        sub: ['费用查询', '费用通知']
                    },
                    {
                        title: '请款管理',
                        icon: '📝',
                        sub: ['待请款客户', '草稿', '待处理', '请款单查询']
                    },
                    {
                        title: '账单管理',
                        icon: '📄',
                        sub: ['新增账单（收款）', '新增账单（销账）', '草稿', '待处理', '账单查询']
                    },
                    {
                        title: '缴费管理',
                        icon: '💳',
                        sub: ['新建缴费单', '草稿', '待处理', '缴费单查询', '取票码']
                    }
                ]
            },
            {
                name: '系统管理',
                key: 'system',
                menus: [{
                        title: '个人设置',
                        icon: '👤',
                        sub: ['基本信息', '修改密码', '邮件设置']
                    },
                    {
                        title: '规则设置',
                        icon: '⚙️',
                        sub: ['处理事项规则', '通知书规则', '发文规则', '编号规则', '代理费规则', '第三方费规则', '邮件标签规则']
                    },
                    {
                        title: '系统设置',
                        icon: '🛠️',
                        sub: ['本所信息', '部门设置', '流程设置', '人员设置', '角色设置', '流程邮件设置']
                    },
                    {
                        title: '基础数据',
                        icon: '📊',
                        sub: ['业务类型', '案件状态', '处理事项', '处理状态', '文件描述', '邮件标签', '费用类型', '客户状态', 'CRM基础数据']
                    }
                ]
            }
        ];

        // 当前选中的模块索引
        let currentModuleIndex = 0;

        // 选项卡数据结构
        let tabs = [{
            id: 'home',
            moduleIndex: null,
            menuIndex: null,
            subIndex: null,
            title: '首页',
            fixed: true
        }];
        let activeTabId = 'home';
        const MAX_TABS = 15;

        // 生成功能页面路径
        function getPagePath(moduleIndex, menuIndex, subIndex = null) {
            // 顶层模块英文目录
            const moduleDirs = [
                'customer_management', 'patent_management', 'trademark_management', 'copyright_management',
                'document_management', 'batch_management', 'finance_management', 'system_management'
            ];
            // 各模块下一级菜单英文目录
            const menuDirs = [
                // 客户管理
                ['crm', 'customer', 'agency', 'contract_management', 'edit_contract'],
                // 专利管理
                ['add_patent', 'personal_cases', 'case_assignment', 'review_management', 'submission_management', 'case_management', 'edit_patent'],
                // 商标管理
                ['add_trademark', 'personal_cases', 'submission_management', 'case_management', 'edit_trademark'],
                // 版权管理
                ['add_copyright', 'case_management', 'edit_copyright'],
                // 发文管理
                ['outgoing_documents', 'email_management'],
                // 批量管理
                ['batch_processing'],
                // 账款管理
                ['fee_management', 'payment_request', 'billing_management', 'payment_management'],
                // 系统管理
                ['personal_settings', 'rule_settings', 'system_settings', 'basic_data']
            ];
            // 各一级菜单下功能文件英文名（不含.php）
            const fileNames = [
                // 客户管理
                [
                    ['leads', 'leads_pool', 'customers', 'contracts', 'customer_pool', 'follow_up_records'],
                    ['add_customer', 'customer_list', 'applicant_list', 'inventor_list', 'contact_records'],
                    ['add_agency', 'agency_list'],
                    ['create_contract', 'draft', 'pending', 'completed', 'contract_list'],
                    ['edit_contract']
                ],
                // 专利管理
                [
                    ['add_patent'],
                    ['in_progress', 'completed', 'overdue', 'my_focus', 'department_cases', 'patent_search'],
                    ['pending_assignment', 'assigned'],
                    ['draft', 'pending_review', 'under_review', 'completed', 'export_review_package'],
                    ['pending', 'under_review', 'completed'],
                    ['patent_search', 'deadline_monitoring', 'process_monitoring', 'patent_incoming', 'file_management'],
                    ['edit_patent']
                ],
                // 商标管理
                [
                    ['add_trademark'],
                    ['in_progress', 'completed', 'overdue', 'my_focus', 'department_cases', 'search'],
                    ['pending', 'under_review', 'completed'],
                    ['trademark_search', 'trademark_incoming', 'process_monitoring', 'file_management', 'deadline_monitoring'],
                    ['edit_trademark']
                ],
                // 版权管理
                [
                    ['add_copyright'],
                    ['copyright_search', 'file_management'],
                    ['edit_copyright']
                ],
                // 发文管理
                [
                    ['create_new', 'draft', 'pending', 'document_list'],
                    ['email_analysis']
                ],
                // 批量管理
                [
                    ['case_update', 'task_update', 'task_completion', 'task_addition', 'import_cases']
                ],
                // 账款管理
                [
                    ['fee_query', 'fee_notification'],
                    ['pending_request_customers', 'draft', 'pending', 'request_query'],
                    ['add_bill_collection', 'add_bill_writeoff', 'draft', 'pending', 'bill_query'],
                    ['create_payment', 'draft', 'pending', 'payment_query', 'ticket_code']
                ],
                // 系统管理
                [
                    ['basic_info', 'change_password', 'email_settings'],
                    ['task_rules', 'notification_rules', 'document_rules', 'numbering_rules', 'agency_fee_rules', 'third_party_fee_rules', 'email_tag_rules'],
                    ['firm_info', 'department_settings', 'process_settings', 'personnel_settings', 'role_settings', 'process_email_settings'],
                    ['business_type', 'case_status', 'task_items', 'process_status', 'file_description', 'email_tags', 'fee_types', 'customer_status', 'crm_basic_data']
                ]
            ];
            const moduleDir = moduleDirs[moduleIndex];
            const menuDir = menuDirs[moduleIndex][menuIndex];
            // 一级菜单无二级菜单，且README.md要求直接在模块目录下的特殊情况
            // 专利管理-新增专利、专利管理-专利编辑、商标管理-新增商标、版权管理-新增版权、客户管理-合同编辑
            if (subIndex === null) {
                // 这些一级菜单直接在模块目录下
                if (
                    (moduleDir === 'patent_management' && (menuDir === 'add_patent' || menuDir === 'edit_patent')) ||
                    (moduleDir === 'trademark_management' && (menuDir === 'add_trademark' || menuDir === 'edit_trademark')) ||
                    (moduleDir === 'copyright_management' && (menuDir === 'add_copyright' || menuDir === 'edit_copyright')) ||
                    (moduleDir === 'customer_management' && menuDir === 'edit_contract')
                ) {
                    return `modules/${moduleDir}/${fileNames[moduleIndex][menuIndex][0]}.php`;
                }
                // 其他情况仍为modules/模块/一级菜单/功能.php
                if (fileNames[moduleIndex][menuIndex].length === 1) {
                    return `modules/${moduleDir}/${menuDir}/${fileNames[moduleIndex][menuIndex][0]}.php`;
                }
                if (fileNames[moduleIndex][menuIndex].length > 1) {
                    // 默认加载第一个
                    return `modules/${moduleDir}/${menuDir}/${fileNames[moduleIndex][menuIndex][0]}.php`;
                }
            } else {
                // 有二级菜单
                return `modules/${moduleDir}/${menuDir}/${fileNames[moduleIndex][menuIndex][subIndex]}.php`;
            }
            return '';
        }

        // 加载功能页面到内容区
        function loadPage(moduleIndex, menuIndex, subIndex = null) {
            const path = getPagePath(moduleIndex, menuIndex, subIndex);
            const contentArea = document.querySelector('.content-area');
            contentArea.innerHTML = '<div style="padding:40px;text-align:center;color:#888;">正在加载...</div>';
            const xhr = new XMLHttpRequest();
            xhr.open('GET', path, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        // 自动执行<script>标签内容
                        insertHtmlWithScripts(contentArea, xhr.responseText);
                    } else {
                        contentArea.innerHTML = '<div style="padding:40px;text-align:center;color:#f00;">页面加载失败：' + path + '</div>';
                    }
                }
            };
            xhr.send();
        }

        // 加载首页内容
        function loadHomePage() {
            const contentArea = document.querySelector('.content-area');
            contentArea.innerHTML = '<div style="padding:40px;text-align:center;color:#888;">正在加载...</div>';
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'home.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        insertHtmlWithScripts(contentArea, xhr.responseText);
                        // 修正：加载完后主动初始化首页折叠功能
                        if (typeof window.initHomeCollapse === 'function') {
                            window.initHomeCollapse();
                        }
                    } else {
                        contentArea.innerHTML = '<div style="padding:40px;text-align:center;color:#f00;">首页加载失败</div>';
                    }
                }
            };
            xhr.send();
        }

        // 工具函数：插入HTML并自动执行其中的<script>标签
        function insertHtmlWithScripts(container, html) {
            container.innerHTML = html;
            // 提取并执行所有<script>
            const scripts = Array.from(container.querySelectorAll('script'));
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                if (script.src) {
                    newScript.src = script.src;
                } else {
                    newScript.text = script.textContent;
                }
                document.body.appendChild(newScript);
                // 可选：移除原有script标签，避免重复
                script.parentNode.removeChild(script);
            });
        }

        // 生成tab唯一id
        function getTabId(moduleIndex, menuIndex, subIndex) {
            return `${moduleIndex}_${menuIndex}_${subIndex === null ? 'n' : subIndex}`;
        }

        // 获取tab显示名
        function getTabTitle(moduleIndex, menuIndex, subIndex) {
            const menu = modules[moduleIndex].menus[menuIndex];
            if (subIndex === null || !menu.sub || menu.sub.length === 0) {
                return menu.title;
            } else {
                return menu.sub[subIndex];
            }
        }

        // 新增或激活tab
        function openTab(moduleIndex, menuIndex, subIndex) {
            const tabId = getTabId(moduleIndex, menuIndex, subIndex);
            if (tabId === 'home') {
                setActiveTab('home');
                return;
            }
            const exist = tabs.find(tab => tab.id === tabId);
            if (exist) {
                setActiveTab(tabId);
                return;
            }
            if (tabs.length >= MAX_TABS + 1) { // +1是首页
                alert('当前选项卡过多，请关闭不用的选项卡');
                return;
            }
            const tab = {
                id: tabId,
                moduleIndex,
                menuIndex,
                subIndex,
                title: getTabTitle(moduleIndex, menuIndex, subIndex),
                fixed: false
            };
            tabs.push(tab);
            setActiveTab(tabId);
            renderTabs();
        }

        // 激活tab
        function setActiveTab(tabId) {
            activeTabId = tabId;
            renderTabs();
            if (tabId === 'home') {
                loadHomePage();
            } else {
                const tab = tabs.find(t => t.id === tabId);
                if (tab) {
                    loadPage(tab.moduleIndex, tab.menuIndex, tab.subIndex);
                }
            }
        }

        // 关闭tab
        function closeTab(tabId) {
            if (tabId === 'home') return;
            const idx = tabs.findIndex(t => t.id === tabId);
            if (idx === -1) return;
            const wasActive = (tabs[idx].id === activeTabId);
            tabs.splice(idx, 1);
            if (wasActive) {
                if (tabs.length > 0) {
                    const newIdx = idx > 0 ? idx - 1 : 0;
                    setActiveTab(tabs[newIdx].id);
                } else {
                    setActiveTab('home');
                }
            } else {
                renderTabs();
            }
        }

        // 一键关闭全部tab
        function closeAllTabs() {
            tabs = tabs.filter(tab => tab.id === 'home');
            setActiveTab('home');
        }

        // 渲染tab栏
        function renderTabs() {
            const tabBar = document.getElementById('tab-bar');
            tabBar.innerHTML = '';
            tabs.forEach(tab => {
                const tabDiv = document.createElement('div');
                tabDiv.className = 'tab-item' + (tab.id === activeTabId ? ' active' : '');
                tabDiv.textContent = tab.title;
                tabDiv.title = tab.title;
                tabDiv.addEventListener('click', function(e) {
                    if (e.target.classList.contains('tab-close')) return;
                    setActiveTab(tab.id);
                });
                // 关闭按钮
                if (!tab.fixed) {
                    const closeBtn = document.createElement('span');
                    closeBtn.className = 'tab-close';
                    closeBtn.innerHTML = '&times;';
                    closeBtn.title = '关闭';
                    closeBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        closeTab(tab.id);
                    });
                    tabDiv.appendChild(closeBtn);
                }
                tabBar.appendChild(tabDiv);
            });
            // 一键关闭按钮
            if (tabs.length > 1) {
                const closeAllBtn = document.createElement('button');
                closeAllBtn.className = 'tab-close-all';
                closeAllBtn.textContent = '关闭全部';
                closeAllBtn.title = '关闭全部选项卡';
                closeAllBtn.addEventListener('click', closeAllTabs);
                tabBar.appendChild(closeAllBtn);
            }
        }

        // 修改左侧菜单点击逻辑，打开tab
        function renderSidebarMenus(moduleIndex) {
            const sidebar = document.getElementById('sidebar-menu');
            sidebar.innerHTML = '';
            const menus = modules[moduleIndex].menus;
            menus.forEach((menu, idx) => {
                if (menu.hidden) return; // 跳过隐藏菜单项
                // 一级菜单
                const li = document.createElement('li');
                li.className = 'menu-item';
                li.setAttribute('data-menu-index', idx);
                li.innerHTML = `<i>${menu.icon}</i> ${menu.title} <span class=\"arrow\">›</span>`;
                // 一级菜单无二级菜单，直接加载
                if (!menu.sub || menu.sub.length === 0) {
                    li.addEventListener('click', function(e) {
                        e.stopPropagation();
                        // 清除所有高亮
                        sidebar.querySelectorAll('.menu-item').forEach(mi => mi.classList.remove('active'));
                        sidebar.querySelectorAll('.sub-menu-item').forEach(si => si.classList.remove('active'));
                        this.classList.add('active');
                        openTab(moduleIndex, idx, null);
                    });
                }
                sidebar.appendChild(li);
                // 每个一级菜单后都append一个ul
                const ul = document.createElement('ul');
                ul.className = 'sub-menu';
                ul.style.display = 'none';
                if (menu.sub && menu.sub.length > 0) {
                    menu.sub.forEach((sub, subIdx) => {
                        const subLi = document.createElement('li');
                        subLi.className = 'sub-menu-item';
                        subLi.textContent = sub;
                        subLi.addEventListener('click', function(e) {
                            e.stopPropagation();
                            // 清除所有高亮
                            sidebar.querySelectorAll('.menu-item').forEach(mi => mi.classList.remove('active'));
                            sidebar.querySelectorAll('.sub-menu-item').forEach(si => si.classList.remove('active'));
                            subLi.classList.add('active');
                            li.classList.add('active');
                            openTab(moduleIndex, idx, subIdx);
                        });
                        ul.appendChild(subLi);
                    });
                }
                sidebar.appendChild(ul);
            });
        }

        // 初始化顶部导航栏点击事件
        function initTopNav() {
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach((item, idx) => {
                item.addEventListener('click', function() {
                    navItems.forEach(ni => ni.classList.remove('active'));
                    this.classList.add('active');
                    currentModuleIndex = idx;
                    renderSidebarMenus(currentModuleIndex);
                    // 新增：如果点击的是"专利管理"，默认进入专利查询页面
                    if (modules[idx].name === '专利管理') {
                        // 专利管理-案件管理-专利查询
                        // 案件管理在专利管理下的第5个菜单（索引5），专利查询在案件管理下的第0个子菜单
                        openTab(idx, 5, 0);
                    }
                    // 新增：如果点击的是"商标管理"，默认进入商标查询页面
                    if (modules[idx].name === '商标管理') {
                        // 商标管理-案件管理-商标查询
                        // 案件管理在商标管理下的第3个菜单（索引3），商标查询在案件管理下的第0个子菜单
                        openTab(idx, 3, 0);
                    }
                    // 新增：如果点击的是"版权管理"，默认进入版权查询页面
                    if (modules[idx].name === '版权管理') {
                        // 版权管理-案件管理-版权查询
                        // 案件管理在版权管理下的第1个菜单（索引1），版权查询在案件管理下的第0个子菜单
                        openTab(idx, 1, 0);
                    }
                });
            });
        }

        // 初始化左侧菜单展开/收起逻辑
        function initSidebarToggle() {
            const sidebar = document.getElementById('sidebar-menu');
            sidebar.addEventListener('click', function(e) {
                const target = e.target.closest('.menu-item');
                if (target) {
                    // 展开/收起对应的二级菜单
                    const menuIndex = target.getAttribute('data-menu-index');
                    const allMenus = sidebar.querySelectorAll('.menu-item');
                    const allSubMenus = sidebar.querySelectorAll('.sub-menu');
                    allMenus.forEach((mi, idx) => {
                        if (idx == menuIndex) {
                            mi.classList.toggle('open');
                            if (allSubMenus[idx]) {
                                allSubMenus[idx].style.display = allSubMenus[idx].style.display === 'block' ? 'none' : 'block';
                            }
                        } else {
                            mi.classList.remove('open');
                            if (allSubMenus[idx]) allSubMenus[idx].style.display = 'none';
                        }
                    });
                }
            });
        }

        // 首页内容块折叠/展开功能
        function initHomeCollapse() {
            var headers = document.querySelectorAll('.collapsible-header');
            headers.forEach(function(header) {
                header.onclick = function() {
                    var targetId = header.getAttribute('data-target');
                    var content = document.getElementById(targetId);
                    if (content.classList.contains('collapsed')) {
                        content.classList.remove('collapsed');
                        header.classList.remove('collapsed');
                    } else {
                        content.classList.add('collapsed');
                        header.classList.add('collapsed');
                    }
                };
            });
        }

        // 初始化
        document.addEventListener('DOMContentLoaded', function() {
            renderSidebarMenus(currentModuleIndex);
            initTopNav();
            initSidebarToggle();
            renderTabs();
            setActiveTab('home');
        });
    </script>
</body>

</html>