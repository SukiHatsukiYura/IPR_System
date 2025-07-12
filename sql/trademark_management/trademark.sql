-- 商标案件基本信息表
CREATE TABLE `trademark_case_info` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `case_code` VARCHAR(50) NOT NULL COMMENT '我方文号/系统编号',
    `case_name_en` VARCHAR(200) DEFAULT NULL COMMENT '英文名称',
    `application_no` VARCHAR(50) DEFAULT NULL COMMENT '申请号',
    `business_dept_id` INT(11) DEFAULT NULL COMMENT '承办部门ID，关联department(id)',
    `trademark_class` VARCHAR(500) DEFAULT NULL COMMENT '商标类别',
    `initial_publication_date` DATE DEFAULT NULL COMMENT '初审公告日',
    `initial_publication_period` VARCHAR(50) DEFAULT NULL COMMENT '初审公告期',
    `client_id` INT(11) DEFAULT NULL COMMENT '客户ID，关联customer(id)',
    `case_type` VARCHAR(100) DEFAULT NULL COMMENT '案件类型',
    `business_type` VARCHAR(100) DEFAULT NULL COMMENT '业务类型',
    `entrust_date` DATE DEFAULT NULL COMMENT '委案日期',
    `case_status` VARCHAR(100) DEFAULT NULL COMMENT '案件状态',
    `process_item` VARCHAR(100) DEFAULT NULL COMMENT '处理事项',
    `source_country` VARCHAR(100) DEFAULT NULL COMMENT '案源国',
    `trademark_description` TEXT COMMENT '商标说明',
    `case_name` VARCHAR(200) NOT NULL COMMENT '商标名称',
    `other_name` VARCHAR(200) DEFAULT NULL COMMENT '其它名称',
    `application_date` DATE DEFAULT NULL COMMENT '申请日',
    `business_user_ids` VARCHAR(200) DEFAULT NULL COMMENT '业务人员ID（多选，逗号分隔，关联user表）',
    `business_assistant_ids` VARCHAR(200) DEFAULT NULL COMMENT '业务助理ID（多选，逗号分隔，关联user表）',
    `trademark_type` VARCHAR(100) DEFAULT NULL COMMENT '商标种类',
    `initial_publication_no` VARCHAR(50) DEFAULT NULL COMMENT '初审公告号',
    `registration_no` VARCHAR(50) DEFAULT NULL COMMENT '注册号',
    `country` VARCHAR(100) DEFAULT NULL COMMENT '国家(地区)',
    `case_flow` VARCHAR(100) DEFAULT NULL COMMENT '案件流向',
    `application_mode` VARCHAR(100) DEFAULT NULL COMMENT '申请方式',
    `open_date` DATE DEFAULT NULL COMMENT '开卷日期',
    `client_case_code` VARCHAR(50) DEFAULT NULL COMMENT '客户文号',
    `approval_date` DATE DEFAULT NULL COMMENT '获批日',
    `remarks` TEXT COMMENT '备注',
    `is_main_case` TINYINT(1) DEFAULT 0 COMMENT '是否主案(1是0否)',
    `registration_publication_date` DATE DEFAULT NULL COMMENT '注册公告日',
    `registration_publication_period` VARCHAR(50) DEFAULT NULL COMMENT '注册公告期',
    `client_status` VARCHAR(100) DEFAULT NULL COMMENT '客户状态',
    `renewal_date` DATE DEFAULT NULL COMMENT '续展日',
    `expire_date` DATE DEFAULT NULL COMMENT '终止日',
    `trademark_image_path` VARCHAR(300) DEFAULT NULL COMMENT '商标图片存储路径',
    `trademark_image_name` VARCHAR(200) DEFAULT NULL COMMENT '商标图片原始文件名',
    `trademark_image_size` BIGINT DEFAULT NULL COMMENT '商标图片文件大小（字节）',
    `trademark_image_type` VARCHAR(50) DEFAULT NULL COMMENT '商标图片文件类型',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_case_code` (`case_code`),
    KEY `idx_business_dept_id` (`business_dept_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_application_no` (`application_no`),
    KEY `idx_registration_no` (`registration_no`),
    KEY `idx_trademark_class` (`trademark_class`),
    CONSTRAINT `fk_trademark_case_info_business_dept` FOREIGN KEY (`business_dept_id`) REFERENCES `department`(`id`),
    CONSTRAINT `fk_trademark_case_info_client` FOREIGN KEY (`client_id`) REFERENCES `customer`(`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '商标案件基本信息表';

