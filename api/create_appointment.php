<?php
header('Content-Type: application/json');
require_once "../config.php";

// توابع کمکی (بدون تغییر)
function convertNumbersToEnglish($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    return str_replace($persian, range(0, 9), str_replace($arabic, range(0, 9), $string));
}

function jalali_to_gregorian($jy, $jm, $jd) {
    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
    $jy += 1595;
    $days = -355668 + (365 * $jy) + (((int)($jy / 33)) * 8) + ((int)((($jy % 33) + 3) / 4)) + $jd;
    if ($jm < 7) {
        $days += ($jm - 1) * 31;
    } else {
        $days += 186 + (($jm - 7) * 30);
    }
    $gy = 400 * ((int)($days / 146097));
    $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * ((int)(--$days / 36524));
        $days %= 36524;
        if ($days >= 365) $days++;
    }
    $gy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $gy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $gd = $days + 1;
    foreach ($g_days_in_month as $gm => $v) {
        if (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) {
            if ($gm == 1) $v++;
        }
        if ($gd <= $v) break;
        $gd -= $v;
    }
    return [$gy, $gm + 1, $gd];
}

$response = ['success' => false, 'message' => 'درخواست نامعتبر.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. دریافت و پاکسازی ورودی‌ها
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);
    $service_id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    $patient_name = convertNumbersToEnglish(trim($_POST['patient_name'] ?? ''));
    $patient_mobile = convertNumbersToEnglish(trim($_POST['patient_mobile'] ?? ''));
    $patient_national_code = convertNumbersToEnglish(trim($_POST['patient_national_code'] ?? ''));
    $appointment_date_jalali = convertNumbersToEnglish(trim($_POST['appointment_date'] ?? ''));
    $appointment_time = trim($_POST['appointment_time'] ?? '');

    // 2. تبدیل تاریخ شمسی به میلادی با فرمت صحیح
    $gregorian_date_str = '';
    if (!empty($appointment_date_jalali) && preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $appointment_date_jalali)) {
        list($jy, $jm, $jd) = explode('-', $appointment_date_jalali);
        list($gy, $gm, $gd) = jalali_to_gregorian((int)$jy, (int)$jm, (int)$jd);
        // ✨ رفع باگ: اطمینان از دو رقمی بودن ماه و روز
        $gm_padded = str_pad($gm, 2, '0', STR_PAD_LEFT);
        $gd_padded = str_pad($gd, 2, '0', STR_PAD_LEFT);
        $gregorian_date_str = "$gy-$gm_padded-$gd_padded";
    }
    $appointment_full_time = $gregorian_date_str . ' ' . $appointment_time . ':00';

    // 3. اعتبارسنجی داده‌ها
    if (!$doctor_id || !$service_id || empty($patient_name) || empty($patient_mobile) || empty($gregorian_date_str)) {
        $response['message'] = 'لطفا تمام فیلدهای ستاره‌دار را تکمیل کنید.';
        echo json_encode($response);
        exit;
    }

    // ✨ رفع باگ: جلوگیری از ثبت نوبت در گذشته
    date_default_timezone_set('Asia/Tehran');
    if (strtotime($appointment_full_time) < time()) {
        $response['message'] = 'امکان ثبت نوبت در تاریخ و زمان گذشته وجود ندارد.';
        echo json_encode($response);
        exit;
    }

    // 4. استفاده از تراکنش برای جلوگیری از ثبت همزمان
    $conn->begin_transaction();
    try {
        // ابتدا بررسی می‌کنیم که آیا این نوبت وجود دارد یا نه
        $check_sql = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_time = ? FOR UPDATE";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $doctor_id, $appointment_full_time);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
             $conn->rollback();
             $response['message'] = 'این ساعت به تازگی رزرو شده است. لطفا زمان دیگری را انتخاب کنید.';
        } else {
            // اگر وجود نداشت، آن را ثبت می‌کنیم
            $sql = "INSERT INTO appointments (doctor_id, service_id, patient_name, patient_mobile, patient_national_code, appointment_time, status) VALUES (?, ?, ?, ?, ?, ?, 'booked')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissss", $doctor_id, $service_id, $patient_name, $patient_mobile, $patient_national_code, $appointment_full_time);

            if ($stmt->execute()) {
                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'نوبت با موفقیت ثبت شد.';
            } else {
                throw new Exception($conn->error);
            }
             $stmt->close();
        }
        $check_stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'خطای سرور: ' . $e->getMessage();
    }
    $conn->close();
}

echo json_encode($response);
?>