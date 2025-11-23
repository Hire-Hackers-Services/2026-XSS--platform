<?php
/**
 * 日志统计API
 */
require_once '../config.php';
session_start();

if (!isLoggedIn()) {
    http_response_code(401);
    jsonResponse(['error' => 'Unauthorized']);
}

try {
    $pdo = getDbConnection();
    $userId = getUserId();
    $isAdminUser = isAdmin();
    
    // 检查 is_gov_site 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM logs LIKE 'is_gov_site'");
    $hasGovSiteField = $stmt->rowCount() > 0;
    
    // 构建WHERE条件 - 普通用户只看自己的数据
    $whereClause = '';
    $params = [];
    if (!$isAdminUser) {
        if ($hasGovSiteField) {
            $whereClause = 'WHERE user_id = ? AND is_gov_site = 0'; // 普通用户不显示政府网站日志
        } else {
            $whereClause = 'WHERE user_id = ?';
        }
        $params = [$userId];
    } else {
        // 管理员过滤掉演示日志
        $whereClause = 'WHERE (user_id IS NOT NULL AND user_id > 0)';
    }
    
    // 总数
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs $whereClause");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // 今日统计
    $today = date('Y-m-d');
    if (!$isAdminUser) {
        if ($hasGovSiteField) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as today FROM logs WHERE user_id = ? AND is_gov_site = 0 AND DATE(created_at) = ?");
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) as today FROM logs WHERE user_id = ? AND DATE(created_at) = ?");
        }
        $stmt->execute([$userId, $today]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as today FROM logs WHERE (user_id IS NOT NULL AND user_id > 0) AND DATE(created_at) = ?");
        $stmt->execute([$today]);
    }
    $todayCount = $stmt->fetchColumn();
    
    // 唯一IP
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip) as unique_ips FROM logs $whereClause");
    $stmt->execute($params);
    $uniqueIps = $stmt->fetchColumn();
    
    // 最近5条活动（管理员查询时同时获取用户名）
    if ($isAdminUser) {
        $stmt = $pdo->prepare("SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.user_id = u.id $whereClause ORDER BY l.created_at DESC LIMIT 5");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM logs $whereClause ORDER BY created_at DESC LIMIT 5");
    }
    $stmt->execute($params);
    $recentActivity = $stmt->fetchAll();
    
    jsonResponse([
        'total_logs' => (int)$total,
        'today_logs' => (int)$todayCount,
        'unique_ips' => (int)$uniqueIps,
        'recent_activity' => $recentActivity
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}