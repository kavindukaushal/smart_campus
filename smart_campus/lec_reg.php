<?php
session_start();
include 'db_connect.php';

// Ensure user is logged in and has admin privileges
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch all lecturers from the lecturer table with related courses and batches
$query = "SELECT l.id, l.title, l.first_name, l.last_name, l.gender, l.birth_date, 
          l.teaching_qualification, l.position, l.email, l.username,
          (SELECT GROUP_CONCAT(c.course_name SEPARATOR ', ') 
           FROM lecturer_courses lc 
           JOIN courses c ON lc.course_id = c.id 
           WHERE lc.lecturer_id = l.id) AS courses,
          (SELECT GROUP_CONCAT(b.batch_name SEPARATOR ', ') 
           FROM lecturer_batches lb 
           JOIN batch b ON lb.batch_id = b.batch_id 
           WHERE lb.lecturer_id = l.id) AS batches
          FROM lecturer l";

// Execute the query and store the result
$result = mysqli_query($conn, $query);

// Check if the query was successful
if (!$result) {
    echo "Error: " . mysqli_error($conn);
    exit;
}

// Fetch all courses for the checkboxes (a lecturer can teach multiple courses)
$query_courses = "SELECT * FROM courses";
$result_courses = mysqli_query($conn, $query_courses);

// Fetch all batches for the checkboxes (currently only one batch for now)
$query_batches = "SELECT * FROM batch";
$result_batches = mysqli_query($conn, $query_batches);

// Handle form submission (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $teaching_qualification = $_POST['teaching_qualification'];
    $position = $_POST['position'];  // Position is selected from the dropdown
    $email = $_POST['email'];  // Email field
    $username = $_POST['username'];  // Username field
    $password = $_POST['password'];  // Password field
    $hashed_password = md5($password); // Hash the password using MD5


    $course_ids = isset($_POST['course_ids']) ? $_POST['course_ids'] : [];  // Multiple courses
    $batch_ids = isset($_POST['batch_ids']) ? $_POST['batch_ids'] : [];  // Multiple batches

    // If there is a lecturer id (Edit), update the record
    if (isset($_POST['lecturer_id']) && !empty($_POST['lecturer_id'])) {
        $lecturer_id = $_POST['lecturer_id'];
        $query = "UPDATE lecturer SET title='$title', first_name='$first_name', last_name='$last_name', gender='$gender', birth_date='$birth_date', teaching_qualification='$teaching_qualification', position='$position', email='$email', username='$username', password_hash='$hashed_password' WHERE id=$lecturer_id";
        mysqli_query($conn, $query);

        // Delete existing course assignments and insert new ones
        $delete_courses = "DELETE FROM lecturer_courses WHERE lecturer_id = $lecturer_id";
        mysqli_query($conn, $delete_courses);

        foreach ($course_ids as $course_id) {
            $query_courses = "INSERT INTO lecturer_courses (lecturer_id, course_id) VALUES ($lecturer_id, $course_id)";
            mysqli_query($conn, $query_courses);
        }

        // Delete existing batch assignments and insert new ones
        $delete_batches = "DELETE FROM lecturer_batches WHERE lecturer_id = $lecturer_id";
        mysqli_query($conn, $delete_batches);

        foreach ($batch_ids as $batch_id) {
            $query_batches = "INSERT INTO lecturer_batches (lecturer_id, batch_id) VALUES ($lecturer_id, $batch_id)";
            mysqli_query($conn, $query_batches);
        }

    } else {
        // Otherwise, insert a new lecturer (id will be auto-incremented)
        $query = "INSERT INTO lecturer (title, first_name, last_name, gender, birth_date, teaching_qualification, position, email, username, password_hash) 
                  VALUES ('$title', '$first_name', '$last_name', '$gender', '$birth_date', '$teaching_qualification', '$position', '$email', '$username', '$hashed_password')";
        mysqli_query($conn, $query);
        $lecturer_id = mysqli_insert_id($conn); // Get the last inserted lecturer's ID

        // Insert course assignments
        foreach ($course_ids as $course_id) {
            $query_courses = "INSERT INTO lecturer_courses (lecturer_id, course_id) VALUES ($lecturer_id, $course_id)";
            mysqli_query($conn, $query_courses);
        }

        // Insert batch assignments
        foreach ($batch_ids as $batch_id) {
            $query_batches = "INSERT INTO lecturer_batches (lecturer_id, batch_id) VALUES ($lecturer_id, $batch_id)";
            mysqli_query($conn, $query_batches);
        }
    }

    header('Location: lec_reg.php'); // Refresh page to show updates
    exit();
}

