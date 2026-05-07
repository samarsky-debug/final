<?php
// delete_image.php
require_once 'auth_check.php';

$image_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($image_id <= 0 || $product_id <= 0) {
    header("Location: products.php");
    exit;
}

// Get image info from database
$stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ? AND product_id = ?");
$stmt->execute([$image_id, $product_id]);
$image = $stmt->fetch();

if ($image) {
    // Delete physical file
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($image['image_url'], '/');
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Delete record from database
    $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
    $stmt->execute([$image_id]);

    // Optional: If the deleted image was main, set another image as main
    $checkMain = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_main = 1");
    $checkMain->execute([$product_id]);
    $hasMain = $checkMain->fetchColumn();

    if (!$hasMain) {
        // Set the first available image as main
        $stmt = $pdo->prepare("SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order LIMIT 1");
        $stmt->execute([$product_id]);
        $newMain = $stmt->fetch();
        if ($newMain) {
            $stmt = $pdo->prepare("UPDATE product_images SET is_main = 1 WHERE id = ?");
            $stmt->execute([$newMain['id']]);
        }
    }
}

header("Location: product_edit.php?id=" . $product_id);
exit;