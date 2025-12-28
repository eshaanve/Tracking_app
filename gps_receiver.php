<?php
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo "No data received";
    exit;
}

$latitude = $data['latitude'] ?? null;
$longitude = $data['longitude'] ?? null;

if ($latitude && $longitude) {
    // Later you can save this to DB
    echo "GPS received: $latitude , $longitude";
} else {
    echo "Invalid GPS data";
}
?>
