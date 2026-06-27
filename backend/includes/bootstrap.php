<?php
/**
 * Common bootstrap: CORS headers, JSON helpers, session start
 * Include this at the top of every API file.
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

/** Read JSON body of the request as an associative array */
function get_json_input(): array {
    $data = json_decode(file_get_contents("php://input"), true);
    return is_array($data) ? $data : [];
}

/** Send a JSON response and stop execution */
function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/** Ensure a user is logged in, return user array from session */
function require_login(): array {
    if (!isset($_SESSION['user_id'])) {
        respond(["error" => "Unauthorized. Please log in."], 401);
    }
    return $_SESSION;
}

/** Ensure logged-in user has one of the allowed roles */
function require_role(array $roles): array {
    $session = require_login();
    if (!in_array($session['role'], $roles)) {
        respond(["error" => "Forbidden. You do not have permission."], 403);
    }
    return $session;
}
