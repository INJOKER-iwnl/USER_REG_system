<?php
// delete_user.php
session_start();

// Admin access check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit;
}

// DB connection
$conn = new mysqli("localhost", "root", "", "user_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Delete user
$id = $conn->real_escape_string($_GET['id']);
$sql = "DELETE FROM users WHERE id = '$id'";

if ($conn->query($sql)) {
    header("Location: admin.php?delete_success=1");
} else {
    header("Location: admin.php?delete_error=1");
}

$conn->close();
exit;
?>
