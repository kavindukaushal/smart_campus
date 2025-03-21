<?php
session_start();
include 'db_connect.php';

// Lecturer Profile Details
$lecturer_id = $_SESSION['lecturer_id'];

// Check if the upload success status is set in the session
$success = isset($_SESSION['upload_success']) ? $_SESSION['upload_success'] : null;

$lecturer_details = $conn->query("SELECT * FROM lecturer WHERE id = '$lecturer_id'")->fetch_assoc();

// Fetch counts for dashboard
$class_count = $conn->query("SELECT COUNT(*) AS total FROM class_schedules WHERE id = '$lecturer_id'")->fetch_assoc()['total'];
$event_count = $conn->query("SELECT COUNT(*) AS total FROM events WHERE id = '$lecturer_id'")->fetch_assoc()['total'];
$logs_result = $conn->query("SELECT * FROM security_logs ORDER BY timestamp DESC LIMIT 5");

$unread_count = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS unread_count 
    FROM chat_messages 
    WHERE lecturer_id = '{$_SESSION['lecturer_id']}' AND sender_type = 'student' AND is_read = 0
"))['unread_count'];
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
     <!-- Bootstrap CSS CDN -->
     <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.7/dist/sweetalert2.min.css" rel="stylesheet">
 
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

<!-- Main Layout -->
<div class="main-container">
    <!-- Dashboard (Left Side) --> 
    <div class="dashboard-container">
        <h2>Welcome, <?php echo $_SESSION['lecturer_username']?>!</h2>
        <div class="dashboard-grid">
            <div class="dashboard-section" onclick="window.location='lec_create_class.php';">
                <h3>Create Class</h3>
                <p>Total Classes: <?php echo $class_count; ?></p>
                <button>Create a Class</button>
            </div><br>

            <div class="dashboard-section" onclick="window.location='lec_create_event.php';">
                <h3>Create Event</h3>
                <p>Total Events: <?php echo $event_count; ?></p>
                <button>Create an Event</button>
            </div><br>

            <div class="dashboard-section" onclick="window.location='lec_view_profile.php';">
                <h3>View Profile</h3>
                <button>View Profile</button>
            </div><br>
            <div class="dashboard-section" onclick="window.location='view_mail.php';">
    <h3>View Mails</h3>
    <button>View Messages</button>
</div><br>
<div class="dashboard-section" onclick="window.location='lecturer_messages.php';" style="cursor: pointer;">
    <h3>📥 View Messages from Students</h3>
    <p>Check your inbox for student messages</p>
    <button>View Messages</button>
    <?php if ($unread_count > 0): ?>
        <span class="badge" style="background-color: red; color: white; padding: 5px 10px; border-radius: 10px; font-weight: bold;">
            <?= $unread_count ?> New
        </span>
    <?php endif; ?>
</div>



</form>

        </div>

    </div>
    <br>

    <!-- Today's Details Section (Between Sidebar & Calendar) -->
<div class="today-details">
    <!-- Today's Classes Box -->
    <div class="today-box today-classes">
        <h3>Today's Classes</h3>
        <ul id="todayClassesList">
            <!-- Classes will be dynamically inserted here -->
        </ul>
    </div>
    <br>

    <!-- Today's Events Box -->
    <div class="today-box today-events">
        <h3>Today's Events</h3>
        <ul id="todayEventsList">
            <!-- Events will be dynamically inserted here -->
        </ul>
    </div>
    
    <br>
    <form action="upload_file.php" method="POST" enctype="multipart/form-data">
    <label for="course">Select Course:</label>
    <select name="course_id" id="course">
        
        <?php
        include('db_connect.php');  // Include the database connection file

        // Fetch all courses from the database
        $sql = "SELECT id, course_name FROM courses";
        $result = $conn->query($sql);

        // Check if courses are available and populate dropdown
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<option value="' . $row['id'] . '">' . $row['course_name'] . '</option>';
            }
        } else {
            echo '<option value="">No courses available</option>';
        }
        ?>
    </select>

    <label for="file">Upload File:</label><br>
    <input type="file" name="file" id="file" accept=".pdf, .docx, .pptx" required><br>
    <br><br>
    <button type="submit">Upload File</button>
