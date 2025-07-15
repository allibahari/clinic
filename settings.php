<?php
// ✨ راه حل ۱: فعال‌سازی صحیح سشن برای جلوگیری از خطا
// فقط در صورتی سشن را استارت می‌زنیم که قبلاً شروع نشده باشد.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// نمایش خطاها برای دیباگ آسان‌تر
ini_set('display_errors', 1);
error_reporting(E_ALL);

// اتصال به پایگاه داده
require_once "config.php";

// ✨ راه حل ۲: تعریف مسیر پایه برای هماهنگی با روتر
// این ثابت باید فقط یک بار تعریف شود. بهتر است این کار در یک فایل مرکزی مثل index.php انجام شود.
// برای جلوگیری از خطا، قبل از تعریف، وجود آن را بررسی می‌کنیم.
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/clinic'); // مقدار /clinic را با مسیر پروژه خود جایگزین کنید
}


// --- بخش مدیریت درخواست‌های AJAX ---
// این بخش درخواست‌های مربوط به ویرایش و حذف سرویس‌ها را مدیریت می‌کند.
if (isset($_POST['action']) && in_array($_POST['action'], ['update_service', 'delete_service'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // به‌روزرسانی سرویس
    if ($action === 'update_service' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $price = filter_var($_POST['price'], FILTER_VALIDATE_INT);
        
        if (empty($name) || $price === false) {
            echo json_encode(['status' => 'error', 'message' => 'نام و قیمت سرویس باید مقادیر معتبر باشند.']);
            exit();
        }

        $sql = "UPDATE services SET name = ?, default_price = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sii", $name, $price, $id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'سرویس با موفقیت به‌روز شد.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'خطا در اجرای به‌روزرسانی.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'خطا در آماده‌سازی کوئری به‌روزرسانی.']);
        }
    }

    // حذف سرویس
    if ($action === 'delete_service' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM services WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'سرویس حذف شد.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'خطا در اجرای حذف.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'خطا در آماده‌سازی کوئری حذف.']);
        }
    }
    
    $conn->close();
    exit();
}


// --- بخش منطق اصلی صفحه ---

// مدیریت پیام‌های فلش برای نمایش بازخورد به کاربر
$flash_message = '';
$flash_type = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

function set_flash_message($message, $type) {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

// تابع برای ذخیره یا به‌روزرسانی یک تنظیم در دیتابیس
function save_setting($conn, $key, $value) {
    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sss", $key, $value, $value);
        return $stmt->execute();
    }
    return false;
}

// تابع برای خواندن یک تنظیم از دیتابیس
function get_setting($conn, $key, $default = '') {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['setting_value'];
        }
    }
    return $default;
}

// --- مدیریت درخواست‌های POST برای فرم‌های اصلی صفحه ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    // ذخیره تنظیمات عمومی
    if ($action === 'save_general_settings') {
        save_setting($conn, 'clinic_name', $_POST['clinic_name'] ?? '');
        save_setting($conn, 'clinic_phone', $_POST['clinic_phone'] ?? '');
        if (isset($_FILES['clinic_logo']) && $_FILES['clinic_logo']['error'] == 0) {
            $upload_dir = 'img/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $logo_name = 'logo_' . time() . '_' . basename($_FILES['clinic_logo']['name']);
            $target_file = $upload_dir . $logo_name;
            if (move_uploaded_file($_FILES['clinic_logo']['tmp_name'], $target_file)) {
                save_setting($conn, 'clinic_logo_path', $target_file);
            }
        }
        set_flash_message('تنظیمات عمومی با موفقیت ذخیره شد.', 'success');
        // ✨ مسیردهی اصلاح شد
        header("Location: " . BASE_PATH . "/settings");
        exit();
    }
    
    // افزودن سرویس جدید
    if ($action === 'add_service') {
        $name = trim($_POST['name'] ?? '');
        $price = filter_var($_POST['price'], FILTER_VALIDATE_INT);

        if (!empty($name) && $price !== false) {
            $sql = "INSERT INTO services (name, default_price) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $name, $price);
            if ($stmt->execute()) {
                 set_flash_message('سرویس جدید با موفقیت اضافه شد.', 'success');
            } else {
                 set_flash_message('خطا در افزودن سرویس.', 'error');
            }
        } else {
            set_flash_message('نام و قیمت سرویس معتبر نیست.', 'error');
        }
        header("Location: " . BASE_PATH . "/settings");
        exit();
    }
    
    // افزودن ادمین جدید
    if ($action === 'add_user') {
        $username = trim($_POST['username']);
        $fullName = trim($_POST['full_name']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        if ($password !== $password_confirm) {
            set_flash_message('رمزهای عبور یکسان نیستند.', 'error');
        } elseif (empty($username) || empty($fullName) || empty($password)) {
            set_flash_message('تمام فیلدها باید پر شوند.', 'error');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, full_name, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $fullName, $hashed_password);
            if ($stmt->execute()) {
                set_flash_message('ادمین جدید با موفقیت اضافه شد.', 'success');
            } else {
                set_flash_message(($conn->errno == 1062) ? 'این نام کاربری قبلاً ثبت شده است.' : 'خطا در افزودن ادمین.', 'error');
            }
        }
        header("Location: " . BASE_PATH . "/settings");
        exit();
    }
}


