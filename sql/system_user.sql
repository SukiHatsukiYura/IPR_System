-- 角色表
CREATE TABLE `role` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '角色ID',
    `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '角色描述',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '角色表';

-- 初始角色
INSERT INTO `role` (`name`, `description`) VALUES ('admin', '系统管理员');
INSERT INTO `role` (`name`, `description`) VALUES ('user', '用户');
INSERT INTO `role` (`name`, `description`) VALUES ('year_fee', '年费人员');

-- 系统用户表
CREATE TABLE `user` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `real_name` VARCHAR(50) DEFAULT NULL COMMENT '姓名',
    `english_name` VARCHAR(50) DEFAULT NULL COMMENT '英文名',
    `job_number` VARCHAR(30) DEFAULT NULL COMMENT '工号',
    `email` VARCHAR(100) NOT NULL COMMENT '邮箱',
    `gender` TINYINT(1) DEFAULT NULL COMMENT '性别（0女1男）',
    `mobile` VARCHAR(20) DEFAULT NULL COMMENT '手机',
    `phone` VARCHAR(20) DEFAULT NULL COMMENT '电话',
    `birthday` DATE DEFAULT NULL COMMENT '出生日期',
    `major` VARCHAR(50) DEFAULT NULL COMMENT '专业',
    `updated_by` VARCHAR(50) DEFAULT NULL COMMENT '更新用户',
    `address` VARCHAR(200) DEFAULT NULL COMMENT '联系地址',
    `is_agent` TINYINT(1) DEFAULT 0 COMMENT '是否分代理人（0否1是）',
    `role_id` INT(11) DEFAULT NULL COMMENT '用户角色ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
    `workplace` VARCHAR(100) DEFAULT NULL COMMENT '工作地',
    `department_info` VARCHAR(200) DEFAULT NULL COMMENT '部门信息',
    `remark` TEXT COMMENT '备注',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否在职（0否1是）',
    PRIMARY KEY (`id`),
    -- 关联角色表
    FOREIGN KEY (`role_id`) REFERENCES `role`(`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '系统用户表';

-- 初始用户
-- 用户名：admin 密码：admin，角色：admin，邮箱：admin@admin.com，使用MD5加密
INSERT INTO `user` (`username`, `password`, `real_name`, `role_id`, `email`) VALUES ('admin', MD5('admin'), '系统管理员', 1, 'admin@admin.com');
