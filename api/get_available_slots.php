<?php
require_once '../config.php';
header('Content-Type: application/json; charset=utf-8');

$doctor_id = (int)$_GET['doctor_id'];
$date = $_GET['date'];

$rule_sql = "SELECT * FROM doctor_availability WHERE doctor_id = ? AND DATE(start_time) = ?";
$stmt_rule = $conn->prepare($rule_sql);
$stmt_rule->bind_param("is", $doctor_id, $date);
$stmt_rule->execute();
$rule_result = $stmt_rule->get_result();
if ($rule_result->num_rows == 0) {
    echo json_encode([]); exit();
}
$rule = $rule_result->fetch_assoc();

$booked_sql = "SELECT TIME(appointment_time) as booked_time FROM appointments WHERE doctor_id = ? AND DATE(appointment_time) = ?";
$stmt_booked = $conn->prepare($booked_sql);
$stmt_booked->bind_param("is", $doctor_id, $date);
$stmt_booked->execute();
$booked_result = $stmt_booked->get_result();
$booked_slots = [];
while($row = $booked_result->fetch_assoc()) {
    $booked_slots[] = $row['booked_time'];
}

$available_slots = [];
$start = new DateTime($rule['start_time']);
$end = new DateTime($rule['end_time']);
$duration = $rule['slot_duration'];

while ($start < $end) {
    $current_slot_time = $start->format('H:i:s');
    if (!in_array($current_slot_time, $booked_slots)) {
        $available_slots[] = [
            'time' => $start->format('H:i'),
            'datetime' => $start->format('Y-m-d H:i:s')
        ];
    }
    $start->add(new DateInterval("PT{$duration}M"));
}
echo json_encode($available_slots);