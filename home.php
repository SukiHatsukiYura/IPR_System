<?php
// 首页内容，仿照原系统布局
?>
<div class="homepage-panel">
    <!-- 待办事项 -->
    <div class="panel collapsible-panel">
        <div class="panel-header collapsible-header" data-target="todo-content">
            <span class="collapse-arrow">&#9660;</span>
            <h3 class="panel-title"><i class="icon-todo"></i> 待办事项</h3>
        </div>
        <div class="panel-content collapsible-content" id="todo-content">
            <div class="panel-empty">暂无待办事项</div>
        </div>
    </div>

    <!-- 进行中的任务 -->
    <div class="panel collapsible-panel">
        <div class="panel-header collapsible-header" data-target="task-content">
            <span class="collapse-arrow">&#9660;</span>
            <h3 class="panel-title"><i class="icon-task"></i> 进行中的任务</h3>
        </div>
        <div class="panel-content collapsible-content" id="task-content">
            <div class="task-cards">
                <div class="task-card">
                    <div class="task-icon orange"><span class="icon-clock"></span>1</div>
                    <div class="task-name">内部期限3天内</div>
                    <div class="task-count">(0)</div>
                </div>
                <div class="task-card">
                    <div class="task-icon orange-dark"><span class="icon-clock"></span>1</div>
                    <div class="task-name">内部期限7天内</div>
                    <div class="task-count">(0)</div>
                </div>
                <div class="task-card">
                    <div class="task-icon red"><span class="icon-clock"></span>3</div>
                    <div class="task-name">客户期限3天内</div>
                    <div class="task-count">(0)</div>
                </div>
                <div class="task-card">
                    <div class="task-icon purple"><span class="icon-clock"></span>7</div>
                    <div class="task-name">客户期限7天内</div>
                    <div class="task-count">(0)</div>
                </div>
                <div class="task-card">
                    <div class="task-icon green"><span class="icon-clock"></span>3</div>
                    <div class="task-name">官方期限3天内</div>
                    <div class="task-count">(0)</div>
                </div>
                <div class="task-card">
                    <div class="task-icon blue"><span class="icon-clock"></span>7</div>
                    <div class="task-name">官方期限7天内</div>
                    <div class="task-count">(0)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 统计报表 -->
    <div class="panel collapsible-panel">
        <div class="panel-header collapsible-header" data-target="report-content">
            <span class="collapse-arrow">&#9660;</span>
            <h3 class="panel-title"><i class="icon-report"></i> 统计报表</h3>
        </div>
        <div class="panel-content collapsible-content" id="report-content">
            <div class="panel-empty">暂无相关报表</div>
        </div>
    </div>
</div>

<style>
    .homepage-panel {
        padding: 18px 18px 0 18px;
    }

    .panel {
        background: #fff;
        margin-bottom: 18px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    .panel-header {
        padding: 12px 18px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
        flex-direction: row;
        justify-content: flex-start;
        background: #f5f5f5;
    }

    .panel-title {
        margin: 0;
        font-size: 17px;
        /* color: #29b6b0; */
        color: #000;
        display: flex;
        align-items: center;
    }

    .panel-title i {
        margin-right: 7px;
        font-size: 18px;
    }

    .collapse-arrow {
        margin-left: 0;
        margin-right: 10px;
        font-size: 16px;
        color: #bbb;
        transition: transform 0.2s;
    }

    .panel-header.collapsed .collapse-arrow {
        transform: rotate(-90deg);
    }

    .panel-content {
        padding: 18px;
        transition: max-height 0.2s, padding 0.2s;
        overflow: hidden;
    }

    .panel-content.collapsed {
        max-height: 0;
        padding-top: 0;
        padding-bottom: 0;
        display: none;
    }

    .panel-empty {
        text-align: center;
        color: #bbb;
        padding: 28px 0;
    }

    .task-cards {
        display: flex;
        justify-content: flex-start;
        flex-wrap: wrap;
        gap: 18px;
    }

    .task-card {
        text-align: center;
        width: 120px;
        margin-bottom: 10px;
    }

    .task-icon {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 10px;
        color: #fff;
        font-size: 20px;
        position: relative;
    }

    .task-icon .icon-clock {
        display: inline-block;
        margin-right: 2px;
        font-size: 18px;
    }

    .orange {
        background: #ff9800;
    }

    .orange-dark {
        background: #f57c00;
    }

    .red {
        background: #f44336;
    }

    .purple {
        background: #9c27b0;
    }

    .green {
        background: #4caf50;
    }

    .blue {
        background: #2196f3;
    }
</style>