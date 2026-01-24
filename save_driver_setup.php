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

// Insert or update driver record in `drivers` table
$driver_id = (int)$driver_id;
$vehicle_number_esc = mysqli_real_escape_string($conn, $vehicle_number);

$query = "INSERT INTO drivers (id, route_id, vehicle_number, lat, lng, speed, last_gps_time, status) VALUES ($driver_id, $route_id, '$vehicle_number_esc', 0, 0, 0, NOW(), 'OFFLINE') ON DUPLICATE KEY UPDATE route_id = $route_id, vehicle_number = '$vehicle_number_esc', last_gps_time = NOW()";

if (mysqli_query($conn, $query)) {
    echo json_encode([
        "success" => true,
        "message" => "Driver setup saved successfully",
        "driver_id" => $driver_id
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to save setup: " . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>