<?php
header('Content-Type: application/json');
require_once "../config.php";

function convertNumbersToEnglish($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    return str_replace($persian, range(0, 9), str_replace($arabic, range(0, 9), $string));
}

/**
 * ✨ تابع جدید برای تبدیل تاریخ شمسی به میلادی
 * @param int $jy Jalali Year
 * @param int $jm Jalali Month
 * @param int $jd Jalali Day
 * @return array Gregorian Date [year, month, day]
 */
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
    // دریافت اطلاعات
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);
    $service_id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT); // ✨ دریافت فیلد جدید
    $patient_name = trim($_POST['patient_name'] ?? '');
    $patient_mobile = trim($_POST['patient_mobile'] ?? '');
    $patient_national_code = trim($_POST['patient_national_code'] ?? ''); // ✨ دریافت فیلد جدید
    $appointment_date_jalali = trim($_POST['appointment_date'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');

    // تبدیل اعداد همه ورودی‌ها به انگلیسی
    $patient_name = convertNumbersToEnglish($patient_name);
    $patient_mobile = convertNumbersToEnglish($patient_mobile);
    $patient_national_code = convertNumbersToEnglish($patient_national_code);
    $appointment_date_jalali = convertNumbersToEnglish($appointment_date_jalali);

    // ✨ تبدیل تاریخ شمسی به میلادی
    $gregorian_date_str = '';
    if (!empty($appointment_date_jalali)) {
        list($jy, $jm, $jd) = explode('-', $appointment_date_jalali);
        list($gy, $gm, $gd) = jalali_to_gregorian((int)$jy, (int)$jm, (int)$jd);
        $gregorian_date_str = "$gy-$gm-$gd";
    }

    $appointment_full_time = $gregorian_date_str . ' ' . $appointment_time;

    // اعتبارسنجی
    if (!$doctor_id || !$service_id || empty($patient_name) || empty($patient_mobile) || empty($gregorian_date_str)) {
        $response['message'] = 'لطفا تمام فیلدهای ستاره‌دار را تکمیل کنید.';
        echo json_encode($response);
        exit;
    }

    try {
        // ✨ کوئری INSERT با فیلدهای جدید
        $sql = "INSERT INTO appointments (doctor_id, service_id, patient_name, patient_mobile, patient_national_code, appointment_time, status) VALUES (?, ?, ?, ?, ?, ?, 'booked')";
        $stmt = $conn->prepare($sql);
        // ✨ বাইন্ড پارامترهای جدید
        $stmt->bind_param("iissss", $doctor_id, $service_id, $patient_name, $patient_mobile, $patient_national_code, $appointment_full_time);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'نوبت با موفقیت ثبت شد.';
        } else {
            $response['message'] = ($conn->errno == 1062) ? 'این زمان برای پزشک انتخابی قبلا رزرو شده است.' : 'خطا در ثبت اطلاعات.';
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'خطای سرور: ' . $e->getMessage();
    }
    $conn->close();
}

echo json_encode($response);