<?php
session_start();
include 'db_connect.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch counts for dashboard
$user_count = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$resource_count = $conn->query("SELECT COUNT(*) AS total FROM resources")->fetch_assoc()['total'];         
$event_count = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'];
$schedule_count = $conn->query("SELECT COUNT(*) AS total FROM class_schedules")->fetch_assoc()['total'];
$batch_count = $conn->query("SELECT COUNT(*) AS total FROM batch")->fetch_assoc()['total'];
$st_count = $conn->query("SELECT COUNT(*) AS total FROM student")->fetch_assoc()['total'];
$lec_count = $conn->query("SELECT COUNT(*) AS total FROM lecturer")->fetch_assoc()['total'];
$logs_result = $conn->query("SELECT * FROM security_logs ORDER BY timestamp DESC LIMIT 5");

// Fetch pending reservation requests
$reservation_query = "SELECT rr.id, s.first_name, s.last_name, r.resource_name, rr.start_time, rr.end_time
                      FROM reservation_requests rr
                      JOIN student s ON rr.student_id = s.id
                      JOIN resources r ON rr.resource_id = r.id
                      WHERE rr.status = 'pending'";
$reservation_result = $conn->query($reservation_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <!-- Add Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <!-- Other head content remains the same -->
</head>

<body>

<!-- Header -->
<header>
    <div class="header-left">
        <img src="images/logo2.png" alt="Smart Campus Logo" class="logo">
        <div class="text">
            <h1>Smart Campus</h1>
            <p>Management System</p>
        </div>
    </div>
    <nav>
        <ul>
            <li><a href="#" id="themeToggle">☀🌙</a></li>
            <li><a href="login.php">⬅ Back</a></li>
            <li><a href="index.php">Logout</a></li>
        </ul>
    </nav>
</header>

<!-- Main Layout - Reorganized to three columns -->
<div class="main-container">
    <!-- Dashboard Cards (Left Column) -->
    <div class="dashboard-container">
        <h2>Welcome, <?php echo $_SESSION['admin_username']; ?>!</h2>
        <div class="dashboard-single-column">
            <div class="dashboard-section" onclick="window.location='manage_users.php';">
                <h4>Add Users</h4>
                <p>Total Users: <?php echo $user_count; ?></p>
            </div>
            <div class="dashboard-section" onclick="window.location='manage_resources.php';">
                <h4>Resource Management</h4>
                <p>Total Resources: <?php echo $resource_count; ?></p>
            </div>
            <div class="dashboard-section" onclick="window.location='manage_events.php';">
                <h4>Event Management</h4>
                <p>Total Events: <?php echo $event_count; ?></p>
            </div>
            <div class="dashboard-section" onclick="window.location='allocate_resources.php';">
                <h4>Class Management</h4>
                <p>Total Scheduled Classes: <?php echo $schedule_count; ?></p>
            </div>
            <div class="dashboard-section" onclick="window.location='manage_batches.php';">
                <h4>Batch Management</h4>
                <p>Total Batches: <?php echo $batch_count; ?></p>
            </div>
            <div class="dashboard-section" onclick="window.location='st_reg.php';">
                <h4>Student Management</h4>
                <p>Total Students: <?php echo $st_count; ?></p>
            </div>
            <div class="dashboard-section" onclick="window.location='lec_reg.php';">
                <h4>Lecturer Management</h4>
                <p>Total Lecturers: <?php echo $lec_count; ?></p>
            </div>
            <div class="dashboard-section" onclick="window.location='generate_report.php';">
                <h4>Event Attendance Report</h4>
                <p>Generate a report based on event attendance</p>
            </div>
            <div class="dashboard-section" onclick="window.location='send_mail.php';">
                <h4>Send Mail</h4>
                <p>Send verification mail to Lecturers or Students</p>
            </div>
        </div>
    </div>
    
    <!-- Reservation Tables (Middle Column) -->
    <div class="reservation-container">
        <!-- Reservation Requests Section -->
        <div class="reservation-requests-container">
            <h2>Pending Resource Reservation Requests</h2>
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Resource</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $reservation_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
                        <td><?php echo $row['resource_name']; ?></td>
                        <td><?php echo $row['start_time']; ?></td>
                        <td><?php echo $row['end_time']; ?></td>
                        <td>Pending</td>
                        <td>
                            <a href="approve_reservation.php?id=<?php echo $row['id']; ?>&action=approve">Approve</a> | 
                            <a href="approve_reservation.php?id=<?php echo $row['id']; ?>&action=reject">Reject</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <h2>All Reservation Requests</h2>
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Resource</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch all pending reservation requests
                    $reservation_query = "SELECT rr.id, s.first_name, s.last_name, r.resource_name, rr.start_time, rr.end_time, rr.status
                                        FROM reservation_requests rr
                                        JOIN student s ON rr.student_id = s.id
                                        JOIN resources r ON rr.resource_id = r.id";
                    $reservation_result = $conn->query($reservation_query);

                    while ($row = $reservation_result->fetch_assoc()) {
                        echo "<tr>
                                <td>" . $row['first_name'] . " " . $row['last_name'] . "</td>
                                <td>" . $row['resource_name'] . "</td>
                                <td>" . $row['start_time'] . "</td>
                                <td>" . $row['end_time'] . "</td>
                                <td>" . ucfirst($row['status']) . "</td>
                                <td>
                                    <a href='approve_reservation.php?id=" . $row['id'] . "&action=approve'>Approve</a> | 
                                    <a href='approve_reservation.php?id=" . $row['id'] . "&action=reject'>Reject</a>
                                </td>
                            </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
  
    <!-- Calendar and FAQ Section (Right Column) -->
    <div class="right-side-container">
        <!-- Calendar Container -->
        <div class="calendar-container">
            <h2>Calendar</h2>
            <div id="calendar"></div>
        </div>
        
        <!-- FAQ Section with Bootstrap Accordion -->
        <div class="faq-container mt-4">
            <h3 class="text-center mb-4" style="color: #0F0E47;">Frequently Asked Questions</h3>
            
            <div class="accordion" id="faqAccordion" style="max-height: 450px; overflow-y: auto;">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            1. How do I register for a class?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            To register for a class, go to the "Class Scheduling" section on the dashboard, select the class, and click "Register".
                        </div>
                    </div>
                </div>

                <!-- Other FAQ items remain the same -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            2. How can I view upcoming events?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Upcoming events are displayed on the "Today's Events" section of the dashboard, or you can view them by accessing the "Event Calendar" page.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            3. How do I approve or reject student registrations?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            As an admin, you can manage student registrations by going to the "Student Management" section and approving or rejecting them from there.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            4. How do I manage lecturer profiles?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Lecturer profiles can be managed under the "Lecturer Management" tab, where you can add, edit, or remove lecturers.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
    <h2 class="accordion-header" id="headingFive">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
            5. How do I reset a student’s password?
        </button>
    </h2>
    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To reset a student's password, go to the "User Management" section, find the student, and click "Reset Password". A temporary password will be sent to their registered email.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingSix">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
            6. How can I update course details?
        </button>
    </h2>
    <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            You can update course details from the "Course Management" section by selecting a course and clicking the "Edit" button.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingSeven">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
            7. How do I send notifications to students?
        </button>
    </h2>
    <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            Notifications can be sent via the "Notifications" tab, where you can compose a message and send it to selected students or all students.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingEight">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
            8. How do I generate reports?
        </button>
    </h2>
    <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            Reports can be generated from the "Reports" section, where you can choose report types such as student performance, attendance, or financial reports.
        </div>
    </div>
</div>

            </div>
        </div>
    </div>
    
    <!-- Security & Compliance Table -->
    <div class="security-table-container">
        <h3>Security & Compliance</h3>
        <table class="security-table">
            <tr>
                <th>Admin ID</th>
                <th>Action</th>
                <th>Timestamp</th>
            </tr>
            <?php while ($log = $logs_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $log['admin_id']; ?></td>
                <td><?php echo $log['action']; ?></td>
                <td><?php echo $log['timestamp']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<!-- Event Pop-up and Scripts remain the same -->
<div id="eventPopup" class="popup" style="display:none;">
    <h2>Event Details</h2>
    <p id="eventDetails"></p>
    <button onclick="closePopup()">Close</button>
</div>

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// JavaScript remains the same
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: 'fetch_calendar_data.php',
        eventClick: function(info) {
            document.getElementById("eventDetails").innerHTML = `
                <strong>Title:</strong> ${info.event.title}<br>
                <strong>Start:</strong> ${info.event.start}<br>
                <strong>End:</strong> ${info.event.end || 'N/A'}
            `;
            document.getElementById("eventPopup").style.display = "block";
        }
    });
    calendar.render();
});

