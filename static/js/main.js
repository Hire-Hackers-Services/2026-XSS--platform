/**
 * 小绿茶XSS反连平台 - 主要JavaScript功能
 * 作者: AI Assistant
 * 版本: 1.0.0
 */

// 全局变量
let currentUser = null;
let isAuthenticated = false;

// 初始化应用
$(document).ready(function() {
    initializeApp();
    setupEventListeners();
    checkAuthentication();
});

/**
 * 初始化应用
 */
function initializeApp() {
    // 设置AJAX全局配置
    $.ajaxSetup({
        timeout: 30000,
        cache: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });
    
    // 显示当前时间
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
    
    // 初始化工具提示
    initTooltips();
    
    // 初始化通知系统
    initNotificationSystem();
    // 每3秒检查一次新日志
    setInterval(checkNewLogs, 3000);
}

/**
 * 设置事件监听器
 */
function setupEventListeners() {
    // 全局错误处理
// 全局错误处理
$(document).ajaxError(function(event, xhr, settings, error) {
    console.error('AJAX Error:', error);
    
    // 忽略登录页面的请求
    if (window.location.pathname === '/login') {
        return;
    }
    
    if (xhr.status === 401) {
        // 检查是否已经在登录页面，避免循环重定向
        if (window.location.pathname !== '/login') {
            // 清除认证状态
            isAuthenticated = false;
            currentUser = null;
            
            // 如果是认证检查请求失败，直接跳转到登录页面
            if (settings.url.includes('/api/auth/check')) {
                window.location.href = '/login';
            } else {
                showAlert('会话已过期，请重新登录', 'warning');
                setTimeout(function() {
                    window.location.href = '/login';
                }, 2000);
            }
        }
    } else if (xhr.status === 403) {
        showAlert('权限不足', 'danger');
    } else if (xhr.status >= 500) {
        showAlert('服务器错误，请稍后重试', 'danger');
    }
});
    
    // 键盘快捷键
    $(document).keydown(function(e) {
        // Ctrl+S 保存 (在编辑器页面)
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            if (typeof savePayload === 'function') {
                savePayload();
            }
        }
        
        // Ctrl+R 刷新 (不在输入框中时)
        if (e.ctrlKey && e.key === 'r' && !$('input, textarea').is(':focus')) {
            e.preventDefault();
            if (typeof refreshLogs === 'function') {
                refreshLogs();
            }
        }
        
        // Escape 键关闭模态框
        if (e.key === 'Escape') {
            $('.modal').modal('hide');
        }
    });
    
    // 页面可见性变化处理
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && isAuthenticated) {
            // 页面重新可见时刷新数据
            refreshCurrentPageData();
        }
    });
}

/**
 * 检查用户认证状态
 */
function checkAuthentication() {
    $.get('/api/auth/check', function(response) {
        if (response.authenticated) {
            currentUser = response.user;
            isAuthenticated = true;
            updateUserInterface();
        } else {
            isAuthenticated = false;
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
    }).fail(function() {
        isAuthenticated = false;
        if (window.location.pathname !== '/login') {
            window.location.href = '/login';
        }
    });
}

/**
 * 更新用户界面
 */
function updateUserInterface() {
    if (currentUser) {
        $('.username-display').text(currentUser.username || '管理员');
        $('.user-avatar').attr('title', currentUser.username || '管理员');
    }
}

/**
 * 更新当前时间
 */
function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    $('#current-time').text(timeString);
}

/**
 * 初始化工具提示
 */
