<?php
/**
 * 添加IP封禁API - 仅管理员可访问
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
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $ip = trim($input['ip'] ?? '');
        $reason = trim($input['reason'] ?? '');
        
        if (empty($ip)) {
            jsonResponse(['success' => false, 'message' => 'IP地址不能为空'], 400);
        }
        
        // 验证IP格式
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            jsonResponse(['success' => false, 'message' => 'IP地址格式不正确'], 400);
        }
        
        // 检查IP是否已存在
        $stmt = $pdo->prepare("SELECT id FROM ip_blacklist WHERE ip = ?");
        $stmt->execute([$ip]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => '该IP已在黑名单中'], 400);
        }
        
        // 添加到黑名单
        $adminId = getUserId();
        $stmt = $pdo->prepare("
            INSERT INTO ip_blacklist (ip, reason, created_by) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$ip, $reason, $adminId]);
        
        jsonResponse([
            'success' => true, 
            'message' => 'IP封禁添加成功',
            'ip' => $ip
        ]);
    }
    
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
