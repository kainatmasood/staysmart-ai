<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

const AI_SERVICE_URL = "http://127.0.0.1:5000";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$input = get_json_input();
$query = trim($input['query'] ?? '');

if (!$query) {
    respond(["error" => "query is required."], 400);
}

// Fetch active properties so the AI service / fallback can search over them
$stmt = $pdo->query("SELECT * FROM properties WHERE status = 'active'");
$properties = $stmt->fetchAll();

$result = call_ai_service('/chatbot', [
    'query' => $query,
    'properties' => $properties,
]);

if ($result !== null) {
    respond($result);
}

// ---- Local fallback: simple keyword parsing ----
$q = strtolower($query);
$matches = $properties;

// "under Rs 50000" / "under 50000"
if (preg_match('/under\s*(?:rs\.?)?\s*([\d,]+)/i', $q, $m)) {
    $max = (float)str_replace(',', '', $m[1]);
    $matches = array_filter($matches, fn($p) => $p['rent'] <= $max);
}

// city / location keywords
foreach (['islamabad','lahore','karachi'] as $city) {
    if (str_contains($q, $city)) {
        $matches = array_filter($matches, fn($p) => strtolower($p['city']) === $city);
    }
}

// duration keyword e.g. "six month" / "6 month" -> just informational, no filter needed
$matches = array_values($matches);

respond([
    "reply" => count($matches) > 0
        ? "I found " . count($matches) . " matching propert" . (count($matches) === 1 ? "y" : "ies") . " for you."
        : "Sorry, I couldn't find any properties matching your request.",
    "properties" => array_slice($matches, 0, 5),
    "source" => "fallback-keyword",
]);

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
