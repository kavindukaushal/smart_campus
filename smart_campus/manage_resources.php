<?php
session_start();
include 'db_connect.php';



if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle Create Resource
if (isset($_POST['create_resource'])) {
    $stmt = $conn->prepare("INSERT INTO resources (resource_name, category, status) VALUES (?, ?, 'available')");
    $stmt->bind_param("ss", $_POST['resource_name'], $_POST['category']);
    $stmt->execute();

    $admin_id = $_SESSION['admin_id'];
    $conn->query("INSERT INTO security_logs (admin_id, action) VALUES ($admin_id, 'Created Resource: {$_POST['resource_name']}')");

    header("Location: manage_resources.php");
    exit;
}

// Handle Edit Resource
if (isset($_POST['edit_resource'])) {
    $stmt = $conn->prepare("UPDATE resources SET resource_name=?, category=?, status=? WHERE id=?");
    $stmt->bind_param("sssi", $_POST['resource_name'], $_POST['category'], $_POST['status'], $_POST['id']);
    $stmt->execute();

    $admin_id = $_SESSION['admin_id'];
    $conn->query("INSERT INTO security_logs (admin_id, action) VALUES ($admin_id, 'Edited Resource ID: {$_POST['id']}')");

    header("Location: manage_resources.php");
    exit;
}

// Handle Delete Resource
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $conn->query("DELETE FROM resources WHERE id = $delete_id");

    $admin_id = $_SESSION['admin_id'];
    $conn->query("INSERT INTO security_logs (admin_id, action) VALUES ($admin_id, 'Deleted Resource ID: $delete_id')");

    header("Location: manage_resources.php");
    exit;
}

// Fetch Resources
$resource_query = "SELECT * FROM resources";
$resource_result = $conn->query($resource_query);

// If editing, fetch resource details
$edit_resource = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_resource = $conn->query("SELECT * FROM resources WHERE id = $edit_id")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resources</title>
    <link rel="stylesheet" href="css/style.css">

    <link rel="stylesheet" href="style.css">
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

    <h2>Manage Resources</h2>

    <!-- Create or Edit Resource Form -->
    <form method="POST" class="resource-form">
        <?php if ($edit_resource): ?>
            <input type="hidden" name="id" value="<?php echo $edit_resource['id']; ?>">
            <button type="submit" name="edit_resource" class="btn-update">Confirm Edit</button>
        <?php else: ?>
            <button type="submit" name="create_resource" class="btn-create">Create Resource</button>
        <?php endif; ?>

        <input type="text" name="resource_name" placeholder="Resource Name" required value="<?php echo $edit_resource['resource_name'] ?? ''; ?>">
        
        <select name="category" required>
            <option value="classroom" <?php echo ($edit_resource && $edit_resource['category'] == 'classroom') ? 'selected' : ''; ?>>Classroom</option>
            <option value="lab" <?php echo ($edit_resource && $edit_resource['category'] == 'lab') ? 'selected' : ''; ?>>Lab</option>
            <option value="equipment" <?php echo ($edit_resource && $edit_resource['category'] == 'equipment') ? 'selected' : ''; ?>>Equipment</option>
        </select>

        <select name="status" required>
            <option value="available" <?php echo ($edit_resource && $edit_resource['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
            <option value="reserved" <?php echo ($edit_resource && $edit_resource['status'] == 'reserved') ? 'selected' : ''; ?>>Reserved</option>
        </select>
    </form>

   <!-- Display Resources -->
<div class="resource-table-container">
    <table class="resource-table">
        <tr>
            <th>ID</th>
            <th>Resource Name</th>
            <th>Category</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while ($resource = $resource_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $resource['id']; ?></td>
            <td><?php echo $resource['resource_name']; ?></td>
            <td><?php echo ucfirst($resource['category']); ?></td>
            <td><?php echo ucfirst($resource['status']); ?></td>
            <td>        
                <a href="manage_resources.php?delete=<?php echo $resource['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure?');">Delete</a>
                <a href="manage_resources.php?edit=<?php echo $resource['id']; ?>" class="btn-edit">Edit</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>


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