<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$input = get_json_input();

$name     = trim($input['name'] ?? '');
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$phone    = trim($input['phone_number'] ?? '');
$role     = $input['role'] ?? 'tenant';        // tenant | owner
$gender   = $input['gender'] ?? null;
$lifestyle    = $input['lifestyle'] ?? null;
$institution  = $input['institution'] ?? null;
$occupation   = $input['occupation'] ?? null;

if (!$name || !$email || !$password) {
    respond(["error" => "Name, email and password are required."], 400);
}

if (!in_array($role, ['tenant', 'owner'])) {
    $role = 'tenant';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(["error" => "Invalid email address."], 400);
}

if (strlen($password) < 6) {
    respond(["error" => "Password must be at least 6 characters."], 400);
}

// Check duplicate email
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    respond(["error" => "Email is already registered."], 409);
}

$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone_number, role, gender, lifestyle, institution, occupation)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$name, $email, $hashed, $phone, $role, $gender, $lifestyle, $institution, $occupation]);

respond([
    "message" => "Registration successful. You can now log in.",
    "user_id" => $pdo->lastInsertId()
], 201);