</form>


<!--Uploaded files table -->
<div id="uplodetable">
<?php 

$sql = "SELECT u.file_name, u.file_type, u.upload_time, c.course_name FROM uploads u
        JOIN courses c ON u.course_id = c.id
        WHERE u.lecturer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lecturer_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<table>";
echo "<tr><th>File Name</th><th>Course</th><th>Upload Time</th><th>Actions</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['file_name'] . "</td>";
    echo "<td>" . $row['course_name'] . "</td>";
    echo "<td>" . $row['upload_time'] . "</td>";
    echo "<td><a href='download.php?file=" . urlencode($row['file_name']) . "'>Download</a></td>";
    echo "</tr>";
}
echo "</table>";


?>
</div>

</div>

<br>
    <!-- Calendar (Right Side) -->
    <div class="calendar-container">
        <h2>Calendar</h2>
        <div id="calendar"></div>
    </div>
</div>


<?php
if ($success === 1) {
    // Display success message using SweetAlert2
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@10.16.7/dist/sweetalert2.all.min.js'></script>";
    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'File uploaded successfully!',
            text: 'Your file has been uploaded successfully.',
            timer: 3000,  // Show the pop-up for 3 seconds
            willClose: () => {
                window.location.href = 'lec_dash.php';  // Stay on the same page after pop-up
            }
        });
    </script>";
} elseif ($success === 0) {
    // Display failure message using SweetAlert2
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@10.16.7/dist/sweetalert2.all.min.js'></script>";
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Failed to upload file!',
            text: 'There was an issue uploading your file.',
            timer: 3000  // Show the pop-up for 3 seconds
        });
    </script>";
}

// After the pop-up is shown, reset the session variable to avoid showing the pop-up again
unset($_SESSION['upload_success']);


?>



<!-- Event Details Pop-up -->
<div id="eventPopup" class="popup" style="display:none;">
    <h2>Event Details</h2>
    <p id="eventDetails"></p>
    <button onclick="closePopup()">Close</button>
</div>

<script>
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
    // Apply saved theme from local storage
    const savedTheme = localStorage.getItem("theme") || "light";
    setTheme(savedTheme, false);

    // Toggle button event
    document.getElementById("themeToggle").addEventListener("click", function () {
        document.getElementById("themePopup").classList.add("show");
    });
});

// Function to set theme
function setTheme(theme, save = true) {
    if (theme === "dark") {
        document.body.classList.add("dark-mode");
    } else {
        document.body.classList.remove("dark-mode");
    }
    if (save) localStorage.setItem("theme", theme);
    closeThemePopup();
}

// Function to close theme popup
function closeThemePopup() {
    document.getElementById("themePopup").classList.remove("show");
}

