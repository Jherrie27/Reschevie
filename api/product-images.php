<?php
// ============================================================
// api/product-images.php — Product image management (admin only)
// GET    ?product_id=X  — list images for a product
// POST   multipart      — upload image for a product
// DELETE ?id=X          — remove an image record
// ============================================================

ini_set('display_errors', 0);
error_reporting(0);
session_start();
include 'db_connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// GET — List images for a product
// ============================================================
if ($method === 'GET') {
    $product_id = (int)($_GET['product_id'] ?? 0);
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT p_image_id, p_image_url, is_primary, sort_order
         FROM product_images
         WHERE product_id = ?
         ORDER BY is_primary DESC, sort_order ASC"
    );
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    $stmt->close();
    echo json_encode($images);
    exit;
}

// ============================================================
// POST — Upload an image for a product
// ============================================================
if ($method === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No valid image file uploaded']);
        exit;
    }

    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type. Allowed: jpg, jpeg, png, webp']);
        exit;
    }

    $filename    = 'product_' . uniqid('', true) . '.' . $ext;
    $upload_dir  = __DIR__ . '/../resources/';
    $target      = $upload_dir . $filename;

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save image file. Check folder permissions.']);
        exit;
    }

    $image_url  = 'resources/' . $filename;
    $is_primary = (int)($_POST['is_primary'] ?? 0);

    // If flagged as primary, clear existing primary for this product
    if ($is_primary) {
        $upStmt = $conn->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
        $upStmt->bind_param("i", $product_id);
        $upStmt->execute();
        $upStmt->close();
    }

    $stmt = $conn->prepare(
        "INSERT INTO product_images (product_id, p_image_url, is_primary) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("isi", $product_id, $image_url, $is_primary);

    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'id' => $id, 'url' => $image_url]);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to save image record']);
    }
    exit;
}

// ============================================================
// DELETE — Remove an image record
// ============================================================
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM product_images WHERE p_image_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to delete image']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
