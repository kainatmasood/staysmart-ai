<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['property_id'])) {
            $query = "SELECT * FROM properties WHERE property_id = :property_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":property_id", $_GET['property_id']);
            $stmt->execute();
            $property = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['status' => true, 'data' => $property]);
        } else {
            $query = "SELECT * FROM properties WHERE is_available = 1 ORDER BY created_at DESC";
            $params = [];
            
            if(isset($_GET['location']) && !empty($_GET['location'])) {
                $query = "SELECT * FROM properties WHERE location LIKE :location AND is_available = 1";
                $params[':location'] = "%{$_GET['location']}%";
            }
            if(isset($_GET['max_price']) && !empty($_GET['max_price'])) {
                $query .= (strpos($query, 'WHERE') ? ' AND' : ' WHERE') . " price_monthly <= :max_price";
                $params[':max_price'] = $_GET['max_price'];
            }
            
            $stmt = $db->prepare($query);
            foreach($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => true, 'data' => $properties]);
        }
        break;
}
?>