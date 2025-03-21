<?php
session_start();
include 'db_connect.php';

// Check if the lecturer is logged in
if (!isset($_SESSION['lecturer_id'])) {
    header('Location: login.php');
    exit();
}

// Handle class update (editing existing record)
if (isset($_POST['update_class'])) {
    $class_id = $_POST['class_id'];
    $course_id = $_POST['course_id'];
    $resource_id = $_POST['resource_id'];
    $schedule_date = $_POST['schedule_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Update class schedule in database
    $update_stmt = $conn->prepare("UPDATE class_schedules SET course_id = ?, resource_id = ?, schedule_date = ?, start_time = ?, end_time = ? WHERE id = ?");
    $update_stmt->bind_param("iisssi", $course_id, $resource_id, $schedule_date, $start_time, $end_time, $class_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['message'] = "Class schedule updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating class schedule: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: lec_create_class.php");
    exit();
}

// Handle class creation (new record)
if (isset($_POST['create_class'])) {
    $course_id = $_POST['course_id'];
    $resource_id = $_POST['resource_id'];
    $schedule_date = $_POST['schedule_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Insert class schedule into database
    // Omit the created_by field if it's not needed
    $stmt = $conn->prepare("INSERT INTO class_schedules (course_id, resource_id, schedule_date, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $course_id, $resource_id, $schedule_date, $start_time, $end_time);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "New class schedule created successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error creating class schedule: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: lec_create_class.php");
    exit();
}


// Handle class deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_stmt = $conn->prepare("DELETE FROM class_schedules WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['message'] = "Class schedule deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting class schedule: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: lec_create_class.php");
    exit();
}

// Determine if we're editing a class
$edit_mode = false;
$edit_class = null;

if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_query = "SELECT * FROM class_schedules WHERE id = ?";
    $edit_stmt = $conn->prepare($edit_query);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    
    if ($edit_result->num_rows > 0) {
        $edit_class = $edit_result->fetch_assoc();
        $edit_mode = true;
    }
}

// Fetch all class schedules for display
$class_query = "SELECT cs.id, c.course_name, cs.schedule_date, cs.start_time, cs.end_time, r.resource_name, c.id as course_id, r.id as resource_id
                FROM class_schedules cs
                LEFT JOIN resources r ON cs.resource_id = r.id
                LEFT JOIN courses c ON cs.course_id = c.id
                ORDER BY cs.schedule_date DESC, cs.start_time ASC";
$class_result = $conn->query($class_query);

// Get the lecturer's name
$lecturer_query = "SELECT first_name, last_name FROM lecturer WHERE id = ?";
$lecturer_stmt = $conn->prepare($lecturer_query);
$lecturer_stmt->bind_param("i", $_SESSION['lecturer_id']);
$lecturer_stmt->execute();
$lecturer_result = $lecturer_stmt->get_result();
$lecturer = $lecturer_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard - Class Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .action-buttons a {
            margin-right: 5px;
        }
        .form-group {
            margin-bottom: 15px;
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
        <!-- Header with welcome message -->
        <div class="header">
            <h1>Lecturer Dashboard</h1>
            <div>
                <h5>Welcome, <?php echo $lecturer['first_name'] . ' ' . $lecturer['last_name']; ?>!</h5>
                
            </div>
        </div>

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

        <!-- Class Creation/Edit Form -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <?php echo $edit_mode ? 'Edit Class Schedule' : 'Create New Class Schedule'; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="lec_create_class.php">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="class_id" value="<?php echo $edit_class['id']; ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="course_id">Course</label>
                            <select name="course_id" id="course_id" class="form-control" required>
                                <option value="">Select Course</option>
                                <?php
                                $courses = $conn->query("SELECT id, course_name FROM courses ORDER BY course_name");
                                while ($course = $courses->fetch_assoc()) {
                                    $selected = "";
                                    if ($edit_mode && $edit_class['course_id'] == $course['id']) {
                                        $selected = "selected";
                                    }
                                    echo "<option value='{$course['id']}' {$selected}>{$course['course_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="resource_id">Resource</label>
                            <select name="resource_id" id="resource_id" class="form-control" required>
                                <option value="">Select Resource</option>
                                <?php
                                $resources = $conn->query("SELECT id, resource_name FROM resources ORDER BY resource_name");
                                while ($resource = $resources->fetch_assoc()) {
                                    $selected = "";
                                    if ($edit_mode && $edit_class['resource_id'] == $resource['id']) {
                                        $selected = "selected";
                                    }
                                    echo "<option value='{$resource['id']}' {$selected}>{$resource['resource_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="schedule_date">Schedule Date</label>
                            <input type="date" class="form-control" id="schedule_date" name="schedule_date" 
                                   value="<?php echo $edit_mode ? $edit_class['schedule_date'] : date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="start_time">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" 
                                   value="<?php echo $edit_mode ? $edit_class['start_time'] : '08:00'; ?>" required>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="end_time">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" 
                                   value="<?php echo $edit_mode ? $edit_class['end_time'] : '10:00'; ?>" required>
                        </div>
                    </div>

                    <div class="form-group text-right">
                        <?php if ($edit_mode): ?>
                            <button type="submit" name="update_class" class="btn btn-success">Update Class Schedule</button>
                            <a href="lec_create_class.php" class="btn btn-secondary">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="create_class" class="btn btn-primary">Create Class Schedule</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Class Schedules Table -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Class Schedules</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Course Name</th>
                                <th>Resource</th>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($class_result->num_rows > 0) {
                                while ($class = $class_result->fetch_assoc()) { 
                            ?>
                                <tr>
                                    <td><?php echo $class['id']; ?></td>
                                    <td><?php echo $class['course_name']; ?></td>
                                    <td><?php echo $class['resource_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($class['schedule_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($class['start_time'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($class['end_time'])); ?></td>
                                    <td class="action-buttons">
                                        <a href="lec_create_class.php?edit_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <a href="lec_create_class.php?delete_id=<?php echo $class['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this class schedule?')">
                                            <i class="fa fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php 
                                }
                            } else {
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center">No class schedules found</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer navigation -->
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>