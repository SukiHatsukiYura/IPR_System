-- 客户信息表
CREATE TABLE `customer` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `customer_code` VARCHAR(50) DEFAULT NULL COMMENT '客户编号（系统自动生成）',
    `customer_name_cn` VARCHAR(100) NOT NULL COMMENT '客户名称(中)',
    `customer_name_en` VARCHAR(100) DEFAULT NULL COMMENT '客户名称(英)',
    `company_leader` VARCHAR(50) DEFAULT NULL COMMENT '公司负责人',
    `email` VARCHAR(100) DEFAULT NULL COMMENT '邮件',
    `business_staff_id` INT(11) DEFAULT NULL COMMENT '业务人员，关联user(id)',
    `internal_signer` VARCHAR(50) DEFAULT NULL COMMENT '内部签署人',
    `external_signer` VARCHAR(50) DEFAULT NULL COMMENT '外部签署人',
    `process_staff_id` INT(11) DEFAULT NULL COMMENT '流程人员，关联user(id)',
    `customer_level` VARCHAR(20) DEFAULT NULL COMMENT '客户等级',
    `address` VARCHAR(200) DEFAULT NULL COMMENT '地址',
    `bank_name` VARCHAR(100) DEFAULT NULL COMMENT '开户银行',
    `deal_status` VARCHAR(20) DEFAULT NULL COMMENT '成交状态',
    `project_leader_id` INT(11) DEFAULT NULL COMMENT '项目负责人，关联user(id)',
    `remark` TEXT COMMENT '备注',
    `case_type_patent` TINYINT(1) DEFAULT 0 COMMENT '案件类型-专利',
    `case_type_trademark` TINYINT(1) DEFAULT 0 COMMENT '案件类型-商标',
    `case_type_copyright` TINYINT(1) DEFAULT 0 COMMENT '案件类型-版权',
    `phone` VARCHAR(30) DEFAULT NULL COMMENT '电话',
    `industry` VARCHAR(200) DEFAULT NULL COMMENT '所属行业（多选，逗号分隔）',
    `creator` VARCHAR(50) DEFAULT NULL COMMENT '创建人',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建日期',
    `internal_signer_phone` VARCHAR(30) DEFAULT NULL COMMENT '内部签署人电话',
    `external_signer_phone` VARCHAR(30) DEFAULT NULL COMMENT '外部签署人电话',
    `billing_address` VARCHAR(200) DEFAULT NULL COMMENT '账单地址',
    `credit_level` VARCHAR(20) DEFAULT NULL COMMENT '信管等级',
    `address_en` VARCHAR(200) DEFAULT NULL COMMENT '英文地址',
    `bank_account` VARCHAR(50) DEFAULT NULL COMMENT '银行账号',
    `customer_id_code` VARCHAR(50) DEFAULT NULL COMMENT '客户代码',
    `new_case_manager_id` INT(11) DEFAULT NULL COMMENT '新申请配案主管，关联user(id)',
    `fax` VARCHAR(30) DEFAULT NULL COMMENT '传真',
    `customer_source` VARCHAR(50) DEFAULT NULL COMMENT '客户来源',
    `internal_signer_email` VARCHAR(100) DEFAULT NULL COMMENT '内部签署人邮箱',
    `external_signer_email` VARCHAR(100) DEFAULT NULL COMMENT '外部签署人邮箱',
    `delivery_address` VARCHAR(200) DEFAULT NULL COMMENT '收货地址',
    `sign_date` DATE DEFAULT NULL COMMENT '客户签约日期',
    `public_email` VARCHAR(100) DEFAULT NULL COMMENT '本所业务公共邮箱',
    `tax_id` VARCHAR(50) DEFAULT NULL COMMENT '纳税人识别号',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_customer_code` (`customer_code`),
    KEY `idx_customer_name_cn` (`customer_name_cn`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '客户信息表';

INSERT INTO customer (customer_name_cn, customer_name_en, company_leader, email, business_staff_id, internal_signer, external_signer, process_staff_id, customer_level, address, bank_name, deal_status, project_leader_id, remark, case_type_patent, case_type_trademark, case_type_copyright, phone, industry, creator, internal_signer_phone, external_signer_phone, billing_address, credit_level, address_en, bank_account, customer_id_code, new_case_manager_id, fax, customer_source, internal_signer_email, external_signer_email, delivery_address, sign_date, public_email, tax_id, created_at) VALUES ('测试客户', 'Test Customer', '张三', 'test@example.com', 1, '李四', '王五', 2, '一般客户', '广州市天河区', '中国银行', '是', 3, '这是一个测试客户', 1, 1, 0, '020-12345678', '地产,制造业,互联网', 'admin', '020-87654321', '13800138000', '广州市天河区某路', '高度信誉', 'Guangzhou, China', '6222021234567890', 'KH20240517001', 4, '020-88888888', '客户介绍', 'lisi@example.com', 'wangwu@example.com', '广州市天河区收货点', '2024-05-17', 'public@example.com', '91440101MA5XXXXXX', NOW());

-- 客户联系人信息表
CREATE TABLE `contact` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `customer_id` INT(11) NOT NULL COMMENT '关联客户ID',
    `name` VARCHAR(50) NOT NULL COMMENT '姓名',
    `mobile` VARCHAR(30) NOT NULL COMMENT '手机',
    `position` VARCHAR(50) DEFAULT NULL COMMENT '职位',
    `private_email` VARCHAR(100) DEFAULT NULL COMMENT '私人邮箱',
    `gender` TINYINT(1) DEFAULT NULL COMMENT '性别（0女1男2未知）',
    `fax` VARCHAR(30) DEFAULT NULL COMMENT '传真',
    `wechat` VARCHAR(50) DEFAULT NULL COMMENT '微信号',
    `letter_title` VARCHAR(100) DEFAULT NULL COMMENT '信函抬头',
    `work_address` VARCHAR(200) NOT NULL COMMENT '工作地址',
    `home_address` VARCHAR(200) DEFAULT NULL COMMENT '家庭地址',
    `hobby` VARCHAR(200) DEFAULT NULL COMMENT '兴趣爱好',
    `remark` TEXT COMMENT '备注',
    `work_email` VARCHAR(100) DEFAULT NULL COMMENT '工作邮箱',
    `phone` VARCHAR(30) DEFAULT NULL COMMENT '电话',
    `salutation` VARCHAR(10) DEFAULT NULL COMMENT '称呼（无、博士、小姐、教授、先生、女士、经理、总经理）',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否在职（1是0否）',
    `contact_type` VARCHAR(20) NOT NULL COMMENT '联系人类别（IPR员、流程人员、技术联系人员、财务人员、公司签发人、发明人、来文通知人员、商标联系人员、其他）',
    `qq` VARCHAR(30) DEFAULT NULL COMMENT 'QQ',
    `sort_order` INT(11) DEFAULT 0 COMMENT '排序序号',
    `postcode` VARCHAR(20) DEFAULT NULL COMMENT '邮编',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_customer_id` (`customer_id`),
    CONSTRAINT `fk_contact_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '联系人信息表';

-- 联系人信息表添加案件类型字段
ALTER TABLE `contact`
ADD COLUMN `case_type_patent` TINYINT(1) DEFAULT 0 COMMENT '案件类型-专利' AFTER `customer_id`,
ADD COLUMN `case_type_trademark` TINYINT(1) DEFAULT 0 COMMENT '案件类型-商标' AFTER `case_type_patent`,
ADD COLUMN `case_type_copyright` TINYINT(1) DEFAULT 0 COMMENT '案件类型-版权' AFTER `case_type_trademark`;

-- 申请人信息表
CREATE TABLE `applicant` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `customer_id` INT(11) NOT NULL COMMENT '关联客户ID',
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
    KEY `idx_customer_id` (`customer_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '申请人信息表';

