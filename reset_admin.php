<?php
/**
 * 管理员账号修复工具
 * 用于重置管理员密码、解除封禁、恢复管理员权限
 * 访问方式：https://你的域名/reset_admin.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// 安全验证密码
$RESET_PASSWORD = 'reset2024'; // 修改为你自己的重置密码

$authenticated = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_password'])) {
    $inputPassword = $_POST['verify_password'] ?? '';
    if ($inputPassword === $RESET_PASSWORD) {
        $authenticated = true;
    }
}

// 处理修复操作
if ($authenticated && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $pdo = getDbConnection();
        $action = $_POST['action'];
        
        if ($action === 'check_user') {
            // 检查用户信息
            $username = $_POST['username'] ?? '';
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // 检查字段是否存在
                $columnsStmt = $pdo->query("SHOW COLUMNS FROM users");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
                
                $userInfo = [
                    'exists' => true,
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => isset($user['role']) ? $user['role'] : '字段不存在',
                    'status' => isset($user['status']) ? $user['status'] : '字段不存在',
                    'banned_reason' => isset($user['banned_reason']) ? $user['banned_reason'] : null,
                    'banned_at' => isset($user['banned_at']) ? $user['banned_at'] : null,
                    'email' => isset($user['email']) ? $user['email'] : '字段不存在',
                    'created_at' => isset($user['created_at']) ? $user['created_at'] : null,
                    'has_role_field' => in_array('role', $columns),
                    'has_status_field' => in_array('status', $columns)
                ];
                
                echo json_encode(['success' => true, 'user' => $userInfo], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => '用户不存在'], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        if ($action === 'reset_password') {
            // 重置密码
            $username = $_POST['username'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            
            if (empty($username) || empty($newPassword)) {
                echo json_encode(['success' => false, 'message' => '用户名和新密码不能为空'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt->execute([$hashedPassword, $username]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => "密码已重置为：$newPassword"], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => '用户不存在或密码未改变'], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        if ($action === 'set_admin') {
            // 设置为管理员
            $username = $_POST['username'] ?? '';
            
            // 检查是否有role字段
            $columnsStmt = $pdo->query("SHOW COLUMNS FROM users");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('role', $columns)) {
                echo json_encode(['success' => false, 'message' => 'users表缺少role字段，请先运行install.php升级数据库'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => '已设置为管理员'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => '用户不存在'], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        if ($action === 'unban_user') {
            // 解除封禁
            $username = $_POST['username'] ?? '';
            
            // 检查是否有status字段
            $columnsStmt = $pdo->query("SHOW COLUMNS FROM users");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('status', $columns)) {
                echo json_encode(['success' => false, 'message' => 'users表缺少status字段，请先运行install.php升级数据库'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE users SET status = 'active', banned_reason = NULL, banned_at = NULL WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => '已解除封禁'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => '用户不存在'], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        if ($action === 'clear_login_attempts') {
            // 清除登录失败记录
            $ip = $_POST['ip'] ?? '';
            
            if (empty($ip)) {
                echo json_encode(['success' => false, 'message' => 'IP地址不能为空'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            try {
                // 清除登录尝试记录
                $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
                $stmt->execute([$ip]);
                $attemptsCleared = $stmt->rowCount();
                
                // 清除临时封禁
                $stmt = $pdo->prepare("DELETE FROM temp_ip_ban WHERE ip = ?");
                $stmt->execute([$ip]);
                $banCleared = $stmt->rowCount();
                
                echo json_encode([
                    'success' => true, 
                    'message' => "已清除 $attemptsCleared 条登录记录，$banCleared 条临时封禁"
                ], JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false, 
                    'message' => '清除失败（表可能不存在）：' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        if ($action === 'full_reset') {
            // 一键完全修复
            $username = $_POST['username'] ?? '';
            $newPassword = $_POST['new_password'] ?? 'Admin@123';
            
            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => '用户名不能为空'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $pdo->beginTransaction();
            
            try {
                $results = [];
                
                // 1. 重置密码
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
                $stmt->execute([$hashedPassword, $username]);
                $results[] = "✓ 密码已重置为：$newPassword";
                
                // 2. 设置为管理员（如果有role字段）
                $columnsStmt = $pdo->query("SHOW COLUMNS FROM users");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('role', $columns)) {
                    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE username = ?");
                    $stmt->execute([$username]);
                    $results[] = "✓ 已设置为管理员";
                }
                
                // 3. 解除封禁（如果有status字段）
                if (in_array('status', $columns)) {
                    $stmt = $pdo->prepare("UPDATE users SET status = 'active', banned_reason = NULL, banned_at = NULL WHERE username = ?");
                    $stmt->execute([$username]);
                    $results[] = "✓ 已解除封禁状态";
                }
                
                // 4. 清除IP限制（如果表存在）
                try {
                    $pdo->exec("DELETE FROM login_attempts");
                    $pdo->exec("DELETE FROM temp_ip_ban");
                    $results[] = "✓ 已清除所有IP登录限制";
                } catch (PDOException $e) {
                    $results[] = "⚠ IP限制表不存在（可忽略）";
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => '账号完全修复成功！',
                    'results' => $results
                ], JSON_UNESCAPED_UNICODE);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => '修复失败：' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '操作失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员账号修复工具</title>
    <link href="static/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/libs/fontawesome/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            min-height: 100vh;
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .reset-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .reset-card {
            background: rgba(20, 20, 20, 0.95);
            border: 1px solid #ff3b3b;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 50px rgba(255, 59, 59, 0.2);
        }
        .reset-header h1 {
            color: #ff3b3b;
            font-size: 1.8rem;
            margin-bottom: 10px;
            text-shadow: 0 0 10px rgba(255, 59, 59, 0.5);
        }
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 59, 59, 0.3);
            color: #e0e0e0;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #ff3b3b;
            box-shadow: 0 0 10px rgba(255, 59, 59, 0.3);
            color: #e0e0e0;
        }
        .btn-danger-custom {
            background: linear-gradient(135deg, rgba(255, 59, 59, 0.2), rgba(255, 59, 59, 0.1));
            border: 2px solid #ff3b3b;
            color: #ff3b3b;
            font-weight: 600;
        }
        .btn-danger-custom:hover {
            background: rgba(255, 59, 59, 0.3);
            box-shadow: 0 0 20px rgba(255, 59, 59, 0.4);
        }
        .btn-success-custom {
            background: linear-gradient(135deg, rgba(0, 255, 65, 0.2), rgba(0, 255, 65, 0.1));
            border: 2px solid #00ff41;
            color: #00ff41;
            font-weight: 600;
        }
        .btn-success-custom:hover {
            background: rgba(0, 255, 65, 0.3);
            box-shadow: 0 0 20px rgba(0, 255, 65, 0.4);
        }
        .info-box {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box i {
            color: #ffc107;
        }
        .user-info {
            background: rgba(0, 191, 255, 0.1);
            border: 1px solid #00bfff;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .result-box {
            background: rgba(0, 255, 65, 0.1);
            border: 1px solid #00ff41;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            display: none;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header text-center mb-4">
                <h1><i class="fas fa-user-shield"></i> 管理员账号修复工具</h1>
                <p class="text-muted">用于修复无法登录的管理员账号</p>
            </div>
            
            <?php if (!$authenticated): ?>
                <!-- 验证界面 -->
                <div class="info-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>安全验证</strong><br>
                    此工具可以修改账号信息，需要输入重置密码验证身份。
                </div>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-key"></i> 重置密码
                        </label>
                        <input type="password" class="form-control" name="verify_password" 
                               placeholder="请输入重置密码" required autofocus>
                        <small class="text-muted">默认密码: reset2024（请修改reset_admin.php中的密码）</small>
                    </div>
                    <button type="submit" class="btn btn-danger-custom w-100">
                        <i class="fas fa-unlock"></i> 验证身份
                    </button>
                </form>
            <?php else: ?>
                <!-- 修复界面 -->
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>使用说明</strong><br>
                    1. 输入你的管理员账号用户名<br>
                    2. 选择修复操作或使用一键修复<br>
                    3. 修复完成后返回登录页面
                </div>
                
                <!-- 用户名输入 -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user"></i> 管理员用户名
                    </label>
                    <input type="text" class="form-control" id="username" placeholder="输入你的管理员用户名" required>
                </div>
                
                <!-- 检查用户信息 -->
                <button class="btn btn-success-custom w-100 mb-3" onclick="checkUser()">
                    <i class="fas fa-search"></i> 检查账号信息
                </button>
                
                <div id="userInfo" class="user-info" style="display:none;">
                    <h6><i class="fas fa-info-circle"></i> 账号信息</h6>
                    <div id="userInfoContent"></div>
                </div>
                
                <hr>
                
                <!-- 修复选项 -->
                <h6 class="mb-3"><i class="fas fa-wrench"></i> 修复选项</h6>
                
                <div class="row g-2">
                    <div class="col-md-6">
                        <button class="btn btn-danger-custom w-100" onclick="showResetPassword()">
                            <i class="fas fa-key"></i> 重置密码
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-danger-custom w-100" onclick="setAdmin()">
                            <i class="fas fa-user-shield"></i> 设置为管理员
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-danger-custom w-100" onclick="unbanUser()">
                            <i class="fas fa-unlock-alt"></i> 解除账号封禁
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-danger-custom w-100" onclick="clearLoginAttempts()">
                            <i class="fas fa-eraser"></i> 清除IP限制
                        </button>
                    </div>
                </div>
                
                <hr>
                
                <!-- 一键修复 -->
                <button class="btn btn-success-custom w-100 mb-3" onclick="fullReset()">
                    <i class="fas fa-magic"></i> 一键完全修复（推荐）
                </button>
                
                <div id="resultBox" class="result-box">
                    <h6><i class="fas fa-check-circle"></i> 操作结果</h6>
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
        function getUsername() {
            const username = document.getElementById('username').value.trim();
            if (!username) {
                alert('请输入用户名');
                return null;
            }
            return username;
        }
        
        async function checkUser() {
            const username = getUsername();
            if (!username) return;
            
            const formData = new FormData();
            formData.append('action', 'check_user');
            formData.append('username', username);
            formData.append('verify_password', '<?php echo $RESET_PASSWORD; ?>');
            
            try {
                const response = await fetch('reset_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const user = data.user;
                    let html = `
                        <p><strong>ID:</strong> ${user.id}</p>
                        <p><strong>用户名:</strong> ${user.username}</p>
                        <p><strong>角色:</strong> <span style="color: ${user.role === 'admin' ? '#00ff41' : '#ffa500'}">${user.role}</span></p>
                        <p><strong>状态:</strong> <span style="color: ${user.status === 'active' ? '#00ff41' : '#ff3b3b'}">${user.status}</span></p>
                    `;
                    
                    if (user.banned_reason) {
                        html += `<p><strong>封禁原因:</strong> <span style="color: #ff3b3b">${user.banned_reason}</span></p>`;
                    }
                    if (user.banned_at) {
                        html += `<p><strong>封禁时间:</strong> ${user.banned_at}</p>`;
                    }
                    
                    document.getElementById('userInfoContent').innerHTML = html;
                    document.getElementById('userInfo').style.display = 'block';
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('❌ 检查失败: ' + error.message);
            }
        }
        
        function showResetPassword() {
            const username = getUsername();
            if (!username) return;
            
            const newPassword = prompt('请输入新密码（至少8位，包含字母和数字）:', 'Admin@123');
            if (!newPassword) return;
            
            resetPassword(username, newPassword);
        }
        
        async function resetPassword(username, newPassword) {
            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('username', username);
            formData.append('new_password', newPassword);
            formData.append('verify_password', '<?php echo $RESET_PASSWORD; ?>');
            
            try {
                const response = await fetch('reset_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                showResult(data);
            } catch (error) {
                alert('❌ 操作失败: ' + error.message);
            }
        }
        
        async function setAdmin() {
            const username = getUsername();
            if (!username) return;
            
            if (!confirm('确定要将此账号设置为管理员吗？')) return;
            
            const formData = new FormData();
            formData.append('action', 'set_admin');
            formData.append('username', username);
            formData.append('verify_password', '<?php echo $RESET_PASSWORD; ?>');
            
            try {
                const response = await fetch('reset_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                showResult(data);
            } catch (error) {
                alert('❌ 操作失败: ' + error.message);
            }
        }
        
        async function unbanUser() {
            const username = getUsername();
            if (!username) return;
            
            if (!confirm('确定要解除此账号的封禁状态吗？')) return;
            
            const formData = new FormData();
            formData.append('action', 'unban_user');
            formData.append('username', username);
            formData.append('verify_password', '<?php echo $RESET_PASSWORD; ?>');
            
            try {
                const response = await fetch('reset_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                showResult(data);
            } catch (error) {
                alert('❌ 操作失败: ' + error.message);
            }
        }
        
        async function clearLoginAttempts() {
            const ip = prompt('请输入要清除限制的IP地址:', '<?php echo $_SERVER['REMOTE_ADDR']; ?>');
            if (!ip) return;
            
            const formData = new FormData();
            formData.append('action', 'clear_login_attempts');
            formData.append('ip', ip);
            formData.append('verify_password', '<?php echo $RESET_PASSWORD; ?>');
            
            try {
                const response = await fetch('reset_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                showResult(data);
            } catch (error) {
                alert('❌ 操作失败: ' + error.message);
            }
        }
        
        async function fullReset() {
            const username = getUsername();
            if (!username) return;
            
            if (!confirm('确定要一键完全修复此账号吗？\n\n将执行以下操作：\n1. 重置密码为 Admin@123\n2. 设置为管理员\n3. 解除封禁状态\n4. 清除所有IP登录限制')) return;
            
            const formData = new FormData();
            formData.append('action', 'full_reset');
            formData.append('username', username);
            formData.append('verify_password', '<?php echo $RESET_PASSWORD; ?>');
            
            try {
                const response = await fetch('reset_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let html = '<p style="color: #00ff41; font-weight: 600;">' + data.message + '</p>';
                    if (data.results) {
                        html += '<ul>';
                        data.results.forEach(result => {
                            html += '<li>' + result + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    document.getElementById('resultContent').innerHTML = html;
                    document.getElementById('resultBox').style.display = 'block';
                    
                    setTimeout(() => {
                        alert('✅ 修复成功！\n\n现在可以使用以下信息登录：\n用户名: ' + username + '\n密码: Admin@123\n\n点击确定跳转到登录页面');
                        window.location.href = 'login.php';
                    }, 1000);
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('❌ 操作失败: ' + error.message);
            }
        }
        
        function showResult(data) {
            if (data.success) {
                document.getElementById('resultContent').innerHTML = '<p style="color: #00ff41;">' + data.message + '</p>';
                document.getElementById('resultBox').style.display = 'block';
                alert('✅ ' + data.message);
            } else {
                alert('❌ ' + data.message);
            }
        }
    </script>
</body>
</html>
