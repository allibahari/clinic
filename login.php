<?php
// این کد در فایل login.php قرار می‌گیرد و توسط index.php (روتر) فراخوانی می‌شود.
// نیازی به session_start() در اینجا نیست، چون روتر آن را فراخوانی می‌کند.
require_once "config.php";

$error = "";

// تابع برای ثبت لاگ ورود
function log_login($db_connection, $user_id) {
    if ($db_connection) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $db_connection->prepare("INSERT INTO login_logs (user_id, ip_address) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $user_id, $ip);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// پردازش فرم فقط در صورت ارسال با متد POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_input = trim($_POST['username']);
    $pass_input = $_POST['password'];
    $captcha_input = trim($_POST['captcha']);

    // مرحله ۱: بررسی کد امنیتی
    if (!isset($_SESSION['captcha_code']) || strtolower($captcha_input) !== strtolower($_SESSION['captcha_code'])) {
        $error = "کد امنیتی اشتباه است.";
    } else {
        // مرحله ۲: بررسی نام کاربری
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $user_input);
        $stmt->execute();

        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $username, $hashed_password);
            $stmt->fetch();

            // مرحله ۳: بررسی رمز عبور
            if (password_verify($pass_input, $hashed_password)) {

                unset($_SESSION['captcha_code']);

                session_regenerate_id(true);
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;

                log_login($conn, $id);

                header("Location: /clinic/dashboard");
                exit;
            } else {
                $error = "نام کاربری یا رمز عبور اشتباه است.";
            }
        } else {
            $error = "نام کاربری یا رمز عبور اشتباه است.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>ورود به سیستم</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.min.css">
  <style>
    body { font-family: 'Vazirmatn', sans-serif; background-image: url('img/bg.webp'); background-size: cover; background-position: center; }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
  <div class="w-full max-w-md bg-white bg-opacity-90 p-8 rounded-2xl shadow-lg text-right">
    <h2 class="text-2xl font-bold text-center mb-6">ورود به سیستم</h2>

    <?php if (!empty($error)): ?>
      <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-center"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="/clinic/login" method="POST" class="space-y-5" autocomplete="off" novalidate>
      <div>
        <label for="username" class="block text-sm font-medium mb-1">نام کاربری</label>
        <input type="text" id="username" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" autofocus>
      </div>
      <div>
        <label for="password" class="block text-sm font-medium mb-1">رمز عبور</label>
        <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label for="captcha" class="block text-sm font-medium mb-1">کد امنیتی</label>
        <div class="flex items-center gap-x-2">
          <input type="text" name="captcha" id="captcha" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 tracking-wider" autocomplete="off">
          <img src="captcha_image.php" alt="کپچا" id="captcha-img" class="h-10 w-auto border rounded-md cursor-pointer" title="برای دریافت کد جدید کلیک کنید" />
        </div>
      </div>
      <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-md hover:bg-indigo-700 transition-colors duration-200">ورود</button>
    </form>
    <div class="text-center mt-4">
        <a href="/clinic/register" class="text-sm text-indigo-600 hover:underline">حساب کاربری ندارید؟ ثبت‌نام کنید</a>
    </div>
  </div>

  <script>
    document.getElementById('captcha-img').addEventListener('click', function () {
      // اضافه کردن یک پارامتر تصادفی برای جلوگیری از کش شدن تصویر توسط مرورگر
      this.src = 'captcha_image.php?' + new Date().getTime();
    });
  </script>
</body>
</html>
<?php
// <<< اصلاح: اتصال به دیتابیس در انتهای اسکریپت بسته می‌شود
if (isset($conn)) {
    $conn->close();
}
?>