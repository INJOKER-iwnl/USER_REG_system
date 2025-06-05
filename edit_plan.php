<?php
// edit_plan.php
session_start();

// Admin access check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// DB connection
$conn = new mysqli("localhost", "root", "", "user_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get plan details
$plan = null;
if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $result = $conn->query("SELECT * FROM plans WHERE id = '$id'");
    if ($result->num_rows > 0) {
        $plan = $result->fetch_assoc();
    } else {
        header("Location: admin.php");
        exit;
    }
} else {
    header("Location: admin.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_name = $conn->real_escape_string($_POST['plan_name']);
    $speed = $conn->real_escape_string($_POST['speed']);
    $price = $conn->real_escape_string($_POST['price']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $sql = "UPDATE plans SET 
            plan_name = '$plan_name',
            speed = '$speed',
            price = '$price',
            description = '$description'
            WHERE id = '$id'";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: admin.php");
        exit;
    } else {
        $error = "Error updating plan: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Plan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 30px;
            background-image: url('img/bgp.png');
            background-size: cover;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-position: center;
            color: #111;
        }
        
        .edit-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        input, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        
        textarea {
            min-height: 100px;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-save {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-save:hover {
            background-color: #45a049;
        }
        
        .btn-cancel {
            background-color: #f44336;
            color: white;
        }
        
        .btn-cancel:hover {
            background-color: #d32f2f;
        }
        
        .error {
            color: #f44336;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <h1>Edit Plan: <?= htmlspecialchars($plan['plan_name']) ?></h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="plan_name">Plan Name</label>
                <input type="text" id="plan_name" name="plan_name" value="<?= htmlspecialchars($plan['plan_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="speed">Speed (Mbps)</label>
                <input type="number" id="speed" name="speed" value="<?= htmlspecialchars($plan['speed']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="price">Price (Rs.)</label>
                <input type="number" id="price" name="price" step="0.01" value="<?= htmlspecialchars($plan['price']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?= htmlspecialchars($plan['description']) ?></textarea>
            </div>
            
            <div class="form-actions">
                <a href="admin.php" class="btn btn-cancel">Cancel</a>
                <button type="submit" class="btn btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</body>
</html>