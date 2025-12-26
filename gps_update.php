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

$now = date("Y-m-d H:i:s");

// 3. Check if bus already exists
$check = "SELECT bus_id FROM bus_location WHERE bus_id = '$bus_id'";
$result = mysqli_query($conn, $check);

if (mysqli_num_rows($result) > 0) {
    // 4A. Update existing row
    $query = "
        UPDATE bus_location
        SET route_id = '$route_id',
            latitude = '$lat',
            longitude = '$lng',
            speed = '$speed',
            last_updated = '$now'
        WHERE bus_id = '$bus_id'
    ";
} else {
    // 4B. Insert new row
    $query = "
        INSERT INTO bus_location
        (bus_id, route_id, latitude, longitude, speed, last_updated)
        VALUES
        ('$bus_id', '$route_id', '$lat', '$lng', '$speed', '$now')
    ";
}

// 5. Execute query
if (mysqli_query($conn, $query)) {
    echo json_encode([
        "success" => true,
        "message" => "Location updated"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);
}
