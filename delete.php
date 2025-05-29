<?php
session_start();
if (!isset($_SESSION['user'])) die("Unauthorized");

$conn = new mysqli("localhost", "root", "", "user_system");
if ($conn->connect_error) die("Connection failed");

$userId = $_SESSION['user']['id'];
$conn->query("DELETE FROM users WHERE id = $userId");

session_destroy();
echo "Account deleted.";
