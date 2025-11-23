<?php
/**
 * 批量导入XSS高级模板到数据库
 * 从jstemplates目录读取所有JS文件并导入
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
    die('<h1>403 Forbidden</h1><p>只有管理员可以批量导入模板</p><a href="templates.php">返回模板页面</a>');
}

// 处理AJAX导入请求
if (isset($_GET['action']) && $_GET['action'] === 'batch_import') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $pdo = getDbConnection();
        
        // jstemplates目录
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
        $errors = 0;
        $details = [];
        
        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                $details[] = "❌ 错误: 无法读取 {$filename}";
                $errors++;
                continue;
            }
            
            // 跳过空文件
            if (empty(trim($content))) {
                $details[] = "⚠️  跳过: {$filename} (空文件)";
                $skipped++;
                continue;
            }
            
            try {
                // 检查是否已存在
                $checkStmt = $pdo->prepare("SELECT id, content FROM templates WHERE filename = ?");
                $checkStmt->execute([$filename]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    // 比较内容是否变化
                    if ($existing['content'] !== $content) {
                        // 内容变化，更新
                        $updateStmt = $pdo->prepare("UPDATE templates SET content = ?, size = ?, updated_at = CURRENT_TIMESTAMP WHERE filename = ?");
                        $updateStmt->execute([$content, strlen($content), $filename]);
                        $updated++;
                        $details[] = "🔄 更新: {$filename} (" . number_format(strlen($content)) . " 字节)";
                    } else {
                        // 内容相同，跳过
                        $skipped++;
                        $details[] = "⏭️  跳过: {$filename} (内容相同)";
                    }
                } else {
                    // 新模板，插入
                    $stmt = $pdo->prepare("INSERT INTO templates (filename, content, size) VALUES (?, ?, ?)");
                    $stmt->execute([$filename, $content, strlen($content)]);
                    $inserted++;
                    $details[] = "✅ 导入: {$filename} (" . number_format(strlen($content)) . " 字节)";
                }
                
            } catch (PDOException $e) {
                $details[] = "❌ 数据库错误: {$filename} - " . $e->getMessage();
                $errors++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "批量导入完成！新增 {$inserted} 个，更新 {$updated} 个，跳过 {$skipped} 个，错误 {$errors} 个",
            'stats' => [
                'total' => count($files),
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors
            ],
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

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="tu/xssicon.png">
    
    <title>批量导入XSS模板 - <?php echo APP_NAME; ?></title>
    <link href="static/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/libs/fontawesome/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Microsoft YaHei', Arial, sans-serif; 
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }
        .container { 
            background: rgba(255, 255, 255, 0.95); 
            border-radius: 15px; 
            box-shadow: 0 15px 50px rgba(0,0,0,0.5); 
            padding: 40px; 
            max-width: 900px; 
            width: 100%; 
        }
        h1 { 
            color: #2c5364; 
            margin-bottom: 15px; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
        }
        .subtitle { 
            color: #666; 
            margin-bottom: 30px; 
            font-size: 15px; 
            line-height: 1.8;
        }
        .info-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 5px solid #2196F3;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 8px;
        }
        .info-box h3 {
            color: #1565C0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .info-box ul {
            margin-left: 25px;
            color: #424242;
            line-height: 2;
        }
        .info-box ul li {
            margin-bottom: 8px;
        }
        .btn { 
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); 
            color: white; 
            border: none; 
            padding: 14px 35px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px; 
            transition: all 0.3s; 
            font-weight: 600;
        }
        .btn:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.4);
        }
        .btn:disabled { 
            background: #ccc; 
            cursor: not-allowed; 
            transform: none;
        }
        .btn-secondary {
            background: linear-gradient(135deg, #78909C 0%, #546E7A 100%);
            margin-left: 15px;
        }
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
            gap: 20px; 
            margin: 25px 0; 
        }
        .stat-card { 
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%); 
            padding: 20px; 
            border-radius: 10px; 
            text-align: center; 
            border: 2px solid #ddd;
        }
        .stat-number { 
            font-size: 36px; 
            font-weight: bold; 
            color: #2196F3; 
        }
        .stat-label { 
            font-size: 13px; 
            color: #666; 
            margin-top: 8px; 
            font-weight: 500;
        }
        .log { 
            background: #263238; 
            border: 2px solid #37474F; 
            border-radius: 10px; 
            padding: 20px; 
            max-height: 450px; 
            overflow-y: auto; 
            font-family: 'Consolas', 'Monaco', monospace; 
            font-size: 13px; 
            line-height: 1.8; 
            margin-top: 25px; 
            display: none;
            color: #CFD8DC;
        }
        .log-item { 
            padding: 8px; 
            border-bottom: 1px solid #37474F; 
            transition: background 0.2s;
        }
        .log-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .log-item:last-child { border-bottom: none; }
        .result {
            margin-top: 20px;
            padding: 18px;
            border-radius: 8px;
            display: none;
            font-size: 15px;
            font-weight: 500;
        }
        .success {
            background: #C8E6C9;
            border: 2px solid #66BB6A;
            color: #2E7D32;
        }
        .error {
            background: #FFCDD2;
            border: 2px solid #EF5350;
            color: #C62828;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-database"></i> 批量导入XSS模板到数据库</h1>
        <p class="subtitle">
            自动扫描 <code>/jstemplates</code> 目录下的所有JS文件，批量导入到数据库<br>
            支持智能更新：检测文件内容变化，自动更新或跳过相同内容
        </p>
        
        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> 导入说明</h3>
            <ul>
                <li><strong>扫描目录：</strong>/jstemplates/*.js</li>
                <li><strong>自动去重：</strong>相同文件名的模板会检查内容是否变化</li>
                <li><strong>智能更新：</strong>内容变化则更新，相同则跳过</li>
                <li><strong>包含模板：</strong>摄像头拍照、GPS定位、真实IP检测、RDP远程控制、钓鱼下载、超级截屏等</li>
            </ul>
        </div>
        
        <div class="stats" id="stats" style="display:none;">
            <div class="stat-card">
                <div class="stat-number" id="totalCount">0</div>
                <div class="stat-label">总文件数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="insertCount">0</div>
                <div class="stat-label">新增导入</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="updateCount">0</div>
                <div class="stat-label">更新覆盖</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="skipCount">0</div>
                <div class="stat-label">跳过未变</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="errorCount">0</div>
                <div class="stat-label">错误数量</div>
            </div>
        </div>
        
        <div id="result" class="result"></div>
        
        <div class="log" id="log"></div>
        
        <div style="margin-top: 20px;">
            <button class="btn" id="importBtn" onclick="batchImport()">
                <i class="fas fa-cloud-upload-alt"></i> 开始批量导入
            </button>
            <button class="btn btn-secondary" onclick="location.href='templates.php'">
                <i class="fas fa-arrow-left"></i> 返回模板管理
            </button>
        </div>
    </div>

    <script>
        function addLog(message, type = 'info') {
            const log = document.getElementById('log');
            log.style.display = 'block';
            const item = document.createElement('div');
            item.className = 'log-item';
            item.innerHTML = message;
            log.appendChild(item);
            log.scrollTop = log.scrollHeight;
        }

        async function batchImport() {
            const btn = document.getElementById('importBtn');
            const result = document.getElementById('result');
            const stats = document.getElementById('stats');
            const log = document.getElementById('log');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 导入中...';
            result.style.display = 'none';
            log.innerHTML = '';
            stats.style.display = 'grid';
            
            addLog('<strong style="color:#4FC3F7;">🚀 开始扫描jstemplates目录...</strong>');
            
            try {
                const response = await fetch('batch_import_templates.php?action=batch_import', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'}
                });
                
                const data = await response.json();
                
                if (data.success) {
                    result.className = 'result success';
                    result.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                    result.style.display = 'block';
                    
                    // 更新统计
                    document.getElementById('totalCount').textContent = data.stats.total;
                    document.getElementById('insertCount').textContent = data.stats.inserted;
                    document.getElementById('updateCount').textContent = data.stats.updated;
                    document.getElementById('skipCount').textContent = data.stats.skipped;
                    document.getElementById('errorCount').textContent = data.stats.errors;
                    
                    addLog(`<strong style="color:#66BB6A;">📊 导入统计:</strong>`);
                    addLog(`   • 扫描文件: ${data.stats.total} 个`);
                    addLog(`   • 新增导入: <strong style="color:#4CAF50;">${data.stats.inserted}</strong> 个`);
                    addLog(`   • 更新覆盖: <strong style="color:#FF9800;">${data.stats.updated}</strong> 个`);
                    addLog(`   • 跳过未变: <strong style="color:#9E9E9E;">${data.stats.skipped}</strong> 个`);
                    addLog(`   • 错误数量: <strong style="color:#F44336;">${data.stats.errors}</strong> 个`);
                    addLog('');
                    addLog('<strong style="color:#4FC3F7;">📝 详细日志:</strong>');
                    
                    // 显示详细日志
                    if (data.details && data.details.length > 0) {
                        data.details.forEach(detail => {
                            addLog('   ' + detail);
                        });
                    }
                    
                    addLog('');
                    addLog('<strong style="color:#66BB6A;">✨ 批量导入完成!</strong>');
                    
                    // 3秒后询问是否跳转
                    setTimeout(() => {
                        if (confirm('导入成功！是否立即查看模板库？')) {
                            location.href = 'templates.php';
                        }
                    }, 2000);
                    
                } else {
                    result.className = 'result error';
                    result.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                    result.style.display = 'block';
                    addLog('<strong style="color:#F44336;">❌ 导入失败: ' + data.message + '</strong>');
                }
            } catch (error) {
                result.className = 'result error';
                result.innerHTML = '<i class="fas fa-exclamation-circle"></i> 导入失败: ' + error.message;
                result.style.display = 'block';
                addLog('<strong style="color:#F44336;">💥 系统错误: ' + error.message + '</strong>');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-redo"></i> 重新导入';
            }
        }
    </script>
</body>
</html>
