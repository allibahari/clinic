<?php
require_once '../config.php';
header('Content-Type: application/json; charset=utf-8');
$doctor_id = (int)$_GET['doctor_id'];

// انتخاب روزهایی که برایشان برنامه زمانی تعریف شده
$sql = "SELECT DISTINCT DATE(start_time) as available_date 
        FROM doctor_availability 
        WHERE doctor_id = ? AND start_time >= CURDATE()
        ORDER BY available_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$days = [];
while($row = $result->fetch_assoc()) {
    $date_obj = new DateTime($row['available_date']);
    // این بخش برای نمایش زیباتر تاریخ در جاوااسکریپت است
    $row['formatted_date'] = $date_obj->format('d F Y');
    $days[] = $row;
}
echo json_encode($days);