// Handle delete action
if (isset($_GET['delete'])) {
    $lecturer_id = $_GET['delete'];
    $query = "DELETE FROM lecturer WHERE id=$lecturer_id";
    mysqli_query($conn, $query);

    // Delete the courses and batches associated with the lecturer
    $query_courses = "DELETE FROM lecturer_courses WHERE lecturer_id=$lecturer_id";
    mysqli_query($conn, $query_courses);

    $query_batches = "DELETE FROM lecturer_batches WHERE lecturer_id=$lecturer_id";
    mysqli_query($conn, $query_batches);

    header('Location: lec_reg.php'); // Refresh page after deletion
    exit();
}

// Fetch single lecturer for editing
$editLecturer = null;
if (isset($_GET['edit'])) {
    $lecturer_id = $_GET['edit'];
    $query = "SELECT * FROM lecturer WHERE id=$lecturer_id";
    $editLecturer = mysqli_fetch_assoc(mysqli_query($conn, $query));
}

// Fetch the courses assigned to the lecturer (if editing)
$assigned_courses = [];
if (isset($editLecturer)) {
    $query_assigned_courses = "SELECT course_id FROM lecturer_courses WHERE lecturer_id=" . $editLecturer['id'];
    $assigned_courses_result = mysqli_query($conn, $query_assigned_courses);
    while ($row = mysqli_fetch_assoc($assigned_courses_result)) {
        $assigned_courses[] = $row['course_id'];
    }
}

