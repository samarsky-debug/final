<?php
// promo_delete.php
require_once 'auth_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM promocodes WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: promocodes.php');
exit;