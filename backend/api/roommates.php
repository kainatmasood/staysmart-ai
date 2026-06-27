<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

// URL of the Python AI microservice (see ai-service/app.py)
const AI_SERVICE_URL = "http://127.0.0.1:5000";

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

/**
 * GET /roommates.php          -> current user's roommate request + matches
 */
function handleGet(PDO $pdo): void {
    $session = require_login();

    $stmt = $pdo->prepare("SELECT * FROM roommate_requests WHERE user_id = ? AND status != 'closed'
                            ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$session['user_id']]);
    $myRequest = $stmt->fetch();

    if (!$myRequest) {
        respond(["my_request" => null, "matches" => []]);
    }

    // Fetch all other open requests with user profile info
    $stmt = $pdo->prepare("SELECT rr.*, u.name, u.gender, u.institution, u.occupation
                            FROM roommate_requests rr
                            JOIN users u ON rr.user_id = u.user_id
                            WHERE rr.status = 'open' AND rr.user_id != ?");
    $stmt->execute([$session['user_id']]);
    $candidates = $stmt->fetchAll();

    // Get my profile info for scoring
    $stmt = $pdo->prepare("SELECT gender, institution, occupation FROM users WHERE user_id = ?");
    $stmt->execute([$session['user_id']]);
    $myProfile = $stmt->fetch();

    $matches = score_matches($myRequest, $myProfile, $candidates);

    respond(["my_request" => $myRequest, "matches" => $matches]);
}

/**
 * POST /roommates.php -> create / update roommate request
 * body: { budget, preferred_city, lifestyle }
 */
function handlePost(PDO $pdo): void {
    $session = require_login();
    $input = get_json_input();

    $budget   = $input['budget'] ?? null;
    $city     = $input['preferred_city'] ?? null;
    $lifestyle = $input['lifestyle'] ?? null;

    if (!$budget) {
        respond(["error" => "budget is required."], 400);
    }

    // Close any previous open request, then create a new one
    $pdo->prepare("UPDATE roommate_requests SET status = 'closed' WHERE user_id = ? AND status = 'open'")
        ->execute([$session['user_id']]);

    $stmt = $pdo->prepare("INSERT INTO roommate_requests (user_id, budget, preferred_city, lifestyle, status)
                            VALUES (?, ?, ?, ?, 'open')");
    $stmt->execute([$session['user_id'], $budget, $city, $lifestyle]);

    respond(["message" => "Roommate request created.", "request_id" => $pdo->lastInsertId()], 201);
}

/**
 * Compatibility scoring between the current user's request and candidates.
 * Tries the AI microservice first; falls back to a local rule-based score
 * if the AI service is unavailable (e.g., during local development).
 *
 * Scoring weights:
 *   - Budget similarity   : 40%
 *   - Lifestyle match     : 25%
 *   - City match          : 20%
 *   - Institution/Occupation match : 15%
 */
function score_matches(array $myRequest, array $myProfile, array $candidates): array {

    // Try delegating to the AI microservice for ML-based scoring
    $ai = call_ai_service('/match-roommates', [
        'my_request' => $myRequest,
        'my_profile' => $myProfile,
        'candidates' => $candidates,
    ]);

    if ($ai !== null && isset($ai['matches'])) {
        return $ai['matches'];
    }

    // ---- Local fallback scoring ----
    $results = [];
    foreach ($candidates as $c) {
        $score = 0;

        // Budget similarity (closer = higher score)
        $diff = abs((float)$myRequest['budget'] - (float)$c['budget']);
        $maxBudget = max((float)$myRequest['budget'], (float)$c['budget'], 1);
        $budgetScore = max(0, 1 - ($diff / $maxBudget));
        $score += $budgetScore * 40;

        // Lifestyle match
        if (!empty($myRequest['lifestyle']) && strtolower($myRequest['lifestyle']) === strtolower($c['lifestyle'] ?? '')) {
            $score += 25;
        }

        // City match
        if (!empty($myRequest['preferred_city']) && strtolower($myRequest['preferred_city']) === strtolower($c['preferred_city'] ?? '')) {
            $score += 20;
        }

        // Institution / Occupation match
        if (!empty($myProfile['institution']) && strtolower($myProfile['institution']) === strtolower($c['institution'] ?? '')) {
            $score += 10;
        }
        if (!empty($myProfile['occupation']) && strtolower($myProfile['occupation']) === strtolower($c['occupation'] ?? '')) {
            $score += 5;
        }

        $results[] = [
            'user_id'        => $c['user_id'],
            'name'           => $c['name'],
            'budget'         => $c['budget'],
            'preferred_city' => $c['preferred_city'],
            'lifestyle'      => $c['lifestyle'],
            'institution'    => $c['institution'],
            'occupation'     => $c['occupation'],
            'compatibility'  => round(min($score, 100)),
        ];
    }

    // Sort by compatibility descending
    usort($results, fn($a, $b) => $b['compatibility'] <=> $a['compatibility']);

    return $results;
}

/**
 * Helper: call the Python AI microservice. Returns decoded JSON array
 * on success, or null if the service is unreachable / errors out.
 */
function call_ai_service(string $path, array $payload): ?array {
    $ch = curl_init(AI_SERVICE_URL . $path);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

    $response = curl_exec($ch);
    $error = curl_errno($ch);
    curl_close($ch);

    if ($error || $response === false) {
        return null;
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}
