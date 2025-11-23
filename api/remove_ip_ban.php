<?php
/**
 * 移除IP封禁API - 仅管理员可访问
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
        $id = (int)($input['id'] ?? 0);
        
        if ($id <= 0) {
            jsonResponse(['success' => false, 'message' => '无效的ID'], 400);
        }
        
        // 删除IP黑名单记录
        $stmt = $pdo->prepare("DELETE FROM ip_blacklist WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            jsonResponse([
                'success' => true, 
                'message' => 'IP封禁已移除'
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => '记录不存在'], 404);
        }
    }
    
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
