<?php
// تنظیم اطلاعات دیتابیس
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard_db";

// ایجاد اتصال به دیتابیس
$conn = new mysqli($servername, $username, $password, $dbname);

// بررسی اتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// تابع ثبت لاگ لاگین
if (!function_exists('log_login')) {
    function log_login($user_id, $conn) {
        $stmt = $conn->prepare("INSERT INTO login_logs (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>
