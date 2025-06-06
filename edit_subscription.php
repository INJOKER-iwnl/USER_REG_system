<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';
require_once 'repositories/SubscriptionRepository.php';

$user_id = $_SESSION['user']['id'];
$subscription_id = $_GET['sub_id'] ?? null;

$conn = DB::getInstance();
$subscriptionRepo = new SubscriptionRepository($conn);

// Fetch current subscription to verify ownership
$current = $subscriptionRepo->getSubscriptionById((int)$subscription_id, (int)$user_id);
if (!$current) {
    die("Subscription not found or unauthorized.");
}

// Get all plans
$plansResult = $conn->query("SELECT * FROM plans");

// If form submitted, update plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_plan_id'])) {
    $newPlanId = (int)$_POST['new_plan_id'];

    $updated = $subscriptionRepo->updateSubscriptionPlan((int)$subscription_id, (int)$user_id, $newPlanId);
    if ($updated) {
        header("Location: subscription.php?message=Subscription updated.");
        exit();
    } else {
        echo "Failed to update subscription.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Subscription</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-image: url('img/sky.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            background: rgba(0, 0, 0, 0.6);
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #00ffff;
        }

        label {
            font-size: 16px;
            display: block;
            margin-bottom: 10px;
        }

        select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            margin-bottom: 20px;
        }

        button {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #218838;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #00ffff;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="card">
    <h2>Edit Your Subscription</h2>
    <form method="post">
        <label for="new_plan_id">Choose a new plan:</label>
        <select name="new_plan_id" id="new_plan_id" required>
            <?php while ($plan = $plansResult->fetch_assoc()): ?>
                <option value="<?= $plan['id'] ?>" <?= $plan['id'] == $current['plan_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($plan['plan_name']) ?> - Rs. <?= htmlspecialchars($plan['price']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit">Update Plan</button>
    </form>
    <a href="subscription.php" class="back-link">← Back to Subscriptions</a>
</div>
</body>
</html>
