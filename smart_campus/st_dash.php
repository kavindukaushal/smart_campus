<?php session_start(); include 'db_connect.php'; 

// Check if the student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id']; // Assuming student_id is stored in session after login

// Process reservation form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resource_id'])) {
    $resource_id = $_POST['resource_id'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    // Validate inputs
    if (empty($resource_id) || empty($start_time) || empty($end_time)) {
        $reservation_error = "All fields are required";
    } elseif ($start_time >= $end_time) {
        $reservation_error = "End time must be after start time";
    } else {
        // Insert the reservation into the database
        $insert_query = "INSERT INTO reservation_requests (student_id, resource_id, start_time, end_time, status) 
                         VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiss", $student_id, $resource_id, $start_time, $end_time);
        
        if ($stmt->execute()) {
            $reservation_success = "Reservation request submitted successfully!";
        } else {
            $reservation_error = "Error submitting reservation: " . $conn->error;
        }
    }
}

// Fetch student details including registered course
$student_query = "SELECT id, first_name, last_name, email, registered_course, batch_id, birth_date, gender, username FROM student WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

// Ensure the registered_course exists and is correct
$registered_course = $student['registered_course']; // Get the registered course
if (empty($registered_course)) {
    echo json_encode(['error' => 'No registered course found for this student.']);
    exit();
}

// Log the registered_course value for debugging
error_log("Registered course ID: " . $registered_course);

$class_schedule_query = "
    SELECT 
        c.id AS class_id,
        c.course_id AS class_course_id,
        c.schedule_date,
        c.start_time,
        c.end_time
    FROM class_schedules c
    WHERE c.course_id = ? ";
 
$stmt = $conn->prepare($class_schedule_query);
$stmt->bind_param("i", $registered_course);
$stmt->execute();
$class_result = $stmt->get_result();

// Check if any class schedules are found
if ($class_result->num_rows === 0) {
    echo json_encode(['error' => 'No class schedules found for this course.']);
    exit();
}

$class_schedules = [];
while ($row = $class_result->fetch_assoc()) {
    // Add class schedules to the array
    $class_schedules[] = [
        'id' => 'class_' . $row['class_id'],
        'title' => 'Class for Course ' . $row['class_course_id'],
        'start' => $row['schedule_date'] . 'T' . $row['start_time'], // Ensure ISO format for FullCalendar
        'end' => $row['schedule_date'] . 'T' . $row['end_time'], // Ensure ISO format for FullCalendar
        'backgroundColor' => '#007bff', // Blue for class schedules
        'borderColor' => '#007bff',
        'className' => 'event-type-class',
        'description' => 'Class Schedule: Course ' . $row['class_course_id']
    ];
}
$unread_count = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS unread_count 
    FROM chat_messages 
    WHERE student_id = '{$_SESSION['student_id']}' AND sender_type = 'lecturer' AND is_read = 0
