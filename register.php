<?php
// فایل اتصال به دیتابیس را فراخوانی کنید
require_once "config.php";

// فعال کردن نمایش خطا برای توسعه
ini_set('display_errors', 1);
error_reporting(E_ALL);

$username = "";
$error = "";
$success = "";

// بررسی اینکه آیا فرم ارسال شده است یا خیر
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. دریافت و پاکسازی ورودی‌ها
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // 2. اعتبارسنجی ورودی‌ها
    if (empty($username)) {
        $error = "لطفاً نام کاربری را وارد کنید.";
    } elseif (empty($password)) {
        $error = "لطفاً رمز عبور را وارد کنید.";
    } elseif ($password !== $confirm_password) {
        $error = "رمزهای عبور با یکدیگر مطابقت ندارند.";
    } else {
        
        // 3. بررسی عدم وجود نام کاربری تکراری
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $error = "این نام کاربری قبلاً انتخاب شده است.";
                } else {
                    // نام کاربری موجود نیست، ادامه فرآیند
                }
            } else {
                $error = "خطایی رخ داد. لطفاً بعداً تلاش کنید.";
            }
            $stmt->close();
        }
    }

    // 4. اگر خطایی وجود نداشت، کاربر را در دیتابیس ذخیره کن
    if (empty($error)) {
        
        // هش کردن امن رمز عبور
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $username, $hashed_password);
            
            if ($stmt->execute()) {
                $success = "ثبت‌نام با موفقیت انجام شد. اکنون می‌توانید وارد شوید.";
                // پاک کردن فرم پس از ثبت موفق
                $username = ""; 
            } else {
                $error = "مشکلی در ثبت‌نام به وجود آمد. لطفاً دوباره تلاش کنید.";
            }
            $stmt->close();
        }
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>ثبت نام کاربر جدید</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      font-family: 'Vazir', sans-serif;
      background-color: #f7fafc;
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">
  <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-md text-right">
    <h2 class="text-2xl font-bold text-center mb-6">ایجاد حساب کاربری جدید</h2>

    <?php if (!empty($error)): ?>
      <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-5" autocomplete="off">
      <div>
        <label for="username" class="block text-sm font-medium mb-1">نام کاربری</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label for="password" class="block text-sm font-medium mb-1">رمز عبور</label>
        <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label for="confirm_password" class="block text-sm font-medium mb-1">تکرار رمز عبور</label>
        <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
      </div>
      <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 transition">ثبت نام</button>
    </form>
    <div class="text-center mt-4">
        <a href="login.php" class="text-sm text-indigo-600 hover:underline">حساب کاربری دارید؟ وارد شوید</a>
    </div>
  </div>
</body>
</html>