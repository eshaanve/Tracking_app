<?php
header("Content-Type: application/json");

include "db.php";

if (!isset($_POST['name']) || !isset($_POST['contact']) || !isset($_POST['age']) || !isset($_POST['condition'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields: name, contact, age, condition"
    ]);
    exit;
}

$name = trim($_POST['name']);
$contact = trim($_POST['contact']);
$age = (int)$_POST['age'];
$condition = trim($_POST['condition']);

// Basic validation (matching JS)
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

if ($age < 5 || $age > 120) {
    echo json_encode([
        "success" => false,
        "message" => "Age must be between 5 and 120"
    ]);
    exit;
}

// Log the attempt for debugging
error_log("Signup attempt: name=$name, contact=$contact, age=$age, condition=$condition");

// Insert into database
$query = "INSERT INTO passengers (name, contact, age, `condition`) VALUES ('$name', '$contact', $age, '$condition')";

if (mysqli_query($conn, $query)) {
    $passenger_id = mysqli_insert_id($conn);
    error_log("Signup successful: passenger_id=$passenger_id");
    echo json_encode([
        "success" => true,
        "message" => "Passenger data saved successfully",
        "passenger_id" => $passenger_id
    ]);
} else {
    $error = mysqli_error($conn);
    error_log("Signup failed: $error");
    echo json_encode([
        "success" => false,
        "message" => "Failed to save data: " . $error
    ]);
}

mysqli_close($conn);
?>