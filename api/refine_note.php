<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🛡️ SECURITY: کلید API OpenAI را از متغیرهای محیطی سرور بخوان
// این روش امن‌تری نسبت به قرار دادن مستقیم کلید در کد است.
$OPENAI_API_KEY = getenv('OPENAI_API_KEY');
$OPENAI_API_URL = "https://api.openai.com/v1/chat/completions";

// بررسی وجود کلید API
if (empty($OPENAI_API_KEY)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'OpenAI API key is not configured on the server.']);
    exit;
}

// بررسی متد درخواست و وجود پارامتر 'note'
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['note'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing "note" parameter.']);
    exit;
}

$original_note = $_POST['note'];

if (empty($original_note)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Note cannot be empty.']);
    exit;
}

// آماده‌سازی پرامپت‌ها برای ساختار پیام OpenAI
$system_prompt = "به عنوان یک دستیار متنی برای یک کلینیک زیبایی در ایران، متن زیر را از نظر گرامر، املایی و نگارشی به فارسی استاندارد و رسمی اصلاح و ویرایش کن. متن ممکن است حاوی اصطلاحات پزشکی یا اطلاعات مربوط به بیمار باشد. فقط متن اصلاح شده را برگردان، بدون هیچ توضیحی.";
$user_prompt = "متن اصلی:\n\"" . $original_note . "\"";

// ساختار دیتا (Payload) برای OpenAI متفاوت است
$data = json_encode([
    'model' => 'gpt-4o', // یا 'gpt-3.5-turbo' برای سرعت بیشتر و هزینه کمتر
    'messages' => [
        [
            'role' => 'system',
            'content' => $system_prompt
        ],
        [
            'role' => 'user',
            'content' => $user_prompt
        ]
    ],
    'temperature' => 0 // برای دریافت پاسخ قطعی و قابل پیش‌بینی
]);

// ارسال درخواست به API
$ch = curl_init($OPENAI_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
// هدر Authorization برای OpenAI الزامی است
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $OPENAI_API_KEY
]);

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// مدیریت خطاهای cURL (مانند مشکلات شبکه)
if ($curl_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'CURL error: ' . $curl_error]);
    exit;
}

$result = json_decode($response, true);

// مدیریت خطاهای API
if ($http_status !== 200) {
    $error_message = $result['error']['message'] ?? 'Unknown API error.';
    http_response_code($http_status);
    echo json_encode(['success' => false, 'message' => 'API response error (' . $http_status . '): ' . $error_message]);
    exit;
}

// ساختار پاسخ در OpenAI متفاوت است
$refined_text = $result['choices'][0]['message']['content'] ?? null;

if ($refined_text) {
    echo json_encode(['success' => true, 'refined_text' => trim($refined_text)]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve refined text from API response.']);
}
?>