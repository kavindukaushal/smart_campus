<?php
session_start();
include 'db_connect.php';

// Fetch lecturer profile details
$lecturer_id = $_SESSION['lecturer_id'];
$lecturer_details = $conn->query("SELECT * FROM lecturer WHERE id = '$lecturer_id'")->fetch_assoc();

// Handle form submission and update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get data from the form
    $title = $_POST['title'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $teaching_qualification = $_POST['teaching_qualification'];
    $position = $_POST['position'];
    $email = $_POST['email'];

    // Update profile in the database
    $stmt = $conn->prepare("UPDATE lecturer SET title = ?, first_name = ?, last_name = ?, gender = ?, birth_date = ?, teaching_qualification = ?, position = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssssssssi", $title, $first_name, $last_name, $gender, $birth_date, $teaching_qualification, $position, $email, $lecturer_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_msg'] = "Failed to update profile. Please try again.";
    }

    // Redirect back to the profile page after update
    header("Location: lec_view_profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Profile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #4e73df;
            --text-color: #5a5c69;
            --heading-color: #2e59d9;
            --border-color: #e3e6f0;
            --card-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15);
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }

        .profile-header h2 {
            color: var(--heading-color);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 2rem;
            color: #0F0E47;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            padding: 0;
            
         
        }

        .profile-banner {
            height: 120px;
            background: linear-gradient(135deg,rgb(11, 14, 74),rgb(71, 73, 165));
            position: relative;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            position: absolute;
            top: 60px;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 5px solid white;
        }

        .profile-avatar i {
            font-size: 50px;
            color: var(--accent-color);
        }

        .profile-content {
            padding: 90px 30px 30px;
           
        }

        .profile-item {
            padding: 15px;
            background-color: #f8f9fc;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .profile-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transform: translateY(-3px);
        }

        .profile-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
            display: block;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid var(--border-color);
            padding: 10px 15px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }

        .btn {
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: #0F0E47;
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #3a5fd9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 83, 223, 0.4);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #17a673;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28,200,138,0.4);
        }

        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
        }

        .btn-secondary:hover {
            background-color: #717384;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(133,135,150,0.4);
        }

        .alert {
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: rgba(28,200,138,0.1);
            border-left: 4px solid var(--success-color);
            color: #1cc88a;
        }

        .alert-danger {
            background-color: rgba(231,74,59,0.1);
            border-left: 4px solid var(--danger-color);
            color: #e74a3b;
        }

        .btn-close {
            margin-left: auto;
        }

        .text-muted {
            color: #858796 !important;
        }

        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-info-grid {
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
            
            .profile-content {
                padding-top: 70px;
            }

            .btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
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

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Alert messages -->
                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success_msg']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_msg']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error_msg']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_msg']); ?>
                <?php endif; ?>

                <div class="profile-card">
                    <div class="profile-banner"></div>
                    <div class="profile-avatar">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>

                    <div class="profile-content">
                        <!-- If not in editing mode, display the profile -->
                        <?php if (!isset($_GET['edit']) || $_GET['edit'] !== 'true'): ?>
                            <div class="profile-header d-flex justify-content-between align-items-center">
                                <h2><i class="fas fa-user-circle me-2"></i>Lecturer Profile</h2>
                                <a href="lec_view_profile.php?edit=true" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </a>
                            </div>

                            <div class="profile-info-grid mt-4">
                                <div class="profile-item">
                                    <span class="profile-label"><i class="fas fa-tag me-2"></i>Title</span>
                                    <div class="fs-5"><?php echo htmlspecialchars($lecturer_details['title']); ?></div>
                                </div>
                                <div class="profile-item">
                                    <span class="profile-label"><i class="fas fa-user me-2"></i>First Name</span>
                                    <div class="fs-5"><?php echo htmlspecialchars($lecturer_details['first_name']); ?></div>
                                </div>
                                <div class="profile-item">
                                    <span class="profile-label"><i class="fas fa-user me-2"></i>Last Name</span>
                                    <div class="fs-5"><?php echo htmlspecialchars($lecturer_details['last_name']); ?></div>
                                </div>
                                <div class="profile-item">
                                    <span class="profile-label"><i class="fas fa-venus-mars me-2"></i>Gender</span>
                                    <div class="fs-5"><?php echo htmlspecialchars($lecturer_details['gender']); ?></div>
                                </div>
                                <div class="profile-item">
                                    <span class="profile-label"><i class="fas fa-birthday-cake me-2"></i>Birth Date</span>
                                    <div class="fs-5"><?php echo htmlspecialchars($lecturer_details['birth_date']); ?></div>
                                </div>
                                <div class="profile-item">
                                    <span class="profile-label"><i class="fas fa-graduation-cap me-2"></i>Teaching Qualification</span>
                                    <div class="fs-5"><?php echo htmlspecialchars($lecturer_details['teaching_qualification']); ?></div>
                                </div>
                                <div class="profile-item">
                                    <span class="profile-label"><i class="fas fa-briefcase me-2"></i>Position</span>
                                    <div class="fs-5"><?php echo htmlspecialchars($lecturer_details['position']); ?></div>
                                </div>
                                <div class="profile-item">
                                    <span class="profile-label"><i class="fas fa-envelope me-2"></i>Email</span>
                                    <div class="fs-5"><?php echo htmlspecialchars($lecturer_details['email']); ?></div>
                                </div>
                            </div>

                        <!-- If in editing mode, display the edit form -->
                        <?php else: ?>
                            <div class="profile-header">
                                <h2><i class="fas fa-user-edit me-2"></i>Edit Profile</h2>
                                <p class="text-muted">Update your personal information</p>
                            </div>

                            <form method="POST" class="row g-3">
                                <div class="col-md-6">
                                    <div class="profile-item">
                                        <label for="title" class="profile-label"><i class="fas fa-tag me-2"></i>Title:</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($lecturer_details['title']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profile-item">
                                        <label for="gender" class="profile-label"><i class="fas fa-venus-mars me-2"></i>Gender:</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="Male" <?php echo ($lecturer_details['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($lecturer_details['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($lecturer_details['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                        </div>
                        <div class="col-md-6">
                                    <div class="profile-item">
                                        <label for="first_name" class="profile-label"><i class="fas fa-user me-2"></i>First Name:</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($lecturer_details['first_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profile-item">
                                        <label for="last_name" class="profile-label"><i class="fas fa-user me-2"></i>Last Name:</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($lecturer_details['last_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profile-item">
                                        <label for="birth_date" class="profile-label"><i class="fas fa-birthday-cake me-2"></i>Birth Date:</label>
                                        <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($lecturer_details['birth_date']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profile-item">
                                        <label for="email" class="profile-label"><i class="fas fa-envelope me-2"></i>Email:</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($lecturer_details['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profile-item">
                                        <label for="teaching_qualification" class="profile-label"><i class="fas fa-graduation-cap me-2"></i>Teaching Qualification:</label>
                                        <input type="text" class="form-control" id="teaching_qualification" name="teaching_qualification" value="<?php echo htmlspecialchars($lecturer_details['teaching_qualification']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profile-item">
                                        <label for="position" class="profile-label"><i class="fas fa-briefcase me-2"></i>Position:</label>
                                        <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($lecturer_details['position']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-12 mt-4 d-flex justify-content-between">
                                    <a href="lec_view_profile.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>