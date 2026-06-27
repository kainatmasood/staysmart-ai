<?php
require_once __DIR__ . '/../includes/bootstrap.php';

const AI_SERVICE_URL = "http://127.0.0.1:5000";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

require_role(['owner', 'admin']);

$input = get_json_input();

$required = ['city', 'rooms', 'property_type'];
foreach ($required as $f) {
    if (empty($input[$f]) && $input[$f] !== 0) {
        respond(["error" => "Field '$f' is required."], 400);
    }
}

$payload = [
    'city'          => $input['city'],
    'rooms'         => (int)$input['rooms'],
    'property_type' => $input['property_type'],
    'facilities'    => $input['facilities'] ?? [],
];

$result = call_ai_service('/predict-price', $payload);

if ($result === null) {
    // Fallback: simple heuristic if AI service is offline
    $base = ['islamabad' => 30000, 'lahore' => 28000, 'karachi' => 27000][strtolower($payload['city'])] ?? 20000;
    $price = $base + ($payload['rooms'] - 1) * 8000;
    $price += count($payload['facilities']) * 1500;
    if ($payload['property_type'] === 'house') $price *= 1.3;
    if ($payload['property_type'] === 'studio') $price *= 0.8;

    respond([
        "predicted_price" => round($price, -2),
        "source" => "fallback-heuristic",
        "note" => "AI microservice unavailable, used local heuristic estimate."
    ]);
}

respond($result);

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
