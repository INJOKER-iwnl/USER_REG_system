<?php
// add_plan.php
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

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize the form data
    $plan_name = $conn->real_escape_string($_POST['plan_name']);
    $speed = (int)$_POST['speed'];
    $price = (float)$_POST['price'];
    $description = $conn->real_escape_string($_POST['description']);

    // SQL query to insert the new plan into the database
    $sql = "INSERT INTO plans (plan_name, speed, price, description, created_at) 
            VALUES ('$plan_name', $speed, $price, '$description', NOW())";

    if ($conn->query($sql) === TRUE) {
        // Optionally, assign the new plan to all users (or a specific group of users)
        // Query to assign the plan to users
        $plan_id = $conn->insert_id; // Get the newly inserted plan ID

        // Assign new plan to all users (or apply conditions as needed)
        $updateUsers = "INSERT INTO user_plans (user_id, plan_id) SELECT id, $plan_id FROM users";
        if ($conn->query($updateUsers) === TRUE) {
            header("Location: admin.php?msg=Plan added and assigned to users successfully!");
        } else {
            echo "Error assigning plan to users: " . $conn->error;
        }
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>
