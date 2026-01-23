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

// Return drivers on this route that are ONLINE and have recent GPS (30s)
$sql = "
    SELECT
        id AS bus_id,
        lat AS latitude,
        lng AS longitude,
        speed,
        last_gps_time AS last_updated
    FROM drivers
    WHERE route_id = ?
      AND status = 'ONLINE'
      AND last_gps_time > (NOW() - INTERVAL 30 SECOND)
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database prepare error"]);
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $route_id);
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

// Fetch ordered route polyline points (id, route_id, lat, lng, seq)
$sqlPoints = "
    SELECT id, route_id, lat, lng, seq
    FROM route_points
    WHERE route_id = ?
    ORDER BY seq ASC
";
$stmtP = mysqli_prepare($conn, $sqlPoints);
if ($stmtP) {
    mysqli_stmt_bind_param($stmtP, 's', $route_id);
    mysqli_stmt_execute($stmtP);
    $resP = mysqli_stmt_get_result($stmtP);
    $route_points = [];
    while ($row = mysqli_fetch_assoc($resP)) {
        $route_points[] = $row;
    }
    mysqli_stmt_close($stmtP);
} else {
    $route_points = [];
}

// Fetch ordered bus stops (id, route_id, lat, lng, name, seq)
$sqlStops = "
    SELECT id, route_id, lat, lng, name, seq
    FROM bus_stops
    WHERE route_id = ?
    ORDER BY seq ASC
";
$stmtS = mysqli_prepare($conn, $sqlStops);
if ($stmtS) {
    mysqli_stmt_bind_param($stmtS, 's', $route_id);
    mysqli_stmt_execute($stmtS);
    $resS = mysqli_stmt_get_result($stmtS);
    $bus_stops = [];
    while ($row = mysqli_fetch_assoc($resS)) {
        $bus_stops[] = $row;
    }
    mysqli_stmt_close($stmtS);
} else {
    $bus_stops = [];
}

echo json_encode([
    "success" => true,
    "buses_count" => count($buses),
    "buses" => $buses,
    "route_points" => $route_points,
    "bus_stops" => $bus_stops
]);

