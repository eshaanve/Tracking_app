let map, busMarker, userMarker;

window.onload = () => {
    const user = JSON.parse(localStorage.getItem('passengerData'));
    if (user) showDestSection(user.name);
};

function loginUser() {
    const name = document.getElementById('name').value;
    const contact = document.getElementById('contact').value;
    const age = parseInt(document.getElementById('age').value);
    const condition = document.getElementById('condition').value;

    // 1. Check if required fields are empty
    if (!name || !contact) {
        return alert("Please fill in Name and Contact Number.");
    }

    // 2. Validate Age (Should not be negative)
    if (age < 0) {
        return alert("Age cannot be a negative number.");
    }

    // 3. Validate Contact (Must be exactly 10 digits)
    const phonePattern = /^[0-9]{10}$/;
    if (!phonePattern.test(contact)) {
        return alert("Contact number must be exactly 10 digits.");
    }

    // 4. Success: Save and Redirect
    const user = { name, contact, age, condition };
    localStorage.setItem('passengerData', JSON.stringify(user));
    
    // Redirecting to the separate destination page as requested
    window.location.href = "Pdestination.html";
}

function showDestSection(name) {
    document.getElementById('user-display').innerText = name;
    document.getElementById('login-section').classList.add('hidden');
    document.getElementById('dest-section').classList.remove('hidden');
}

function startTracking() {
    const sel = document.getElementById('destination');
    document.getElementById('fare-amt').innerText = sel.options[sel.selectedIndex].dataset.fare;
    document.getElementById('dest-section').classList.add('hidden');
    document.getElementById('tracking-section').classList.remove('hidden');
    initMap();
}

function initMap() {
    map = L.map('map').setView([0, 0], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    
    // Static marker initialized at 0,0; updated by fetchBusData
    busMarker = L.marker([0, 0]).addTo(map).bindPopup("Bus Location");

    navigator.geolocation.watchPosition(pos => {
        const loc = [pos.coords.latitude, pos.coords.longitude];
        if (!userMarker) {
            userMarker = L.marker(loc).addTo(map).bindPopup("You");
            map.setView(loc);
        }
        userMarker.setLatLng(loc);
    });

    setInterval(fetchBusData, 5000);
    fetchBusData();
}

async function fetchBusData() {
    try {
        const res = await fetch('get_bus.php');
        const d = await res.json();
        
        console.log("Real DB Update:", d); // Log live data to console
        
        if (d.latitude) {
            const pos = [parseFloat(d.latitude), parseFloat(d.longitude)];
            busMarker.setLatLng(pos);
            document.getElementById('bus-num').innerText = d.bus_no;
            document.getElementById('driver-info').innerText = d.driver;
            document.getElementById('seats').innerText = d.seats + " Seats Left";
            document.getElementById('eta').innerText = d.eta + " mins";
            document.getElementById('status').innerText = d.is_offline ? "OFFLINE" : "LIVE";
            document.getElementById('status').style.color = d.is_offline ? "red" : "green";
        }
    } catch (e) { console.error("API Error:", e); }
}
