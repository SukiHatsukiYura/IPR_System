-- 修改专利案件申请人表结构
-- 1. 删除外键约束
ALTER TABLE
    `patent_case_applicant` DROP FOREIGN KEY `fk_patent_applicant_customer`;

-- 2. 删除客户ID字段
ALTER TABLE
    `patent_case_applicant` DROP COLUMN `customer_id`;

-- 3. 删除客户ID索引
ALTER TABLE
    `patent_case_applicant` DROP INDEX `idx_customer_id`;

-- 4. 修改表注释
ALTER TABLE
    `patent_case_applicant` COMMENT = '专利案件申请人表';