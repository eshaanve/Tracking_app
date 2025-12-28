<?php
header("Content-Type: application/json");

include __DIR__ . "/db.php";

// Check route_id
if (!isset($_POST['route_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "route_id missing"
    ]);
    exit;
}

$route_id = $_POST['route_id'];

// Fetch latest bus on this route
$query = "
    SELECT
        bus_id,
        latitude,
        longitude,
        speed,
        last_updated
    FROM bus_location
    WHERE route_id = '$route_id'
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode([
        "success" => false,
        "message" => "No bus found on this route"
    ]);
    exit;
}

$bus = mysqli_fetch_assoc($result);

// Check offline (15 seconds)
$last = strtotime($bus['last_updated']);
$now  = time();

$offline = ($now - $last > 15);

echo json_encode([
    "success" => true,
    "bus_id" => $bus['bus_id'],
    "latitude" => $bus['latitude'],
    "longitude" => $bus['longitude'],
    "speed" => $bus['speed'],
    "last_updated" => $bus['last_updated'],
    "offline" => $offline
]);

