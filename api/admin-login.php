<?php
// Dedicated admin login endpoint for Reschevie
session_start();
include 'db_connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

$stmt = $conn->prepare("SELECT admin_id, admin_fname, admin_lname, admin_email, admin_password FROM admins WHERE admin_email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($admin) {
    if ($password === $admin['admin_password'] || password_verify($password, $admin['admin_password'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id'    => $admin['admin_id'],
            'fname' => $admin['admin_fname'],
            'lname' => $admin['admin_lname'],
            'email' => $admin['admin_email'],
            'role'  => 'admin'
        ];
        echo json_encode(['success' => true, 'role' => 'admin', 'fname' => $admin['admin_fname']]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
