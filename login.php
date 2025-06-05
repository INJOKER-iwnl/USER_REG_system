<?php
// -------------------- SESSION SECURITY --------------------
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Redirect logged-in admin
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

// -------------------- DATABASE CONNECTION --------------------
$conn = new mysqli("localhost", "root", "", "user_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errorMessage = '';
$remainingAttempts = 5;

// -------------------- FORM HANDLING --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $errorMessage = "Please fill in all fields.";
    } else {
        // Check failed login attempts in last 15 mins
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM login_attempts 
            WHERE email = ? 
            AND attempt_time > (NOW() - INTERVAL 15 MINUTE)
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($attempts);
        $stmt->fetch();
        $stmt->close();

        $maxAttempts = 5;
        $remainingAttempts = $maxAttempts - $attempts;

        if ($attempts >= $maxAttempts) {
            $stmt = $conn->prepare("SELECT MAX(attempt_time) FROM login_attempts WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($lastAttemptTime);
            $stmt->fetch();
            $stmt->close();

            $lockoutTimeLeft = strtotime($lastAttemptTime) + 900 - time();
            if ($lockoutTimeLeft > 0) {
                $errorMessage = "Too many failed attempts. Account locked for " . ceil($lockoutTimeLeft / 60) . " minute(s).";
            } else {
                // Clear old attempts after 15 mins
                $stmt = $conn->prepare("DELETE FROM login_attempts WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Validate credentials
        if ($attempts < $maxAttempts && empty($errorMessage)) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                if (!isset($user['is_verified']) || $user['is_verified'] != 1) {
                    $errorMessage = "Your email is not verified. Please check your inbox or contact support.";
                } elseif ($user['role'] === 'admin') {
                    $errorMessage = "Admins must log in through the Admin Login page.";
                } else {
                    // Successful login: clear attempts and set session
                    $delete = $conn->prepare("DELETE FROM login_attempts WHERE email = ?");
                    $delete->bind_param("s", $email);
                    $delete->execute();
                    $delete->close();

                    session_regenerate_id(true);
                    $_SESSION['user'] = $user;

                    header("Location: profile.php");
                    exit;
                }
            } else {
                // Failed login
                $insert = $conn->prepare("INSERT INTO login_attempts (email) VALUES (?)");
                $insert->bind_param("s", $email);
                $insert->execute();
                $insert->close();

                $remainingAttempts--;
                $errorMessage = "Invalid email or password. You have $remainingAttempts attempt(s) remaining.";

                if ($remainingAttempts <= 0) {
                    $errorMessage = "Too many failed attempts. Account locked for 15 minutes.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Login</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            height: 100vh;
            background-image: url('img/bg.jpeg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            width: 400px;
            text-align: center;
        }

        h2 {
            font-size: 2em;
            margin-bottom: 20px;
        }

        input, button {
            width: 100%;
            margin: 15px 0;
            padding: 12px;
            font-size: 1.1em;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        button {
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            font-weight: bold;
        }

        p {
            font-size: 1.1em;
        }

        a {
            color: #007BFF;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Login</h2>

        <?php if ($errorMessage): ?>
            <p class="error"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="register.php">Register here</a></p>
        <a href="index.php" style="display: block; margin-top: 15px;">← Back to Home Page</a>
    </div>
</body>
</html>
