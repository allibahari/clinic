<?php
include_once "config.php";
include_once "inc/nav.php";

// بررسی ثبت‌نام
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    $name = trim($_POST['name']);
    $family = trim($_POST['family']);
    $role_id = $_POST['role']; // گرفتن نقش کاربر از فرم

    // بررسی وجود نام کاربری در پایگاه داده
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "نام کاربری قبلاً ثبت شده است. لطفاً نام کاربری دیگری انتخاب کنید.";
    } else {
        // بررسی صحت role_id
        $stmt = $conn->prepare("SELECT id FROM roles WHERE id = ?");
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            $error = "نقش انتخاب شده معتبر نیست.";
        } else {
            // هش کردن رمز عبور با MD5
            $hashed_password = md5($pass);

            // استفاده از Prepared Statements برای جلوگیری از SQL Injection
            $stmt = $conn->prepare("INSERT INTO users (username, password, name, family, role_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $user, $hashed_password, $name, $family, $role_id);

            if ($stmt->execute()) {
                $success = "کاربر با موفقیت اضافه شد.";
            } else {
                $error = "خطا در ثبت کاربر. لطفاً دوباره تلاش کنید.";
            }
        }
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive_991.css" media="(max-width:991px)">
    <link rel="stylesheet" href="css/responsive_768.css" media="(max-width:768px)">
    <link rel="stylesheet" href="css/font.css">
    <style>
        .card {
            background-color: rgba(255, 255, 255, 0.8);
            border: none;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h3 {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mt-5">
                    <div class="card-header">
                        <h3> افزودن به مدیر </h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($success)) { echo '<div class="alert alert-success">' . htmlspecialchars($success, ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                        <?php if(isset($error)) { echo '<div class="alert alert-danger">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="name">نام</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="family">نام خانوادگی</label>
                                <input type="text" class="form-control" id="family" name="family" required>
                            </div>
                            <div class="form-group">
                                <label for="username">نام کاربری</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="password">رمز عبور</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="role">نقش</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="1">ادمین</option>
                                    <option value="2">اوپراتور</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"> ثبت مدیر</button>
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
