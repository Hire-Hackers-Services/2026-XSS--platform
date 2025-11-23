<?php
/**
 * 数据库升级管理页面
 */
require_once 'config.php';
session_start();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 仅管理员可访问
if (!isAdmin()) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>只有管理员可以访问此页面</p>');
}

if (!checkIpWhitelist()) {
    http_response_code(403);
    die('IP地址 ' . $_SERVER['REMOTE_ADDR'] . ' 不在白名单中');
}

// 定义数据库升级任务
$upgradeTasks = [
    [
        'id' => 'add_gov_site_field',
        'name' => '添加政府网站检测字段',
        'description' => '为 logs 表添加 is_gov_site 字段，用于标记和过滤政府网站的XSS测试记录',
        'version' => '1.1.0',
        'sql' => [
            "ALTER TABLE `logs` ADD COLUMN `is_gov_site` TINYINT(1) DEFAULT 0 COMMENT '是否为政府网站 0=否 1=是' AFTER `content_type`",
            "ALTER TABLE `logs` ADD INDEX `idx_is_gov_site` (`is_gov_site`)"
        ],
        'check_sql' => "SELECT COUNT(*) as count FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'is_gov_site'"
    ],
    [
        'id' => 'add_user_id_to_logs',
        'name' => '添加用户关联字段',
        'description' => '为 logs 表添加 user_id 字段，将日志关联到特定用户',
        'version' => '1.1.0',
        'sql' => [
            "ALTER TABLE `logs` ADD COLUMN `user_id` INT UNSIGNED NULL COMMENT '用户ID' AFTER `log_id`",
            "ALTER TABLE `logs` ADD INDEX `idx_user_id` (`user_id`)"
        ],
        'check_sql' => "SELECT COUNT(*) as count FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'user_id'"
    ],
    [
        'id' => 'update_users_table',
        'name' => '更新用户表结构',
        'description' => '为 users 表添加角色、状态、邮箱等字段',
        'version' => '1.1.0',
        'sql' => [
            "ALTER TABLE `users` ADD COLUMN `role` VARCHAR(20) DEFAULT 'user' COMMENT '用户角色' AFTER `password`",
            "ALTER TABLE `users` ADD COLUMN `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱' AFTER `role`",
            "ALTER TABLE `users` ADD COLUMN `status` VARCHAR(20) DEFAULT 'active' COMMENT '状态' AFTER `email`",
            "ALTER TABLE `users` ADD COLUMN `banned_reason` TEXT NULL COMMENT '封禁原因' AFTER `status`",
            "ALTER TABLE `users` ADD COLUMN `banned_at` TIMESTAMP NULL COMMENT '封禁时间' AFTER `banned_reason`"
        ],
        'check_sql' => "SELECT COUNT(*) as count FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'"
    ]
];

