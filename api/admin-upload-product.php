<?php
// Admin Product Upload Handler for Reschevie
session_start();
include 'db_connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$type = $_POST['type'] ?? '';
$origin = $_POST['origin'] ?? '';
$materials = trim($_POST['materials'] ?? '');
$karat = trim($_POST['karat'] ?? '');
$weight = trim($_POST['weight'] ?? '');
$price = $_POST['price'] ?? null;
$status = $_POST['status'] ?? 'available';
$emoji = trim($_POST['emoji'] ?? '');

if (!$name || !$type || !$origin) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Handle image upload
$image_url = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type']);
        exit;
    }
    $filename = uniqid('product_', true) . '.' . $ext;
    $target = '../resources/' . $filename;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        $image_url = 'resources/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
}

$stmt = $conn->prepare("INSERT INTO products (product_name, product_type, product_origin, product_materials, product_karat, product_weight, product_price, product_status, product_emoji) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssdss", $name, $type, $origin, $materials, $karat, $weight, $price, $status, $emoji);
if ($stmt->execute()) {
    $product_id = $conn->insert_id;
    $stmt->close();
    // Insert image if uploaded
    if ($image_url) {
        $imgStmt = $conn->prepare("INSERT INTO product_images (product_id, p_image_url, is_primary) VALUES (?, ?, 1)");
        $imgStmt->bind_param("is", $product_id, $image_url);
        $imgStmt->execute();
        $imgStmt->close();
    }
    echo json_encode(['success' => true, 'id' => $product_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add product']);
}
