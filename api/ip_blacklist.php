<?php
/**
 * IP黑名单列表API - 仅管理员可访问
 */
require_once '../config.php';
session_start();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    jsonResponse(['error' => 'Unauthorized']);
}

// 检查管理员权限
if (!isAdmin()) {
    http_response_code(403);
    jsonResponse(['error' => 'Forbidden - 仅管理员可访问']);
}

try {
    $pdo = getDbConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 获取所有IP黑名单
        $stmt = $pdo->query("SELECT * FROM ip_blacklist ORDER BY created_at DESC");
        $blacklist = $stmt->fetchAll();
        
        jsonResponse(['blacklist' => $blacklist]);
    }
    
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
