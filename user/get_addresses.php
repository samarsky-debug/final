<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, city, zip_code, address, is_default FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'addresses' => $addresses]);