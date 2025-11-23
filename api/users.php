<?php
/**
 * 用户管理API - 仅管理员可访问
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
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 获取所有用户及其日志数量
        $stmt = $pdo->query("
            SELECT 
                u.id,
                u.username,
                u.role,
                u.email,
                u.status,
                u.banned_reason,
                u.banned_at,
                u.created_at,
                u.updated_at,
                COUNT(l.id) as log_count
            FROM users u
            LEFT JOIN logs l ON u.id = l.user_id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll();
        
        // 统计数据
        $stats = [
            'total' => count($users),
            'admins' => 0,
            'users' => 0,
            'total_logs' => 0
        ];
        
        foreach ($users as $user) {
            if ($user['role'] === 'admin') {
                $stats['admins']++;
            } else {
                $stats['users']++;
            }
            $stats['total_logs'] += (int)$user['log_count'];
        }
        
        jsonResponse([
            'users' => $users,
            'stats' => $stats
        ]);
    }
    
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