function initTooltips() {
    // 初始化Bootstrap工具提示
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * 初始化通知系统
 */
function initNotificationSystem() {
    // 创建通知容器
    if ($('#notification-container').length === 0) {
        $('body').append('<div id="notification-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>');
    }
}

/**
 * 显示通知消息
 * @param {string} message - 消息内容
 * @param {string} type - 消息类型 (success, danger, warning, info)
 * @param {number} duration - 显示时长(毫秒)
 */
function showAlert(message, type = 'info', duration = 3000) {
    const alertId = 'alert-' + Date.now();
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas ${getAlertIcon(type)}"></i>
            <span class="ms-2">${escapeHtml(message)}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('#notification-container').append(alertHtml);
    
    // 自动移除
    if (duration > 0) {
        setTimeout(function() {
            $(`#${alertId}`).alert('close');
        }, duration);
    }
    
    // 滚动到顶部
    $('html, body').animate({scrollTop: 0}, 'fast');
}

/**
 * 获取通知图标
 */
function getAlertIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        danger: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

/**
 * HTML转义函数
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * 刷新当前页面数据
 */
function refreshCurrentPageData() {
    const currentPath = window.location.pathname;
    
    if (currentPath.includes('/admin/logs')) {
        if (typeof loadLogs === 'function') {
            loadLogs();
        }
    } else if (currentPath.includes('/admin/dashboard')) {
        if (typeof loadDashboardData === 'function') {
            loadDashboardData();
        }
    } else if (currentPath.includes('/admin/templates')) {
        if (typeof loadTemplates === 'function') {
            loadTemplates();
        }
    } else if (currentPath.includes('/admin/payloads')) {
        if (typeof loadPayloads === 'function') {
            loadPayloads();
        }
    }
}

/**
 * 显示加载状态
 */
function showLoading(containerId, message = '加载中...') {
    const loadingHtml = `
        <div class="text-center py-4">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">${message}</span>
            </div>
            <p class="text-muted mt-2">${message}</p>
        </div>
    `;
    $(`#${containerId}`).html(loadingHtml);
}

/**
 * 隐藏加载状态
 */
function hideLoading(containerId) {
    $(`#${containerId}`).empty();
}


/**
 * 检查新日志
 */
function checkNewLogs() {
    if (!isAuthenticated) return;
    
    $.get('/api/logs/check-new', function(response) {
        if (response.has_new) {
            showAlert('🍃 检测到新的XSS反向连接！', 'success', 5000);
            
            // 如果当前在日志页面，自动刷新
            if (window.location.pathname.includes('/admin/logs')) {
                if (typeof loadLogs === 'function') {
                    loadLogs(false); // 不显示加载动画
                }
            }
            
            // 如果当前在仪表板页面，刷新统计数据
            if (window.location.pathname.includes('/admin/dashboard')) {
                if (typeof loadDashboardData === 'function') {
                    loadDashboardData();
                }
            }
        }
    }).fail(function() {
        // 静默失败，不显示错误
    });
}

/**
 * 格式化文件大小
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * 格式化日期时间
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

/**
 * 格式化相对时间
 */
function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return '刚刚';
    } else if (diffInSeconds < 3600) {
        return Math.floor(diffInSeconds / 60) + ' 分钟前';
    } else if (diffInSeconds < 86400) {
        return Math.floor(diffInSeconds / 3600) + ' 小时前';
    } else if (diffInSeconds < 2592000) {
        return Math.floor(diffInSeconds / 86400) + ' 天前';
    } else {
        return formatDateTime(dateString);
    }
}

/**
 * 复制到剪贴板
 */
function copyToClipboard(text, successMessage = '已复制到剪贴板') {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function() {
            showAlert(successMessage, 'success');
        }).catch(function() {
            fallbackCopyTextToClipboard(text, successMessage);
        });
    } else {
        fallbackCopyTextToClipboard(text, successMessage);
    }
}

/**
 * 备用复制方法
 */
function fallbackCopyTextToClipboard(text, successMessage) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.top = '0';
    textArea.style.left = '0';
    textArea.style.position = 'fixed';
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showAlert(successMessage, 'success');
    } catch (err) {
        showAlert('复制失败，请手动复制', 'danger');
    }
    
    document.body.removeChild(textArea);
}

