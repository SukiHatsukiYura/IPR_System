# 鸿鼎知识产权系统 - 导航索引对照表

## 模块索引说明

- 格式：(模块索引, 菜单索引, 子菜单索引)
- 路径：modules/模块目录/菜单目录/文件名.php

## 0 - 客户管理 (customer_management)

### CRM (菜单索引 0)

- 线索: (0, 0, 0) => modules/customer_management/crm/leads.php
- 线索池: (0, 0, 1) => modules/customer_management/crm/leads_pool.php
- 客户: (0, 0, 2) => modules/customer_management/crm/customers.php
- 合同: (0, 0, 3) => modules/customer_management/crm/contracts.php
- 客户公海: (0, 0, 4) => modules/customer_management/crm/customer_pool.php
- 跟进记录: (0, 0, 5) => modules/customer_management/crm/follow_up_records.php

### 客户 (菜单索引 1)

- 新增客户: (0, 1, 0) => modules/customer_management/customer/add_customer.php
- 客户列表: (0, 1, 1) => modules/customer_management/customer/customer_list.php
- 申请人列表: (0, 1, 2) => modules/customer_management/customer/applicant_list.php
- 发明人列表: (0, 1, 3) => modules/customer_management/customer/inventor_list.php
- 联系记录: (0, 1, 4) => modules/customer_management/customer/contact_records.php

### 代理机构 (菜单索引 2)

- 新增代理机构: (0, 2, 0) => modules/customer_management/agency/add_agency.php
- 代理机构列表: (0, 2, 1) => modules/customer_management/agency/agency_list.php

### 合同管理 (菜单索引 3)

- 新建合同: (0, 3, 0) => modules/customer_management/contract_management/create_contract.php
- 草稿: (0, 3, 1) => modules/customer_management/contract_management/draft.php
- 待处理: (0, 3, 2) => modules/customer_management/contract_management/pending.php
- 已完成: (0, 3, 3) => modules/customer_management/contract_management/completed.php
- 合同列表: (0, 3, 4) => modules/customer_management/contract_management/contract_list.php

### 合同编辑 (菜单索引 4 - 隐藏)

- 合同编辑: (0, 4, null) => modules/customer_management/edit_contract.php

---

## 1 - 专利管理 (patent_management)

### 新增专利 (菜单索引 0)

- 新增专利: (1, 0, null) => modules/patent_management/add_patent.php

### 个人案件 (菜单索引 1)

- 进行中: (1, 1, 0) => modules/patent_management/personal_cases/in_progress.php
- 已完成: (1, 1, 1) => modules/patent_management/personal_cases/completed.php
- 已逾期: (1, 1, 2) => modules/patent_management/personal_cases/overdue.php
- 我的关注: (1, 1, 3) => modules/patent_management/personal_cases/my_focus.php
- 部门案件: (1, 1, 4) => modules/patent_management/personal_cases/department_cases.php
- 专利查询: (1, 1, 5) => modules/patent_management/personal_cases/patent_search.php

### 配案管理 (菜单索引 2)

- 待配案: (1, 2, 0) => modules/patent_management/case_assignment/pending_assignment.php
- 已配案: (1, 2, 1) => modules/patent_management/case_assignment/assigned.php

### 核稿管理 (菜单索引 3)

- 草稿: (1, 3, 0) => modules/patent_management/review_management/draft.php
- 待我核稿: (1, 3, 1) => modules/patent_management/review_management/pending_review.php
- 审核中: (1, 3, 2) => modules/patent_management/review_management/under_review.php
- 已完成: (1, 3, 3) => modules/patent_management/review_management/completed.php
- 导出核稿包: (1, 3, 4) => modules/patent_management/review_management/export_review_package.php

### 递交管理 (菜单索引 4)

- 待处理: (1, 4, 0) => modules/patent_management/submission_management/pending.php
- 审核中: (1, 4, 1) => modules/patent_management/submission_management/under_review.php
- 已完成: (1, 4, 2) => modules/patent_management/submission_management/completed.php

### 案件管理 (菜单索引 5)

- 专利查询: (1, 5, 0) => modules/patent_management/case_management/patent_search.php
- 期限监控: (1, 5, 1) => modules/patent_management/case_management/deadline_monitoring.php
- 流程监控: (1, 5, 2) => modules/patent_management/case_management/process_monitoring.php
- 专利来文: (1, 5, 3) => modules/patent_management/case_management/patent_incoming.php
- 文件管理: (1, 5, 4) => modules/patent_management/case_management/file_management.php

### 专利编辑 (菜单索引 6 - 隐藏)

