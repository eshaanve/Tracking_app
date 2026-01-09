<?php
header("Content-Type: application/json");

include "db.php";

if (!isset($_POST['name']) || !isset($_POST['contact'])) {
    echo json_encode([
        "success" => false,
        "message" => "Name and contact are required"
    ]);
    exit;
}

$name = trim($_POST['name']);
$contact = trim($_POST['contact']);

// Basic validation
if (strlen($name) < 3) {
    echo json_encode([
        "success" => false,
        "message" => "Name must be at least 3 characters"
    ]);
    exit;
}

if (!preg_match('/^[0-9]{10}$/', $contact)) {
    echo json_encode([
        "success" => false,
        "message" => "Contact must be exactly 10 digits"
    ]);
    exit;
}

$query = "SELECT * FROM passengers WHERE name = '$name' AND contact = '$contact'";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $passenger = mysqli_fetch_assoc($result);
    echo json_encode([
        "success" => true,
        "passenger" => $passenger
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Passenger not found. Please sign up first."
    ]);
}

mysqli_close($conn);
?>