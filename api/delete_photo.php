<?php
header('Content-Type: application/json');
require_once "../config.php";

$response = ['success' => false, 'message' => 'درخواست نامعتبر.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_id'], $_POST['column_name'])) {
    $employee_id = (int)$_POST['employee_id'];
    $column_name = $_POST['column_name'];

    // اعتبارسنجی نام ستون برای جلوگیری از SQL Injection
    $allowed_columns = ['photo_path1', 'photo_path2', 'photo_path3', 'photo_path4', 'photo_path5', 'photo_path6'];
    if (!in_array($column_name, $allowed_columns)) {
        $response['message'] = 'نام ستون نامعتبر است.';
        echo json_encode($response);
        exit;
    }

    // واکشی مسیر فایل برای حذف از سرور
    $stmt_select = $conn->prepare("SELECT $column_name FROM employees WHERE id = ?");
    $stmt_select->bind_param("i", $employee_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $row = $result->fetch_assoc();
    $stmt_select->close();

    if ($row && !empty($row[$column_name])) {
        $file_path = "../" . $row[$column_name]; // مسیر فایل نسبت به این اسکریپت
        
        // آپدیت دیتابیس برای خالی کردن فیلد
        $default_photo = 'img/profile.png';
        $stmt_update = $conn->prepare("UPDATE employees SET $column_name = ? WHERE id = ?");
        $stmt_update->bind_param("si", $default_photo, $employee_id);
        
        if ($stmt_update->execute()) {
            // حذف فایل فیزیکی از سرور (فقط اگر عکس پیش‌فرض نباشد)
            if ($row[$column_name] !== 'img/profile.png' && file_exists($file_path)) {
                unlink($file_path);
            }
            $response['success'] = true;
            $response['message'] = 'عکس با موفقیت حذف شد.';
        } else {
            $response['message'] = 'خطا در آپدیت دیتابیس: ' . $conn->error;
        }
        $stmt_update->close();
    } else {
        $response['message'] = 'عکسی برای حذف یافت نشد.';
    }
}

$conn->close();
echo json_encode($response);