-- 申请人相关上传文件表
CREATE TABLE `applicant_file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `applicant_id` INT(11) NOT NULL COMMENT '关联申请人ID',
    `file_type` VARCHAR(50) NOT NULL COMMENT '文件类型（费减证明/总委托书/附件）',
    `file_name` VARCHAR(200) NOT NULL COMMENT '文件名',
    `file_path` VARCHAR(300) NOT NULL COMMENT '文件存储路径',
    `official_issue_date` DATE DEFAULT NULL COMMENT '官方发文日（可选）',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
    PRIMARY KEY (`id`),
    KEY `idx_applicant_id` (`applicant_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '申请人相关上传文件表';

-- 发明人信息表
CREATE TABLE `inventor` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
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
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '发明人信息表';

ALTER TABLE `inventor`
ADD COLUMN `customer_id` INT(11) NOT NULL COMMENT '关联客户ID' AFTER `id`,
ADD KEY `idx_customer_id` (`customer_id`),
ADD CONSTRAINT `fk_inventor_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer`(`id`) ON DELETE CASCADE;

-- 客户要求
CREATE TABLE `customer_requirement` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `customer_id` INT(11) NOT NULL COMMENT '关联客户ID',
    `user_id` INT(11) DEFAULT NULL COMMENT '更新者/创建人ID',
    `case_type` VARCHAR(50) DEFAULT NULL COMMENT '案件类型(专利/商标/版权)',
    `requirement_type` VARCHAR(50) DEFAULT NULL COMMENT '要求类型(看稿要求/费用要求/其他要求)',
    `title` VARCHAR(200) NOT NULL COMMENT '要求标题',
    `content` TEXT NOT NULL COMMENT '要求内容',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_req_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_req_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户要求表';

