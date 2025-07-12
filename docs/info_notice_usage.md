# 信息提示条使用说明

## 概述

`render_info_notice()` 是一个用于在页面中显示信息提示条的公共函数，支持多种颜色主题和图标。

## 函数签名

```php
render_info_notice($message, $type = 'info', $icon = null)
```

## 参数说明

- `$message` (string): 要显示的提示信息内容
- `$type` (string): 提示类型，可选值：
  - `'success'` - 成功提示（绿色）
  - `'info'` - 信息提示（蓝色，默认）
  - `'warning'` - 警告提示（橙色）
  - `'error'` - 错误提示（红色）
- `$icon` (string, 可选): 图标类名，如 `'icon-search'`, `'icon-list'`, `'icon-edit'`, `'icon-cancel'` 等

## 使用示例

### 基本用法

```php
// 成功提示
render_info_notice("操作成功完成", 'success');

// 信息提示
render_info_notice("这是一般信息");

// 警告提示
render_info_notice("请注意相关事项", 'warning');

// 错误提示
render_info_notice("发生了错误", 'error');
```

### 带图标的用法

```php
// 搜索相关提示
render_info_notice("个人案件查询：只显示您的相关案件", 'success', 'icon-search');

// 列表相关提示
render_info_notice("待配案：只显示未配案的案件", 'warning', 'icon-list');

// 编辑相关提示
render_info_notice("请先选择要编辑的项目", 'info', 'icon-edit');

// 错误相关提示
render_info_notice("操作失败，请重试", 'error', 'icon-cancel');
```

### 动态内容示例

```php
// 包含用户信息的提示
$current_user_name = $user['real_name'];
render_info_notice("个人案件查询（当前用户：" . $current_user_name . "）：只显示您的相关案件", 'success', 'icon-search');

// 包含统计信息的提示
$count = 15;
render_info_notice("共找到 {$count} 个待处理案件", 'warning', 'icon-list');
```

## 样式类型对应关系

| 类型    | 背景色           | 文字色           | 左边框色       | 使用场景           |
| ------- | ---------------- | ---------------- | -------------- | ------------------ |
| success | 浅绿色 (#e8f5e8) | 深绿色 (#2e7d32) | 绿色 (#4caf50) | 成功操作、正常状态 |
| info    | 浅蓝色 (#e3f2fd) | 深蓝色 (#1565c0) | 蓝色 (#2196f3) | 一般信息、说明     |
| warning | 浅橙色 (#fff3e0) | 深橙色 (#e65100) | 橙色 (#ff9800) | 警告、注意事项     |
| error   | 浅红色 (#ffebee) | 深红色 (#c62828) | 红色 (#f44336) | 错误、紧急情况     |

## 常用图标类名

- `icon-search` - 搜索图标 🔍
- `icon-list` - 列表图标 ☰
- `icon-edit` - 编辑图标 ✎
- `icon-save` - 保存图标 ✓
- `icon-cancel` - 取消图标 ✖
- `icon-add` - 添加图标 +

## 替换旧代码

### 替换前（旧的内联样式）

```php
<div style="background:#e8f5e8;padding:8px 12px;margin-bottom:10px;border-radius:4px;color:#2e7d32;font-size:14px;">
    <i class="icon-search"></i> 个人案件查询：只显示您的相关案件
</div>
```

### 替换后（使用新函数）

```php
<?php render_info_notice("个人案件查询：只显示您的相关案件", 'success', 'icon-search'); ?>
```

## 注意事项

1. 使用前请确保已引入 `common/functions.php` 文件
2. 确保页面已加载 `css/module.css` 样式文件
3. 消息内容会自动进行 HTML 转义，防止 XSS 攻击
4. 如果不指定类型，默认使用 'info' 类型
5. 图标参数是可选的，不传入则不显示图标

## 文件位置

- 函数定义：`common/functions.php`
- 样式定义：`css/module.css`
- 测试页面：`test_notice.php`
