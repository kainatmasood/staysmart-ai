<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    // Get property price
    $rent_query = "SELECT price_monthly, property_name, location FROM properties WHERE property_id = :property_id";
    $rent_stmt = $db->prepare($rent_query);
    $rent_stmt->bindParam(":property_id", $data->property_id);
    $rent_stmt->execute();
    $property = $rent_stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_amount = $property['price_monthly'] * $data->duration_months;
    
    $query = "INSERT INTO bookings (user_id, property_id, property_name, location, check_in, check_out, duration_months, total_amount, status) 
              VALUES (:user_id, :property_id, :property_name, :location, :check_in, :check_out, :duration, :total, 'confirmed')";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":user_id", $data->user_id);
    $stmt->bindParam(":property_id", $data->property_id);
    $stmt->bindParam(":property_name", $property['property_name']);
    $stmt->bindParam(":location", $property['location']);
    $stmt->bindParam(":check_in", $data->check_in);
    $stmt->bindParam(":check_out", $data->check_out);
    $stmt->bindParam(":duration", $data->duration_months);
    $stmt->bindParam(":total", $total_amount);
    
    if($stmt->execute()) {
        echo json_encode(['status' => true, 'message' => 'Booking confirmed!', 'booking_id' => $db->lastInsertId(), 'total' => $total_amount]);
    } else {
        echo json_encode(['status' => false, 'message' => 'Booking failed']);
    }
}

if($method == 'GET' && isset($_GET['user_id'])) {
    $query = "SELECT * FROM bookings WHERE user_id = :user_id ORDER BY booking_date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $_GET['user_id']);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => true, 'data' => $bookings]);
}
?>