-- 客户联系记录表
CREATE TABLE `contact_record` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `contact_id` INT(11) NOT NULL COMMENT '客户联系人ID，关联contact表',
    `contact_time` DATE NOT NULL COMMENT '联系时间',
    `contact_method` VARCHAR(50) NOT NULL COMMENT '联系方式（如电话、拜访、邮件等）',
    `contact_type` VARCHAR(50) NOT NULL COMMENT '联系类型（如案件通知、费用通知、官文通知）',
    `content` TEXT NOT NULL COMMENT '联系内容',
    `user_id` INT(11) NOT NULL COMMENT '我方联系人ID，关联user表',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_contact_id` (`contact_id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_contact_record_contact` FOREIGN KEY (`contact_id`) REFERENCES `contact`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_contact_record_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '联系记录表';

-- 客户文件表
CREATE TABLE `customer_file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `customer_id` INT(11) NOT NULL COMMENT '关联客户ID',
    `file_name` VARCHAR(200) NOT NULL COMMENT '附件名称',
    `file_type` VARCHAR(50) DEFAULT NULL COMMENT '文件类型',
    `file_desc` VARCHAR(200) DEFAULT NULL COMMENT '文件描述',
    `file_path` VARCHAR(300) NOT NULL COMMENT '文件存储路径',
    `uploader_id` INT(11) DEFAULT NULL COMMENT '上传者ID，关联user表',
    `upload_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
    PRIMARY KEY (`id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_uploader_id` (`uploader_id`),
    CONSTRAINT `fk_customer_file_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_customer_file_user` FOREIGN KEY (`uploader_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户文件表';

