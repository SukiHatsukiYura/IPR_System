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

// 记录用户登录日志函数
function log_user_login($pdo, $user_id, $username, $login_status = 1, $failure_reason = null)
{
    try {
        // 获取用户IP地址
        $login_ip = get_client_ip();

        // 获取用户代理信息
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // 解析设备类型和浏览器信息
        $device_info = parse_user_agent($user_agent);

        // 获取会话ID
        $session_id = session_id();

        // 开始事务
        $pdo->beginTransaction();

        // 插入登录日志
        $sql = "INSERT INTO user_login_log (
            user_id, username, login_time, login_ip, user_agent, 
            login_status, failure_reason, session_id, device_type, 
            browser_name, os_name
        ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $user_id,
            $username,
            $login_ip,
            $user_agent,
            $login_status,
            $failure_reason,
            $session_id,
            $device_info['device_type'],
            $device_info['browser_name'],
            $device_info['os_name']
        ]);

        // 更新登录次数统计
        if ($result && $user_id > 0) {
            update_login_stats($pdo, $user_id, $username, $login_status, $login_ip);
        }

        // 提交事务
        $pdo->commit();

        return $result;
    } catch (Exception $e) {
        // 回滚事务
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // 记录日志失败不应该影响登录流程，只记录错误
        error_log("登录日志记录失败: " . $e->getMessage());
        return false;
    }
}

// 获取客户端真实IP地址
function get_client_ip()
{
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // 处理多个IP的情况（代理链）
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }

    // 验证IP格式
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    return $ip;
}

// 解析用户代理信息
function parse_user_agent($user_agent)
{
    $device_type = 'PC';
    $browser_name = 'Unknown';
    $os_name = 'Unknown';

    if (empty($user_agent)) {
        return [
            'device_type' => $device_type,
            'browser_name' => $browser_name,
            'os_name' => $os_name
        ];
    }

    $user_agent = strtolower($user_agent);

    // 检测设备类型
    if (
        strpos($user_agent, 'mobile') !== false ||
        strpos($user_agent, 'android') !== false ||
        strpos($user_agent, 'iphone') !== false ||
        strpos($user_agent, 'ipod') !== false
    ) {
        $device_type = 'Mobile';
    } elseif (
        strpos($user_agent, 'tablet') !== false ||
        strpos($user_agent, 'ipad') !== false
    ) {
        $device_type = 'Tablet';
    }

    // 检测浏览器
    if (strpos($user_agent, 'edge') !== false) {
        $browser_name = 'Edge';
    } elseif (strpos($user_agent, 'chrome') !== false) {
        $browser_name = 'Chrome';
    } elseif (strpos($user_agent, 'firefox') !== false) {
        $browser_name = 'Firefox';
    } elseif (strpos($user_agent, 'safari') !== false) {
        $browser_name = 'Safari';
    } elseif (strpos($user_agent, 'opera') !== false || strpos($user_agent, 'opr') !== false) {
        $browser_name = 'Opera';
    } elseif (strpos($user_agent, 'msie') !== false || strpos($user_agent, 'trident') !== false) {
        $browser_name = 'IE';
    }

    // 检测操作系统
    if (strpos($user_agent, 'windows') !== false) {
        $os_name = 'Windows';
    } elseif (strpos($user_agent, 'macintosh') !== false || strpos($user_agent, 'mac os') !== false) {
        $os_name = 'macOS';
    } elseif (strpos($user_agent, 'linux') !== false) {
        $os_name = 'Linux';
    } elseif (strpos($user_agent, 'android') !== false) {
        $os_name = 'Android';
    } elseif (strpos($user_agent, 'iphone') !== false || strpos($user_agent, 'ipad') !== false) {
        $os_name = 'iOS';
    }

    return [
        'device_type' => $device_type,
        'browser_name' => $browser_name,
        'os_name' => $os_name
    ];
}

