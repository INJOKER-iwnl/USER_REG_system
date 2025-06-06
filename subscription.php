<?php
session_start();

require_once 'db.php';
require_once 'repositories/UserRepository.php';
require_once 'repositories/SubscriptionRepository.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];

$conn = DB::getInstance();
$subRepo = new SubscriptionRepository($conn);

// Get the user's active subscription
$currentPlanId = null;
$subscription_id = null;
$currentSubscription = $subRepo->getSubscriptionByUserId($user_id);
if ($currentSubscription) {
    $currentPlanId = $currentSubscription['plan_id'];
    $subscription_id = $currentSubscription['id'];
}

$search = $_GET['search'] ?? '';
$speedFilter = $_GET['speed_filter'] ?? '';

$query = "SELECT * FROM plans WHERE 1";
if (!empty($search)) {
    $searchEsc = $conn->real_escape_string($search);
    $query .= " AND (plan_name LIKE '%$searchEsc%' OR speed LIKE '%$searchEsc%' OR price LIKE '%$searchEsc%' OR description LIKE '%$searchEsc%')";
}
if (!empty($speedFilter)) {
    $speedFilterEsc = $conn->real_escape_string($speedFilter);
    $query .= " AND speed = '$speedFilterEsc'";
}

$plans = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Choose a Subscription Plan</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Segoe UI', sans-serif;
        height: 100vh;
        background-image: url('img/sky.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color: #fff;
    }
    .overlay {
        background-color: rgba(0, 0, 0, 0.75);
        min-height: 100vh;
        padding: 40px 20px;
    }
    .container {
        max-width: 1200px;
        margin: auto;
    }
    h2, h3 {
        text-align: center;
        color: #00ffff;
        margin-bottom: 15px;
    }
    .filter-form {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
        margin: 30px 0;
    }
    input[type="text"], select {
        padding: 10px;
        width: 220px;
        border: none;
        border-radius: 5px;
    }
    button {
        padding: 10px 20px;
        background-color: #00bfff;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
    }
    button[disabled] {
        background-color: #aaa;
        cursor: not-allowed;
    }
    .plans {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
    }
    .plan-card {
        background-color: rgba(255, 255, 255, 0.1);
        border: 2px solid transparent;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 0 12px rgba(0, 255, 255, 0.3);
        transition: 0.3s;
    }
    .plan-card.highlight {
        border-color: #28a745;
        box-shadow: 0 0 20px rgba(40, 167, 69, 0.7);
    }
    .plan-name {
        font-size: 22px;
        color: #00ffff;
        margin-bottom: 10px;
    }
    .plan-speed {
        font-size: 16px;
        color: #eee;
    }
    .plan-price {
        font-size: 24px;
        color: #28a745;
        margin: 10px 0;
        font-weight: bold;
    }
    .plan-desc {
        font-size: 14px;
        color: #ccc;
        min-height: 60px;
        margin-bottom: 15px;
    }
    .cancel-button, .edit-button {
        margin-left: 10px;
        border: none;
        padding: 10px 15px;
        border-radius: 6px;
        color: white;
        cursor: pointer;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
    }
    .cancel-button {
        background-color: #ff4c4c;
    }
    .cancel-button:hover {
        background-color: #e04343;
    }
    .edit-button {
        background-color: #007bff;
    }
    .edit-button:hover {
        background-color: #0056b3;
    }
    .footer {
        text-align: center;
        margin-top: 40px;
    }
    .footer a {
        color: #00ffff;
        margin: 0 15px;
        text-decoration: none;
    }
    .footer a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
<div class="overlay">
    <div class="container">
        <h2>Welcome, <?= htmlspecialchars($user['name']) ?>!</h2>
        <h3>Select Your Internet Subscription Plan</h3>

        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search plans..." value="<?= htmlspecialchars($search) ?>">
          <select name="speed_filter">
    <option value="">All Speeds</option>
    <option value="20" <?= $speedFilter === '20' ? 'selected' : '' ?>>20 Mbps</option>
    <option value="30" <?= $speedFilter === '30' ? 'selected' : '' ?>>30 Mbps</option>
    <option value="34" <?= $speedFilter === '34' ? 'selected' : '' ?>>34 Mbps</option>
    <option value="50" <?= $speedFilter === '50' ? 'selected' : '' ?>>50 Mbps</option>
    <option value="80" <?= $speedFilter === '80' ? 'selected' : '' ?>>80 Mbps</option>
    <option value="90" <?= $speedFilter === '90' ? 'selected' : '' ?>>90 Mbps</option>
    <option value="150" <?= $speedFilter === '150' ? 'selected' : '' ?>>150 Mbps</option>
</select>

            <button type="submit">Apply</button>
        </form>

        <form action="subscribe.php" method="post">
            <div class="plans">
                <?php if ($plans && $plans->num_rows > 0): ?>
                    <?php while ($plan = $plans->fetch_assoc()): ?>
                        <div class="plan-card <?= ($plan['id'] == $currentPlanId) ? 'highlight' : '' ?>">
                            <div class="plan-name"><?= htmlspecialchars($plan['plan_name']) ?></div>
                            <div class="plan-speed"><?= htmlspecialchars($plan['speed']) ?> Speed</div>
                            <div class="plan-price">Rs. <?= htmlspecialchars($plan['price']) ?>/month</div>
                            <div class="plan-desc"><?= htmlspecialchars($plan['description']) ?></div>

                            <?php if ($plan['id'] == $currentPlanId): ?>
                                <button type="button" disabled>Current Plan</button>
                                <a
                                  href="cancel_subscription.php?sub_id=<?= urlencode($subscription_id) ?>"
                                  class="cancel-button"
                                  onclick="return confirm('Are you sure you want to cancel your subscription?');"
                                >Cancel</a>

                                <a
                                  href="edit_subscription.php?sub_id=<?= urlencode($subscription_id) ?>"
                                  class="edit-button"
                                >Edit</a>

                            <?php else: ?>
                                <button
                                  type="submit"
                                  name="plan_id"
                                  value="<?= $plan['id'] ?>"
                                  onclick="return confirm('Are you sure you want to subscribe to this plan?');"
                                >Subscribe</button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align:center;">No subscription plans found.</p>
                <?php endif; ?>
            </div>
        </form>

        <div class="footer">
            <a href="profile.php">Back to Profile</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>
</body>
</html>
