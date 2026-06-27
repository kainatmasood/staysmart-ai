<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$input = get_json_input();
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || !$password) {
    respond(["error" => "Email and password are required."], 400);
}

$stmt = $pdo->prepare("SELECT user_id, name, email, password, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    respond(["error" => "Invalid email or password."], 401);
}

// Set session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['name']    = $user['name'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = $user['role'];

respond([
    "message" => "Login successful.",
    "user" => [
        "user_id" => $user['user_id'],
        "name"    => $user['name'],
        "email"   => $user['email'],
        "role"    => $user['role'],
    ]
]);
