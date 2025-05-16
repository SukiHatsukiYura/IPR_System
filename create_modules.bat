@echo off
chcp 65001
echo 正在创建模块目录结构...

:: 创建主模块目录
mkdir modules
cd modules

:: 一、客户管理
mkdir "customer_management"
cd "customer_management"

:: CRM模块
mkdir "crm"
cd "crm"
echo ^<?php>leads.php
echo // 线索功能 - 客户管理/CRM模块下的线索管理功能>>leads.php

echo ^<?php>leads_pool.php
echo // 线索池功能 - 客户管理/CRM模块下的线索池管理功能>>leads_pool.php

echo ^<?php>customers.php
echo // 客户功能 - 客户管理/CRM模块下的客户管理功能>>customers.php

echo ^<?php>contracts.php
echo // 合同功能 - 客户管理/CRM模块下的合同管理功能>>contracts.php

echo ^<?php>customer_pool.php
echo // 客户公海功能 - 客户管理/CRM模块下的客户公海管理功能>>customer_pool.php

echo ^<?php>follow_up_records.php
echo // 跟进记录功能 - 客户管理/CRM模块下的跟进记录管理功能>>follow_up_records.php
cd ..

:: 客户模块
mkdir "customer"
cd "customer"
echo ^<?php>add_customer.php
echo // 新增客户功能 - 客户管理/客户模块下的新增客户功能>>add_customer.php

echo ^<?php>customer_list.php
echo // 客户列表功能 - 客户管理/客户模块下的客户列表功能>>customer_list.php

echo ^<?php>applicant_list.php
echo // 申请人列表功能 - 客户管理/客户模块下的申请人列表功能>>applicant_list.php

echo ^<?php>inventor_list.php
echo // 发明人列表功能 - 客户管理/客户模块下的发明人列表功能>>inventor_list.php

echo ^<?php>contact_records.php
echo // 联系记录功能 - 客户管理/客户模块下的联系记录功能>>contact_records.php
cd ..

:: 代理机构模块
mkdir "agency"
cd "agency"
echo ^<?php>add_agency.php
echo // 新增代理机构功能 - 客户管理/代理机构模块下的新增代理机构功能>>add_agency.php

echo ^<?php>agency_list.php
echo // 代理机构列表功能 - 客户管理/代理机构模块下的代理机构列表功能>>agency_list.php
cd ..

:: 合同管理模块
mkdir "contract_management"
cd "contract_management"
echo ^<?php>create_contract.php
echo // 新建合同功能 - 客户管理/合同管理模块下的新建合同功能>>create_contract.php

echo ^<?php>draft.php
echo // 草稿功能 - 客户管理/合同管理模块下的草稿管理功能>>draft.php

echo ^<?php>pending.php
echo // 待处理功能 - 客户管理/合同管理模块下的待处理管理功能>>pending.php

echo ^<?php>completed.php
echo // 已完成功能 - 客户管理/合同管理模块下的已完成管理功能>>completed.php

echo ^<?php>contract_list.php
echo // 合同列表功能 - 客户管理/合同管理模块下的合同列表功能>>contract_list.php
cd ..
cd ..

:: 二、专利管理
mkdir "patent_management"
cd "patent_management"

echo ^<?php>add_patent.php
echo // 新增专利功能 - 专利管理模块下的新增专利功能>>add_patent.php

:: 个人案件模块
mkdir "personal_cases"
cd "personal_cases"
echo ^<?php>in_progress.php
echo // 进行中功能 - 专利管理/个人案件模块下的进行中案件管理功能>>in_progress.php

echo ^<?php>completed.php
echo // 已完成功能 - 专利管理/个人案件模块下的已完成案件管理功能>>completed.php

echo ^<?php>overdue.php
echo // 己逾期功能 - 专利管理/个人案件模块下的己逾期案件管理功能>>overdue.php

echo ^<?php>my_focus.php
echo // 我的关注功能 - 专利管理/个人案件模块下的我的关注案件管理功能>>my_focus.php

echo ^<?php>department_cases.php
echo // 部门案件功能 - 专利管理/个人案件模块下的部门案件管理功能>>department_cases.php

echo ^<?php>patent_search.php
echo // 专利查询功能 - 专利管理/个人案件模块下的专利查询功能>>patent_search.php
cd ..

:: 配案管理模块
mkdir "case_assignment"
cd "case_assignment"
echo ^<?php>pending_assignment.php
echo // 待配案功能 - 专利管理/配案管理模块下的待配案功能>>pending_assignment.php

echo ^<?php>assigned.php
echo // 已配案功能 - 专利管理/配案管理模块下的已配案功能>>assigned.php
cd ..

