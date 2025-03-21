<?php
session_start();
include 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle Create or Edit Schedule
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'];
    $resource_id = $_POST['resource_id'];
    $schedule_date = $_POST['schedule_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $admin_id = $_SESSION['admin_id'];

    if (isset($_POST['schedule_id'])) {
        // Editing an existing schedule
        $schedule_id = $_POST['schedule_id'];

        // Check for conflicts
        $check_query = "SELECT COUNT(*) AS count FROM class_schedules WHERE resource_id = ? AND schedule_date = ? 
                        AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?)) AND id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("isssssi", $resource_id, $schedule_date, $start_time, $start_time, $end_time, $end_time, $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] > 0) {
            $error = "The selected classroom/lab is already booked for this time slot!";
        } else {
            // Update existing schedule
            $stmt = $conn->prepare("UPDATE class_schedules SET course_id=?, resource_id=?, schedule_date=?, start_time=?, end_time=? WHERE id=?");
            $stmt->bind_param("iisssi", $course_id, $resource_id, $schedule_date, $start_time, $end_time, $schedule_id);
            $stmt->execute();

            // Update resource status to reserved
            $conn->query("UPDATE resources SET status='reserved' WHERE id = $resource_id");

            header("Location: allocate_resources.php");
            exit;
        }
    } else {
        // Creating a new schedule

        // Check for conflicts
        $check_query = "SELECT COUNT(*) AS count FROM class_schedules WHERE resource_id = ? AND schedule_date = ? 
                        AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("isssss", $resource_id, $schedule_date, $start_time, $start_time, $end_time, $end_time);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] > 0) {
            $error = "The selected classroom/lab is already booked for this time slot!";
        } else {
            // Insert new schedule
            $stmt = $conn->prepare("INSERT INTO class_schedules (course_id, resource_id, schedule_date, start_time, end_time, created_by) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssi", $course_id, $resource_id, $schedule_date, $start_time, $end_time, $admin_id);
            $stmt->execute();

            // Update resource status to reserved
            $conn->query("UPDATE resources SET status='reserved' WHERE id = $resource_id");

            header("Location: allocate_resources.php");
            exit;
        }
    }
}

// Handle Delete Schedule
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    // Get resource ID for this schedule to update its availability
    $schedule_query = $conn->query("SELECT resource_id FROM class_schedules WHERE id = $delete_id");
    $schedule = $schedule_query->fetch_assoc();
    $resource_id = $schedule['resource_id'];

    $conn->query("DELETE FROM class_schedules WHERE id = $delete_id");

    // Update resource status to available
    $conn->query("UPDATE resources SET status='available' WHERE id = $resource_id");

    header("Location: allocate_resources.php");
    exit;
}

// Fetch Courses
$course_query = "SELECT * FROM courses";
$course_result = $conn->query($course_query);

// Fetch Available Resources
$resource_query = "SELECT * FROM resources";
$resource_result = $conn->query($resource_query);

// Fetch Existing Class Schedules
$schedule_query = "SELECT class_schedules.*, courses.course_name, resources.resource_name 
                   FROM class_schedules 
                   JOIN courses ON class_schedules.course_id = courses.id 
                   JOIN resources ON class_schedules.resource_id = resources.id";
$schedule_result = $conn->query($schedule_query);

// If Editing, Fetch Schedule Data
$edit_schedule = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_schedule = $conn->query("SELECT * FROM class_schedules WHERE id = $edit_id")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Allocation</title>
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


<h2><?php echo $edit_schedule ? "Edit Schedule" : "Schedule a Class"; ?></h2>

<?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>

<!-- Create/Edit Form -->
<form method="POST">
    <?php if ($edit_schedule): ?>
        <input type="hidden" name="schedule_id" value="<?php echo $edit_schedule['id']; ?>">
    <?php endif; ?>

    <label for="course_id">Select Course:</label>
    <select name="course_id" required>
        <?php while ($course = $course_result->fetch_assoc()): ?>
            <option value="<?php echo $course['id']; ?>" <?php if ($edit_schedule && $edit_schedule['course_id'] == $course['id']) echo 'selected'; ?>>
                <?php echo $course['course_name']; ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="resource_id">Select Resource:</label>
    <select name="resource_id" required>
        <?php while ($resource = $resource_result->fetch_assoc()): ?>
            <option value="<?php echo $resource['id']; ?>" <?php if ($edit_schedule && $edit_schedule['resource_id'] == $resource['id']) echo 'selected'; ?>>
                <?php echo $resource['resource_name']; ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="schedule_date">Schedule Date:</label>
    <input type="date" name="schedule_date" value="<?php echo $edit_schedule['schedule_date'] ?? ''; ?>" required>

    <label for="start_time">Start Time:</label>
    <input type="time" name="start_time" value="<?php echo $edit_schedule['start_time'] ?? ''; ?>" required>

    <label for="end_time">End Time:</label>
    <input type="time" name="end_time" value="<?php echo $edit_schedule['end_time'] ?? ''; ?>" required>

    <button type="submit"><?php echo $edit_schedule ? "Update Schedule" : "Schedule Class"; ?></button>
</form>

<!-- Scheduled Classes Table -->
<h2>Scheduled Classes</h2>

<!-- Table Container with Scroll -->
<div class="table-container">
    <table>
        <tr>
            <th>ID</th>
            <th>Course</th>
            <th>Resource</th>
            <th>Date</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Actions</th>
        </tr>
        <?php while ($schedule = $schedule_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $schedule['id']; ?></td>
            <td><?php echo $schedule['course_name']; ?></td>
            <td><?php echo $schedule['resource_name']; ?></td>
            <td><?php echo $schedule['schedule_date']; ?></td>
            <td><?php echo $schedule['start_time']; ?></td>
            <td><?php echo $schedule['end_time']; ?></td>
            <td>
                <a href="allocate_resources.php?edit=<?php echo $schedule['id']; ?>"><button id="edit">Edit</button></a> |
                <a href="allocate_resources.php?delete=<?php echo $schedule['id']; ?>" onclick="return confirm('Are you sure?');"><button id="delete">Delete</button></a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<style>
    /* Table Container with Scroll */
.table-container {
    width: 100%;
    max-height: 400px; 
    overflow-y: auto; 
    margin-top: 20px;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background-color: #ffffff;
}

table th, table td {
    padding: 12px;
    text-align: left;
    border: 1px solid #8686AC;
}

table th {
    background-color: #0F0E47;
    color: #fff;
}

table tr:nth-child(even) {
    background-color: #f4f4f4;
}

table tr:hover {
    background-color: #e1e1e1;
}

/* Action Buttons */
button {
    padding: 4px 8px; /* Smaller padding */
    margin: 0 2px; /* Tiny margin between buttons */
    border: none;
    font-size: 12px; /* Smaller font */
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.3s;
    display: inline-block; /* Keep buttons on the same line */
    width: auto; /* Let buttons adjust to content */
}

/* Edit Button */
button#edit {
    background-color: #0F0E47;
    color: white;
}

button#edit:hover {
    background-color: #505081;
}

/* Delete Button */
button#delete {
    background-color: red;
    color: white;
}

button#delete:hover {
    background-color: #ff4d4d;
}

/* Ensure the actions column stays clean */
table td:nth-child(7) {
    white-space: nowrap; /* Prevent wrapping */
    text-align: center; /* Center the buttons */
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