<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get user data from session
$user = $_SESSION['user'];
$user_id = $user['id'];

// Connect to the database
$conn = new mysqli("localhost", "root", "", "user_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current subscription plan
$currentPlanId = null;
$subQuery = $conn->prepare("SELECT plan_id FROM subscriptions WHERE user_id = ?");
$subQuery->bind_param("i", $user_id);
$subQuery->execute();
$subResult = $subQuery->get_result();
if ($subRow = $subResult->fetch_assoc()) {
    $currentPlanId = $subRow['plan_id'];
}
$subQuery->close();

// Get all plans
$plans = $conn->query("SELECT * FROM plans");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Choose a Subscription Plan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1e1e1e;
            color: white;
            padding: 40px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: #2b2b2b;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.2);
        }
        h2, h3 {
            color: #00ffff;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        th, td {
            padding: 15px;
            border: 1px solid #444;
            background: #333;
            text-align: center;
        }
        th {
            background: #444;
            color: #00ffff;
        }
        tr.highlight {
            background-color: #006666;
        }
        button {
            padding: 10px 20px;
            border: none;
            background: #00bfff;
            color: white;
            border-radius: 6px;
            cursor: pointer;
        }
        button[disabled] {
            background: gray;
            cursor: default;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
        }
        .footer a {
            color: #00ffff;
            margin: 0 15px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
    <h3>Your Subscription & Available Plans</h3>

    <?php if ($plans->num_rows > 0): ?>
        <form action="subscribe.php" method="post">
            <table>
                <tr>
                    <th>Plan Name</th>
                    <th>Speed</th>
                    <th>Price</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
                <?php while ($plan = $plans->fetch_assoc()): ?>
                    <tr class="<?php echo ($plan['id'] == $currentPlanId) ? 'highlight' : ''; ?>">
                        <td><?php echo htmlspecialchars($plan['plan_name']); ?></td>
                        <td><?php echo htmlspecialchars($plan['speed']); ?></td>
                        <td>Rs. <?php echo htmlspecialchars($plan['price']); ?></td>
                        <td><?php echo htmlspecialchars($plan['description']); ?></td>
                        <td>
                            <?php if ($plan['id'] == $currentPlanId): ?>
                                <button type="button" disabled>Current Plan</button>
                            <?php else: ?>
                                <button type="submit" name="plan_id" value="<?php echo $plan['id']; ?>">Subscribe</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </form>
    <?php else: ?>
        <p>No subscription plans available.</p>
    <?php endif; ?>

    <div class="footer">
        <a href="profile.php">Back to Profile</a> |
        <a href="logout.php">Logout</a>
    </div>
</div>

</body>
</html>
