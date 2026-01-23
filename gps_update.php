<?php
header("Content-Type: application/json");

include __DIR__ . "/db.php";

// 1. Validate required POST fields
$required = ['bus_id', 'route_id', 'latitude', 'longitude', 'speed'];

foreach ($required as $field) {
    if (!isset($_POST[$field])) {
        echo json_encode([
            "success" => false,
            "message" => "Missing field: $field"
        ]);
        exit;
    }
}

// 2. Get POST data
$bus_id   = $_POST['bus_id'];
$route_id = $_POST['route_id'];
$lat      = $_POST['latitude'];
$lng      = $_POST['longitude'];
$speed    = $_POST['speed'];

// 3. Update drivers table: set lat,lng,last_gps_time and status=ONLINE
$sql = "UPDATE drivers SET route_id = ?, lat = ?, lng = ?, speed = ?, last_gps_time = NOW(), status = 'ONLINE' WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database prepare error"]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'sddss', $route_id, $lat, $lng, $speed, $bus_id);
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => false, "message" => "Database execute error"]);
    mysqli_stmt_close($stmt);
    exit;
}

$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affected > 0) {
    echo json_encode(["success" => true, "message" => "Location updated"]);
} else {
    // If no row was updated, the driver id may be unknown
    echo json_encode(["success" => false, "message" => "Driver not found or no change"]);
}
