<?php
// Assuming you have database connection already established
require 'db_connect.php';

// Fetch all students from the student table
$query = "SELECT * FROM student";
$result = mysqli_query($conn, $query);

// Fetch all courses for the dropdown
$query_courses = "SELECT * FROM courses";
$result_courses = mysqli_query($conn, $query_courses);

// Fetch all batches for the dropdown
$query_batches = "SELECT * FROM batch";
$result_batches = mysqli_query($conn, $query_batches);

// Handle form submission (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $registered_course = mysqli_real_escape_string($conn, $_POST['registered_course']);
    $batch_id = mysqli_real_escape_string($conn, $_POST['batch_id']);
    $birth_date = mysqli_real_escape_string($conn, $_POST['birth_date']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $hashed_password = md5($password); // Hash the password using MD5
    
    // Current timestamp for created_at and updated_at fields
    $current_timestamp = date("Y-m-d H:i:s");

    // If there is a student id (Edit), update the record
    if (isset($_POST['student_id']) && !empty($_POST['student_id'])) {
        $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
        $query = "UPDATE student SET 
                first_name='$first_name', 
                last_name='$last_name', 
                email='$email', 
                registered_course='$registered_course', 
                batch_id='$batch_id', 
                birth_date='$birth_date', 
                gender='$gender', 
                username='$username', 
                updated_at='$current_timestamp'";
                
        // Only update password if it's provided
        if (!empty($password)) {
            $query .= ", password_hash='$hashed_password'";
        }
        
        $query .= " WHERE id=$student_id";
        mysqli_query($conn, $query);
    } else {
        // Otherwise, insert a new student (id will be auto-incremented)
        $query = "INSERT INTO student (
                first_name, last_name, email, registered_course, batch_id, 
                birth_date, gender, username, password_hash, created_at, updated_at
            ) VALUES (
                '$first_name', '$last_name', '$email', '$registered_course', '$batch_id', 
                '$birth_date', '$gender', '$username', '$hashed_password', '$current_timestamp', '$current_timestamp'
            )";
        mysqli_query($conn, $query);
    }

    header('Location: st_reg.php'); // Refresh page to show updates
    exit();
}

// Handle delete action
if (isset($_GET['delete'])) {
    $student_id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First, delete any related records in the student_courses table
        $query_delete_courses = "DELETE FROM student_courses WHERE student_id = $student_id";
        mysqli_query($conn, $query_delete_courses);
        
        // Then delete the student
        $query_delete_student = "DELETE FROM student WHERE id = $student_id";
        mysqli_query($conn, $query_delete_student);
        
        // If we get here, commit the transaction
        mysqli_commit($conn);
        
        header('Location: st_reg.php'); // Refresh page after deletion
        exit();
    } catch (Exception $e) {
        // If there's an error, roll back the transaction
        mysqli_rollback($conn);
        echo "Error deleting record: " . $e->getMessage();
    }
}

