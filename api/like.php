<?php
/* Author: shiguang */
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
$id = intval($_POST['id'] ?? 0);
$ip = $_SERVER['REMOTE_ADDR'];

if (!$id) { echo json_encode(['success'=>false]); exit; }

try {
    // 尝试插入点赞记录，如果IP已存在则失败（UNIQUE约束）
    $stmt = $pdo->prepare("INSERT INTO likes (capsule_id, ip_address) VALUES (?, ?)");
    $stmt->execute([$id, $ip]);
    
    // 如果插入成功，更新总数
    $pdo->prepare("UPDATE capsules SET likes_count = likes_count + 1 WHERE id = ?")->execute([$id]);
    
    // 获取最新总数
    $cnt = $pdo->prepare("SELECT likes_count FROM capsules WHERE id = ?");
    $cnt->execute([$id]);
    echo json_encode(['success' => true, 'new_count' => $cnt->fetchColumn()]);
} catch (Exception $e) {
    // 可能是重复点赞
    echo json_encode(['success' => false, 'message' => 'Already liked']);
}