<?php
/**
 * 数据库安装/升级脚本
 * 独立运行，无需登录
 * 访问方式：https://你的域名/install.php
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 引入配置文件
require_once 'config.php';

// 安全验证：可以设置一个安装密码
$INSTALL_PASSWORD = 'xss2024'; // 修改为你自己的安装密码

// 检查是否提交了密码
$authenticated = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputPassword = $_POST['password'] ?? '';
    if ($inputPassword === $INSTALL_PASSWORD) {
        $authenticated = true;
    }
}

// 如果有action参数且已认证，执行升级
if ($authenticated && isset($_GET['action']) && $_GET['action'] === 'upgrade') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $pdo = getDbConnection();
        $results = [];
        
        // ==================== 升级 users 表 ====================
        $results[] = ['task' => '检查 users 表结构', 'status' => 'start'];
        
        // 检查表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() === 0) {
            // 创建 users 表
            $pdo->exec("
                CREATE TABLE `users` (
                  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `username` VARCHAR(50) NOT NULL UNIQUE,
                  `password` VARCHAR(255) NOT NULL,
                  `role` VARCHAR(20) DEFAULT 'user' COMMENT '用户角色 admin/user',
                  `email` VARCHAR(100) DEFAULT NULL,
                  `status` VARCHAR(20) DEFAULT 'active' COMMENT '状态 active/banned',
                  `banned_reason` TEXT NULL COMMENT '封禁原因',
                  `banned_at` TIMESTAMP NULL COMMENT '封禁时间',
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_username` (`username`),
                  KEY `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $results[] = ['task' => '创建 users 表', 'status' => 'success'];
        } else {
            // 表存在，检查并添加缺失的字段
            $columnsStmt = $pdo->query("SHOW COLUMNS FROM users");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('role', $columns)) {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `role` VARCHAR(20) DEFAULT 'user' COMMENT '用户角色 admin/user' AFTER `password`");
                $results[] = ['task' => '添加 role 字段', 'status' => 'success'];
            }
            
            if (!in_array('email', $columns)) {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `email` VARCHAR(100) DEFAULT NULL AFTER `role`");
                $results[] = ['task' => '添加 email 字段', 'status' => 'success'];
            }
            
            if (!in_array('status', $columns)) {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `status` VARCHAR(20) DEFAULT 'active' COMMENT '状态 active/banned' AFTER `email`");
                $results[] = ['task' => '添加 status 字段', 'status' => 'success'];
            }
            
            if (!in_array('banned_reason', $columns)) {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `banned_reason` TEXT NULL COMMENT '封禁原因' AFTER `status`");
                $results[] = ['task' => '添加 banned_reason 字段', 'status' => 'success'];
            }
            
            if (!in_array('banned_at', $columns)) {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `banned_at` TIMESTAMP NULL COMMENT '封禁时间' AFTER `banned_reason`");
                $results[] = ['task' => '添加 banned_at 字段', 'status' => 'success'];
            }
        }
        
        // ==================== 升级 logs 表 ====================
        $results[] = ['task' => '检查 logs 表结构', 'status' => 'start'];
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'logs'");
        if ($stmt->rowCount() > 0) {
            $logsColumnsStmt = $pdo->query("SHOW COLUMNS FROM logs");
            $logsColumns = $logsColumnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('user_id', $logsColumns)) {
                $pdo->exec("ALTER TABLE `logs` ADD COLUMN `user_id` INT UNSIGNED NULL COMMENT '用户ID' AFTER `log_id`");
                $pdo->exec("ALTER TABLE `logs` ADD INDEX `idx_user_id` (`user_id`)");
                $results[] = ['task' => '添加 user_id 字段', 'status' => 'success'];
            }
            
            if (!in_array('is_gov_site', $logsColumns)) {
                $pdo->exec("ALTER TABLE `logs` ADD COLUMN `is_gov_site` TINYINT(1) DEFAULT 0 COMMENT '是否为政府网站 0=否 1=是' AFTER `content_type`");
                $pdo->exec("ALTER TABLE `logs` ADD INDEX `idx_is_gov_site` (`is_gov_site`)");
                $results[] = ['task' => '添加 is_gov_site 字段', 'status' => 'success'];
            }
        }
        
        // ==================== 创建 login_attempts 表 ====================
        $stmt = $pdo->query("SHOW TABLES LIKE 'login_attempts'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("
                CREATE TABLE `login_attempts` (
                  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `ip` VARCHAR(45) NOT NULL,
                  `username` VARCHAR(50) NOT NULL,
                  `success` TINYINT(1) DEFAULT 0 COMMENT '0=失败 1=成功',
                  `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_ip` (`ip`),
                  KEY `idx_username` (`username`),
                  KEY `idx_attempt_time` (`attempt_time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $results[] = ['task' => '创建 login_attempts 表', 'status' => 'success'];
        }
        
        // ==================== 创建 temp_ip_ban 表 ====================
        $stmt = $pdo->query("SHOW TABLES LIKE 'temp_ip_ban'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("
                CREATE TABLE `temp_ip_ban` (
                  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `ip` VARCHAR(45) NOT NULL UNIQUE,
                  `reason` VARCHAR(255) DEFAULT NULL,
                  `ban_until` TIMESTAMP NOT NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_ip` (`ip`),
                  KEY `idx_ban_until` (`ban_until`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $results[] = ['task' => '创建 temp_ip_ban 表', 'status' => 'success'];
        }
        
        // ==================== 创建 ip_blacklist 表 ====================
        $stmt = $pdo->query("SHOW TABLES LIKE 'ip_blacklist'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("
                CREATE TABLE `ip_blacklist` (
                  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `ip` VARCHAR(45) NOT NULL UNIQUE,
                  `reason` VARCHAR(255) DEFAULT NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_ip` (`ip`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $results[] = ['task' => '创建 ip_blacklist 表', 'status' => 'success'];
        }
        
        // ==================== 创建默认管理员账号 ====================
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount == 0) {
            // 创建默认管理员账号：admin / Admin@123
            $defaultAdminPassword = password_hash('Admin@123', PASSWORD_BCRYPT);
            $pdo->exec("
                INSERT INTO users (username, password, role, email, status) 
                VALUES ('admin', '$defaultAdminPassword', 'admin', 'admin@example.com', 'active')
            ");
            $results[] = ['task' => '创建默认管理员账号', 'status' => 'success', 'note' => '用户名: admin, 密码: Admin@123'];
        }
        
        $results[] = ['task' => '数据库升级完成', 'status' => 'complete'];
        
        echo json_encode([
            'success' => true,
            'message' => '数据库升级成功！',
            'results' => $results
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => '数据库升级失败: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库安装/升级 - 蓝莲花XSS平台</title>
    <link href="static/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/libs/fontawesome/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #e0e0e0;
        }
        .install-container {
            max-width: 600px;
            width: 100%;
            padding: 20px;
        }
        .install-card {
            background: rgba(20, 20, 20, 0.95);
            border: 1px solid #00ff41;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 0 50px rgba(0, 255, 65, 0.2);
        }
        .install-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .install-header h1 {
            color: #00ff41;
            font-size: 2rem;
            margin-bottom: 10px;
            text-shadow: 0 0 10px rgba(0, 255, 65, 0.5);
        }
        .install-header p {
            color: #888;
            font-size: 0.9rem;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(0, 255, 65, 0.3);
            color: #e0e0e0;
            border-radius: 5px;
            padding: 12px;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #00ff41;
            box-shadow: 0 0 10px rgba(0, 255, 65, 0.3);
            color: #e0e0e0;
        }
        .btn-install {
            background: linear-gradient(135deg, rgba(0, 255, 65, 0.2), rgba(0, 255, 65, 0.1));
            border: 2px solid #00ff41;
            color: #00ff41;
            font-weight: 600;
            padding: 12px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-install:hover {
            background: rgba(0, 255, 65, 0.3);
            box-shadow: 0 0 20px rgba(0, 255, 65, 0.4);
            transform: translateY(-2px);
        }
        .result-box {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 255, 65, 0.3);
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }
        .result-item {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .result-item:last-child {
            border-bottom: none;
        }
        .status-success {
            color: #00ff41;
        }
        .status-start {
            color: #00bfff;
        }
        .status-complete {
            color: #ffa500;
            font-weight: 600;
        }
        .warning-box {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .warning-box i {
            color: #ffc107;
        }
        .info-box {
            background: rgba(0, 191, 255, 0.1);
            border: 1px solid #00bfff;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box i {
            color: #00bfff;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <h1><i class="fas fa-database"></i> 数据库安装/升级</h1>
                <p>蓝莲花XSS在线平台 v2.0.8</p>
            </div>
            
            <?php if (!$authenticated): ?>
                <!-- 未认证，显示密码输入表单 -->
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>安全提示</strong><br>
                    此页面将对数据库进行修改，需要输入安装密码验证身份。
                </div>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-key"></i> 安装密码
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="请输入安装密码" required autofocus>
                        <small class="text-muted">默认密码: xss2024（请修改install.php中的密码）</small>
                    </div>
                    
                    <button type="submit" class="btn btn-install w-100">
                        <i class="fas fa-unlock"></i> 验证密码
                    </button>
                </form>
            <?php else: ?>
                <!-- 已认证，显示升级界面 -->
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>升级说明</strong><br>
                    点击下方按钮将自动检测并升级数据库结构，包括：
                    <ul class="mb-0 mt-2">
                        <li>users 表添加 role、email、status 等字段</li>
                        <li>logs 表添加 user_id、is_gov_site 字段</li>
                        <li>创建 login_attempts、temp_ip_ban、ip_blacklist 表</li>
                        <li>如果没有管理员账号，将创建默认管理员</li>
                    </ul>
                </div>
                
                <button type="button" class="btn btn-install w-100" onclick="startUpgrade()">
                    <i class="fas fa-rocket"></i> 开始升级数据库
                </button>
                
                <div id="resultBox" class="result-box">
                    <h6><i class="fas fa-list"></i> 升级日志</h6>
                    <div id="resultContent"></div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="text-success">
                        <i class="fas fa-arrow-left"></i> 返回登录页面
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        async function startUpgrade() {
            const resultBox = document.getElementById('resultBox');
            const resultContent = document.getElementById('resultContent');
            
            // 显示结果框
            resultBox.style.display = 'block';
            resultContent.innerHTML = '<p class="status-start"><i class="fas fa-spinner fa-spin"></i> 正在升级数据库...</p>';
            
            try {
                const response = await fetch('install.php?action=upgrade', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'password=<?php echo $INSTALL_PASSWORD; ?>'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let html = '';
                    data.results.forEach(item => {
                        const statusClass = item.status === 'success' ? 'status-success' : 
                                          item.status === 'complete' ? 'status-complete' : 'status-start';
                        const icon = item.status === 'success' ? 'fa-check-circle' : 
                                   item.status === 'complete' ? 'fa-flag-checkered' : 'fa-cog';
                        
                        html += `<div class="result-item ${statusClass}">`;
                        html += `<i class="fas ${icon}"></i> ${item.task}`;
                        if (item.note) {
                            html += `<br><small style="color: #ffa500;">${item.note}</small>`;
                        }
                        html += `</div>`;
                    });
                    
                    resultContent.innerHTML = html;
                    
                    // 显示成功提示
                    setTimeout(() => {
                        alert('✅ 数据库升级成功！\n\n现在可以正常登录了。\n\n如果创建了默认管理员账号：\n用户名: admin\n密码: Admin@123');
                    }, 500);
                } else {
                    resultContent.innerHTML = `<p style="color: #ff3b3b;"><i class="fas fa-times-circle"></i> ${data.message}</p>`;
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                resultContent.innerHTML = `<p style="color: #ff3b3b;"><i class="fas fa-times-circle"></i> 升级失败: ${error.message}</p>`;
                alert('❌ 升级失败: ' + error.message);
            }
        }
    </script>
</body>
</html>
