<?php
// فایل: delete_employee.php

include "config.php"; // برای اتصال به پایگاه داده

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $employee_id = $_POST['id'];

    // --- مرحله ۱: پیدا کردن مسیر عکس‌ها و پوشه کاربر قبل از حذف ---
    $stmt_select = $conn->prepare("SELECT national_code, photo_path1, photo_path2, photo_path3, photo_path4, photo_path5, photo_path6 FROM employees WHERE id = ?");
    $stmt_select->bind_param("i", $employee_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $user_data = $result->fetch_assoc();
    $stmt_select->close();

    $photo_paths = [];
    if ($user_data) {
        // جمع‌آوری تمام مسیرهای عکس در یک آرایه
        for ($i = 1; $i <= 6; $i++) {
            if (!empty($user_data['photo_path' . $i])) {
                $photo_paths[] = $user_data['photo_path' . $i];
            }
        }
    }

    // --- مرحله ۲: حذف کاربر از دیتابیس ---
    $stmt_delete = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt_delete->bind_param("i", $employee_id);

    if ($stmt_delete->execute()) {
        // --- مرحله ۳: حذف فایل‌های فیزیکی عکس‌ها ---
        foreach ($photo_paths as $path) {
            if (file_exists($path)) {
                unlink($path); // حذف فایل
            }
        }
        
        // --- مرحله ۴: حذف پوشه کاربر (اگر خالی باشد) ---
        if ($user_data && !empty($user_data['national_code'])) {
            $user_dir = "uploads/" . preg_replace('/[^0-9]/', '', $user_data['national_code']);
            // بررسی اینکه آیا پوشه وجود دارد و خالی است
            if (is_dir($user_dir) && count(scandir($user_dir)) == 2) { // . and ..
                rmdir($user_dir); // حذف پوشه
            }
        }

        // هدایت به صفحه کاربران با پیام موفقیت
        header("Location: users.php?status=success&message=" . urlencode("کاربر و تمام فایل‌های مرتبط با موفقیت حذف شدند."));
        exit();

    } else {
        // هدایت با پیام خطا
        $errorMessage = "خطا در حذف کاربر از پایگاه داده.";
        header("Location: users.php?status=error&message=" . urlencode($errorMessage));
        exit();
    }
    
    $stmt_delete->close();
}

$conn->close();
?>