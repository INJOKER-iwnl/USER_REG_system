<?php
$conn = new mysqli("localhost", "root", "", "user_system");
if ($conn->connect_error) die("Connection failed");

// Collect form data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$error_message = '';

// Define password pattern (at least one uppercase, one special character, and at least 8 characters)
$passwordPattern = '/^(?=.*[A-Z])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';

// Check if the password matches the pattern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!preg_match($passwordPattern, $password)) {
        $error_message = "Password must contain at least one uppercase letter, one special character, and be at least 8 characters long.";
    }

    // Hash the password
    $password = password_hash($password, PASSWORD_DEFAULT);

    // Check if the email is already registered
    if (!$error_message) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        // If email already exists, show a message with a link to register page
        if ($check->num_rows > 0) {
            $error_message = "Email is already registered. Please try another email.Register with a different email";
        }
        $check->close();
    }

    // If there are no errors, insert the new user into the database
    if (!$error_message) {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);

        if ($stmt->execute()) {
            echo "Registered successfully. <a href='login.php'></a>";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="style.css">
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
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
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
    }

    .error-message {
      color: red;
      margin-bottom: 15px;
      font-weight: bold;
    }

    .password-info {
      font-size: 0.9em;
      color: #888;
      margin-top: -10px;
      margin-bottom: 20px;
      text-align: left;
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
    <h2>Register</h2>

    <?php if ($error_message): ?>
      <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <form action="register.php" method="POST">
      <input type="text" name="name" placeholder="Full Name" value="<?= htmlspecialchars($name) ?? '' ?>" required>
      <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($email) ?? '' ?>" required>
      <input type="password" name="password" id="password" placeholder="Password" value="<?= htmlspecialchars($password) ?? '' ?>" required>

      <p class="password-info">
        Password must contain at least one uppercase letter, one special character, and be at least 8 characters long.
      </p>

      <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
  </div>

</body>
</html>
