<?php
/**
 * 用户日志API - 仅管理员可访问
 */
require_once '../config.php';
session_start();

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
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    if ($userId <= 0) {
        jsonResponse(['error' => '无效的用户ID'], 400);
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 获取指定用户的日志列表
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20))); // 限制最多100条
        $offset = ($page - 1) * $perPage;
        if ($offset < 0) $offset = 0; // 防止整数溢出
        
        // 获取总数
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE user_id = ?");
        $stmt->execute([$userId]);
        $total = $stmt->fetchColumn();
        
        // 获取唯一IP数
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip) FROM logs WHERE user_id = ?");
        $stmt->execute([$userId]);
        $uniqueIps = $stmt->fetchColumn();
        
        // 获取分页数据（使用参数化查询防止SQL注入）
        $sql = "SELECT * FROM logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $perPage, $offset]);
        $logs = $stmt->fetchAll();
        
        jsonResponse([
            'logs' => $logs,
            'total' => (int)$total,
            'unique_ips' => (int)$uniqueIps,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ]);
    }
    
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