- 专利编辑: (1, 6, null) => modules/patent_management/edit_patent.php

---

## 2 - 商标管理 (trademark_management)

### 新增商标 (菜单索引 0)

- 新增商标: (2, 0, null) => modules/trademark_management/add_trademark.php

### 个人案件 (菜单索引 1)

- 进行中: (2, 1, 0) => modules/trademark_management/personal_cases/in_progress.php
- 已完成: (2, 1, 1) => modules/trademark_management/personal_cases/completed.php
- 已逾期: (2, 1, 2) => modules/trademark_management/personal_cases/overdue.php
- 我的关注: (2, 1, 3) => modules/trademark_management/personal_cases/my_focus.php
- 部门案件: (2, 1, 4) => modules/trademark_management/personal_cases/department_cases.php
- 查询: (2, 1, 5) => modules/trademark_management/personal_cases/search.php

### 递交管理 (菜单索引 2)

- 待处理: (2, 2, 0) => modules/trademark_management/submission_management/pending.php
- 审核中: (2, 2, 1) => modules/trademark_management/submission_management/under_review.php
- 已完成: (2, 2, 2) => modules/trademark_management/submission_management/completed.php

### 案件管理 (菜单索引 3)

- 商标查询: (2, 3, 0) => modules/trademark_management/case_management/trademark_search.php
- 商标来文: (2, 3, 1) => modules/trademark_management/case_management/trademark_incoming.php
- 流程监控: (2, 3, 2) => modules/trademark_management/case_management/process_monitoring.php
- 文件管理: (2, 3, 3) => modules/trademark_management/case_management/file_management.php
- 期限监控: (2, 3, 4) => modules/trademark_management/case_management/deadline_monitoring.php

### 商标编辑 (菜单索引 4 - 隐藏)

- 商标编辑: (2, 4, null) => modules/trademark_management/edit_trademark.php

---

## 3 - 版权管理 (copyright_management)

### 新增版权 (菜单索引 0)

- 新增版权: (3, 0, null) => modules/copyright_management/add_copyright.php

### 案件管理 (菜单索引 1)

- 版权查询: (3, 1, 0) => modules/copyright_management/case_management/copyright_search.php
- 文件管理: (3, 1, 1) => modules/copyright_management/case_management/file_management.php

### 版权编辑 (菜单索引 2 - 隐藏)

- 版权编辑: (3, 2, null) => modules/copyright_management/edit_copyright.php

---

## 4 - 发文管理 (document_management)

### 发文管理 (菜单索引 0)

- 新建: (4, 0, 0) => modules/document_management/outgoing_documents/create_new.php
- 草稿: (4, 0, 1) => modules/document_management/outgoing_documents/draft.php
- 待处理: (4, 0, 2) => modules/document_management/outgoing_documents/pending.php
- 发文列表: (4, 0, 3) => modules/document_management/outgoing_documents/document_list.php

### 邮箱管理 (菜单索引 1)

- 邮件分析: (4, 1, 0) => modules/document_management/email_management/email_analysis.php

---

## 5 - 批量管理 (batch_management)

### 批处理 (菜单索引 0)

- 案件更新: (5, 0, 0) => modules/batch_management/batch_processing/case_update.php
- 处理事项更新: (5, 0, 1) => modules/batch_management/batch_processing/task_update.php
- 处理事项完成: (5, 0, 2) => modules/batch_management/batch_processing/task_completion.php
- 处理事项添加: (5, 0, 3) => modules/batch_management/batch_processing/task_addition.php
- 导入案件: (5, 0, 4) => modules/batch_management/batch_processing/import_cases.php

---

## 6 - 账款管理 (finance_management)

### 费用管理 (菜单索引 0)

- 费用查询: (6, 0, 0) => modules/finance_management/fee_management/fee_query.php
- 费用通知: (6, 0, 1) => modules/finance_management/fee_management/fee_notification.php

### 请款管理 (菜单索引 1)

- 待请款客户: (6, 1, 0) => modules/finance_management/payment_request/pending_request_customers.php
- 草稿: (6, 1, 1) => modules/finance_management/payment_request/draft.php
- 待处理: (6, 1, 2) => modules/finance_management/payment_request/pending.php
- 请款单查询: (6, 1, 3) => modules/finance_management/payment_request/request_query.php

### 账单管理 (菜单索引 2)

