<?php
session_start();
require_once "config.php"; // استفاده از require_once به جای include_once

// بررسی لاگین
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $pass = md5($_POST['password']); // تبدیل پسورد به هش MD5
    
    // استفاده از Prepared Statements برای جلوگیری از SQL Injection
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $hashed_password);
    $stmt->fetch();
    
    if ($stmt->num_rows > 0 && $pass === $hashed_password) {
        $_SESSION['username'] = true;
        log_login($conn, $user_id); // ثبت لاگ لاگین
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "نام کاربری یا رمز عبور اشتباه است.";
    }
    $stmt->close();
}

// تابع ثبت لاگ لاگین
function log_login($conn, $user_id) {
    $ip_address = $_SERVER['REMOTE_ADDR']; // به دست آوردن آدرس IP کاربر
    $stmt = $conn->prepare("INSERT INTO login_logs (user_id, ip_address) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $ip_address);
    $stmt->execute();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazir:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazir', sans-serif; /* استفاده از فونت وزیر */
            background-image: url(img/bg.webp);
            background-repeat: no-repeat;
            background-size: cover;
        }
        .container {
            text-align: right; /* راست چین کردن متن‌ها در کانتینر */
        }
        .card {
            background-color: rgba(255, 255, 255, 0.9); /* تغییر opacity به 0.8 برای پس‌زمینه کارت */
            border: none; /* حذف حاشیه‌ی کارت */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* اضافه کردن سایه به کارت */
            text-align: right; /* راست چین کردن متن‌ها در کارت */
        }
        form {
            text-align: right; /* راست چین کردن متن‌ها در فرم */
        }
        h3 {
            text-align: center; /* وسط چین کردن عنوان هدر */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header">
                        <h3>ورود به سیستم</h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)) { echo '<div class="alert alert-danger">' . $error . '</div>'; } ?>
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="username">نام کاربری</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="password">رمز عبور</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">ورود</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/jquery-3.5.1.slim.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>
