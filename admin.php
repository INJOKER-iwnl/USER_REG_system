<?php
// admin.php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}


// DB connection
$conn = new mysqli("localhost", "root", "", "user_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---- Plans ----
$plans = [];
$result = $conn->query("SELECT * FROM plans ORDER BY speed ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $plans[] = $row;
    }
}

// ---- User Filters & Pagination ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['goto_page'])) {
    $_SESSION['user_search'] = $_POST['search'] ?? '';
    $_SESSION['user_role'] = $_POST['role'] ?? '';
    $_SESSION['user_page'] = 1;
}

if (isset($_POST['goto_page'])) {
    $_SESSION['user_page'] = (int)$_POST['goto_page'];
}

$search = $_SESSION['user_search'] ?? '';
$roleFilter = $_SESSION['user_role'] ?? '';
$page = $_SESSION['user_page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE 1";
if (!empty($search)) {
    $escapedSearch = $conn->real_escape_string($search);
    $where .= " AND (name LIKE '%$escapedSearch%' OR email LIKE '%$escapedSearch%')";
}
if (!empty($roleFilter)) {
    $escapedRole = $conn->real_escape_string($roleFilter);
    $where .= " AND role = '$escapedRole'";
}

$totalResult = $conn->query("SELECT COUNT(*) AS total FROM users $where");
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$users = [];
$sql = "
    SELECT u.id, u.name, u.email, u.role, u.created_at, u.plan_id, p.plan_name 
    FROM users u
    LEFT JOIN plans p ON u.plan_id = p.id
    $where
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
";
$userResult = $conn->query($sql);
if ($userResult) {
    while ($user = $userResult->fetch_assoc()) {
        $users[] = $user;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 30px;
            background-image: url('img/bgp.png');
            background-size: cover;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-position: center;
            color: #111;
        }

        h1 {
            text-align: center;
            margin-bottom: 40px;
            font-size: 32px;
        }

        .container {
            display: flex;
            gap: 40px;
            justify-content: space-between;
        }

        .column {
            flex: 1;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }

        h2.toggle-header {
            font-size: 20px;
            background-color: #f9f9f9;
            padding: 12px 15px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }

        .indicator {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .collapsible-content {
            display: none;
        }

        .logout-form {
            text-align: right;
            margin-bottom: 20px;
        }

        .logout-form button {
            background-color: #e63946;
            color: #fff;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .logout-form button:hover {
            background-color: #c82333;
        }

        .search-bar input,
        .filter select,
        .add-form input,
        .add-form textarea,
        form input,
        form select {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .plan {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 10px;
            background-color: #fafafa;
            margin-bottom: 15px;
            transition: transform 0.2s ease;
        }

        .plan:hover {
            transform: scale(1.01);
        }

        .plan h3 {
            margin-top: 0;
            color: #0077b6;
        }

        .plan p {
            margin: 5px 0;
        }

        .plan-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            text-decoration: none;
            font-size: 14px;
        }

        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }

        .edit-btn:hover {
            background-color: #45a049;
            text-decoration: none;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
            text-decoration: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #f4f4f4;
        }

        .user-actions {
            display: flex;
            gap: 8px;
        }

        .user-delete-btn {
            padding: 4px 8px;
            border-radius: 4px;
            background-color: #f44336;
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
        }

        .user-delete-btn:hover {
            background-color: #d32f2f;
            text-decoration: none;
        }

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #ccc;
            margin: 0 4px;
            border-radius: 5px;
            background: #f0f0f0;
            font-weight: bold;
        }

        .pagination button.active {
            background-color: #0077b6;
            color: white;
        }

        .add-form h2 {
            margin-top: 30px;
        }

        .add-form button {
            background-color: #2a9d8f;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .add-form button:hover {
            background-color: #21867a;
        }
    </style>
</head>
<body>

    <h1>Admin Panel</h1>

    <div class="logout-form">
        <form action="logout.php" method="post" onsubmit="return confirm('Are you sure you want to logout?')">
            <button type="submit">Logout</button>
        </form>
    </div>

    <?php if (isset($_GET['delete_success'])): ?>
        <div class="alert success" style="background-color: #4CAF50; color: white; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            User deleted successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['delete_error'])): ?>
        <div class="alert error" style="background-color: #f44336; color: white; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            Error deleting user. Please try again.
        </div>
    <?php endif; ?>

    <div class="container">
        <!-- LEFT COLUMN: Plans -->
        <div class="column">
            <h2 class="toggle-header" onclick="toggleSection('plansSection')">
                <span id="plansSectionIndicator">▼</span> Subscription Plans
            </h2>
            <div id="plansSection" class="collapsible-content">
                <div class="search-bar">
                    <input type="text" id="search" placeholder="Search by plan name..." onkeyup="filterPlans()">
                </div>
                <div class="filter">
                    <label for="speedFilter">Filter by Speed (Mbps):</label>
                    <select id="speedFilter" onchange="filterPlans()">
                        <option value="">All</option>
                        <?php foreach ($plans as $p): ?>
                            <option value="<?= $p['speed'] ?>"><?= $p['speed'] ?> Mbps</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php foreach ($plans as $plan): ?>
                    <div class="plan" data-name="<?= htmlspecialchars($plan['plan_name']) ?>" data-speed="<?= $plan['speed'] ?>">
                        <h3><?= htmlspecialchars($plan['plan_name']) ?></h3>
                        <p><strong>Speed:</strong> <?= $plan['speed'] ?> Mbps</p>
                        <p><strong>Price:</strong> Rs. <?= number_format($plan['price'], 2) ?></p>
                        <p><?= htmlspecialchars($plan['description']) ?></p>
                        <div class="plan-actions">
                            <a href="edit_plan.php?id=<?= $plan['id'] ?>" class="edit-btn">Edit</a>
                            <a href="delete_plan.php?id=<?= $plan['id'] ?>" onclick="return confirm('Are you sure?')" class="delete-btn">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="add-form">
                    <h2>Add New Subscription Plan</h2>
                    <form method="post" action="add_plan.php">
                        <input type="text" name="plan_name" placeholder="Plan Name" required>
                        <input type="number" name="speed" placeholder="Speed (Mbps)" required>
                        <input type="number" name="price" placeholder="Price (Rs.)" required>
                        <textarea name="description" placeholder="Description" required></textarea>
                        <button type="submit">Add Plan</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Users -->
        <div class="column">
            <h2 class="toggle-header" onclick="toggleSection('usersSection')">
                <span id="usersSectionIndicator">▼</span> Registered Users
            </h2>
            <div id="usersSection" class="collapsible-content">
                <form method="post" id="filterForm">
                    <input type="text" name="search" placeholder="Search name/email..." value="<?= htmlspecialchars($search) ?>">
                    <select name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>User</option>
                    </select>
                    <button type="submit">Apply</button>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Plan</th><th>Registered At</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="7">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['role']) ?></td>
                                    <td><?= $user['plan_name'] ?: 'No Plan Assigned' ?></td>
                                    <td><?= $user['created_at'] ?></td>
                                    <td class="user-actions">
                                        <a href="delete_user.php?id=<?= $user['id'] ?>" class="user-delete-btn" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <form method="post">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <button name="goto_page" value="<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </button>
                            <?php endfor; ?>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('plansSection').style.display = 'block';
        document.getElementById('usersSection').style.display = 'block';

        function toggleSection(id) {
            const content = document.getElementById(id);
            const indicator = document.getElementById(id + 'Indicator');
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                indicator.innerHTML = '▲';
            } else {
                content.style.display = 'none';
                indicator.innerHTML = '▼';
            }
        }

        function filterPlans() {
            const search = document.getElementById('search').value.toLowerCase();
            const speed = document.getElementById('speedFilter').value;
            const plans = document.querySelectorAll('.plan');

            plans.forEach(plan => {
                const name = plan.getAttribute('data-name').toLowerCase();
                const planSpeed = plan.getAttribute('data-speed');
                const matchName = name.includes(search);
                const matchSpeed = !speed || speed === planSpeed;
                plan.style.display = (matchName && matchSpeed) ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>