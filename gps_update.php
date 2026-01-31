<?php
header("Content-Type: application/json");

include __DIR__ . "/db.php";

// 1. Validate required POST fields
$required = ['driver_id','route_id','latitude','longitude','speed'];

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
$driver_id = $_POST['driver_id'] ?? null;
$bus_id   = $_POST['bus_id'] ?? null;
$route_id = $_POST['route_id'];
$lat      = $_POST['latitude'];
$lng      = $_POST['longitude'];
$speed    = $_POST['speed'];

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

// 3. Update drivers table: set lat,lng,last_gps_time and status=ONLINE
$errors = [];

if (!table_exists($conn, 'drivers')) {
    $errors[] = "drivers table missing";
} else {
    $has_lat = column_exists($conn, 'drivers', 'lat');
    $has_lng = column_exists($conn, 'drivers', 'lng');
    $has_speed = column_exists($conn, 'drivers', 'speed');
    $has_last_gps_time = column_exists($conn, 'drivers', 'last_gps_time');
    $has_status = column_exists($conn, 'drivers', 'status');
    $has_route_id = column_exists($conn, 'drivers', 'route_id');

    if (!$driver_id) {
        $errors[] = "driver_id missing";
    } else if ($has_lat && $has_lng) {
        $setParts = [];
        $params = [];
        $types = '';

        if ($has_route_id) {
            $setParts[] = "route_id = ?";
            $params[] = $route_id;
            $types .= 's';
        }
        $setParts[] = "lat = ?";
        $params[] = $lat;
        $types .= 'd';

        $setParts[] = "lng = ?";
        $params[] = $lng;
        $types .= 'd';

        if ($has_speed) {
            $setParts[] = "speed = ?";
            $params[] = $speed;
            $types .= 'd';
        }
        if ($has_last_gps_time) {
            $setParts[] = "last_gps_time = NOW()";
        }
        if ($has_status) {
            $setParts[] = "status = 'ONLINE'";
        }

        $sql = "UPDATE drivers SET " . implode(', ', $setParts) . " WHERE id = ?";
        $params[] = $driver_id;
        $types .= 'i';

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $errors[] = "drivers prepare error";
        } else {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (!mysqli_stmt_execute($stmt)) {
                $errors[] = "drivers execute error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $errors[] = "drivers table missing lat/lng columns";
    }
}

// 4. Update bus_location if table exists and bus_id is numeric
if (table_exists($conn, 'bus_location') && $bus_id !== null && $bus_id !== '') {
    if (!preg_match('/^\d+$/', (string)$bus_id)) {
        $errors[] = "bus_id must be numeric";
    } else {
        $bus_id_int = (int)$bus_id;
        $sqlBus = "INSERT INTO bus_location (bus_id, route_id, latitude, longitude, speed, last_updated) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE route_id = VALUES(route_id), latitude = VALUES(latitude), longitude = VALUES(longitude), speed = VALUES(speed), last_updated = NOW()";
        $stmtBus = mysqli_prepare($conn, $sqlBus);
        if (!$stmtBus) {
            $errors[] = "bus_location prepare error";
        } else {
            mysqli_stmt_bind_param($stmtBus, 'isddd', $bus_id_int, $route_id, $lat, $lng, $speed);
            if (!mysqli_stmt_execute($stmtBus)) {
                $errors[] = "bus_location execute error: " . mysqli_stmt_error($stmtBus);
            }
            mysqli_stmt_close($stmtBus);
        }
    }
}

if (count($errors) === 0) {
    echo json_encode(["success" => true, "message" => "Location updated"]);
} else {
    echo json_encode(["success" => false, "message" => implode("; ", $errors)]);
}