// Fetch the batches assigned to the lecturer (if editing)
$assigned_batches = [];
if (isset($editLecturer)) {
    $query_assigned_batches = "SELECT batch_id FROM lecturer_batches WHERE lecturer_id=" . $editLecturer['id'];
    $assigned_batches_result = mysqli_query($conn, $query_assigned_batches);
    while ($row = mysqli_fetch_assoc($assigned_batches_result)) {
        $assigned_batches[] = $row['batch_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Registration</title>
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
    
    <h2>Lecturer Registration</h2>

<div class="form-container">
    
    <form action="lec_reg.php" method="POST">
        <!-- Hidden field for lecturer_id (needed only for editing) -->
        <input type="hidden" name="lecturer_id" value="<?php echo isset($editLecturer) ? $editLecturer['id'] : ''; ?>">
        <h2><?php echo isset($editLecturer) ? 'Edit Lecturer' : 'Add New Lecturer'; ?></h2>
        <div class="form-panels-container">
            <!-- Left Panel -->
            <div class="left-panel">
                <div class="form-group">
                    <label for="title">Title</label>
                    <select id="title" name="title" required>
                        <option value="Mr" <?php echo isset($editLecturer) && $editLecturer['title'] == 'Mr' ? 'selected' : ''; ?>>Mr</option>
                        <option value="Miss" <?php echo isset($editLecturer) && $editLecturer['title'] == 'Miss' ? 'selected' : ''; ?>>Miss</option>
                        <option value="Ms" <?php echo isset($editLecturer) && $editLecturer['title'] == 'Ms' ? 'selected' : ''; ?>>Ms</option>
                        <option value="Dr" <?php echo isset($editLecturer) && $editLecturer['title'] == 'Dr' ? 'selected' : ''; ?>>Dr</option>
                        <option value="Prof" <?php echo isset($editLecturer) && $editLecturer['title'] == 'Prof' ? 'selected' : ''; ?>>Prof</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo isset($editLecturer) ? $editLecturer['first_name'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo isset($editLecturer) ? $editLecturer['last_name'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="Male" <?php echo isset($editLecturer) && $editLecturer['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo isset($editLecturer) && $editLecturer['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo isset($editLecturer) && $editLecturer['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="birth_date">Birth Date</label>
                    <input type="date" id="birth_date" name="birth_date" value="<?php echo isset($editLecturer) ? $editLecturer['birth_date'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($editLecturer) ? $editLecturer['email'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($editLecturer) ? $editLecturer['username'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" value="" <?php echo isset($editLecturer) ? '' : 'required'; ?>>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="right-panel">
                <div class="form-group">
                    <label for="teaching_qualification">Teaching Qualification</label>
                    <select id="teaching_qualification" name="teaching_qualification" required>
                        <option value="HND" <?php echo isset($editLecturer) && $editLecturer['teaching_qualification'] == 'HND' ? 'selected' : ''; ?>>HND</option>
                        <option value="Degree" <?php echo isset($editLecturer) && $editLecturer['teaching_qualification'] == 'Degree' ? 'selected' : ''; ?>>Degree</option>
                        <option value="Masters" <?php echo isset($editLecturer) && $editLecturer['teaching_qualification'] == 'Masters' ? 'selected' : ''; ?>>Masters</option>
                        <option value="PhD" <?php echo isset($editLecturer) && $editLecturer['teaching_qualification'] == 'PhD' ? 'selected' : ''; ?>>PhD</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="position">Position</label>
                    <select id="position" name="position" required>
                        <option value="Tutor" <?php echo isset($editLecturer) && $editLecturer['position'] == 'Tutor' ? 'selected' : ''; ?>>Tutor</option>
                        <option value="Assistant Lecturer" <?php echo isset($editLecturer) && $editLecturer['position'] == 'Assistant Lecturer' ? 'selected' : ''; ?>>Assistant Lecturer</option>
                        <option value="Lecturer" <?php echo isset($editLecturer) && $editLecturer['position'] == 'Lecturer' ? 'selected' : ''; ?>>Lecturer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="course_ids">Courses</label>
                    <div class="course-checkboxes">
                        <?php 
                        mysqli_data_seek($result_courses, 0);
                        while ($course = mysqli_fetch_assoc($result_courses)): 
                        ?>
                            <label><input type="checkbox" name="course_ids[]" value="<?php echo $course['id']; ?>" 
                            <?php echo isset($assigned_courses) && in_array($course['id'], $assigned_courses) ? 'checked' : ''; ?>> 
                            <?php echo $course['course_name']; ?></label>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="batch_ids">Batch</label>
                    <div class="checkbox-group">
                        <?php 
                        mysqli_data_seek($result_batches, 0);
                        while ($batch = mysqli_fetch_assoc($result_batches)): 
                        ?>
                            <label><input type="checkbox" name="batch_ids[]" value="<?php echo $batch['batch_id']; ?>" 
                            <?php echo isset($assigned_batches) && in_array($batch['batch_id'], $assigned_batches) ? 'checked' : ''; ?>> 
                            <?php echo $batch['batch_name']; ?></label>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit"><?php echo isset($editLecturer) ? 'Edit Lecturer' : 'Add Lecturer'; ?></button>
    </form>
</div>
<h2>Registered Lecturers</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">ID</th>
                <th style="width: 5%;">Title</th>
                <th style="width: 8%;">First Name</th>
                <th style="width: 8%;">Last Name</th>
                <th style="width: 6%;">Gender</th>
                <th style="width: 8%;">Birth Date</th>
                <th style="width: 8%;">Qualification</th>
                <th style="width: 8%;">Position</th>
                <th style="width: 8%;">Email</th>
                <th style="width: 8%;">Username</th>
                <th style="width: 12%;">Courses</th>
                <th style="width: 7%;">Batch</th>
                <th style="width: 10%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['title']; ?></td>
                    <td><?php echo $row['first_name']; ?></td>
                    <td><?php echo $row['last_name']; ?></td>
                    <td><?php echo $row['gender']; ?></td>
                    <td><?php echo $row['birth_date']; ?></td>
                    <td><?php echo $row['teaching_qualification']; ?></td>
                    <td><?php echo $row['position']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['username']; ?></td>
                    <td><?php echo $row['courses']; ?></td>
                    <td><?php echo $row['batches']; ?></td>
                    <td>
                        <form id="edit" method="get" style="display:inline;">
                            <button type="submit" name="edit" value="<?php echo $row['id']; ?>" class="btn btn-primary">Edit</button>
                        </form>
                        <form id="delete" method="get" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this lecturer?');">
                            <button type="submit" name="delete" value="<?php echo $row['id']; ?>" class="btn btn-danger">Delete</button>
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
    height: 650px;
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
    gap: 5px;
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
    width: 100%;
    max-height: 350px;
    overflow-y: auto;
    margin: 0 auto 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

/* Table styling */
.table-container table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    min-width: 1200px; /* This ensures the table doesn't shrink below a usable size */
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