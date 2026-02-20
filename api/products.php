<?php
ob_start();
session_start();
include 'db_connect.php';
header('Content-Type: application/json');
// ============================================================
// api/products.php — CRUD for products
// ============================================================

session_start();
include 'db_connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// ADMIN GATE — must be declared BEFORE any write operation
// GET is intentionally public; everything else requires admin
// ============================================================
function requireAdmin() {
    if (
        !isset($_SESSION['user']) ||
        $_SESSION['user']['role'] !== 'admin'
    ) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

// Allowed filter values
const ALLOWED_ORIGINS = ['Italy', 'Japan', 'Saudi Arabia', 'Hong Kong'];
const ALLOWED_TYPES   = ['necklace', 'ring', 'bracelet', 'earring', 'pendant'];
const ALLOWED_STATUSES = ['available', 'sold', 'reserved'];

// ============================================================
// GET — Fetch all products (public)
// ============================================================
if ($method === 'GET') {
    $conditions = [];
    $params     = [];
    $types      = '';

    if (!empty($_GET['origin'])) {
        $conditions[] = "product_origin = ?";
        $params[]     = $_GET['origin'];
        $types       .= 's';
    }

    if (!empty($_GET['type'])) {
        $conditions[] = "product_type = ?";
        $params[]     = $_GET['type'];
        $types       .= 's';
    }

    if (!empty($_GET['status'])) {
        $conditions[] = "product_status = ?";
        $params[]     = $_GET['status'];
        $types       .= 's';
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sql   = "SELECT * FROM products $where ORDER BY product_featured DESC, product_id DESC";

    $stmt = $conn->prepare($sql);

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result   = $stmt->get_result();
    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $stmt->close();
    ob_clean();
    echo json_encode($products);
    exit;
}

// ============================================================
// POST — Add a product (admin only)
// ============================================================
if ($method === 'POST') {
    requireAdmin();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit;
    }

    // Required field
    $required = ['product_name', 'product_type', 'product_origin', 'product_status'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }

    // Whitelist validation
    if (!in_array($data['product_type'], ALLOWED_TYPES, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid product type']);
        exit;
    }
    if (!in_array($data['product_status'], ALLOWED_STATUSES, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid product status']);
        exit;
    }

    $name        = trim($data['product_name']);
    $description = trim($data['product_description'] ?? '');
    $type        = $data['product_type'];
    $origin      = trim($data['product_origin'] ?? '');
    $materials   = trim($data['product_materials'] ?? '');
    $karat       = trim($data['product_karat'] ?? '');
    $weight      = trim($data['product_weight'] ?? '');
    $poa         = !empty($data['product_price_poa']) ? 1 : 0;
    $price       = $poa ? null : (float)($data['product_price'] ?? 0);
    $status      = $data['product_status'];
    $featured    = !empty($data['product_featured']) ? 1 : 0;
    $emoji       = trim($data['product_emoji'] ?? '');

    $stmt = $conn->prepare(
        "INSERT INTO products
            (product_name, product_description, product_type, product_origin,
             product_materials, product_karat, product_weight,
             product_price, product_price_poa, product_status, product_featured, product_emoji)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    // s=string, d=double, i=integer
    // product_price=d (nullable), product_price_poa=i, product_featured=i
    $stmt->bind_param(
        "sssssssdisi s",
        $name, $description, $type, $origin,
        $materials, $karat, $weight,
        $price, $poa, $status, $featured, $emoji
    );

    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        error_log('[RESCHEVIE] Product insert error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to add product']);
    }
    exit;
}

// ============================================================
// PUT — Update a product (admin only)
// ============================================================
if ($method === 'PUT') {
    requireAdmin();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit;
    }

    $id = (int)($data['product_id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }

    // Confirm product exists
    $check = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    $check->close();

    // Whitelist validation
    if (isset($data['product_type']) && !in_array($data['product_type'], ALLOWED_TYPES, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid product type']);
        exit;
    }
    if (isset($data['product_status']) && !in_array($data['product_status'], ALLOWED_STATUSES, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid product status']);
        exit;
    }

    $name        = trim($data['product_name']        ?? '');
    $description = trim($data['product_description'] ?? '');
    $type        = $data['product_type']              ?? '';
    $origin      = trim($data['product_origin']      ?? '');
    $materials   = trim($data['product_materials']   ?? '');
    $karat       = trim($data['product_karat']       ?? '');
    $weight      = trim($data['product_weight']      ?? '');
    $poa         = !empty($data['product_price_poa']) ? 1 : 0;
    $price       = $poa ? null : (float)($data['product_price'] ?? 0);
    $status      = $data['product_status']            ?? '';
    $featured    = !empty($data['product_featured'])  ? 1 : 0;
    $emoji       = trim($data['product_emoji']        ?? '');

    $stmt = $conn->prepare(
        "UPDATE products
         SET product_name=?, product_description=?, product_type=?, product_origin=?,
             product_materials=?, product_karat=?, product_weight=?,
             product_price=?, product_price_poa=?, product_status=?, product_featured=?, product_emoji=?
         WHERE product_id=?"
    );

    $stmt->bind_param(
        "sssssssdi ssi",
        $name, $description, $type, $origin,
        $materials, $karat, $weight,
        $price, $poa, $status, $featured, $emoji,
        $id
    );

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        error_log('[RESCHEVIE] Product update error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update product']);
    }
    exit;
}

// ============================================================
// DELETE — Remove a product (admin only)
// ============================================================
if ($method === 'DELETE') {
    requireAdmin();

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }

    // Confirm product exists before deleting
    $check = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    $check->close();

    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        error_log('[RESCHEVIE] Product delete error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
    }
    exit;
}

// ============================================================
// UNKNOWN METHOD
// ============================================================
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);