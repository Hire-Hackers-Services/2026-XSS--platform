<?php
/**
 * 登录页面
 */
require_once 'config.php';
require_once 'includes/Captcha.php';
session_start();

// 如果已登录，重定向到管理后台
if (isLoggedIn()) {
    header('Location: admin.php');
    exit;
}

// 获取客户端IP
$clientIp = getClientIp();

/**
 * 检查IP是否被临时封禁
 */
function checkTempBan($pdo, $ip) {
    // 清理过期的封禁
    $pdo->exec("DELETE FROM temp_ip_ban WHERE ban_until < NOW()");
    
    // 检查当前IP是否被封禁
    $stmt = $pdo->prepare("SELECT ban_until, reason FROM temp_ip_ban WHERE ip = ? AND ban_until > NOW()");
    $stmt->execute([$ip]);
    return $stmt->fetch();
}

/**
 * 记录登录尝试
 */
function recordLoginAttempt($pdo, $ip, $username, $success) {
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, ?)");
    $stmt->execute([$ip, $username, $success ? 1 : 0]);
}

/**
 * 检查登录失败次数
 */
function getFailedAttempts($pdo, $ip, $minutes = 30) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM login_attempts 
        WHERE ip = ? AND success = 0 AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    $stmt->execute([$ip, $minutes]);
    return (int)$stmt->fetchColumn();
}

/**
 * 封禁IP一段时间
 */
