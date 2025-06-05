<?php
$conn = new mysqli("localhost", "root", "", "user_system");
if ($conn->connect_error) die("Connection failed");

$token = $_GET['token'] ?? '';

if ($token) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $update->bind_param("s", $token);
        $update->execute();
        echo "✅ Email successfully verified. <a href='login.php'>Login here</a>.";
    } else {
        echo "❌ Invalid or already used verification token.";
    }
}
?>

