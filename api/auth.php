<?php
session_start();
include 'db_connect.php';
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
ini_set('display_errors', 0);
error_reporting(E_ALL);
// ============================================================
// api/auth.php — Login / Registration / Logout
// ============================================================

session_start();
include 'db_connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Only POST (GET logout okay, but POST safer)
$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

// ============================================================
// LOGIN
// ============================================================
if ($action === 'login') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Check users table first
    $stmt = $conn->prepare(
        "SELECT user_id, user_fname, user_lname, user_email, user_password
         FROM users
         WHERE user_email = ?
         LIMIT 1"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['user_password'])) {
        // Regenerate session ID on login to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'    => $user['user_id'],
            'fname' => $user['user_fname'],
            'lname' => $user['user_lname'],
            'email' => $user['user_email'],
            'role'  => 'user'
        ];

        echo json_encode([
            'success' => true,
            'role'    => 'user',
            'fname'   => $user['user_fname']
        ]);
        exit;
    }

    // Check admins table
    $stmt2 = $conn->prepare(
        "SELECT admin_id, admin_fname, admin_lname, admin_email, admin_password
         FROM admins
         WHERE admin_email = ?
         LIMIT 1"
    );
    $stmt2->bind_param("s", $email);
    $stmt2->execute();
    $admin = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    if ($admin && password_verify($password, $admin['admin_password'])) {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'    => $admin['admin_id'],
            'fname' => $admin['admin_fname'],
            'lname' => $admin['admin_lname'] ?? '',
            'email' => $admin['admin_email'],
            'role'  => 'admin'
        ];

        echo json_encode([
            'success' => true,
            'role'    => 'admin',
            'fname'   => $admin['admin_fname']
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

// ============================================================
// REGISTER
// ============================================================
if ($action === 'register') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $fname    = trim($_POST['fname']    ?? '');
    $lname    = trim($_POST['lname']    ?? '');
    $contact  = trim($_POST['contact']  ?? '');

    // Input val
    if (!$username || !$email || !$password || !$fname || !$lname) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    if (strlen($username) < 3 || strlen($username) > 50) {
        echo json_encode(['success' => false, 'message' => 'Username must be 3–50 characters']);
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        echo json_encode(['success' => false, 'message' => 'Username may only contain letters, numbers, dots, hyphens, and underscores']);
        exit;
    }

    // Password strength
    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters and include one uppercase letter and one number']);
        exit;
    }

    // Duplicate check
    $check = $conn->prepare(
        "SELECT user_id FROM users WHERE user_email = ? OR user_username = ? LIMIT 1"
    );
    $check->bind_param("ss", $email, $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        echo json_encode(['success' => false, 'message' => 'Email or username is already taken']);
        exit;
    }
    $check->close();

    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $conn->prepare(
        "INSERT INTO users
            (user_username, user_password, user_email, user_fname, user_lname, user_contact, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param("ssssss", $username, $hashed, $email, $fname, $lname, $contact);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        $stmt->close();
        error_log('[RESCHEVIE] Registration DB error: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
    exit;
}

// ============================================================
// LOGOUT
// ============================================================
if ($action === 'logout') {
    // Wipe session data, destroy session, and expire cookie
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// ============================================================
// UNKNOWN ACTION
// ============================================================
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);