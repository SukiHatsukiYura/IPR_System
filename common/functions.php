<?php
// 通用htmlspecialchars函数,改成如果引用的页面已经有了这个函数，则不重复定义
if (!function_exists('h')) {
    function h($v)
    {
        return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// 通用alert提示条相关函数
function render_alert_assets()
{
    static $loaded = false;
    if ($loaded) return; // 防止重复输出
    $loaded = true;
    echo '<style>
    .alert { padding: 10px 18px; border-radius: 4px; margin-bottom: 14px; text-align: center; font-size: 15px; position: relative; }
    .alert-error { background: #ffeaea; color: #d32f2f; border: 1px solid #ffcdd2; }
    .alert-success { background: #e8f5e9; color: #388e3c; border: 1px solid #c8e6c9; }
    .alert-close { position: absolute; right: 12px; top: 8px; font-size: 18px; color: #888; cursor: pointer; line-height: 1; }
    .alert-close:hover { color: #d32f2f; }
    </style>';
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var alerts = document.querySelectorAll(".alert");
        alerts.forEach(function(alert) {
            var closeBtn = alert.querySelector(".alert-close");
            if (closeBtn) {
                closeBtn.addEventListener("click", function() {
                    alert.style.display = "none";
                });
            }
            setTimeout(function() {
                if (alert) alert.style.display = "none";
            }, 5000);
        });
    });
    </script>';
}

// 通用alert提示条
function render_alert($msg, $type = 'success')
{
    if (!$msg) return;
    $type_class = $type === 'error' ? 'alert-error' : 'alert-success';
    echo '<div class="alert ' . $type_class . '"><span class="alert-close">×</span>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</div>';
}


// 输出搜索下拉和多选下拉的JS（只输出一次）
function render_select_search_assets()
{
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    // 使用heredoc语法输出JavaScript代码，避免拼接问题
    echo <<<JS
<script>
// 设置全局状态变量
window.moduleDropdownState = {
    openedDropdown: null,
    isProcessingClick: false
};

// 初始化函数
function initSelectSearchBoxes() {
    // 单选下拉框初始化
    document.querySelectorAll('.module-select-search-box').forEach(function(box) {
        if (box.dataset.initialized === 'true') return;
        box.dataset.initialized = 'true';
        
        var input = box.querySelector('.module-select-search-input');
        var hiddenInput = box.querySelector('input[type="hidden"]');
        var list = box.querySelector('.module-select-search-list');
        var searchInput = box.querySelector('.module-select-search-list-input');
        var items = box.querySelectorAll('.module-select-search-item');
        
        // 设置输入框为可点击样式
        input.style.cursor = 'pointer';
        
        // 点击输入框显示下拉列表
        input.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // 关闭其他下拉框
            closeAllDropdowns();
            
            // 显示当前下拉框
            list.style.display = 'block';
            window.moduleDropdownState.openedDropdown = list;
            
            // 清空搜索框并聚焦
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
                
                // 显示所有选项
                items.forEach(function(item) {
                    item.style.display = 'block';
                });
            }
        });
        
        // 搜索框输入事件
        if (searchInput) {
            searchInput.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            searchInput.addEventListener('input', function() {
                var filter = this.value.toLowerCase();
                items.forEach(function(item) {
                    var text = item.textContent.toLowerCase();
                    item.style.display = text.indexOf(filter) > -1 ? 'block' : 'none';
                });
            });
        }
        
        // 点击选项
        items.forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // 设置值
                var value = item.getAttribute('data-value');
                var text = item.textContent;
                
                if (text === '--请选择--') {
                    input.value = '';
                    hiddenInput.value = '';
                } else {
                    input.value = text;
                    hiddenInput.value = value;
                }
                
                // 关闭下拉框
                list.style.display = 'none';
                window.moduleDropdownState.openedDropdown = null;
            });
        });
    });
    
    // 多选下拉框初始化
    document.querySelectorAll('.module-select-search-multi-box').forEach(function(box) {
        if (box.dataset.initialized === 'true') return;
        box.dataset.initialized = 'true';
        
        var input = box.querySelector('.module-select-search-multi-input');
        var hiddenInput = box.querySelector('input[type="hidden"]');
        var list = box.querySelector('.module-select-search-multi-list');
        var searchInput = box.querySelector('.module-select-search-multi-list-input');
        var items = box.querySelectorAll('.module-select-search-multi-item');
        var checkboxes = box.querySelectorAll('.module-select-search-multi-item input[type="checkbox"]');
        var btnSelectAll = box.querySelector('.btn-multi-select-all');
        var btnClear = box.querySelector('.btn-multi-clear');
        
        // 设置输入框为可点击样式
        input.style.cursor = 'pointer';
        
        // 更新输入框显示
        function updateDisplay() {
            var selectedTexts = [];
            var selectedValues = [];
            
            checkboxes.forEach(function(checkbox) {
                if (checkbox.checked) {
                    selectedTexts.push(checkbox.parentNode.textContent.trim());
                    selectedValues.push(checkbox.value);
                }
            });
            
            input.value = selectedTexts.join(', ');
            hiddenInput.value = selectedValues.join(',');
        }
        
        // 点击输入框显示下拉列表
        input.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // 关闭其他下拉框
            closeAllDropdowns();
            
            // 显示当前下拉框
            list.style.display = 'block';
            window.moduleDropdownState.openedDropdown = list;
            
            // 清空搜索框并聚焦
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
                
                // 显示所有选项
                items.forEach(function(item) {
                    item.style.display = 'block';
                });
            }
        });
        
        // 搜索框输入事件
        if (searchInput) {
            searchInput.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            searchInput.addEventListener('input', function() {
                var filter = this.value.toLowerCase();
                items.forEach(function(item) {
                    var text = item.textContent.toLowerCase();
                    item.style.display = text.indexOf(filter) > -1 ? 'block' : 'none';
                });
            });
        }
        
        // 复选框变更事件
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function(e) {
                e.stopPropagation();
                updateDisplay();
            });
        });
        
        // 点击项目区域（非复选框）
        items.forEach(function(item) {
            item.addEventListener('click', function(e) {
                if (e.target.type !== 'checkbox') {
                    e.stopPropagation();
                    
                    var checkbox = item.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        updateDisplay();
                    }
                }
            });
        });
        
        // 全选按钮
        if (btnSelectAll) {
            btnSelectAll.addEventListener('click', function(e) {
                e.stopPropagation();
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = true;
                });
                updateDisplay();
            });
        }
        
        // 清除按钮
        if (btnClear) {
            btnClear.addEventListener('click', function(e) {
                e.stopPropagation();
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = false;
                });
                updateDisplay();
            });
        }
        
        // 列表点击事件阻止冒泡
        list.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // 初始化更新显示
        updateDisplay();
    });
    
    // 为整个下拉列表添加阻止冒泡
    document.querySelectorAll('.module-select-search-list, .module-select-search-multi-list').forEach(function(list) {
        list.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
}

// 关闭所有下拉框
function closeAllDropdowns() {
    document.querySelectorAll('.module-select-search-list, .module-select-search-multi-list').forEach(function(list) {
        list.style.display = 'none';
    });
    window.moduleDropdownState.openedDropdown = null;
}

// 点击页面其他地方关闭下拉框
document.addEventListener('click', function() {
    closeAllDropdowns();
});

// 页面加载完成时初始化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSelectSearchBoxes);
} else {
    initSelectSearchBoxes();
}

