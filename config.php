<?php
/**
 * 数据库配置文件
 * 支持环境变量和.env文件
 */

// 加载.env文件（如果存在）
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // 跳过注释行
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // 解析键值对
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // 如果环境变量不存在，则设置
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// 辅助函数：获取环境变量或默认值
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    // 处理布尔值
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
        case 'empty':
        case '(empty)':
            return '';
    }
    return $value;
}

// 设置安全HTTP响应头（API场景跳过）
if (!defined('IS_API_REQUEST')) {
    // 防止MIME类型混淆攻击
    header('X-Content-Type-Options: nosniff');
    // 防止点击劫持
    header('X-Frame-Options: SAMEORIGIN');
    // 启用浏览器XSS过滤器
    header('X-XSS-Protection: 1; mode=block');
    // Referrer策略
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // 注意：此为XSS平台，不设置CSP，因为需要执行用户提交的JavaScript
}

// 数据库配置（优先使用环境变量）
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'admin'));
define('DB_USER', env('DB_USER', 'admin'));
define('DB_PASS', env('DB_PASS', 'mima'));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// 应用配置
define('APP_NAME', env('APP_NAME', '黑客仓库XSS反连平台'));
define('APP_VERSION', env('APP_VERSION', '2.0.8'));
define('SESSION_TIMEOUT', (int)env('SESSION_TIMEOUT', 3600)); // 会话超时时间（秒）

// 路径配置
define('BASE_PATH', __DIR__);
define('UPLOAD_PATH', BASE_PATH . '/myjs');
define('TEMPLATE_PATH', BASE_PATH . '/jstemplates');

// 时区设置
date_default_timezone_set(env('TIMEZONE', 'Asia/Shanghai'));

// 错误报告（生产环境已关闭）
error_reporting((int)env('ERROR_REPORTING', 0));
ini_set('display_errors', env('DISPLAY_ERRORS', '0'));
ini_set('log_errors', '1');
ini_set('error_log', env('LOG_PATH', BASE_PATH . '/data') . '/' . env('ERROR_LOG', 'php_errors.log'));

// 会话配置
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
session_set_cookie_params(SESSION_TIMEOUT);

// 数据库连接
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// JSON响应函数
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 检查登录状态
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// 检查是否是管理员 - 新增
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// 获取当前用户角色 - 新增
function getUserRole() {
    return $_SESSION['role'] ?? 'user';
}

// 获取当前用户ID - 新增
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// 检查IP白名单
function checkIpWhitelist() {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT value FROM settings WHERE `key` = 'ip_whitelist_enabled'");
    $enabled = $stmt->fetchColumn();
    
    if (!$enabled) {
        return true;
    }
    
    $stmt = $pdo->query("SELECT value FROM settings WHERE `key` = 'allowed_ips'");
    $allowedIps = $stmt->fetchColumn();
    
    if (empty($allowedIps)) {
        return true;
    }
    
    $clientIp = $_SERVER['REMOTE_ADDR'];
    
    // 本地访问直接允许
    if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
        return true;
    }
    
    $ipList = explode("\n", $allowedIps);
    foreach ($ipList as $ipPattern) {
        $ipPattern = trim($ipPattern);
        if (empty($ipPattern)) {
            continue;
        }
        
        // 单个IP匹配
        if (strpos($ipPattern, '/') === false) {
            if ($clientIp === $ipPattern) {
                return true;
            }
        } else {
            // CIDR匹配
            if (ipInCidr($clientIp, $ipPattern)) {
                return true;
            }
        }
    }
    
    return false;
}

// CIDR IP检查
function ipInCidr($ip, $cidr) {
    list($subnet, $mask) = explode('/', $cidr);
    
    $ip_long = ip2long($ip);
    $subnet_long = ip2long($subnet);
    $mask_long = -1 << (32 - (int)$mask);
    $subnet_long &= $mask_long;
    
    return ($ip_long & $mask_long) == $subnet_long;
}

// 获取客户端IP（防止HTTP头伪造攻击）
function getClientIp() {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // 只有在反向代理后才使用X-Forwarded-For
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        // 取第一个IP（最原始IP）
        $forwardedIp = trim($forwardedIps[0]);
        // 验证IP格式
        if (filter_var($forwardedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $ip = $forwardedIp;
        }
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $realIp = trim($_SERVER['HTTP_X_REAL_IP']);
        if (filter_var($realIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $ip = $realIp;
        }
    }
    
    return $ip;
}

// 安全的HTML输出函数
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// 生成CSRF Token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 验证CSRF Token
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 安全的文件名验证
function validateFilename($filename) {
    // 只允许字母、数字、下划线、短横线和点
    return preg_match('/^[a-zA-Z0-9_.-]+$/', $filename);
}

// 防止目录遍历攻击
function sanitizePath($path, $baseDir) {
    $realBase = realpath($baseDir);
    $userPath = $baseDir . DIRECTORY_SEPARATOR . $path;
    $realUserPath = realpath($userPath);
    
    // 防止跳出基础目录
    if ($realUserPath === false || strpos($realUserPath, $realBase) !== 0) {
        return false;
    }
    
    return $realUserPath;
}

// 请求频率限制（简单的基于Session的限流）
function checkRateLimit($key, $maxRequests = 60, $timeWindow = 60) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    $rateLimitKey = $key . '_' . getClientIp();
    
    // 清理过期记录
    if (isset($_SESSION['rate_limit'][$rateLimitKey])) {
        $_SESSION['rate_limit'][$rateLimitKey] = array_filter(
            $_SESSION['rate_limit'][$rateLimitKey],
            function($timestamp) use ($now, $timeWindow) {
                return ($now - $timestamp) < $timeWindow;
            }
        );
    } else {
        $_SESSION['rate_limit'][$rateLimitKey] = [];
    }
    
    // 检查是否超过限制
    if (count($_SESSION['rate_limit'][$rateLimitKey]) >= $maxRequests) {
        return false;
    }
    
    // 记录当前请求
    $_SESSION['rate_limit'][$rateLimitKey][] = $now;
    return true;
}

// SQL安全过滤（防止常见的SQL注入关键字）
function containsSqlInjection($input) {
    $patterns = [
        '/\bUNION\b.*\bSELECT\b/i',
        '/\bDROP\b.*\bTABLE\b/i',
        '/\bINSERT\b.*\bINTO\b/i',
        '/\bUPDATE\b.*\bSET\b/i',
        '/\bDELETE\b.*\bFROM\b/i',
        '/<script[^>]*>.*?<\/script>/is',
        '/javascript:/i',
        '/on\w+\s*=/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    return false;
}

// 安全的整数验证
function validateInt($value, $min = null, $max = null) {
    if (!is_numeric($value)) {
        return false;
    }
    $intValue = (int)$value;
    if ($min !== null && $intValue < $min) {
        return false;
    }
    if ($max !== null && $intValue > $max) {
        return false;
    }
    return $intValue;
}

// 安全的邮箱验证
function validateEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// 安全的URL验证
function validateUrl($url) {
    $url = filter_var($url, FILTER_SANITIZE_URL);
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}
