<?php
/* Author: shiguang */
require_once '../includes/db.php';
header('Content-Type: application/json');

// 1. 获取并处理参数
$id = intval($_GET['id'] ?? 0);
$limit = intval($_GET['limit'] ?? 500); 

if (!$id) {
    echo json_encode(['comments' => [], 'count' => 0]); 
    exit;
}

try {
    // 2. 准备 SQL 语句
    // 注意：LIMIT 后面接参数在 PDO 中需要特别小心
    $sql = "SELECT * FROM comments WHERE capsule_id = :id AND status = 'approved' ORDER BY id ASC LIMIT :limit";
    $stmt = $pdo->prepare($sql);

    // 3. 强制绑定为整数 (这是解决 Loading 卡死的关键)
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    
    $stmt->execute();
    
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['comments' => $comments, 'count' => count($comments)]);

} catch (Exception $e) {
    // 4. 如果出错，返回错误信息，而不是让前端干等
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>