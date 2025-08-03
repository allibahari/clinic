<?php
// api/get_available_times.php
header('Content-Type: application/json');
require_once "../config.php";

$doctor_id = $_GET['doctor_id'] ?? 0;
$date = $_GET['date'] ?? '';

if (empty($doctor_id) || empty($date)) {
    echo json_encode(['success' => false, 'message' => 'اطلاعات ناقص است.']);
    exit;
}

// لیست وضعیت‌هایی که یک زمان را "اشغال شده" نشان می‌دهند
// نوبت‌های لغو شده یا عدم حضور، جزو این لیست نیستند
$booked_statuses = ['booked', 'arrived', 'in_room', 'completed'];
$status_placeholders = implode(',', array_fill(0, count($booked_statuses), '?'));

$sql = "SELECT SUBSTRING(appointment_time, 12, 5) AS time 
        FROM appointments 
        WHERE doctor_id = ? 
        AND DATE(appointment_time) = ? 
        AND status IN ($status_placeholders)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // ترکیب پارامترها برای bind_param
    // "is" برای doctor_id (integer) و date (string)
    // بقیه 's' ها برای وضعیت‌ها هستند
    $bind_types = "is" . str_repeat('s', count($booked_statuses));
    $bind_params = array_merge([$doctor_id, $date], $booked_statuses);
    
    // استفاده از call_user_func_array برای بایند کردن داینامیک پارامترها
    $stmt->bind_param($bind_types, ...$bind_params);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_times = [];
    while ($row = $result->fetch_assoc()) {
        $booked_times[] = $row['time'];
    }
    
    echo json_encode(['success' => true, 'booked_times' => $booked_times]);
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'خطا در آماده‌سازی کوئری.']);
}

$conn->close();
?>