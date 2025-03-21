<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ensure admin ID is set in session
$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
if (!$admin_id) {
    die("Error: Admin ID is missing. Please log in again.");
}

// Fetch Courses for Checkboxes
$course_query = "SELECT * FROM courses";
$course_result = $conn->query($course_query);
$courses = [];
while ($row = $course_result->fetch_assoc()) {
    $courses[$row['id']] = $row['course_name'];
}

// Handle Delete Event
if (isset($_GET['delete'])) {
    $event_id = $_GET['delete'];

    // Delete associated course entries first
    $stmt = $conn->prepare("DELETE FROM event_courses WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->close();

    // Delete the event
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_events.php");
    exit;
}

// Handle Edit Event
$edit_event = false;
if (isset($_GET['edit'])) {
    $event_id = $_GET['edit'];
    $edit_event = true;

    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event_data = $result->fetch_assoc();
    $stmt->close();

    // Fetch associated courses
    $stmt = $conn->prepare("SELECT course_id FROM event_courses WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $selected_courses = [];
    while ($row = $result->fetch_assoc()) {
        $selected_courses[] = $row['course_id'];
    }
    $stmt->close();
}

// Handle Create or Update Event
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $venue = $_POST['venue'];
    $selected_courses = isset($_POST['course_id']) ? $_POST['course_id'] : [];

    if (isset($_POST['edit_event'])) {
        $event_id = $_POST['id'];

        // Update event details
        $stmt = $conn->prepare("UPDATE events SET title=?, description=?, event_date=?, start_time=?, end_time=?, venue=? WHERE id=?");
        $stmt->bind_param("ssssssi", $title, $description, $event_date, $start_time, $end_time, $venue, $event_id);
        $stmt->execute();
        $stmt->close();

        // Delete previous course mappings
        $stmt = $conn->prepare("DELETE FROM event_courses WHERE event_id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new event
        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, start_time, end_time, venue, organizer_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $title, $description, $event_date, $start_time, $end_time, $venue, $admin_id);
        $stmt->execute();
        $event_id = $conn->insert_id;
        $stmt->close();
    }

    // Insert new course mappings
    foreach ($selected_courses as $course_id) {
        $stmt = $conn->prepare("INSERT INTO event_courses (event_id, course_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $event_id, $course_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: manage_events.php");
    exit;
}

// Fetch Events with Related Courses
$event_query = "
    SELECT e.*, GROUP_CONCAT(c.id SEPARATOR ',') AS course_ids, 
    GROUP_CONCAT(c.course_name SEPARATOR ', ') AS course_names 
    FROM events e 
    LEFT JOIN event_courses ec ON e.id = ec.event_id 
    LEFT JOIN courses c ON ec.course_id = c.id 
    GROUP BY e.id";
$event_result = $conn->query($event_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>


<!-- Header -->
 <header>
        <div class="logo">
            <img src="images/logo2.png" alt="Smart Campus Logo">
            <div class="text">
                <h1>Smart Campus</h1>
                <p>Management System</p>
            </div>
        </div>
        <nav>
            <ul>

                <li><a href="index.php"> <div class="button-container">
        <button onclick="window.location.href='st_dash.php'">
            <i class="fas fa-arrow-left"></i> Log out
        </button>
    </div></a></li>
                <li><a href="dashboard.php"> <div class="button-container">
        <button onclick="window.location.href='st_dash.php'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
    </div></a></li>
            </ul>
        </nav>
    </header>
<br>

<h2><?php echo $edit_event ? "Edit Event" : "Create Event"; ?></h2>

<!-- Form Container with Left and Right Panels -->
<div class="form-container">
    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $edit_event ? $event_data['id'] : ''; ?>">
        
        <div class="form-panels-container">
            <!-- Left Panel: Event Details Form -->
            <!-- Left Panel: Event Details Form -->
<div class="left-panel">
    <h4>Event Details:</h4>
    
    <div class="form-group">
        <label for="event-title">Title:</label>
        <input type="text" id="event-title" name="title" value="<?php echo $edit_event ? $event_data['title'] : ''; ?>" required>
    </div>
    
    <div class="form-group textarea-group">
        <label for="event-description">Description:</label>
        <textarea id="event-description" name="description" required><?php echo $edit_event ? $event_data['description'] : ''; ?></textarea>
    </div>
    
    <div class="form-group">
        <label for="event-date">Date:</label>
        <input type="date" id="event-date" name="event_date" value="<?php echo $edit_event ? $event_data['event_date'] : ''; ?>" required>
    </div>
    
    <div class="form-group">
        <label for="start-time">Start Time:</label>
        <input type="time" id="start-time" name="start_time" value="<?php echo $edit_event ? $event_data['start_time'] : ''; ?>" required>
    </div>
    
    <div class="form-group">
        <label for="end-time">End Time:</label>
        <input type="time" id="end-time" name="end_time" value="<?php echo $edit_event ? $event_data['end_time'] : ''; ?>" required>
    </div>
    
    <div class="form-group">
        <label for="event-venue">Venue:</label>
        <input type="text" id="event-venue" name="venue" value="<?php echo $edit_event ? $event_data['venue'] : ''; ?>" required>
    </div>
</div>

            <!-- Right Panel: Course Selection -->
            <div class="right-panel">
                <h4>Select Courses:</h4>
                <div class="course-checkboxes">
                    <?php foreach ($courses as $id => $name): ?>
                        <label>
                            <input type="checkbox" name="course_id[]" value="<?php echo $id; ?>" 
                                <?php echo ($edit_event && in_array($id, $selected_courses)) ? 'checked' : ''; ?> >
                            <?php echo $name; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Submit Button (Below both panels) -->
        <div class="submit-container">
            <button type="submit" name="<?php echo $edit_event ? 'edit_event' : 'create_event'; ?>">
                <?php echo $edit_event ? 'Update Event' : 'Create Event'; ?>
            </button>
        </div>
    </form>
</div>

<h2>Manage Events</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th style="width: 12.5%;">Title</th>
                <th style="width: 12.5%;">Description</th>
                <th style="width: 12.5%;">Date</th>
                <th style="width: 12.5%;">Time</th>
                <th style="width: 12.5%;">Venue</th>
                <th style="width: 12.5%;">Courses</th>
                <th style="width: 15.5%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($event = $event_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $event['title']; ?></td>
                <td><?php echo $event['description']; ?></td>
                <td><?php echo $event['event_date']; ?></td>
                <td><?php echo $event['start_time'] . " - " . $event['end_time']; ?></td>
                <td><?php echo $event['venue']; ?></td>
                <td><?php echo $event['course_names']; ?></td>
                <td>
                    <form id="edit" method="get" style="display:inline;">
                        <button type="submit" name="edit" value="<?php echo $event['id']; ?>" class="btn btn-primary">Edit</button>
                    </form>
                    <form id="delete" method="get" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this event?');">
                        <button type="submit" name="delete" value="<?php echo $event['id']; ?>" class="btn btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>



<style>
/* Container to hold the form */
.form-container {
    width: 90%;
    max-width: 1200px;
    margin: 0 auto 30px;
}

/* Container for both panels */
.form-panels-container {
    height: 500px;
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 20px;
    width: 100%;
}

/* Left form (Event Details) */
.left-panel {
    width: 48%;
    padding: 15px;
    background-color: #f8f8f8;
    border-radius: 5px;
    box-sizing: border-box;
    overflow-y: auto; /* Add scrolling if content overflows */
    height: 100%; /* Ensure it takes full height */
}

/* Right form (Select Courses) */
.right-panel {
    width: 48%;
    padding: 15px;
    background-color: #f8f8f8;
    border-radius: 5px;
    box-sizing: border-box;
    overflow-y: auto; /* Add scrolling if content overflows */
    height: 100%; /* Ensure it takes full height */
}

/* Updated left panel styling for left-aligned labels */
.left-panel .form-group {
    display: grid;
    grid-template-columns: 120px 1fr; /* Adjust the 120px width as needed */
    gap: 10px;
    margin-bottom: 15px;
    align-items: center;
}

.left-panel label {
    text-align: right;
    font-weight: 500;
    color: #333;
}

.left-panel input[type="text"],
.left-panel input[type="date"],
.left-panel input[type="time"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

/* Special case for textarea as it's taller */
.left-panel .form-group.textarea-group {
    align-items: start;
}

.left-panel .form-group.textarea-group label {
    padding-top: 8px; /* Align with the top of textarea */
}

.left-panel textarea {
    width: 100%;
    height: 100px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
    box-sizing: border-box;
}

/* Improved course checkboxes alignment */
.course-checkboxes {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 420px; /* Increased to use more of the available space */
    overflow-y: auto;
    padding-right: 10px;
    height: calc(100% - 40px); /* Leave space for the heading */
}

/* Checkbox Styling */
.course-checkboxes label {
    display: grid;
    grid-template-columns: 24px 1fr;
    align-items: center;
    margin-bottom: 8px;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.course-checkboxes label:hover {
    background-color: #f0f0f0;
}

.course-checkboxes input[type="checkbox"] {
    margin-right: 10px;
    min-width: 16px;
    height: 16px;
    justify-self: center;
}

/* Submit button container */
.submit-container {
    text-align: center;
    margin-top: 15px;
    width: 100%;
}

.submit-container button {
    padding: 10px 20px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    min-width: 200px;
}

.submit-container button:hover {
    background-color: #45a049;
}

/* Button Styling for Edit and Delete */
.btn {
    padding: 8px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: inline-block;
    margin: 5px;
}

/* Edit Button Styling */
.btn-primary {
    background-color: #007bff;
    color: white;
    border: none;
}

.btn-primary:hover {
    background-color: #0056b3;
}

/* Delete Button Styling */
.btn-danger {
    background-color: #dc3545;
    color: white;
    border: none;
}

.btn-danger:hover {
    background-color: #c82333;
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-panels-container {
        flex-direction: column;
        height: auto;
    }

    .left-panel, .right-panel {
        width: 100%;
        margin-bottom: 20px;
        height: 400px; /* Set a fixed height for mobile view */
    }
    
    .left-panel .form-group {
        grid-template-columns: 100px 1fr; /* Smaller label width on mobile */
    }
}

/* Override any main CSS that might be limiting width */
form, .form-container form {
    width: 100% !important;
    max-width: none !important;
}

/* Ensure form elements have enough space */
input, textarea, select, button {
    box-sizing: border-box !important;
}

/* Ensure all form elements have proper spacing */
.form-container * {
    box-sizing: border-box;
}

/* Improve form headings */
.left-panel h4, .right-panel h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
}

/* Table container with scrolling */
.table-container {
    max-height: 400px;
    overflow-y: auto;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

/* Table styling */
.table-container table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

/* Header styling */
.table-container thead {
    position: sticky;
    top: 0;
    background-color: #f8f8f8;
    z-index: 1;
}

.table-container th {
    padding: 12px 8px;
    text-align: center;
    border-bottom: 2px solid #ddd;
    font-weight: bold;
    background-color: #0F0E47;
    color: white;
}

.table-container td {
    padding: 8px;
    border-bottom: 1px solid #ddd;
    vertical-align: middle;
}

/* Description cell specific styles */
.table-container td:nth-child(2) {
    max-height: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Scrollbar styling */
.table-container::-webkit-scrollbar,
.course-checkboxes::-webkit-scrollbar,
.left-panel::-webkit-scrollbar,
.right-panel::-webkit-scrollbar {
    width: 8px;
}

.table-container::-webkit-scrollbar-track,
.course-checkboxes::-webkit-scrollbar-track,
.left-panel::-webkit-scrollbar-track,
.right-panel::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb,
.course-checkboxes::-webkit-scrollbar-thumb,
.left-panel::-webkit-scrollbar-thumb,
.right-panel::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb:hover,
.course-checkboxes::-webkit-scrollbar-thumb:hover,
.left-panel::-webkit-scrollbar-thumb:hover,
.right-panel::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Edit and Delete button styling */
#edit button {
    background-color: #007bff;
    border: 1px solid #007bff;
    color: white;
    padding: 8px 16px;
    font-size: 14px;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

#edit button:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}

#delete button {
    background-color: #dc3545;
    border: 1px solid #dc3545;
    color: white;
    padding: 8px 16px;
    font-size: 14px;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

#delete button:hover {
    background-color: #a71d2a;
    border-color: #a71d2a;
}

td {
    text-align: center;
}

td form {
    display: inline-block;
    margin: 0 5px;
}

button {
    width: auto;
    min-width: 80px;
}

h3 {
    text-align: center;
}
.button-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .button-container button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .button-container button:hover {
            background-color: #3a5fd9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78,115,223,0.4);
        }

        .button-container button i {
            font-size: 0.9rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
            }
            
            .profile-banner {
                height: 100px;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
                top: 50px;
            }
            
            .profile-details {
                padding-top: 70px;
            }
        }

</style>

</body>
</html>