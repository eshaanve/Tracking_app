<?php
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