<?php
ob_start();
session_start();
include 'db_connect.php';
header('Content-Type: application/json');

// ============================================================
// api/newsletters.php — Newsletter subscriptions
//
// NOTE on schema FK: newsletters.newsletter_email has a
// FOREIGN KEY referencing users.user_email ON DELETE CASCADE.
// This means only registered user emails can be subscribed.
// If you want to allow guest subscriptions, drop that FK from
// the schema and remove the user-existence check below.
//
// POST   — subscribe an email (logged-in user or guest*)
// GET    — list all active subscribers (admin only)
// DELETE — unsubscribe an email (own account or admin)
// ============================================================

session_start();
include 'db_connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'];

function requireAdmin() {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

// ============================================================
// POST — Subscribe an email
// ============================================================
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit;
    }

    $email = trim($data['email'] ?? '');

    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // FK constraint: email must belong to a registered user - remove if not needed like guest sub
    $userCheck = $conn->prepare("SELECT user_id FROM users WHERE user_email = ? LIMIT 1");
    $userCheck->bind_param("s", $email);
    $userCheck->execute();
    $userCheck->store_result();
    if ($userCheck->num_rows === 0) {
        $userCheck->close();
        echo json_encode([
            'success' => false,
            'message' => 'This email is not associated with a registered account'
        ]);
        exit;
    }
    $userCheck->close();

    // Already subscribed (active or inactive)?
    $existing = $conn->prepare(
        "SELECT newsletter_id, is_active FROM newsletters WHERE newsletter_email = ? LIMIT 1"
    );
    $existing->bind_param("s", $email);
    $existing->execute();
    $row = $existing->get_result()->fetch_assoc();
    $existing->close();

    if ($row) {
        if ($row['is_active']) {
            echo json_encode(['success' => false, 'message' => 'This email is already subscribed']);
            exit;
        }

        // Re-activate a previously unsubscribed email
        $stmt = $conn->prepare(
            "UPDATE newsletters SET is_active = 1, newsletter_subbed_at = NOW()
             WHERE newsletter_email = ?"
        );
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Successfully re-subscribed']);
        } else {
            error_log('[RESCHEVIE] Newsletter reactivate error: ' . $conn->error);
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Failed to subscribe. Please try again.']);
        }
        exit;
    }

    // New sub
    $stmt = $conn->prepare(
        "INSERT INTO newsletters (newsletter_email) VALUES (?)"
    );
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Successfully subscribed']);
    } else {
        error_log('[RESCHEVIE] Newsletter insert error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to subscribe. Please try again.']);
    }
    exit;
}

// ============================================================
// GET — List all active subscribers (admin only)
// ============================================================
if ($method === 'GET') {
    requireAdmin();

    // OPT. filter inactive via ?all=1
    $showAll   = isset($_GET['all']) && $_GET['all'] === '1';
    $whereClause = $showAll ? '' : 'WHERE n.is_active = 1';

    $stmt = $conn->prepare(
        "SELECT
            n.newsletter_id,
            n.newsletter_email,
            n.newsletter_subbed_at,
            n.is_active,
            u.user_fname,
            u.user_lname
         FROM newsletters n
         LEFT JOIN users u ON n.newsletter_email = u.user_email
         $whereClause
         ORDER BY n.newsletter_subbed_at DESC"
    );
    $stmt->execute();
    $result      = $stmt->get_result();
    $subscribers = [];

    while ($row = $result->fetch_assoc()) {
        $subscribers[] = $row;
    }

    $stmt->close();
    ob_clean();
    echo json_encode($subscribers);
    exit;
}

// ============================================================
// DELETE — Unsubscribe an email
// Users can only unsubscribe their own email; admins can do any
// ============================================================
if ($method === 'DELETE') {
    $email = trim($_GET['email'] ?? '');

    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    $isAdmin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
    $isOwner = isset($_SESSION['user']) && $_SESSION['user']['email'] === $email;

    if (!$isAdmin && !$isOwner) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $check = $conn->prepare(
        "SELECT newsletter_id, is_active FROM newsletters WHERE newsletter_email = ? LIMIT 1"
    );
    $check->bind_param("s", $email);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Email not found in subscribers']);
        exit;
    }
    if (!$row['is_active']) {
        echo json_encode(['success' => false, 'message' => 'Email is already unsubscribed']);
        exit;
    }

    // Soft-delete: set is_active = 0 to preserve history
    $stmt = $conn->prepare(
        "UPDATE newsletters SET is_active = 0 WHERE newsletter_email = ?"
    );
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Successfully unsubscribed']);
    } else {
        error_log('[RESCHEVIE] Newsletter unsubscribe error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to unsubscribe. Please try again.']);
    }
    exit;
}

// ============================================================
// UNKNOWN METHOD
// ============================================================
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);