:: 核稿管理模块
mkdir "review_management"
cd "review_management"
echo ^<?php>draft.php
echo // 草稿功能 - 专利管理/核稿管理模块下的草稿功能>>draft.php

echo ^<?php>pending_review.php
echo // 待我核稿功能 - 专利管理/核稿管理模块下的待我核稿功能>>pending_review.php

echo ^<?php>under_review.php
echo // 审核中功能 - 专利管理/核稿管理模块下的审核中功能>>under_review.php

echo ^<?php>completed.php
echo // 已完成功能 - 专利管理/核稿管理模块下的已完成功能>>completed.php

echo ^<?php>export_review_package.php
echo // 导出核稿包功能 - 专利管理/核稿管理模块下的导出核稿包功能>>export_review_package.php
cd ..

:: 递交管理模块
mkdir "submission_management"
cd "submission_management"
echo ^<?php>pending.php
echo // 待处理功能 - 专利管理/递交管理模块下的待处理功能>>pending.php

echo ^<?php>under_review.php
echo // 审核中功能 - 专利管理/递交管理模块下的审核中功能>>under_review.php

echo ^<?php>completed.php
echo // 已完成功能 - 专利管理/递交管理模块下的已完成功能>>completed.php
cd ..

:: 案件管理模块
mkdir "case_management"
cd "case_management"
echo ^<?php>patent_search.php
echo // 专利查询功能 - 专利管理/案件管理模块下的专利查询功能>>patent_search.php

echo ^<?php>deadline_monitoring.php
echo // 期限监控功能 - 专利管理/案件管理模块下的期限监控功能>>deadline_monitoring.php

echo ^<?php>process_monitoring.php
echo // 流程监控功能 - 专利管理/案件管理模块下的流程监控功能>>process_monitoring.php

echo ^<?php>patent_incoming.php
echo // 专利来文功能 - 专利管理/案件管理模块下的专利来文功能>>patent_incoming.php

echo ^<?php>file_management.php
echo // 文件管理功能 - 专利管理/案件管理模块下的文件管理功能>>file_management.php
cd ..
cd ..

:: 三、商标管理
mkdir "trademark_management"
cd "trademark_management"

echo ^<?php>add_trademark.php
echo // 新增商标功能 - 商标管理模块下的新增商标功能>>add_trademark.php

:: 个人案件模块
mkdir "personal_cases"
cd "personal_cases"
echo ^<?php>in_progress.php
echo // 进行中功能 - 商标管理/个人案件模块下的进行中功能>>in_progress.php

echo ^<?php>completed.php
echo // 已完成功能 - 商标管理/个人案件模块下的已完成功能>>completed.php

echo ^<?php>overdue.php
echo // 已逾期功能 - 商标管理/个人案件模块下的已逾期功能>>overdue.php

echo ^<?php>my_focus.php
echo // 我的关注功能 - 商标管理/个人案件模块下的我的关注功能>>my_focus.php

echo ^<?php>department_cases.php
echo // 部门案件功能 - 商标管理/个人案件模块下的部门案件功能>>department_cases.php

echo ^<?php>search.php
echo // 查询功能 - 商标管理/个人案件模块下的查询功能>>search.php
cd ..

:: 递交管理模块
mkdir "submission_management"
cd "submission_management"
echo ^<?php>pending.php
echo // 待处理功能 - 商标管理/递交管理模块下的待处理功能>>pending.php

echo ^<?php>under_review.php
echo // 审核中功能 - 商标管理/递交管理模块下的审核中功能>>under_review.php

echo ^<?php>completed.php
echo // 已完成功能 - 商标管理/递交管理模块下的已完成功能>>completed.php
cd ..

:: 案件管理模块
mkdir "case_management"
cd "case_management"
echo ^<?php>trademark_search.php
echo // 商标查询功能 - 商标管理/案件管理模块下的商标查询功能>>trademark_search.php

echo ^<?php>trademark_incoming.php
echo // 商标来文功能 - 商标管理/案件管理模块下的商标来文功能>>trademark_incoming.php

echo ^<?php>process_monitoring.php
echo // 流程监控功能 - 商标管理/案件管理模块下的流程监控功能>>process_monitoring.php

echo ^<?php>file_management.php
echo // 文件管理功能 - 商标管理/案件管理模块下的文件管理功能>>file_management.php

echo ^<?php>deadline_monitoring.php
echo // 期限监控功能 - 商标管理/案件管理模块下的期限监控功能>>deadline_monitoring.php
cd ..
cd ..

:: 四、版权管理
mkdir "copyright_management"
cd "copyright_management"

echo ^<?php>add_copyright.php
echo // 新增版权功能 - 版权管理模块下的新增版权功能>>add_copyright.php

