<?php
session_start();
include 'db_connect.php';

// Check if the lecturer is logged in
if (!isset($_SESSION['lecturer_id'])) {
    header('Location: login.php');
    exit();
}

// Handle event update (editing existing record)
if (isset($_POST['update_event'])) {
    $event_id = $_POST['event_id'];
    $title = $_POST['title'];
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $description = $_POST['description'];
    $venue = $_POST['venue'];
    $selected_courses = $_POST['courses']; // Get selected courses

    // Update event in the database
    $update_stmt = $conn->prepare("UPDATE events SET title = ?, event_date = ?, start_time = ?, end_time = ?, description = ?, venue = ? WHERE id = ?");
    $update_stmt->bind_param("ssssssi", $title, $event_date, $start_time, $end_time, $description, $venue, $event_id);
    if ($update_stmt->execute()) {
        // Delete existing course associations for this event
        $delete_courses_stmt = $conn->prepare("DELETE FROM event_courses WHERE event_id = ?");
        $delete_courses_stmt->bind_param("i", $event_id);
        $delete_courses_stmt->execute();

        // Insert new course associations
        foreach ($selected_courses as $course_id) {
            $insert_course_stmt = $conn->prepare("INSERT INTO event_courses (event_id, course_id) VALUES (?, ?)");
            $insert_course_stmt->bind_param("ii", $event_id, $course_id);
            $insert_course_stmt->execute();
        }

        $_SESSION['message'] = "Event updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating event: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }

    header("Location: lec_create_event.php");
    exit();
}

// Handle event creation (new record)
if (isset($_POST['create_event'])) {
    $title = $_POST['title'];
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $description = $_POST['description'];
    $venue = $_POST['venue'];
    $selected_courses = $_POST['courses']; // Get selected courses

    // Insert new event into the database
    $stmt = $conn->prepare("INSERT INTO events (title, event_date, start_time, end_time, description, venue) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $title, $event_date, $start_time, $end_time, $description, $venue);
    
    if ($stmt->execute()) {
        $event_id = $stmt->insert_id;

        // Insert course associations into event_courses table
        foreach ($selected_courses as $course_id) {
            $insert_course_stmt = $conn->prepare("INSERT INTO event_courses (event_id, course_id) VALUES (?, ?)");
            $insert_course_stmt->bind_param("ii", $event_id, $course_id);
            $insert_course_stmt->execute();
        }

        $_SESSION['message'] = "New event created successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error creating event: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }

    header("Location: lec_create_event.php");
    exit();
}

// Handle event deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['message'] = "Event deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting event: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: lec_create_event.php");
    exit();
}

// Determine if we're editing an event
$edit_mode = false;
$edit_event = null;
$edit_courses = [];

if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_query = "SELECT * FROM events WHERE id = ?";
    $edit_stmt = $conn->prepare($edit_query);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    
    if ($edit_result->num_rows > 0) {
        $edit_event = $edit_result->fetch_assoc();
        $edit_mode = true;

        // Fetch the courses associated with this event
        $courses_query = "SELECT course_id FROM event_courses WHERE event_id = ?";
        $courses_stmt = $conn->prepare($courses_query);
        $courses_stmt->bind_param("i", $edit_id);
        $courses_stmt->execute();
        $courses_result = $courses_stmt->get_result();

        while ($course = $courses_result->fetch_assoc()) {
            $edit_courses[] = $course['course_id'];
        }
    }
}

// Fetch all events for display
$event_query = "SELECT e.id, e.title, e.event_date, e.start_time, e.end_time, e.description, e.venue, GROUP_CONCAT(c.course_name) as courses
                FROM events e
                LEFT JOIN event_courses ec ON e.id = ec.event_id
                LEFT JOIN courses c ON ec.course_id = c.id
                GROUP BY e.id
                ORDER BY e.event_date DESC, e.start_time ASC";
$event_result = $conn->query($event_query);

// Fetch all courses for the form dropdown
$courses_query = "SELECT id, course_name FROM courses";
$courses_result = $conn->query($courses_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create or Edit Event</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
                <li><a href="lec_dash.php"> <div class="button-container">
        <button onclick="window.location.href='st_dash.php'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
    </div></a></li>
            </ul>
        </nav>
    </header>
    
    <div class="container">
        <h1>Manage Events</h1>

        <!-- Success/Error messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Event Creation/Edit Form -->
        <div class="card mb-3">
            <div class="card-header">
                <?php echo $edit_mode ? 'Edit Event' : 'Create New Event'; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="lec_create_event.php">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="title">Event Title</label>
                        <input type="text" class="form-control" name="title" required value="<?php echo $edit_mode ? $edit_event['title'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="event_date">Event Date</label>
                        <input type="date" class="form-control" name="event_date" required value="<?php echo $edit_mode ? $edit_event['event_date'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" class="form-control" name="start_time" required value="<?php echo $edit_mode ? $edit_event['start_time'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" class="form-control" name="end_time" required value="<?php echo $edit_mode ? $edit_event['end_time'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" name="description" required><?php echo $edit_mode ? $edit_event['description'] : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="venue">Venue</label>
                        <input type="text" class="form-control" name="venue" required value="<?php echo $edit_mode ? $edit_event['venue'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="courses">Select Courses</label>
                        <select name="courses[]" id="courses" class="form-control" multiple required>
                            <?php while ($course = $courses_result->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo ($edit_mode && in_array($course['id'], $edit_courses)) ? 'selected' : ''; ?>>
                                    <?php echo $course['course_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group text-right">
                        <?php if ($edit_mode): ?>
                            <button type="submit" name="update_event" class="btn btn-success">Update Event</button>
                        <?php else: ?>
                            <button type="submit" name="create_event" class="btn btn-primary">Create Event</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Event List Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Upcoming Events</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Venue</th>
                            <th>Courses</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = $event_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $event['title']; ?></td>
                                <td><?php echo $event['event_date']; ?></td>
                                <td><?php echo $event['start_time'] . ' - ' . $event['end_time']; ?></td>
                                <td><?php echo $event['venue']; ?></td>
                                <td><?php echo $event['courses']; ?></td>
                                <td>
                                    <a href="lec_create_event.php?edit_id=<?php echo $event['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="lec_create_event.php?delete_id=<?php echo $event['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
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