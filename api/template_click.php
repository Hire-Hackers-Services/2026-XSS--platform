<?php
/**
 * 模板点击统计API
 */
header('Content-Type: application/json');
require_once '../config.php';
session_start();

// 检查登录
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只接受POST请求']);
    exit;
}

try {
    // 获取请求数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['template_id'])) {
        echo json_encode(['success' => false, 'message' => '缺少template_id参数']);
        exit;
    }
    
    $templateId = intval($input['template_id']);
    
    // 连接数据库
    $pdo = getDbConnection();
    
    // 增加点击次数
    $stmt = $pdo->prepare("UPDATE templates SET click_count = COALESCE(click_count, 0) + 1 WHERE id = ?");
    $stmt->execute([$templateId]);
    
    echo json_encode(['success' => true, 'message' => '点击次数已更新']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '错误: ' . $e->getMessage()]);
}
