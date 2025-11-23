<?php
/**
 * 封禁用户API - 仅管理员可访问
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
        $userId = (int)($input['user_id'] ?? 0);
        $reason = trim($input['reason'] ?? '违反平台使用规则');
        
        if ($userId <= 0) {
            jsonResponse(['success' => false, 'message' => '无效的用户ID'], 400);
        }
        
        // 检查用户是否存在
        $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(['success' => false, 'message' => '用户不存在'], 404);
        }
        
        // 不能封禁管理员
        if ($user['role'] === 'admin') {
            jsonResponse(['success' => false, 'message' => '不能封禁管理员账号'], 403);
        }
        
        // 封禁用户
        $stmt = $pdo->prepare("
            UPDATE users 
            SET status = 'banned', 
                banned_reason = ?, 
                banned_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$reason, $userId]);
        
        jsonResponse([
            'success' => true, 
            'message' => '用户已封禁',
            'user_id' => $userId,
            'username' => $user['username']
        ]);
    }
    
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
