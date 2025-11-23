<?php
/**
 * Payload文件管理API - 用户隔离版本
 */
require_once '../config.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$userId = $_SESSION['user_id'];
$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 获取当前用户的Payload列表
    try {
        $stmt = $pdo->prepare("SELECT * FROM payloads WHERE user_id = ? ORDER BY updated_at DESC");
        $stmt->execute([$userId]);
        $payloads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'payloads' => $payloads
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => '获取失败: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 保存/更新Payload
    $filename = $_POST['filename'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (empty($filename)) {
        echo json_encode(['success' => false, 'message' => '文件名不能为空'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // 限制文件名长度
    if (strlen($filename) > 100) {
        echo json_encode(['success' => false, 'message' => '文件名过长，最多100个字符'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // 限制内容大小（最大1MB）
    if (strlen($content) > 1048576) {
        echo json_encode(['success' => false, 'message' => '文件内容过大，最大支持1MB'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // 确保文件名以.js结尾
    if (!preg_match('/\.js$/', $filename)) {
        $filename .= '.js';
    }
    
    // 验证文件名（仅允许字母、数字、下划线、短横线和点号）
    if (!preg_match('/^[a-zA-Z0-9_.-]+\.js$/', $filename)) {
        echo json_encode(['success' => false, 'message' => '文件名只能包含字母、数字、下划线、短横线和点号，且必须以.js结尾'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // 防止目录遍历攻击
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        echo json_encode(['success' => false, 'message' => '非法的文件名'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // ========== 恶意代码检测 ==========
    $dangerousPatterns = [
        // PHP代码执行
        '/<\?php/i' => 'PHP代码',
        '/<\?=/i' => 'PHP短标签',
        '/\beval\s*\(/i' => 'eval()函数',
        '/\bexec\s*\(/i' => 'exec()函数',
        '/\bsystem\s*\(/i' => 'system()函数',
        '/\bpassthru\s*\(/i' => 'passthru()函数',
        '/\bshell_exec\s*\(/i' => 'shell_exec()函数',
        '/\bproc_open\s*\(/i' => 'proc_open()函数',
        '/\bpopen\s*\(/i' => 'popen()函数',
        
        // 反弹Shell（常见特征）
        '/\/bin\/bash/i' => '反弹Shell特征',
        '/\/bin\/sh/i' => 'Shell脚本',
        '/nc\s+-[a-z]*e/i' => 'netcat反弹shell',
        '/bash\s+-i/i' => 'bash交互式shell',
        '/\/dev\/tcp\//i' => 'TCP反弹连接',
        '/python.*socket/i' => 'Python反弹shell',
        
        // 文件操作（危险）
        '/file_put_contents/i' => '写文件操作',
        '/fwrite/i' => '文件写入',
        '/file_get_contents.*php:\/\//i' => '远程文件包含',
        '/include\s*\(/i' => 'PHP包含',
        '/require\s*\(/i' => 'PHP包含',
        '/include_once/i' => 'PHP包含',
        '/require_once/i' => 'PHP包含',
        
        // Node.js恶意代码
        '/require\s*\(["\']child_process["\']\)/i' => 'Node.js子进程',
        '/require\s*\(["\']fs["\']\).*writeFile/i' => 'Node.js文件写入',
        '/process\.env/i' => '环境变量访问',
        
        // SQL注入尝试
        '/DROP\s+TABLE/i' => 'SQL删除表',
        '/DELETE\s+FROM/i' => 'SQL删除数据',
        '/TRUNCATE/i' => 'SQL清空表',
        
        // 加密/混淆代码
        '/String\.fromCharCode\s*\(.*,.*,.*,.*,.*\)/i' => '混淆代码',
        '/atob\s*\(/i' => 'Base64解码',
        '/Function\s*\(["\'].*["\']\)/i' => '动态函数构造',
        '/new\s+Function\s*\(/i' => '动态函数',
        
        // WebShell特征
        '/assert\s*\(/i' => 'assert()函数',
        '/create_function/i' => 'create_function()',
        '/call_user_func/i' => '动态函数调用',
        '/preg_replace.*\/e/i' => 'preg_replace /e修饰符',
        
        // 木马特征
        '/phpspy/i' => 'PHP木马',
        '/c99shell/i' => 'C99 Shell',
        '/r57shell/i' => 'R57 Shell',
        '/webshell/i' => 'WebShell',
        '/backdoor/i' => '后门',
        '/trojan/i' => '木马',
        
        // 危险的浏览器API滥用
        '/navigator\.sendBeacon.*php/i' => '向PHP端点发送数据',
        '/fetch\s*\(.*\.php/i' => '请求PHP文件',
        '/XMLHttpRequest.*\.php/i' => 'XHR请求PHP',
    ];
    
    // 检测恶意代码
    $detectedThreats = [];
    foreach ($dangerousPatterns as $pattern => $description) {
        if (preg_match($pattern, $content)) {
            $detectedThreats[] = $description;
        }
    }
    
    // 如果检测到恶意代码，拒绝保存
    if (!empty($detectedThreats)) {
        echo json_encode([
            'success' => false,
            'message' => '⚠️ 检测到危险代码，已阻止保存！',
            'threats' => $detectedThreats,
            'hint' => '此平台仅用于合法的XSS安全测试，禁止上传木马、反弹Shell等恶意代码'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // 记录恶意尝试到日志
        error_log("[SECURITY] 用户 {$userId} 尝试上传恶意Payload: " . implode(', ', $detectedThreats));
        exit;
    }
    
    // 白名单检查：只允许XSS相关的合法API
    $allowedApis = [
        'fetch', 'XMLHttpRequest', 'document\.cookie', 'localStorage', 'sessionStorage',
        'navigator\.', 'location\.', 'alert', 'console\.', 'window\.',
        'document\.getElementById', 'querySelector', 'addEventListener',
        'JSON\.stringify', 'JSON\.parse', 'encodeURIComponent', 'btoa',
        'Date', 'Math', 'Array', 'String', 'Object', 'Promise'
    ];
    
    // 检查是否仅使用XSS相关的合法API
    // 这是一个宽松检查，主要确保没有服务器端代码
    if (preg_match('/<\?|\$_GET|\$_POST|\$_SERVER|\$_SESSION/i', $content)) {
        echo json_encode([
            'success' => false,
            'message' => '❌ 检测到服务器端代码，Payload仅支持客户端JavaScript',
            'hint' => '请移除PHP变量和服务器端代码'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        error_log("[SECURITY] 用户 {$userId} 尝试在Payload中使用服务器端代码");
        exit;
    }
    
    try {
        // 检查是否已存在
        $stmt = $pdo->prepare("SELECT id FROM payloads WHERE user_id = ? AND filename = ?");
        $stmt->execute([$userId, $filename]);
        $existing = $stmt->fetch();
        
        $size = strlen($content);
        
        if ($existing) {
            // 更新
            $stmt = $pdo->prepare("UPDATE payloads SET content = ?, size = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$content, $size, $existing['id']]);
        } else {
            // 新建
            $stmt = $pdo->prepare("INSERT INTO payloads (user_id, filename, content, size) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $filename, $content, $size]);
        }
        
        // 记录安全的Payload创建
        error_log("[INFO] 用户 {$userId} 保存Payload: {$filename} (" . strlen($content) . " bytes)");
        
        echo json_encode([
            'success' => true,
            'message' => '保存成功',
            'filename' => $filename
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => '保存失败: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // 删除Payload
    $data = json_decode(file_get_contents('php://input'), true);
    $payloadId = $data['id'] ?? 0;
    
    if (empty($payloadId)) {
        echo json_encode(['success' => false, 'message' => 'ID不能为空'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    try {
        // 只能删除自己的Payload
        $stmt = $pdo->prepare("DELETE FROM payloads WHERE id = ? AND user_id = ?");
        $stmt->execute([$payloadId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => '删除成功'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode(['success' => false, 'message' => '删除失败或没有权限'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => '删除失败: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // 获取单个Payload内容
    $data = json_decode(file_get_contents('php://input'), true);
    $payloadId = $data['id'] ?? 0;
    
    if (empty($payloadId)) {
        echo json_encode(['success' => false, 'message' => 'ID不能为空'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM payloads WHERE id = ? AND user_id = ?");
        $stmt->execute([$payloadId, $userId]);
        $payload = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payload) {
            echo json_encode([
                'success' => true,
                'payload' => $payload
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode(['success' => false, 'message' => 'Payload不存在或没有权限'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => '获取失败: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