"))['unread_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@3.10.2/dist/fullcalendar.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.10.2/dist/fullcalendar.min.js"></script>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Base styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0F0E47;
            padding: 15px 30px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
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
            margin: 0;
            padding: 0;
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
            transition: all 0.3s ease-in-out;
        }
        
        nav ul li a:hover {
            background: #505081;
            transform: translateY(-3px);
        }
        
        /* Main Layout - Three Column */
        .main-container {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            max-width: 1600px;
            margin: 0 auto;
            gap: 20px;
        }
        
        /* Column styles */
        .column {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .column:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .sidebar-column {
            flex: 1;
            max-width: 22%;
        }
        
        .reservations-column {
            flex: 1.5;
            max-width: 31%;
        }
        
        .calendar-column {
            flex: 2;
            max-width: 47%;
        }
        
        /* Button styles */
        .button-container {
            margin: 15px 0;
        }
        
        .button-container button {
            padding: 12px 20px;
            margin-bottom: 10px;
            background-color: #0F0E47;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 6px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .button-container button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        /* Headings */
        h2, h3 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
        }
        
        .column-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .column-header i {
            margin-right: 10px;
            color: #0F0E47;
            font-size: 24px;
        }
        
        /* Calendar */
        #calendar {
            width: 100%;
            height: auto;
        }
        
        .fc-day {
            height: 80px !important;
        }
        
        .fc-event {
            padding: 6px 8px !important;
            font-size: 14px !important;
            border-radius: 4px !important;
            margin-bottom: 3px !important;
            cursor: pointer !important;
            font-weight: bold !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Make today's date more visible */
        .fc-today {
            background-color: #f0f8ff !important;
            border: 2px solid #0F0E47 !important;
        }
        
        /* Make month title larger */
        .fc-center h2 {
            font-size: 24px !important;
        }
        
        /* Hide the header right buttons (day, week, month) */
        .fc-right {
            display: none !important;
        }
        
        /* Event legend */
        .event-legend {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            display: flex;
            justify-content: center;
        }
        
        .legend-item {
            margin: 0 15px;
            display: flex;
            align-items: center;
            font-weight: bold;
        }
        
        .event-indicator {
            display: inline-block;
            width: 15px;
            height: 15px;
            margin-right: 8px;
            border-radius: 50%;
        }
        
        .event-type-event {
            background-color: #FF5733;
        }
        
        .event-type-class {
            background-color: #3498DB;
        }
        
        /* Reservation Form */
        .reservation-form {
            margin-top: 20px;
            padding: 20px;
            background: #e9ecef;
            border-radius: 10px;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .reservation-form input,
        .reservation-form select,
        .reservation-form button {
            padding: 12px;
            margin: 10px 0;
            width: 100%;
            border-radius: 6px;
            border: 1px solid #ccc;
            transition: all 0.3s ease;
        }
        
        .reservation-form input:focus,
        .reservation-form select:focus {
            border-color: #0F0E47;
            box-shadow: 0 0 0 3px rgba(15,14,71,0.1);
            outline: none;
        }
        
        .reservation-form button {
            background-color: #0F0E47;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }
        
        .reservation-form button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        /* Alert messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        /* Reservation table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background-color: #0F0E47;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        table tr:hover {
            background-color: #f5f5f5;
        }
        
        /* Status badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }
        
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Badge for unread messages */
        .badge {
            background-color: red;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85em;
            display: inline-block;
            margin-left: 10px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        /* Event Popup */
        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.25);
            z-index: 1000;
            max-width: 500px;
            width: 90%;
        }
        
        .popup h2 {
            margin-top: 0;
            color: #0F0E47;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        .popup button {
            padding: 12px 24px;
            background-color: #0F0E47;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 20px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .popup button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        #eventDetails {
            line-height: 1.8;
            font-size: 16px;
        }
        
        /* Welcome section with avatar */
        .welcome-section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #0F0E47;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .welcome-text {
            flex-grow: 1;
        }
        
        .welcome-text h2 {
            margin: 0;
            padding: 0;
            border: none;
            font-size: 20px;
        }
        
        .welcome-text p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }

        /* Dark Mode CSS Variables */
:root {
    /* Light Theme Variables (Default) */
    --bg-color: #f8f9fa;
    --header-bg: #0F0E47;
    --header-text: white;
    --subheader-text: #8686AC;
    --primary-text: #2c3e50;
    --secondary-text: #666;
    --column-bg: white;
    --border-color: #eee;
    --hover-bg: #f5f5f5;
    --form-bg: #e9ecef;
    --form-border: #ccc;
    --today-bg: #f0f8ff;
    --legend-bg: #f9f9f9;
    --box-shadow: rgba(0, 0, 0, 0.08);
    --hover-shadow: rgba(0, 0, 0, 0.12);
    --popup-shadow: rgba(0, 0, 0, 0.25);
    --form-shadow: rgba(0, 0, 0, 0.05);
}

/* Dark Theme Variables */
[data-theme="dark"] {
    --bg-color: #121212;
    --header-bg: #0a0a2e;
    --header-text: #f0f0f0;
    --subheader-text: #a0a0d0;
    --primary-text: #e0e0e0;
    --secondary-text: #b0b0b0;
    --column-bg: #1e1e2e;
    --border-color: #333;
    --hover-bg: #252535;
    --form-bg: #2a2a3a;
    --form-border: #444;
    --today-bg: #1a1a4f;
    --legend-bg: #252535;
    --box-shadow: rgba(0, 0, 0, 0.3);
    --hover-shadow: rgba(0, 0, 0, 0.4);
    --popup-shadow: rgba(0, 0, 0, 0.5);
    --form-shadow: rgba(0, 0, 0, 0.2);
}

