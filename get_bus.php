<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// Return drivers on this route that are ONLINE and have recent GPS (30s)
$sql = "
SELECT
    id AS bus_id,
    lat AS latitude,
    lng AS longitude,
    speed,
    last_gps_time AS last_updated
FROM drivers
WHERE lat != 0
AND lng != 0
AND status = 'ONLINE'
AND route_id = ?
AND last_gps_time > (NOW() - INTERVAL 30 SECOND)
";


$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database prepare error"]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $route_id);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => false, "message" => "Database execute error"]);
    mysqli_stmt_close($stmt);
    exit;
}


$res = mysqli_stmt_get_result($stmt);
$buses = [];
while ($row = mysqli_fetch_assoc($res)) {
    $buses[] = $row;
}
mysqli_stmt_close($stmt);

echo json_encode([
    "success" => true,
    "buses" => $buses
]);