<?php
include "inc/nav.php";
include "./config.php";
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش اطلاعات کاربر</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive_991.css" media="(max-width:991px)">
    <link rel="stylesheet" href="css/responsive_768.css" media="(max-width:768px)">
    <link rel="stylesheet" href="css/font.css">
    <style>
        .employee-form {
            border: 1px solid #ccc;
            padding: 20px;
            margin-top: 20px;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .form-group label {
            font-weight: bold;
           float: right;
        }
        .btn-custom {
            width: 100%;
            margin-top: 10px;
        }
        .back-btn {
            margin-top: 20px;
            display: inline-block;
            color: #ffffff;
            background-color: #dc3545;
            padding: 20px 25px;
            border-radius: 5px;
            text-decoration: none;
        }
        .back-btn:hover {
            text-decoration: none;
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mt-4 mb-4 text-center">ویرایش اطلاعات کاربر</h1>
        <!-- <a href="list_employees.php" class="back-btn">بازگشت به صفحه اصلی</a> -->
        <?php
        if (isset($_GET['id'])) {
            $employee_id = $_GET['id'];
            // کوئری برای دریافت جزئیات کاربر خاص
            $sql = "SELECT * FROM employees1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<div class="employee-form">';
                    echo '<form action="" method="post" enctype="multipart/form-data" onsubmit="return confirm(\'آیا مطمئن هستید که می‌خواهید تغییرات را ذخیره کنید؟\');">';
                    echo '<input type="hidden" name="id" value="' . $employee_id . '">';
                    echo '<div class="form-group">';
                    echo '<label for="first_name">نام:</label>';
                    echo '<input type="text" class="form-control" id="first_name" name="first_name" value="' . htmlspecialchars($row["first_name"]) . '">';
                    echo '</div>';
                    echo '<div class="form-group">';
                    echo '<label for="last_name">نام خانوادگی:</label>';
                    echo '<input type="text" class="form-control" id="last_name" name="last_name" value="' . htmlspecialchars($row["last_name"]) . '">';
                    echo '</div>';
                    echo '<div class="form-group">';
                    echo '<label for="national_code">کد ملی:</label>';
                    echo '<input type="text" class="form-control" id="national_code" name="national_code" value="' . htmlspecialchars($row["national_code"]) . '" readonly>';
                    echo '</div>';
                    echo '<div class="form-group">';
                    echo '<label for="birth_date">تاریخ تولد:</label>';
                    echo '<input type="date" class="form-control" id="birth_date" name="birth_date" value="' . htmlspecialchars($row["birth_date"]) . '">';
                    echo '</div>';
                    echo '<div class="form-group">';
                    echo '<label for="service_type"> نوع خدمات:</label>';
                    echo '<input type="text" class="form-control" id="service_type" name="service_type" value="' . htmlspecialchars($row["service_type"]) . '">';
                    echo '</div>';
                    echo '<div class="form-group">';
                    echo '<label for="mobile">شماره موبایل:</label>';
                    echo '<input type="text" class="form-control" id="mobile" name="mobile" value="' . htmlspecialchars($row["mobile"]) . '" maxlength="11">';
                    echo '</div>';
                    echo '<div class="form-group">';
                    echo '<label for="note">توضیحات:</label>';
                    echo '<input type="text" class="form-control" id="note" name="note" value="' . htmlspecialchars($row["note"]) . '">';
                    echo '</div>';
                    for ($i = 1; $i <= 6; $i++) {
                        $photo_path = $row["photo_path$i"];
                        echo '<div class="form-group">';
                        echo '<label for="photo_path' . $i . '">عکس پرسنلی ' . $i . ':</label>';
                        echo '<input type="file" class="form-control" id="photo_path' . $i . '" name="photo_path' . $i . '">';
                        if ($photo_path) {
                            echo '<img src="' . htmlspecialchars($photo_path) . '" alt="عکس پرسنلی ' . $i . '" class="img-thumbnail mt-2" style="max-width: 150px;">';
                        }
                        echo '</div>';
                    }
                    echo '<button type="submit" class="btn btn-primary btn-custom">ذخیره تغییرات</button>';
                    echo '</form>';
                    echo '</div>';
                }
            } else {
                echo "<p class='mt-4 mb-4'>هیچ اطلاعاتی یافت نشد.</p>";
            }
            $stmt->close();
        } else {
            echo "<p class='mt-4 mb-4'>خطا در دریافت اطلاعات.</p>";
        }
        ?>
    </div>

    <!-- Bootstrap JS and jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
// بررسی متد درخواست POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("اتصال به پایگاه داده ناموفق: " . $conn->connect_error);
    }

    $employee_id = $_POST['id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $national_code = $_POST['national_code'];
    $birth_date = $_POST['birth_date'];
    $service_type = $_POST['service_type'];
    $mobile = $_POST['mobile'];
    $note = $_POST['note'];

    // تابع برای آپلود فایل‌ها و بررسی موفقیت آپلود
    function uploadFile($file_input, $upload_dir, $current_file = null) {
        if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES[$file_input]['tmp_name'];
            $file_name = basename($_FILES[$file_input]['name']);
            $dest_path = $upload_dir . $file_name;

            // بررسی نوع فایل
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES[$file_input]['type'], $allowed_types)) {
                return $current_file;
            }

            // بررسی اندازه فایل
            if ($_FILES[$file_input]['size'] > 2 * 1024 * 1024) {
                return $current_file;
            }

            // انتقال فایل به پوشه مقصد
            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                return $dest_path;
            }
        }
        return $current_file;
    }

    // کوئری برای دریافت نام‌های قبلی عکس‌ها
    $sql = "SELECT * FROM employees1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // مسیر بارگذاری عکس‌ها
    $upload_dir = "uploads/";

    // آپلود عکس‌ها و حفظ نام‌های قبلی در صورت عدم بارگذاری جدید
    $photo_path1 = uploadFile("photo_path1", $upload_dir, $row["photo_path1"]);
    $photo_path2 = uploadFile("photo_path2", $upload_dir, $row["photo_path2"]);
    $photo_path3 = uploadFile("photo_path3", $upload_dir, $row["photo_path3"]);
    $photo_path4 = uploadFile("photo_path4", $upload_dir, $row["photo_path4"]);
    $photo_path5 = uploadFile("photo_path5", $upload_dir, $row["photo_path5"]);
    $photo_path6 = uploadFile("photo_path6", $upload_dir, $row["photo_path6"]);

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
                photo_path6 = ?, 
                note = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssi", $first_name, $last_name, $national_code, $birth_date, $service_type, $mobile, $photo_path1, $photo_path2, $photo_path3, $photo_path4, $photo_path5, $photo_path6, $note, $employee_id);

    if ($stmt->execute()) {
        echo "<p class='mt-4 mb-4 text-success'>اطلاعات کاربر با موفقیت به‌روزرسانی شد.</p>";
    } else {
        echo "<p class='mt-4 mb-4 text-danger'>خطا در به‌روزرسانی اطلاعات: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<p class='mt-4 mb-4 text-danger'>درخواست نامعتبر.</p>";
}
?>
