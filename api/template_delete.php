<?php
/**
 * 删除XSS模板API
 * 仅管理员可用
 */

require_once __DIR__ . '/../config.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

// 允许跨域（仅限本域）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// 检查登录状态
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '未登录，无权访问'
    ], JSON_UNESCAPED_UNICODE);
    http_response_code(401);
    exit;
}

// 检查管理员权限
if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => '权限不足，仅管理员可以删除模板'
    ], JSON_UNESCAPED_UNICODE);
    http_response_code(403);
    exit;
}

try {
    // 获取请求数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['template_id'])) {
        echo json_encode([
            'success' => false,
            'message' => '缺少template_id参数'
        ], JSON_UNESCAPED_UNICODE);
        http_response_code(400);
        exit;
    }
    
    $templateId = intval($data['template_id']);
    
    if ($templateId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => '无效的模板ID'
        ], JSON_UNESCAPED_UNICODE);
        http_response_code(400);
        exit;
    }
    
    $pdo = getDbConnection();
    
    // 检查模板是否存在
    $checkStmt = $pdo->prepare("SELECT filename FROM templates WHERE id = ?");
    $checkStmt->execute([$templateId]);
    $template = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        echo json_encode([
            'success' => false,
            'message' => '模板不存在'
        ], JSON_UNESCAPED_UNICODE);
        http_response_code(404);
        exit;
    }
    
    // 执行删除
    $deleteStmt = $pdo->prepare("DELETE FROM templates WHERE id = ?");
    $deleteStmt->execute([$templateId]);
    
    echo json_encode([
        'success' => true,
        'message' => '模板删除成功',
        'deleted_template' => $template['filename']
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    http_response_code(500);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '删除失败: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    http_response_code(500);
}
