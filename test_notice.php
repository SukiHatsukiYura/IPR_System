<?php
include_once(__DIR__ . '/database.php');
include_once(__DIR__ . '/common/functions.php');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>提示条样式测试</title>
    <link rel="stylesheet" href="css/module.css">
</head>
<body>
    <div class="module-panel">
        <h2>提示条样式测试</h2>
        
        <h3>成功提示 (success) - 绿色</h3>
        <?php render_info_notice("这是一个成功提示，用于显示操作成功、正常状态等信息", 'success', 'icon-search'); ?>
        
        <h3>信息提示 (info) - 蓝色</h3>
        <?php render_info_notice("这是一个信息提示，用于显示一般性信息、说明等", 'info', 'icon-list'); ?>
        
        <h3>警告提示 (warning) - 橙色</h3>
        <?php render_info_notice("这是一个警告提示，用于显示需要注意的信息、待处理事项等", 'warning', 'icon-edit'); ?>
        
        <h3>错误提示 (error) - 红色</h3>
        <?php render_info_notice("这是一个错误提示，用于显示错误信息、紧急情况等", 'error', 'icon-cancel'); ?>
        
        <h3>无图标的提示</h3>
        <?php render_info_notice("这是一个没有图标的提示条", 'info'); ?>
        
        <h3>使用示例</h3>
        <pre style="background:#f5f5f5;padding:15px;border-radius:4px;overflow-x:auto;">
// PHP 使用方法：
render_info_notice("提示信息内容", '类型', '图标类名(可选)');

// 可用类型：
'success' - 成功提示（绿色）
'info'    - 信息提示（蓝色）  
'warning' - 警告提示（橙色）
'error'   - 错误提示（红色）

// 示例：
render_info_notice("操作成功完成", 'success', 'icon-search');
render_info_notice("请注意相关事项", 'warning', 'icon-list');
render_info_notice("发生了错误", 'error', 'icon-cancel');
render_info_notice("普通信息提示", 'info');
        </pre>
    </div>
</body>
</html> 