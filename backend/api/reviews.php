<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($pdo);
        break;
    case 'POST':
        handlePost($pdo);
        break;
    default:
        respond(["error" => "Method not allowed"], 405);
}

/** GET /reviews.php?property_id=5 */
function handleGet(PDO $pdo): void {
    if (empty($_GET['property_id'])) {
        respond(["error" => "property_id is required."], 400);
    }
    $stmt = $pdo->prepare("SELECT r.*, u.name AS user_name FROM reviews r
                            JOIN users u ON r.user_id = u.user_id
                            WHERE property_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$_GET['property_id']]);
    respond($stmt->fetchAll());
}

/**
 * POST /reviews.php -> add review (tenant must have a completed/confirmed booking for the property)
 * body: { property_id, rating (1-5), comments }
 */
function handlePost(PDO $pdo): void {
    $session = require_login();
    $input = get_json_input();

    $propertyId = $input['property_id'] ?? null;
    $rating     = (int)($input['rating'] ?? 0);
    $comments   = trim($input['comments'] ?? '');

    if (!$propertyId || $rating < 1 || $rating > 5) {
        respond(["error" => "property_id and rating (1-5) are required."], 400);
    }

    // Verify the tenant has booked this property before
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings
                            WHERE user_id = ? AND property_id = ? AND status IN ('confirmed','completed')");
    $stmt->execute([$session['user_id'], $propertyId]);
    if ($stmt->fetchColumn() == 0) {
        respond(["error" => "You can only review properties you have booked."], 403);
    }

    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, property_id, rating, comments) VALUES (?, ?, ?, ?)");
    $stmt->execute([$session['user_id'], $propertyId, $rating, $comments]);

    respond(["message" => "Review submitted successfully.", "review_id" => $pdo->lastInsertId()], 201);
}