/*......................................................................................................................*/ 
document.addEventListener("DOMContentLoaded", function () {
    // Load saved theme from local storage
    const savedTheme = localStorage.getItem("theme") || "light";
    setTheme(savedTheme, false);

    // Toggle Theme Button
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

/* Header */
header {
    display: flex;
    justify-content: space-between;
    background: #0F0E47;
    padding: 15px 30px;
    box-shadow: 0px 4px 8px rgba(255, 255, 255, 0.1);
    position: sticky;
    top: 0;
    z-index: 999;
}

.header-left {
    display: flex;
   
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

/* Main Layout */
.main-container {
    display: flex;
    justify-content: space-between;
    padding: 20px;
    animation: fadeInUp 0.5s ease-in-out;
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
/* Dashboard - Left Side */
.dashboard-container {
    
    flex-direction: column;
    gap: 20px;
    width: 500px;
    height: 100px;
}

.dashboard-section {
    background: #0F0E47;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    color: white;
    height: 180px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 50%;
    height: fit-content;
}

.dashboard-section h3 {
    font-size: 20px;
    margin-bottom: 10px;
}

.dashboard-section p {
    font-size: 16px;
    margin-bottom: 15px;
}

.dashboard-section button {
    padding: 10px 15px;
    background: #8686AC;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.dashboard-section button:hover {
    background: #272757;
}

.dashboard-section:hover {
    transform: translateY(-6px);
    box-shadow: 0px 8px 18px rgba(255, 255, 255, 0.2);
}

.dashboard-section::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 5px;
    background: linear-gradient(to right, #505081, #0F0E47);
    top: 0;
    left: 0;
    transform: scaleX(0);
    transition: transform 0.3s ease-in-out;
}

.dashboard-section:hover::before {
    transform: scaleX(1);
}

/* Form Styling */
form {
    background-color: #8686AC;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 500px;
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

form label {
    font-size: 16px;
    color: white;
}

form select, form input[type="file"] {
    padding: 10px;
    border: 1px solid #505081;
    border-radius: 5px;
    background-color: white;
    font-size: 14px;
}

form select {
    color: #505081;
}

form input[type="file"] {
    color: #505081;
}

form button {
    padding: 10px 15px;
    background-color: #0F0E47;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

form button:hover {
    background-color: #272757;
}

/* Success and Error Message */
.error {
    color: red;
    font-size: 14px;
}

.success {
    color: green;
    font-size: 14px;
}


/* Calendar */
.calendar-container {
    width: 700PX;
    height: 610px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease-in-out;
}

.calendar-container:hover {
    transform: translateY(-5px);
}
/* Security Table */
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

/* Mobile Responsive */
@media (max-width: 768px) {
    .main-container {
        flex-direction: column;
    }

    .dashboard-container, .calendar-container {
        width: 100%;
        margin-bottom: 20px;
    }
}

 

/*........................................................................................................................*/ 
/* Light Mode / Night Mode Toggle Button in Header */
#themeToggle {
    cursor: pointer;
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 18px;
    background: none;
    color: white;
    transition: color 0.3s ease-in-out;
}

#themeToggle:hover {
    color: #8686AC;
}

/* Night Mode */
body.dark-mode {
    background-color: #121212;
    color: #e0e0e0;
}

/* Header */
body.dark-mode header {
    background: linear-gradient(to right, #1c1c1c, #272757);
    box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.1);
}

/* Dashboard */
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

/* Calendar */
body.dark-mode .calendar-container {
    background: #222;
    color: white;
}

/* Today's Events & Classes */
body.dark-mode .today-classes {
    background: linear-gradient(to right, #2a2a2a, #505050);
}

body.dark-mode .today-events {
    background: linear-gradient(to right, #1c1c1c, #0F0E47);
}

/* Container for the Table */
#uplodetable {
    width: 100%;
    max-width: 1000px;
    margin: 20px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
}

/* Table Styling */
#uplodetable table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
}

#uplodetable th, #uplodetable td {
    padding: 12px;
    text-align: center;
    border: 1px solid #505081;
    color: #272757;
}

#uplodetable th {
    background-color: #0F0E47;
    color: white;
    font-size: 16px;
}

#uplodetable td {
    background-color: #f4f4f4;
    font-size: 14px;
}

#uplodetable td a {
    text-decoration: none;
    color: #0F0E47;
    padding: 5px 10px;
    background-color: #505081;
    border-radius: 5px;
    transition: background-color 0.3s ease, color 0.3s ease;
}

#uplodetable td a:hover {
    background-color: #272757;
    color: white;
}

/* Even Row Styling */
#uplodetable tr:nth-child(even) td {
    background-color: #e0e0e0;
}

/* Row Hover Effect */
#uplodetable tr:hover {
    background-color: #d6d6d6;
}

/* Responsive Table for Small Screens */
@media (max-width: 768px) {
    #uplodetable {
        padding: 10px;
    }

    #uplodetable table {
        font-size: 12px;
    }

    #uplodetable th, #uplodetable td {
        padding: 8px;
    }
}



</style>

<!-- Bootstrap JS and dependencies (jQuery, Popper) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>