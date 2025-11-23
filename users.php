<?php
/**
 * 用户管理页面 - 仅管理员可访问
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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <h1><i class="fas fa-users"></i> 用户管理</h1>
                <div>
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addIpBanModal">
                        <i class="fas fa-ban"></i> 添加IP封禁
                    </button>
                    <span class="badge bg-success ms-2">管理员专属</span>
                </div>
            </div>
            
            <!-- 用户统计卡片 -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm h-100 stats-chart-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">总用户数</small>
                                    <h3 class="mb-0 text-success" id="totalUsers">0</h3>
                                </div>
                                <i class="fas fa-users fa-2x text-success" style="opacity:0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm h-100 stats-chart-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">管理员</small>
                                    <h3 class="mb-0 text-danger" id="adminCount">0</h3>
                                </div>
                                <i class="fas fa-user-shield fa-2x text-danger" style="opacity:0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm h-100 stats-chart-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">普通用户</small>
                                    <h3 class="mb-0 text-info" id="userCount">0</h3>
                                </div>
                                <i class="fas fa-user fa-2x text-info" style="opacity:0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm h-100 stats-chart-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">总日志数</small>
                                    <h3 class="mb-0 text-warning" id="totalLogs">0</h3>
                                </div>
                                <i class="fas fa-database fa-2x text-warning" style="opacity:0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 用户列表 -->
            <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                        <i class="fas fa-users"></i> 用户列表
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ip-ban-tab" data-bs-toggle="tab" data-bs-target="#ip-ban" type="button" role="tab">
                        <i class="fas fa-ban"></i> IP黑名单
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="userTabsContent">
                <!-- 用户列表标签页 -->
                <div class="tab-pane fade show active" id="users" role="tabpanel">
                    <div class="card shadow fade-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list"></i> 注册用户列表</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="usersTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>用户名</th>
                                            <th>角色</th>
                                            <th>邮箱</th>
                                            <th>日志数</th>
                                            <th>状态</th>
                                            <th>注册时间</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody id="usersBody">
                                        <tr><td colspan="8" class="text-center">加载中...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- IP黑名单标签页 -->
                <div class="tab-pane fade" id="ip-ban" role="tabpanel">
                    <div class="card shadow fade-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-ban"></i> IP黑名单</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="ipBanTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>IP地址</th>
                                            <th>封禁原因</th>
                                            <th>添加时间</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ipBanBody">
                                        <tr><td colspan="5" class="text-center">加载中...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 添加IP封禁模态框 -->
    <div class="modal fade" id="addIpBanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-ban"></i> 添加IP封禁</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addIpBanForm">
                        <div class="mb-3">
                            <label for="banIp" class="form-label">IP地址</label>
                            <input type="text" class="form-control" id="banIp" required placeholder="例如: 192.168.1.1">
                            <div class="form-text">支持IPv4和IPv6地址</div>
                        </div>
                        <div class="mb-3">
                            <label for="banReason" class="form-label">封禁原因</label>
                            <textarea class="form-control" id="banReason" rows="3" required placeholder="请输入封禁原因..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-danger" onclick="addIpBan()">
                        <i class="fas fa-ban"></i> 添加封禁
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- 全局通知系统 -->
    <script src="static/js/notification.js"></script>
    
    <script>
        async function loadUsers() {
            try {
                const response = await fetch('api/users.php');
                const data = await response.json();
                
                if (data.error) {
                    alert('加载失败: ' + data.error);
                    return;
                }
                
                // 更新统计
                document.getElementById('totalUsers').textContent = data.stats.total || 0;
                document.getElementById('adminCount').textContent = data.stats.admins || 0;
                document.getElementById('userCount').textContent = data.stats.users || 0;
                document.getElementById('totalLogs').textContent = data.stats.total_logs || 0;
                
                // 渲染用户列表
                const tbody = document.getElementById('usersBody');
                if (data.users && data.users.length > 0) {
                    tbody.innerHTML = data.users.map(user => {
                        const roleBadge = user.role === 'admin' 
                            ? '<span class="badge bg-danger">管理员</span>' 
                            : '<span class="badge bg-info">普通用户</span>';
                        
                        const statusBadge = user.status === 'banned'
                            ? '<span class="badge bg-dark">已封禁</span>'
                            : '<span class="badge bg-success">正常</span>';
                        
                        const actionButtons = user.status === 'banned'
                            ? `<button class="btn btn-sm btn-success" onclick="unbanUser(${user.id}, '${user.username}')">
                                <i class="fas fa-unlock"></i> 解封
                            </button>`
                            : (user.role !== 'admin' ? `<button class="btn btn-sm btn-warning" onclick="banUser(${user.id}, '${user.username}')">
                                <i class="fas fa-ban"></i> 封禁
                            </button>` : '');
                        
                        return `
                        <tr>
                            <td>${user.id}</td>
                            <td><strong>${user.username}</strong></td>
                            <td>${roleBadge}</td>
                            <td>${user.email || '-'}</td>
                            <td><span class="badge bg-secondary">${user.log_count || 0}</span></td>
                            <td>${statusBadge}</td>
                            <td>${user.created_at || '-'}</td>
                            <td>
                                <div class="btn-group text-end" role="group">
                                    <button class="btn btn-sm btn-primary" onclick="viewUserLogs(${user.id}, '${user.username}')">
                                        <i class="fas fa-eye"></i> 日志
                                    </button>
                                    ${actionButtons}
                                </div>
                            </td>
                        </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">暂无用户</td></tr>';
                }
                
            } catch (error) {
                console.error('加载失败:', error);
                alert('加载失败，请重试');
            }
        }
        
        function viewUserLogs(userId, username) {
            window.location.href = `user_logs.php?user_id=${userId}&username=${encodeURIComponent(username)}`;
        }
        
        async function banUser(userId, username) {
            const reason = prompt(`请输入封禁用户 "${username}" 的原因：`, '违反平台使用规则');
            if (reason === null) return; // 用户取消
            
            if (!confirm(`确认封禁用户 "${username}" 吗？`)) return;
            
            try {
                const response = await fetch('api/ban_user.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId, reason: reason})
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('用户已封禁');
                    loadUsers(); // 重新加载用户列表
                } else {
                    alert('封禁失败：' + result.message);
                }
            } catch (error) {
                console.error('封禁失败:', error);
                alert('封禁失败，请重试');
            }
        }
        
        async function unbanUser(userId, username) {
            if (!confirm(`确认解封用户 "${username}" 吗？`)) return;
            
            try {
                const response = await fetch('api/unban_user.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId})
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('用户已解封');
                    loadUsers(); // 重新加载用户列表
                } else {
                    alert('解封失败：' + result.message);
                }
            } catch (error) {
                console.error('解封失败:', error);
                alert('解封失败，请重试');
            }
        }
        
        // 页面加载时执行
        loadUsers();
        
        // 标签页切换时加载IP黑名单
        document.getElementById('ip-ban-tab').addEventListener('click', function() {
            loadIpBlacklist();
        });
        
        // IP黑名单管理函数
        async function loadIpBlacklist() {
            try {
                const response = await fetch('api/ip_blacklist.php');
                const data = await response.json();
                
                if (data.error) {
                    alert('加载失败: ' + data.error);
                    return;
                }
                
                const tbody = document.getElementById('ipBanBody');
                if (data.blacklist && data.blacklist.length > 0) {
                    tbody.innerHTML = data.blacklist.map(item => `
                        <tr>
                            <td>${item.id}</td>
                            <td><code>${item.ip}</code></td>
                            <td>${item.reason || '-'}</td>
                            <td>${item.created_at || '-'}</td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="removeIpBan(${item.id}, '${item.ip}')">
                                    <i class="fas fa-trash"></i> 移除
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">暂无IP封禁</td></tr>';
                }
            } catch (error) {
                console.error('加载失败:', error);
                alert('加载失败，请重试');
            }
        }
        
        async function addIpBan() {
            const ip = document.getElementById('banIp').value.trim();
            const reason = document.getElementById('banReason').value.trim();
            
            if (!ip || !reason) {
                alert('请填写完整信息');
                return;
            }
            
            try {
                const response = await fetch('api/add_ip_ban.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ip: ip, reason: reason})
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('IP封禁添加成功');
                    bootstrap.Modal.getInstance(document.getElementById('addIpBanModal')).hide();
                    document.getElementById('addIpBanForm').reset();
                    loadIpBlacklist();
                } else {
                    alert('添加失败：' + result.message);
                }
            } catch (error) {
                console.error('添加失败:', error);
                alert('添加失败，请重试');
            }
        }
        
        async function removeIpBan(id, ip) {
            if (!confirm(`确认移除IP "${ip}" 的封禁吗？`)) return;
            
            try {
                const response = await fetch('api/remove_ip_ban.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('IP封禁已移除');
                    loadIpBlacklist();
                } else {
                    alert('移除失败：' + result.message);
                }
            } catch (error) {
                console.error('移除失败:', error);
                alert('移除失败，请重试');
            }
        }
    </script>
</body>
</html>