-- 代理机构表
CREATE TABLE `agency` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `agency_name_cn` VARCHAR(200) NOT NULL COMMENT '代理机构(中文)',
    `agency_name_en` VARCHAR(200) DEFAULT NULL COMMENT '代理机构(英文)',
    `country` VARCHAR(100) DEFAULT NULL COMMENT '所属国家(地区)',
    `province` VARCHAR(100) DEFAULT NULL COMMENT '省份',
    `city_cn` VARCHAR(100) DEFAULT NULL COMMENT '城市(中文)',
    `city_en` VARCHAR(100) DEFAULT NULL COMMENT '城市(英文)',
    `street_address_cn` VARCHAR(200) DEFAULT NULL COMMENT '街道地址(中文)',
    `street_address_en` VARCHAR(200) DEFAULT NULL COMMENT '街道地址(英文)',
    `department_cn` VARCHAR(100) DEFAULT NULL COMMENT '部门/楼层(中文)',
    `department_en` VARCHAR(100) DEFAULT NULL COMMENT '部门/楼层(英文)',
    `responsible_person` VARCHAR(100) DEFAULT NULL COMMENT '负责人',
    `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    `fax` VARCHAR(50) DEFAULT NULL COMMENT '传真',
    `agency_code` VARCHAR(100) DEFAULT NULL COMMENT '代理机构代码',
    `establish_date` DATE DEFAULT NULL COMMENT '成立时间',
    `phone` VARCHAR(50) DEFAULT NULL COMMENT '联系电话',
    `mail` VARCHAR(100) DEFAULT NULL COMMENT '邮件',
    `website` VARCHAR(200) DEFAULT NULL COMMENT '网址',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否有效(1是0否)',
    `is_customer` TINYINT(1) DEFAULT 0 COMMENT '是否为客户(1是0否)',
    `customer_id` INT(11) DEFAULT NULL COMMENT '关联客户ID，仅“是否为客户”为是时填写',
    `is_default` TINYINT(1) DEFAULT 0 COMMENT '是否默认本所(1是0否)',
    `credit_code` VARCHAR(100) DEFAULT NULL COMMENT '统一社会信用代码',
    `agency_types` VARCHAR(50) DEFAULT NULL COMMENT '默认代理机构类型(逗号分隔:专利,商标,版权)',
    `remark` TEXT COMMENT '备注',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_customer_id` (`customer_id`),
    CONSTRAINT `fk_agency_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理机构表';

-- 代理机构联系人表
CREATE TABLE `agency_contact` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `agency_id` INT(11) NOT NULL COMMENT '关联代理机构ID',
    `case_type_patent` TINYINT(1) DEFAULT 0 COMMENT '案件类型-专利',
    `case_type_trademark` TINYINT(1) DEFAULT 0 COMMENT '案件类型-商标',
    `case_type_copyright` TINYINT(1) DEFAULT 0 COMMENT '案件类型-版权',
    `name` VARCHAR(50) NOT NULL COMMENT '姓名',
    `mobile` VARCHAR(30) NOT NULL COMMENT '手机',
    `position` VARCHAR(50) DEFAULT NULL COMMENT '职位',
    `private_email` VARCHAR(100) DEFAULT NULL COMMENT '私人邮箱',
    `gender` TINYINT(1) DEFAULT 2 COMMENT '性别（0女1男2未知）',
    `fax` VARCHAR(30) DEFAULT NULL COMMENT '传真',
    `wechat` VARCHAR(50) DEFAULT NULL COMMENT '微信号',
    `letter_title` VARCHAR(100) DEFAULT NULL COMMENT '信函抬头',
    `work_address` VARCHAR(200) NOT NULL COMMENT '工作地址',
    `home_address` VARCHAR(200) DEFAULT NULL COMMENT '家庭地址',
    `hobby` VARCHAR(200) DEFAULT NULL COMMENT '兴趣爱好',
    `remark` TEXT COMMENT '备注',
    `work_email` VARCHAR(100) DEFAULT NULL COMMENT '工作邮箱',
    `phone` VARCHAR(30) DEFAULT NULL COMMENT '电话',
    `salutation` VARCHAR(10) DEFAULT NULL COMMENT '称呼（无、博士、小姐、教授、先生、女士、经理、总经理）',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否在职（1是0否）',
    `contact_type` VARCHAR(20) NOT NULL COMMENT '联系人类别',
    `qq` VARCHAR(30) DEFAULT NULL COMMENT 'QQ',
    `sort_order` INT(11) DEFAULT 0 COMMENT '排序序号',
    `history_address` VARCHAR(200) DEFAULT NULL COMMENT '历史地址',
    `postcode` VARCHAR(20) DEFAULT NULL COMMENT '邮编',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_agency_id` (`agency_id`),
    CONSTRAINT `fk_agency_contact_agency` FOREIGN KEY (`agency_id`) REFERENCES `agency`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '代理机构联系人表';

