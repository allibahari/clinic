<?php
// تنظیم هدر برای پاسخ JSON
header('Content-Type: application/json');

// اتصال به دیتابیس
require_once "../config.php";

// دریافت پارامترهای ورودی
$doctorId = $_GET['doctor_id'] ?? null;
$date = $_GET['date'] ?? null; // فرمت مورد انتظار: YYYY-MM-DD

// اعتبارسنجی ورودی‌ها
if (empty($doctorId) || empty($date)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'شناسه پزشک و تاریخ الزامی است.']);
    exit;
}

// آماده‌سازی کوئری برای جلوگیری از SQL Injection
// ما فقط بخش زمان را با فرمت HH:MM نیاز داریم
$sql = "SELECT TIME_FORMAT(appointment_time, '%H:%i') as time_slot 
        FROM appointments 
        WHERE doctor_id = ? AND DATE(appointment_time) = ?";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("is", $doctorId, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookedTimes = [];
    while ($row = $result->fetch_assoc()) {
        $bookedTimes[] = $row['time_slot'];
    }
    
    $stmt->close();
    
    echo json_encode(['success' => true, 'booked_times' => $bookedTimes]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'خطا در آماده‌سازی کوئری.']);
}

$conn->close();
?>