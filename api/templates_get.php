<?php
/**
 * 获取模板内容API
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// 允许跨域
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $filename = $_GET['filename'] ?? '';
    
    if (empty($filename)) {
        echo json_encode([
            'success' => false,
            'message' => '缺少filename参数'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM templates WHERE filename = ? LIMIT 1");
    $stmt->execute([$filename]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($template) {
        echo json_encode([
            'success' => true,
            'template' => $template
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '模板不存在'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '错误: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
