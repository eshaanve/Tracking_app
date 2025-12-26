// Get HTML elements
const latDisplay = document.getElementById("lat_id");
const lngDisplay = document.getElementById("lng_id");
const statusDisplay = document.getElementById("status_id");

// Check if GPS is supported
if (!navigator.geolocation) {
    statusDisplay.innerText = "GPS not supported by this browser";
} else {
    statusDisplay.innerText = "Waiting for GPS permission...";

    // Watch GPS position
    navigator.geolocation.watchPosition(
        successFunction,
        errorFunction,
        { enableHighAccuracy: true }
    );
}

// If GPS access is allowed
function successFunction(position) {
    latDisplay.innerText = position.coords.latitude;
    lngDisplay.innerText = position.coords.longitude;

    statusDisplay.innerText = "GPS access allowed";
}

// If GPS error occurs
function errorFunction(error) {

    if (error.code === error.PERMISSION_DENIED) {
        statusDisplay.innerText = "Error: Permission denied";
    }
    else if (error.code === error.POSITION_UNAVAILABLE) {
        statusDisplay.innerText = "Error: Location unavailable";
    }
    else {
        statusDisplay.innerText = "Error: GPS error occurred";
    }
}
