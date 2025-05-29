<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];

$conn = new mysqli("localhost", "root", "", "user_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - ConnectFast</title>
    <style>
        :root {
            --primary: #00bcd4;
            --secondary: #007b8f;
            --bg-dark: #1c1f26;
            --bg-light: #f4f4f4;
            --text-dark: #ffffff;
            --text-light: #111111;
            --sidebar-bg-dark: #2b2f3a;
            --sidebar-bg-light: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            min-height: 100vh;
        }

        body.dark-mode {
            background-color: var(--bg-dark);
            color: var(--text-dark);
        }

        body.light-mode {
            background-color: var(--bg-light);
            color: var(--text-light);
        }

        .sidebar {
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            background-color: var(--sidebar-bg-dark);
            padding: 30px 20px;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar a {
            text-decoration: none;
            color: white;
            margin: 12px 0;
            font-size: 1rem;
            transition: color 0.3s;
        }

        .sidebar a:hover {
            color: var(--primary);
        }

        .main {
            flex: 1;
            padding: 40px;
            margin-left: 250px;
        }

        .container {
            padding: 30px;
            border-radius: 12px;
            background-color: #2a2e38;
            box-shadow: 0 0 15px rgba(0, 188, 212, 0.2);
        }

        h2, h3 {
            margin-bottom: 15px;
        }

        p {
            line-height: 1.6;
        }

        .section {
            margin-top: 30px;
        }

        .section ul {
            list-style-type: disc;
            padding-left: 20px;
            margin-top: 10px;
        }

        .section ul li {
            margin-bottom: 8px;
        }

        blockquote {
            border-left: 4px solid var(--primary);
            background: rgba(0, 188, 212, 0.1);
            padding: 10px 15px;
            margin: 15px 0;
            font-style: italic;
            border-radius: 6px;
        }

        blockquote footer {
            text-align: right;
            font-size: 0.9em;
            color: #aaa;
        }

        .cta-box {
            background-color: var(--primary);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 40px;
            text-align: center;
        }

        .cta-box a.btn {
            background-color: white;
            color: var(--primary);
            font-weight: bold;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 10px;
        }

        .cta-box a.btn:hover {
            background-color: #e0f7fa;
        }
    </style>
</head>
<body class="dark-mode">

<!-- Sidebar -->
<div class="sidebar">
    <img src="logo.png" alt="Logo" width="100" style="margin-bottom: 20px;">
    <h3>ConnectFast</h3>
    <a href="dashboard.php">Dashboard</a>
    <a href="profile.php">Profile</a>
    <a href="subscription.php">Subscribe</a>
    <a href="logout.php">Logout</a>
</div>

<!-- Main Content -->
<div class="main">
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($user_data['name']); ?>!</h2>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>

        <div class="section">
            <h3>🌍 Company Overview</h3>
            <p>
                Piethon Network is a leading provider of high-speed internet solutions, offering secure, scalable, and affordable connectivity. 
                We serve both urban and rural areas, delivering unmatched service and support.
            </p>
        </div>

        <div class="section">
            <h3>🚀 Key Features</h3>
            <ul>
                <li>Up to <strong>1 Gbps</strong> fiber connectivity</li>
                <li><strong>99.9% uptime guarantee</strong></li>
                <li><strong>Smart router</strong> included</li>
                <li>Optimized for streaming, gaming, and video calls</li>
                <li><strong>24/7 multilingual support</strong> (EN/DE/FR)</li>
            </ul>
        </div>

        <div class="section">
            <h3>🌐 Our Coverage</h3>
            <p>
                Available in <strong>70+ cities across Europe</strong>. which means you get easy acess to our network and it shows our reliability
            </p>
        </div>

        <div class="section">
            <h3>💬 Testimonials</h3>
            <blockquote>
                “Unbelievable speed and reliability — I finally enjoy working from home.” 
                <footer>— Clara M., Vienna</footer>
            </blockquote>
            <blockquote>
                “The switch was seamless. Great value and customer support.” 
                <footer>— Miguel S., Madrid</footer>
            </blockquote>
        </div>

        <div class="cta-box">
            <h3>🎉 Join the Fast Lane</h3>
            <p>Explore our subscription plans and upgrade your connection today.</p>
            <a href="subscription.php" class="btn">View Plans</a>
        </div>
    </div>
</div>

</body>
</html>
