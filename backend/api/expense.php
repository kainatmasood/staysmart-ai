<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$input = get_json_input();

$rent        = (float)($input['rent'] ?? 0);
$electricity = (float)($input['electricity'] ?? 0);
$water       = (float)($input['water'] ?? 0);
$internet    = (float)($input['internet'] ?? 0);
$maintenance = (float)($input['maintenance'] ?? 0);

if ($rent <= 0) {
    respond(["error" => "rent is required and must be greater than 0."], 400);
}

$total = $rent + $electricity + $water + $internet + $maintenance;

respond([
    "breakdown" => [
        "rent"         => $rent,
        "electricity"  => $electricity,
        "water"        => $water,
        "internet"     => $internet,
        "maintenance"  => $maintenance,
    ],
    "total_monthly_cost" => $total,
    "message" => "Estimated total monthly living cost is Rs. " . number_format($total, 2),
]);
