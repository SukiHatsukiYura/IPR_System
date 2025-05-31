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
    `expire_date` DATE DEFAULT NULL COMMENT '属满日',
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
    `cost` DECIMAL(12,2) DEFAULT NULL COMMENT '成本',

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
    `budget` DECIMAL(12,2) DEFAULT NULL COMMENT '预算',

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
    CONSTRAINT `fk_extend_department` FOREIGN KEY (`department_id`) REFERENCES `department`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='专利案件扩展信息表';

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
    CONSTRAINT `fk_task_handler` FOREIGN KEY (`handler_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_task_external_handler` FOREIGN KEY (`external_handler_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_task_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_task_creator` FOREIGN KEY (`creator_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_task_modifier` FOREIGN KEY (`modifier_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='专利案件处理事项表';