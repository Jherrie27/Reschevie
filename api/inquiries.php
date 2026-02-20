<?php
ob_start();
session_start();
include 'db_connect.php';
header('Content-Type: application/json');

// ============================================================
// api/inquiries.php — Submit & manage inquiries
// POST   — submit a new inquiry (public/authenticated)
// GET    — fetch all inquiries with items (admin only)
// PUT    — update inquiry status (admin only)
// DELETE — delete an inquiry (admin only)
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

const ALLOWED_CONTACT_PREFS = ['email', 'phone'];
const ALLOWED_STATUSES      = ['pending', 'in-progress', 'completed', 'cancelled'];

// ============================================================
// POST — Submit a new inquiry (public or logged-in user)
// ============================================================
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit;
    }

    // Required fields
    $required = ['fname', 'lname', 'email'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // items must be non-empty integer array
    $items = $data['items'] ?? [];
    if (!is_array($items) || count($items) === 0) {
        echo json_encode(['success' => false, 'message' => 'At least one product must be included in the inquiry']);
        exit;
    }
    $items = array_map('intval', $items);
    $items = array_filter($items, fn($id) => $id > 0);
    if (count($items) === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product IDs in inquiry']);
        exit;
    }

    $contactPref = $data['contactPref'] ?? 'email';
    if (!in_array($contactPref, ALLOWED_CONTACT_PREFS, true)) {
        $contactPref = 'email';
    }

    $fname   = trim($data['fname']);
    $lname   = trim($data['lname']);
    $email   = trim($data['email']);
    $phone   = trim($data['phone']   ?? '');
    $notes   = trim($data['notes']   ?? '');
    $userId  = isset($_SESSION['user']) ? (int)$_SESSION['user']['id'] : null;

    // All product IDs exist before inserting?
    $placeholders = implode(',', array_fill(0, count($items), '?'));
    $checkStmt = $conn->prepare(
        "SELECT product_id FROM products WHERE product_id IN ($placeholders)"
    );
    $types = str_repeat('i', count($items));
    $checkStmt->bind_param($types, ...$items);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows !== count($items)) {
        $checkStmt->close();
        echo json_encode(['success' => false, 'message' => 'One or more products do not exist']);
        exit;
    }
    $checkStmt->close();

    // Wrap in a transaction
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            "INSERT INTO inquiries
                (user_id, fname, lname, email, phone, contact_pref, special_requests)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issssss", $userId, $fname, $lname, $email, $phone, $contactPref, $notes);
        $stmt->execute();
        $inquiryId = $conn->insert_id;
        $stmt->close();

        $itemStmt = $conn->prepare(
            "INSERT INTO inquiry_items (inquiry_id, product_id) VALUES (?, ?)"
        );
        foreach ($items as $productId) {
            $itemStmt->bind_param("ii", $inquiryId, $productId);
            $itemStmt->execute();
        }
        $itemStmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'inquiry_id' => $inquiryId]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log('[RESCHEVIE] Inquiry insert error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to submit inquiry. Please try again.']);
    }
    exit;
}

// ============================================================
// GET — Fetch all inquiries with their products (admin only)
// ============================================================
if ($method === 'GET') {
    requireAdmin();

    // OPT. filter by status
    $statusFilter = $_GET['status'] ?? '';

    if ($statusFilter && !in_array($statusFilter, ALLOWED_STATUSES, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status filter']);
        exit;
    }

    $where = $statusFilter ? "WHERE i.status = ?" : '';
    $sql   = "SELECT
                i.inquiry_id, i.user_id, i.fname, i.lname, i.email,
                i.phone, i.contact_pref, i.special_requests, i.status,
                i.submitted_at, i.updated_at,
                GROUP_CONCAT(
                    JSON_OBJECT(
                        'product_id',   p.product_id,
                        'product_name', p.product_name,
                        'product_type', p.product_type,
                        'product_emoji', p.product_emoji
                    )
                ) AS items_json
              FROM inquiries i
              LEFT JOIN inquiry_items ii ON i.inquiry_id = ii.inquiry_id
              LEFT JOIN products p      ON ii.product_id = p.product_id
              $where
              GROUP BY i.inquiry_id
              ORDER BY i.submitted_at DESC";

    $stmt = $conn->prepare($sql);
    if ($statusFilter) {
        $stmt->bind_param("s", $statusFilter);
    }
    $stmt->execute();
    $result    = $stmt->get_result();
    $inquiries = [];

    while ($row = $result->fetch_assoc()) {
        $row['items'] = $row['items_json']
            ? json_decode('[' . $row['items_json'] . ']', true)
            : [];
        unset($row['items_json']);
        $inquiries[] = $row;
    }

    $stmt->close();
    ob_clean();
    echo json_encode($inquiries);
    exit;
}

// ============================================================
// PUT — Update inquiry status (admin only)
// ============================================================
if ($method === 'PUT') {
    requireAdmin();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit;
    }

    $id     = (int)($data['inquiry_id'] ?? 0);
    $status = $data['status'] ?? '';

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid inquiry ID']);
        exit;
    }
    if (!in_array($status, ALLOWED_STATUSES, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit;
    }

    $check = $conn->prepare("SELECT inquiry_id FROM inquiries WHERE inquiry_id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
        exit;
    }
    $check->close();

    $stmt = $conn->prepare("UPDATE inquiries SET status = ? WHERE inquiry_id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        error_log('[RESCHEVIE] Inquiry update error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update inquiry status']);
    }
    exit;
}

// ============================================================
// DELETE — Remove an inquiry (admin only)
// ============================================================
if ($method === 'DELETE') {
    requireAdmin();

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid inquiry ID']);
        exit;
    }

    $check = $conn->prepare("SELECT inquiry_id FROM inquiries WHERE inquiry_id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
        exit;
    }
    $check->close();

    // inquiry_items cascade-deletes via FK, so only delete parent row
    $stmt = $conn->prepare("DELETE FROM inquiries WHERE inquiry_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        error_log('[RESCHEVIE] Inquiry delete error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to delete inquiry']);
    }
    exit;
}

// ============================================================
// UNKNOWN METHOD
// ============================================================
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);