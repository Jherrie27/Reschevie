<?php
ob_start();
session_start();
include 'db_connect.php';
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ============================================================
// api/users.php — User management (admin only)
// GET    — fetch all users
// PUT    — update a user's details
// DELETE — delete a user by ID
// ============================================================

$method = $_SERVER['REQUEST_METHOD'];

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

// ============================================================
// GET — Fetch all users (admin only)
// ============================================================
if ($method === 'GET') {
    requireAdmin();

    $stmt = $conn->prepare(
        "SELECT user_id, user_username, user_email, user_fname, user_lname,
                user_contact, created_at, updated_at
         FROM users
         ORDER BY created_at DESC"
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $users  = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    $stmt->close();
    ob_clean();
    echo json_encode($users);
    exit;
}

// ============================================================
// PUT — Update a user (admin only)
// ============================================================
if ($method === 'PUT') {
    requireAdmin();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit;
    }

    $id = (int)($data['user_id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }

    // Confirm user exists
    $check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    $check->close();

    $fname    = trim($data['user_fname']    ?? '');
    $lname    = trim($data['user_lname']    ?? '');
    $email    = trim($data['user_email']    ?? '');
    $contact  = trim($data['user_contact']  ?? '');
    $username = trim($data['user_username'] ?? '');

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE users
         SET user_fname = ?, user_lname = ?, user_email = ?,
             user_contact = ?, user_username = ?
         WHERE user_id = ?"
    );
    $stmt->bind_param("sssssi", $fname, $lname, $email, $contact, $username, $id);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        error_log('[RESCHEVIE] User update error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
    exit;
}

// ============================================================
// DELETE — Delete a user by ID (admin only)
// ============================================================
if ($method === 'DELETE') {
    requireAdmin();

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }

    // Confirm user exists before deleting
    $check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    $check->close();

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        error_log('[RESCHEVIE] User delete error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
    exit;
}

// ============================================================
// UNKNOWN METHOD
// ============================================================
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
