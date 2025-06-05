<?php
// delete_plan.php
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

// Check if a plan id is provided
if (isset($_GET['id'])) {
    $plan_id = (int)$_GET['id'];

    // SQL query to delete the plan from the database
    $sql = "DELETE FROM plans WHERE id = $plan_id";
    
    if ($conn->query($sql) === TRUE) {
        // Delete the plan from all user profiles
        $deleteUserPlan = "DELETE FROM user_plans WHERE plan_id = $plan_id";
        if ($conn->query($deleteUserPlan) === TRUE) {
            header("Location: admin.php?msg=Plan deleted and removed from user profiles!");
        } else {
            echo "Error deleting plan from user profiles: " . $conn->error;
        }
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>