:: 案件管理模块
mkdir "case_management"
cd "case_management"
echo ^<?php>copyright_search.php
echo // 版权查询功能 - 版权管理/案件管理模块下的版权查询功能>>copyright_search.php

echo ^<?php>file_management.php
echo // 文件管理功能 - 版权管理/案件管理模块下的文件管理功能>>file_management.php
cd ..
cd ..

:: 五、发文管理
mkdir "document_management"
cd "document_management"

:: 发文管理模块
mkdir "outgoing_documents"
cd "outgoing_documents"
echo ^<?php>create_new.php
echo // 新建功能 - 发文管理/发文管理模块下的新建功能>>create_new.php

echo ^<?php>draft.php
echo // 草稿功能 - 发文管理/发文管理模块下的草稿功能>>draft.php

echo ^<?php>pending.php
echo // 待处理功能 - 发文管理/发文管理模块下的待处理功能>>pending.php

echo ^<?php>document_list.php
echo // 发文列表功能 - 发文管理/发文管理模块下的发文列表功能>>document_list.php
cd ..

:: 邮箱管理模块
mkdir "email_management"
cd "email_management"
echo ^<?php>email_analysis.php
echo // 邮件分析功能 - 发文管理/邮箱管理模块下的邮件分析功能>>email_analysis.php
cd ..
cd ..

:: 六、批量管理
mkdir "batch_management"
cd "batch_management"

:: 批处理模块
mkdir "batch_processing"
cd "batch_processing"
echo ^<?php>case_update.php
echo // 案件更新功能 - 批量管理/批处理模块下的案件更新功能>>case_update.php

echo ^<?php>task_update.php
echo // 处理事项更新功能 - 批量管理/批处理模块下的处理事项更新功能>>task_update.php

echo ^<?php>task_completion.php
echo // 处理事项完成功能 - 批量管理/批处理模块下的处理事项完成功能>>task_completion.php

echo ^<?php>task_addition.php
echo // 处理事项添加功能 - 批量管理/批处理模块下的处理事项添加功能>>task_addition.php

echo ^<?php>import_cases.php
echo // 导入案件功能 - 批量管理/批处理模块下的导入案件功能>>import_cases.php
cd ..
cd ..

:: 七、账款管理
mkdir "finance_management"
cd "finance_management"

:: 费用管理模块
mkdir "fee_management"
cd "fee_management"
echo ^<?php>fee_query.php
echo // 费用查询功能 - 账款管理/费用管理模块下的费用查询功能>>fee_query.php

echo ^<?php>fee_notification.php
echo // 费用通知功能 - 账款管理/费用管理模块下的费用通知功能>>fee_notification.php
cd ..

:: 请款管理模块
mkdir "payment_request"
cd "payment_request"
echo ^<?php>pending_request_customers.php
echo // 待请款客户功能 - 账款管理/请款管理模块下的待请款客户功能>>pending_request_customers.php

echo ^<?php>draft.php
echo // 草稿功能 - 账款管理/请款管理模块下的草稿功能>>draft.php

echo ^<?php>pending.php
echo // 待处理功能 - 账款管理/请款管理模块下的待处理功能>>pending.php

echo ^<?php>request_query.php
echo // 请款单查询功能 - 账款管理/请款管理模块下的请款单查询功能>>request_query.php
cd ..

:: 账单管理模块
mkdir "billing_management"
cd "billing_management"
echo ^<?php>add_bill_collection.php
echo // 新增账单（收款）功能 - 账款管理/账单管理模块下的新增账单（收款）功能>>add_bill_collection.php

echo ^<?php>add_bill_writeoff.php
echo // 新增账单（销账）功能 - 账款管理/账单管理模块下的新增账单（销账）功能>>add_bill_writeoff.php

echo ^<?php>draft.php
echo // 草稿功能 - 账款管理/账单管理模块下的草稿功能>>draft.php

echo ^<?php>pending.php
echo // 待处理功能 - 账款管理/账单管理模块下的待处理功能>>pending.php

echo ^<?php>bill_query.php
echo // 账单查询功能 - 账款管理/账单管理模块下的账单查询功能>>bill_query.php
cd ..

:: 缴费管理模块
mkdir "payment_management"
cd "payment_management"
echo ^<?php>create_payment.php
echo // 新建缴费单功能 - 账款管理/缴费管理模块下的新建缴费单功能>>create_payment.php

echo ^<?php>draft.php
echo // 草稿功能 - 账款管理/缴费管理模块下的草稿功能>>draft.php

echo ^<?php>pending.php
echo // 待处理功能 - 账款管理/缴费管理模块下的待处理功能>>pending.php

