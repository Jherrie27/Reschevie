<?php
// ============================================================
// api/stories.php — Client stories / testimonials
// GET    — fetch all stories (public)
// POST   — add a story (admin only)
// PUT    — update a story (admin only)
// DELETE — delete a story (admin only)
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
// GET — Fetch all stories (public)
// ============================================================
if ($method === 'GET') {
    $stmt = $conn->prepare(
        "SELECT story_id, story_name, story_author, story_description, story_date_posted, created_at
         FROM client_stories
         ORDER BY story_date_posted DESC"
    );
    $stmt->execute();
    $result  = $stmt->get_result();
    $stories = [];

    while ($row = $result->fetch_assoc()) {
        $stories[] = $row;
    }

    $stmt->close();
    echo json_encode($stories);
    exit;
}

// ============================================================
// POST — Add a new story (admin only)
// ============================================================
if ($method === 'POST') {
    requireAdmin();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit;
    }

    // Required fields
    foreach (['story_name', 'story_author', 'story_description'] as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }

    $name        = trim($data['story_name']);
    $author      = trim($data['story_author']);
    $description = trim($data['story_description']);
    $datePosted  = isset($data['story_date_posted']) && $data['story_date_posted']
                    ? $data['story_date_posted']
                    : date('Y-m-d');

    // Date (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePosted) || !strtotime($datePosted)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO client_stories (story_name, story_author, story_description, story_date_posted)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $name, $author, $description, $datePosted);

    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        error_log('[RESCHEVIE] Story insert error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to add story']);
    }
    exit;
}

// ============================================================
// PUT — Update a story (admin only)
// ============================================================
if ($method === 'PUT') {
    requireAdmin();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit;
    }

    $id = (int)($data['story_id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid story ID']);
        exit;
    }

    // Confirm story exists
    $check = $conn->prepare("SELECT story_id FROM client_stories WHERE story_id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Story not found']);
        exit;
    }
    $check->close();

    foreach (['story_name', 'story_author', 'story_description'] as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }

    $name        = trim($data['story_name']);
    $author      = trim($data['story_author']);
    $description = trim($data['story_description']);
    $datePosted  = isset($data['story_date_posted']) && $data['story_date_posted']
                    ? $data['story_date_posted']
                    : date('Y-m-d');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePosted) || !strtotime($datePosted)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']);
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE client_stories
         SET story_name = ?, story_author = ?, story_description = ?, story_date_posted = ?
         WHERE story_id = ?"
    );
    $stmt->bind_param("ssssi", $name, $author, $description, $datePosted, $id);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        error_log('[RESCHEVIE] Story update error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update story']);
    }
    exit;
}

// ============================================================
// DELETE — Remove a story (admin only)
// ============================================================
if ($method === 'DELETE') {
    requireAdmin();

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid story ID']);
        exit;
    }

    $check = $conn->prepare("SELECT story_id FROM client_stories WHERE story_id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Story not found']);
        exit;
    }
    $check->close();

    $stmt = $conn->prepare("DELETE FROM client_stories WHERE story_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        error_log('[RESCHEVIE] Story delete error: ' . $conn->error);
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to delete story']);
    }
    exit;
}

// ============================================================
// UNKNOWN METHOD
// ============================================================
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);