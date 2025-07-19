<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['note'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر یا پارامتر note وجود ندارد.']);
    exit;
}

$original_note = trim($_POST['note']);
if ($original_note === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'متن نمی‌تواند خالی باشد.']);
    exit;
}

// پرامپت ترکیبی
$system_prompt = "به عنوان یک دستیار متنی برای یک کلینیک زیبایی در ایران، متن زیر را از نظر گرامر، املایی و نگارشی به فارسی استاندارد و رسمی اصلاح و ویرایش کن. متن ممکن است حاوی اصطلاحات پزشکی یا اطلاعات مربوط به بیمار باشد. فقط متن اصلاح شده را برگردان، بدون هیچ توضیحی. پاسخ را کوتاه و مختصر بده.";

$user_prompt = "متن اصلی:\n\"" . $original_note . "\"";

// ساخت متن کامل برای ارسال
$full_prompt = $system_prompt . "\n\n" . $user_prompt;

// پارامترهای API
$text = urlencode($full_prompt);
$country = 'Asia';
$user_id = 'usery3peypi26p';

$url = "https://yw85opafq6.execute-api.us-east-1.amazonaws.com/default/boss_mode_15aug?text={$text}&country={$country}&user_id={$user_id}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در ارتباط CURL: ' . $curl_error]);
    exit;
}

// حذف \n از متن و تبدیل آنها به خط جدید واقعی
// ابتدا \n واقعی ممکن است بصورت رشته "\n" باشد یا کاراکترهای LF (line feed).
// اگر پاسخ به صورت JSON نیست و صرفا رشته متنی است، می‌توانیم اینطور جایگزین کنیم:
$clean_text = str_replace('\n', "\n", $response);  // جایگزینی \n رشته‌ای با خط جدید واقعی

// اگر همچنان در خروجی \n به صورت رشته باقی مانده بود، می‌توانیم با preg_replace حذف کنیم یا تبدیل کنیم.

// برگرداندن متن تمیز شده به صورت JSON
echo json_encode(['success' => true, 'refined_text' => trim($clean_text)]);