echo ^<?php>payment_query.php
echo // 缴费单查询功能 - 账款管理/缴费管理模块下的缴费单查询功能>>payment_query.php

echo ^<?php>ticket_code.php
echo // 取票码功能 - 账款管理/缴费管理模块下的取票码功能>>ticket_code.php
cd ..
cd ..

:: 八、系统管理
mkdir "system_management"
cd "system_management"

:: 个人设置模块
mkdir "personal_settings"
cd "personal_settings"
echo ^<?php>basic_info.php
echo // 基本信息功能 - 系统管理/个人设置模块下的基本信息功能>>basic_info.php

echo ^<?php>change_password.php
echo // 修改密码功能 - 系统管理/个人设置模块下的修改密码功能>>change_password.php

echo ^<?php>email_settings.php
echo // 邮件设置功能 - 系统管理/个人设置模块下的邮件设置功能>>email_settings.php
cd ..

:: 规则设置模块
mkdir "rule_settings"
cd "rule_settings"
echo ^<?php>task_rules.php
echo // 处理事项规则功能 - 系统管理/规则设置模块下的处理事项规则功能>>task_rules.php

echo ^<?php>notification_rules.php
echo // 通知书规则功能 - 系统管理/规则设置模块下的通知书规则功能>>notification_rules.php

echo ^<?php>document_rules.php
echo // 发文规则功能 - 系统管理/规则设置模块下的发文规则功能>>document_rules.php

echo ^<?php>numbering_rules.php
echo // 编号规则功能 - 系统管理/规则设置模块下的编号规则功能>>numbering_rules.php

echo ^<?php>agency_fee_rules.php
echo // 代理费规则功能 - 系统管理/规则设置模块下的代理费规则功能>>agency_fee_rules.php

echo ^<?php>third_party_fee_rules.php
echo // 第三方费规则功能 - 系统管理/规则设置模块下的第三方费规则功能>>third_party_fee_rules.php

echo ^<?php>email_tag_rules.php
echo // 邮件标签规则功能 - 系统管理/规则设置模块下的邮件标签规则功能>>email_tag_rules.php
cd ..

:: 系统设置模块
mkdir "system_settings"
cd "system_settings"
echo ^<?php>firm_info.php
echo // 本所信息功能 - 系统管理/系统设置模块下的本所信息功能>>firm_info.php

echo ^<?php>department_settings.php
echo // 部门设置功能 - 系统管理/系统设置模块下的部门设置功能>>department_settings.php

echo ^<?php>process_settings.php
echo // 流程设置功能 - 系统管理/系统设置模块下的流程设置功能>>process_settings.php

echo ^<?php>personnel_settings.php
echo // 人员设置功能 - 系统管理/系统设置模块下的人员设置功能>>personnel_settings.php

echo ^<?php>role_settings.php
echo // 角色设置功能 - 系统管理/系统设置模块下的角色设置功能>>role_settings.php

echo ^<?php>process_email_settings.php
echo // 流程邮件设置功能 - 系统管理/系统设置模块下的流程邮件设置功能>>process_email_settings.php
cd ..

:: 基础数据模块
mkdir "basic_data"
cd "basic_data"
echo ^<?php>business_type.php
echo // 业务类型功能 - 系统管理/基础数据模块下的业务类型功能>>business_type.php

echo ^<?php>case_status.php
echo // 案件状态功能 - 系统管理/基础数据模块下的案件状态功能>>case_status.php

echo ^<?php>task_items.php
echo // 处理事项功能 - 系统管理/基础数据模块下的处理事项功能>>task_items.php

echo ^<?php>process_status.php
echo // 处理状态功能 - 系统管理/基础数据模块下的处理状态功能>>process_status.php

echo ^<?php>file_description.php
echo // 文件描述功能 - 系统管理/基础数据模块下的文件描述功能>>file_description.php

echo ^<?php>email_tags.php
echo // 邮件标签功能 - 系统管理/基础数据模块下的邮件标签功能>>email_tags.php

echo ^<?php>fee_types.php
echo // 费用类型功能 - 系统管理/基础数据模块下的费用类型功能>>fee_types.php

echo ^<?php>customer_status.php
echo // 客户状态功能 - 系统管理/基础数据模块下的客户状态功能>>customer_status.php

echo ^<?php>crm_basic_data.php
echo // CRM基础数据功能 - 系统管理/基础数据模块下的CRM基础数据功能>>crm_basic_data.php
cd ..
cd ..

cd ..
cd ..

echo 目录结构和PHP文件创建完成！
echo 目录路径格式：modules/模块名称/模块功能名称/功能文件
echo 每个PHP文件都包含对应的中文功能注释。
pause 