/* Apply variables to elements */
body {
    background-color: var(--bg-color);
    color: var(--primary-text);
    transition: all 0.3s ease;
}

header {
    background: var(--header-bg);
}

header .text h1 {
    color: var(--header-text);
}

header .text p {
    color: var(--subheader-text);
}

nav ul li a {
    color: var(--header-text);
}

.column {
    background: var(--column-bg);
    box-shadow: 0 5px 15px var(--box-shadow);
}

.column:hover {
    box-shadow: 0 8px 20px var(--hover-shadow);
}

h2, h3 {
    color: var(--primary-text);
    border-bottom: 2px solid var(--border-color);
}

.column-header i {
    color: var(--header-bg);
}

/* Calendar Styles */
.fc-day {
    background-color: var(--column-bg) !important;
}

.fc-day-header {
    background-color: var(--header-bg) !important;
    color: var(--header-text) !important;
}

.fc-unthemed th, 
.fc-unthemed td, 
.fc-unthemed thead, 
.fc-unthemed tbody, 
.fc-unthemed .fc-divider, 
.fc-unthemed .fc-row, 
.fc-unthemed .fc-content, 
.fc-unthemed .fc-popover, 
.fc-unthemed .fc-scroll-table, 
.fc-unthemed .fc-scrolling-area, 
.fc-unthemed .fc-list-table, 
.fc-unthemed .fc-list-view, 
.fc-unthemed .fc-list-heading td {
    border-color: var(--border-color) !important;
}

.fc-unthemed .fc-divider, 
.fc-unthemed .fc-popover .fc-header, 
.fc-unthemed .fc-list-heading td {
    background: var(--form-bg) !important;
}

.fc-today {
    background-color: var(--today-bg) !important;
    border: 2px solid var(--header-bg) !important;
}

/* Event Legend */
.event-legend {
    background: var(--legend-bg);
}

/* Reservation Form */
.reservation-form {
    background: var(--form-bg);
    box-shadow: inset 0 2px 5px var(--form-shadow);
}

.reservation-form input,
.reservation-form select {
    background-color: var(--column-bg);
    color: var(--primary-text);
    border: 1px solid var(--form-border);
}

/* Table styles */
table th {
    background-color: var(--header-bg);
}

table td {
    color: var(--primary-text);
}

table tr:hover {
    background-color: var(--hover-bg);
}

/* Welcome section */
.welcome-text p {
    color: var(--secondary-text);
}

/* Popup */
.popup {
    background: var(--column-bg);
    box-shadow: 0 5px 25px var(--popup-shadow);
}

.popup h2 {
    color: var(--primary-text);
    border-bottom: 2px solid var(--border-color);
}

