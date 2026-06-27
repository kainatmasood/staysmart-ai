<?php
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateToken($length = 50) {
    return bin2hex(random_bytes($length));
}

function sendResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function calculateMonthlyExpenses($rent, $utilities = 5000, $internet = 2000, $maintenance = 3000) {
    return [
        'rent' => $rent,
        'electricity' => $utilities * 0.4,
        'water' => $utilities * 0.2,
        'internet' => $internet,
        'maintenance' => $maintenance,
        'total' => $rent + $utilities + $internet + $maintenance
    ];
}
?>