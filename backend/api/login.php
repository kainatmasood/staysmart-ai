<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email) && !empty($data->password)) {
    
    $query = "SELECT user_id, name, email, password, role, phone, budget, lifestyle FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(password_verify($data->password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            
            echo json_encode(array(
                'status' => true,
                'message' => 'Login successful',
                'data' => array(
                    'user_id' => $user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'phone' => $user['phone'],
                    'budget' => $user['budget'],
                    'lifestyle' => $user['lifestyle']
                )
            ));
        } else {
            echo json_encode(array(
                'status' => false, 
                'message' => 'Invalid password'
            ));
        }
    } else {
        echo json_encode(array(
            'status' => false, 
            'message' => 'User not found. Please register first.'
        ));
    }
} else {
    echo json_encode(array(
        'status' => false, 
        'message' => 'Email and password required'
    ));
}
?>