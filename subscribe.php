<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once 'db.php';
require_once 'repositories/UserRepository.php';
require_once 'repositories/SubscriptionRepository.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$conn = DB::getInstance();
$userRepo = new UserRepository($conn);
$subRepo = new SubscriptionRepository($conn);

$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];

    // Handle Subscribe action
    if (isset($_POST['plan_id'])) {
        $plan_id = intval($_POST['plan_id']);

        $activeSubscriptions = $subRepo->getUserSubscriptions($user_id);

        if (!empty($activeSubscriptions)) {
            $errorMsg = "You already have an active subscription. Please cancel it first or use the Edit option to change your plan.";
        } else {
            $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, plan_id, subscribed_at, status) VALUES (?, ?, NOW(), 'active')");
            $stmt->bind_param("ii", $user_id, $plan_id);

            if ($stmt->execute() && $userRepo->updateUserPlan($user_id, $plan_id)) {
                $_SESSION['user']['plan_id'] = $plan_id;

                $stmt->close();
                $conn->close();

                header("Location: subscription.php?message=Subscription+successful");
                exit();
            } else {
                $errorMsg = "Error subscribing: " . $stmt->error;
                $stmt->close();
            }
        }
    }

    // Handle Cancel action
    elseif (isset($_POST['action']) && strpos($_POST['action'], 'cancel_') === 0) {
        $planId = intval(substr($_POST['action'], 7));
        $activeSub = $subRepo->getActiveSubscriptionByPlan($user_id, $planId);

        if ($activeSub) {
            $subRepo->cancelSubscription($activeSub['id'], $user_id);
            $userRepo->updateUserPlan($user_id, null);
            $_SESSION['user']['plan_id'] = null;

            $conn->close();
            header("Location: subscription.php?message=Subscription+canceled+successfully");
            exit();
        } else {
            $errorMsg = "Error canceling subscription.";
        }
    }

    // Handle Edit action
    elseif (isset($_POST['action']) && strpos($_POST['action'], 'edit_') === 0) {
        $planId = intval(substr($_POST['action'], 5));
        $conn->close();
        header("Location: edit_subscription.php?plan_id=$planId");
        exit();
    } else {
        $errorMsg = "Invalid action.";
    }
} else {
    $errorMsg = "Invalid request method.";
}
?>

<?php if (!empty($errorMsg)): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscription Error</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f2f4f8;
            margin: 0;
            padding: 0;
        }
        .message-container {
            max-width: 600px;
            margin: 100px auto;
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .error {
            color: #d9534f;
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .back-button {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="message-container">
        <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
        <a href="subscription.php" class="back-button">← Back to Plans</a>
    </div>
</body>
</html>
<?php endif; ?>