function closePopup() {
    document.getElementById("eventPopup").style.display = "none";
}

document.addEventListener("DOMContentLoaded", function () {
    // Apply saved theme from local storage
    const savedTheme = localStorage.getItem("theme") || "light";
    setTheme(savedTheme, false);

    // Toggle button event
    document.getElementById("themeToggle").addEventListener("click", function () {
        toggleTheme();
    });
});

// Function to Toggle Theme
function toggleTheme() {
    const currentTheme = document.body.classList.contains("dark-mode") ? "light" : "dark";
    setTheme(currentTheme);
}

// Function to Set Theme
function setTheme(theme, save = true) {
    if (theme === "dark") {
        document.body.classList.add("dark-mode");
    } else {
        document.body.classList.remove("dark-mode");
    }
    if (save) localStorage.setItem("theme", theme);
}
</script>

<style>
/* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

/* Modified Main Layout for 3-column design */
.main-container {
    display: grid;
    grid-template-columns: 0.8fr 1.2fr 1fr;
    gap: 15px;
    padding: 20px;
    animation: fadeInUp 0.5s ease-in-out;
}

/* Dashboard Container - Left Column */
.dashboard-container {
    width: 280px !important;
    grid-column: 1;
    width: 100%;
    padding-right: 10px;
}

/* Modified Dashboard Grid to be single column */
.dashboard-single-column {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

/* Dashboard Sections */
.dashboard-section {
    width: 250px !important;
    background: #0F0E47;
    color: white;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
    height: 110px !important;
}

/* Reservation Container - Middle Column */
.reservation-container {
    margin-left: -120px !important;
    grid-column: 2;
    width: 100%;
}

/* Reservation Requests Container */
.reservation-requests-container {
    width: 560px !important;
    padding: 15px;
    background-color: #f4f4f4;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

/* Right Side Container - Third Column */
.right-side-container {
    margin-left: -130px !important;
    grid-column: 3;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Security Table goes full width */
.security-table-container {
    grid-column: 1 / span 3;
    width: 100%;
    padding: 0 20px 20px 20px;
}

/* Other styling remains the same */
/* Header */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #0F0E47;
    padding: 15px 30px;
    box-shadow: 0px 4px 8px rgba(255, 255, 255, 0.1);
    position: sticky;
    top: 0;
    z-index: 999;
}

.header-left {
    display: flex;
    align-items: center;
}

.logo {
    width: 55px;
    margin-right: 15px;
    transition: transform 0.3s ease-in-out;
}

.logo:hover {
    transform: scale(1.1);
}

header .text h1 {
    font-size: 22px;
    color: white;
    margin: 0;
}

header .text p {
    font-size: 14px;
    color: #8686AC;
    margin: 0;
}

nav ul {
    list-style: none;
    display: flex;
}

nav ul li {
    margin-left: 25px;
}

nav ul li a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    padding: 10px 15px;
    border-radius: 5px;
    transition: background 0.3s ease-in-out;
}

nav ul li a:hover {
    background: #505081;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dashboard-section::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 5px;
    background: linear-gradient(to right, rgb(21, 21, 110), rgb(80, 91, 129));
    top: 0;
    left: 0;
    transform: scaleX(0);
    transition: transform 0.3s ease-in-out;
}

.dashboard-section:hover::before {
    transform: scaleX(1);
}

.dashboard-section:hover {
    transform: translateY(-6px);
    box-shadow: 0px 8px 18px rgba(255, 255, 255, 0.2);
}

.reservation-requests-container h2 {
    font-size: 22px;
    margin-bottom: 20px;
}

.reservation-requests-container table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.reservation-requests-container table th,
.reservation-requests-container table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: center;
}

