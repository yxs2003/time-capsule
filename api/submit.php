<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$email = $_POST['email'] ?? '';
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$deliver_at = $_POST['deliver_at'] ?? '';
$is_public = isset($_POST['is_public']) ? 1 : 0;

if (strtotime($deliver_at) <= time()) {
    echo json_encode(['success' => false, 'message' => '时间必须是未来']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO capsules (email, title, content, deliver_at, is_public, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$email, $title, $content, $deliver_at, $is_public]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error']);
}