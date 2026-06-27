<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

const AI_SERVICE_URL = "http://127.0.0.1:5000";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$input = get_json_input();

$budget    = (float)($input['budget'] ?? 0);
$city      = trim($input['location'] ?? '');
$duration  = (int)($input['duration'] ?? 1);     // months
$lifestyle = trim($input['lifestyle'] ?? '');
$facilities = $input['facilities'] ?? [];         // array of strings e.g. ['wifi','ac']

if ($budget <= 0) {
    respond(["error" => "budget is required."], 400);
}

// Fetch active properties (optionally pre-filtered by city)
$sql = "SELECT * FROM properties WHERE status = 'active'";
$params = [];
if ($city) {
    $sql .= " AND city LIKE ?";
    $params[] = "%$city%";
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

if (empty($properties)) {
    respond(["recommendations" => [], "message" => "No properties found for the given criteria."]);
}

// Try AI microservice first
$ai = call_ai_service('/recommend', [
    'budget'     => $budget,
    'location'   => $city,
    'duration'   => $duration,
    'lifestyle'  => $lifestyle,
    'facilities' => $facilities,
    'properties' => $properties,
]);

if ($ai !== null && isset($ai['recommendations'])) {
    respond($ai);
}

// ---- Local fallback recommendation logic ----
// Weights: budget fit 50%, lifestyle match 25%, facilities match 25%
$results = [];
foreach ($properties as $p) {
    $score = 0;

    // Budget fit: full score if rent <= budget, decreasing penalty if over budget
    if ($p['rent'] <= $budget) {
        $score += 50;
    } else {
        $over = ($p['rent'] - $budget) / $budget;
        $score += max(0, 50 * (1 - $over));
    }

    // Lifestyle match
    if ($lifestyle && strtolower($p['lifestyle_tag'] ?? '') === strtolower($lifestyle)) {
        $score += 25;
    }

    // Facilities match
    if (!empty($facilities)) {
        $propFacilities = array_map('trim', explode(',', strtolower($p['facilities'] ?? '')));
        $matched = 0;
        foreach ($facilities as $f) {
            if (in_array(strtolower(trim($f)), $propFacilities)) {
                $matched++;
            }
        }
        $score += 25 * ($matched / max(count($facilities), 1));
    } else {
        $score += 12; // neutral partial credit if no facility preference given
    }

    $results[] = [
        'property_id'   => $p['property_id'],
        'property_name' => $p['property_name'],
        'city'          => $p['city'],
        'rent'          => $p['rent'],
        'rooms'         => $p['rooms'],
        'facilities'    => $p['facilities'],
        'images'        => $p['images'],
        'match_score'   => round(min($score, 100)),
    ];
}

usort($results, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

respond(["recommendations" => array_slice($results, 0, 10)]);

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
