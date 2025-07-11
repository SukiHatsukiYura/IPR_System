-- 专利案件基本信息表
CREATE TABLE `patent_case_info` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `case_code` VARCHAR(50) NOT NULL COMMENT '我方文号/系统编号',
    `case_name` VARCHAR(200) NOT NULL COMMENT '案件名称',
    `case_name_en` VARCHAR(200) DEFAULT NULL COMMENT '英文名称',
    `business_dept_id` INT(11) DEFAULT NULL COMMENT '承办部门ID，关联department(id)',
    `open_date` DATE DEFAULT NULL COMMENT '开卷日期',
    `client_case_code` VARCHAR(50) DEFAULT NULL COMMENT '客户文号',
    `process_item` VARCHAR(100) DEFAULT NULL COMMENT '处理事项',
    `client_id` INT(11) DEFAULT NULL COMMENT '客户ID，关联customer(id)',
    `business_type` VARCHAR(100) DEFAULT NULL COMMENT '业务类型',
    `entrust_date` DATE DEFAULT NULL COMMENT '委案日期',
    `case_status` VARCHAR(100) DEFAULT NULL COMMENT '案件状态',
    `same_day_apply` TEXT COMMENT '同日申请(JSON/逗号分隔)',
    `same_day_submit` TEXT COMMENT '同日递交(JSON/逗号分隔)',
    `agent_rule` VARCHAR(20) DEFAULT NULL COMMENT '代理费规则(自定义/纯包/按项)',
    `remarks` TEXT COMMENT '案件备注',
    `application_no` VARCHAR(50) DEFAULT NULL COMMENT '申请号',
    `application_date` DATE DEFAULT NULL COMMENT '申请日',
    `publication_no` VARCHAR(50) DEFAULT NULL COMMENT '公开号',
    `publication_date` DATE DEFAULT NULL COMMENT '公开日',
    `handler_id` INT(11) DEFAULT NULL COMMENT '处理人ID，关联user(id)',
    `announcement_no` VARCHAR(50) DEFAULT NULL COMMENT '公告号',
    `announcement_date` DATE DEFAULT NULL COMMENT '公告日',
    `certificate_no` VARCHAR(50) DEFAULT NULL COMMENT '证书号',
    `expire_date` DATE DEFAULT NULL COMMENT '届满日',
    `enter_substantive_date` DATE DEFAULT NULL COMMENT '进入实审日',
    `application_mode` VARCHAR(100) DEFAULT NULL COMMENT '申请方式',
    `business_user_ids` VARCHAR(200) DEFAULT NULL COMMENT '业务人员ID（多选，逗号分隔，关联user表）',
    `business_assistant_ids` VARCHAR(200) DEFAULT NULL COMMENT '业务助理ID（多选，逗号分隔，关联user表）',
    `project_leader_id` INT(11) DEFAULT NULL COMMENT '项目负责人ID，关联user(id)',
    `application_type` VARCHAR(100) DEFAULT NULL COMMENT '申请类型',
    `is_allocated` TINYINT(1) DEFAULT 1 COMMENT '是否配案(1是0否)',
    `country` VARCHAR(100) DEFAULT NULL COMMENT '国家(地区)',
    `case_flow` VARCHAR(100) DEFAULT NULL COMMENT '案件流向',
    `start_stage` VARCHAR(100) DEFAULT NULL COMMENT '起始阶段',
    `client_status` VARCHAR(100) DEFAULT NULL COMMENT '客户状态',
    `source_country` VARCHAR(100) DEFAULT NULL COMMENT '案源国',
    `other_options` TEXT COMMENT '其他复选项(JSON/逗号分隔)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_case_code` (`case_code`),
    KEY `idx_business_dept_id` (`business_dept_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_handler_id` (`handler_id`),
    KEY `idx_project_leader_id` (`project_leader_id`),
    CONSTRAINT `fk_patent_case_info_business_dept` FOREIGN KEY (`business_dept_id`) REFERENCES `department`(`id`),
    CONSTRAINT `fk_patent_case_info_client` FOREIGN KEY (`client_id`) REFERENCES `customer`(`id`),
    CONSTRAINT `fk_patent_case_info_handler` FOREIGN KEY (`handler_id`) REFERENCES `user`(`id`),
    CONSTRAINT `fk_patent_case_info_project_leader` FOREIGN KEY (`project_leader_id`) REFERENCES `user`(`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利案件表';



-- 专利案件扩展信息表
CREATE TABLE `patent_case_extend_info` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件主表ID',
    `department_id` INT(11) DEFAULT NULL COMMENT '所属部门，关联department(id)',
    -- 第一列
    `original_application_no` VARCHAR(100) DEFAULT NULL COMMENT '原案申请号',
    `original_application_date` DATE DEFAULT NULL COMMENT '原案申请日',
    `reexamination_invalid_case_no` VARCHAR(100) DEFAULT NULL COMMENT '复审无效案件编号',
    `temporary_application_no` VARCHAR(100) DEFAULT NULL COMMENT '临时申请号',
    `temporary_application_date` DATE DEFAULT NULL COMMENT '临时申请日',
    `applicant_reference_no` VARCHAR(100) DEFAULT NULL COMMENT '申请人文号',
    `art_unit` VARCHAR(100) DEFAULT NULL COMMENT 'Art Unit',
    `enter_national_phase_date` DATE DEFAULT NULL COMMENT '进入国家阶段日期',
    `grant_notice_date` DATE DEFAULT NULL COMMENT '授权发文日',
    `cooperation_agency` VARCHAR(100) DEFAULT NULL COMMENT '协办所',
    `external_source_person` VARCHAR(100) DEFAULT NULL COMMENT '外部案源人',
    `cost` DECIMAL(12, 2) DEFAULT NULL COMMENT '成本',
    -- 第二列
    `pct_application_no` VARCHAR(100) DEFAULT NULL COMMENT 'PCT申请号',
    `pct_application_date` DATE DEFAULT NULL COMMENT 'PCT申请日',
    `pct_publication_no` VARCHAR(100) DEFAULT NULL COMMENT 'PCT公开号',
    `pct_publication_date` DATE DEFAULT NULL COMMENT 'PCT公布日',
    `pct_publication_language` VARCHAR(50) DEFAULT NULL COMMENT 'PCT公布语言',
    `international_search_unit` VARCHAR(100) DEFAULT NULL COMMENT '国际检索单位',
    `confirmation_number` VARCHAR(100) DEFAULT NULL COMMENT 'Confirmation Number',
    `registration_procedure_stage` VARCHAR(100) DEFAULT NULL COMMENT '办登手续阶段',
    `new_application_submit_date` DATE DEFAULT NULL COMMENT '新申请递交日',
    `cooperation_agency_case_no` VARCHAR(100) DEFAULT NULL COMMENT '协办所案号',
    `internal_source_person` VARCHAR(100) DEFAULT NULL COMMENT '内部案源人',
    `budget` DECIMAL(12, 2) DEFAULT NULL COMMENT '预算',
    -- 第三列
    `independent_claim_count` INT DEFAULT NULL COMMENT '独立权利要求项数',
    `claim_count` INT DEFAULT NULL COMMENT '权利要求项数',
    `specification_page_count` INT DEFAULT NULL COMMENT '说明书(包括附图)页数',
    `design_image_count` INT DEFAULT NULL COMMENT '外观设计图片幅数',
    `specification_word_count` INT DEFAULT NULL COMMENT '说明书字数',
    `international_search_complete_date` DATE DEFAULT NULL COMMENT '国际检索完成日',
    `is_first_application` TINYINT(1) DEFAULT NULL COMMENT '是否首次申请 0否 1是',
    `das_access_code` VARCHAR(100) DEFAULT NULL COMMENT 'DAS接入码',
    `case_series` VARCHAR(100) DEFAULT NULL COMMENT '案件系列',
    `grant_date` DATE DEFAULT NULL COMMENT '授权日',
    `division` VARCHAR(100) DEFAULT NULL COMMENT '所属分部',
    `deferred_examination` VARCHAR(50) DEFAULT NULL COMMENT '延迟审查',
    PRIMARY KEY (`id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_department_id` (`department_id`),
    CONSTRAINT `fk_extend_patent_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_extend_department` FOREIGN KEY (`department_id`) REFERENCES `department`(`id`) ON DELETE
    SET
        NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利案件扩展信息表';

-- 修改专利案件扩展信息表的字段名
-- 将 case_series 改为 case_coefficient (案件系数)
ALTER TABLE
    `patent_case_extend_info` CHANGE COLUMN `case_series` `case_coefficient` VARCHAR(100) DEFAULT NULL COMMENT '案件系数';

-- 专利案件处理事项表
CREATE TABLE `patent_case_task` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    -- 基本信息
    `task_item` VARCHAR(200) NOT NULL COMMENT '处理事项',
    `task_status` VARCHAR(100) DEFAULT NULL COMMENT '处理状态',
    `case_stage` VARCHAR(100) DEFAULT NULL COMMENT '案件阶段',
    -- 期限管理
    `internal_deadline` DATE DEFAULT NULL COMMENT '内部期限',
    `client_deadline` DATE DEFAULT NULL COMMENT '客户期限',
    `official_deadline` DATE DEFAULT NULL COMMENT '官方期限',
    -- 人员管理
    `handler_id` INT(11) DEFAULT NULL COMMENT '处理人ID，关联user表',
    `external_handler_id` INT(11) DEFAULT NULL COMMENT '对外处理人ID，关联user表',
    `supervisor_id` INT(11) DEFAULT NULL COMMENT '核稿人ID，关联user表',
    -- 日期管理
    `first_draft_date` DATE DEFAULT NULL COMMENT '初稿日',
    `final_draft_date` DATE DEFAULT NULL COMMENT '定稿日',
    `return_date` DATE DEFAULT NULL COMMENT '返稿日',
    `completion_date` DATE DEFAULT NULL COMMENT '完成日',
    `send_to_firm_date` DATE DEFAULT NULL COMMENT '送合作所日',
    `internal_final_date` DATE DEFAULT NULL COMMENT '内部定稿日',
    -- 创建和修改信息
    `creator_id` INT(11) DEFAULT NULL COMMENT '创建人ID，关联user表',
    `creation_date` DATE DEFAULT NULL COMMENT '创建日期',
    `modifier_id` INT(11) DEFAULT NULL COMMENT '修改人ID，关联user表',
    `modification_date` DATE DEFAULT NULL COMMENT '修改日期',
    -- 其他信息
    `is_urgent` TINYINT(1) DEFAULT 0 COMMENT '是否紧急 0否 1是',
    `task_rule_count` VARCHAR(20) DEFAULT NULL COMMENT '处理事项系数',
    `translation_word_count` INT DEFAULT NULL COMMENT '翻译字数',
    `contract_number` VARCHAR(100) DEFAULT NULL COMMENT '合同编号',
    `remarks` TEXT COMMENT '备注',
    -- 系统字段
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_handler_id` (`handler_id`),
    KEY `idx_external_handler_id` (`external_handler_id`),
    KEY `idx_supervisor_id` (`supervisor_id`),
    KEY `idx_creator_id` (`creator_id`),
    KEY `idx_modifier_id` (`modifier_id`),
    CONSTRAINT `fk_task_patent_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_task_handler` FOREIGN KEY (`handler_id`) REFERENCES `user`(`id`) ON DELETE
    SET
        NULL,
        CONSTRAINT `fk_task_external_handler` FOREIGN KEY (`external_handler_id`) REFERENCES `user`(`id`) ON DELETE
    SET
        NULL,
        CONSTRAINT `fk_task_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `user`(`id`) ON DELETE
    SET
        NULL,
        CONSTRAINT `fk_task_creator` FOREIGN KEY (`creator_id`) REFERENCES `user`(`id`) ON DELETE
    SET
        NULL,
        CONSTRAINT `fk_task_modifier` FOREIGN KEY (`modifier_id`) REFERENCES `user`(`id`) ON DELETE
    SET
        NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利案件处理事项表';

-- 处理事项附件表
CREATE TABLE `patent_task_attachment` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `task_id` INT(11) NOT NULL COMMENT '关联处理事项ID',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    `file_type` VARCHAR(50) NOT NULL COMMENT '文件类型（申请书/说明书/权利要求书/附图/其他）',
    `file_name` VARCHAR(200) NOT NULL COMMENT '文件名',
    `original_file_name` VARCHAR(200) DEFAULT NULL COMMENT '原始文件名',
    `file_path` VARCHAR(300) NOT NULL COMMENT '文件存储路径',
    `file_size` BIGINT DEFAULT NULL COMMENT '文件大小（字节）',
    `mime_type` VARCHAR(100) DEFAULT NULL COMMENT '文件MIME类型',
    `upload_user_id` INT(11) DEFAULT NULL COMMENT '上传人ID，关联user表',
    `official_issue_date` DATE DEFAULT NULL COMMENT '官方发文日（可选）',
    `remarks` TEXT COMMENT '备注说明',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_task_id` (`task_id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_upload_user_id` (`upload_user_id`),
    CONSTRAINT `fk_attachment_task` FOREIGN KEY (`task_id`) REFERENCES `patent_case_task`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attachment_patent_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attachment_upload_user` FOREIGN KEY (`upload_user_id`) REFERENCES `user`(`id`) ON DELETE
    SET
        NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '处理事项附件表';

-- 专利案件申请人关联表
CREATE TABLE `patent_case_applicant` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    -- 基本信息
    `case_type` VARCHAR(50) DEFAULT NULL COMMENT '案件类型（专利/商标/版权，逗号分隔）',
    `applicant_type` VARCHAR(20) DEFAULT NULL COMMENT '申请人类型（个人/单位/其他）',
    `entity_type` VARCHAR(20) DEFAULT NULL COMMENT '实体类型（大实体/小实体/微实体）',
    `name_cn` VARCHAR(100) DEFAULT NULL COMMENT '名称(中文)',
    `name_en` VARCHAR(100) DEFAULT NULL COMMENT '名称(英文)',
    `name_xing_cn` VARCHAR(50) DEFAULT NULL COMMENT '名称/姓(中文)',
    `name_xing_en` VARCHAR(50) DEFAULT NULL COMMENT '名称/姓(英文)',
    `is_first_contact` TINYINT(1) DEFAULT 0 COMMENT '是否第一联系人（0否1是）',
    `is_receipt_title` TINYINT(1) DEFAULT 0 COMMENT '作为收据抬头（0否1是）',
    `receipt_title` VARCHAR(100) DEFAULT NULL COMMENT '申请人收据抬头（勾选作为收据抬头时填写）',
    `credit_code` VARCHAR(50) DEFAULT NULL COMMENT '申请人统一社会信用代码（勾选作为收据抬头时填写）',
    -- 其它字段
    `contact_person` VARCHAR(50) DEFAULT NULL COMMENT '联系人',
    `phone` VARCHAR(30) DEFAULT NULL COMMENT '电话',
    `email` VARCHAR(100) DEFAULT NULL COMMENT '邮件',
    `province` VARCHAR(50) DEFAULT NULL COMMENT '省份',
    `city_cn` VARCHAR(50) DEFAULT NULL COMMENT '城市(中文)',
    `city_en` VARCHAR(50) DEFAULT NULL COMMENT '城市(英文)',
    `district` VARCHAR(50) DEFAULT NULL COMMENT '行政区划',
    `postcode` VARCHAR(20) DEFAULT NULL COMMENT '邮编',
    `address_cn` VARCHAR(200) DEFAULT NULL COMMENT '街道地址(中文)',
    `address_en` VARCHAR(200) DEFAULT NULL COMMENT '街道地址(英文)',
    `department_cn` VARCHAR(100) DEFAULT NULL COMMENT '部门/楼层(中文)',
    `department_en` VARCHAR(100) DEFAULT NULL COMMENT '部门/楼层(英文)',
    `id_type` VARCHAR(50) DEFAULT NULL COMMENT '证件类型',
    `id_number` VARCHAR(100) DEFAULT NULL COMMENT '证件号',
    `is_fee_reduction` TINYINT(1) DEFAULT 0 COMMENT '费用减案（0否1是）',
    `fee_reduction_start` DATE DEFAULT NULL COMMENT '费用减案有效期起',
    `fee_reduction_end` DATE DEFAULT NULL COMMENT '费用减案有效期止',
    `fee_reduction_code` VARCHAR(100) DEFAULT NULL COMMENT '备案证件号',
    `cn_agent_code` VARCHAR(100) DEFAULT NULL COMMENT '中国总委托编号',
    `pct_agent_code` VARCHAR(100) DEFAULT NULL COMMENT 'PCT总委托编号',
    `is_fee_monitor` TINYINT(1) DEFAULT 0 COMMENT '监控年费（0否1是）',
    `country` VARCHAR(100) DEFAULT NULL COMMENT '国家(地区)',
    `nationality` VARCHAR(100) DEFAULT NULL COMMENT '国籍',
    `business_license` VARCHAR(200) DEFAULT NULL COMMENT '营业执照（文件路径）',
    `remark` TEXT COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    CONSTRAINT `fk_patent_applicant_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利案件申请人关联表';

-- 专利案件申请人相关上传文件表
CREATE TABLE `patent_case_applicant_file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_case_applicant_id` INT(11) NOT NULL COMMENT '关联专利案件申请人ID',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    `file_type` VARCHAR(50) NOT NULL COMMENT '文件类型（费减证明/总委托书/附件）',
    `file_name` VARCHAR(200) NOT NULL COMMENT '文件名',
    `file_path` VARCHAR(300) NOT NULL COMMENT '文件存储路径',
    `file_size` BIGINT DEFAULT NULL COMMENT '文件大小（字节）',
    `mime_type` VARCHAR(100) DEFAULT NULL COMMENT '文件MIME类型',
    `upload_user_id` INT(11) DEFAULT NULL COMMENT '上传人ID，关联user表',
    `official_issue_date` DATE DEFAULT NULL COMMENT '官方发文日（可选）',
    `remarks` TEXT COMMENT '备注说明',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_patent_case_applicant_id` (`patent_case_applicant_id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_upload_user_id` (`upload_user_id`),
    CONSTRAINT `fk_patent_applicant_file_applicant` FOREIGN KEY (`patent_case_applicant_id`) REFERENCES `patent_case_applicant`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_patent_applicant_file_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_patent_applicant_file_user` FOREIGN KEY (`upload_user_id`) REFERENCES `user`(`id`) ON DELETE
    SET
        NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利案件申请人相关上传文件表';

-- 专利案件发明人关联表
CREATE TABLE `patent_case_inventor` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    `name_cn` VARCHAR(50) NOT NULL COMMENT '中文名',
    `name_en` VARCHAR(50) DEFAULT NULL COMMENT '英文名',
    `job_no` VARCHAR(30) DEFAULT NULL COMMENT '工号',
    `xing_cn` VARCHAR(50) DEFAULT NULL COMMENT '名称/姓(中文)',
    `xing_en` VARCHAR(50) DEFAULT NULL COMMENT '名称/姓(英文)',
    `ming_cn` VARCHAR(50) DEFAULT NULL COMMENT '名(中文)',
    `ming_en` VARCHAR(50) DEFAULT NULL COMMENT '名(英文)',
    `nationality` VARCHAR(50) DEFAULT NULL COMMENT '国籍',
    `country` VARCHAR(50) DEFAULT NULL COMMENT '国家(地区)',
    `is_tech_contact` TINYINT(1) DEFAULT 0 COMMENT '是否为技术联系人(0否1是)',
    `province` VARCHAR(50) DEFAULT NULL COMMENT '省份',
    `city_cn` VARCHAR(50) DEFAULT NULL COMMENT '城市(中文)',
    `city_en` VARCHAR(50) DEFAULT NULL COMMENT '城市(英文)',
    `address_cn` VARCHAR(200) DEFAULT NULL COMMENT '街道地址(中文)',
    `address_en` VARCHAR(200) DEFAULT NULL COMMENT '街道地址(英文)',
    `department_cn` VARCHAR(100) DEFAULT NULL COMMENT '部门/楼层(中文)',
    `department_en` VARCHAR(100) DEFAULT NULL COMMENT '部门/楼层(英文)',
    `email` VARCHAR(100) DEFAULT NULL COMMENT '邮件',
    `id_number` VARCHAR(50) DEFAULT NULL COMMENT '证件号码',
    `phone` VARCHAR(30) DEFAULT NULL COMMENT '座机',
    `qq` VARCHAR(30) DEFAULT NULL COMMENT 'QQ',
    `mobile` VARCHAR(30) DEFAULT NULL COMMENT '手机',
    `postcode` VARCHAR(20) DEFAULT NULL COMMENT '邮编',
    `remark` TEXT COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    CONSTRAINT `fk_patent_inventor_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利案件发明人关联表';

-- 专利案件代理机构关联表
CREATE TABLE `patent_case_agency` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    `agency_id` INT(11) NOT NULL COMMENT '关联代理机构ID',
    `agency_agent_id` INT(11) DEFAULT NULL COMMENT '关联代理人ID',
    `agency_contact_id` INT(11) DEFAULT NULL COMMENT '关联联系人ID',
    `agency_name_cn` VARCHAR(200) DEFAULT NULL COMMENT '代理机构名称(中文)',
    `agency_code` VARCHAR(100) DEFAULT NULL COMMENT '代理机构代码',
    `agent_name_cn` VARCHAR(50) DEFAULT NULL COMMENT '代理人姓名(中文)',
    `agent_license_no` VARCHAR(50) DEFAULT NULL COMMENT '代理人执业证号',
    `contact_name` VARCHAR(50) DEFAULT NULL COMMENT '联系人姓名',
    `contact_phone` VARCHAR(30) DEFAULT NULL COMMENT '联系人电话',
    `contact_email` VARCHAR(100) DEFAULT NULL COMMENT '联系人邮箱',
    `remark` TEXT COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_agency_id` (`agency_id`),
    KEY `idx_agency_agent_id` (`agency_agent_id`),
    KEY `idx_agency_contact_id` (`agency_contact_id`),
    CONSTRAINT `fk_patent_case_agency_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_patent_case_agency_agency` FOREIGN KEY (`agency_id`) REFERENCES `agency`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_patent_case_agency_agent` FOREIGN KEY (`agency_agent_id`) REFERENCES `agency_agent`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_patent_case_agency_contact` FOREIGN KEY (`agency_contact_id`) REFERENCES `agency_contact`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='专利案件代理机构关联表';

-- 专利案件官费表
CREATE TABLE `patent_case_official_fee` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    `sequence_no` INT(11) DEFAULT NULL COMMENT '序号',
    `fee_name` VARCHAR(200) NOT NULL COMMENT '费用名称',
    `fee_reduction_type` ENUM('基础费用', '单位费减', '个人费减') DEFAULT '基础费用' COMMENT '费减类型',
    `currency` VARCHAR(10) DEFAULT 'CNY' COMMENT '币别',
    `amount` DECIMAL(12, 2) DEFAULT NULL COMMENT '金额',
    `quantity` INT(11) DEFAULT 1 COMMENT '数量',
    `actual_currency` VARCHAR(10) DEFAULT 'CNY' COMMENT '实际币别',
    `actual_amount` DECIMAL(12, 2) DEFAULT NULL COMMENT '实际金额',
    `receivable_date` DATE DEFAULT NULL COMMENT '应收日期',
    `received_date` DATE DEFAULT NULL COMMENT '实收日期',
    `official_deadline` DATE DEFAULT NULL COMMENT '官方期限',
    `paid_date` DATE DEFAULT NULL COMMENT '实付日期',
    `task_item` VARCHAR(100) DEFAULT NULL COMMENT '处理事项',
    `is_verified` TINYINT(1) DEFAULT 0 COMMENT '是否核查(1是0否)',
    `remarks` TEXT COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_fee_name` (`fee_name`),
    KEY `idx_receivable_date` (`receivable_date`),
    KEY `idx_official_deadline` (`official_deadline`),
    KEY `idx_task_item` (`task_item`),
    CONSTRAINT `fk_official_fee_patent_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利案件官费表';

-- 专利案件文件表
CREATE TABLE `patent_case_file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    `file_type` VARCHAR(50) NOT NULL COMMENT '文件类型（申请书/说明书/权利要求书/附图/审查意见/答复意见/其他）',
    `file_name` VARCHAR(200) NOT NULL COMMENT '文件名',
    `file_path` VARCHAR(300) NOT NULL COMMENT '文件存储路径',
    `file_size` BIGINT DEFAULT NULL COMMENT '文件大小（字节）',
    `mime_type` VARCHAR(100) DEFAULT NULL COMMENT '文件MIME类型',
    `upload_user_id` INT(11) DEFAULT NULL COMMENT '上传人ID，关联user表',
    `official_issue_date` DATE DEFAULT NULL COMMENT '官方发文日（可选）',
    `remarks` TEXT COMMENT '备注说明',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_file_type` (`file_type`),
    KEY `idx_upload_user_id` (`upload_user_id`),
    CONSTRAINT `fk_patent_case_file_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_patent_case_file_user` FOREIGN KEY (`upload_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利案件文件表';

-- 用户专利关注表（使用逗号分隔字段）
CREATE TABLE `user_patent_follow` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` INT(11) NOT NULL COMMENT '用户ID，关联user表',
    `followed_case_ids` TEXT COMMENT '关注的案件ID列表（逗号分隔）',
    `follow_count` INT DEFAULT 0 COMMENT '关注案件总数',
    `last_follow_time` DATETIME DEFAULT NULL COMMENT '最后关注时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_follow` (`user_id`),
    KEY `idx_follow_count` (`follow_count`),
    KEY `idx_last_follow_time` (`last_follow_time`),
    CONSTRAINT `fk_user_follow_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '用户专利关注表';

-- 用户专利关注案件状态表
CREATE TABLE `user_patent_follow_case_status` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_patent_follow_id` INT(11) NOT NULL COMMENT '关联用户专利关注表ID',
    `user_id` INT(11) NOT NULL COMMENT '用户ID，关联user表',
    `patent_case_id` INT(11) NOT NULL COMMENT '专利案件ID，关联patent_case_info表',
    `case_status` ENUM('进行中', '已完成', '已逾期') DEFAULT '进行中' COMMENT '案件状态',
    `status_note` VARCHAR(500) DEFAULT NULL COMMENT '状态备注',
    `status_update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '状态更新时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_follow_case` (`user_patent_follow_id`, `patent_case_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_patent_case_id` (`patent_case_id`),
    KEY `idx_case_status` (`case_status`),
    KEY `idx_status_update_time` (`status_update_time`),
    CONSTRAINT `fk_patent_follow_case_status_follow` FOREIGN KEY (`user_patent_follow_id`) REFERENCES `user_patent_follow`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_patent_follow_case_status_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_patent_follow_case_status_case` FOREIGN KEY (`patent_case_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '用户专利关注案件状态表';

-- 专利案件核稿状态表
CREATE TABLE `patent_case_review_status` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    `patent_case_task_id` INT(11) NOT NULL COMMENT '关联处理事项ID',
    `review_status` ENUM('草稿', '审核中', '已完成') DEFAULT '草稿' COMMENT '核稿状态',
    `reviewer_id` INT(11) DEFAULT NULL COMMENT '核稿人ID，关联user表',
    `review_comments` TEXT COMMENT '核稿意见',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_case_task` (`patent_case_info_id`, `patent_case_task_id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_patent_case_task_id` (`patent_case_task_id`),
    KEY `idx_review_status` (`review_status`),
    KEY `idx_reviewer_id` (`reviewer_id`),
    CONSTRAINT `fk_review_status_patent_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_review_status_patent_task` FOREIGN KEY (`patent_case_task_id`) REFERENCES `patent_case_task`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_review_status_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利案件核稿状态表';

-- 专利案件递交状态表
CREATE TABLE `patent_case_submission_status` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    `patent_case_task_id` INT(11) NOT NULL COMMENT '关联处理事项ID',
    `submission_status` ENUM('待处理', '审核中', '已完成') DEFAULT '待处理' COMMENT '递交状态',
    `reviewer_id` INT(11) DEFAULT NULL COMMENT '核稿人ID，关联user表',
    `review_comments` TEXT COMMENT '核稿意见',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_case_task` (`patent_case_info_id`, `patent_case_task_id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_patent_case_task_id` (`patent_case_task_id`),
    KEY `idx_submission_status` (`submission_status`),
    KEY `idx_reviewer_id` (`reviewer_id`),
    CONSTRAINT `fk_submission_status_patent_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_submission_status_patent_task` FOREIGN KEY (`patent_case_task_id`) REFERENCES `patent_case_task`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_submission_status_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利案件递交状态表';

-- 专利来文记录表
CREATE TABLE `patent_incoming_document` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    `incoming_type` VARCHAR(100) NOT NULL COMMENT '来文类型（审查意见通知书/授权通知书/驳回决定/补正通知书/缴费通知书/年费缴费通知书/复审通知书/无效宣告通知书/专利证书/登记手续通知书/视为撤回通知书/恢复权利通知书/其他官方文件）',
    `incoming_date` DATE NOT NULL COMMENT '来文日期',
    `official_number` VARCHAR(100) DEFAULT NULL COMMENT '官方文号',
    `deadline` DATE DEFAULT NULL COMMENT '期限日期',
    `urgency` ENUM('普通', '紧急', '特急') DEFAULT '普通' COMMENT '紧急程度',
    `status` ENUM('待处理', '处理中', '已处理', '已归档') DEFAULT '待处理' COMMENT '来文状态',
    `handler_id` INT(11) DEFAULT NULL COMMENT '处理人ID，关联user表',
    `content` TEXT COMMENT '来文内容',
    `remarks` TEXT COMMENT '备注',
    `creator_id` INT(11) DEFAULT NULL COMMENT '创建人ID，关联user表',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_incoming_type` (`incoming_type`),
    KEY `idx_incoming_date` (`incoming_date`),
    KEY `idx_deadline` (`deadline`),
    KEY `idx_status` (`status`),
    KEY `idx_urgency` (`urgency`),
    KEY `idx_handler_id` (`handler_id`),
    KEY `idx_creator_id` (`creator_id`),
    CONSTRAINT `fk_incoming_patent_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_incoming_handler` FOREIGN KEY (`handler_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_incoming_creator` FOREIGN KEY (`creator_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利来文记录表';

-- 专利来文附件表
CREATE TABLE `patent_incoming_document_file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `patent_incoming_document_id` INT(11) NOT NULL COMMENT '关联专利来文记录ID',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    `file_type` VARCHAR(50) NOT NULL COMMENT '文件类型（官方文件/附件/其他）',
    `file_name` VARCHAR(200) NOT NULL COMMENT '文件名',
    `file_path` VARCHAR(300) NOT NULL COMMENT '文件存储路径',
    `file_size` BIGINT DEFAULT NULL COMMENT '文件大小（字节）',
    `mime_type` VARCHAR(100) DEFAULT NULL COMMENT '文件MIME类型',
    `upload_user_id` INT(11) DEFAULT NULL COMMENT '上传人ID，关联user表',
    `remarks` TEXT COMMENT '备注说明',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_patent_incoming_document_id` (`patent_incoming_document_id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_file_type` (`file_type`),
    KEY `idx_upload_user_id` (`upload_user_id`),
    CONSTRAINT `fk_incoming_file_document` FOREIGN KEY (`patent_incoming_document_id`) REFERENCES `patent_incoming_document`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_incoming_file_patent_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_incoming_file_upload_user` FOREIGN KEY (`upload_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '专利来文附件表';