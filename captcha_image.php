<?php
session_start();

header('Content-type: image/png');

// تنظیمات تصویر
$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

// رنگ‌ها
$bgColor = imagecolorallocate($image, 255, 255, 255); // سفید
$textColor = imagecolorallocate($image, 0, 0, 0); // مشکی
$noiseColor = imagecolorallocate($image, 100, 120, 180);

// پس‌زمینه سفید
imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

// ایجاد نویز (نقاط رنگی)
for ($i = 0; $i < 100; $i++) {
    imagefilledellipse($image, rand(0,$width), rand(0,$height), 2, 3, $noiseColor);
}

// ایجاد نویز (خطوط)
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0,$width), rand(0,$height), rand(0,$width), rand(0,$height), $noiseColor);
}

// ساخت رشته کپچا
$characters = '1234567890'; // کاراکترهای بدون شباهت (برای خوانایی بهتر)
$captcha_code = '';
$length = 5;
for ($i = 0; $i < $length; $i++) {
    $captcha_code .= $characters[rand(0, strlen($characters) - 1)];
}
// ذخیره کپچا در سشن برای مقایسه
$_SESSION['captcha_code'] = $captcha_code;

// فونت (می‌توانید فونت ttf دلخواه را در فولدر قرار دهید و مسیرش را تغییر دهید)
$font = __DIR__ . '/fonts/Vazir.ttf'; // مطمئن شوید فونت موجود است، اگر نیست، به مسیر فونت سیستم اشاره کنید یا فونت را حذف کنید
// نوشتن متن کپچا روی تصویر
for ($i = 0; $i < $length; $i++) {
    $angle = rand(-15, 15);
    $x = 10 + ($i * 20);
    $y = rand(25, 35);
    if (file_exists($font)) {
        imagettftext($image, 20, $angle, $x, $y, $textColor, $font, $captcha_code[$i]);
    } else {
        // اگر فونت پیدا نشد از فونت پیش‌فرض استفاده کن
        imagestring($image, 5, $x, 10, $captcha_code[$i], $textColor);
    }
}
// خروجی تصویر
imagepng($image);
imagedestroy($image);
