<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    respond(["logged_in" => false]);
}

respond([
    "logged_in" => true,
    "user" => [
        "user_id" => $_SESSION['user_id'],
        "name"    => $_SESSION['name'],
        "email"   => $_SESSION['email'],
        "role"    => $_SESSION['role'],
    ]
]);
