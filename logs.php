<?php
/**
 * 日志查看页面
 */
require_once 'config.php';
session_start();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!checkIpWhitelist()) {
    http_response_code(403);
    die('IP地址 ' . $_SERVER['REMOTE_ADDR'] . ' 不在白名单中');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>日志管理 - <?php echo APP_NAME; ?></title>
    <link href="static/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="static/css/style.css">
    <style>
        /* 美化滚动条 */
        .legal-scroll::-webkit-scrollbar {
            width: 8px;
        }
        .legal-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        .legal-scroll::-webkit-scrollbar-thumb {
            background: rgba(0, 255, 65, 0.3);
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .legal-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 255, 65, 0.5);
            box-shadow: 0 0 10px rgba(0, 255, 65, 0.3);
        }
        /* Firefox 滚动条 */
        .legal-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 255, 65, 0.3) rgba(255, 255, 255, 0.05);
        }
        /* 扫描线动画 */
        @keyframes scanLine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <h1><i class="fas fa-list"></i> 日志管理</h1>
                <button class="btn btn-danger" onclick="clearLogs()">
                    <i class="fas fa-trash"></i> 清空所有日志
                </button>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100 stats-chart-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">总记录</small>
                                    <h3 class="mb-0 text-success" id="totalCount">0</h3>
                                </div>
                                <i class="fas fa-database fa-2x text-success" style="opacity:0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100 stats-chart-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">当前页</small>
                                    <h3 class="mb-0 text-info" id="currentPage">1</h3>
                                </div>
                                <i class="fas fa-file-alt fa-2x text-info" style="opacity:0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow fade-in">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="logsTable">
                            <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>IP地址</th>
                                    <th>方法</th>
                                    <th>来源</th>
                                    <th>User-Agent</th>
                                    <th>用户</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="logsBody">
                                <tr><td colspan="7" class="text-center">加载中...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center" id="pagination"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 详情弹窗 -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle"></i> 日志详情</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="static/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- 全局通知系统 -->
    <script src="static/js/notification.js"></script>
    
    <script>
        let currentPage = 1;
        let hasGovSiteLogs = false; // 标记是否有政府网站日志
        
        async function loadLogs(page = 1) {
            try {
                const response = await fetch(`api/logs.php?page=${page}&per_page=20`);
                const data = await response.json();
                
                console.log('API返回数据:', data); // 调试输出
                
                // 检测是否有政府网站日志
                if (data.logs && data.logs.length > 0) {
                    const govLogs = data.logs.filter(log => log.is_gov_site == 1);
                    if (govLogs.length > 0 && !hasGovSiteLogs) {
                        hasGovSiteLogs = true;
                        // 管理员看到政府网站日志时弹出警告
                        <?php if (isAdmin()): ?>
                        setTimeout(() => showGovSiteWarning(govLogs.length), 1000);
                        <?php endif; ?>
                    }
                }
                
                document.getElementById('totalCount').textContent = data.total || 0;
                document.getElementById('currentPage').textContent = page;
                currentPage = page;
                
                const tbody = document.getElementById('logsBody');
                if (data.logs && data.logs.length > 0) {
                    tbody.innerHTML = data.logs.map(log => {
                        // 安全处理可能为null的字段
                        const referer = log.referer || '-';
                        const userAgent = log.user_agent || '-';
                        const isLink = referer !== '-' && (referer.startsWith('http://') || referer.startsWith('https://'));
                        const displayReferer = referer.length > 50 ? referer.substring(0, 50) + '...' : referer;
                        const displayUA = userAgent.length > 50 ? userAgent.substring(0, 50) + '...' : userAgent;
                        
                        const refererDisplay = isLink
                            ? `<a href="${referer}" target="_blank" class="text-warning" title="${referer}">${displayReferer}</a>`
                            : `<span title="${referer}" class="text-truncate" style="max-width:200px;">${displayReferer}</span>`;
                        
                        // 获取用户名，如果没有则显示"系统"
                        const username = log.username || '系统';
                        
                        return `
                        <tr>
                            <td>${log.created_at || '-'}</td>
                            <td><span class="badge bg-secondary">${log.ip || '-'}</span></td>
                            <td><span class="badge bg-primary">${log.method || '-'}</span></td>
                            <td>${refererDisplay}</td>
                            <td title="${userAgent}" class="text-truncate" style="max-width:200px;">${displayUA}</td>
                            <td><span class="badge bg-success">${username}</span></td>
                            <td>
                                <div class="text-end">
                                    <button class="btn btn-sm btn-info" onclick="viewDetail('${log.log_id}')">
                                        <i class="fas fa-eye"></i> 查看
                                    </button>
                                </div>
                            </td>
                        </tr>
                        `;
                    }).join('');
                    
                    // 生成分页 - 智能分页逻辑
                    const totalPages = data.total_pages || 1;
                    let paginationHtml = '';
                    
                    if (totalPages > 1) {
                        // 上一页按钮
                        paginationHtml += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="${page > 1 ? `loadLogs(${page - 1}); return false;` : 'return false;'}">
                                <i class="fas fa-chevron-left"></i> 上一页
                            </a>
                        </li>`;
                        
                        // 页码逻辑
                        if (totalPages <= 10) {
                            // 总页数不超过10页，显示所有页码
                            for (let i = 1; i <= totalPages; i++) {
                                paginationHtml += `<li class="page-item ${i === page ? 'active' : ''}">
                                    <a class="page-link" href="#" onclick="loadLogs(${i}); return false;">${i}</a>
                                </li>`;
                            }
                        } else {
                            // 超过10页，显示智能省略
                            paginationHtml += `<li class="page-item ${page === 1 ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="loadLogs(1); return false;">1</a>
                            </li>`;
                            
                            if (page > 4) {
                                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                            }
                            
                            let startPage = Math.max(2, page - 2);
                            let endPage = Math.min(totalPages - 1, page + 2);
                            
                            for (let i = startPage; i <= endPage; i++) {
                                paginationHtml += `<li class="page-item ${i === page ? 'active' : ''}">
                                    <a class="page-link" href="#" onclick="loadLogs(${i}); return false;">${i}</a>
                                </li>`;
                            }
                            
                            if (page < totalPages - 3) {
                                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                            }
                            
                            paginationHtml += `<li class="page-item ${page === totalPages ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="loadLogs(${totalPages}); return false;">${totalPages}</a>
                            </li>`;
                        }
                        
                        // 下一页按钮
                        paginationHtml += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="${page < totalPages ? `loadLogs(${page + 1}); return false;` : 'return false;'}">
                                下一页 <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>`;
                    }
                    
                    document.getElementById('pagination').innerHTML = paginationHtml;
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center">暂无数据</td></tr>';
                    document.getElementById('pagination').innerHTML = '';
                }
            } catch (error) {
                console.error('加载失败:', error);
                document.getElementById('logsBody').innerHTML = '<tr><td colspan="6" class="text-center text-danger">加载失败: ' + error.message + '</td></tr>';
            }
        }
        
        async function viewDetail(logId) {
            try {
                const response = await fetch(`api/logs.php?page=1&per_page=100`);
                const data = await response.json();
                const log = data.logs.find(l => l.log_id === logId);
                
                if (log) {
                    // 安全解析JSON字段
                    let headers = {};
                    let cookies = {};
                    let logData = {};
                    
                    try {
                        headers = log.headers ? JSON.parse(log.headers) : {};
                    } catch(e) {
                        headers = { error: 'JSON解析失败', raw: log.headers };
                    }
                    
                    try {
                        cookies = log.cookies ? JSON.parse(log.cookies) : {};
                    } catch(e) {
                        cookies = { error: 'JSON解析失败', raw: log.cookies };
                    }
                    
                    try {
                        // data字段可能是字符串或JSON
                        if (typeof log.data === 'string') {
                            try {
                                logData = JSON.parse(log.data);
                            } catch(e) {
                                logData = { raw: log.data };
                            }
                        } else {
                            logData = log.data || {};
                        }
                    } catch(e) {
                        logData = { error: 'JSON解析失败', raw: log.data };
                    }
                    
                    // 识别Payload类型并添加图标
                    let payloadIcon = '📝';
                    let payloadType = '普通数据';
                    
                    if (logData.type) {
                        switch(logData.type) {
                            case 'camera_capture':
                                payloadIcon = '📷';
                                payloadType = '摄像头拍照';
                                break;
                            case 'gps_location':
                                payloadIcon = '📍';
                                payloadType = 'GPS定位';
                                break;
                            case 'ip_detect':
                                payloadIcon = '🌐';
                                payloadType = '真实IP检测';
                                break;
                            case 'super_screenshot':
                                payloadIcon = '📸';
                                payloadType = '超级截屏';
                                break;
                            case 'rdp_control':
                            case 'rdp_final':
                                payloadIcon = '🖥️';
                                payloadType = 'RDP远程控制';
                                break;
                            case 'phishing_download_click':
                            case 'phishing_module_loaded':
                                payloadIcon = '🎣';
                                payloadType = '钓鱼下载';
                                break;
                            case 'cookie_theft':
                            case 'multi_cookie':
                                payloadIcon = '🍪';
                                payloadType = 'Cookie窃取';
                                break;
                            case 'keylogger':
                            case 'form_submit':
                                payloadIcon = '⌨️';
                                payloadType = '键盘记录';
                                break;
                            case 'fingerprint':
                                payloadIcon = '🔍';
                                payloadType = '浏览器指纹';
                                break;
                            case 'clipboard_copy':
                            case 'clipboard_read':
                                payloadIcon = '📋';
                                payloadType = '剪贴板劫持';
                                break;
                            default:
                                payloadIcon = '📦';
                                payloadType = logData.type;
                        }
                    }
                    
                    document.getElementById('detailContent').innerHTML = `
                        <div class="alert alert-info" style="background: linear-gradient(135deg, rgba(0, 212, 255, 0.1) 0%, rgba(102, 126, 234, 0.1) 100%); border: 1px solid rgba(0, 212, 255, 0.3);">
                            <h5 style="margin: 0; color: #0d6efd;">${payloadIcon} ${payloadType}</h5>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-success"><i class="fas fa-info-circle"></i> 基本信息</h6>
                            <div class="bg-light p-3 rounded">
                                <p><strong>ID:</strong> ${log.log_id || '-'}</p>
                                <p><strong>时间:</strong> ${log.created_at || '-'}</p>
                                <p><strong>IP:</strong> ${log.ip || '-'}</p>
                                <p><strong>方法:</strong> ${log.method || '-'}</p>
                                <p><strong>端点:</strong> ${log.endpoint || '-'}</p>
                                <p><strong>URL:</strong> ${log.url || '-'}</p>
                                <p class="mb-0"><strong>来源:</strong> ${log.referer || '-'}</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-success"><i class="fas fa-user-agent"></i> User-Agent</h6>
                            <div class="bg-light p-3 rounded">
                                <p class="mb-0">${log.user_agent || '-'}</p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-success"><i class="fas fa-server"></i> 请求头</h6>
                            <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">${JSON.stringify(headers, null, 2)}</pre>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-success"><i class="fas fa-cookie"></i> Cookies</h6>
                            <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">${JSON.stringify(cookies, null, 2)}</pre>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-success"><i class="fas fa-database"></i> Payload数据 (${log.data_type || 'unknown'})</h6>
                            <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">${JSON.stringify(logData, null, 2)}</pre>
                        </div>
                        ${log.raw_data ? `<div class="mb-3"><h6 class="text-success"><i class="fas fa-file-code"></i> 原始数据</h6><pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">${log.raw_data}</pre></div>` : ''}
                    `;
                    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                    modal.show();
                } else {
                    alert('未找到日志记录');
                }
            } catch (error) {
                console.error('加载详情失败:', error);
                alert('加载详情失败: ' + error.message);
            }
        }
        
        async function clearLogs() {
            if (!confirm('确定要清空所有日志吗？此操作不可恢复！')) {
                return;
            }
            
            try {
                const response = await fetch('api/logs.php', { method: 'DELETE' });
                const data = await response.json();
                
                if (data.success) {
                    alert('日志已清空');
                    loadLogs(1);
                } else {
                    alert('清空失败');
                }
            } catch (error) {
                alert('操作失败');
            }
        }
        
        // 页面加载时获取日志
        loadLogs(1);
        
        // 每10秒自动刷新
        setInterval(() => loadLogs(currentPage), 10000);
        
        // ========== 政府网站违规警告弹窗 ==========
        
        // 管理员警告弹窗（看到违规日志时）
        function showGovSiteWarning(count) {
            const modal = document.createElement('div');
            modal.id = 'govWarningModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.95);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.3s ease;
                backdrop-filter: blur(10px);
            `;
            
            modal.innerHTML = `
                <div style="
                    background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
                    border: 2px solid #ff4444;
                    box-shadow: 0 0 40px rgba(255, 68, 68, 0.5);
                    border-radius: 4px;
                    padding: 0;
                    width: 90%;
                    max-width: 650px;
                    max-height: 85vh;
                    overflow: hidden;
                    position: relative;
                ">
                    <!-- 顶部警告标题 -->
                    <div style="
                        background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
                        padding: 25px 30px;
                        border-bottom: 1px solid #ff4444;
                    ">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="
                                width: 60px;
                                height: 60px;
                                background: rgba(255, 255, 255, 0.1);
                                border: 2px solid #fff;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 32px;
                                box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
                            ">
                                <i class="fas fa-exclamation-triangle" style="color: #fff;"></i>
                            </div>
                            <div style="flex: 1;">
                                <h2 style="
                                    color: #fff;
                                    margin: 0;
                                    font-size: 24px;
                                    font-weight: bold;
                                    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                ">⚠️ SECURITY ALERT</h2>
                                <p style="
                                    color: rgba(255, 255, 255, 0.9);
                                    margin: 5px 0 0 0;
                                    font-size: 14px;
                                ">检测到违规操作记录</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 内容区域 -->
                    <div style="padding: 30px; color: #e0e0e0; line-height: 1.8; font-size: 15px;">
                        <div style="
                            background: rgba(255, 68, 68, 0.1);
                            border-left: 4px solid #ff4444;
                            padding: 20px;
                            margin-bottom: 25px;
                            border-radius: 4px;
                        ">
                            <p style="margin: 0; color: #ff6666; font-size: 16px; font-weight: bold;">
                                <i class="fas fa-shield-alt"></i> 管理员警告！
                            </p>
                            <p style="margin: 10px 0 0 0; color: #e0e0e0;">
                                系统检测到 <strong style="color: #ff4444;">${count}</strong> 条针对<strong style="color: #ff4444;">政府网站 (.gov.cn)</strong> 的XSS测试记录！
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <p style="margin: 0 0 15px 0; font-size: 16px; font-weight: bold; color: #fff;">
                                <i class="fas fa-exclamation-circle"></i> 违规情况：
                            </p>
                            <ul style="margin: 0; padding-left: 25px; color: #ccc;">
                                <li style="margin-bottom: 10px;">用户尝试对政府网站进行XSS渗透测试</li>
                                <li style="margin-bottom: 10px;">此行为<strong style="color: #ff4444;">严重违反平台使用协议</strong></li>
                                <li style="margin-bottom: 10px;">可能涉及<strong style="color: #ff4444;">违反网络安全法</strong>等相关法律法规</li>
                                <li>普通用户无法看到这些记录，仅管理员可见</li>
                            </ul>
                        </div>
                        
                        <div style="
                            background: rgba(255, 170, 0, 0.1);
                            border-left: 4px solid #ffaa00;
                            padding: 15px;
                            margin-bottom: 20px;
                            border-radius: 4px;
                        ">
                            <p style="margin: 0; color: #ffaa00; font-size: 14px;">
                                <i class="fas fa-info-circle"></i> <strong>建议处理措施：</strong>
                            </p>
                            <ul style="margin: 10px 0 0 20px; padding: 0; color: #ccc; font-size: 14px;">
                                <li>识别违规用户并进行警告</li>
                                <li>重复违规者可考虑封禁账号</li>
                                <li>保留违规记录作为证据</li>
                                <li>必要时配合有关部门调查</li>
                            </ul>
                        </div>
                        
                        <div style="
                            background: rgba(255, 68, 68, 0.15);
                            padding: 15px;
                            border-radius: 4px;
                            text-align: center;
                        ">
                            <p style="margin: 0; color: #ff6666; font-size: 13px; line-height: 1.6;">
                                🚨 作为平台管理员，请对违规行为保持高度警惕<br>
                                并及时采取必要的管理措施
                            </p>
                        </div>
                    </div>
                    
                    <!-- 底部按钮 -->
                    <div style="
                        background: rgba(0, 0, 0, 0.3);
                        padding: 20px 30px;
                        border-top: 1px solid rgba(255, 68, 68, 0.3);
                        text-align: center;
                    ">
                        <button onclick="closeGovWarning()" style="
                            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
                            border: 1px solid #ff6666;
                            color: #fff;
                            padding: 12px 40px;
                            cursor: pointer;
                            font-family: 'Microsoft YaHei', sans-serif;
                            font-size: 15px;
                            font-weight: 600;
                            border-radius: 4px;
                            transition: all 0.3s ease;
                            box-shadow: 0 0 20px rgba(255, 68, 68, 0.3);
                        " onmouseover="this.style.background='linear-gradient(135deg, #ff6666 0%, #ff4444 100%)'; this.style.boxShadow='0 0 30px rgba(255, 68, 68, 0.5)';" onmouseout="this.style.background='linear-gradient(135deg, #ff4444 0%, #cc0000 100%)'; this.style.boxShadow='0 0 20px rgba(255, 68, 68, 0.3)';">
                            <i class="fas fa-check"></i> 我已知晓
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }
        
        function closeGovWarning() {
            const modal = document.getElementById('govWarningModal');
            if (modal) modal.remove();
        }
        
        // 普通用户法律声明弹窗（首次查看日志时）
        <?php if (!isAdmin()): ?>
        const logsLegalNoticeShown = localStorage.getItem('logsLegalNoticeShown');
        if (!logsLegalNoticeShown) {
            setTimeout(() => showLogsLegalNotice(), 500);
        }
        <?php endif; ?>
        
        function showLogsLegalNotice() {
            // 使用与login.php相同的法律声明弹窗
            const modal = document.createElement('div');
            modal.id = 'logsLegalNoticeModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.95);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.3s ease;
                backdrop-filter: blur(10px);
            `;
            
            modal.innerHTML = `
                <div style="
                    background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
                    border: 1px solid #00ff41;
                    box-shadow: 0 0 30px rgba(0, 255, 65, 0.3);
                    border-radius: 4px;
                    padding: 0;
                    width: 90%;
                    max-width: 700px;
                    max-height: 85vh;
                    overflow: hidden;
                    position: relative;
                ">
                    <div style="position: relative; background: rgba(0, 0, 0, 0.5); padding: 25px 30px; border-bottom: 1px solid #00ff41;">
                        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 2px; background: #00ff41; animation: scanLine 3s linear infinite;"></div>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 50px; height: 50px; background: rgba(0, 255, 65, 0.1); border: 2px solid #00ff41; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 0 15px rgba(0, 255, 65, 0.3);">
                                <i class="fas fa-shield-alt" style="color: #00ff41;"></i>
                            </div>
                            <div style="flex: 1;">
                                <h2 style="color: #00ff41; margin: 0; font-size: 20px; font-weight: bold; text-shadow: 0 0 10px rgba(0, 255, 65, 0.5);">SECURITY NOTICE</h2>
                                <p style="color: rgba(0, 255, 65, 0.7); margin: 5px 0 0 0; font-size: 13px;">法律声明与使用协议</p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding: 30px; color: #e0e0e0; line-height: 1.9; font-size: 14px; max-height: 50vh; overflow-y: auto;" class="legal-scroll">
                        <div style="background: rgba(255, 68, 68, 0.1); border-left: 4px solid #ff4444; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                            <p style="margin: 0; color: #ff6666; font-size: 15px; font-weight: bold;">⚠️ 重要安全警告</p>
                            <p style="margin: 10px 0 0 0; color: #ff9999; font-size: 13px; line-height: 1.7;">本 XSS 平台<strong>仅供授权安全测试使用</strong>。任何未经授权的渗透测试行为均属违法行为，将承担相应法律责任。</p>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <p style="margin: 0 0 10px 0; color: #00ff41; font-size: 15px; font-weight: bold;"><i class="fas fa-ban"></i> PROHIBITED ACTIVITIES</p>
                            <div style="color: #ccc; font-size: 13px; line-height: 1.8;">
                                <p style="margin: 8px 0; padding-left: 20px; position: relative;"><span style="position: absolute; left: 0; color: #ff4444;">✖</span> <strong style="color: #ff6666;">政府机构及其下属网站</strong>、系统、平台的任何形式渗透测试</p>
                                <p style="margin: 8px 0; padding-left: 20px; position: relative;"><span style="position: absolute; left: 0; color: #ff4444;">✖</span> <strong style="color: #ff6666;">企业公司</strong>、商业组织的生产环境、办公系统等未授权测试</p>
                                <p style="margin: 8px 0; padding-left: 20px; position: relative;"><span style="position: absolute; left: 0; color: #ff4444;">✖</span> <strong style="color: #ff6666;">教育机构</strong>、医疗系统、金融平台等关键基础设施</p>
                                <p style="margin: 8px 0; padding-left: 20px; position: relative;"><span style="position: absolute; left: 0; color: #ff4444;">✖</span> <strong>任何未获得明确书面授权</strong>的第三方网站或系统</p>
                                <p style="margin: 8px 0; padding-left: 20px; position: relative;"><span style="position: absolute; left: 0; color: #ff4444;">✖</span> 利用本平台进行<strong style="color: #ff6666;">恶意攻击</strong>、数据窣取、勒索等犯罪活动</p>
                            </div>
                        </div>
                        
                        <div style="background: rgba(255, 170, 0, 0.1); border-left: 4px solid #ffaa00; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                            <p style="margin: 0 0 10px 0; color: #ffaa00; font-size: 14px; font-weight: bold;"><i class="fas fa-gavel"></i> LEGAL DISCLAIMER</p>
                            <div style="color: #b0b0b0; font-size: 13px; line-height: 1.8;">
                                <p style="margin: 0 0 10px 0;">• 使用本平台即表示您已完全理解并同意遵守上述所有条款</p>
                                <p style="margin: 0 0 10px 0;">• 违反规定造成的一切法律后果由<strong style="color: #ffaa00;">使用者本人承担</strong></p>
                                <p style="margin: 0;">• 本声明适用于<strong style="color: #ffaa00;">国际网络安全法律</strong>及您所在地区的相关法规</p>
                            </div>
                        </div>
                        
                        <div style="background: rgba(255, 68, 68, 0.15); padding: 15px; border-radius: 4px; text-align: center;">
                            <p style="margin: 0; color: #ff6666; font-size: 13px; line-height: 1.6;">
                                🚨 <strong>特别提示</strong>：对政府网站 (.gov.cn) 进行XSS测试将被系统自动隐藏，<br>
                                此类违规行为会被记录并可能导致账号被封禁
                            </p>
                        </div>
                    </div>
                    
                    <div style="background: rgba(0, 0, 0, 0.3); padding: 20px 30px; border-top: 1px solid rgba(0, 255, 65, 0.3); display: flex; align-items: center; justify-content: space-between;">
                        <span id="logsTimerText" style="color: #00ff41; font-size: 13px;">请仔细阅读 (<span id="logsCountdown">5</span>s)</span>
                        <button id="logsAgreeBtn" disabled style="background: rgba(100, 100, 100, 0.3); border: 1px solid #666; color: #888; padding: 10px 30px; cursor: not-allowed; font-family: 'Microsoft YaHei', sans-serif; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; border-radius: 4px; transition: all 0.3s ease;">
                            <span id="logsBtnText">等待中...</span>
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            let countdown = 5;
            const countdownEl = document.getElementById('logsCountdown');
            const timerTextEl = document.getElementById('logsTimerText');
            const agreeBtn = document.getElementById('logsAgreeBtn');
            const btnText = document.getElementById('logsBtnText');
            
            const timer = setInterval(() => {
                countdown--;
                countdownEl.textContent = countdown;
                if (countdown <= 0) {
                    clearInterval(timer);
                    timerTextEl.innerHTML = '<i class="fas fa-check-circle"></i> 已完成阅读';
                    agreeBtn.disabled = false;
                    agreeBtn.style.cssText = `
                        background: rgba(0, 255, 65, 0.15);
                        border: 1px solid #00ff41;
                        color: #00ff41;
                        padding: 10px 30px;
                        cursor: pointer;
                        font-family: 'Microsoft YaHei', sans-serif;
                        font-size: 13px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                        border-radius: 4px;
                        transition: all 0.3s ease;
                        box-shadow: 0 0 20px rgba(0, 255, 65, 0.2);
                    `;
                    btnText.textContent = 'I AGREE';
                    agreeBtn.onmouseover = function() {
                        this.style.background = 'rgba(0, 255, 65, 0.25)';
                        this.style.boxShadow = '0 0 30px rgba(0, 255, 65, 0.4)';
                    };
                    agreeBtn.onmouseout = function() {
                        this.style.background = 'rgba(0, 255, 65, 0.15)';
                        this.style.boxShadow = '0 0 20px rgba(0, 255, 65, 0.2)';
                    };
                    agreeBtn.onclick = function() {
                        closeLogsLegalNotice();
                    };
                }
            }, 1000);
        }
        
        function closeLogsLegalNotice() {
            const modal = document.getElementById('logsLegalNoticeModal');
            if (modal) modal.remove();
            localStorage.setItem('logsLegalNoticeShown', 'true');
        }
    </script>
</body>
</html>
