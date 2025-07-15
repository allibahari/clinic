<?php
require_once '../config.php';
header('Content-Type: application/json; charset=utf-8');

$doctor_id = (int)$_POST['doctor_id'];
$patient_name = $_POST['patient_name'];
$mobile = $_POST['mobile'];
$appointment_time = $_POST['appointment_time'];

// چک کردن مجدد برای جلوگیری از رزرو تکراری
$check_sql = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_time = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $doctor_id, $appointment_time);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'متاسفانه این نوبت لحظاتی قبل رزرو شد.']);
    exit();
}

$sql = "INSERT INTO appointments (doctor_id, patient_name, mobile, appointment_time, status) VALUES (?, ?, ?, ?, 'booked')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $doctor_id, $patient_name, $mobile, $appointment_time);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'خطا در ثبت نهایی نوبت.']);
}