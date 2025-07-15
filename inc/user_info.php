<?php
// این فایل باید فقط شامل session_start() باشد
include 'session.php'; 

// فایل اتصال به دیتابیس
include "config.php";

// ---- این بخش کد برای بررسی لاگین است و باید اول اجرا شود ---- //
// اگر کاربر لاگین نکرده بود، او را به صفحه ورود هدایت کن
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php'); // یا login.php
    exit; // اجرای اسکریپت را متوقف کن
}
// ----------------------------------------------------------------- //


// حالا که مطمئن هستیم کاربر لاگین کرده، اطلاعات او را می‌خوانیم
$username = $_SESSION['username']; // این خط دیگر به مقدار پیش‌فرض نیاز ندارد

// شمارش تعداد بیماران (این بخش می‌تواند بماند)
$sql = "SELECT COUNT(*) as patient_count FROM employees1"; 
$result = $conn->query($sql);

$patient_count = 0;
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $patient_count = $row['patient_count'];
}
?>