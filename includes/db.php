<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = mysqli_connect("localhost", "root", "", "insureeasy");

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

// Set timezone to ensure consistency with system checks
date_default_timezone_set('Asia/Kolkata');
?>
