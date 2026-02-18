<?php
// ============================================================
// RESCHEVIE — PHP BACKEND INTEGRATION GUIDE
// Replace localStorage with real MySQL + PHP backend
// Place these files in your web server (e.g., XAMPP/WAMP htdocs)
// ============================================================

// db_connect.php — include this in every PHP file
/*
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // change to your MySQL user
define('DB_PASS', '');           // change to your MySQL password
define('DB_NAME', 'reschevie_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['error' => 'DB connection failed: ' . $conn->connect_error]));
}
$conn->set_charset('utf8mb4');
?>
*/

// ============================================================
// api/auth.php — Login / Registration / Logout
// ============================================================
/*
session_start();
include 'db_connect.php';
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT user_id, user_fname, user_lname, user_email, user_password FROM users WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result && password_verify($password, $result['user_password'])) {
        $_SESSION['user'] = ['id' => $result['user_id'], 'fname' => $result['user_fname'], 'email' => $result['user_email'], 'role' => 'user'];
        echo json_encode(['success' => true, 'role' => 'user']);
    } else {
        // Check admins table
        $stmt2 = $conn->prepare("SELECT admin_id, admin_fname, admin_email, admin_password FROM admins WHERE admin_email = ?");
        $stmt2->bind_param("s", $email);
        $stmt2->execute();
        $admin = $stmt2->get_result()->fetch_assoc();
        if ($admin && password_verify($password, $admin['admin_password'])) {
            $_SESSION['user'] = ['id' => $admin['admin_id'], 'fname' => $admin['admin_fname'], 'email' => $admin['admin_email'], 'role' => 'admin'];
            echo json_encode(['success' => true, 'role' => 'admin']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    }
}

if ($action === 'register') {
    $username = $conn->real_escape_string($_POST['username']);
    $email    = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $fname    = $conn->real_escape_string($_POST['fname']);
    $lname    = $conn->real_escape_string($_POST['lname']);
    $contact  = $conn->real_escape_string($_POST['contact'] ?? '');

    $check = $conn->prepare("SELECT user_id FROM users WHERE user_email = ? OR user_username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email or username already taken']);
    } else {
        $stmt = $conn->prepare("INSERT INTO users (user_username, user_password, user_email, user_fname, user_lname, user_contact) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $username, $password, $email, $fname, $lname, $contact);
        echo $stmt->execute() ? json_encode(['success' => true]) : json_encode(['success' => false, 'message' => 'Registration failed']);
    }
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
}
*/

// ============================================================
// api/products.php — CRUD for products
// ============================================================
/*
session_start();
include 'db_connect.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// GET all products (public)
if ($method === 'GET') {
    $where = "1=1";
    if (isset($_GET['origin'])) $where .= " AND product_origin = '" . $conn->real_escape_string($_GET['origin']) . "'";
    if (isset($_GET['type']))   $where .= " AND product_type = '"   . $conn->real_escape_string($_GET['type'])   . "'";
    if (isset($_GET['status'])) $where .= " AND product_status = '" . $conn->real_escape_string($_GET['status']) . "'";
    $result = $conn->query("SELECT * FROM products WHERE $where ORDER BY product_featured DESC, product_id DESC");
    $products = [];
    while ($row = $result->fetch_assoc()) $products[] = $row;
    echo json_encode($products);
}

// POST/PUT/DELETE require admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    if ($method !== 'GET') { http_response_code(403); echo json_encode(['error' => 'Unauthorized']); exit; }
}

// POST — add product
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO products (product_name, product_description, product_type, product_origin, product_materials, product_karat, product_weight, product_price, product_price_poa, product_status, product_featured, product_emoji) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $price = $data['product_price_poa'] ? NULL : $data['product_price'];
    $stmt->bind_param("sssssssdisis", $data['product_name'], $data['product_description'], $data['product_type'], $data['product_origin'], $data['product_materials'], $data['product_karat'], $data['product_weight'], $price, $data['product_price_poa'], $data['product_status'], $data['product_featured'], $data['product_emoji']);
    echo $stmt->execute() ? json_encode(['success' => true, 'id' => $conn->insert_id]) : json_encode(['success' => false]);
}

// PUT — update product
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)$data['product_id'];
    $stmt = $conn->prepare("UPDATE products SET product_name=?, product_description=?, product_type=?, product_origin=?, product_materials=?, product_karat=?, product_weight=?, product_price=?, product_price_poa=?, product_status=?, product_featured=?, product_emoji=? WHERE product_id=?");
    $price = $data['product_price_poa'] ? NULL : $data['product_price'];
    $stmt->bind_param("sssssssdiissi", $data['product_name'], $data['product_description'], $data['product_type'], $data['product_origin'], $data['product_materials'], $data['product_karat'], $data['product_weight'], $price, $data['product_price_poa'], $data['product_status'], $data['product_featured'], $data['product_emoji'], $id);
    echo $stmt->execute() ? json_encode(['success' => true]) : json_encode(['success' => false]);
}

// DELETE — remove product
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    echo $stmt->execute() ? json_encode(['success' => true]) : json_encode(['success' => false]);
}
*/

// ============================================================
// api/inquiries.php — Submit & manage inquiries
// ============================================================
/*
session_start();
include 'db_connect.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = isset($_SESSION['user']) ? $_SESSION['user']['id'] : NULL;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO inquiries (user_id, fname, lname, email, phone, contact_pref, special_requests) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $userId, $data['fname'], $data['lname'], $data['email'], $data['phone'], $data['contactPref'], $data['notes']);
        $stmt->execute();
        $inquiryId = $conn->insert_id;

        foreach ($data['items'] as $productId) {
            $stmt2 = $conn->prepare("INSERT INTO inquiry_items (inquiry_id, product_id) VALUES (?,?)");
            $stmt2->bind_param("ii", $inquiryId, $productId);
            $stmt2->execute();
        }
        $conn->commit();
        echo json_encode(['success' => true, 'inquiry_id' => $inquiryId]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
*/

// ============================================================
// HOW TO CONNECT YOUR JS TO THE PHP BACKEND
// In js/data.js, replace localStorage functions with:
// ============================================================
/*
// Example: Replace getProducts() with:
async function getProducts() {
    const res = await fetch('api/products.php');
    return await res.json();
}

// Replace saveProducts() with:
async function saveProducts(products) {
    // Called per-product via admin panel, handled by add/update endpoints
}

// Replace inquiry submission:
async function submitInquiryToServer(data) {
    const res = await fetch('api/inquiries.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return await res.json();
}
*/

echo "// This file is for documentation only — not executed directly.";
?>