// 监听DOM变化自动初始化新添加的下拉框
if (typeof MutationObserver !== 'undefined') {
    var observer = new MutationObserver(function(mutations) {
        var shouldInit = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                shouldInit = true;
            }
        });
        
        if (shouldInit) {
            initSelectSearchBoxes();
        }
    });
    
    // 开始监视
    observer.observe(document.body, { childList: true, subtree: true });
}

// 全局初始化函数
window.initSelectSearchControls = function() {
    // 重置初始化标志
    document.querySelectorAll('.module-select-search-box, .module-select-search-multi-box').forEach(function(box) {
        box.dataset.initialized = 'false';
    });
    
    // 执行初始化
    initSelectSearchBoxes();
};
</script>
JS;
}

// 单选搜索下拉框
function render_select_search($name, $options, $selected = '')
{
    $display = '';
    foreach ($options as $val => $text) {
        if ((string)$val === (string)$selected) {
            $display = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            break;
        }
    }
    echo '<div class="module-select-search-box">';
    echo '<input type="text" class="module-input module-select-search-input" name="' . $name . '_display" value="' . $display . '" readonly placeholder="点击选择" style="background-color: #fff;">';
    echo '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($selected, ENT_QUOTES, 'UTF-8') . '">';
    echo '<div class="module-select-search-list" style="display:none;">';
    echo '<input type="text" class="module-select-search-list-input" placeholder="搜索">';
    echo '<div class="module-select-search-list-items">';
    foreach ($options as $val => $text) {
        echo '<div class="module-select-search-item" data-value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</div>';
    }
    echo '</div></div></div>';
}

