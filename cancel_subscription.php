<?php
session_start();

require_once 'db.php';
require_once 'repositories/SubscriptionRepository.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$sub_id = isset($_GET['sub_id']) ? intval($_GET['sub_id']) : 0;

$conn = DB::getInstance();
$subscriptionRepo = new SubscriptionRepository($conn);

// ✅ Allow fetching even if already cancelled (to avoid false "invalid" errors)
$subscription = $subscriptionRepo->getSubscriptionById($sub_id, $user_id, true);

if (!$subscription) {
    echo "Invalid subscription ID or you do not have permission.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $wasCancelled = $subscription['cancelled_at'] !== null;

    if ($wasCancelled) {
        header("Location: subscription.php?message=Subscription+was+already+cancelled");
        exit();
    }

    if ($subscriptionRepo->cancelSubscription($sub_id, $user_id)) {
        header("Location: subscription.php?message=Subscription+cancelled+successfully");
        exit();
    } else {
        echo "Error cancelling subscription.";
    }
} else {
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
            <a href="subscription.php">No, Go Back</a>
        </form>
    </body>
    </html>
    <?php
}
?>
