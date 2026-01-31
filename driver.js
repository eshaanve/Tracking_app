// Driver JS with login, setup, and tracking

let driverData = null;

window.onload = () => {
    const isLoginPage = document.getElementById('login-section');
    const isSetupPage = document.getElementById('setup-section');
    const isMainPage = document.getElementById('start_btn');

    const storedDriver = localStorage.getItem('driverData');
    if (storedDriver) {
        try {
            driverData = JSON.parse(storedDriver);
        } catch (e) {
            localStorage.removeItem('driverData');
        }
    }

    if (isLoginPage && driverData) window.location.href = "driver_setup.html";
    if (isSetupPage) {
        if (!driverData) window.location.href = "driver.html";
        document.getElementById('driver-name').innerText = driverData.driver_name;
        loadRoutes();
    }
    if (isMainPage) {
        if (!driverData) window.location.href = "driver.html";
        document.getElementById('driver-name').innerText = driverData.driver_name;
        document.getElementById('route-name').innerText = driverData.route_name || 'Unknown';
        document.getElementById('vehicle-number').innerText = driverData.vehicle_number;
        initGPS();
    }
};

window.loginDriver = function() {
    const phone = document.getElementById('phone').value.trim();
    console.log('Sending phone:', phone);
    if (!/^[0-9]{10}$/.test(phone)) return alert("Phone must be exactly 10 digits.");

    fetch('login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `phone=${phone}`
    })
    .then(res => res.json())
    .then(data => {
        console.log('Login response:', data);
        if (data.success) {
            driverData = data;
            localStorage.setItem('driverData', JSON.stringify(driverData));
            window.location.href = 'driver_setup.html';
        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error logging in.');
    });
};

window.loadRoutes = function() {
    fetch('get_routes.php')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('route');
            select.innerHTML = '<option value="">Select Route</option>';
            data.routes.forEach(route => {
                select.innerHTML += `<option value="${route.id}">${route.route_name}</option>`;
            });
        }
    });
};

window.saveSetup = function() {
    const routeId = document.getElementById('route').value;
    const vehicle = document.getElementById('vehicle').value.trim();
    if (!routeId || !vehicle) return alert("Please select a route and enter vehicle number.");

    fetch('save_driver_setup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `driver_id=${driverData.driver_id}&route_id=${routeId}&vehicle_number=${vehicle}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            driverData.route_id = routeId;
            driverData.vehicle_number = vehicle;
            driverData.bus_id = data.bus_id;
            // Get route name
            const routeSelect = document.getElementById('route');
            const selectedOption = routeSelect.options[routeSelect.selectedIndex];
            driverData.route_name = selectedOption.text;
            localStorage.setItem('driverData', JSON.stringify(driverData));
            window.location.href = 'driver_main.html';
        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error saving setup.');
    });
};

window.logout = function() {
    localStorage.removeItem('driverData');
    driverData = null;
    window.location.href = 'driver.html';
};

// GPS functions
const latDisplay = document.getElementById("lat_id");
const lngDisplay = document.getElementById("lng_id");
const statusDisplay = document.getElementById("status_id");

function initGPS() {
    if (!navigator.geolocation) {
        if (statusDisplay) statusDisplay.innerText = "GPS not supported by this browser";
    } else {
        if (statusDisplay) statusDisplay.innerText = "Waiting for GPS permission...";
        navigator.geolocation.watchPosition(
            successFunction,
            errorFunction,
            { enableHighAccuracy: true }
        );
    }
}

function successFunction(position) {

    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;
    const speed = position.coords.speed || 0;

    // STORE FOR DATABASE
    lastCoords = {
        latitude: latitude,
        longitude: longitude,
        speed: speed
    };

    if (latDisplay) latDisplay.innerText = latitude;
    if (lngDisplay) lngDisplay.innerText = longitude;
    if (statusDisplay) statusDisplay.innerText = "GPS access allowed";
}


function errorFunction(error) {
    if (statusDisplay) {
        if (error.code === error.PERMISSION_DENIED) {
            statusDisplay.innerText = "Error: Permission denied";
        } else if (error.code === error.POSITION_UNAVAILABLE) {
            statusDisplay.innerText = "Error: Location unavailable";
        } else {
            statusDisplay.innerText = "Error: GPS error occurred";
        }
    }
}

let gpsWatchId = null;
let gpsSendIntervalId = null;
let lastCoords = null;

function sendGpsUpdate() {
    if (!driverData || !lastCoords) return;

    const busId = driverData.bus_id || (driverData.vehicle_number && /^\d+$/.test(driverData.vehicle_number) ? driverData.vehicle_number : driverData.driver_id);
    const params = new URLSearchParams({
        driver_id: driverData.driver_id,
        bus_id: busId,
        route_id: driverData.route_id || "",
        latitude: lastCoords.latitude,
        longitude: lastCoords.longitude,
        speed: lastCoords.speed || 0
    });

    fetch('gps_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            console.warn('GPS update failed:', data.message);
            if (statusDisplay) statusDisplay.innerText = `GPS error: ${data.message}`;
        }
    })
    .catch(err => {
        console.error('GPS update error:', err);
        if (statusDisplay) statusDisplay.innerText = "GPS update error";
    });
}

// Start/Stop buttons
if (document.getElementById('start_btn')) {
    document.getElementById('start_btn').onclick = () => {
        if (!navigator.geolocation) {
            if (statusDisplay) statusDisplay.innerText = "GPS not supported by this browser";
            return;
        }

        if (statusDisplay) statusDisplay.innerText = "Trip Started";

        if (gpsWatchId === null) {
            gpsWatchId = navigator.geolocation.watchPosition(
                (position) => {
                    const { latitude, longitude, speed } = position.coords;
                    lastCoords = { latitude, longitude, speed: speed || 0 };
                    if (latDisplay) latDisplay.innerText = latitude;
                    if (lngDisplay) lngDisplay.innerText = longitude;
                    if (statusDisplay) statusDisplay.innerText = "GPS access allowed";
                },
                (error) => {
                    console.error(error);
                    if (statusDisplay) statusDisplay.innerText = "GPS error occurred";
                },
                { enableHighAccuracy: true }
            );
        }

        if (gpsSendIntervalId === null) {
            gpsSendIntervalId = setInterval(sendGpsUpdate, 5000);
        }
    };
}

if (document.getElementById('stop_btn')) {
    document.getElementById('stop_btn').onclick = () => {
        if (gpsWatchId !== null) {
            navigator.geolocation.clearWatch(gpsWatchId);
            gpsWatchId = null;
        }
        if (gpsSendIntervalId !== null) {
            clearInterval(gpsSendIntervalId);
            gpsSendIntervalId = null;
        }
        if (statusDisplay) statusDisplay.innerText = "Trip Stopped";
    };
}
