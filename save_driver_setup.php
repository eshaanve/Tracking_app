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

function column_exists($conn, $table, $column) {
    $table_esc = mysqli_real_escape_string($conn, $table);
    $column_esc = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table_esc` LIKE '$column_esc'");
    return $result && mysqli_num_rows($result) > 0;
}

function table_exists($conn, $table) {
    $table_esc = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table_esc'");
    return $result && mysqli_num_rows($result) > 0;
}

// Insert or update driver record in `drivers` table
$driver_id = (int)$driver_id;
$vehicle_number_esc = mysqli_real_escape_string($conn, $vehicle_number);

$errors = [];

// Update drivers table only if expected columns exist
if (table_exists($conn, 'drivers')) {
    $has_route_id = column_exists($conn, 'drivers', 'route_id');
    $has_vehicle_number = column_exists($conn, 'drivers', 'vehicle_number');
    $has_lat = column_exists($conn, 'drivers', 'lat');
    $has_lng = column_exists($conn, 'drivers', 'lng');
    $has_speed = column_exists($conn, 'drivers', 'speed');
    $has_last_gps_time = column_exists($conn, 'drivers', 'last_gps_time');
    $has_status = column_exists($conn, 'drivers', 'status');

    if ($has_route_id && $has_vehicle_number) {
        $columns = "id, route_id, vehicle_number";
        $values = "$driver_id, $route_id, '$vehicle_number_esc'";
        $updates = "route_id = $route_id, vehicle_number = '$vehicle_number_esc'";

        if ($has_lat) {
            $columns .= ", lat";
            $values .= ", 0";
        }
        if ($has_lng) {
            $columns .= ", lng";
            $values .= ", 0";
        }
        if ($has_speed) {
            $columns .= ", speed";
            $values .= ", 0";
        }
        if ($has_last_gps_time) {
            $columns .= ", last_gps_time";
            $values .= ", NOW()";
            $updates .= ", last_gps_time = NOW()";
        }
        if ($has_status) {
            $columns .= ", status";
            $values .= ", 'OFFLINE'";
        }

        $query = "INSERT INTO drivers ($columns) VALUES ($values) ON DUPLICATE KEY UPDATE $updates";
        if (!mysqli_query($conn, $query)) {
            $errors[] = "drivers: " . mysqli_error($conn);
        }
    }
}

// Update bus_location table if present
if (table_exists($conn, 'bus_location')) {
    if (!preg_match('/^\d+$/', $vehicle_number)) {
        $errors[] = "Vehicle number must be numeric to save bus location";
    } else {
        $bus_id = (int)$vehicle_number;
        $query_bus = "INSERT INTO bus_location (bus_id, route_id, latitude, longitude, speed, last_updated) VALUES ($bus_id, $route_id, 0, 0, 0, NOW()) ON DUPLICATE KEY UPDATE route_id = $route_id, last_updated = NOW()";
        if (!mysqli_query($conn, $query_bus)) {
            $errors[] = "bus_location: " . mysqli_error($conn);
        }
    }
}

if (count($errors) === 0) {
    echo json_encode([
        "success" => true,
        "message" => "Driver setup saved successfully",
        "driver_id" => $driver_id,
        "bus_id" => isset($bus_id) ? $bus_id : null
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to save setup: " . implode("; ", $errors)
    ]);
}

mysqli_close($conn);
?>