// Fetch single student for editing
$editStudent = null;
if (isset($_GET['edit'])) {
    $student_id = mysqli_real_escape_string($conn, $_GET['edit']);
    $query = "SELECT * FROM student WHERE id=$student_id";
    $edit_result = mysqli_query($conn, $query);
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $editStudent = mysqli_fetch_assoc($edit_result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        h1 {
            color: #0056b3; /* Default blue for headers */
            text-align: center;
            margin-top: 20px;
        }
        .form-container {
            background-color: #ffffff;
            padding: 20px;
            margin: 20px auto;
            width: 50%;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #0056b3; /* Default blue */
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #004494; /* Darker blue on hover */
        }
        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: #ffffff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color:rgb(14, 13, 74); /* Default blue */
            color: #ffffff;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #e0e0e0;
        }
        .actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }
        .actions button {
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
        .actions .edit-button {
            background-color:rgb(9, 13, 73); /* Blue for Edit */
            color: #ffffff;
        }
        .actions .edit-button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }
        .actions .delete-button {
            background-color:red; /* Red for Delete */
            color: #ffffff;
        }
        .actions .delete-button:hover {
            background-color: #a71d2a; /* Darker red on hover */
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin: 10px auto;
            width: 90%;
            border-radius: 4px;
            display: none;
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
                <li><a href="dashboard.php"> <div class="button-container">
        <button onclick="window.location.href='st_dash.php'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
    </div></a></li>
            </ul>
        </nav>
    </header>




    <h1>Student Registration</h1>
    <div id="error-message" class="error-message"></div>
    <div class="form-container">
        <h2><?php echo isset($editStudent) ? 'Edit Student' : 'Add New Student'; ?></h2>
        <form action="st_reg.php" method="POST">
            <input type="hidden" name="student_id" value="<?php echo isset($editStudent) ? $editStudent['id'] : ''; ?>">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo isset($editStudent) ? $editStudent['first_name'] : ''; ?>" required>
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo isset($editStudent) ? $editStudent['last_name'] : ''; ?>" required>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo isset($editStudent) ? $editStudent['email'] : ''; ?>" required>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo isset($editStudent) ? $editStudent['username'] : ''; ?>" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" <?php echo isset($editStudent) ? '' : 'required'; ?>>
            <?php if(isset($editStudent)): ?>
                <small style="display: block; margin-top: -5px; color: #666;">Leave blank to keep current password</small>
            <?php endif; ?>
            <label for="registered_course">Registered Course</label>
            <select id="registered_course" name="registered_course" required>
                <?php 
                // Reset the courses result pointer
                mysqli_data_seek($result_courses, 0);
                while ($course = mysqli_fetch_assoc($result_courses)): 
                ?>
                    <option value="<?php echo $course['id']; ?>" <?php echo isset($editStudent) && $editStudent['registered_course'] == $course['id'] ? 'selected' : ''; ?>><?php echo $course['course_name']; ?></option>
                <?php endwhile; ?>
            </select>
            <label for="batch_id">Batch</label>
            <select id="batch_id" name="batch_id" required>
                <?php 
                // Reset the batches result pointer
                mysqli_data_seek($result_batches, 0);
                while ($batch = mysqli_fetch_assoc($result_batches)): 
                ?>
                    <option value="<?php echo $batch['batch_id']; ?>" <?php echo isset($editStudent) && $editStudent['batch_id'] == $batch['batch_id'] ? 'selected' : ''; ?>><?php echo $batch['batch_name']; ?></option>
                <?php endwhile; ?>
            </select>
            <label for="birth_date">Birth Date</label>
            <input type="date" id="birth_date" name="birth_date" value="<?php echo isset($editStudent) ? $editStudent['birth_date'] : ''; ?>" required>
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="Male" <?php echo isset($editStudent) && $editStudent['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo isset($editStudent) && $editStudent['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo isset($editStudent) && $editStudent['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
            <button type="submit"><?php echo isset($editStudent) ? 'Update Student' : 'Add Student'; ?></button>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Username</th>
                <th>Registered Course</th>
                <th>Batch</th>
                <th>Birth Date</th>
                <th>Gender</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Reset the student result pointer
            mysqli_data_seek($result, 0);
            while ($row = mysqli_fetch_assoc($result)): 
            ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['first_name']; ?></td>
                    <td><?php echo $row['last_name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['username']; ?></td>
                    <td><?php 
                        $course_query = "SELECT course_name FROM courses WHERE id=" . $row['registered_course'];
                        $course_result = mysqli_query($conn, $course_query);
                        $course = mysqli_fetch_assoc($course_result);
                        echo $course ? $course['course_name'] : 'N/A'; 
                    ?></td>
                    <td><?php 
                        $batch_query = "SELECT batch_name FROM batch WHERE batch_id=" . $row['batch_id'];
                        $batch_result = mysqli_query($conn, $batch_query);
                        $batch = mysqli_fetch_assoc($batch_result);
                        echo $batch ? $batch['batch_name'] : 'N/A'; 
                    ?></td>
                    <td><?php echo $row['birth_date']; ?></td>
                    <td><?php echo $row['gender']; ?></td>
                    <td class="actions">
                        <a href="st_reg.php?edit=<?php echo $row['id']; ?>"><button class="edit-button">Edit</button></a>
                        <button class="delete-button" onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
        function confirmDelete(studentId) {
            if (confirm('Are you sure you want to delete this student? This will also delete all associated course enrollments.')) {
                window.location.href = 'st_reg.php?delete=' + studentId;
            }
        }

        // Show error message if there is one
        <?php if(isset($error_message)): ?>
        document.getElementById('error-message').style.display = 'block';
        document.getElementById('error-message').innerText = '<?php echo $error_message; ?>';
        <?php endif; ?>
    </script>
</body>
</html>