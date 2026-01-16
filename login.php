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

$phone = trim($_POST['phone']);
error_log("Login attempt: phone=$phone");

$query = "SELECT id, driver_name FROM drivers WHERE phone = '$phone'";
error_log("Query: $query");

$result = mysqli_query($conn, $query);

if (!$result) {
    error_log("Query failed: " . mysqli_error($conn));
    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);
    exit;
}

if (mysqli_num_rows($result) > 0) {
    $driver = mysqli_fetch_assoc($result);
    error_log("Driver found: id=" . $driver['id'] . ", name=" . $driver['driver_name']);

    echo json_encode([
        "success" => true,
        "driver_id" => $driver['id'],
        "driver_name" => $driver['driver_name']
    ]);
} else {
    error_log("No driver found for phone: $phone");
    echo json_encode([
        "success" => false,
        "message" => "Driver not found"
    ]);
}

mysqli_close($conn);
?>
