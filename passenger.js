/* --- passenger.js: Unified Logic for all 3 Pages --- */
let map, busMarker, userMarker;

window.onload = () => {
    const isMapPage = document.getElementById('map');
    const isDestPage = document.getElementById('dest-section');
    const isLoginPage = document.getElementById('login-section');

    // 1. Logic for Ptracking.html
    if (isMapPage) {
        const params = new URLSearchParams(window.location.search);
        document.getElementById('live-fare').innerText = "Rs. " + (params.get('fare') || "0");
        document.getElementById('target-dest-display').innerText = "To: " + (params.get('dest') || "Unknown");
        initMap();
    } 
    // 2. Logic for Pdestination.html
    else if (isDestPage) {
        const user = JSON.parse(localStorage.getItem('passengerData'));
        if (!user) window.location.href = "index.html"; // Redirect to login if no data
        document.getElementById('user-display').innerText = user.name;
        document.getElementById('dest-section').classList.remove('hidden');
    }
};

/* --- FLOW 1: Login (index.html) -> Pdestination.html --- */
function loginUser() {
    const name = document.getElementById('name').value.trim();
    const contact = document.getElementById('contact').value.trim();
    const age = document.getElementById('age').value;

    if (!name || name.length < 3) return alert("Please enter a valid full name.");
    if (!/^[0-9]{10}$/.test(contact)) return alert("Contact must be exactly 10 digits.");
    if (isNaN(age) || age < 5 || age > 120) return alert("Please enter a realistic age (5-120).");
    
    localStorage.setItem('passengerData', JSON.stringify({ name, contact, age }));
    window.location.href = "Pdestination.html"; // Redirect to next step
}

/* --- FLOW 2: Destination (Pdestination.html) -> Ptracking.html --- */
function startTracking() {
    const sel = document.getElementById('destination');
    const fare = sel.options[sel.selectedIndex].dataset.fare;
    const dest = sel.value;

    // Pass data through URL to the Map page
    window.location.href = `Ptracking.html?fare=${fare}&dest=${encodeURIComponent(dest)}`;
}

/* --- FLOW 3: Map & Tracking (Ptracking.html) --- */
function initMap() {
    // Initialize map centered at Kathmandu
    map = L.map('map').setView([27.7172, 85.3240], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    // Marker for the Bus
    busMarker = L.marker([0, 0]).addTo(map).bindPopup("Live Bus Location");

    // Track Passenger's phone location
    navigator.geolocation.watchPosition(pos => {
        const loc = [pos.coords.latitude, pos.coords.longitude];
        if (!userMarker) {
            userMarker = L.marker(loc, {title: "You"}).addTo(map).bindPopup("You are here").openPopup();
            map.setView(loc);
        }
        userMarker.setLatLng(loc);
    });

    setInterval(fetchBusData, 5000);
    fetchBusData();
}

async function fetchBusData() {
    try {
        const res = await fetch("get_bus.php", { 
            method: "POST", 
            headers: { "Content-Type": "application/x-www-form-urlencoded" }, 
            body: "route_id=1" 
        });
        const d = await res.json();
        if (d.success) {
            const busPos = [parseFloat(d.latitude), parseFloat(d.longitude)];
            busMarker.setLatLng(busPos);
            document.getElementById('live-speed').innerText = d.speed || 0;
            
            const badge = document.getElementById('status-badge');
            badge.innerText = d.is_offline ? "OFFLINE" : "LIVE";
            badge.style.background = d.is_offline ? "#ef4444" : "#10b981";
        }
    } catch (e) { console.log("Connection to get_bus.php failed..."); }
}