- 新增账单（收款）: (6, 2, 0) => modules/finance_management/billing_management/add_bill_collection.php
- 新增账单（销账）: (6, 2, 1) => modules/finance_management/billing_management/add_bill_writeoff.php
- 草稿: (6, 2, 2) => modules/finance_management/billing_management/draft.php
- 待处理: (6, 2, 3) => modules/finance_management/billing_management/pending.php
- 账单查询: (6, 2, 4) => modules/finance_management/billing_management/bill_query.php

### 缴费管理 (菜单索引 3)

- 新建缴费单: (6, 3, 0) => modules/finance_management/payment_management/create_payment.php
- 草稿: (6, 3, 1) => modules/finance_management/payment_management/draft.php
- 待处理: (6, 3, 2) => modules/finance_management/payment_management/pending.php
- 缴费单查询: (6, 3, 3) => modules/finance_management/payment_management/payment_query.php
- 取票码: (6, 3, 4) => modules/finance_management/payment_management/ticket_code.php

---

## 7 - 系统管理 (system_management)

### 个人设置 (菜单索引 0)

- 基本信息: (7, 0, 0) => modules/system_management/personal_settings/basic_info.php
- 修改密码: (7, 0, 1) => modules/system_management/personal_settings/change_password.php
- 邮件设置: (7, 0, 2) => modules/system_management/personal_settings/email_settings.php

### 规则设置 (菜单索引 1)

- 处理事项规则: (7, 1, 0) => modules/system_management/rule_settings/task_rules.php
- 通知书规则: (7, 1, 1) => modules/system_management/rule_settings/notification_rules.php
- 发文规则: (7, 1, 2) => modules/system_management/rule_settings/document_rules.php
- 编号规则: (7, 1, 3) => modules/system_management/rule_settings/numbering_rules.php
- 代理费规则: (7, 1, 4) => modules/system_management/rule_settings/agency_fee_rules.php
- 第三方费规则: (7, 1, 5) => modules/system_management/rule_settings/third_party_fee_rules.php
- 邮件标签规则: (7, 1, 6) => modules/system_management/rule_settings/email_tag_rules.php

### 系统设置 (菜单索引 2)

- 本所信息: (7, 2, 0) => modules/system_management/system_settings/firm_info.php
- 部门设置: (7, 2, 1) => modules/system_management/system_settings/department_settings.php
- 流程设置: (7, 2, 2) => modules/system_management/system_settings/process_settings.php
- 人员设置: (7, 2, 3) => modules/system_management/system_settings/personnel_settings.php
- 角色设置: (7, 2, 4) => modules/system_management/system_settings/role_settings.php
- 流程邮件设置: (7, 2, 5) => modules/system_management/system_settings/process_email_settings.php
- 日志管理: (7, 2, 6) => modules/system_management/system_settings/log_management.php

### 基础数据 (菜单索引 3)

- 业务类型: (7, 3, 0) => modules/system_management/basic_data/business_type.php
- 案件状态: (7, 3, 1) => modules/system_management/basic_data/case_status.php
- 处理事项: (7, 3, 2) => modules/system_management/basic_data/task_items.php
- 处理状态: (7, 3, 3) => modules/system_management/basic_data/process_status.php
- 文件描述: (7, 3, 4) => modules/system_management/basic_data/file_description.php
- 邮件标签: (7, 3, 5) => modules/system_management/basic_data/email_tags.php
- 费用类型: (7, 3, 6) => modules/system_management/basic_data/fee_types.php
- 客户状态: (7, 3, 7) => modules/system_management/basic_data/customer_status.php
- CRM 基础数据: (7, 3, 8) => modules/system_management/basic_data/crm_basic_data.php

---

## 特殊页面

### 首页

- 首页: home => home.php

### 编辑页面（通过 set*edit*\*.php 设置后跳转）

- 专利编辑: (1, 6, null) => modules/patent_management/edit_patent.php
- 商标编辑: (2, 4, null) => modules/trademark_management/edit_trademark.php
- 版权编辑: (3, 2, null) => modules/copyright_management/edit_copyright.php
- 合同编辑: (0, 4, null) => modules/customer_management/edit_contract.php

---

## 常用跳转示例

### JavaScript 调用格式

```javascript
// 通用格式
window.parent.openTab(模块索引, 菜单索引, 子菜单索引);

// 示例
window.parent.openTab(0, 1, 0); // 新增客户
window.parent.openTab(1, 5, 0); // 专利查询
window.parent.openTab(2, 3, 0); // 商标查询
window.parent.openTab(7, 2, 6); // 日志管理
```

### 默认启动页面

- 专利管理默认页面: (1, 5, 0) => 专利查询
- 商标管理默认页面: (2, 3, 0) => 商标查询
- 版权管理默认页面: (3, 1, 0) => 版权查询
