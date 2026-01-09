<?php
header("Content-Type: application/json");

include "db.php";

$query = "SELECT id, route_name FROM routes";

$result = mysqli_query($conn, $query);

$routes = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $routes[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "routes" => $routes
]);

mysqli_close($conn);
?>