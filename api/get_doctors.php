<?php
// فایل api/get_doctors.php

// فعال کردن نمایش خطا برای دیباگ
ini_set('display_errors', 1);
error_reporting(E_ALL);

// اتصال به دیتابیس
require_once '../config.php'; // مسیر به config.php یک پوشه بالاتر است

header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT id, full_name, specialty, address, profile_image_path FROM doctors ORDER BY id DESC";
$result = $conn->query($sql);

if (!$result) {
    // در صورت بروز خطا در کوئری
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit();
}

$doctors = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

echo json_encode($doctors);