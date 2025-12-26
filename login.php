<?php
header("Content-Type: application/json");

include "db.php";

if (!isset($_POST['phone'])) {
    echo json_encode([
        "success" => false,
        "message" => "Phone number not provided"
    ]);
    exit;
}

$phone = $_POST['phone'];

$query = "SELECT id, driver_name FROM drivers WHERE phone = '$phone'";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $driver = mysqli_fetch_assoc($result);

    echo json_encode([
    "success" => true,
    "driver_id" => $driver['id'],
    "driver_name" => $driver['driver_name']
]);

} else {
    echo json_encode([
        "success" => false,
        "message" => "Driver not found"
    ]);
}
?>
