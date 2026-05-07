<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

$cityId = (int)($_POST['city_id'] ?? 0);
if ($cityId) {
    $stmt = $pdo->prepare("SELECT id, name FROM cities WHERE id = ? AND is_active = 1");
    $stmt->execute([$cityId]);
    $city = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($city) {
        $_SESSION['user_city'] = ['id' => $city['id'], 'name' => $city['name']];
        echo json_encode(['success' => true, 'city' => $city['name']]);
        exit;
    }
}
echo json_encode(['success' => false, 'error' => 'Город не найден']);