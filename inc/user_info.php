<?php
include 'session.php'; // شامل کردن فایل سشن
include "config.php";

// Fetch username from the session
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'مهمان';

// Fetch number of patients from the database
$sql = "SELECT COUNT(*) as patient_count FROM employees1"; // Adjust table name if needed
$result = $conn->query($sql);

$patient_count = 0;
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $patient_count = $row['patient_count'];
}

// بررسی ورود کاربر
if (!isset($_SESSION['username']) || $_SESSION['username'] !== true) {
    header('Location: index.php'); // هدایت به صفحه ورود
    exit; // متوقف کردن اجرای باقی‌مانده‌ی اسکریپت
}
?>
