<?php
// Assuming you have database connection already established
require 'db_connect.php';


// Fetch all batches from the database
$query = "SELECT * FROM batch";
$result = mysqli_query($conn, $query);

// Handle form submission (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batch_name = $_POST['batch_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $mode = $_POST['mode'];
    $status = $_POST['status'];

    // If there is a batch_id (Edit), update the record
    if (isset($_POST['batch_id']) && !empty($_POST['batch_id'])) {
        $batch_id = $_POST['batch_id'];
        $query = "UPDATE batch SET batch_name='$batch_name', start_date='$start_date', end_date='$end_date', mode='$mode', status='$status' WHERE batch_id=$batch_id";
        mysqli_query($conn, $query);
    } else {
        // Otherwise, insert a new batch (batch_id will be auto-incremented)
        $query = "INSERT INTO batch (batch_name, start_date, end_date, mode, status) VALUES ('$batch_name', '$start_date', '$end_date', '$mode', '$status')";
        mysqli_query($conn, $query);
    }

    header('Location: manage_batches.php'); // Refresh page to show updates
    exit();
}

// Handle delete action
if (isset($_GET['delete'])) {
    $batch_id = $_GET['delete'];
    $query = "DELETE FROM batch WHERE batch_id=$batch_id";
    mysqli_query($conn, $query);
    header('Location: manage_batches.php'); // Refresh page after deletion
    exit();
}

// Fetch single batch for editing
$editBatch = null;
if (isset($_GET['edit'])) {
    $batch_id = $_GET['edit'];
    $query = "SELECT * FROM batch WHERE batch_id=$batch_id";
    $editBatch = mysqli_fetch_assoc(mysqli_query($conn, $query));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="css/style.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Batches</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color:white;
            color: #fff;
            margin: 0;
            padding: 0;
        }
        h1 {
            color:rgb(6, 6, 33);
            text-align: center;
        }
        .form-container {

           color: #0F0E47;          ;
            padding: 20px;
            margin: 20px auto;
            width: 50%;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #8686AC;
            border-radius: 4px;
           
            color: #fff;
        }
        button {
            background-color: #505081;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #8686AC;
        }
        table {
            color:rgb(6, 6, 41);
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
        }
        th, td {
            background-color:rgb(255, 255, 255);
            padding: 12px;
            text-align: left;
            border: 1px solid #8686AC;
        }
        th {
            background-color:rgb(13, 13, 53);
        }
        tr:nth-child(even) {
            background-color: #272757;
        }
        /* Actions buttons */   
.actions button {
    padding: 3px 8px; /* Smaller padding */
    margin: 0 2px; /* Smaller margin */
    border: none;
    cursor: pointer;
    font-size: 12px; /* Smaller font size */
    display: inline-block; /* Ensure buttons stay inline */
    width: auto; /* Adjust width to fit content */
}

/* Edit Button Style */
.actions button#edit {
    background-color: #0F0E47; /* Dark blue color */
    color: white;
    border-radius: 3px; /* Slightly rounded corners */
}

.actions button#edit:hover {
    background-color: #505081; /* Lighter shade for hover effect */
}

/* Delete Button Style */
.actions button#delete {
    background-color: red; /* Red color for delete button */
    color: white;
    border-radius: 3px; /* Slightly rounded corners */
}

.actions button#delete:hover {
    background-color: #ff4d4d; /* Lighter red shade for hover effect */
}
.actions {
    display: flex;
    justify-content: center;
    gap: 5px; /* Space between buttons */
}

.actions button {
    padding: 3px 8px; 
    font-size: 14px;
    border: none;
    cursor: pointer;
}

/* Optional: Centering buttons in the cell vertically */
td .actions {
    display: flex;
    align-items: center;
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

   
    <h1>Manage Batches</h1>

 

    <div class="form-container">
        <h2><?php echo isset($editBatch) ? 'Edit Batch' : 'Add New Batch'; ?></h2>
        <form action="manage_batches.php" method="POST">
            <!-- Hidden field for batch_id (needed only for editing) -->
            <input type="hidden" name="batch_id" value="<?php echo isset($editBatch) ? $editBatch['batch_id'] : ''; ?>">

            <label for="batch_name">Batch Name</label>
            <input type="text" id="batch_name" name="batch_name" value="<?php echo isset($editBatch) ? $editBatch['batch_name'] : ''; ?>" required>

            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo isset($editBatch) ? $editBatch['start_date'] : ''; ?>" required>

            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo isset($editBatch) ? $editBatch['end_date'] : ''; ?>" required>

            <label for="mode">Mode</label>
            <select id="mode" name="mode" required>
                <option value="Online" <?php echo isset($editBatch) && $editBatch['mode'] == 'Online' ? 'selected' : ''; ?>>Online</option>
                <option value="Physical" <?php echo isset($editBatch) && $editBatch['mode'] == 'Physical' ? 'selected' : ''; ?>>Physical</option>
            </select>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="Active" <?php echo isset($editBatch) && $editBatch['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo isset($editBatch) && $editBatch['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>

            <button type="submit"><?php echo isset($editBatch) ? 'Edit Batch' : 'Add Batch'; ?></button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Batch ID</th>
                <th>Batch Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Mode</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['batch_id']; ?></td> <!-- Displaying batch_id -->
                    <td><?php echo $row['batch_name']; ?></td>
                    <td><?php echo $row['start_date']; ?></td>
                    <td><?php echo $row['end_date']; ?></td>
                    <td><?php echo $row['mode']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td class="actions">
                        <!-- Edit Button with batch_id as parameter -->
                        <a id="edit" href="manage_batches.php?edit=<?php echo $row['batch_id']; ?>"><button id="edit">Edit</button></a><br>
                        <!-- Delete Button with batch_id as parameter -->
                        <a id="delete" href="manage_batches.php?delete=<?php echo $row['batch_id']; ?>"><button id="delete">Delete</button></a>
                    </td>

                    
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    

</body>
</html>