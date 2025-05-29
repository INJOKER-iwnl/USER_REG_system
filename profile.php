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

// Fetch subscription info
$stmt_sub = $conn->prepare("SELECT s.*, p.plan_name, p.speed, p.price, p.description FROM subscriptions s JOIN plans p ON s.plan_id = p.id WHERE s.user_id = ?");
$stmt_sub->bind_param("i", $user_id);
$stmt_sub->execute();
$sub_result = $stmt_sub->get_result();
$sub = $sub_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <style>
        :root {
            --primary: #00bcd4;
            --secondary: #007b8f;
            --bg-light: #f4f4f4;
            --text-light: #111111;
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
            background-color: var(--bg-light);
            color: var(--text-light);
        }

        .sidebar {
            width: 250px;
            position: fixed;
            left: -250px;
            top: 0;
            height: 100%;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: left 0.3s ease;
            background-color: var(--sidebar-bg-light);
            color: var(--text-light);
            border-right: 1px solid #ccc;
            z-index: 998;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar h3 {
            margin-bottom: 30px;
        }

        .sidebar a {
            text-decoration: none;
            margin: 12px 0;
            font-size: 1rem;
            color: var(--text-light);
            transition: color 0.3s;
        }

        .sidebar a:hover {
            color: var(--primary);
        }

        .main {
            flex: 1;
            padding: 40px;
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        .sidebar.active ~ .main {
            margin-left: 250px;
        }

        .container {
            background-color: #ffffff;
            color: #111;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 188, 212, 0.2);
        }

        .message {
            background-color: #28a745;
            color: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }

        h2 {
            margin-bottom: 10px;
        }

        h3 {
            margin-top: 30px;
            margin-bottom: 15px;
        }

        ul {
            list-style: none;
            padding-left: 0;
        }

        ul li {
            margin-bottom: 8px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin: 5px;
            transition: background 0.3s;
        }

        .btn:hover {
            background-color: var(--secondary);
        }

        .btn:disabled {
            background-color: #555;
            cursor: not-allowed;
        }

        .toggle-sidebar {
            position: fixed;
            top: 20px;
            left: 20px;
            background: var(--primary);
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            z-index: 999;
        }

        .close-sidebar {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: black;
            cursor: pointer;
        }

        .no-subscription {
            line-height: 1.6;
        }

        .no-subscription .section {
            margin-top: 30px;
        }

        .no-subscription ul {
            list-style-type: disc;
            padding-left: 20px;
            margin-top: 10px;
        }

        .no-subscription ul li {
            margin-bottom: 8px;
        }

        .no-subscription blockquote {
            border-left: 4px solid var(--primary);
            background: rgba(0, 188, 212, 0.05);
            padding: 10px 15px;
            margin: 15px 0;
            font-style: italic;
            border-radius: 6px;
        }

        .no-subscription blockquote footer {
            text-align: right;
            font-size: 0.9em;
            color: gray;
        }

        .cta-box {
            background-color: var(--primary);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 40px;
            text-align: center;
        }

        .cta-box h3 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="light-mode">

<!-- Sidebar Toggle Button -->
<button class="toggle-sidebar" onclick="toggleSidebar()">☰</button>

<!-- Sidebar -->
<div class="sidebar light-mode" id="sidebar">
    <span class="close-sidebar" onclick="toggleSidebar()">×</span>
    <h3>User Panel</h3>
    <a href="profile.php" onclick="handleSidebarLinkClick()">Dashboard</a>
    <a href="update.php" onclick="handleSidebarLinkClick()">Update Profile</a>
    <a href="delete.php" onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">Delete Account</a>
    <a href="subscription.php" onclick="handleSidebarLinkClick()">Subscribe</a>
    <a href="logout.php" onclick="handleSidebarLinkClick()">Logout</a>
</div>

<!-- Main Content -->
<div class="main" id="main">
    <div class="container" id="main-container">
        <?php if (isset($_GET['message'])): ?>
            <div class="message">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <h2>Welcome, <?php echo htmlspecialchars($user_data['name']); ?>!</h2>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>

<!-- Always show company info below -->
<div class="no-subscription">
    <h2>Welcome to Piethon Network — Premium Internet Solutions for Modern Living</h2>
    <p>
        You’ve joined thousands of users across Europe who trust us to power their digital lives.
        While you haven’t subscribed to a plan yet, here’s what ConnectFast has to offer you.
    </p>
    <div class="section">
        <h3>🌍 Company Overview</h3>
        <p>
            Piethon Network is a leading high-speed internet provider specializing in secure, scalable, and affordable connectivity solutions.
            Our mission is to make the internet faster, more accessible, and more reliable — from urban hubs to rural towns.
        </p>
    </div>
    <div class="section">
        <h3>🚀 Key Features</h3>
        <ul>
            <li>Up to <strong>1 Gbps</strong> Fiber Connectivity</li>
            <li><strong>99.9% Uptime Guarantee</strong> with real-time monitoring</li>
            <li><strong>Smart Router Support</strong> included with all plans</li>
            <li>Works with <strong>Netflix, YouTube, Xbox, Zoom</strong> and more</li>
            <li>Responsive, <strong>24/7 Support</strong> in English, German, and French</li>
        </ul>
    </div>
    <div class="section">
        <h3>🌐 Our Coverage</h3>
        <p>
            We currently offer service in over <strong>70+ cities</strong> across Europe. which means you get easy access to our network and it shows our reliability.
        </p>
    </div>
    <div class="section">
        <h3>💬 What Our Customers Say</h3>
        <blockquote>
            “I switched to Piethon Network from a major provider — never looking back! The speed and support are unmatched.”
            <footer>— Emma L., Berlin</footer>
        </blockquote>
        <blockquote>
            “Fast setup, honest pricing, no hidden fees. Highly recommended.”
            <footer>— David G., Amsterdam</footer>
        </blockquote>
    </div>
    <div class="cta-box">
        <h3>🎉 Ready to Get Started?</h3>
        <p>Explore our tailored plans designed for your digital lifestyle by also clicking “Subscribe” from the sidebar.</p>
        <a href="subscription.php" class="btn">Subscribe Now</a>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('active');
    }

    function handleSidebarLinkClick() {
        if (window.innerWidth < 768) {
            document.getElementById('sidebar').classList.remove('active');
        }
    }
</script>
</body>
</html>
