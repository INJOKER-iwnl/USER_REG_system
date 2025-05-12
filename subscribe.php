<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "user_system");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_id'])) {
    $user_id = $_SESSION['user']['id'];
    $plan_id = intval($_POST['plan_id']);

    // Check if user already has a subscription
    $check = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $existing = $check->get_result();

    if ($existing->num_rows > 0) {
        // Update existing subscription
        $stmt = $conn->prepare("UPDATE subscriptions SET plan_id = ?, subscribed_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("ii", $plan_id, $user_id);
    } else {
        // Insert new subscription
        $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, plan_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $plan_id);
    }

    // Execute the statement
    if ($stmt->execute()) {
        // Close connections
        $stmt->close();
        $conn->close();
        // Redirect to profile with message
        header("Location: profile.php?message=Subscription+updated+successfully");
        exit();
    } else {
        echo "Error processing subscription: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