// 处理AJAX请求
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $pdo = getDbConnection();
    
    if ($_GET['action'] === 'check') {
        // 检查所有升级任务状态
        $result = [];
        foreach ($upgradeTasks as $task) {
            $stmt = $pdo->query($task['check_sql']);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $result[] = [
                'id' => $task['id'],
                'name' => $task['name'],
                'description' => $task['description'],
                'version' => $task['version'],
                'status' => $count > 0 ? 'installed' : 'pending'
            ];
        }
        echo json_encode(['success' => true, 'tasks' => $result], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($_GET['action'] === 'upgrade') {
        // 执行升级
        $taskId = $_POST['task_id'] ?? '';
        
        if (empty($taskId)) {
            echo json_encode(['success' => false, 'message' => '无效的任务ID'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 查找任务
        $task = null;
        foreach ($upgradeTasks as $t) {
            if ($t['id'] === $taskId) {
                $task = $t;
                break;
            }
        }
        
        if (!$task) {
            echo json_encode(['success' => false, 'message' => '任务不存在'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 检查是否已安装
        $stmt = $pdo->query($task['check_sql']);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => '此升级已安装，无需重复执行'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 执行升级SQL
        try {
            $pdo->beginTransaction();
            
            foreach ($task['sql'] as $sql) {
                $pdo->exec($sql);
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => "升级完成：{$task['name']}"
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => '升级失败: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
    
    if ($_GET['action'] === 'upgrade_all') {
        // 一键升级所有待安装的任务
        $upgraded = [];
        $failed = [];
        
        try {
            foreach ($upgradeTasks as $task) {
                // 检查是否已安装
                $stmt = $pdo->query($task['check_sql']);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($count > 0) {
                    continue; // 已安装，跳过
                }
                
                // 执行升级
                $pdo->beginTransaction();
                
                try {
                    foreach ($task['sql'] as $sql) {
                        $pdo->exec($sql);
                    }
                    $pdo->commit();
                    $upgraded[] = $task['name'];
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $failed[] = $task['name'] . ': ' . $e->getMessage();
                }
            }
            
            $message = '';
            if (count($upgraded) > 0) {
                $message .= '成功升级 ' . count($upgraded) . ' 项：' . implode(', ', $upgraded);
            }
            if (count($failed) > 0) {
                $message .= ' | 失败 ' . count($failed) . ' 项：' . implode(', ', $failed);
            }
            if (count($upgraded) === 0 && count($failed) === 0) {
                $message = '所有升级已完成，数据库已是最新版本';
            }
            
            echo json_encode([
                'success' => count($failed) === 0,
                'message' => $message,
                'upgraded' => count($upgraded),
                'failed' => count($failed)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => '升级过程出错: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库升级 - <?php echo APP_NAME; ?></title>
    <link href="static/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="static/css/style.css">
    <style>
        .upgrade-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .upgrade-card.pending {
            border-left-color: #ffc107;
            background: rgba(255, 193, 7, 0.05);
        }
        .upgrade-card.installed {
            border-left-color: #28a745;
            background: rgba(40, 167, 69, 0.05);
        }
        .upgrade-card:hover {
            box-shadow: 0 4px 12px rgba(0, 255, 65, 0.2);
        }
        .status-badge {
            font-size: 0.85em;
            padding: 4px 12px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <div>
                    <h1><i class="fas fa-database"></i> 数据库升级管理</h1>
                    <p class="text-muted mb-0">检测并执行数据库结构升级任务</p>
                </div>
                <div>
                    <button class="btn btn-success me-2" onclick="upgradeAll()">
                        <i class="fas fa-rocket"></i> 一键升级全部
                    </button>
                    <button class="btn btn-primary" onclick="checkUpgrades()">
                        <i class="fas fa-sync-alt"></i> 检查更新
                    </button>
                </div>
            </div>
            
            <!-- 统计卡片 -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm stats-chart-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">总任务</small>
                                    <h3 class="mb-0 text-info" id="totalTasks">0</h3>
                                </div>
                                <i class="fas fa-tasks fa-2x text-info" style="opacity:0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm stats-chart-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">待升级</small>
                                    <h3 class="mb-0 text-warning" id="pendingTasks">0</h3>
                                </div>
                                <i class="fas fa-clock fa-2x text-warning" style="opacity:0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm stats-chart-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">已完成</small>
                                    <h3 class="mb-0 text-success" id="installedTasks">0</h3>
                                </div>
                                <i class="fas fa-check-circle fa-2x text-success" style="opacity:0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 升级任务列表 -->
            <div class="row" id="tasksContainer">
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">加载中...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="static/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
        async function checkUpgrades() {
            try {
                const response = await fetch('database_upgrade.php?action=check');
                const data = await response.json();
                
                if (data.success) {
                    displayTasks(data.tasks);
                } else {
                    alert('检查失败');
                }
            } catch (error) {
                console.error('检查升级失败:', error);
                alert('检查失败: ' + error.message);
            }
        }
        
        function displayTasks(tasks) {
            const container = document.getElementById('tasksContainer');
            
            const pending = tasks.filter(t => t.status === 'pending').length;
            const installed = tasks.filter(t => t.status === 'installed').length;
            
            document.getElementById('totalTasks').textContent = tasks.length;
            document.getElementById('pendingTasks').textContent = pending;
            document.getElementById('installedTasks').textContent = installed;
            
            if (tasks.length === 0) {
                container.innerHTML = '<div class="col-12"><div class="alert alert-info">暂无升级任务</div></div>';
                return;
            }
            
            container.innerHTML = tasks.map(task => {
                const statusBadge = task.status === 'installed' 
                    ? '<span class="badge bg-success status-badge"><i class="fas fa-check"></i> 已安装</span>'
                    : '<span class="badge bg-warning status-badge"><i class="fas fa-clock"></i> 待升级</span>';
                
                const actionButton = task.status === 'pending'
                    ? `<button class="btn btn-sm btn-primary" onclick="upgradeSingle('${task.id}')">
                        <i class="fas fa-arrow-up"></i> 立即升级
                       </button>`
                    : '<button class="btn btn-sm btn-secondary" disabled><i class="fas fa-check"></i> 已完成</button>';
                
                return `
                    <div class="col-md-6 mb-3">
                        <div class="card upgrade-card ${task.status} shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-puzzle-piece text-primary"></i> ${task.name}
                                    </h5>
                                    ${statusBadge}
                                </div>
                                <p class="card-text text-muted small mb-2">${task.description}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-code-branch"></i> 版本 ${task.version}
                                    </small>
                                    ${actionButton}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        async function upgradeSingle(taskId) {
            if (!confirm('确定要执行此升级吗？')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('task_id', taskId);
                
                const response = await fetch('database_upgrade.php?action=upgrade', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ ' + data.message);
                    checkUpgrades(); // 刷新列表
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                console.error('升级失败:', error);
                alert('升级失败: ' + error.message);
            }
        }
        
        async function upgradeAll() {
            if (!confirm('确定要一键升级所有待安装的任务吗？\n\n这将自动执行所有数据库结构更新。')) {
                return;
            }
            
            try {
                const response = await fetch('database_upgrade.php?action=upgrade_all', {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                if (data.success || data.upgraded > 0) {
                    alert('✅ ' + data.message);
                    checkUpgrades(); // 刷新列表
                } else {
                    alert('ℹ️ ' + data.message);
                }
            } catch (error) {
                console.error('一键升级失败:', error);
                alert('一键升级失败: ' + error.message);
            }
        }
        
        // 页面加载时自动检查
        checkUpgrades();
    </script>
</body>
</html>
