let watchId = null;
let sendIntervalId = null;

const startBtn = document.getElementById("startTrip");
const stopBtn = document.getElementById("stopTrip");
const statusText = document.getElementById("status");
const locationText = document.getElementById("location");

startBtn.addEventListener("click", () => {

    if (!navigator.geolocation) {
        statusText.innerText = "GPS not supported";
        return;
    }

    statusText.innerText = "Tracking started";
    startBtn.disabled = true;
    stopBtn.disabled = false;

    // Start GPS tracking
    watchId = navigator.geolocation.watchPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            locationText.innerText = `Latitude: ${lat}, Longitude: ${lon}`;
        },
        (error) => {
            statusText.innerText = "GPS error";
            console.error(error);
        },
        { enableHighAccuracy: true }
    );

    // Send GPS to server every 5 seconds
    sendIntervalId = setInterval(sendGPSData, 5000);
});

stopBtn.addEventListener("click", () => {

    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }

    if (sendIntervalId !== null) {
        clearInterval(sendIntervalId);
        sendIntervalId = null;
    }

    statusText.innerText = "Tracking stopped";
    startBtn.disabled = false;
    stopBtn.disabled = true;
});

function sendGPSData() {
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            statusText.innerText = "Sending GPS…";

            fetch("gps_receiver.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    latitude: lat,
                    longitude: lon
                })
            })
            .then(res => res.text())
            .then(data => console.log(data))
            .catch(err => {
                statusText.innerText = "GPS error";
                console.error(err);
            });
        },
        () => {
            statusText.innerText = "GPS error";
        }
    );
}

