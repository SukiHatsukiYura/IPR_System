开发规范：
1. 所有功能文件必须放在明确的模块/子模块目录下，禁止跨目录引用。
2. 页面内引用其他文件（如AJAX、跳转）时，优先使用以项目根目录为基准的绝对路径，如modules/customer_management/customer/add_customer.php。
3. 同一目录下的文件可用相对路径（如customer_list.php），但要确保当前目录环境一致。
4. 所有表单和数据交互必须使用AJAX提交，禁止form原生提交。
5. AJAX请求的URL必须用绝对路径，避免因页面加载方式不同导致404。
6. AJAX响应必须为JSON格式，前端要有异常处理（如解析失败、404等）。
7. 编辑、详情等功能优先通过URL参数传递ID等关键参数。如果框架的openTab等方法无法传递参数，可用会话变量（session）兜底，用后要及时unset，避免脏数据。
8. 所有参数名要与后端代码、SQL语句保持一致。
9. 所有功能页面都**只能通过框架方法跳转**（如window.parent.openTab），**禁止直接访问和直接window.location.href跳转**，否则一律视为不合规。
10. 跳转目标文件必须真实存在且路径正确。
11. 表单提交、保存、删除等操作必须用AJAX，禁止页面刷新。所有表单字段、按钮、标题等要根据"新增/编辑"模式动态切换，避免硬编码。
12. 所有SQL语句的参数必须与数据数组一一对应，多一项或少一项都会报错。编辑（UPDATE）时只传递SQL用到的字段，避免多余参数。
13. 所有输入数据都要做安全过滤（如htmlspecialchars、intval等）。
14. 所有功能页面必须检测自身访问方式，若检测到直接访问应自动跳转到框架首页或给出友好提示，禁止裸页面展示。
15. 开发调试阶段应加console.log等浏览器控制台输出，便于快速定位问题。生产环境要关闭详细调试输出，防止信息泄露。
16. 所有功能文件结构、命名、跳转、AJAX、参数处理等要统一，便于维护和扩展。新增/编辑合并时，所有表单字段、标题、按钮等都要根据模式动态切换。

---

相关规制
1. 这是一个复刻的网站系统，复刻的是https://yun2.wadeinfo.com/index.aspx。
2. 要求尽可能的还原原网站的样式和功能
3. 使用php7.1+mysql8.0。
4. 表单提交使用ajax提交，不要使用form表单提交。

系统标题：鸿鼎知识产权系统

模块功能：
<!-- 模块名称 -->
一、客户管理
<!-- 模块功能名称 -->
  1. CRM
    <!-- 功能文件 -->
    线索
    线索池
    客户
    合同
    客户公海
    跟进记录
  2. 客户
    新增客户
    客户列表
    申请人列表
    发明人列表
    联系记录
  3. 代理机构
    新增代理机构
    代理机构列表
  4. 合同管理
    新建合同
    草稿
    待处理
    已完成
    合同列表

二、专利管理
  1. 新增专利
  2. 个人案件
    进行中
    已完成
    己逾期
    我的关注
    部门案件
    专利查询
  3. 配案管理
    待配案
    已配案
  4. 核稿管理
    草稿
    待我核稿
    审核中
    已完成
    导出核稿包
  5. 递交管理
    待处理
    审核中
    已完成
  6. 案件管理
    专利查询（主页面，点击"专利管理"默认进入）
    期限监控
    流程监控
    专利来文
    文件管理

三、商标管理
  1. 新增商标
  2. 个人案件
    进行中
    已完成
    已逾期
    我的关注
    部门案件
    查询
  3. 递交管理
    待处理
    审核中
    已完成
  4. 案件管理
    商标查询
    商标来文
    流程监控
    文件管理
    期限监控

四、版权管理
  1. 新增版权
  2. 案件管理
    版权查询
    文件管理

五、发文管理
  1. 发文管理
    新建
    草稿
    待处理
    发文列表
  2. 邮箱管理
    邮件分析

