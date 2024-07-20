<?php
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = $_POST['id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $national_code = $_POST['national_code'];
    $birth_date = $_POST['birth_date'];
    $service_type = $_POST['service_type'];
    $mobile = $_POST['mobile'];

    // تابع برای آپلود فایل‌ها و بررسی موفقیت آپلود
    function uploadFile($file_input, $upload_dir) {
        if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES[$file_input]['tmp_name'];
            $file_name = basename($_FILES[$file_input]['name']);
            $dest_path = $upload_dir . $file_name;

            // بررسی نوع فایل (می‌توانید فایل‌های مجاز را مشخص کنید)
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES[$file_input]['type'], $allowed_types)) {
                return false;
            }

            // بررسی اندازه فایل (مثلاً حداکثر 2 مگابایت)
            if ($_FILES[$file_input]['size'] > 2 * 1024 * 1024) {
                return false;
            }

            // انتقال فایل به پوشه مقصد
            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                return $dest_path;
            }
        }
        return null;
    }

    $upload_dir = "uploads/";
    $photo_path1 = uploadFile("photo_path1", $upload_dir);
    $photo_path2 = uploadFile("photo_path2", $upload_dir);
    $photo_path3 = uploadFile("photo_path3", $upload_dir);
    $photo_path4 = uploadFile("photo_path4", $upload_dir);
    $photo_path5 = uploadFile("photo_path5", $upload_dir);
    $photo_path6 = uploadFile("photo_path6", $upload_dir);

    // کوئری به‌روزرسانی اطلاعات کاربر با استفاده از prepared statements
    $sql = "UPDATE employees1 SET 
                first_name = ?, 
                last_name = ?, 
                national_code = ?, 
                birth_date = ?, 
                service_type = ?, 
                mobile = ?, 
                photo_path1 = ?, 
                photo_path2 = ?, 
                photo_path3 = ?, 
                photo_path4 = ?, 
                photo_path5 = ?, 
                photo_path6 = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssi", $first_name, $last_name, $national_code, $birth_date, $service_type, $mobile, $photo_path1, $photo_path2, $photo_path3, $photo_path4, $photo_path5, $photo_path6, $employee_id);

    if ($stmt->execute()) {
        echo "اطلاعات کاربر با موفقیت به‌روزرسانی شد.";
    } else {
        echo "خطا در به‌روزرسانی اطلاعات: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "درخواست نامعتبر.";
}
?>
