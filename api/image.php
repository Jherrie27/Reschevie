<?php
// api/image.php — Serves a product image stored as binary in the DB
// Usage: /api/image.php?id=X
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
include 'db_connect.php';
ob_clean();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit;
}

$stmt = $conn->prepare("SELECT p_image_data, p_image_mime FROM product_images WHERE p_image_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row || !$row['p_image_data']) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . ($row['p_image_mime'] ?: 'image/jpeg'));
header('Cache-Control: public, max-age=86400');
echo $row['p_image_data'];
