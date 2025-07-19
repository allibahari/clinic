<?php
// فعال‌سازی سشن و نمایش خطاها
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// تعریف مسیر پایه پروژه
define('BASE_PATH', '/clinic');

$routes = [
    // مسیرهای اصلی و صفحات مدیریتی
    '/'                 => ['file' => 'dashboard.php',       'protected' => true], // صفحه اصلی برای کاربران لاگین کرده
    '/login'            => ['file' => 'login.php',           'protected' => false],
    '/logout'           => ['file' => 'logout.php',          'protected' => true],
    '/dashboard'        => ['file' => 'dashboard.php',       'protected' => true],
    '/users'            => ['file' => 'users.php',           'protected' => true],
    '/employees'        => ['file' => 'employees.php',       'protected' => true],
    '/edit_employee'    => ['file' => 'edit_employee.php',   'protected' => true],
    '/employee_details' => ['file' => 'employee_details.php','protected' => true],
    '/delete-user'      => ['file' => 'delete-user.php',     'protected' => true],
    '/appointments'     => ['file' => 'appointments.php',    'protected' => true],
    '/doctors'          => ['file' => 'doctors.php',         'protected' => true],
    '/generate_invoice' => ['file' => 'generate_invoice.php','protected' => true],
    '/settings' => ['file' => 'settings.php', 'protected' => true],
     '/reports' => ['file' => 'reports.php', 'protected' => true],

    // مسیرهای مربوط به کلاینت (رزرو)
    '/booking' => ['file' => 'booking.php', 'protected' => false],

    // مسیرهای API (برای درخواست‌های AJAX)
    '/api/get_availability'           => ['file' => 'api/get_availability.php', 'protected' => false],
    '/api/book_appointment'           => ['file' => 'api/book_appointment.php', 'protected' => false],
    '/api/update_appointment_status'  => ['file' => 'api/update_appointment_status.php', 'protected' => true],
];

// دریافت URI درخواست شده
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// حذف مسیر پایه از URI
$route_path = str_replace(BASE_PATH, '', $request_uri);
// اگر مسیر خالی بود، آن را به / تغییر بده (برای صفحه اصلی)
if (empty($route_path)) {
    $route_path = '/';
}
// پارامترها (مثل id) مستقیماً توسط فایل‌ها با استفاده از $_GET['id'] خوانده می‌شوند.
if (array_key_exists($route_path, $routes)) {
    $config = $routes[$route_path];

    // بررسی محافظت شده بودن مسیر
    if ($config['protected'] && (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true)) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    // فراخوانی فایل مربوطه
    $file_path = __DIR__ . '/' . $config['file'];
    if (file_exists($file_path)) {
        require $file_path;
    } else {
        // اگر فایل تعریف شده در روتینگ وجود نداشت
        http_response_code(500);
        echo "خطای سرور: فایل مسیر پیدا نشد.";
    }
} else {
    // اگر هیچ مسیری پیدا نشد
    // مدیریت صفحه اصلی در اینجا انجام می‌شود تا کد تکراری نباشد
    if ($route_path === '/index.php' || $route_path === '/') {
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
            header('Location: ' . BASE_PATH . '/dashboard');
        } else {
            header('Location: ' . BASE_PATH . '/login');
        }
        exit;
    }
    
    http_response_code(404);
    require __DIR__ . '/404.php'; 
}
