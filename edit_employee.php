<?php
// فعال‌سازی نمایش خطا و اتصال به دیتابیس
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "config.php";

// --- توابع کمکی برای تبدیل تاریخ ---
/**
 * 1. تبدیل اعداد فارسی/عربی به انگلیسی
 * @param string $string رشته ورودی
 * @return string رشته با اعداد انگلیسی
 */
function convertPersianNumbersToEnglish($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($persian, $english, str_replace($arabic, $english, $string));
}

/**
 * 2. تبدیل تاریخ شمسی (جلال) به میلادی
 * @param string|null $jalali_date تاریخ شمسی با فرمت YYYY-MM-DD یا YYYY/MM/DD
 * @return string|null تاریخ میلادی با فرمت YYYY-MM-DD یا null در صورت نامعتبر بودن
 */
function jalaliToGregorian($jalali_date) {
    if (empty($jalali_date)) return null;

    $jalali_date = convertPersianNumbersToEnglish($jalali_date);
    
    // اگر فرمت ورودی از قبل میلادی بود، همان را برگردان
    if (preg_match('/^[12][0-9]{3}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', $jalali_date)) {
        return $jalali_date;
    }

    // جدا کردن سال، ماه و روز
    $parts = preg_split('/[-\/]/', $jalali_date);
    if (count($parts) !== 3) return null; // فرمت نامعتبر
    
    list($jy, $jm, $jd) = array_map('intval', $parts);

    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    $jy += 1595;
    $days = -355668 + (365 * $jy) + (((int)($jy / 33)) * 8) + ((int)((($jy % 33) + 3) / 4)) + $jd;
    for ($i = 0; $i < $jm - 1; $i++) $days += $j_days_in_month[$i];
    
    $gy = 400 * ((int)($days / 146097));
    $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * ((int)(--$days / 36524));
        $days %= 36524;
        if ($days >= 365) $days++;
    }
    $gy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $gy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $gd = $days + 1;
    
    if (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) $g_days_in_month[1] = 29;

    $gm = 0;
    while ($gm < 12 && $gd > $g_days_in_month[$gm]) {
        $gd -= $g_days_in_month[$gm];
        $gm++;
    }
    $gm++;

    return sprintf("%04d-%02d-%02d", $gy, $gm, $gd);
}


$statusMessage = "";
$statusClass = "";
$employee_id = $_GET['id'] ?? 0;

// ۱. پردازش فرم اصلی (جزئیات کاربر)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'main_details') {
    // دریافت و پاکسازی اطلاعات
    $employee_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $national_code = $_POST['national_code']; // غیرقابل تغییر

    // تبدیل تاریخ به میلادی قبل از ذخیره
    $raw_birth_date = trim($_POST['birth_date']);
    $birth_date = jalaliToGregorian($raw_birth_date);
    
    $service_type = htmlspecialchars(trim($_POST['service_type']));
    $mobile = htmlspecialchars(trim($_POST['mobile']));
    $note = htmlspecialchars(trim($_POST['note']));

    // مسیر آپلود بر اساس کد ملی
    $upload_dir = "uploads/" . preg_replace('/[^0-9]/', '', $national_code) . "/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // دریافت مسیر عکس‌های فعلی از دیتابیس
    $stmt_photos = $conn->prepare("SELECT photo_path1, photo_path2, photo_path3, photo_path4, photo_path5, photo_path6 FROM employees WHERE id = ?");
    $stmt_photos->bind_param("i", $employee_id);
    $stmt_photos->execute();
    $current_photos = $stmt_photos->get_result()->fetch_assoc();
    $stmt_photos->close();

    $photo_paths = [];
    // پردازش آپلود عکس‌ها
    for ($i = 1; $i <= 6; $i++) {
        $file_input_name = 'photo_path' . $i;
        // اگر فایل جدیدی آپلود شده بود
        if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES[$file_input_name]['tmp_name'];
            $file_name = uniqid(time() . '_', true) . '_' . basename($_FILES[$file_input_name]['name']);
            $dest_path = $upload_dir . $file_name;

            // حذف عکس قدیمی (اگر وجود داشت)
            if (!empty($current_photos[$file_input_name]) && file_exists($current_photos[$file_input_name])) {
                @unlink($current_photos[$file_input_name]);
            }

            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                $photo_paths[$i - 1] = $dest_path;
            } else {
                $photo_paths[$i - 1] = $current_photos[$file_input_name]; // در صورت خطا، عکس قبلی باقی بماند
            }
        } else {
            // اگر فایل جدیدی آپلود نشده بود، مسیر قبلی را حفظ کن
            $photo_paths[$i - 1] = $current_photos[$file_input_name];
        }
    }
    
    // به‌روزرسانی اطلاعات در دیتابیس
    $sql_update = "UPDATE employees SET first_name=?, last_name=?, birth_date=?, service_type=?, mobile=?, note=?, photo_path1=?, photo_path2=?, photo_path3=?, photo_path4=?, photo_path5=?, photo_path6=? WHERE id=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssssssssssssi", $first_name, $last_name, $birth_date, $service_type, $mobile, $note, $photo_paths[0], $photo_paths[1], $photo_paths[2], $photo_paths[3], $photo_paths[4], $photo_paths[5], $employee_id);

    if ($stmt_update->execute()) {
        $statusMessage = "اطلاعات با موفقیت به‌روزرسانی شد.";
        $statusClass = "success";
    } else {
        $statusMessage = "خطا در به‌روزرسانی اطلاعات: " . $conn->error;
        $statusClass = "error";
    }
    $stmt_update->close();
}