// 多选搜索下拉框
function render_select_search_multi($name, $options, $selecteds = array())
{
    if (!is_array($selecteds)) $selecteds = explode(',', $selecteds);
    $display = array();
    foreach ($options as $val => $text) {
        if (in_array((string)$val, $selecteds, true)) {
            $display[] = $text;
        }
    }
    echo '<div class="module-select-search-multi-box">';
    echo '<input type="text" class="module-input module-select-search-multi-input" name="' . $name . '_display" value="' . htmlspecialchars(join(", ", $display), ENT_QUOTES, 'UTF-8') . '" readonly placeholder="请选择/搜索" style="background-color: #fff;">';
    echo '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars(join(",", $selecteds), ENT_QUOTES, 'UTF-8') . '">';
    echo '<div class="module-select-search-multi-list" style="display:none;">';
    echo '<div class="multi-ops"><button type="button" class="btn-mini btn-multi-select-all">全选</button><button type="button" class="btn-mini btn-multi-clear">清除</button></div>';
    echo '<input type="text" class="module-select-search-multi-list-input" placeholder="搜索">';
    echo '<div class="module-select-search-multi-list-items">';
    foreach ($options as $val => $text) {
        $checked = in_array((string)$val, $selecteds, true) ? 'checked' : '';
        echo '<div class="module-select-search-multi-item"><label><input type="checkbox" value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '" ' . $checked . '> ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</label></div>';
    }
    echo '</div></div></div>';
}

// 通用分页渲染函数
function render_pagination($total, $page, $page_size, $total_pages, $params = [])
{
    // 保留原有参数，替换p和page_size
    $base_params = $params;
    $base_params['page_size'] = $page_size;
    $html = '';
    $html .= '<span>共 <span id="total-records">' . $total . '</span> 条记录，每页</span>';
    // 每页条数选择
    $html .= '<form method="get" style="display:inline;">';
    foreach ($base_params as $k => $v) {
        if ($k !== 'page_size' && $k !== 'p') {
            $html .= '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '">';
        }
    }
    $html .= '<select id="page-size-select" name="page_size" onchange="this.form.submit()">';
    foreach ([10, 20, 50, 100] as $size) {
        $html .= '<option value="' . $size . '"' . ($page_size == $size ? ' selected' : '') . '>' . $size . '</option>';
    }
    $html .= '</select>';
    $html .= '</form>';
    $html .= '<span>条，当前 <span id="current-page">' . $page . '</span>/<span id="total-pages">' . $total_pages . '</span> 页</span>';
    // 分页按钮
    function pager_url($pageno, $page_size, $params)
    {
        $params['p'] = $pageno;
        $params['page_size'] = $page_size;
        return '?' . http_build_query($params);
    }
    $html .= '<a href="' . ($page > 1 ? pager_url(1, $page_size, $base_params) : 'javascript:void(0);') . '" class="btn-page-go"' . ($page <= 1 ? ' disabled' : '') . '>首页</a>';
    $html .= '<a href="' . ($page > 1 ? pager_url($page - 1, $page_size, $base_params) : 'javascript:void(0);') . '" class="btn-page-go"' . ($page <= 1 ? ' disabled' : '') . '>上一页</a>';
    $html .= '<a href="' . ($page < $total_pages ? pager_url($page + 1, $page_size, $base_params) : 'javascript:void(0);') . '" class="btn-page-go"' . ($page >= $total_pages ? ' disabled' : '') . '>下一页</a>';
    $html .= '<a href="' . ($page < $total_pages ? pager_url($total_pages, $page_size, $base_params) : 'javascript:void(0);') . '" class="btn-page-go"' . ($page >= $total_pages ? ' disabled' : '') . '>末页</a>';
    // 跳转form
    $html .= '<span>跳转到</span>';
    $html .= '<form method="get" style="display:inline;">';
    foreach ($base_params as $k => $v) {
        if ($k !== 'p' && $k !== 'page_size') {
            $html .= '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '">';
        }
    }
    $html .= '<input type="hidden" name="page_size" value="' . $page_size . '">';
    $html .= '<input type="number" name="p" min="1" max="' . $total_pages . '" value="' . $page . '" style="width:48px;">';
    $html .= '<span>页</span>';
    $html .= '<button type="submit" class="btn-page-go">确定</button>';
    $html .= '</form>';
    return $html;
}
