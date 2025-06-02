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

-- 处理事项附件表
CREATE TABLE `patent_task_attachment` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `task_id` INT(11) NOT NULL COMMENT '关联处理事项ID',
    `patent_case_info_id` INT(11) NOT NULL COMMENT '关联专利案件ID',
    `file_type` VARCHAR(50) NOT NULL COMMENT '文件类型（申请书/说明书/权利要求书/附图/其他）',
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
    KEY `idx_task_id` (`task_id`),
    KEY `idx_patent_case_info_id` (`patent_case_info_id`),
    KEY `idx_upload_user_id` (`upload_user_id`),
    CONSTRAINT `fk_attachment_task` FOREIGN KEY (`task_id`) REFERENCES `patent_case_task`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attachment_patent_case` FOREIGN KEY (`patent_case_info_id`) REFERENCES `patent_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attachment_upload_user` FOREIGN KEY (`upload_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='处理事项附件表';

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
    CONSTRAINT `fk_patent_applicant_file_user` FOREIGN KEY (`upload_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='专利案件申请人相关上传文件表';

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