六、批量管理
  1. 批处理
    案件更新
    处理事项更新
    处理事项完成
    处理事项添加
    导入案件

七、账款管理
  1. 费用管理
    费用查询
    费用通知
  2. 请款管理
    待请款客户
    草稿
    待处理
    请款单查询
  3. 账单管理
    新增账单（收款）
    新增账单（销账）
    草稿
    待处理
    账单查询
  4. 缴费管理
    新建缴费单
    草稿
    待处理
    缴费单查询
    取票码
    
八、系统管理
  1. 个人设置
    基本信息
    修改密码
    邮件设置
  2. 规则设置
    处理事项规则
    通知书规则
    发文规则
    编号规则
    代理费规则
    第三方费规则
    邮件标签规则
  3. 系统设置
    本所信息
    部门设置
    流程设置
    人员设置
    角色设置
    流程邮件设置
  4. 基础数据
    业务类型
    案件状态
    处理事项
    处理状态
    文件描述
    邮件标签
    费用类型
    客户状态
    CRM基础数据

模块相关的文件结构

modules/
├── customer_management/                // 客户管理
│   ├── crm/                           // CRM模块
│   │   ├── leads.php                  // 线索
│   │   ├── leads_pool.php             // 线索池
│   │   ├── customers.php              // 客户
│   │   ├── contracts.php              // 合同
│   │   ├── customer_pool.php          // 客户公海
│   │   └── follow_up_records.php      // 跟进记录
│   ├── customer/                      // 客户
│   │   ├── add_customer.php           // 新增客户
│   │   ├── customer_list.php          // 客户列表
│   │   ├── applicant_list.php         // 申请人列表
│   │   ├── inventor_list.php          // 发明人列表
│   │   └── contact_records.php        // 联系记录
│   ├── agency/                        // 代理机构
│   │   ├── add_agency.php             // 新增代理机构
│   │   └── agency_list.php            // 代理机构列表
│   └── contract_management/           // 合同管理
│       ├── create_contract.php        // 新建合同
│       ├── draft.php                  // 草稿
│       ├── pending.php                // 待处理
│       ├── completed.php              // 已完成
│       └── contract_list.php          // 合同列表
├── patent_management/                  // 专利管理
│   ├── add_patent.php                 // 新增专利
│   ├── personal_cases/                // 个人案件
│   │   ├── in_progress.php            // 进行中
│   │   ├── completed.php              // 已完成
│   │   ├── overdue.php                // 己逾期
│   │   ├── my_focus.php               // 我的关注
│   │   ├── department_cases.php       // 部门案件
│   │   └── patent_search.php          // 专利查询
│   ├── case_assignment/               // 配案管理
│   │   ├── pending_assignment.php     // 待配案
│   │   └── assigned.php               // 已配案
│   ├── review_management/             // 核稿管理
│   │   ├── draft.php                  // 草稿
│   │   ├── pending_review.php         // 待我核稿
│   │   ├── under_review.php           // 审核中
│   │   ├── completed.php              // 已完成
│   │   └── export_review_package.php  // 导出核稿包
│   ├── submission_management/         // 递交管理
│   │   ├── pending.php                // 待处理
│   │   ├── under_review.php           // 审核中
│   │   └── completed.php              // 已完成
│   └── case_management/               // 案件管理
│       ├── patent_search.php          // 专利查询
│       ├── deadline_monitoring.php    // 期限监控
│       ├── process_monitoring.php     // 流程监控
│       ├── patent_incoming.php        // 专利来文
│       └── file_management.php        // 文件管理
├── trademark_management/               // 商标管理
│   ├── add_trademark.php              // 新增商标
│   ├── personal_cases/                // 个人案件
│   │   ├── in_progress.php            // 进行中
│   │   ├── completed.php              // 已完成
│   │   ├── overdue.php                // 已逾期
│   │   ├── my_focus.php               // 我的关注
│   │   ├── department_cases.php       // 部门案件
│   │   └── search.php                 // 查询
│   ├── submission_management/         // 递交管理
│   │   ├── pending.php                // 待处理
│   │   ├── under_review.php           // 审核中
│   │   └── completed.php              // 已完成
│   └── case_management/               // 案件管理
│       ├── trademark_search.php       // 商标查询
│       ├── trademark_incoming.php     // 商标来文
│       ├── process_monitoring.php     // 流程监控
│       ├── file_management.php        // 文件管理
│       └── deadline_monitoring.php    // 期限监控
├── copyright_management/               // 版权管理
│   ├── add_copyright.php              // 新增版权
│   └── case_management/               // 案件管理
│       ├── copyright_search.php       // 版权查询
│       └── file_management.php        // 文件管理
├── document_management/                // 发文管理
│   ├── outgoing_documents/            // 发文管理
│   │   ├── create_new.php             // 新建
│   │   ├── draft.php                  // 草稿
│   │   ├── pending.php                // 待处理
│   │   └── document_list.php          // 发文列表
│   └── email_management/              // 邮箱管理
│       └── email_analysis.php         // 邮件分析
├── batch_management/                   // 批量管理
│   └── batch_processing/              // 批处理
│       ├── case_update.php            // 案件更新
│       ├── task_update.php            // 处理事项更新
│       ├── task_completion.php        // 处理事项完成
│       ├── task_addition.php          // 处理事项添加
│       └── import_cases.php           // 导入案件
├── finance_management/                 // 账款管理
│   ├── fee_management/                // 费用管理
│   │   ├── fee_query.php              // 费用查询
│   │   └── fee_notification.php       // 费用通知
│   ├── payment_request/               // 请款管理
│   │   ├── pending_request_customers.php // 待请款客户
│   │   ├── draft.php                  // 草稿
│   │   ├── pending.php                // 待处理
│   │   └── request_query.php          // 请款单查询
│   ├── billing_management/            // 账单管理
│   │   ├── add_bill_collection.php    // 新增账单（收款）
│   │   ├── add_bill_writeoff.php      // 新增账单（销账）
│   │   ├── draft.php                  // 草稿
│   │   ├── pending.php                // 待处理
│   │   └── bill_query.php             // 账单查询
│   └── payment_management/            // 缴费管理
│       ├── create_payment.php         // 新建缴费单
│       ├── draft.php                  // 草稿
│       ├── pending.php                // 待处理
│       ├── payment_query.php          // 缴费单查询
│       └── ticket_code.php            // 取票码
└── system_management/                  // 系统管理
    ├── personal_settings/             // 个人设置
    │   ├── basic_info.php             // 基本信息
    │   ├── change_password.php        // 修改密码
    │   └── email_settings.php         // 邮件设置
    ├── rule_settings/                 // 规则设置
    │   ├── task_rules.php             // 处理事项规则
    │   ├── notification_rules.php     // 通知书规则
    │   ├── document_rules.php         // 发文规则
    │   ├── numbering_rules.php        // 编号规则
    │   ├── agency_fee_rules.php       // 代理费规则
    │   ├── third_party_fee_rules.php  // 第三方费规则
    │   └── email_tag_rules.php        // 邮件标签规则
    ├── system_settings/               // 系统设置
    │   ├── firm_info.php              // 本所信息
    │   ├── department_settings.php    // 部门设置
    │   ├── process_settings.php       // 流程设置
    │   ├── personnel_settings.php     // 人员设置
    │   ├── role_settings.php          // 角色设置
    │   └── process_email_settings.php // 流程邮件设置
    └── basic_data/                    // 基础数据
        ├── business_type.php          // 业务类型
        ├── case_status.php            // 案件状态
        ├── task_items.php             // 处理事项
        ├── process_status.php         // 处理状态
        ├── file_description.php       // 文件描述
        ├── email_tags.php             // 邮件标签
        ├── fee_types.php              // 费用类型
        ├── customer_status.php        // 客户状态
        └── crm_basic_data.php         // CRM基础数据                
