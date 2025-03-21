// Modal open and close functionality
function openModal() {
    document.getElementById("modal").style.display = "block";
}

function closeModal() {
    document.getElementById("modal").style.display = "none";
}

// Close modal when clicking outside the modal content
window.onclick = function(event) {
    if (event.target == document.getElementById("modal")) {
        closeModal();
    }
}


// Password show/hide functionality
function togglePassword() {
    var passwordField = document.getElementById("password");
    if (passwordField.type === "password") {
        passwordField.type = "text";
    } else {
        passwordField.type = "password";
    }
}

// Login Form Validation
function validateForm() {
    var username = document.getElementById("username").value;
    var password = document.getElementById("password").value;

    if (username.trim() === "" || password.trim() === "") {
        alert("Username and password cannot be empty.");
        return false;
    }
    return true;
}

// Function to toggle visibility of security logs
function toggleTable() {
    var table = document.getElementById('security-logs');
    table.style.display = table.style.display === 'none' ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: 'fetch_calendar_data.php',
        eventClick: function(info) {
            var eventId = info.event.id;
            if (eventId.startsWith("event_")) {
                window.location.href = "dashboard.php?id=" + eventId;
            } else if (eventId.startsWith("class_")) {
                window.location.href = "dashboard.php?id=" + eventId;
            }
        }
    });
    calendar.render();
});


function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const dashboardContainer = document.querySelector('.dashboard-container');
    
    sidebar.classList.toggle('collapsed');
    dashboardContainer.classList.toggle('collapsed');
}

document.addEventListener("DOMContentLoaded", function () {
    // Fetch today's classes
    fetch("fetch_today_classes.php")
        .then(response => response.json())
        .then(data => {
            const classList = document.getElementById("todayClassesList");
            if (data.length > 0) {
                data.forEach(cls => {
                    let li = document.createElement("li");
                    li.textContent = `${cls.course_name} - ${cls.start_time} to ${cls.end_time}`;
                    classList.appendChild(li);
                });
            } else {
                classList.innerHTML = "<li>No classes today</li>";
            }
        });

    // Fetch today's events
    fetch("fetch_today_events.php")
        .then(response => response.json())
        .then(data => {
            const eventList = document.getElementById("todayEventsList");
            if (data.length > 0) {
                data.forEach(event => {
                    let li = document.createElement("li");
                    li.textContent = `${event.title} - ${event.event_date}`;
                    eventList.appendChild(li);
                });
            } else {
                eventList.innerHTML = "<li>No events today</li>";
            }
        });
});



document.addEventListener("DOMContentLoaded", function () {
    // Load saved theme from local storage
    const savedTheme = localStorage.getItem("theme") || "light";
    setTheme(savedTheme, false);

    // Toggle button event for popup
    document.getElementById("themeToggle").addEventListener("click", function () {
        document.getElementById("themePopup").classList.add("show");
    });
});

// Function to Set Theme
function setTheme(theme, save = true) {
    if (theme === "dark") {
        document.body.classList.add("dark-mode");
    } else {
        document.body.classList.remove("dark-mode");
    }
    if (save) localStorage.setItem("theme", theme);
    closeThemePopup();
}

// Function to Close Theme Popup
function closeThemePopup() {
    document.getElementById("themePopup").classList.remove("show");
}