function banIpTemporarily($pdo, $ip, $hours, $reason) {
    $stmt = $pdo->prepare("
        INSERT INTO temp_ip_ban (ip, reason, ban_until) 
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))
        ON DUPLICATE KEY UPDATE ban_until = DATE_ADD(NOW(), INTERVAL ? HOUR), reason = ?
    ");
    $stmt->execute([$ip, $reason, $hours, $hours, $reason]);
}

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? 'login';
    
    // 注册功能
    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $captcha = trim($_POST['captcha'] ?? '');
        
        // 验证验证码
        if (!Captcha::verify($captcha)) {
            echo json_encode(['success' => false, 'message' => '验证码错误或已过期！']);
            exit;
        }
        
        // 获取客户端IP
        $clientIp = getClientIp();
        
        try {
            $pdo = getDbConnection();
            
            // 检查IP是否被封禁
            $stmt = $pdo->prepare("SELECT reason FROM ip_blacklist WHERE ip = ?");
            $stmt->execute([$clientIp]);
            $ipBan = $stmt->fetch();
            
            if ($ipBan) {
                $reason = $ipBan['reason'] ?: '您的IP已被封禁';
                echo json_encode(['success' => false, 'message' => $reason]);
                exit;
            }
        } catch (PDOException $e) {
            // ip_blacklist表可能不存在，忽略此错误继续执行
        }
        
        // 验证输入
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => '用户名和密码不能为空']);
            exit;
        }
        
        if (strlen($username) < 3 || strlen($username) > 30) {
            echo json_encode(['success' => false, 'message' => '用户名长度必须在3-30个字符之间']);
            exit;
        }
        
        // 验证用户名格式（只允许字母、数字、下划线）
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            echo json_encode(['success' => false, 'message' => '用户名只能包含字母、数字和下划线']);
            exit;
        }
        
        // 验证密码强度（至少8位，包含字母和数字）
        if (strlen($password) < 8) {
            echo json_encode(['success' => false, 'message' => '密码至少8个字符']);
            exit;
        }
        
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            echo json_encode(['success' => false, 'message' => '密码必须包含字母和数字']);
            exit;
        }
        
        // 验证邮箱格式
        if (!empty($email) && !validateEmail($email)) {
            echo json_encode(['success' => false, 'message' => '邮箱格式不正确']);
            exit;
        }
        
        try {
            $pdo = getDbConnection();
            
            // 检查用户名是否已存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => '用户名已存在']);
                exit;
            }
            
            // 开始事务
            $pdo->beginTransaction();
            
            // 插入新用户（兼容没有role和email字段的情况）
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // 检查users表是否有role和email字段
            $columnsStmt = $pdo->query("SHOW COLUMNS FROM users");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $hasRoleColumn = in_array('role', $columns);
            $hasEmailColumn = in_array('email', $columns);
            
            if ($hasRoleColumn && $hasEmailColumn) {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'user', ?)");
                $stmt->execute([$username, $hashedPassword, $email]);
            } elseif ($hasRoleColumn) {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
                $stmt->execute([$username, $hashedPassword]);
            } elseif ($hasEmailColumn) {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hashedPassword, $email]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $hashedPassword]);
            }
            
            $newUserId = $pdo->lastInsertId();
            
            // 为新用户创建演示XSS日志（兼容没有user_id字段的情况）
            $demoLogId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            $demoData = [
                'log_id' => $demoLogId,
                'user_id' => $newUserId,
                'ip' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'referer' => 'https://example.com/vulnerable-page.html',
                'url' => 'https://your-xss-platform.com/?uid=' . $newUserId . '&js=alert("XSS")',
                'method' => 'GET',
                'endpoint' => '/',
                'cookies' => json_encode([
                    'sessionid' => 'demo_session_' . substr(md5(uniqid()), 0, 16),
                    'username' => 'demo_user',
                    'token' => 'demo_token_' . substr(md5(uniqid()), 0, 20)
                ]),
                'headers' => json_encode([
                    'Host' => 'your-xss-platform.com',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection' => 'keep-alive'
                ]),
                'data' => json_encode([
                    'uid' => $newUserId,
                    'js' => 'alert("XSS演示")',
                    'timestamp' => time()
                ]),
                'data_type' => 'query_params',
                'raw_data' => 'uid=' . $newUserId . '&js=alert("XSS演示")',
                'content_type' => 'text/html',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // 检查logs表是否有user_id字段
            try {
                $logsColumnsStmt = $pdo->query("SHOW COLUMNS FROM logs");
                $logsColumns = $logsColumnsStmt->fetchAll(PDO::FETCH_COLUMN);
                $hasUserIdColumn = in_array('user_id', $logsColumns);
                
                if ($hasUserIdColumn) {
                    $stmt = $pdo->prepare("
                        INSERT INTO logs 
                        (log_id, user_id, ip, user_agent, referer, url, method, endpoint, cookies, headers, data, data_type, raw_data, content_type, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $demoData['log_id'],
                        $demoData['user_id'],
                        $demoData['ip'],
                        $demoData['user_agent'],
                        $demoData['referer'],
                        $demoData['url'],
                        $demoData['method'],
                        $demoData['endpoint'],
                        $demoData['cookies'],
                        $demoData['headers'],
                        $demoData['data'],
                        $demoData['data_type'],
                        $demoData['raw_data'],
                        $demoData['content_type'],
                        $demoData['created_at']
                    ]);
                } else {
                    // 如果没有user_id字段，不插入演示日志
                }
            } catch (PDOException $e) {
                // 演示日志插入失败，不影响注册，继续执行
            }
            
            // 提交事务
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => '注册成功！请登录查看']);
        } catch (PDOException $e) {
            // 回滚事务
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => '注册失败：' . $e->getMessage()]);
        }
        exit;
    }
    
    // 登录功能
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $captcha = trim($_POST['captcha'] ?? '');
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => '用户名和密码不能为空']);
        exit;
    }
    
    // 验证验证码
    if (!Captcha::verify($captcha)) {
        try {
            $pdo = getDbConnection();
            recordLoginAttempt($pdo, $clientIp, $username, false);
        } catch (Exception $e) {
            // 忽略记录失败的错误
        }
        echo json_encode(['success' => false, 'message' => '验证码错误或已过期！']);
        exit;
    }
    
    try {
        $pdo = getDbConnection();
        
        // 检查IP是否被临时封禁（如果表存在）
        try {
            $tempBan = checkTempBan($pdo, $clientIp);
            if ($tempBan) {
                $banUntil = date('Y-m-d H:i:s', strtotime($tempBan['ban_until']));
                $reason = $tempBan['reason'] ?: '登录失败次数过多';
                echo json_encode([
                    'success' => false, 
                    'message' => $reason . '，封禁至：' . $banUntil,
                    'banned' => true,
                    'ban_until' => $banUntil
                ]);
                exit;
            }
        } catch (PDOException $e) {
            // temp_ip_ban表可能不存在，忽略此错误
        }
        
        // 检查最近失败次数（如果表存在）
        try {
            $failedAttempts = getFailedAttempts($pdo, $clientIp);
            if ($failedAttempts >= 6) {
                // 自动封禁6小时
                banIpTemporarily($pdo, $clientIp, 6, '登录失败6次，自动封禁6小时');
                echo json_encode([
                    'success' => false, 
                    'message' => '登录失败次数过多，您的IP已被封禁6小时！',
                    'banned' => true
                ]);
                exit;
            }
        } catch (PDOException $e) {
            // login_attempts表可能不存在，忽略此错误，设置默认值
            $failedAttempts = 0;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // 检查用户是否被封禁（如果有status字段）
            if (isset($user['status']) && $user['status'] === 'banned') {
                try {
                    recordLoginAttempt($pdo, $clientIp, $username, false);
                } catch (Exception $e) {
                    // 忽略记录失败
                }
                $reason = isset($user['banned_reason']) && $user['banned_reason'] ? $user['banned_reason'] : '您的账号已被封禁';
                $bannedTime = isset($user['banned_at']) && $user['banned_at'] ? ' (封禁时间: ' . $user['banned_at'] . ')' : '';
                echo json_encode(['success' => false, 'message' => $reason . $bannedTime]);
                exit;
            }
            
            // 登录成功，记录成功的尝试
            try {
                recordLoginAttempt($pdo, $clientIp, $username, true);
            } catch (Exception $e) {
                // 忽略记录失败
            }
            
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'] ?? 'user';  // 保存用户角色
            
            echo json_encode(['success' => true, 'redirect' => 'admin.php']);
        } else {
            // 记录失败的尝试
            try {
                recordLoginAttempt($pdo, $clientIp, $username, false);
                $remainingAttempts = 6 - ($failedAttempts ?? 0) - 1;
            } catch (Exception $e) {
                // 如果记录失败，设置默认剩余次数
                $remainingAttempts = 5;
            }
            
            if ($remainingAttempts <= 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => '用户名或密码错误！登录失败次数过多，请稍后再试！',
                    'attempts' => 0
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => '用户名或密码错误！剩余尝试次数：' . $remainingAttempts,
                    'attempts' => $remainingAttempts
                ]);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '登录失败：' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="tu/xssicon.png">
    <link rel="shortcut icon" type="image/png" href="tu/xssicon.png">
    <link rel="apple-touch-icon" href="tu/xssicon.png">
    
    <title>登录 - <?php echo APP_NAME; ?></title>
    <link href="static/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="static/css/style.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        @keyframes scanLine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-wrapper fade-in">
            <div class="login-header text-center">
                <h2><i class="fas fa-terminal"></i> <?php echo APP_NAME; ?></h2>
                <p class="text-muted">v<?php echo APP_VERSION; ?></p>
            </div>
            
            <div class="card shadow">
                <div class="card-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名</label>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">密码</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="captcha" class="form-label">验证码</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="captcha" name="captcha" placeholder="请输入验证码" required maxlength="4">
                                <img src="api/captcha.php" id="captchaImg" class="captcha-img" alt="验证码" onclick="refreshCaptcha()" title="点击刷新">
                            </div>
                            <small class="text-muted">看不清？点击图片刷新</small>
                        </div>
                        
                        <div id="error-message" class="alert alert-danger" style="display:none;"></div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-sign-in-alt"></i> 登录
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="#" id="showRegister" class="text-success">
                            <i class="fas fa-user-plus"></i> 还没有账号？点击注册
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- 注册表单卡片（初始隐藏） -->
            <div class="card shadow mt-3" id="registerCard" style="display: none;">
                <div class="card-body">
                    <h5 class="card-title text-center mb-3">
                        <i class="fas fa-user-plus"></i> 用户注册
                    </h5>
                    <form id="registerForm">
                        <div class="mb-3">
                            <label for="reg_username" class="form-label">用户名</label>
                            <input type="text" class="form-control" id="reg_username" name="username" required>
                            <div class="form-text">至少3个字符</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reg_password" class="form-label">密码</label>
                            <input type="password" class="form-control" id="reg_password" name="password" required>
                            <div class="form-text">至少6个字符</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reg_email" class="form-label">邮箱（可选）</label>
                            <input type="email" class="form-control" id="reg_email" name="email">
                        </div>
                        
                        <div class="mb-3">
                            <label for="reg_captcha" class="form-label">验证码</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="reg_captcha" name="captcha" placeholder="请输入验证码" required maxlength="4">
                                <img src="api/captcha.php" id="regCaptchaImg" class="captcha-img" alt="验证码" onclick="refreshRegCaptcha()" title="点击刷新">
                            </div>
                            <small class="text-muted">看不清？点击图片刷新</small>
                        </div>
                        
                        <div id="register-message" class="alert" style="display:none;"></div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-user-plus"></i> 注册
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="#" id="showLogin" class="text-success">
                            <i class="fas fa-sign-in-alt"></i> 已有账号？点击登录
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <small class="text-muted">⚠️ 仅供授权的安全测试使用</small>
            </div>
        </div>
    </div>
    
    <script>
        // 刷新登录验证码
        function refreshCaptcha() {
            document.getElementById('captchaImg').src = 'api/captcha.php?' + Math.random();
        }
        
        // 刷新注册验证码
        function refreshRegCaptcha() {
            document.getElementById('regCaptchaImg').src = 'api/captcha.php?' + Math.random();
        }
        
        // 显示法律声明弹窗
        function showLegalNotice() {
            const modal = document.createElement('div');
            modal.id = 'legalNoticeModal';
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
                z-index: 9999;
                animation: fadeIn 0.3s ease;
                backdrop-filter: blur(10px);
            `;
            
            modal.innerHTML = `
                <div style="
                    background: #0a0a0a;
                    border: 1px solid #00ff41;
                    box-shadow: 0 0 50px rgba(0, 255, 65, 0.2), inset 0 0 30px rgba(0, 255, 65, 0.05);
                    max-width: 700px;
                    width: 92%;
                    max-height: 85vh;
                    overflow: hidden;
                    font-family: 'Roboto Mono', 'Courier New', monospace;
                ">
                    <!-- 顶部扫描线效果 -->
                    <div style="
                        position: relative;
                        background: linear-gradient(180deg, rgba(0, 255, 65, 0.15) 0%, rgba(0, 0, 0, 0) 100%);
                        padding: 25px 30px;
                        border-bottom: 1px solid rgba(0, 255, 65, 0.3);
                    ">
                        <div style="
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            height: 2px;
                            background: linear-gradient(90deg, transparent, #00ff41, transparent);
                            animation: scanLine 3s linear infinite;
                        "></div>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="
                                width: 50px;
                                height: 50px;
                                background: rgba(0, 255, 65, 0.1);
                                border: 2px solid #00ff41;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                box-shadow: 0 0 20px rgba(0, 255, 65, 0.3);
                            ">
                                <i class="fas fa-shield-alt" style="color: #00ff41; font-size: 24px;"></i>
                            </div>
                            <div>
                                <h2 style="
                                    margin: 0;
                                    color: #00ff41;
                                    font-size: 1.4rem;
                                    font-weight: 600;
                                    letter-spacing: 2px;
                                    text-transform: uppercase;
                                    text-shadow: 0 0 10px rgba(0, 255, 65, 0.5);
                                ">SECURITY NOTICE</h2>
                                <p style="
                                    margin: 5px 0 0 0;
                                    color: #888;
                                    font-size: 0.75rem;
                                    letter-spacing: 1px;
                                ">法律声明与使用协议</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 内容滚动区域 -->
                    <div style="
                        padding: 30px;
                        color: #e0e0e0;
                        line-height: 1.9;
                        font-size: 14px;
                        max-height: 50vh;
                        overflow-y: auto;
                    " class="legal-scroll">
                        <!-- 重要提示框 -->
                        <div style="
                            background: rgba(255, 59, 59, 0.08);
                            border: 1px solid #ff3b3b;
                            padding: 20px;
                            margin-bottom: 25px;
                            position: relative;
                        ">
                            <div style="
                                position: absolute;
                                top: -1px;
                                left: -1px;
                                width: 4px;
                                height: 40px;
                                background: #ff3b3b;
                                box-shadow: 0 0 10px #ff3b3b;
                            "></div>
                            <div style="display: flex; align-items: flex-start; gap: 12px;">
                                <i class="fas fa-exclamation-circle" style="color: #ff3b3b; font-size: 20px; margin-top: 2px;"></i>
                                <div>
                                    <p style="margin: 0 0 10px 0; color: #ff3b3b; font-weight: 600; font-size: 15px; text-transform: uppercase; letter-spacing: 1px;">重要安全警告</p>
                                    <p style="margin: 0; color: #c0c0c0; line-height: 1.7;">本XSS平台仅供<strong style="color: #00ff41;">授权安全测试</strong>使用。任何未经授权的渗透测试行为均属<strong style="color: #ff3b3b;">违法行为</strong>，将承担相应法律责任。</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 禁止行为 -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="
                                color: #ff3b3b;
                                font-size: 13px;
                                text-transform: uppercase;
                                letter-spacing: 2px;
                                margin: 0 0 15px 0;
                                padding-bottom: 10px;
                                border-bottom: 1px solid rgba(255, 59, 59, 0.3);
                            "><i class="fas fa-ban"></i> PROHIBITED ACTIVITIES</h3>
                            <div style="display: grid; gap: 12px;">
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(255, 255, 255, 0.02); border-left: 3px solid #ff3b3b;">
                                    <i class="fas fa-times" style="color: #ff3b3b; margin-top: 3px;"></i>
                                    <span><strong style="color: #00ff41;">政府机构</strong>及其下属网站、系统、平台的任何形式渗透测试</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(255, 255, 255, 0.02); border-left: 3px solid #ff3b3b;">
                                    <i class="fas fa-times" style="color: #ff3b3b; margin-top: 3px;"></i>
                                    <span><strong style="color: #00ff41;">企业公司</strong>、商业组织的生产环境、办公系统等未授权测试</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(255, 255, 255, 0.02); border-left: 3px solid #ff3b3b;">
                                    <i class="fas fa-times" style="color: #ff3b3b; margin-top: 3px;"></i>
                                    <span><strong style="color: #00ff41;">教育机构</strong>、医疗系统、金融平台等关键基础设施</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(255, 255, 255, 0.02); border-left: 3px solid #ff3b3b;">
                                    <i class="fas fa-times" style="color: #ff3b3b; margin-top: 3px;"></i>
                                    <span>任何<strong style="color: #00ff41;">未获得明确书面授权</strong>的第三方网站或系统</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(255, 255, 255, 0.02); border-left: 3px solid #ff3b3b;">
                                    <i class="fas fa-times" style="color: #ff3b3b; margin-top: 3px;"></i>
                                    <span>利用本平台进行<strong style="color: #ff3b3b;">恶意攻击、数据窃取、勒索</strong>等犯罪活动</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 合法用途 -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="
                                color: #00ff41;
                                font-size: 13px;
                                text-transform: uppercase;
                                letter-spacing: 2px;
                                margin: 0 0 15px 0;
                                padding-bottom: 10px;
                                border-bottom: 1px solid rgba(0, 255, 65, 0.3);
                            "><i class="fas fa-check-circle"></i> LEGITIMATE USE CASES</h3>
                            <div style="display: grid; gap: 12px;">
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(0, 255, 65, 0.03); border-left: 3px solid #00ff41;">
                                    <i class="fas fa-check" style="color: #00ff41; margin-top: 3px;"></i>
                                    <span>已获得<strong style="color: #00ff41;">正式授权书/授权函</strong>的安全测试项目</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(0, 255, 65, 0.03); border-left: 3px solid #00ff41;">
                                    <i class="fas fa-check" style="color: #00ff41; margin-top: 3px;"></i>
                                    <span>个人/团队<strong style="color: #00ff41;">自有项目</strong>的安全评估与漏洞研究</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(0, 255, 65, 0.03); border-left: 3px solid #00ff41;">
                                    <i class="fas fa-check" style="color: #00ff41; margin-top: 3px;"></i>
                                    <span>网络安全<strong style="color: #00ff41;">教育培训、学术研究</strong>等非商业用途</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(0, 255, 65, 0.03); border-left: 3px solid #00ff41;">
                                    <i class="fas fa-check" style="color: #00ff41; margin-top: 3px;"></i>
                                    <span>符合当地法律法规的<strong style="color: #00ff41;">合法渗透测试</strong>项目</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 法律条款 -->
                        <div style="
                            background: rgba(255, 170, 0, 0.05);
                            border: 1px solid rgba(255, 170, 0, 0.3);
                            padding: 20px;
                            margin-bottom: 20px;
                        ">
                            <h3 style="
                                color: #ffaa00;
                                font-size: 13px;
                                text-transform: uppercase;
                                letter-spacing: 2px;
                                margin: 0 0 12px 0;
                            "><i class="fas fa-gavel"></i> LEGAL DISCLAIMER</h3>
                            <div style="color: #b0b0b0; font-size: 13px; line-height: 1.8;">
                                <p style="margin: 0 0 10px 0;">• 使用本平台即表示您已完全理解并同意遵守上述所有条款</p>
                                <p style="margin: 0 0 10px 0;">• 违反规定造成的一切法律后果由<strong style="color: #ffaa00;">使用者本人承担</strong></p>
                                <p style="margin: 0;">• 本声明适用于<strong style="color: #ffaa00;">国际网络安全法律</strong>及您所在地区的相关法规</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 底部操作栏 -->
                    <div style="
                        background: rgba(0, 0, 0, 0.5);
                        padding: 20px 30px;
                        border-top: 1px solid rgba(0, 255, 65, 0.2);
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <div style="display: flex; align-items: center; gap: 10px; color: #888; font-size: 12px;">
                            <i class="fas fa-clock"></i>
                            <span id="timerText">请仔细阅读 (<span id="countdown">5</span>s)</span>
                        </div>
                        <button id="agreeBtn" disabled style="
                            background: #333;
                            border: 1px solid #555;
                            color: #666;
                            padding: 12px 35px;
                            cursor: not-allowed;
                            font-family: 'Roboto Mono', monospace;
                            font-size: 13px;
                            font-weight: 600;
                            text-transform: uppercase;
                            letter-spacing: 2px;
                            transition: all 0.3s ease;
                            position: relative;
                            overflow: hidden;
                        ">
                            <span id="btnText">等待中...</span>
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // 5秒倒计时
            let countdown = 5;
            const countdownEl = modal.querySelector('#countdown');
            const timerTextEl = modal.querySelector('#timerText');
            const agreeBtn = modal.querySelector('#agreeBtn');
            const btnText = modal.querySelector('#btnText');
            
            const timer = setInterval(() => {
                countdown--;
                countdownEl.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(timer);
                    timerTextEl.innerHTML = '<i class="fas fa-check-circle" style="color: #00ff41;"></i> 已完成阅读';
                    agreeBtn.disabled = false;
                    agreeBtn.style.cssText = `
                        background: rgba(0, 255, 65, 0.15);
                        border: 1px solid #00ff41;
                        color: #00ff41;
                        padding: 12px 35px;
                        cursor: pointer;
                        font-family: 'Roboto Mono', monospace;
                        font-size: 13px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 2px;
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
                        document.getElementById('legalNoticeModal').remove();
                    };
                }
            }, 1000);
        }
        
        // 登录表单提交
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const errorMsg = document.getElementById('error-message');
            
            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    // 刷新验证码
                    refreshCaptcha();
                    
                    // 显示错误信息
                    errorMsg.textContent = result.message;
                    errorMsg.style.display = 'block';
                    
                    // 如果被封禁，弹窗提示
                    if (result.banned) {
                        alert('⚠️ 安全提示\n\n' + result.message + '\n\n请稍后再试或联系管理员。');
                    } else if (result.attempts !== undefined && result.attempts <= 3) {
                        // 剩余次数较少时弹窗警告
                        alert('⚠️ 登录失败警告\n\n' + result.message + '\n\n请注意：连续失败6次将被封禁IP 6小时！');
                    }
                    
                    // 清空验证码输入框
                    document.getElementById('captcha').value = '';
                }
            } catch (error) {
                refreshCaptcha();
                errorMsg.textContent = '登录失败，请重试';
                errorMsg.style.display = 'block';
            }
        });
        
        // 注册表单提交
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'register');
            const regMsg = document.getElementById('register-message');
            
            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    regMsg.className = 'alert alert-success';
                    regMsg.textContent = result.message;
                    regMsg.style.display = 'block';
                    
                    // 2秒后切换到登录表单
                    setTimeout(() => {
                        document.getElementById('showLogin').click();
                    }, 2000);
                } else {
                    // 刷新验证码
                    refreshRegCaptcha();
                    
                    regMsg.className = 'alert alert-danger';
                    regMsg.textContent = result.message;
                    regMsg.style.display = 'block';
                    
                    // 清空验证码输入框
                    document.getElementById('reg_captcha').value = '';
                }
            } catch (error) {
                refreshRegCaptcha();
                regMsg.className = 'alert alert-danger';
                regMsg.textContent = '注册失败，请重试';
                regMsg.style.display = 'block';
            }
        });
        
        // 切换到注册表单
        document.getElementById('showRegister').addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelector('.login-wrapper > .card').style.display = 'none';
            document.getElementById('registerCard').style.display = 'block';
            
            // 显示法律声明弹窗
            setTimeout(() => {
                showLegalNotice();
            }, 300);
        });
        
        // 切换到登录表单
        document.getElementById('showLogin').addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('registerCard').style.display = 'none';
            document.querySelector('.login-wrapper > .card').style.display = 'block';
        });
    </script>
</body>
</html>
