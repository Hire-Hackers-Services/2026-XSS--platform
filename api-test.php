<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API测试工具</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #000;
            color: #0f0;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { color: #0ff; }
        .test-box {
            background: #111;
            border: 2px solid #0f0;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        button {
            background: #0f0;
            color: #000;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: bold;
            margin: 5px;
        }
        button:hover { background: #0ff; }
        .result {
            background: #000;
            border: 1px solid #0f0;
            padding: 10px;
            margin-top: 10px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0ff; }
    </style>
</head>
<body>
    <h1>🔧 API /api/collect 测试工具</h1>
    
    <div class="test-box">
        <h2>测试1: 直接PHP测试</h2>
        <p>直接在服务器端测试API功能</p>
        <?php
        // 设置错误显示
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        echo "<div class='result'>";
        echo "<div class='info'>[检查1] 检查config.php...</div>";
        if (file_exists(__DIR__ . '/config.php')) {
            echo "<div class='success'>✓ config.php 存在</div>";
            require_once __DIR__ . '/config.php';
        } else {
            echo "<div class='error'>✗ config.php 不存在</div>";
        }
        
        echo "<div class='info'>[检查2] 测试数据库连接...</div>";
        try {
            $pdo = getDbConnection();
            echo "<div class='success'>✓ 数据库连接成功</div>";
            
            // 检查logs表
            $stmt = $pdo->query("SHOW TABLES LIKE 'logs'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='success'>✓ logs表存在</div>";
                
                // 检查表结构
                $stmt = $pdo->query("DESCRIBE logs");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "<div class='info'>表字段: " . implode(', ', $columns) . "</div>";
            } else {
                echo "<div class='error'>✗ logs表不存在</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>✗ 数据库连接失败: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        echo "<div class='info'>[检查3] 检查API文件...</div>";
        if (file_exists(__DIR__ . '/api/collect.php')) {
            echo "<div class='success'>✓ /api/collect.php 存在</div>";
            echo "<div class='info'>文件大小: " . filesize(__DIR__ . '/api/collect.php') . " bytes</div>";
        } else {
            echo "<div class='error'>✗ /api/collect.php 不存在</div>";
        }
        
        echo "</div>";
        ?>
    </div>
    
    <div class="test-box">
        <h2>测试2: JavaScript POST测试</h2>
        <p>使用fetch发送POST请求到API</p>
        <button onclick="testPost()">运行POST测试</button>
        <div class="result" id="postResult">点击按钮开始测试...</div>
    </div>
    
    <div class="test-box">
        <h2>测试3: JavaScript GET测试</h2>
        <p>使用fetch发送GET请求到API</p>
        <button onclick="testGet()">运行GET测试</button>
        <div class="result" id="getResult">点击按钮开始测试...</div>
    </div>
    
    <div class="test-box">
        <h2>测试4: XMLHttpRequest测试</h2>
        <p>使用传统XHR方式测试</p>
        <button onclick="testXHR()">运行XHR测试</button>
        <div class="result" id="xhrResult">点击按钮开始测试...</div>
    </div>
    
    <script>
        // POST测试
        async function testPost() {
            const result = document.getElementById('postResult');
            result.innerHTML = '<div class="info">正在测试...</div>';
            
            try {
                const response = await fetch('/api/collect', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: 'test_post',
                        message: 'POST测试数据',
                        timestamp: new Date().toISOString()
                    })
                });
                
                result.innerHTML = `<div class="info">响应状态: ${response.status} ${response.statusText}</div>`;
                result.innerHTML += `<div class="info">Content-Type: ${response.headers.get('content-type')}</div>`;
                
                const text = await response.text();
                result.innerHTML += `<div class="info">原始响应: ${text.substring(0, 500)}</div>`;
                
                try {
                    const data = JSON.parse(text);
                    result.innerHTML += `<div class="success">✓ JSON解析成功</div>`;
                    result.innerHTML += `<div class="success">响应数据:\n${JSON.stringify(data, null, 2)}</div>`;
                } catch (e) {
                    result.innerHTML += `<div class="error">✗ JSON解析失败: ${e.message}</div>`;
                }
            } catch (error) {
                result.innerHTML += `<div class="error">✗ 请求失败: ${error.message}</div>`;
            }
        }
        
        // GET测试
        async function testGet() {
            const result = document.getElementById('getResult');
            result.innerHTML = '<div class="info">正在测试...</div>';
            
            try {
                const response = await fetch('/api/collect?test=get&timestamp=' + Date.now(), {
                    method: 'GET'
                });
                
                result.innerHTML = `<div class="info">响应状态: ${response.status}</div>`;
                
                const text = await response.text();
                result.innerHTML += `<div class="info">原始响应: ${text.substring(0, 500)}</div>`;
                
                try {
                    const data = JSON.parse(text);
                    result.innerHTML += `<div class="success">✓ 响应:\n${JSON.stringify(data, null, 2)}</div>`;
                } catch (e) {
                    result.innerHTML += `<div class="error">✗ JSON解析失败</div>`;
                }
            } catch (error) {
                result.innerHTML += `<div class="error">✗ 失败: ${error.message}</div>`;
            }
        }
        
        // XHR测试
        function testXHR() {
            const result = document.getElementById('xhrResult');
            result.innerHTML = '<div class="info">正在测试...</div>';
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/collect', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    result.innerHTML = `<div class="info">状态: ${xhr.status}</div>`;
                    result.innerHTML += `<div class="info">响应: ${xhr.responseText.substring(0, 500)}</div>`;
                    
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            result.innerHTML += `<div class="success">✓ 成功:\n${JSON.stringify(data, null, 2)}</div>`;
                        } catch (e) {
                            result.innerHTML += `<div class="error">✗ JSON解析失败</div>`;
                        }
                    } else {
                        result.innerHTML += `<div class="error">✗ HTTP错误</div>`;
                    }
                }
            };
            
            xhr.send(JSON.stringify({
                type: 'test_xhr',
                message: 'XHR测试',
                timestamp: new Date().toISOString()
            }));
        }
    </script>
</body>
</html>
