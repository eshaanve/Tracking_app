<?php
<<<<<<< HEAD
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "bus_tracker");

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed"]));
}

// Fetches real data joined from drivers and bus_location tables
$sql = "SELECT b.latitude, b.longitude, b.seats, b.eta, b.last_updated, d.name as driver, d.bus_no 
        FROM bus_location b 
        JOIN drivers d ON b.driver_id = d.id 
        ORDER BY b.last_updated DESC LIMIT 1";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    // Logic: If no update in 15 seconds, mark as offline
    $data['is_offline'] = (time() - strtotime($data['last_updated']) > 15);
    echo json_encode($data);
} else {
    echo json_encode(["error" => "No active bus found"]);
}
?>
=======
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
>>>>>>> 02837de0d5aefd1e83b91c1aa22e634fcddfe39c
