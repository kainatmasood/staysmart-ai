<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

$budget = isset($data->budget) ? $data->budget : 50000;
$city = isset($data->city) ? $data->city : '';
$property_type = isset($data->property_type) ? $data->property_type : '';
$bedrooms = isset($data->bedrooms) ? $data->bedrooms : 1;

// Query properties
$query = "SELECT * FROM properties WHERE is_available = 1";
$params = [];

if($city) {
    $query .= " AND location LIKE :city";
    $params[':city'] = "%$city%";
}
if($property_type) {
    $query .= " AND property_type = :type";
    $params[':type'] = $property_type;
}
if($bedrooms) {
    $query .= " AND bedrooms >= :beds";
    $params[':beds'] = $bedrooms;
}

$stmt = $db->prepare($query);
foreach($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate AI match scores
$recommendations = [];
foreach($properties as $property) {
    $match_score = 0;
    
    // Budget match (40%)
    if($property['price_monthly'] <= $budget) {
        $budget_score = 1 - ($property['price_monthly'] / $budget);
        $match_score += $budget_score * 40;
    } else {
        $match_score += max(0, (1 - ($property['price_monthly'] - $budget) / $budget)) * 30;
    }
    
    // Location match (20%)
    if($city && stripos($property['location'], $city) !== false) {
        $match_score += 20;
    } else {
        $match_score += 10;
    }
    
    // Bedrooms match (15%)
    if($property['bedrooms'] >= $bedrooms) {
        $match_score += 15;
    } else {
        $match_score += ($property['bedrooms'] / $bedrooms) * 10;
    }
    
    // Property type match (15%)
    if($property_type && $property['property_type'] == $property_type) {
        $match_score += 15;
    } else {
        $match_score += 5;
    }
    
    // Random factor for variety (10%)
    $match_score += rand(0, 10);
    
    $match_level = $match_score >= 80 ? "Excellent Match" : ($match_score >= 60 ? "Good Match" : "Potential Match");
    
    $recommendations[] = array_merge($property, [
        'match_score' => round($match_score),
        'match_level' => $match_level
    ]);
}

// Sort by match score
usort($recommendations, function($a, $b) {
    return $b['match_score'] - $a['match_score'];
});

echo json_encode(['status' => true, 'recommendations' => array_slice($recommendations, 0, 8)]);
?>