<?php
// set_main_image.php
require_once 'auth_check.php';

$image_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($image_id <= 0 || $product_id <= 0) {
    header("Location: products.php");
    exit;
}

// Verify image belongs to product
$stmt = $pdo->prepare("SELECT id FROM product_images WHERE id = ? AND product_id = ?");
$stmt->execute([$image_id, $product_id]);
if (!$stmt->fetch()) {
    header("Location: product_edit.php?id=" . $product_id);
    exit;
}

// Set all images of this product as non-main
$stmt = $pdo->prepare("UPDATE product_images SET is_main = 0 WHERE product_id = ?");
$stmt->execute([$product_id]);

// Set selected image as main
$stmt = $pdo->prepare("UPDATE product_images SET is_main = 1 WHERE id = ?");
$stmt->execute([$image_id]);

header("Location: product_edit.php?id=" . $product_id);
exit;