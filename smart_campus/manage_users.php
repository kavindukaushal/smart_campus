<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle Create User
if (isset($_POST['create_user'])) {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $_POST['username'], $_POST['email'], md5($_POST['password']), $_POST['role']);
    $stmt->execute();
    header("Location: manage_users.php");
    exit;
}
// Handle Edit User - Fetch User Details
$edit_id = "";
$edit_username = "";
$edit_email = "";
$edit_role = "";

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $edit_username = $user['username'];
        $edit_email = $user['email'];
        $edit_role = $user['role'];
    }
}

// Handle Update User
if (isset($_POST['edit_user'])) {
    $update_id = $_POST['id'];
    $update_username = $_POST['username'];
    $update_email = $_POST['email'];
    $update_role = $_POST['role'];

    // If a new password is set, update it; otherwise, keep the old password
    if (!empty($_POST['password'])) {
        $update_password = md5($_POST['password']);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password_hash=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $update_username, $update_email, $update_password, $update_role, $update_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $update_username, $update_email, $update_role, $update_id);
    }

    $stmt->execute();
    header("Location: manage_users.php");
    exit;
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_users.php");
    exit;
}

// Fetch Users
$user_query = "SELECT * FROM users";
$user_result = $conn->query($user_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
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


<h2>Manage Users</h2>

<!-- User Creation/Editing Form -->
<form method="POST">
    <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
    <input type="text" name="username" placeholder="Username" required value="<?php echo $edit_username; ?>">
    <input type="email" name="email" placeholder="Email" required value="<?php echo $edit_email; ?>">
    <input type="password" name="password" placeholder="New Password (Leave empty to keep current)">
    <select name="role">
        <option value="student" <?php if ($edit_role == "student") echo "selected"; ?>>Student</option>
        <option value="staff" <?php if ($edit_role == "staff") echo "selected"; ?>>Staff</option>
        <option value="lecturer" <?php if ($edit_role == "lecturer") echo "selected"; ?>>Lecturer</option>
    </select>
    <?php if ($edit_id): ?>
        <button type="submit" name="edit_user" class="btn-update">Update User</button>
    <?php else: ?>
        <button type="submit" name="create_user" class="btn-create">Create User</button>
    <?php endif; ?>
</form>

<!-- Users Table -->
<table>
    <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr>
    <?php while ($user = $user_result->fetch_assoc()): ?>
    <tr>
        <td><?php echo $user['id']; ?></td>
        <td><?php echo $user['username']; ?></td>
        <td><?php echo $user['email']; ?></td>
        <td><?php echo ucfirst($user['role']); ?></td>
        <td>
            <a href="manage_users.php?edit=<?php echo $user['id']; ?>" class="btn-edit">Edit</a> | 
            <a href="manage_users.php?delete=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure?');">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<style>
        /* Table Container with Scroll */
.resource-table-container {
    width: 100%;
    max-height: 400px; 
    overflow-y: auto; 
    margin-top: 20px;
}

/* Resource Table Styling */
.resource-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background-color: #ffffff;
}

.resource-table th, .resource-table td {
    
    padding: 12px;
    text-align: left;
    border: 1px solid #8686AC;
}

.resource-table th {
    
    background-color: #0F0E47;
    color: #fff;
}

.resource-table tr:nth-child(even) {
    background-color: #f4f4f4;
}

.resource-table tr:hover {
    background-color: #e1e1e1;
}

/* Action Buttons */
.btn-delete, .btn-edit {
    padding: 6px 12px;
    margin-right: 5px;
    border: none;
    font-size: 14px;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.3s;
}
/* Edit Button */
.btn-edit {
    background-color: #0F0E47;
    color: white;
}

.btn-edit:hover {
    background-color: #505081;
}

/* Delete Button */
.btn-delete {
    background-color: red;
    color: white;
}

.btn-delete:hover {
    background-color: #ff4d4d;
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