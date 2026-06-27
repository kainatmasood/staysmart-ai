<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// Check if data is complete
if(!empty($data->name) && !empty($data->email) && !empty($data->password)) {
    
    // Check if email already exists
    $checkQuery = "SELECT user_id FROM users WHERE email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":email", $data->email);
    $checkStmt->execute();
    
    if($checkStmt->rowCount() > 0) {
        echo json_encode(array(
            "status" => false, 
            "message" => "Email already exists. Please use a different email."
        ));
        exit();
    }
    
    // Insert new user
    $query = "INSERT INTO users (name, email, password, phone, role, budget, lifestyle) 
              VALUES (:name, :email, :password, :phone, :role, :budget, :lifestyle)";
    
    $stmt = $db->prepare($query);
    
    // Hash password
    $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
    
    // Set default values
    $role = isset($data->role) ? $data->role : 'user';
    $budget = isset($data->budget) ? $data->budget : 0;
    $lifestyle = isset($data->lifestyle) ? $data->lifestyle : 'balanced';
    $phone = isset($data->phone) ? $data->phone : '';
    
    // Bind parameters
    $stmt->bindParam(":name", $data->name);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $hashed_password);
    $stmt->bindParam(":phone", $phone);
    $stmt->bindParam(":role", $role);
    $stmt->bindParam(":budget", $budget);
    $stmt->bindParam(":lifestyle", $lifestyle);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "status" => true, 
            "message" => "Registration successful! Please login.",
            "user_id" => $db->lastInsertId()
        ));
    } else {
        echo json_encode(array(
            "status" => false, 
            "message" => "Registration failed. Please try again."
        ));
    }
} else {
    echo json_encode(array(
        "status" => false, 
        "message" => "Please fill all required fields."
    ));
}
?>