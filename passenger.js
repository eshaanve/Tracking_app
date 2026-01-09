/* --- passenger.js: Final Version with Dual Tracking & Boarding Sync --- */
let map, busMarker, userMarker;

window.onload = () => {
    const isMapPage = document.getElementById('map');
    const isDestPage = document.getElementById('dest-section');
    const isChoicePage = document.getElementById('choice-section');
    const userData = localStorage.getItem('passengerData');
    let user = null;
    if (userData) {
        try {
            user = JSON.parse(userData);
        } catch (e) {
            console.error('Error parsing passengerData:', e);
            localStorage.removeItem('passengerData'); // Clear corrupted data
        }
    }

    if (isChoicePage && user) window.location.href = "Pdestination.html";

    if (isMapPage) {
        const params = new URLSearchParams(window.location.search);
        document.getElementById('live-fare').innerText = "Rs. " + (params.get('fare') || "0");
        document.getElementById('target-dest-display').innerText = "To: " + (params.get('dest') || "Unknown");
        initMap();
    } else if (isDestPage) {
        if (!user) window.location.href = "passenger.html";
        document.getElementById('user-display').innerText = user.name;
        document.getElementById('dest-section').classList.remove('hidden');
    }
};

window.showLogin = function() {
    console.log('showLogin called');
    document.getElementById('choice-section').classList.add('hidden');
    document.getElementById('login-section').classList.remove('hidden');
    document.getElementById('signup-section').classList.add('hidden');
};

window.showSignUp = function() {
    console.log('showSignUp called');
    document.getElementById('choice-section').classList.add('hidden');
    document.getElementById('login-section').classList.add('hidden');
    document.getElementById('signup-section').classList.remove('hidden');
};

window.backToChoice = function() {
    console.log('backToChoice called');
    document.getElementById('choice-section').classList.remove('hidden');
    document.getElementById('login-section').classList.add('hidden');
    document.getElementById('signup-section').classList.add('hidden');
};

window.loginUser = function(mode) {
    console.log('loginUser called with mode:', mode);
    if (mode === 'login') {
        const name = document.getElementById('login-name').value.trim();
        const contact = document.getElementById('login-contact').value.trim();
        if (!name || name.length < 3) return alert("Please enter a valid full name.");
        if (!/^[0-9]{10}$/.test(contact)) return alert("Contact must be exactly 10 digits.");

        fetch('login_passenger.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `name=${encodeURIComponent(name)}&contact=${encodeURIComponent(contact)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                localStorage.setItem('passengerData', JSON.stringify(data.passenger));
                window.location.href = 'Pdestination.html';
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error logging in. Please try again.');
        });
    } else if (mode === 'signup') {
        const name = document.getElementById('name').value.trim();
        const contact = document.getElementById('contact').value.trim();
        const age = document.getElementById('age').value;
        const condition = document.getElementById('condition').value;
       if (!name || name.length < 3) return alert("Please enter a valid full name.");
        if (!/^[0-9]{10}$/.test(contact)) return alert("Contact must be exactly 10 digits.");
        if (isNaN(age) || age < 5 || age > 120) return alert("Please enter a realistic age (5-120).");

        fetch('save_passenger.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `name=${encodeURIComponent(name)}&contact=${encodeURIComponent(contact)}&age=${age}&condition=${encodeURIComponent(condition)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                localStorage.setItem('passengerData', JSON.stringify({ name, contact, age, condition, id: data.passenger_id }));
                window.location.href = 'Pdestination.html';
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error signing up. Please try again.');
        });
    }
};

window.startTracking = function() {
    const sel = document.getElementById('destination');
    window.location.href = `Ptracking.html?fare=${sel.options[sel.selectedIndex].dataset.fare}&dest=${encodeURIComponent(sel.value)}`;
};

function initMap() {
    map = L.map('map').setView([27.7172, 85.3240], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    busMarker = L.marker([0, 0], {icon: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/3448/3448339.png', iconSize: [40, 40]})}).addTo(map).bindPopup("Bus Location");

    // Live Passenger Location (Moves as you walk/ride)
    navigator.geolocation.watchPosition(pos => {
        const loc = [pos.coords.latitude, pos.coords.longitude];
        if (!userMarker) { 
            userMarker = L.marker(loc).addTo(map).bindPopup("You are here").openPopup(); 
            map.setView(loc); 
        } else {
            userMarker.setLatLng(loc);
        }
    }, err => console.error(err), { enableHighAccuracy: true });

    setInterval(fetchBusData, 5000);
}

window.logout = function() {
    localStorage.removeItem('passengerData');
    window.location.href = 'passenger.html';
};

async function fetchBusData() {
    try {
        const res = await fetch("get_bus.php", { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: "route_id=1" });
        const d = await res.json();
        if (d.success) {
            const busPos = L.latLng(parseFloat(d.latitude), parseFloat(d.longitude));
            busMarker.setLatLng(busPos);
            document.getElementById('live-speed').innerText = d.speed || 0;

            if (userMarker) {
                const dist = userMarker.getLatLng().distanceTo(busPos);
                // When distance < 20m, markers will overlap perfectly
              document.getElementById('eta-display').innerText =
  dist < 30
    ? "On Board"
    : (d.speed > 0
        ? Math.round((dist / ((d.speed ?? 0) * (5/18))) / 60) + " mins"
        : "Stopped");

            }
        }
    } catch (e) { console.log("Sync Error"); }
}