// ۲. دریافت اطلاعات کاربر برای نمایش در فرم
if ($employee_id > 0) {
    $stmt_select = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt_select->bind_param("i", $employee_id);
    $stmt_select->execute();
    $user = $stmt_select->get_result()->fetch_assoc();
    $stmt_select->close();
} else {
    $user = null;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش اطلاعات کاربر</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        .message.success { color: #16a34a; background-color: #dcfce7; border: 1px solid #4ade80; }
        .message.error { color: #dc2626; background-color: #fee2e2; border: 1px solid #f87171; }
        
        /* استایل‌های بخش آپلود عکس */
        .photo-upload-container {
            position: relative;
            width: 128px; /* w-32 */
            height: 128px; /* h-32 */
            border-radius: 0.75rem; /* rounded-xl */
            overflow: hidden;
            border: 2px dashed #475569; /* border-slate-700 */
            transition: all 0.3s ease;
        }
        .photo-upload-container:hover {
            border-color: #3b82f6; /* hover:border-blue-500 */
        }
        .photo-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }
        .photo-upload-label {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: rgba(0,0,0,0.5);
            color: white;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .photo-upload-container:hover .photo-upload-label {
            opacity: 1;
        }
        .photo-upload-input {
            display: none;
        }
        
        /* استایل‌های پاپ‌آپ */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
            z-index: 40; display: none; opacity: 0; transition: opacity 0.3s ease;
        }
        .modal-content {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%) scale(0.95);
            background-color: #1e293b; z-index: 50; padding: 2rem;
            border-radius: 0.75rem; width: 90%; max-width: 600px;
            max-height: 90vh; overflow-y: auto; display: none;
            opacity: 0; transition: all 0.3s ease;
        }
        .modal-overlay.active, .modal-content.active {
            display: block;
            opacity: 1;
        }
        .modal-content.active {
             transform: translate(-50%, -50%) scale(1);
        }
        .close-btn {
            position: absolute; top: 1rem; left: 1.5rem; font-size: 2rem;
            color: #94a3b8; cursor: pointer; line-height: 1; transition: color 0.2s;
        }
        .close-btn:hover { color: #e2e8f0; }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 antialiased">

    <div class="flex">
        <?php require_once "inc/nav.php"; ?>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-white">ویرایش اطلاعات کاربر</h1>
                <a href="users.php" class="bg-slate-600 hover:bg-slate-500 px-4 py-2 rounded-lg text-white text-sm font-semibold transition-colors">بازگشت به لیست</a>
            </div>

            <div class="max-w-5xl mx-auto">
                <?php if ($user): ?>
                <form method="POST" enctype="multipart/form-data" class="bg-slate-800 p-6 sm:p-8 rounded-xl shadow-2xl space-y-8">
                    <input type="hidden" name="form_type" value="main_details">
                    <input type="hidden" id="employee_id" name="id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="national_code" value="<?php echo htmlspecialchars($user['national_code']); ?>">

                    <?php if (!empty($statusMessage)): ?>
                        <div class="message <?php echo $statusClass; ?> p-4 rounded-lg text-center font-semibold">
                            <?php echo $statusMessage; ?>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium mb-2">نام:</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required class="w-full bg-slate-700 p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border border-slate-600">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium mb-2">نام خانوادگی:</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required class="w-full bg-slate-700 p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border border-slate-600">
                        </div>
                        <div>
                            <label for="national_code_display" class="block text-sm font-medium mb-2">کد ملی (غیرقابل تغییر):</label>
                            <input type="text" id="national_code_display" value="<?php echo htmlspecialchars($user['national_code']); ?>" readonly class="w-full bg-slate-900 text-slate-400 p-2.5 rounded-lg cursor-not-allowed border border-slate-700">
                        </div>
                        <div>
                            <label for="birth_date_persian_input" class="block text-sm font-medium mb-2">تاریخ تولد:</label>
                            <input type="text" id="birth_date_persian_input" autocomplete="off" class="w-full bg-slate-700 p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border border-slate-600">
                            <input type="hidden" id="birth_date_gregorian" name="birth_date" value="<?php echo htmlspecialchars($user['birth_date']); ?>">
                        </div>
                         <div>
                            <label for="mobile" class="block text-sm font-medium mb-2">شماره موبایل:</label>
                            <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($user['mobile']); ?>" required class="w-full bg-slate-700 p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border border-slate-600" maxlength="11">
                        </div>
                        <div>
                            <label for="service_type" class="block text-sm font-medium mb-2">نوع خدمات:</label>
                            <input type="text" id="service_type" name="service_type" value="<?php echo htmlspecialchars($user['service_type']); ?>" class="w-full bg-slate-700 p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border border-slate-600">
                        </div>
                    </div>

                    <div>
                        <label for="note" class="block text-sm font-medium mb-2">یادداشت:</label>
                        <textarea id="note" name="note" class="w-full bg-slate-700 p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border border-slate-600" rows="4"><?php echo htmlspecialchars($user['note']); ?></textarea>
                    </div>
                    
                    <div class="border-t border-slate-700 pt-8">
                         <button type="button" id="manageCostsBtn" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition-transform hover:scale-[1.02] shadow-lg">
                            مدیریت هزینه‌ها و خدمات 📝
                        </button>
                    </div>

                    <div class="border-t border-slate-700 pt-8">
                        <h3 class="text-xl font-bold mb-6 text-white">ویرایش تصاویر 🖼️</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                            <div class="flex flex-col items-center">
                                <label for="photo_input_<?= $i ?>" class="photo-upload-container">
                                    <img id="photo_preview_<?= $i ?>" src="<?php echo !empty($user['photo_path'.$i]) && file_exists($user['photo_path'.$i]) ? htmlspecialchars($user['photo_path'.$i]) : 'https://via.placeholder.com/128x128.png?text=عکس+' . $i; ?>" alt="تصویر <?= $i ?>" class="photo-preview">
                                    <div class="photo-upload-label">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        <span>تغییر عکس</span>
                                    </div>
                                </label>
                                <input type="file" name="photo_path<?= $i ?>" id="photo_input_<?= $i ?>" class="photo-upload-input" data-preview-target="#photo_preview_<?= $i ?>" accept="image/*">
                                <span class="text-xs mt-2 text-slate-400">عکس <?= $i ?></span>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="pt-8 border-t border-slate-700">
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition-transform hover:scale-[1.02] shadow-lg">ذخیره تمام تغییرات</button>
                    </div>
                </form>
                <?php else: ?>
                    <div class="bg-slate-800 p-8 rounded-xl shadow-lg text-center"><p class="text-red-400 font-semibold text-lg">کاربری با این شناسه یافت نشد.</p></div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="costsModalOverlay" class="modal-overlay"></div>
    <div id="costsModalContent" class="modal-content">
        <span id="closeCostsModal" class="close-btn">&times;</span>
        <h2 class="text-2xl font-bold text-white mb-6">مدیریت هزینه‌ها</h2>

        <form id="addCostForm" class="space-y-4 mb-6 border-b border-slate-700 pb-6">
            <input type="hidden" name="employee_id" value="<?php echo $user ? $user['id'] : '0'; ?>">
            <div>
                <label for="item_name" class="block text-sm font-medium mb-2">شرح خدمات/کالا</label>
                <input type="text" name="item_name" id="item_name" required class="w-full bg-slate-700 p-2.5 rounded-lg border border-slate-600 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="quantity" class="block text-sm font-medium mb-2">تعداد</label>
                    <input type="number" name="quantity" id="quantity" value="1" min="1" required class="w-full bg-slate-700 p-2.5 rounded-lg border border-slate-600 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label for="price" class="block text-sm font-medium mb-2">مبلغ (تومان)</label>
                    <input type="number" name="price" id="price" min="0" required class="w-full bg-slate-700 p-2.5 rounded-lg border border-slate-600 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-transform hover:scale-[1.02]">افزودن هزینه</button>
            </div>
        </form>

        <h3 class="text-lg font-semibold text-white mb-4">لیست هزینه‌ها</h3>
        <div id="costsListContainer" class="space-y-3">
            </div>
    </div>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    <script>
        $(document).ready(function() {
            // --- راه‌اندازی DatePicker ---
            const initialDate = $('#birth_date_gregorian').val();
            $('#birth_date_persian_input').persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                initialValue: !!initialDate, // true اگر تاریخ وجود داشت
                altField: '#birth_date_gregorian',
                altFormat: 'YYYY-MM-DD',
                observer: true,
                calendar: {
                    persian: {
                        locale: 'fa'
                    }
                }
            });

            // --- مدیریت پیش‌نمایش عکس ---
            $('.photo-upload-input').on('change', function(event) {
                const targetPreview = $(this).data('preview-target');
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $(targetPreview).attr('src', e.target.result);
                    }
                    reader.readAsDataURL(file);
                }
            });

            // --- کدهای پاپ‌آپ هزینه‌ها ---
            const employeeId = $('#employee_id').val();
            const modalOverlay = $('#costsModalOverlay');
            const modalContent = $('#costsModalContent');

            // باز کردن پاپ‌آپ
            $('#manageCostsBtn').on('click', function() {
                loadCosts();
                modalOverlay.addClass('active');
                modalContent.addClass('active');
            });

            // بستن پاپ‌آپ
            function closeModal() {
                 modalOverlay.removeClass('active');
                 modalContent.removeClass('active');
            }
            $('#closeCostsModal, #costsModalOverlay').on('click', closeModal);

            // جلوگیری از بسته شدن پاپ آپ با کلیک روی محتوای آن
            modalContent.on('click', function(e) {
                 e.stopPropagation();
            });

            // تابع برای بارگذاری لیست هزینه‌ها
            function loadCosts() {
                const container = $('#costsListContainer');
                container.html('<p class="text-center text-slate-400">در حال بارگذاری...</p>'); 
                
                $.ajax({
                    url: 'ajax_costs_handler.php',
                    type: 'GET',
                    data: { action: 'get_costs', employee_id: employeeId },
                    dataType: 'json',
                    success: function(response) {
                        let html = '';
                        if (response.success && response.data.length > 0) {
                            response.data.forEach(function(cost) {
                                const priceFormatted = Number(cost.price).toLocaleString('fa-IR');
                                html += `<div class="flex items-center justify-between bg-slate-700 p-3 rounded-lg">
                                            <div>
                                                <p class="font-semibold text-white">${cost.item_name}</p>
                                                <p class="text-sm text-slate-400">${cost.quantity} عدد - ${priceFormatted} تومان</p>
                                            </div>
                                            <button class="delete-cost-btn bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-3 py-1.5 rounded-md transition-transform hover:scale-105" data-cost-id="${cost.id}">حذف</button>
                                         </div>`;
                            });
                        } else if (!response.success) {
                             html = `<p class="text-center text-red-400">${response.message}</p>`;
                        } else {
                            html = '<p class="text-center text-slate-500">هنوز هزینه‌ای ثبت نشده است.</p>';
                        }
                        container.html(html);
                    },
                    error: function(xhr) {
                        console.error("AJAX Error:", xhr.responseText);
                        container.html('<p class="text-center text-red-500">خطا در ارتباط با سرور. لطفاً کنسول را بررسی کنید.</p>');
                    }
                });
            }

            // افزودن هزینه جدید
            $('#addCostForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const submitButton = form.find('button[type="submit"]');
                submitButton.prop('disabled', true).text('در حال افزودن...');

                const formData = form.serialize() + '&action=add_cost';
                
                $.ajax({
                    url: 'ajax_costs_handler.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            loadCosts();
                            form[0].reset();
                        } else {
                            alert('خطا: ' + (response.message || 'مشکلی پیش آمد.'));
                        }
                    },
                    error: function() {
                        alert('خطای سرور در افزودن هزینه.');
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).text('افزودن هزینه');
                    }
                });
            });

            // حذف هزینه (با event delegation)
            $('#costsListContainer').on('click', '.delete-cost-btn', function() {
                if (!confirm('آیا برای حذف این هزینه مطمئن هستید؟')) {
                    return;
                }
                const costId = $(this).data('cost-id');
                const deleteButton = $(this);
                deleteButton.prop('disabled', true);

                $.ajax({
                    url: 'ajax_costs_handler.php',
                    type: 'POST',
                    data: { action: 'delete_cost', cost_id: costId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            deleteButton.closest('.flex').fadeOut(400, function() {
                                $(this).remove();
                                if ($('#costsListContainer').children().length === 0) {
                                    loadCosts();
                                }
                            });
                        } else {
                            alert('خطا: ' + (response.message || 'مشکلی در حذف پیش آمد.'));
                            deleteButton.prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('خطای سرور در حذف هزینه.');
                        deleteButton.prop('disabled', false);
                    }
                });
            });

        });
    </script>
</body>
</html>