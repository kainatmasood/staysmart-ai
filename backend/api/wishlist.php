<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get user wishlist
        if(isset($_GET['user_id'])) {
            $query = "SELECT w.*, p.property_name, p.location, p.price_monthly, p.image_url 
                      FROM wishlist w 
                      JOIN properties p ON w.property_id = p.property_id 
                      WHERE w.user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $_GET['user_id']);
            $stmt->execute();
            $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => true, 'data' => $wishlist]);
        }
        break;
        
    case 'POST':
        // Add to wishlist
        $data = json_decode(file_get_contents("php://input"));
        
        // Check if already exists
        $checkQuery = "SELECT * FROM wishlist WHERE user_id = :user_id AND property_id = :property_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(":user_id", $data->user_id);
        $checkStmt->bindParam(":property_id", $data->property_id);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() > 0) {
            echo json_encode(['status' => false, 'message' => 'Already in wishlist']);
            exit();
        }
        
        $query = "INSERT INTO wishlist (user_id, property_id) VALUES (:user_id, :property_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $data->user_id);
        $stmt->bindParam(":property_id", $data->property_id);
        
        if($stmt->execute()) {
            echo json_encode(['status' => true, 'message' => 'Added to wishlist']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to add']);
        }
        break;
        
    case 'DELETE':
        // Remove from wishlist
        parse_str(file_get_contents("php://input"), $data);
        $query = "DELETE FROM wishlist WHERE user_id = :user_id AND property_id = :property_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $data['user_id']);
        $stmt->bindParam(":property_id", $data['property_id']);
        
        if($stmt->execute()) {
            echo json_encode(['status' => true, 'message' => 'Removed from wishlist']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to remove']);
        }
        break;
}
?>