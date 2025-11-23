<?php
// 简单的API测试文件
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 显示所有错误
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 测试1: 基本响应
    $result = [
        'status' => 'success',
        'message' => 'API测试成功',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ];
    
    // 测试2: 配置文件
    if (file_exists(__DIR__ . '/../config.php')) {
        $result['config'] = 'exists';
        require_once __DIR__ . '/../config.php';
        
        // 测试3: 数据库连接
        try {
            $pdo = getDbConnection();
            $result['database'] = 'connected';
            
            // 测试4: 检查logs表
            $stmt = $pdo->query("SHOW TABLES LIKE 'logs'");
            if ($stmt->rowCount() > 0) {
                $result['logs_table'] = 'exists';
                
                // 获取表字段
                $stmt = $pdo->query("DESCRIBE logs");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $result['logs_columns'] = $columns;
            } else {
                $result['logs_table'] = 'missing';
            }
        } catch (Exception $e) {
            $result['database'] = 'error: ' . $e->getMessage();
        }
    } else {
        $result['config'] = 'missing';
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