-- ALTER TABLE trademark_case_info MODIFY COLUMN trademark_class VARCHAR(500) DEFAULT NULL COMMENT '商标类别';

-- -- 为现有商标表添加图片相关字段
-- ALTER TABLE `trademark_case_info` 
-- ADD COLUMN `trademark_image_path` VARCHAR(300) DEFAULT NULL COMMENT '商标图片存储路径' AFTER `expire_date`,
-- ADD COLUMN `trademark_image_name` VARCHAR(200) DEFAULT NULL COMMENT '商标图片原始文件名' AFTER `trademark_image_path`,
-- ADD COLUMN `trademark_image_size` BIGINT DEFAULT NULL COMMENT '商标图片文件大小（字节）' AFTER `trademark_image_name`,
-- ADD COLUMN `trademark_image_type` VARCHAR(50) DEFAULT NULL COMMENT '商标图片文件类型' AFTER `trademark_image_size`;

-- 商标案件处理事项表
CREATE TABLE `trademark_case_task` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `trademark_case_info_id` INT(11) NOT NULL COMMENT '关联商标案件ID',
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
    KEY `idx_trademark_case_info_id` (`trademark_case_info_id`),
    KEY `idx_handler_id` (`handler_id`),
    KEY `idx_external_handler_id` (`external_handler_id`),
    KEY `idx_supervisor_id` (`supervisor_id`),
    KEY `idx_creator_id` (`creator_id`),
    KEY `idx_modifier_id` (`modifier_id`),
    CONSTRAINT `fk_trademark_task_case` FOREIGN KEY (`trademark_case_info_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_task_handler` FOREIGN KEY (`handler_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_trademark_task_external_handler` FOREIGN KEY (`external_handler_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_trademark_task_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_trademark_task_creator` FOREIGN KEY (`creator_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_trademark_task_modifier` FOREIGN KEY (`modifier_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '商标案件处理事项表';

-- 商标案件扩展信息表
CREATE TABLE `trademark_case_extend_info` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `trademark_case_info_id` INT(11) NOT NULL COMMENT '关联商标案件主表ID',    
    -- 商标特征信息
    `is_3d_mark` TINYINT(1) DEFAULT 0 COMMENT '是否三维标志 0否 1是',
    `color_form` VARCHAR(100) DEFAULT NULL COMMENT '颜色形式',
    `specified_color` VARCHAR(200) DEFAULT NULL COMMENT '指定颜色',
    `sound_file_path` VARCHAR(300) DEFAULT NULL COMMENT '声音文件路径',
    `case_nature` VARCHAR(100) DEFAULT NULL COMMENT '案件性质',
    `trademark_form_type` VARCHAR(100) DEFAULT NULL COMMENT '商标形式类型',
    -- 案源信息
    `second_source_person` VARCHAR(100) DEFAULT NULL COMMENT '第二案源人',
    `external_source_person` VARCHAR(100) DEFAULT NULL COMMENT '外部案源人',
    `internal_source_person` VARCHAR(100) DEFAULT NULL COMMENT '内部案源人',
    -- 疑难信息
    `difficulty_type` VARCHAR(100) DEFAULT NULL COMMENT '疑难类型',
    `difficulty_description` TEXT COMMENT '疑难说明',
    `opponent_name` VARCHAR(200) DEFAULT NULL COMMENT '对方当事人名称',
    `supplementary_reason` TEXT COMMENT '补充理由',
    -- 财务信息
    `cost` DECIMAL(12, 2) DEFAULT NULL COMMENT '成本',
    `budget` DECIMAL(12, 2) DEFAULT NULL COMMENT '预算',
    -- 马德里相关信息
    `madrid_application_language` VARCHAR(50) DEFAULT NULL COMMENT '马德里申请语言',
    `madrid_application_no` VARCHAR(100) DEFAULT NULL COMMENT '马德里申请号',
    `madrid_application_date` DATE DEFAULT NULL COMMENT '马德里申请日',
    `madrid_registration_no` VARCHAR(100) DEFAULT NULL COMMENT '马德里注册号',
    `madrid_registration_date` DATE DEFAULT NULL COMMENT '马德里注册日',
    -- 其他信息
    `is_famous_trademark` TINYINT(1) DEFAULT 0 COMMENT '是否认定驰名商标 0否 1是',
    -- 系统字段
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_trademark_case_info_id` (`trademark_case_info_id`),
    CONSTRAINT `fk_trademark_extend_case` FOREIGN KEY (`trademark_case_info_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '商标案件扩展信息表';


-- 商标案件申请人关联表
CREATE TABLE `trademark_case_applicant` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `trademark_case_info_id` INT(11) NOT NULL COMMENT '关联商标案件ID',
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
    KEY `idx_trademark_case_info_id` (`trademark_case_info_id`),
    CONSTRAINT `fk_trademark_applicant_case` FOREIGN KEY (`trademark_case_info_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '商标案件申请人关联表';

-- 商标案件申请人相关上传文件表
CREATE TABLE `trademark_case_applicant_file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `trademark_case_applicant_id` INT(11) NOT NULL COMMENT '关联商标案件申请人ID',
    `trademark_case_info_id` INT(11) NOT NULL COMMENT '关联商标案件ID',
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
    KEY `idx_trademark_case_applicant_id` (`trademark_case_applicant_id`),
    KEY `idx_trademark_case_info_id` (`trademark_case_info_id`),
    KEY `idx_upload_user_id` (`upload_user_id`),
    CONSTRAINT `fk_trademark_applicant_file_applicant` FOREIGN KEY (`trademark_case_applicant_id`) REFERENCES `trademark_case_applicant`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_applicant_file_case` FOREIGN KEY (`trademark_case_info_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_applicant_file_user` FOREIGN KEY (`upload_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '商标案件申请人相关上传文件表';

-- 商标案件代理机构关联表
CREATE TABLE `trademark_case_agency` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `trademark_case_info_id` INT(11) NOT NULL COMMENT '关联商标案件ID',
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
    KEY `idx_trademark_case_info_id` (`trademark_case_info_id`),
    KEY `idx_agency_id` (`agency_id`),
    KEY `idx_agency_agent_id` (`agency_agent_id`),
    KEY `idx_agency_contact_id` (`agency_contact_id`),
    CONSTRAINT `fk_trademark_case_agency_case` FOREIGN KEY (`trademark_case_info_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_case_agency_agency` FOREIGN KEY (`agency_id`) REFERENCES `agency`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_case_agency_agent` FOREIGN KEY (`agency_agent_id`) REFERENCES `agency_agent`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_trademark_case_agency_contact` FOREIGN KEY (`agency_contact_id`) REFERENCES `agency_contact`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商标案件代理机构关联表';

-- 商标案件官费表
CREATE TABLE `trademark_case_official_fee` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `trademark_case_info_id` INT(11) NOT NULL COMMENT '关联商标案件ID',
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
    KEY `idx_trademark_case_info_id` (`trademark_case_info_id`),
    KEY `idx_fee_name` (`fee_name`),
    KEY `idx_receivable_date` (`receivable_date`),
    KEY `idx_official_deadline` (`official_deadline`),
    KEY `idx_task_item` (`task_item`),
    CONSTRAINT `fk_trademark_official_fee_case` FOREIGN KEY (`trademark_case_info_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '商标案件官费表';

-- 商标案件文件表
CREATE TABLE `trademark_case_file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `trademark_case_info_id` INT(11) NOT NULL COMMENT '关联商标案件ID',
    `file_type` VARCHAR(50) NOT NULL COMMENT '文件类型（申请书/商标图样/使用证据/审查意见/答复意见/其他）',
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
    KEY `idx_trademark_case_info_id` (`trademark_case_info_id`),
    KEY `idx_file_type` (`file_type`),
    KEY `idx_upload_user_id` (`upload_user_id`),
    CONSTRAINT `fk_trademark_case_file_case` FOREIGN KEY (`trademark_case_info_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_case_file_user` FOREIGN KEY (`upload_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '商标案件文件表';

-- 处理事项附件表
CREATE TABLE `trademark_task_attachment` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `task_id` INT(11) NOT NULL COMMENT '关联处理事项ID',
    `trademark_case_info_id` INT(11) NOT NULL COMMENT '关联商标案件ID',
    `file_type` VARCHAR(50) NOT NULL COMMENT '文件类型（申请书/商标图样/使用证据/审查意见/答复意见/其他）',
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
    KEY `idx_trademark_case_info_id` (`trademark_case_info_id`),
    KEY `idx_upload_user_id` (`upload_user_id`),
    CONSTRAINT `fk_trademark_attachment_task` FOREIGN KEY (`task_id`) REFERENCES `trademark_case_task`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_attachment_case` FOREIGN KEY (`trademark_case_info_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_attachment_upload_user` FOREIGN KEY (`upload_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '商标处理事项附件表';

-- 用户商标关注表（使用逗号分隔字段）
CREATE TABLE `user_trademark_follow` (
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
    CONSTRAINT `fk_user_trademark_follow_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '用户商标关注表';

-- 用户商标关注案件状态表
CREATE TABLE `user_trademark_follow_case_status` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_trademark_follow_id` INT(11) NOT NULL COMMENT '关联用户商标关注表ID',
    `user_id` INT(11) NOT NULL COMMENT '用户ID，关联user表',
    `trademark_case_id` INT(11) NOT NULL COMMENT '商标案件ID，关联trademark_case_info表',
    `case_status` ENUM('进行中', '已完成', '已逾期') DEFAULT '进行中' COMMENT '案件状态',
    `status_note` VARCHAR(500) DEFAULT NULL COMMENT '状态备注',
    `status_update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '状态更新时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_follow_case` (`user_trademark_follow_id`, `trademark_case_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_trademark_case_id` (`trademark_case_id`),
    KEY `idx_case_status` (`case_status`),
    KEY `idx_status_update_time` (`status_update_time`),
    CONSTRAINT `fk_follow_case_status_follow` FOREIGN KEY (`user_trademark_follow_id`) REFERENCES `user_trademark_follow`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_follow_case_status_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_follow_case_status_case` FOREIGN KEY (`trademark_case_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '用户商标关注案件状态表';

-- 商标案件递交状态表
CREATE TABLE `trademark_case_submission_status` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `trademark_case_info_id` INT(11) NOT NULL COMMENT '关联商标案件ID',
    `trademark_case_task_id` INT(11) NOT NULL COMMENT '关联处理事项ID',
    `submission_status` ENUM('待处理', '审核中', '已完成') DEFAULT '待处理' COMMENT '递交状态',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_case_task` (`trademark_case_info_id`, `trademark_case_task_id`),
    KEY `idx_trademark_case_info_id` (`trademark_case_info_id`),
    KEY `idx_trademark_case_task_id` (`trademark_case_task_id`),
    KEY `idx_submission_status` (`submission_status`),
    CONSTRAINT `fk_trademark_submission_status_case` FOREIGN KEY (`trademark_case_info_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_submission_status_task` FOREIGN KEY (`trademark_case_task_id`) REFERENCES `trademark_case_task`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '商标案件递交状态表';

-- 商标来文记录表
CREATE TABLE `trademark_incoming_document` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `trademark_case_info_id` INT(11) NOT NULL COMMENT '关联商标案件ID',
    `incoming_type` VARCHAR(100) NOT NULL COMMENT '来文类型（审查意见通知书/授权通知书/驳回决定/补正通知书/缴费通知书/年费缴费通知书/复审通知书/无效宣告通知书/商标证书/登记手续通知书/视为撤回通知书/恢复权利通知书/其他官方文件）',
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
    KEY `idx_trademark_case_info_id` (`trademark_case_info_id`),
    KEY `idx_incoming_type` (`incoming_type`),
    KEY `idx_incoming_date` (`incoming_date`),
    KEY `idx_deadline` (`deadline`),
    KEY `idx_status` (`status`),
    KEY `idx_urgency` (`urgency`),
    KEY `idx_handler_id` (`handler_id`),
    KEY `idx_creator_id` (`creator_id`),
    CONSTRAINT `fk_trademark_incoming_case` FOREIGN KEY (`trademark_case_info_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_incoming_handler` FOREIGN KEY (`handler_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_trademark_incoming_creator` FOREIGN KEY (`creator_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '商标来文记录表';

-- 商标来文附件表
CREATE TABLE `trademark_incoming_document_file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `trademark_incoming_document_id` INT(11) NOT NULL COMMENT '关联商标来文记录ID',
    `trademark_case_info_id` INT(11) NOT NULL COMMENT '关联商标案件ID',
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
    KEY `idx_trademark_incoming_document_id` (`trademark_incoming_document_id`),
    KEY `idx_trademark_case_info_id` (`trademark_case_info_id`),
    KEY `idx_file_type` (`file_type`),
    KEY `idx_upload_user_id` (`upload_user_id`),
    CONSTRAINT `fk_trademark_incoming_file_document` FOREIGN KEY (`trademark_incoming_document_id`) REFERENCES `trademark_incoming_document`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_incoming_file_case` FOREIGN KEY (`trademark_case_info_id`) REFERENCES `trademark_case_info`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trademark_incoming_file_upload_user` FOREIGN KEY (`upload_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '商标来文附件表'; 