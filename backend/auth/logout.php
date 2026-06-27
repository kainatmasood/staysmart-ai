<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$_SESSION = [];
session_destroy();

respond(["message" => "Logged out successfully."]);