/* Toggle button styling */
.theme-toggle {
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 20px;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.theme-toggle:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

/* Hide moon or sun based on current theme */
[data-theme="light"] .moon-icon {
    display: inline-block;
}

[data-theme="light"] .sun-icon {
    display: none;
}

[data-theme="dark"] .moon-icon {
    display: none;
}

[data-theme="dark"] .sun-icon {
    display: inline-block;
}

.reservation-form input[type="datetime-local"] {
        width: 100%; /* Adjusts the input field to match the container size */
        max-width: 300px; /* Optional: limits the maximum width of the fields */
        box-sizing: border-box; /* Ensures padding is included in width */
    }

    .reservation-form label {
        display: block; /* Keeps labels on separate lines for better layout */
        margin-bottom: 5px;
    }

    .reservation-form div {
        margin-bottom: 15px; /* Adds spacing between form groups */
    }
    </style>
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
                <li><a href="index.php">Logout</a></li>
                <li><a href="#" id="themeToggle">☀🌙</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Layout - Three Column -->
    <div class="main-container">
        <!-- Column 1: Sidebar -->
        <div class="column sidebar-column">
            <!-- Welcome Section with Avatar -->
            <div class="welcome-section">
                <div class="avatar">
                    <?php echo substr($student['first_name'], 0, 1); ?>
                </div>
                <div class="welcome-text">
                    <h2>Welcome, <?php echo $student['first_name']; ?>!</h2>
                    <p><?php echo $student['email']; ?></p>
                </div>
            </div>
            
            <!-- Navigation Buttons -->
            <div class="button-container">
                <button onclick="location.href='st_view_profile.php'">View Profile</button>
                <button onclick="location.href='view_files.php'">View Files</button>
                <button onclick="location.href='messages.php'">
                    Messages
                    <?php if ($unread_count > 0): ?>
                        <span class="badge"><?= $unread_count ?> New</span>
                    <?php endif; ?>
                </button>
                <button onclick="location.href='view_mail.php'">View Mail</button>
            </div>
            
            <!-- Alerts Section -->
            <?php if (isset($reservation_success)): ?>
                <div class="alert alert-success"><?php echo $reservation_success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($reservation_error)): ?>
                <div class="alert alert-danger"><?php echo $reservation_error; ?></div>
            <?php endif; ?>
            
            <!-- Reservation Form -->
            <div class="reservation-form">
                <div class="column-header">
                    <h3>Reserve a Resource</h3>
                </div>
                <form method="POST" action="">
                    <label for="resource_id">Resource</label>
                    <select name="resource_id" id="resource_id" required>
                        <option value="">Select Resource</option>
                        <?php
                        // Fetch available resources
                        $resources_query = "SELECT id, resource_name FROM resources WHERE status = 'available'";
                        $resources_result = $conn->query($resources_query);
                        while ($resource = $resources_result->fetch_assoc()) {
                            echo "<option value='{$resource['id']}'>{$resource['resource_name']}</option>";
                        }
                        ?>
                    </select>
                    
                    <label for="start_time">Start Time</label>
                    <input type="datetime-local" id="start_time" name="start_time" required>
                    
                    <label for="end_time">End Time</label>
                    <input type="datetime-local" id="end_time" name="end_time" required>
                    
                    <button type="submit">Submit Reservation</button>
                </form>
            </div>
        </div>
        
        <!-- Column 2: Reservations Table -->
        <div class="column reservations-column">
            <div class="column-header">
                <h2>Your Reservation Status</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Resource</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch all reservations made by the student
                    $reservation_query = "SELECT r.resource_name, rr.start_time, rr.end_time, rr.status
                                          FROM reservation_requests rr
                                          JOIN resources r ON rr.resource_id = r.id
                                          WHERE rr.student_id = ?";
                    $stmt = $conn->prepare($reservation_query);
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $status_class = "status-" . strtolower($row['status']);
                            
                            echo "<tr>
                                    <td>" . $row['resource_name'] . "</td>
                                    <td>" . date('M j, g:i A', strtotime($row['start_time'])) . "</td>
                                    <td>" . date('M j, g:i A', strtotime($row['end_time'])) . "</td>
                                    <td><span class='status-badge " . $status_class . "'>" . ucfirst($row['status']) . "</span></td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align: center;'>No reservations found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Column 3: Calendar -->
        <div class="column calendar-column">
            <div class="column-header">
                <h2>Event Calendar</h2>
            </div>
            <div id="calendar"></div>
            
            <!-- Legend for event types -->
            <div class="event-legend">
                <div class="legend-item">
                    <span class="event-indicator event-type-event"></span> Events
                </div>
                <div class="legend-item">
                    <span class="event-indicator event-type-class"></span> Class Schedules
                </div>
            </div>
        </div>
    </div>

    <!-- Event Details Pop-up -->
    <div id="eventPopup" class="popup" style="display:none;">
        <h2 id="popupTitle">Event Details</h2>
        <div id="eventDetails"></div>
        <button onclick="closePopup()">Close</button>
    </div>