// 更新用户登录次数统计
function update_login_stats($pdo, $user_id, $username, $login_status, $login_ip)
{
    try {
        $current_date = date('Y-m-d');
        $current_month = date('Y-m');

        // 检查是否已存在统计记录
        $check_sql = "SELECT id, last_update_date, consecutive_failed_count FROM user_login_stats WHERE user_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$user_id]);
        $stats = $check_stmt->fetch();

        if ($stats) {
            // 更新现有记录
            $updates = [];
            $params = [];

            // 基础更新字段
            $updates[] = "total_login_count = total_login_count + 1";
            $updates[] = "last_login_time = NOW()";
            $updates[] = "last_login_ip = ?";
            $updates[] = "updated_at = NOW()";
            $params[] = $login_ip;

            // 根据登录状态更新
            if ($login_status == 1) {
                // 登录成功
                $updates[] = "success_login_count = success_login_count + 1";
                $updates[] = "last_success_login_time = NOW()";
                $updates[] = "consecutive_failed_count = 0"; // 重置连续失败次数
            } else {
                // 登录失败
                $updates[] = "failed_login_count = failed_login_count + 1";
                $updates[] = "last_failed_login_time = NOW()";
                $updates[] = "consecutive_failed_count = consecutive_failed_count + 1";
            }

            // 检查是否需要重置今日和本月计数
            if ($stats['last_update_date'] !== $current_date) {
                // 跨日了，重置今日计数
                $updates[] = "today_login_count = 1";
                $updates[] = "last_update_date = ?";
                $params[] = $current_date;

                // 检查是否跨月
                if (substr($stats['last_update_date'], 0, 7) !== $current_month) {
                    $updates[] = "this_month_login_count = 1";
                } else {
                    $updates[] = "this_month_login_count = this_month_login_count + 1";
                }
            } else {
                // 同一天，增加今日和本月计数
                $updates[] = "today_login_count = today_login_count + 1";
                $updates[] = "this_month_login_count = this_month_login_count + 1";
            }

            $params[] = $user_id;
            $update_sql = "UPDATE user_login_stats SET " . implode(', ', $updates) . " WHERE user_id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute($params);
        } else {
            // 创建新记录
            $insert_sql = "INSERT INTO user_login_stats (
                user_id, username, total_login_count, success_login_count, failed_login_count,
                last_login_time, last_login_ip, last_success_login_time, last_failed_login_time,
                consecutive_failed_count, today_login_count, this_month_login_count, last_update_date
            ) VALUES (?, ?, 1, ?, ?, NOW(), ?, ?, ?, ?, 1, 1, ?)";

            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([
                $user_id,
                $username,
                $login_status == 1 ? 1 : 0, // success_login_count
                $login_status == 0 ? 1 : 0, // failed_login_count
                $login_ip,
                $login_status == 1 ? date('Y-m-d H:i:s') : null, // last_success_login_time
                $login_status == 0 ? date('Y-m-d H:i:s') : null, // last_failed_login_time
                $login_status == 0 ? 1 : 0, // consecutive_failed_count
                $current_date
            ]);
        }

        return true;
    } catch (Exception $e) {
        error_log("登录次数统计更新失败: " . $e->getMessage());
        throw $e; // 重新抛出异常，让事务回滚
    }
}

// 记录用户退出日志
function log_user_logout($pdo, $user_id, $session_id)
{
    try {
        // 更新最近的登录记录，设置退出时间和会话持续时间
        $sql = "UPDATE user_login_log SET 
                logout_time = NOW(),
                session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW())
                WHERE user_id = ? AND session_id = ? AND logout_time IS NULL
                ORDER BY login_time DESC LIMIT 1";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$user_id, $session_id]);
    } catch (Exception $e) {
        error_log("退出日志记录失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 渲染信息提示条
 * @param string $message 提示信息内容
 * @param string $type 提示类型 (success/info/warning/error)
 * @param string $icon 图标类名 (可选，如 'icon-search', 'icon-list' 等)
 * @return void
 */
function render_info_notice($message, $type = 'info', $icon = null)
{
    $type_classes = [
        'success' => 'module-notice-success',
        'info' => 'module-notice-info',
        'warning' => 'module-notice-warning',
        'error' => 'module-notice-error'
    ];

    $class = isset($type_classes[$type]) ? $type_classes[$type] : $type_classes['info'];

    echo '<div class="module-notice ' . $class . '">';
    if ($icon) {
        echo '<i class="' . htmlspecialchars($icon) . '"></i> ';
    }
    echo htmlspecialchars($message);
    echo '</div>';
}
