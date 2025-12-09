<?php
/* Author: shiguang */
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$capsule_id = intval($_POST['capsule_id'] ?? 0);
$parent_id = intval($_POST['parent_id'] ?? 0); // 接收父ID
$nickname = trim($_POST['nickname'] ?? '');
$email = trim($_POST['email'] ?? '');
$content = trim($_POST['content'] ?? '');

if (!$capsule_id || empty($content) || empty($nickname) || empty($email)) {
    echo json_encode(['success' => false, 'message' => '请填写所有字段']); exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (capsule_id, parent_id, nickname, email, content, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$capsule_id, $parent_id, htmlspecialchars($nickname), $email, htmlspecialchars($content)]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error']);
}