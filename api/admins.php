<?php
// ============================================================
// api/admins.php — Admin account management (admin only)
// GET    — list all admins
// POST   — add a new admin
// PUT    — update an admin (name, email, optional password)
// DELETE — delete an admin
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
// GET — List all admins
// ============================================================
if ($method === 'GET') {
    $stmt = $conn->prepare(
        "SELECT admin_id, admin_email, admin_fname, admin_lname, created_at
         FROM admins
         ORDER BY created_at ASC"
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $admins = [];
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    $stmt->close();
    echo json_encode($admins);
    exit;
}

// ============================================================
// POST — Add a new admin
// ============================================================
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit;
    }

    $email    = trim($data['admin_email']    ?? '');
    $fname    = trim($data['admin_fname']    ?? '');
    $lname    = trim($data['admin_lname']    ?? '');
    $password =      $data['admin_password'] ?? '';

    if (!$email || !$fname || !$lname || !$password) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $conn->prepare(
        "INSERT INTO admins (admin_email, admin_password, admin_fname, admin_lname) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $email, $hashed, $fname, $lname);

    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Email already exists or database error']);
    }
    exit;
}

// ============================================================
// PUT — Update an admin
// ============================================================
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit;
    }

    $id    = (int)($data['admin_id']    ?? 0);
    $email = trim($data['admin_email']  ?? '');
    $fname = trim($data['admin_fname']  ?? '');
    $lname = trim($data['admin_lname']  ?? '');

    if ($id <= 0 || !$email || !$fname || !$lname) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    if (!empty($data['admin_password'])) {
        if (strlen($data['admin_password']) < 8) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
            exit;
        }
        $hashed = password_hash($data['admin_password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $conn->prepare(
            "UPDATE admins SET admin_email=?, admin_fname=?, admin_lname=?, admin_password=? WHERE admin_id=?"
        );
        $stmt->bind_param("ssssi", $email, $fname, $lname, $hashed, $id);
    } else {
        $stmt = $conn->prepare(
            "UPDATE admins SET admin_email=?, admin_fname=?, admin_lname=? WHERE admin_id=?"
        );
        $stmt->bind_param("sssi", $email, $fname, $lname, $id);
    }

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update admin']);
    }
    exit;
}

// ============================================================
// DELETE — Remove an admin
// ============================================================
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid admin ID']);
        exit;
    }

    // Prevent deleting your own account
    if ($id === (int)$_SESSION['user']['id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM admins WHERE admin_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to delete admin']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
