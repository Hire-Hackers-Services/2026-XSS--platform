<?php
/**
 * 调试页面 - 检查数据库和API状态
 */
require_once 'config.php';
session_start();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 测试数据库连接
$dbStatus = '❌ 未连接';
$dbError = '';
try {
    $pdo = getDbConnection();
    $dbStatus = '✅ 连接成功';
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// 获取日志数量
$logCount = 0;
$lastLog = null;
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM logs");
    $logCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT * FROM logs ORDER BY created_at DESC LIMIT 1");
    $lastLog = $stmt->fetch();
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// 测试API连接
$apiTest = '未测试';
if (isset($_GET['test_api'])) {
    $apiTest = '正在测试...';
    
    // 发送测试数据到API
    $testData = [
        'test' => true,
        'message' => 'Debug API Test',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $ch = curl_init('https://xss.li/api/collect');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $apiTest = '✅ API测试成功';
    } else {
        $apiTest = "❌ API测试失败 (HTTP $httpCode)";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统调试 - <?php echo APP_NAME; ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .debug-container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .status-box { padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #ccc; }
        .status-box.success { background: #d4edda; border-left-color: #28a745; }
        .status-box.error { background: #f8d7da; border-left-color: #dc3545; }
        .status-box.info { background: #d1ecf1; border-left-color: #17a2b8; }
        h2 { color: #667eea; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        table th { background: #f5f5f5; }
        .btn { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #5568d3; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="nav">
            <a href="admin.php" class="btn">← 返回后台</a>
            <a href="?test_api=1" class="btn">测试API</a>
            <a href="?" class="btn">刷新</a>
        </div>
        
        <h1>🔍 系统调试信息</h1>
        
        <h2>📊 数据库状态</h2>
        <div class="status-box <?php echo $dbError ? 'error' : 'success'; ?>">
            <p><strong>连接状态:</strong> <?php echo $dbStatus; ?></p>
            <?php if ($dbError): ?>
                <p><strong>错误信息:</strong> <?php echo $dbError; ?></p>
            <?php endif; ?>
            <p><strong>数据库名:</strong> <?php echo DB_NAME; ?></p>
            <p><strong>数据库主机:</strong> <?php echo DB_HOST; ?></p>
            <p><strong>日志总数:</strong> <?php echo $logCount; ?> 条</p>
        </div>
        
        <h2>📝 最新日志记录</h2>
        <?php if ($lastLog): ?>
            <div class="status-box info">
                <table>
                    <tr><th>字段</th><th>值</th></tr>
                    <tr><td>ID</td><td><?php echo htmlspecialchars($lastLog['log_id']); ?></td></tr>
                    <tr><td>时间</td><td><?php echo htmlspecialchars($lastLog['created_at']); ?></td></tr>
                    <tr><td>IP</td><td><?php echo htmlspecialchars($lastLog['ip']); ?></td></tr>
                    <tr><td>方法</td><td><?php echo htmlspecialchars($lastLog['method']); ?></td></tr>
                    <tr><td>数据类型</td><td><?php echo htmlspecialchars($lastLog['data_type']); ?></td></tr>
                    <tr><td>URL</td><td><?php echo htmlspecialchars($lastLog['url']); ?></td></tr>
                </table>
                <p><strong>数据内容:</strong></p>
                <pre><?php echo htmlspecialchars($lastLog['data']); ?></pre>
            </div>
        <?php else: ?>
            <div class="status-box error">
                <p>❌ 没有找到任何日志记录</p>
            </div>
        <?php endif; ?>
        
        <h2>🔌 API测试</h2>
        <div class="status-box <?php echo strpos($apiTest, '✅') !== false ? 'success' : 'info'; ?>">
            <p><strong>状态:</strong> <?php echo $apiTest; ?></p>
            <p><strong>API地址:</strong> <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/api/collect'; ?></p>
            <?php if (!isset($_GET['test_api'])): ?>
                <p><a href="?test_api=1" class="btn">点击测试API</a></p>
            <?php endif; ?>
        </div>
        
        <h2>🗂️ 最近5条日志</h2>
        <?php
        try {
            $stmt = $pdo->query("SELECT * FROM logs ORDER BY created_at DESC LIMIT 5");
            $recentLogs = $stmt->fetchAll();
            
            if ($recentLogs): ?>
                <table>
                    <thead>
                        <tr>
                            <th>时间</th>
                            <th>IP</th>
                            <th>方法</th>
                            <th>数据类型</th>
                            <th>Endpoint</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip']); ?></td>
                                <td><?php echo htmlspecialchars($log['method']); ?></td>
                                <td><?php echo htmlspecialchars($log['data_type']); ?></td>
                                <td><?php echo htmlspecialchars($log['endpoint']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>暂无日志</p>
            <?php endif;
        } catch (Exception $e) {
            echo "<p style='color:red;'>查询失败: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        
        <h2>⚙️ 系统配置</h2>
        <table>
            <tr><td>PHP版本</td><td><?php echo PHP_VERSION; ?></td></tr>
            <tr><td>时区</td><td><?php echo date_default_timezone_get(); ?></td></tr>
            <tr><td>当前时间</td><td><?php echo date('Y-m-d H:i:s'); ?></td></tr>
            <tr><td>应用路径</td><td><?php echo BASE_PATH; ?></td></tr>
            <tr><td>Session状态</td><td><?php echo session_status() === PHP_SESSION_ACTIVE ? '✅ 活跃' : '❌ 未激活'; ?></td></tr>
        </table>
        
        <h2>🧪 手动测试</h2>
        <div class="status-box info">
            <p>在浏览器控制台运行以下代码测试API:</p>
            <pre>
fetch('<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/api/collect'; ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        test: 'manual',
        message: 'Manual API Test',
        timestamp: new Date().toISOString()
    })
}).then(r => r.json()).then(d => console.log('Result:', d));
            </pre>
            <p>然后刷新此页面查看是否有新日志记录。</p>
        </div>
    </div>
</body>
</html>
