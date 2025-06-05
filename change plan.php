<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// Validate the new plan ID from the URL
if (!isset($_GET['plan_id']) || !is_numeric($_GET['plan_id'])) {
    header("Location: profile.php?message=Invalid+plan+selection");
    exit();
}

$new_plan_id = intval($_GET['plan_id']);

// Connect to the database
$conn = new mysqli("localhost", "root", "", "user_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user has a current subscription
$stmt = $conn->prepare("SELECT plan_id FROM subscriptions WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$current = $result->fetch_assoc();

if (!$current) {
    header("Location: profile.php?message=No+active+subscription+to+change");
    exit();
}

// If the selected plan is the same as current, don't update
if ($current['plan_id'] == $new_plan_id) {
    header("Location: profile.php?message=You+are+already+on+this+plan");
    exit();
}

// Update the subscriptions table
$update = $conn->prepare("UPDATE subscriptions SET plan_id = ?, subscribed_at = NOW() WHERE user_id = ?");
$update->bind_param("ii", $new_plan_id, $user_id);

if ($update->execute()) {
    // ✅ Also update the plan_id in the users table
    $updateUser = $conn->prepare("UPDATE users SET plan_id = ? WHERE id = ?");
    $updateUser->bind_param("ii", $new_plan_id, $user_id);
    $updateUser->execute();

    // Optional: Update the session data
    $_SESSION['user']['plan_id'] = $new_plan_id;

    header("Location: profile.php?message=Subscription+plan+updated+successfully");
} else {
    header("Location: profile.php?message=Failed+to+update+subscription");
}
exit();
?>
