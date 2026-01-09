<?php
header("Content-Type: application/json");

include "db.php";

if (!isset($_POST['driver_id']) || !isset($_POST['route_id']) || !isset($_POST['vehicle_number'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields: driver_id, route_id, vehicle_number"
    ]);
    exit;
}

$driver_id = (int)$_POST['driver_id'];
$route_id = (int)$_POST['route_id'];
$vehicle_number = trim($_POST['vehicle_number']);

// Basic validation
if (empty($vehicle_number)) {
    echo json_encode([
        "success" => false,
        "message" => "Vehicle number cannot be empty"
    ]);
    exit;
}

// Insert into bus_location (assuming bus_id is vehicle_number)
$query = "INSERT INTO bus_location (bus_id, route_id, latitude, longitude, speed, last_updated) VALUES ('$vehicle_number', $route_id, 0, 0, 0, NOW()) ON DUPLICATE KEY UPDATE route_id = $route_id, last_updated = NOW()";

if (mysqli_query($conn, $query)) {
    echo json_encode([
        "success" => true,
        "message" => "Driver setup saved successfully",
        "bus_id" => $vehicle_number
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to save setup: " . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>