-- 合同基本信息表
CREATE TABLE `contract` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    -- 合同信息
    `contract_no` VARCHAR(50) NOT NULL COMMENT '合同编号',
    `contract_name` VARCHAR(200) NOT NULL COMMENT '合同名称',
    `customer_id` INT(11) NOT NULL COMMENT '对应客户ID，关联customer(id)',
    `opportunity_id` INT(11) DEFAULT NULL COMMENT '对应的商机ID',
    `contract_amount` DECIMAL(12, 2) NOT NULL COMMENT '合同总金额',
    `currency` VARCHAR(20) DEFAULT '人民币' COMMENT '货币类型',
    `valid_start_date` DATE DEFAULT NULL COMMENT '合同有效时间开始',
    `valid_end_date` DATE DEFAULT NULL COMMENT '合同有效时间结束',
    `case_count` INT(11) NOT NULL COMMENT '案件数量',
    `party_a_signer` VARCHAR(50) DEFAULT NULL COMMENT '甲方签约人',
    `party_a_signer_mobile` VARCHAR(30) DEFAULT NULL COMMENT '甲方签约人手机',
    `business_user_id` INT(11) NOT NULL COMMENT '业务人员ID，关联user(id)',
    `contract_type` VARCHAR(50) NOT NULL COMMENT '合同类型',
    `payment_method` VARCHAR(50) NOT NULL COMMENT '付款方式',
    `party_b_company` VARCHAR(200) DEFAULT NULL COMMENT '乙方签约公司',
    `party_b_signer` VARCHAR(50) DEFAULT NULL COMMENT '乙方签约人',
    `party_b_signer_mobile` VARCHAR(30) DEFAULT NULL COMMENT '乙方签约人手机',
    `sign_date` DATE DEFAULT NULL COMMENT '签约日期',
    `contract_receive_date` DATE DEFAULT NULL COMMENT '合同领用日期',
    `remarks` TEXT COMMENT '备注',
    
    -- 跟进信息
    `contract_status` VARCHAR(50) DEFAULT NULL COMMENT '合同状态',
    `next_follow_date` DATE DEFAULT NULL COMMENT '下次跟进时间',
    
    -- 人员信息
    `responsible_user_id` INT(11) DEFAULT NULL COMMENT '负责人ID，关联user(id)',
    `collaborator_user_ids` VARCHAR(200) DEFAULT NULL COMMENT '协作人ID（多选，逗号分隔，关联user表）',
    
    -- 其他信息
    `leader_user_id` INT(11) DEFAULT NULL COMMENT '负责人ID，关联user(id)',
    `department_id` INT(11) DEFAULT NULL COMMENT '所属部门ID，关联department(id)',
    `previous_responsible_user_id` INT(11) DEFAULT NULL COMMENT '前负责人ID，关联user(id)',
    `creator_user_id` INT(11) DEFAULT NULL COMMENT '创建人ID，关联user(id)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_contract_no` (`contract_no`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_business_user_id` (`business_user_id`),
    KEY `idx_responsible_user_id` (`responsible_user_id`),
    KEY `idx_department_id` (`department_id`),
    KEY `idx_creator_user_id` (`creator_user_id`),
    KEY `idx_contract_status` (`contract_status`),
    KEY `idx_sign_date` (`sign_date`),
    KEY `idx_next_follow_date` (`next_follow_date`),
    
    CONSTRAINT `fk_contract_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_contract_business_user` FOREIGN KEY (`business_user_id`) REFERENCES `user`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_contract_responsible_user` FOREIGN KEY (`responsible_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_contract_department` FOREIGN KEY (`department_id`) REFERENCES `department`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_contract_creator_user` FOREIGN KEY (`creator_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_contract_previous_responsible_user` FOREIGN KEY (`previous_responsible_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_contract_leader_user` FOREIGN KEY (`leader_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '合同基本信息表';

-- 插入示例数据
INSERT INTO `contract` (
    `contract_no`, `contract_name`, `customer_id`, `contract_amount`, `case_count`, 
    `business_user_id`, `contract_type`, `payment_method`, `contract_status`, 
    `creator_user_id`, `created_at`
) VALUES (
    'HT20240517001', '测试合同', 1, 50000.00, 10, 
    1, '专利代理合同', '分期付款', '执行中', 
    1, NOW()
);

-- 合同扩展信息表
CREATE TABLE `contract_extend_info` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `contract_id` INT(11) NOT NULL COMMENT '关联合同ID',
    
    -- 第一列扩展字段
    `department_branch` VARCHAR(100) DEFAULT NULL COMMENT '所属分部',
    `importance_level` VARCHAR(50) DEFAULT NULL COMMENT '重要程度',
    `party_a_email` VARCHAR(100) DEFAULT NULL COMMENT '甲方合同邮箱',
    `total_official_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '合同官费总额',
    `first_official_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '首付官费',
    `first_agency_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '首付代理费',
    `first_total_amount` DECIMAL(12, 2) DEFAULT NULL COMMENT '首付总额',
    `long_term_payment_method` VARCHAR(100) DEFAULT NULL COMMENT '长期付款方式',
    `advance_payment` DECIMAL(12, 2) DEFAULT NULL COMMENT '预付款',
    `long_term_payment_note` TEXT COMMENT '长期付款说明',
    `invoice_method` VARCHAR(100) DEFAULT NULL COMMENT '开票方式',
    `invoice_title` VARCHAR(200) DEFAULT NULL COMMENT '发票抬头',
    `invention_count` INT DEFAULT NULL COMMENT '发明件数',
    `invention_agency_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '发明代理费',
    `other_count` INT DEFAULT NULL COMMENT '其他件数',
    `other_agency_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '其他代理费',
    `other_note` TEXT COMMENT '其他说明',
    
    -- 第二列扩展字段
    `contract_summary` TEXT COMMENT '合同摘要',
    `party_b_email` VARCHAR(100) DEFAULT NULL COMMENT '乙方合同邮箱',
    `total_agency_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '合同代理费总额',
    `middle_official_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '中间款官费',
    `middle_agency_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '中间款代理费',
    `middle_total_amount` DECIMAL(12, 2) DEFAULT NULL COMMENT '中间款总额',
    `application_fee_payment_method` VARCHAR(100) DEFAULT NULL COMMENT '申请费缴费方式',
    `is_deferred_examination_fee` TINYINT(1) DEFAULT 0 COMMENT '是否缓交实审费(0否1是)',
    `agency_fee_settlement_method` VARCHAR(100) DEFAULT NULL COMMENT '代理费结算方式',
    `dual_report_count` INT DEFAULT NULL COMMENT '双报件数',
    `dual_report_total_agency_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '双报总代理费',
    `utility_model_count` INT DEFAULT NULL COMMENT '新型件数',
    `utility_model_agency_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '新型代理费',
    `application_region` VARCHAR(200) DEFAULT NULL COMMENT '申报区域',
    `application_deadline` VARCHAR(200) DEFAULT NULL COMMENT '申报期限',
    `application_requirements` TEXT COMMENT '申报要求',
    
    -- 第三列扩展字段
    `payment_account` VARCHAR(200) DEFAULT NULL COMMENT '收款账户',
    `service_fee_standard` VARCHAR(100) DEFAULT NULL COMMENT '服务费标准',
    `final_official_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '尾款官费',
    `final_agency_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '尾款代理费',
    `final_total_amount` DECIMAL(12, 2) DEFAULT NULL COMMENT '尾款总额',
    `authorization_fee_payment_method` VARCHAR(100) DEFAULT NULL COMMENT '授权费缴费方式',
    `first_three_years_fee_payment_method` VARCHAR(100) DEFAULT NULL COMMENT '前三年年费缴费方式',
    `dual_report_invention_agency_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '双报发明代理费',
    `dual_report_utility_model_agency_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '双报新型代理费',
    `design_count` INT DEFAULT NULL COMMENT '外观件数',
    `design_agency_fee` DECIMAL(12, 2) DEFAULT NULL COMMENT '外观代理费',
    `annual_fee_supervision_requirements` TEXT COMMENT '年费监管要求',
    
    -- 系统字段
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    PRIMARY KEY (`id`),
    KEY `idx_contract_id` (`contract_id`),
    CONSTRAINT `fk_contract_extend_contract` FOREIGN KEY (`contract_id`) REFERENCES `contract`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '合同扩展信息表'; 

-- 合同附件表
CREATE TABLE `contract_file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `contract_id` INT(11) NOT NULL COMMENT '关联合同ID',
    `file_type` VARCHAR(50) NOT NULL COMMENT '文件类型（合同正本/合同副本/补充协议/其他）',
    `file_name` VARCHAR(200) NOT NULL COMMENT '文件名',
    `file_path` VARCHAR(300) NOT NULL COMMENT '文件存储路径',
    `file_size` BIGINT DEFAULT NULL COMMENT '文件大小（字节）',
    `mime_type` VARCHAR(100) DEFAULT NULL COMMENT '文件MIME类型',
    `upload_user_id` INT(11) DEFAULT NULL COMMENT '上传人ID，关联user表',
    `upload_date` DATE DEFAULT NULL COMMENT '上传日期',
    `remarks` TEXT COMMENT '备注说明',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_contract_id` (`contract_id`),
    KEY `idx_file_type` (`file_type`),
    KEY `idx_upload_user_id` (`upload_user_id`),
    CONSTRAINT `fk_contract_file_contract` FOREIGN KEY (`contract_id`) REFERENCES `contract`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_contract_file_user` FOREIGN KEY (`upload_user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '合同附件表';

-- 合同跟进记录表
CREATE TABLE `contract_follow_record` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `contract_id` INT(11) NOT NULL COMMENT '关联合同ID',
    `follow_date` DATE NOT NULL COMMENT '跟进日期',
    `follow_type` VARCHAR(50) NOT NULL COMMENT '跟进类型（电话跟进/邮件跟进/面谈/其他）',
    `follow_content` TEXT NOT NULL COMMENT '跟进内容',
    `follow_result` VARCHAR(100) DEFAULT NULL COMMENT '跟进结果',
    `next_follow_date` DATE DEFAULT NULL COMMENT '下次跟进时间',
    `follow_user_id` INT(11) NOT NULL COMMENT '跟进人ID，关联user表',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_contract_id` (`contract_id`),
    KEY `idx_follow_user_id` (`follow_user_id`),
    KEY `idx_follow_date` (`follow_date`),
    KEY `idx_next_follow_date` (`next_follow_date`),
    CONSTRAINT `fk_contract_follow_contract` FOREIGN KEY (`contract_id`) REFERENCES `contract`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_contract_follow_user` FOREIGN KEY (`follow_user_id`) REFERENCES `user`(`id`) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '合同跟进记录表';

-- 合同付款记录表
CREATE TABLE `contract_payment_record` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `contract_id` INT(11) NOT NULL COMMENT '关联合同ID',
    `payment_date` DATE NOT NULL COMMENT '付款日期',
    `payment_amount` DECIMAL(12, 2) NOT NULL COMMENT '付款金额',
    `payment_method` VARCHAR(50) NOT NULL COMMENT '付款方式',
    `payment_status` VARCHAR(50) NOT NULL COMMENT '付款状态（已付款/部分付款/未付款）',
    `invoice_no` VARCHAR(100) DEFAULT NULL COMMENT '发票号码',
    `payment_remarks` TEXT COMMENT '付款备注',
    `recorder_user_id` INT(11) NOT NULL COMMENT '记录人ID，关联user表',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_contract_id` (`contract_id`),
    KEY `idx_recorder_user_id` (`recorder_user_id`),
    KEY `idx_payment_date` (`payment_date`),
    KEY `idx_payment_status` (`payment_status`),
    CONSTRAINT `fk_contract_payment_contract` FOREIGN KEY (`contract_id`) REFERENCES `contract`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_contract_payment_user` FOREIGN KEY (`recorder_user_id`) REFERENCES `user`(`id`) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '合同付款记录表';