/**
 * 下载文件
 */
function downloadFile(content, filename, contentType = 'text/plain') {
    const blob = new Blob([content], { type: contentType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

/**
 * 上传文件
 */
function uploadFile(inputElement, callback) {
    const file = inputElement.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        callback(e.target.result, file);
    };
    reader.onerror = function() {
        showAlert('文件读取失败', 'danger');
    };
    reader.readAsText(file);
}

/**
 * 防抖函数
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * 节流函数
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * 验证IP地址
 */
function isValidIP(ip) {
    const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
    return ipRegex.test(ip);
}

/**
 * 验证URL
 */
function isValidURL(url) {
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
}

/**
 * 验证邮箱地址
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * 生成UUID
 */
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

/**
 * 获取随机颜色
 */
function getRandomColor() {
    const colors = [
        '#28a745', '#007bff', '#ffc107', '#dc3545', '#6f42c1',
        '#20c997', '#fd7e14', '#e83e8c', '#6c757d', '#17a2b8'
    ];
    return colors[Math.floor(Math.random() * colors.length)];
}

/**
 * 本地存储操作
 */
const Storage = {
    set: function(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error('Storage set error:', e);
        }
    },
    
    get: function(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            console.error('Storage get error:', e);
            return defaultValue;
        }
    },
    
    remove: function(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {
            console.error('Storage remove error:', e);
        }
    },
    
    clear: function() {
        try {
            localStorage.clear();
        } catch (e) {
            console.error('Storage clear error:', e);
        }
    }
};

/**
 * 会话存储操作
 */
const SessionStorage = {
    set: function(key, value) {
        try {
            sessionStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error('SessionStorage set error:', e);
        }
    },
    
    get: function(key, defaultValue = null) {
        try {
            const item = sessionStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            console.error('SessionStorage get error:', e);
            return defaultValue;
        }
    },
    
    remove: function(key) {
        try {
            sessionStorage.removeItem(key);
        } catch (e) {
            console.error('SessionStorage remove error:', e);
        }
    },
    
    clear: function() {
        try {
            sessionStorage.clear();
        } catch (e) {
            console.error('SessionStorage clear error:', e);
        }
    }
};

/**
 * 导出函数到全局作用域
 */
window.showAlert = showAlert;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.copyToClipboard = copyToClipboard;
window.downloadFile = downloadFile;
window.uploadFile = uploadFile;
window.formatFileSize = formatFileSize;
window.formatDateTime = formatDateTime;
window.formatRelativeTime = formatRelativeTime;
window.escapeHtml = escapeHtml;
window.generateUUID = generateUUID;
window.getRandomColor = getRandomColor;
window.Storage = Storage;
window.SessionStorage = SessionStorage;



/**
 * 修改密码
 */
function changePassword() {
    const oldPassword = $('#oldPassword').val();
    const newPassword = $('#newPassword').val();
    const confirmPassword = $('#confirmPassword').val();
    
    if (!oldPassword || !newPassword || !confirmPassword) {
        showAlert('请填写完整的密码信息', 'warning');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showAlert('新密码和确认密码不一致', 'danger');
        return;
    }
    
    if (newPassword.length < 4) {
        showAlert('新密码长度至少4位', 'warning');
        return;
    }
    
    // 显示加载状态
    showAlert('正在修改密码...', 'info', 0);
    
    $.ajax({
        url: '/api/settings',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            password_change: {
                old_password: oldPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            }
        }),
        success: function(response) {
            if (response.success) {
                showAlert('密码修改成功，正在退出登录...', 'success');
                // 清空密码字段
                $('#oldPassword, #newPassword, #confirmPassword').val('');
                
                // 直接重定向到退出登录页面
                setTimeout(function() {
                    window.location.href = '/logout';
                }, 1500);
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            let errorMsg = '密码修改失败，请重试';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showAlert(errorMsg, 'danger');
        }
    });
}