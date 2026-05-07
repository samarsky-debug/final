<?php require_once 'auth_check.php';
$id = (int)$_GET['id'];
$pdo->prepare("DELETE FROM cities WHERE id = ?")->execute([$id]);
header('Location: cities.php');
exit;