<script>
$(document).ready(function() {
    // Fetch class schedules and events
    $.ajax({
        url: 'fetch_calendar_student_data.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log("Fetched data: ", data);

            // Format the class schedules for FullCalendar
            var formattedClassSchedules = data.class_schedules.map(function(event) {
                return {
                    id: event.id,
                    title: event.title,
                    start: event.start,
                    end: event.end,
                    backgroundColor: event.backgroundColor,
                    borderColor: event.borderColor,
                    description: event.description,
                    className: event.className
                };
            });

            // Format the events for FullCalendar
            var formattedEvents = data.events.map(function(event) {
                return {
                    id: event.id,
                    title: event.title,
                    start: event.start,
                    end: event.end,
                    backgroundColor: event.backgroundColor,
                    borderColor: event.borderColor,
                    description: event.description,
                    className: event.className
                };
            });

            // Combine class schedules and events into one array
            var combinedEvents = formattedClassSchedules.concat(formattedEvents);

            // Initialize FullCalendar with class schedules and events
            $('#calendar').fullCalendar({
                events: combinedEvents,
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                defaultView: 'month',
                height: 650,
                contentHeight: 600,
                eventLimit: true,
                eventLimitText: 'more',

                // Customize event rendering
                eventRender: function(event, element) {
                    element.find('.fc-title').text(event.title);
                    element.find('.fc-time').remove();
                },

                eventClick: function(calEvent, jsEvent, view) {
                    // Set popup content
                    var detailsHtml = 
                        "<strong>Title:</strong> " + calEvent.title + "<br>" +
                        "<strong>Date:</strong> " + moment(calEvent.start).format('dddd, MMMM D, YYYY') + "<br>" +
                        "<strong>Time:</strong> " + moment(calEvent.start).format('h:mm A') + " - " + moment(calEvent.end).format('h:mm A') + "<br>" +
                        "<strong>Description:</strong><br>" + 
                        (calEvent.description ? calEvent.description : 'No description available'); 

                    // Update the pop-up content
                    document.getElementById("eventDetails").innerHTML = detailsHtml;
                    document.getElementById("eventPopup").style.display = "block";
                }
            });
        },
        error: function(xhr, status, error) {
            console.error("Error fetching calendar data:", error);
            alert('Error loading calendar data. Please try again later.');
        }
    });
});

// Close the pop-up
function closePopup() {
    document.getElementById("eventPopup").style.display = "none";
}

document.addEventListener('DOMContentLoaded', function() {
    // Check for saved theme preference or use prefer-color-scheme
    const savedTheme = localStorage.getItem('theme');
    
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    } else {
        // Check if user prefers dark mode
        const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
        
        if (prefersDarkScheme.matches) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
        }
    }

    // Update toggle button to reflect current theme
    updateToggleButton();
    
    // Set up the theme toggle button
    const themeToggle = document.getElementById('themeToggle');
    
    themeToggle.addEventListener('click', function() {
        // Toggle theme
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        // Update HTML attribute
        document.documentElement.setAttribute('data-theme', newTheme);
        
        // Save preference to localStorage
        localStorage.setItem('theme', newTheme);
        
        // Update toggle button text
        updateToggleButton();
    });
});

// Updates the toggle button icon based on current theme
function updateToggleButton() {
    const themeToggle = document.getElementById('themeToggle');
    const currentTheme = document.documentElement.getAttribute('data-theme');
    
    // Replace text with proper icons
    themeToggle.innerHTML = `
        <span class="sun-icon">☀</span>
        <span class="moon-icon">🌙</span>
    `;
}

// Add FullCalendar theme observer to update after theme changes
const themeObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'data-theme') {
            // Refresh FullCalendar if it exists
            if ($('#calendar').length && $('#calendar').fullCalendar) {
                $('#calendar').fullCalendar('render');
                
                // Update FullCalendar colors based on theme
                const currentTheme = document.documentElement.getAttribute('data-theme');
                
                if (currentTheme === 'dark') {
                    $('.fc-unthemed th').css('background-color', 'var(--header-bg)');
                    $('.fc-unthemed th').css('color', 'var(--header-text)');
                    $('.fc-unthemed td').css('color', 'var(--primary-text)');
                } else {
                    $('.fc-unthemed th').css('background-color', '');
                    $('.fc-unthemed th').css('color', '');
                    $('.fc-unthemed td').css('color', '');
                }
            }
        }
    });
});

// Start observing the document with the configured parameters
themeObserver.observe(document.documentElement, { attributes: true });

</script>

</body>
</html>