<?php
/**
 * 日志管理API
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
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 获取日志列表
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20))); // 限制最多100条
        $ipFilter = trim($_GET['ip'] ?? '');
        
        // 验证IP格式（如果提供了IP过滤）
        if (!empty($ipFilter) && !filter_var($ipFilter, FILTER_VALIDATE_IP)) {
            jsonResponse(['error' => '无效的IP地址格式'], 400);
        }
        
        // 防止整数溢出
        $offset = ($page - 1) * $perPage;
        if ($offset < 0) $offset = 0;
        
        // 构建查询 - 添加用户过滤
        $where = [];
        $params = [];
        
        // 普通用户只能看到user_id等于自己的日志，管理员看所有
        if (!$isAdminUser) {
            $where[] = 'user_id = ?';
            $params[] = $userId;
            
            // 普通用户不显示政府网站日志（如果字段存在）
            if ($hasGovSiteField) {
                $where[] = 'is_gov_site = 0';
            }
        } else {
            // 管理员可以看到所有日志，但过滤掉演示日志（没有有效user_id的日志）
            $where[] = '(user_id IS NOT NULL AND user_id > 0)';
        }
        
        if ($ipFilter) {
            $where[] = 'ip = ?';
            $params[] = $ipFilter;
        }
        
        $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // 获取总数
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs $whereClause");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // 获取分页数据（使用参数化查询防止SQL注入）
        // 管理员查询时同时获取用户名
        if ($isAdminUser) {
            $sql = "SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.user_id = u.id $whereClause ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
        } else {
            $sql = "SELECT * FROM logs $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        }
        $stmt = $pdo->prepare($sql);
        // 合并参数数组
        $executeParams = array_merge($params, [$perPage, $offset]);
        $stmt->execute($executeParams);
        $logs = $stmt->fetchAll();
        
        jsonResponse([
            'logs' => $logs,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // 清空日志 - 普通用户只能清空自己的，管理员清空所有
        if ($isAdminUser) {
            $pdo->exec("TRUNCATE TABLE logs");
            jsonResponse(['success' => true, 'message' => '所有日志已清空']);
        } else {
            $stmt = $pdo->prepare("DELETE FROM logs WHERE user_id = ?");
            $stmt->execute([$userId]);
            jsonResponse(['success' => true, 'message' => '您的日志已清空']);
        }
    }
    
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}