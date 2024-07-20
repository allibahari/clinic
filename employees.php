<?php
include "inc/nav.php";
include "./config.php";

use Melipayamak\MelipayamakApi;

$statusMessage = "";
$defaultPhoto = 'img/profile.png'; // مسیر عکس پیش‌فرض

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $birth_date = $conn->real_escape_string($_POST['birth_date']);
    $national_code = $conn->real_escape_string($_POST['national_code']);
    $marital_status = $conn->real_escape_string($_POST['marital_status']);
    $email = $conn->real_escape_string($_POST['email']);
    $service_type = isset($_POST['service_type']) ? $conn->real_escape_string($_POST['service_type']) : '';
    $mobile = $conn->real_escape_string($_POST['mobile']);
    $note = $conn->real_escape_string($_POST['note']); // دریافت یادداشت

    // Create user directory with national code
    $user_dir = "uploads/" . $national_code;
    if (!is_dir($user_dir)) {
        mkdir($user_dir, 0777, true);
    }

    $photo_paths = [];

    // Handle all photos
    for ($i = 1; $i <= 6; $i++) {
        if (isset($_FILES['photo' . $i]) && $_FILES['photo' . $i]['size'][0] > 0) {
            $photo_set = $_FILES['photo' . $i];
            $photo_name = time() . '_' . basename($photo_set['name'][0]);
            $target_file = $user_dir . '/' . $photo_name;

            if ($photo_set['size'][0] > 500 * 1024) {
                $statusMessage = "حجم عکس باید کمتر از 500 کیلوبایت باشد.";
                break;
            }

            if (move_uploaded_file($photo_set['tmp_name'][0], $target_file)) {
                $photo_paths[] = $target_file;
            } else {
                $statusMessage = "خطا در آپلود عکس.";
                break;
            }
        } else {
            $photo_paths[] = $defaultPhoto;
        }
    }

    if (empty($statusMessage)) {
        // Ensure all 6 photo paths are set
        while (count($photo_paths) < 6) {
            $photo_paths[] = $defaultPhoto;
        }

        try {
            // Prepare SQL statement
            $stmt = $conn->prepare("INSERT INTO employees1 (first_name, last_name, birth_date, national_code, marital_status, email, service_type, mobile, photo_path1, photo_path2, photo_path3, photo_path4, photo_path5, photo_path6, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssssss", $first_name, $last_name, $birth_date, $national_code, $marital_status, $email, $service_type, $mobile, $photo_paths[0], $photo_paths[1], $photo_paths[2], $photo_paths[3], $photo_paths[4], $photo_paths[5], $note);

            // Execute SQL statement
            if ($stmt->execute()) {
                $statusMessage = "اطلاعات کاربر با موفقیت ذخیره شد.";
                // ارسال پیامک پس از ذخیره موفقیت‌آمیز اطلاعات
                require 'vendor/autoload.php';
                $username = '989015255027';
                $password = 'f8e6b48f-8e46-4b4a-8cd6-304392c48fea';
                $api = new MelipayamakApi($username, $password);
                $sms = $api->sms();
                $to = $mobile;
                $from = '50002710055027'; // شماره ارسال کننده
                $text = "با سلام آقا/خانم $first_name $last_name. پرونده شما در مجموعه ما ایجاد گردید سپاسگزارم که مجموعه ما را برای خدمات $service_type انتخاب کردید.";

                $isFlash = false;
                $response = $sms->send($to, $from, $text, $isFlash);
            } else {
                $statusMessage = "خطا در ذخیره اطلاعات: " . $stmt->error;
            }

            // Close statement
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $statusMessage = "کد ملی تکراری است. لطفاً کد ملی دیگری وارد کنید.";
            } else {
                $statusMessage = "خطا در ذخیره اطلاعات: " . $e->getMessage();
            }
        }
    }

    // Close connection
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فرم اطلاعات کاربران</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <!-- Persian Datepicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive_991.css" media="(max-width:991px)">
    <link rel="stylesheet" href="css/responsive_768.css" media="(max-width:768px)">
    <link rel="stylesheet" href="css/font.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 7px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: bold;
        }
        .form-control, .form-select {
            margin-bottom: 15px;
        }
        .message .success {
            color: green;
        }
        .message .error {
            color: red;
            background-color: #ffe0e0;
            padding: 10px;
            border-radius: 5px;
        }
        .photo-upload {
            display: none;
            margin-bottom: 15px;
            border: 1px dashed #ccc;
            padding: 15px;
            border-radius: 10px;
            position: relative;
            background-color: #fafafa;
        }
        .photo-upload label {
            position: absolute;
            top: -10px;
            left: 10px;
            background-color: #fff;
            padding: 0 5px;
            font-weight: bold;
        }
        .photo-upload input[type="file"] {
            opacity: 0;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .photo-upload .placeholder {
            text-align: center;
            color: #aaa;
            font-size: 14px;
            display: none;
        }
        .photo-upload .preview {
            text-align: center;
            margin-top: 10px;
        }
        .photo-upload .preview img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 5px;
        }
        .progress {
            margin-top: 10px;
            height: 20px;
            background-color: #f3f3f3;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background-color: #007bff;
            text-align: center;
            color: white;
            line-height: 20px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>فرم اطلاعات کاربران</h2>
        <form method="POST" enctype="multipart/form-data" id="employeeForm">
            <div class="form-group">
                <label for="first_name">نام زیباجو:</label>
                <input type="text" id="first_name" name="first_name" required class="form-control">
            </div>
            <div class="form-group">
                <label for="last_name">نام خانوادگی زیباجو:</label>
                <input type="text" id="last_name" name="last_name" required class="form-control">
            </div>
            <div class="form-group">
                <label for="birth_date">تاریخ تولد:</label>
                <input type="text" id="birth_date" name="birth_date" required class="form-control" data-provide="datepicker" data-date-language="fa" data-date-format="yyyy/mm/dd">
            </div>
            <div class="form-group">
                <label for="national_code">کد ملی:</label>
                <input type="text" id="national_code" name="national_code" required class="form-control">
            </div>
            <div class="form-group">
                <label for="mobile">شماره موبایل:</label>
                <input type="text" id="mobile" name="mobile" required class="form-control" maxlength="11">
            </div>
            <div class="form-group">
                <label for="marital_status">وضعیت ازدواج:</label>
                <select id="marital_status" name="marital_status" required class="form-select">
                    <option value="single">مجرد</option>
                    <option value="married">متاهل</option>
                </select>
            </div>
            <div class="form-group">
                <label for="email">ایمیل:</label>
                <input type="email" id="email" name="email" required class="form-control">
            </div>
            <div class="form-group">
                <label for="service_type">نوع خدمات:</label>
                <select id="service_type" name="service_type" required class="form-select">
                    <option value="ژل لب">ژل لب</option>
                    <option value="تزریق ژل">تزریق ژل</option>
                    <option value="تزریق بوتاکس">تزریق بوتاکس</option>
                    <option value="کاشت ابرو">کاشت ابرو</option>
                    <option value="جوانسازی پوست">جوانسازی پوست</option>
                </select>
            </div>
            <div class="form-group">
                <label for="note">یادداشت:</label>
                <textarea id="note" name="note" class="form-control" rows="4"></textarea>
            </div>
            <?php for ($i = 1; $i <= 6; $i++): ?>
            <div class="form-group photo-upload" id="photo-group-<?= $i ?>" <?= $i == 1 ? 'style="display:block;"' : '' ?>>
                <label for="photo<?= $i ?>">عکس زیباجو <?= $i ?>:</label>
                <input type="file" id="photo<?= $i ?>" name="photo<?= $i ?>[]" class="form-control" multiple>
                <div class="placeholder">برای انتخاب عکس کلیک کنید</div>
                <div class="preview"></div>
                <div class="progress">
                    <div class="progress-bar" id="progress-bar-<?= $i ?>" role="progressbar" style="width: 0%;">0%</div>
                </div>
            </div>
            <?php endfor; ?>
            <div class="form-group">
                <br>
                <button type="submit" class="btn btn-primary w-100">ارسال</button>
                <br>
            </div>
        </form>
        <div class="message" id="statusMessage">
            <?php
            if (!empty($statusMessage)) {
                echo '<div class="';
                echo strpos($statusMessage, 'موفقیت') !== false ? 'success' : 'error';
                echo '">';
                echo $statusMessage;
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-date/dist/persian-date.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            for (let i = 1; i <= 6; i++) {
                document.getElementById('photo' + i).addEventListener('change', function () {
                    if (this.files.length > 0 && i < 6) {
                        document.getElementById('photo-group-' + (i + 1)).style.display = 'block';
                    }
                    const preview = document.getElementById('photo-group-' + i).querySelector('.preview');
                    preview.innerHTML = '';
                    for (let file of this.files) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(file);
                        preview.appendChild(img);
                    }

                    // فایل‌ها را آپلود و پیشرفت آپلود را مدیریت کنید
                    const formData = new FormData();
                    formData.append('file', this.files[0]);

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'YOUR_UPLOAD_ENDPOINT_HERE', true);

                    xhr.upload.onprogress = function (event) {
                        if (event.lengthComputable) {
                            const percentComplete = Math.round((event.loaded / event.total) * 100);
                            const progressBar = document.getElementById('progress-bar-' + i);
                            progressBar.style.width = percentComplete + '%';
                            progressBar.innerText = percentComplete + '%';
                        }
                    };

                    xhr.onload = function () {
                        if (xhr.status == 200) {
                            console.log('آپلود موفقیت‌آمیز بود');
                        } else {
                            console.log('آپلود با خطا مواجه شد');
                        }
                    };

                    xhr.send(formData);
                });
            }
        });

        $(document).ready(function () {
            $('#birth_date').persianDatepicker({
                format: 'YYYY/MM/DD'
            });
        });
    </script>
</body>
</html>
