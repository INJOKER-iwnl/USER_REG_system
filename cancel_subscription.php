<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$sub_id = isset($_GET['sub_id']) ? intval($_GET['sub_id']) : 0;

$conn = new mysqli("localhost", "root", "", "user_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the subscription exists and belongs to this user
$check_sql = "SELECT id FROM subscriptions WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $sub_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo "Invalid subscription ID or you do not have permission.";
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    $delete_sql = "DELETE FROM subscriptions WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $sub_id, $user_id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: profile.php?message=Subscription+cancelled+successfully");
        exit();
    } else {
        echo "Error cancelling subscription: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
} else {
    // Show confirmation form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>Confirm Cancel Subscription</title>
        <style>
            body { font-family: Arial, sans-serif; background: #222; color: #eee; text-align: center; padding: 40px; }
            button { padding: 10px 20px; font-size: 16px; margin: 10px; border-radius: 5px; cursor: pointer; }
            button[name="confirm"] { background-color: #ff5555; color: white; border: none; }
            a { color: #00ffff; text-decoration: none; font-weight: bold; margin-left: 20px; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <h2>Are you sure you want to cancel your subscription?</h2>
        <form method="post" action="cancel_subscription.php?sub_id=<?= htmlspecialchars($sub_id) ?>">
            <button type="submit" name="confirm" value="yes">Yes, Cancel</button>
            <a href="profile.php">No, Go Back</a>
        </form>
    </body>
    </html>
    <?php
}
?>
