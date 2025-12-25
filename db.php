<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "bus_tracker";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Database connection failed");
}
?>