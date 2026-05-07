<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, name, region, delivery_price 
    FROM cities 
    WHERE is_active = 1 AND name LIKE CONCAT(?, '%')
    ORDER BY 
        CASE WHEN name = ? THEN 1 ELSE 0 END DESC,
        LENGTH(name) ASC,
        name ASC
    LIMIT 15
");
$stmt->execute([$query, $query]);
$cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Принудительно преобразуем delivery_price в число
foreach ($cities as &$city) {
    $city['delivery_price'] = (float)($city['delivery_price'] ?? 0);
}

echo json_encode($cities);