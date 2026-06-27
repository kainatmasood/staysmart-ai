<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

$session = require_role(['admin']);
$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';

switch ($method) {
    case 'GET':
        handleGet($pdo, $resource);
        break;
    case 'PUT':
        handlePut($pdo, $resource);
        break;
    case 'DELETE':
        handleDelete($pdo, $resource);
        break;
    default:
        respond(["error" => "Method not allowed"], 405);
}

function handleGet(PDO $pdo, string $resource): void {
    switch ($resource) {
        case 'users':
            $stmt = $pdo->query("SELECT user_id, name, email, phone_number, role, gender, lifestyle, institution, occupation, created_at FROM users ORDER BY created_at DESC");
            respond($stmt->fetchAll());

        case 'properties':
            $stmt = $pdo->query("SELECT p.*, u.name AS owner_name FROM properties p
                                  JOIN users u ON p.owner_id = u.user_id
                                  ORDER BY p.created_at DESC");
            respond($stmt->fetchAll());

        case 'bookings':
            $stmt = $pdo->query("SELECT b.*, p.property_name, p.city, u.name AS tenant_name, o.name AS owner_name
                                  FROM bookings b
                                  JOIN properties p ON b.property_id = p.property_id
                                  JOIN users u ON b.user_id = u.user_id
                                  JOIN users o ON p.owner_id = o.user_id
                                  ORDER BY b.created_at DESC");
            respond($stmt->fetchAll());

        case 'stats':
            $stats = [];
            $stats['total_users']      = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $stats['total_tenants']    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='tenant'")->fetchColumn();
            $stats['total_owners']     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='owner'")->fetchColumn();
            $stats['total_properties'] = (int)$pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
            $stats['active_properties'] = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status='active'")->fetchColumn();
            $stats['pending_properties'] = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status='pending'")->fetchColumn();
            $stats['total_bookings']   = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
            $stats['confirmed_bookings'] = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn();
            $stats['total_revenue']    = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status IN ('confirmed','completed')")->fetchColumn();
            $stats['total_roommate_requests'] = (int)$pdo->query("SELECT COUNT(*) FROM roommate_requests WHERE status='open'")->fetchColumn();

            // Revenue / bookings by city for a simple report chart
            $byCity = $pdo->query("SELECT p.city, COUNT(*) AS bookings, COALESCE(SUM(b.total_amount),0) AS revenue
                                    FROM bookings b JOIN properties p ON b.property_id = p.property_id
                                    GROUP BY p.city ORDER BY revenue DESC")->fetchAll();
            $stats['by_city'] = $byCity;

            respond($stats);

        default:
            respond(["error" => "Unknown resource. Use ?resource=users|properties|bookings|stats"], 400);
    }
}

/**
 * PUT /admin.php?resource=users&id=5  -> update user role
 * PUT /admin.php?resource=properties&id=5 -> update property status (approve/moderate)
 */
function handlePut(PDO $pdo, string $resource): void {
    if (empty($_GET['id'])) {
        respond(["error" => "id is required."], 400);
    }
    $input = get_json_input();

    if ($resource === 'users') {
        if (empty($input['role']) || !in_array($input['role'], ['tenant','owner','admin'])) {
            respond(["error" => "A valid role (tenant, owner, admin) is required."], 400);
        }
        $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?")->execute([$input['role'], $_GET['id']]);
        respond(["message" => "User role updated."]);
    }

    if ($resource === 'properties') {
        if (empty($input['status']) || !in_array($input['status'], ['active','pending','inactive'])) {
            respond(["error" => "A valid status (active, pending, inactive) is required."], 400);
        }
        $pdo->prepare("UPDATE properties SET status = ? WHERE property_id = ?")->execute([$input['status'], $_GET['id']]);
        respond(["message" => "Property status updated to '{$input['status']}'."]);
    }

    respond(["error" => "Unknown resource."], 400);
}

/**
 * DELETE /admin.php?resource=users&id=5
 * DELETE /admin.php?resource=properties&id=5
 */
function handleDelete(PDO $pdo, string $resource): void {
    if (empty($_GET['id'])) {
        respond(["error" => "id is required."], 400);
    }

    if ($resource === 'users') {
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$_GET['id']]);
        respond(["message" => "User deleted."]);
    }

    if ($resource === 'properties') {
        $pdo->prepare("DELETE FROM properties WHERE property_id = ?")->execute([$_GET['id']]);
        respond(["message" => "Property deleted."]);
    }

    respond(["error" => "Unknown resource."], 400);
}