.reservation-requests-container table th {
    background-color: #8686AC;
    color: white;
}

/* Calendar */
.calendar-container {
    width: 100%;
    height: auto;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease-in-out;
}

.calendar-container:hover {
    transform: translateY(-5px);
}

/* FAQ Container */
.faq-container {
    width: 100%;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.security-table {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
}

.security-table th, .security-table td {
    padding: 12px;
    border-bottom: 1px solid #8686AC;
    text-align: center;
}

.security-table th {
    background: #8686AC;
    color: white;
}

/* Popup */
.popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    color: black;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.4);
    z-index: 1000;
    text-align: center;
    opacity: 0;
    animation: fadeIn 0.3s ease-in-out forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translate(-50%, -55%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

/* Buttons */
button {
    padding: 10px 15px;
    background: #505081;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
}

button:hover {
    background: #8686AC;
}

/* Night Mode */
body.dark-mode {
    background-color: #1e1e1e;
    color: #e0e0e0;
}

body.dark-mode header {
    background: linear-gradient(to right, #1c1c1c, #272757);
    box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.1);
}

body.dark-mode .dashboard-container {
    background: #1e1e1e;
    box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.2);
}

body.dark-mode .dashboard-section {
    background: linear-gradient(to right, #2a2a2a, #505050);
    color: white;
}

body.dark-mode .dashboard-section:hover {
    box-shadow: 0px 6px 15px rgba(255, 255, 255, 0.3);
}

body.dark-mode .calendar-container {
    background: #222;
    color: white;
}

body.dark-mode .faq-container {
    background: #222 !important;
    color: #e0e0e0;
}

body.dark-mode .security-table {
    background: #1c1c1c;
    color: white;
}

body.dark-mode .reservation-requests-container {
    background: #1c1c1c;
    color: white; 
}

/* FAQ Text Color in Dark Mode */
body.dark-mode .text-center {
    color: #e0e0e0 !important;
}

/* Accordion Styles */
body.dark-mode .accordion-item {
    background-color: #2a2a2a;
    color: #e0e0e0;
    border-color: #444;
}

body.dark-mode .accordion-button {
    background-color: #2a2a2a;
    color: #e0e0e0;
}

body.dark-mode .accordion-button:not(.collapsed) {
    background-color: #0F0E47;
    color: #fff;
}

body.dark-mode .accordion-body {
    background-color: #333;
    color: #e0e0e0;
}

/* Media Queries for Responsiveness */
@media (max-width: 1200px) {
    .main-container {
        grid-template-columns: 1fr 1fr;
    }
    
    .dashboard-container {
        grid-column: 1;
    }
    
    .reservation-container {
        grid-column: 2;
    }
    
    .right-side-container {
        grid-column: 1 / span 2;
        grid-row: 2;
    }
    
    .security-table-container {
        grid-column: 1 / span 2;
        grid-row: 3;
    }
}

@media (max-width: 768px) {
    .main-container {
        grid-template-columns: 1fr;
    }
    
    .dashboard-container,
    .reservation-container,
    .right-side-container,
    .security-table-container {
        grid-column: 1;
    }
    
    header {
        flex-direction: column;
        padding: 10px;
    }
    
    .header-left {
        margin-bottom: 10px;
    }
    
    nav ul {
        flex-direction: column;
        align-items: center;
    }
    
    nav ul li {
        margin: 5px 0;
    }
}
</style>

</body>
</html>