// --- خواندن اطلاعات برای نمایش در فرم‌ها ---
$clinic_name = get_setting($conn, 'clinic_name', 'نام کلینیک شما');
$clinic_phone = get_setting($conn, 'clinic_phone');
$clinic_logo = get_setting($conn, 'clinic_logo_path', 'img/profile.png');

$services = $conn->query("SELECT * FROM services ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل تنظیمات</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { font-family: 'Vazirmatn', sans-serif; }
        .message { padding: 1rem; border-radius: 0.375rem; text-align: center; margin-bottom: 1.5rem; }
        .message.success { color: #16a34a; background-color: #dcfce7; border: 1px solid #4ade80; }
        .message.error { color: #dc2626; background-color: #fee2e2; border: 1px solid #f87171; }
        .service-edit { display: none; } /* به جای کلاس hidden تیل‌ویند برای کنترل بهتر با جی‌کوئری */
    </style>
</head>
<body class="bg-slate-900 text-slate-300 font-sans">
    <div class="flex">
        <?php 
        // فایل ناوبری را در صورت وجود فراخوانی می‌کند
        if (file_exists("inc/nav.php")) {
            require_once "inc/nav.php"; 
        }
        ?>

        <main class="flex-1 p-6">
            <h1 class="text-3xl font-bold text-white mb-8">تنظیمات پنل</h1>
            <div class="max-w-7xl mx-auto">
                <?php if (!empty($flash_message)): ?>
                    <div id="flash-msg" class="message <?php echo htmlspecialchars($flash_type); ?>">
                        <?php echo htmlspecialchars($flash_message); ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="space-y-8">
                        <div class="bg-slate-800 p-8 rounded-xl shadow-lg">
                            <h2 class="text-xl font-semibold text-white mb-4 border-b border-slate-700 pb-3"><i class="fas fa-cogs ml-2"></i>تنظیمات عمومی کلینیک</h2>
                            <form action="<?php echo BASE_PATH; ?>/settings" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_general_settings">
                                <div class="space-y-4">
                                    <div><label for="clinic_name" class="block text-sm font-medium mb-1">نام کلینیک:</label><input type="text" id="clinic_name" name="clinic_name" value="<?php echo htmlspecialchars($clinic_name); ?>" class="w-full bg-slate-700 p-2 rounded-md border border-slate-600 focus:ring-blue-500 focus:border-blue-500"></div>
                                    <div><label for="clinic_phone" class="block text-sm font-medium mb-1">تلفن تماس:</label><input type="text" id="clinic_phone" name="clinic_phone" value="<?php echo htmlspecialchars($clinic_phone); ?>" class="w-full bg-slate-700 p-2 rounded-md border border-slate-600 focus:ring-blue-500 focus:border-blue-500"></div>
                                    <div>
                                        <label class="block text-sm font-medium mb-1">لوگوی فعلی:</label>
                                        <img src="<?php echo htmlspecialchars(ltrim($clinic_logo, '/')); ?>" alt="لوگو" class="w-24 h-24 rounded-md object-cover bg-slate-700 mb-2">
                                        <label for="clinic_logo" class="block text-sm font-medium mb-1">تغییر لوگو:</label>
                                        <input type="file" id="clinic_logo" name="clinic_logo" class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-slate-700 file:text-slate-300 hover:file:bg-slate-600">
                                    </div>
                                </div>
                                <div class="mt-6 text-left"><button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md transition-colors">ذخیره تنظیمات</button></div>
                            </form>
                        </div>
                        <div class="bg-slate-800 p-8 rounded-xl shadow-lg">
                            <h2 class="text-xl font-semibold text-white mb-4 border-b border-slate-700 pb-3"><i class="fas fa-user-plus ml-2"></i>افزودن ادمین جدید</h2>
                            <form action="<?php echo BASE_PATH; ?>/settings" method="POST">
                                <input type="hidden" name="action" value="add_user">
                                <div class="space-y-4">
                                    <div><label for="new-user-fullname" class="block text-sm font-medium mb-1">نام و نام خانوادگی:</label><input type="text" id="new-user-fullname" name="full_name" required class="w-full bg-slate-700 p-2 rounded-md border border-slate-600 focus:ring-blue-500 focus:border-blue-500"></div>
                                    <div><label for="new-user-username" class="block text-sm font-medium mb-1">نام کاربری (برای ورود):</label><input type="text" id="new-user-username" name="username" required class="w-full bg-slate-700 p-2 rounded-md border border-slate-600 focus:ring-blue-500 focus:border-blue-500"></div>
                                    <div><label for="new-user-password" class="block text-sm font-medium mb-1">رمز عبور:</label><input type="password" id="new-user-password" name="password" required class="w-full bg-slate-700 p-2 rounded-md border border-slate-600 focus:ring-blue-500 focus:border-blue-500"></div>
                                    <div><label for="new-user-password-confirm" class="block text-sm font-medium mb-1">تکرار رمز عبور:</label><input type="password" id="new-user-password-confirm" name="password_confirm" required class="w-full bg-slate-700 p-2 rounded-md border border-slate-600 focus:ring-blue-500 focus:border-blue-500"></div>
                                </div>
                                <div class="mt-6 text-left"><button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-md transition-colors">افزودن کاربر</button></div>
                            </form>
                        </div>
                    </div>
                    <div class="bg-slate-800 p-8 rounded-xl shadow-lg flex flex-col">
                        <h2 class="text-xl font-semibold text-white mb-4 border-b border-slate-700 pb-3"><i class="fas fa-concierge-bell ml-2"></i>مدیریت خدمات</h2>
                        <div id="services-list" class="space-y-2 flex-grow overflow-y-auto pr-2 mb-6">
                            <?php if (empty($services)): ?>
                                <p class="text-slate-500 text-center py-4">هنوز سرویسی اضافه نشده است.</p>
                            <?php else: ?>
                                <?php foreach ($services as $service): ?>
                                <div class="bg-slate-700 p-3 rounded-lg" id="service-<?php echo $service['id']; ?>">
                                    <div class="service-view">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-semibold text-white"><?php echo htmlspecialchars($service['name']); ?></p>
                                                <p class="text-sm text-blue-400"><?php echo number_format($service['default_price']); ?> تومان</p>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <button onclick="editService(<?php echo $service['id']; ?>)" class="text-yellow-400 hover:text-yellow-300 transition-colors"><i class="fas fa-edit"></i></button>
                                                <button onclick="deleteService(<?php echo $service['id']; ?>)" class="text-red-500 hover:text-red-400 transition-colors"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="service-edit space-y-3">
                                        <input type="text" class="w-full bg-slate-600 p-2 rounded-md border border-slate-500" value="<?php echo htmlspecialchars($service['name']); ?>">
                                        <input type="number" step="1" class="w-full bg-slate-600 p-2 rounded-md border border-slate-500" value="<?php echo $service['default_price']; ?>">
                                        <div class="flex gap-2">
                                            <button onclick="saveService(<?php echo $service['id']; ?>)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-1 rounded-md transition-colors">ذخیره</button>
                                            <button onclick="cancelEdit(<?php echo $service['id']; ?>)" class="flex-1 bg-gray-600 hover:bg-gray-500 text-white py-1 rounded-md transition-colors">انصراف</button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="border-t border-slate-700 pt-4 mt-auto">
                             <h3 class="font-semibold mb-3">افزودن سرویس جدید</h3>
                             <form action="<?php echo BASE_PATH; ?>/settings" method="POST" class="flex items-center gap-2">
                                <input type="hidden" name="action" value="add_service">
                                 <input type="text" name="name" placeholder="نام سرویس" required class="flex-grow bg-slate-700 p-2 rounded-md border border-slate-600 focus:ring-blue-500 focus:border-blue-500">
                                 <input type="number" step="1" name="price" placeholder="قیمت" required class="w-32 bg-slate-700 p-2 rounded-md border border-slate-600 focus:ring-blue-500 focus:border-blue-500">
                                 <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold p-2 rounded-md transition-colors"><i class="fas fa-plus"></i></button>
                             </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // محو شدن پیام فلش پس از چند ثانیه
    if ($('#flash-msg').length) {
        setTimeout(function() {
            $('#flash-msg').slideUp('slow');
        }, 3000);
    }
});

function showView(serviceId) {
    $(`#service-${serviceId} .service-view`).show();
    $(`#service-${serviceId} .service-edit`).hide();
}

function showEdit(serviceId) {
    $(`#service-${serviceId} .service-view`).hide();
    $(`#service-${serviceId} .service-edit`).show();
}

function editService(id) {
    showEdit(id);
}

function cancelEdit(id) {
    // می‌توانید مقادیر را به حالت اولیه بازگردانید اگر کاربر تغییراتی داده باشد
    const container = $(`#service-${id}`);
    const originalName = container.find('.service-view .font-semibold').text();
    const originalPrice = container.find('.service-view .text-blue-400').text().replace(/[^0-9]/g, '');
    container.find('.service-edit input[type="text"]').val(originalName);
    container.find('.service-edit input[type="number"]').val(originalPrice);
    showView(id);
}

function saveService(id) {
    const container = $(`#service-${id}`);
    const name = container.find('.service-edit input[type="text"]').val();
    const price = container.find('.service-edit input[type="number"]').val();
    const ajaxUrl = '<?php echo BASE_PATH; ?>/settings';

    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'update_service',
            id: id,
            name: name,
            price: price
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                container.find('.service-view .font-semibold').text(name);
                container.find('.service-view .text-blue-400').text(new Intl.NumberFormat('fa-IR').format(price) + ' تومان');
                showView(id);
                Swal.fire({
                    icon: 'success',
                    title: 'موفق',
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                Swal.fire('خطا', response.message, 'error');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
            Swal.fire('خطا', 'خطایی در ارتباط با سرور رخ داد. لطفا کنسول را برای جزئیات بیشتر بررسی کنید.', 'error');
        }
    });
}

function deleteService(id) {
    const ajaxUrl = '<?php echo BASE_PATH; ?>/settings';
    
    Swal.fire({
        title: 'آیا مطمئن هستید؟',
        text: "این عمل غیرقابل بازگشت است!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بله، حذف کن!',
        cancelButtonText: 'انصراف'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_service',
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $(`#service-${id}`).slideUp(function() {
                            $(this).remove();
                        });
                        Swal.fire('حذف شد!', 'سرویس مورد نظر حذف گردید.', 'success');
                    } else {
                        Swal.fire('خطا', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('خطا', 'خطایی در ارتباط با سرور رخ داد.', 'error');
                }
            });
        }
    });
}
</script>

</body>
</html>