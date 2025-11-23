<?php
/**
 * 用户日志详情页面 - 仅管理员可访问
 */
require_once 'config.php';
session_start();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 检查是否是管理员
if (!isAdmin()) {
    http_response_code(403);
    die('403 Forbidden - 只有管理员可以访问此页面');
}

if (!checkIpWhitelist()) {
    http_response_code(403);
    die('IP地址 ' . $_SERVER['REMOTE_ADDR'] . ' 不在白名单中');
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : '未知用户';

if ($userId <= 0) {
    die('无效的用户ID');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $username; ?> 的日志 - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <div>
                    <h1><i class="fas fa-user"></i> <?php echo $username; ?> 的XSS日志</h1>
                    <p class="text-muted mb-0">用户ID: <?php echo $userId; ?></p>
                </div>
                <div>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> 返回用户列表
                    </a>
                </div>
            </div>
            
            <!-- 统计卡片 -->
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
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
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100 stats-chart-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">唯一IP</small>
                                    <h3 class="mb-0 text-info" id="uniqueIPs">0</h3>
                                </div>
                                <i class="fas fa-network-wired fa-2x text-info" style="opacity:0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100 stats-chart-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">当前页</small>
                                    <h3 class="mb-0 text-warning" id="currentPage">1</h3>
                                </div>
                                <i class="fas fa-file-alt fa-2x text-warning" style="opacity:0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 日志列表 -->
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
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="logsBody">
                                <tr><td colspan="6" class="text-center">加载中...</td></tr>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const userId = <?php echo $userId; ?>;
        let currentPage = 1;
        
        async function loadLogs(page = 1) {
            try {
                const response = await fetch(`api/user_logs.php?user_id=${userId}&page=${page}&per_page=20`);
                const data = await response.json();
                
                if (data.error) {
                    alert('加载失败: ' + data.error);
                    return;
                }
                
                // 更新统计
                document.getElementById('totalCount').textContent = data.total || 0;
                document.getElementById('uniqueIPs').textContent = data.unique_ips || 0;
                document.getElementById('currentPage').textContent = page;
                currentPage = page;
                
                const tbody = document.getElementById('logsBody');
                if (data.logs && data.logs.length > 0) {
                    tbody.innerHTML = data.logs.map(log => {
                        const referer = log.referer || '-';
                        const userAgent = log.user_agent || '-';
                        const isLink = referer !== '-' && (referer.startsWith('http://') || referer.startsWith('https://'));
                        const displayReferer = referer.length > 50 ? referer.substring(0, 50) + '...' : referer;
                        const displayUA = userAgent.length > 50 ? userAgent.substring(0, 50) + '...' : userAgent;
                        
                        const refererDisplay = isLink
                            ? `<a href="${referer}" target="_blank" class="text-warning" title="${referer}">${displayReferer}</a>`
                            : `<span title="${referer}" class="text-truncate" style="max-width:200px;">${displayReferer}</span>`;
                        
                        return `
                        <tr>
                            <td>${log.created_at || '-'}</td>
                            <td><span class="badge bg-secondary">${log.ip || '-'}</span></td>
                            <td><span class="badge bg-primary">${log.method || '-'}</span></td>
                            <td>${refererDisplay}</td>
                            <td title="${userAgent}" class="text-truncate" style="max-width:200px;">${displayUA}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewDetail('${log.log_id}')">
                                    <i class="fas fa-eye"></i> 查看
                                </button>
                            </td>
                        </tr>
                        `;
                    }).join('');
                    
                    // 生成分页
                    const totalPages = data.total_pages || 1;
                    let paginationHtml = '';
                    
                    if (totalPages > 1) {
                        // 上一页
                        paginationHtml += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="${page > 1 ? `loadLogs(${page - 1}); return false;` : 'return false;'}">
                                <i class="fas fa-chevron-left"></i> 上一页
                            </a>
                        </li>`;
                        
                        // 页码
                        if (totalPages <= 10) {
                            for (let i = 1; i <= totalPages; i++) {
                                paginationHtml += `<li class="page-item ${i === page ? 'active' : ''}">
                                    <a class="page-link" href="#" onclick="loadLogs(${i}); return false;">${i}</a>
                                </li>`;
                            }
                        } else {
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
                        
                        // 下一页
                        paginationHtml += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="${page < totalPages ? `loadLogs(${page + 1}); return false;` : 'return false;'}">
                                下一页 <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>`;
                    }
                    
                    document.getElementById('pagination').innerHTML = paginationHtml;
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">该用户暂无日志</td></tr>';
                    document.getElementById('pagination').innerHTML = '';
                }
                
            } catch (error) {
                console.error('加载失败:', error);
                alert('加载失败，请重试');
            }
        }
        
        async function viewDetail(logId) {
            try {
                const response = await fetch(`api/log_detail.php?log_id=${logId}`);
                const log = await response.json();
                
                if (log.error) {
                    alert('加载失败: ' + log.error);
                    return;
                }
                
                let detailHtml = `
                    <table class="table table-bordered">
                        <tr><th width="150">日志ID</th><td>${log.log_id || '-'}</td></tr>
                        <tr><th>时间</th><td>${log.created_at || '-'}</td></tr>
                        <tr><th>IP地址</th><td>${log.ip || '-'}</td></tr>
                        <tr><th>请求方法</th><td><span class="badge bg-primary">${log.method || '-'}</span></td></tr>
                        <tr><th>端点</th><td>${log.endpoint || '-'}</td></tr>
                        <tr><th>完整URL</th><td style="word-break:break-all;">${log.url || '-'}</td></tr>
                        <tr><th>来源页面</th><td style="word-break:break-all;">${log.referer || '-'}</td></tr>
                        <tr><th>User-Agent</th><td style="word-break:break-all;">${log.user_agent || '-'}</td></tr>
                        <tr><th>Content-Type</th><td>${log.content_type || '-'}</td></tr>
                        <tr><th>数据类型</th><td>${log.data_type || '-'}</td></tr>
                    </table>
                    
                    <h6><i class="fas fa-cookie"></i> Cookies:</h6>
                    <pre class="bg-light p-2 rounded" style="max-height:200px; overflow:auto;">${log.cookies || '{}'}</pre>
                    
                    <h6><i class="fas fa-network-wired"></i> Headers:</h6>
                    <pre class="bg-light p-2 rounded" style="max-height:200px; overflow:auto;">${log.headers || '{}'}</pre>
                    
                    <h6><i class="fas fa-database"></i> 数据内容:</h6>
                    <pre class="bg-light p-2 rounded" style="max-height:300px; overflow:auto;">${log.data || '{}'}</pre>
                    
                    ${log.raw_data ? `<h6><i class="fas fa-file-code"></i> 原始数据:</h6>
                    <pre class="bg-light p-2 rounded" style="max-height:200px; overflow:auto;">${log.raw_data}</pre>` : ''}
                `;
                
                document.getElementById('detailContent').innerHTML = detailHtml;
                new bootstrap.Modal(document.getElementById('detailModal')).show();
                
            } catch (error) {
                console.error('加载详情失败:', error);
                alert('加载详情失败');
            }
        }
        
        // 页面加载时执行
        loadLogs();
    </script>
</body>
</html>