-- 代理机构代理人表
CREATE TABLE `agency_agent` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `agency_id` INT(11) NOT NULL COMMENT '关联代理机构ID',
    `user_id` INT(11) DEFAULT NULL COMMENT '本所用户ID，关联user表',
    `name_cn` VARCHAR(50) NOT NULL COMMENT '姓名(中文)',
    `xing_cn` VARCHAR(50) DEFAULT NULL COMMENT '名称/姓(中文)',
    `ming_cn` VARCHAR(50) DEFAULT NULL COMMENT '名(中文)',
    `name_en` VARCHAR(50) DEFAULT NULL COMMENT '姓名(英文)',
    `xing_en` VARCHAR(50) DEFAULT NULL COMMENT '名称/姓(英文)',
    `ming_en` VARCHAR(50) DEFAULT NULL COMMENT '名(英文)',
    `gender` TINYINT(1) DEFAULT 1 COMMENT '性别（0女1男）',
    `major` VARCHAR(50) DEFAULT NULL COMMENT '专业',
    `birthday` DATE DEFAULT NULL COMMENT '出生日期',
    `license_no` VARCHAR(50) NOT NULL COMMENT '执业证号',
    `phone` VARCHAR(30) DEFAULT NULL COMMENT '电话',
    `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    `qualification_no` VARCHAR(50) DEFAULT NULL COMMENT '资格证号',
    `qualification_date` DATE DEFAULT NULL COMMENT '获得代理资格日期',
    `is_default` TINYINT(1) DEFAULT 0 COMMENT '默认本所代理人(1是0否)',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否有效(1是0否)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_agency_id` (`agency_id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_agency_agent_agency` FOREIGN KEY (`agency_id`) REFERENCES `agency`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_agency_agent_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理机构代理人表';

-- 代理机构要求表
CREATE TABLE `agency_requirement` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `agency_id` INT(11) NOT NULL COMMENT '关联代理机构ID',
    `user_id` INT(11) DEFAULT NULL COMMENT '更新者/创建人ID',
    `case_type` VARCHAR(50) DEFAULT NULL COMMENT '案件类型(专利/商标/版权)',
    `requirement_type` VARCHAR(50) DEFAULT NULL COMMENT '要求类型(看稿要求/费用要求/其他要求)',
    `title` VARCHAR(200) NOT NULL COMMENT '要求标题',
    `content` TEXT NOT NULL COMMENT '要求内容',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_agency_id` (`agency_id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_req_agency` FOREIGN KEY (`agency_id`) REFERENCES `agency`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_agency_req_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理机构要求表';

-- 代理机构联系记录表
CREATE TABLE `agency_contact_record` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `agency_contact_id` INT(11) NOT NULL COMMENT '代理机构联系人ID，关联agency_contact表',
    `contact_time` DATE NOT NULL COMMENT '联系时间',
    `contact_method` VARCHAR(50) NOT NULL COMMENT '联系方式（如电话、拜访、邮件等）',
    `contact_type` VARCHAR(50) NOT NULL COMMENT '联系类型（如案件通知、费用通知、官文通知）',
    `content` TEXT NOT NULL COMMENT '联系内容',
    `user_id` INT(11) NOT NULL COMMENT '我方联系人ID，关联user表',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_agency_contact_id` (`agency_contact_id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_agency_contact_record_contact` FOREIGN KEY (`agency_contact_id`) REFERENCES `agency_contact`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_agency_contact_record_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理机构联系记录表';

-- 代理机构文件表
CREATE TABLE `agency_file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `agency_id` INT(11) NOT NULL COMMENT '关联代理机构ID',
    `file_name` VARCHAR(200) NOT NULL COMMENT '附件名称',
    `file_type` VARCHAR(50) DEFAULT NULL COMMENT '文件类型',
    `file_desc` VARCHAR(200) DEFAULT NULL COMMENT '文件描述',
    `file_path` VARCHAR(300) NOT NULL COMMENT '文件存储路径',
    `uploader_id` INT(11) DEFAULT NULL COMMENT '上传者ID，关联user表',
    `upload_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
    PRIMARY KEY (`id`),
    KEY `idx_agency_id` (`agency_id`),
    KEY `idx_uploader_id` (`uploader_id`),
    CONSTRAINT `fk_agency_file_agency` FOREIGN KEY (`agency_id`) REFERENCES `agency`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_agency_file_user` FOREIGN KEY (`uploader_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理机构文件表';

-- 代理机构银行账户信息表
CREATE TABLE `agency_bank_account` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `agency_id` INT(11) NOT NULL COMMENT '关联代理机构ID',
    `payee_name` VARCHAR(100) NOT NULL COMMENT '收款人名称',
    `payee_address` VARCHAR(200) DEFAULT NULL COMMENT '收款人地址',
    `bank_name` VARCHAR(100) NOT NULL COMMENT '开户银行',
    `bank_branch` VARCHAR(100) DEFAULT NULL COMMENT '分行',
    `bank_address` VARCHAR(200) DEFAULT NULL COMMENT '银行地址',
    `bank_account` VARCHAR(100) NOT NULL COMMENT '银行账号',
    `intermediary_bank` VARCHAR(200) DEFAULT NULL COMMENT '中转银行',
    `intermediary_account` VARCHAR(100) DEFAULT NULL COMMENT '中转银行账号(ABA Routing No.)',
    `swift_code` VARCHAR(50) DEFAULT NULL COMMENT '银行国际代码(Swift Code No.)',
    `other_info` VARCHAR(300) DEFAULT NULL COMMENT '其他信息',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否有效(1是0否)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_agency_id` (`agency_id`),
    CONSTRAINT `fk_agency_bank_account_agency` FOREIGN KEY (`agency_id`) REFERENCES `agency`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '代理机构银行账户信息表';