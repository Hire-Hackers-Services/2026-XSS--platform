<?php
/**
 * 日志详情API
 */
require_once '../config.php';
session_start();

if (!isLoggedIn()) {
    http_response_code(401);
    jsonResponse(['error' => 'Unauthorized']);
}

try {
    $pdo = getDbConnection();
    $logId = isset($_GET['log_id']) ? trim($_GET['log_id']) : '';
    
    if (empty($logId)) {
        jsonResponse(['error' => '无效的日志ID'], 400);
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 获取日志详情
        $stmt = $pdo->prepare("SELECT * FROM logs WHERE log_id = ?");
        $stmt->execute([$logId]);
        $log = $stmt->fetch();
        
        if (!$log) {
            jsonResponse(['error' => '日志不存在'], 404);
        }
        
        // 检查权限：普通用户只能查看自己的日志
        $userId = getUserId();
        $isAdminUser = isAdmin();
        
        if (!$isAdminUser && $log['user_id'] != $userId) {
            jsonResponse(['error' => '无权查看此日志'], 403);
        }
        
        jsonResponse($log);
    }
    
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
