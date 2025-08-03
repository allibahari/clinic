<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "config.php";
$username = $_SESSION['username'] ?? 'مدیر';

// تنظیم منطقه زمانی برای محاسبات صحیح تاریخ
date_default_timezone_set('Asia/Tehran');

/**
 * تابع کامل برای تبدیل تاریخ و زمان میلادی به شمسی
 */
function format_jalali_datetime($gregorian_datetime, $use_persian_digits = true) {
    if (empty($gregorian_datetime)) return '---';
    try {
        $date_obj = new DateTime($gregorian_datetime);
    } catch (Exception $e) {
        return 'تاریخ نامعتبر';
    }
    $time_part = $date_obj->format('H:i');
    $g_y = (int)$date_obj->format('Y'); $g_m = (int)$date_obj->format('m'); $g_d = (int)$date_obj->format('d');
    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
    $gy = $g_y - 1600; $gm = $g_m - 1; $gd = $g_d - 1;
    $g_day_no = 365 * $gy + floor(($gy + 3) / 4) - floor(($gy + 99) / 100) + floor(($gy + 399) / 400);
    for ($i = 0; $i < $gm; ++$i) $g_day_no += $g_days_in_month[$i];
    if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))) $g_day_no++;
    $g_day_no += $gd; $j_day_no = $g_day_no - 79;
    $j_np = floor($j_day_no / 12053); $j_day_no %= 12053;
    $jy = 979 + 33 * $j_np + 4 * floor($j_day_no / 1461); $j_day_no %= 1461;
    if ($j_day_no >= 366) { $jy += floor(($j_day_no - 1) / 365); $j_day_no = ($j_day_no - 1) % 365; }
    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) $j_day_no -= $j_days_in_month[$i];
    $jm = $i + 1; $jd = $j_day_no + 1;
    $jalali_date_part = "$jy/" . str_pad($jm, 2, '0', STR_PAD_LEFT) . "/" . str_pad($jd, 2, '0', STR_PAD_LEFT);
    $final_string = $jalali_date_part . ' ' . $time_part;
    if ($use_persian_digits) {
        $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str_replace(range(0, 9), $persian_digits, $final_string);
    }
    return $final_string;
}

// ۱. مدیریت تاریخ فعال برای فیلتر کردن
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$active_date = $_GET['date'] ?? $today;

// ۲. کوئری اصلی همیشه بر اساس تاریخ فعال فیلتر می‌شود
$sql_app = "SELECT app.*, doc.full_name as doctor_name 
            FROM appointments app 
            JOIN doctors doc ON app.doctor_id = doc.id
            WHERE DATE(app.appointment_time) = ?
            ORDER BY app.appointment_time ASC";
$stmt_app = $conn->prepare($sql_app);
if ($stmt_app) {
    $stmt_app->bind_param("s", $active_date);
    $stmt_app->execute();
    $appointments = $stmt_app->get_result();
} else {
    die("خطا در اجرای کوئری: " . $conn->error);
}

// دریافت لیست پزشکان و خدمات برای مودال
$doctors_result = $conn->query("SELECT id, full_name FROM doctors");
$doctors = $doctors_result ? $doctors_result->fetch_all(MYSQLI_ASSOC) : [];
$services_result = $conn->query("SELECT id, name FROM services WHERE is_active = 1 ORDER BY name");
$services = $services_result ? $services_result->fetch_all(MYSQLI_ASSOC) : [];

$status_map = ['booked' => ['text' => 'رزرو شده', 'class' => 'status-booked'],'arrived' => ['text' => 'حضور پیدا کرده', 'class' => 'status-arrived'],'in_room' => ['text' => 'در اتاق دکتر', 'class' => 'status-in-room'],'completed' => ['text' => 'اتمام کار', 'class' => 'status-completed'],'no_show' => ['text' => 'حضور پیدا نکرده', 'class' => 'status-no-show'],'cancelled' => ['text' => 'لغو شده', 'class' => 'status-cancelled']];
$conn->close();

// آماده‌سازی تاریخ برای نمایش در فیلد دیت‌پیکر
$jalali_display_date = '';
if (!in_array($active_date, [$today, $yesterday, $tomorrow])) {
    $jalali_full = format_jalali_datetime($active_date . ' 00:00:00', false);
    $jalali_display_date = explode(' ', $jalali_full)[0];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت نوبت‌ها</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { font-family: 'Vazirmatn', sans-serif; }
        .status-booked { background-color: rgba(59, 130, 246, 0.2); color: rgba(96, 165, 250, 1); }
        .status-arrived { background-color: rgba(234, 179, 8, 0.2); color: rgba(251, 191, 36, 1); }
        .status-in-room { background-color: rgba(139, 92, 246, 0.2); color: rgba(167, 139, 250, 1); }
        .status-completed { background-color: rgba(34, 197, 94, 0.2); color: rgba(74, 222, 128, 1); }
        .status-no-show { background-color: rgba(128, 128, 128, 0.2); color: rgba(156, 163, 175, 1); }
        .status-cancelled { background-color: rgba(239, 68, 68, 0.2); color: rgba(248, 113, 113, 1); }
        .modal-step { display: none; }
        .modal-step.active { display: block; }
        .date-tab { background-color: #334155; color: #cbd5e1; padding: 0.5rem 1rem; border-radius: 0.5rem; transition: all 0.2s; white-space: nowrap; }
        .date-tab:hover { background-color: #475569; }
        .date-tab.active { background-color: #3b82f6; color: white; font-weight: 600; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeInUp { animation: fadeInUp 0.5s ease-out forwards; }
        .table-row-animate { opacity: 0; animation: fadeInUp 0.5s ease-out forwards; }
        select option:disabled { color: #94a3b8; text-decoration: line-through; background-color: #334155; }

        /* --- START: Attractive Persian Datepicker Dark Theme --- */

        /* 1. General styles for the POP-UP container */
        .datepicker-container {
            background-color: #1e293b; /* slate-800 */
            border: 1px solid #334155; /* slate-700 */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem; /* rounded-xl */
        }

        /* 2. Header: Month/Year title and navigation arrows */
        .datepicker-container .pdp-header {
            background-color: transparent;
            border-bottom: 1px solid #334155;
            padding-bottom: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .datepicker-container .pdp-header .pdp-month-name,
        .datepicker-container .pdp-header .pdp-year-name {
            color: #f1f5f9; /* slate-100 */
            font-weight: 600;
            transition: color 0.2s;
        }
        .datepicker-container .pdp-header .pdp-month-name:hover,
        .datepicker-container .pdp-header .pdp-year-name:hover {
            color: #3b82f6; /* blue-500 */
        }
        .datepicker-container .pdp-header .pdp-nav-arrow {
            background-color: #334155;
            border-radius: 9999px;
            width: 2rem;
            height: 2rem;
            transition: background-color 0.2s;
        }
        .datepicker-container .pdp-header .pdp-nav-arrow:hover {
            background-color: #475569;
        }
        .datepicker-container .pdp-header .pdp-nav-arrow::before {
            border-color: #94a3b8;
        }

        /* 3. Weekday names (شنبه, یکشنبه, etc.) */
        .datepicker-container .pdp-week-day {
            color: #64748b; /* slate-500 */
            font-weight: 600;
        }

        /* 4. Individual day cells */
        .datepicker-container .pdp-day {
            color: #cbd5e1; /* slate-300 */
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
            border: 2px solid transparent;
        }
        .datepicker-container .pdp-day:not(.pdp-day-disabled):not(.pdp-day-selected):hover {
            background-color: #334155; /* slate-700 */
            color: white;
        }
        .datepicker-container .pdp-day.pdp-day-today:not(.pdp-day-selected) {
            border-color: #3b82f6; /* blue-500 */
            color: #f1f5f9; /* slate-100 */
        }
        .datepicker-container .pdp-day.pdp-day-selected {
            background-color: #3b82f6 !important;
            color: white !important;
            font-weight: 700;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
        }
        .datepicker-container .pdp-day.pdp-day-disabled {
            color: #475569 !important;
            background-color: transparent !important;
            text-decoration: none;
        }
         .datepicker-container .pdp-day.pdp-day-disabled:hover {
            cursor: not-allowed;
        }

        /* 5. Month/Year picker views */
        .datepicker-container .pdp-month-container,
        .datepicker-container .pdp-year-container {
            background-color: #1e293b;
        }
        .datepicker-container .pdp-month,
        .datepicker-container .pdp-year {
            color: #cbd5e1;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .datepicker-container .pdp-month:hover,
        .datepicker-container .pdp-year:hover {
            background-color: #334155;
        }
        .datepicker-container .pdp-month.pdp-month-selected,
        .datepicker-container .pdp-year.pdp-year-selected {
            background-color: #3b82f6;
            color: white;
        }

        /* 6. Styles for the INLINE calendar in the modal */
        #inline-datepicker-container .pdp-group {
            border: none;
            box-shadow: none;
            background-color: transparent;
        }
        #inline-datepicker-container .pdp-header { background-color: transparent; color: #cbd5e1; }
        #inline-datepicker-container .pdp-month-name,
        #inline-datepicker-container .pdp-year-name { color: #f1f5f9; }
        #inline-datepicker-container .pdp-nav-arrow:hover { background-color: #475569; }
        #inline-datepicker-container .pdp-week-day { color: #64748b; font-weight: 500; }
        #inline-datepicker-container .pdp-day { color: #cbd5e1; border-radius: 0.375rem; transition: all 0.2s; }
        #inline-datepicker-container .pdp-day:not(.pdp-day-disabled):hover { background-color: #475569; color: white; }
        #inline-datepicker-container .pdp-day.pdp-day-today:not(.pdp-day-selected) {
            background-color: rgba(59, 130, 246, 0.2) !important;
            color: white !important;
            border: 1px solid #3b82f6;
        }
        #inline-datepicker-container .pdp-day.pdp-day-selected {
            background-color: #3b82f6 !important;
            color: white !important;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);
        }
        #inline-datepicker-container .pdp-day-disabled {
            color: #475569 !important;
            background-color: transparent !important;
            text-decoration: none;
            position: relative;
        }
        #inline-datepicker-container .pdp-day-disabled::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 15%;
            right: 15%;
            border-top: 1px solid #334155;
        }
        /* --- END: Datepicker Theme --- */

        /* استایل برای دکمه‌های زمان */
        #time-slots-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.75rem;
            max-height: 200px;
            overflow-y: auto;
            padding-top: 0.5rem;
        }
        .time-slot { background-color: #334155; color: #cbd5e1; padding: 0.75rem; border-radius: 0.5rem; text-align: center; cursor: pointer; transition: all 0.2s; border: 2px solid transparent; }
        .time-slot:hover:not(.disabled) { background-color: #475569; color: white; }
        .time-slot.selected { background-color: #3b82f6; color: white; font-weight: 600; border-color: #60a5fa; }
        .time-slot.disabled { background-color: #1e293b; color: #475569; cursor: not-allowed; text-decoration: line-through; }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 font-sans">
    <div class="flex flex-col md:flex-row min-h-screen">
        <?php include "inc/nav.php"; ?>
        <main class="flex-1 p-4 sm:p-6">
            <header class="flex justify-between items-center mb-6 animate-fadeInUp" style="animation-delay: 0.1s;">
                <h1 class="text-3xl font-bold text-white">مدیریت نوبت‌ها</h1>
                <button id="openBookingModalBtn" class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-lg text-white text-sm transition-colors">افزودن نوبت جدید</button>
            </header>
            <div class="bg-slate-800 p-3 rounded-xl shadow-lg mb-6 animate-fadeInUp" style="animation-delay: 0.2s;">
                <div class="flex items-center gap-2 overflow-x-auto">
                    <a href="?date=<?php echo $yesterday; ?>" class="date-tab <?php echo ($active_date == $yesterday) ? 'active' : ''; ?>">دیروز</a>
                    <a href="?date=<?php echo $today; ?>" class="date-tab <?php echo ($active_date == $today) ? 'active' : ''; ?>">امروز</a>
                    <a href="?date=<?php echo $tomorrow; ?>" class="date-tab <?php echo ($active_date == $tomorrow) ? 'active' : ''; ?>">فردا</a>
                    <div class="relative">
                        <input type="text" id="tab_date_picker_p" 
                               value="<?php echo $jalali_display_date; ?>" 
                               placeholder="انتخاب تاریخ..." readonly
                               class="date-tab cursor-pointer <?php if (!in_array($active_date, [$today, $yesterday, $tomorrow])) echo 'active'; ?>">
                    </div>
                </div>
            </div>
            <div class="bg-slate-800 p-4 sm:p-6 rounded-xl shadow-lg animate-fadeInUp" style="animation-delay: 0.3s;">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-400 uppercase bg-slate-700/50">
                            <tr>
                                <th scope="col" class="px-6 py-3">بیمار</th><th scope="col" class="px-6 py-3">پزشک</th>
                                <th scope="col" class="px-6 py-3">زمان نوبت</th><th scope="col" class="px-6 py-3">وضعیت</th>
                                <th scope="col" class="px-6 py-3 text-center">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($appointments && $appointments->num_rows > 0): ?>
                                <?php $rowIndex = 0; ?>
                                <?php while($app = $appointments->fetch_assoc()): ?>
                                    <?php $delay = 300 + ($rowIndex * 60); ?>
                                    <tr class="border-b border-slate-700 hover:bg-slate-700/30 table-row-animate" style="animation-delay: <?php echo $delay; ?>ms;">
                                        <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($app['patient_name']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($app['doctor_name']); ?></td>
                                        <td class="px-6 py-4 text-cyan-400"><?php echo format_jalali_datetime($app['appointment_time']); ?></td>
                                        <td class="px-6 py-4">
                                            <?php $status_display = $status_map[$app['status']] ?? ['text' => $app['status'], 'class' => '']; ?>
                                            <span class="px-2 py-1 font-semibold text-xs rounded-full <?php echo $status_display['class']; ?>"><?php echo htmlspecialchars($status_display['text']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ($app['status'] === 'completed'): ?>
                                                <a href="employees.php?appointment_id=<?php echo $app['id']; ?>" class="text-green-400 font-semibold px-2 py-1 hover:underline">مشاهده پرونده</a>
                                            <?php else: ?>
                                                <select onchange="updateStatus(this, <?php echo $app['id']; ?>)" class="bg-slate-600 border border-slate-500 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
                                                    <option selected disabled>تغییر وضعیت...</option>
                                                    <option value="arrived" <?php echo ($app['status'] == 'arrived') ? 'selected' : ''?>>حضور پیدا کرده</option>
                                                    <option value="in_room" <?php echo ($app['status'] == 'in_room') ? 'selected' : ''?>>در اتاق دکتر</option>
                                                    <option value="completed">اتمام کار</option>
                                                    <option value="no_show" <?php echo ($app['status'] == 'no_show') ? 'selected' : ''?>>حضور پیدا نکرده</option>
                                                    <option value="cancelled" <?php echo ($app['status'] == 'cancelled') ? 'selected' : ''?>>لغو شده</option>
                                                </select>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php $rowIndex++; ?>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-8 text-slate-400">هیچ نوبتی برای این روز یافت نشد.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="bookingModal" class="fixed inset-0 bg-black bg-opacity-70 flex justify-center items-center z-50 hidden">
        <div class="bg-slate-800 rounded-xl shadow-2xl p-8 w-full max-w-2xl"> <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">ثبت نوبت جدید</h2>
                <button id="closeModalBtn" class="text-slate-400 hover:text-white text-3xl">&times;</button>
            </div>
            <form id="bookingForm" novalidate>
                <div id="step1" class="modal-step active">
                    <h3 class="text-lg font-semibold mb-4 text-cyan-400">مرحله ۱: انتخاب پزشک</h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <?php if(!empty($doctors)): foreach($doctors as $doc): ?>
                            <label class="block">
                                <input type="radio" name="doctor_id" value="<?php echo $doc['id']; ?>" class="hidden peer" required>
                                <div class="p-4 bg-slate-700 rounded-lg cursor-pointer hover:bg-slate-600 border-2 border-transparent peer-checked:border-blue-500 peer-checked:bg-blue-900/50 transition-all">
                                    <p class="font-semibold text-white"><?php echo htmlspecialchars($doc['full_name']); ?></p>
                                </div>
                            </label>
                        <?php endforeach; else: ?>
                            <p>پزشکی برای انتخاب وجود ندارد.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="step2" class="modal-step">
                    <h3 class="text-lg font-semibold mb-4 text-cyan-400">مرحله ۲: اطلاعات بیمار و علت مراجعه</h3>
                    <div class="space-y-4">
                        <div><label for="patient_name" class="block text-sm font-medium mb-1">نام و نام خانوادگی بیمار <span class="text-red-500">*</span></label><input type="text" id="patient_name" name="patient_name" required class="w-full bg-slate-700 p-2 rounded-md"></div>
                        <div><label for="patient_mobile" class="block text-sm font-medium mb-1">شماره موبایل <span class="text-red-500">*</span></label><input type="text" id="patient_mobile" name="patient_mobile" required class="w-full bg-slate-700 p-2 rounded-md" maxlength="11"></div>
                        <div><label for="patient_national_code" class="block text-sm font-medium mb-1">کد ملی</label><input type="text" id="patient_national_code" name="patient_national_code" class="w-full bg-slate-700 p-2 rounded-md" maxlength="10"></div>
                        <div><label for="service_id" class="block text-sm font-medium mb-1">علت مراجعه <span class="text-red-500">*</span></label><select id="service_id" name="service_id" required class="w-full bg-slate-700 p-2 rounded-md"><option value="">انتخاب کنید...</option><?php if(!empty($services)): foreach($services as $service): ?><option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option><?php endforeach; endif; ?></select></div>
                    </div>
                </div>

                <div id="step3" class="modal-step">
                    <h3 class="text-lg font-semibold mb-2 text-cyan-400">مرحله ۳: انتخاب تاریخ و ساعت</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">تاریخ نوبت <span class="text-red-500">*</span></label>
                            <div id="inline-datepicker-container" class="bg-slate-700/50 rounded-lg p-2"></div>
                            <input type="hidden" id="appointment_date_g_modal" name="appointment_date" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">ساعت نوبت <span class="text-red-500">*</span></label>
                            <div id="time-slots-container" class="mt-2">
                                <p class="text-slate-400 text-sm">ابتدا تاریخ را انتخاب کنید...</p>
                            </div>
                            <input type="hidden" id="appointment_time_hidden" name="appointment_time" required>
                        </div>
                    </div>
                </div>
            </form>
            <div class="mt-8 flex justify-between items-center">
                <button id="backBtn" class="bg-slate-600 hover:bg-slate-700 px-6 py-2 rounded-lg text-white transition-colors invisible">قبلی</button>
                <button id="nextBtn" class="bg-blue-500 hover:bg-blue-600 px-6 py-2 rounded-lg text-white transition-colors">بعدی</button>
                <button id="submitBtn" type="submit" form="bookingForm" class="bg-green-600 hover:bg-green-700 px-6 py-2 rounded-lg text-white transition-colors hidden">ثبت نوبت</button>
            </div>
        </div>
    </div>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-date/dist/persian-date.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    <script>
        function updateStatus(selectElement, id) {
            const newStatus = selectElement.value;
            if (newStatus === 'completed') {
                Swal.fire({title: 'تکمیل نوبت و ثبت پرونده', text: 'برای تکمیل این نوبت، به صفحه ایجاد پرونده بیمار هدایت می‌شوید. ادامه می‌دهید؟', icon: 'info', showCancelButton: true, confirmButtonText: 'بله، ادامه بده', cancelButtonText: 'انصراف'}).then(result => { if (result.isConfirmed) window.location.href = `employees.php?appointment_id=${id}`; else selectElement.selectedIndex = 0; });
                return;
            }
            const statusText = selectElement.options[selectElement.selectedIndex].text;
            Swal.fire({title: `تغییر وضعیت به "${statusText}"`, text: "آیا از انجام این کار مطمئن هستید؟", icon: 'warning', showCancelButton: true, confirmButtonText: 'بله، تغییر بده', cancelButtonText: 'انصراف'}).then(result => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('status', newStatus);
                    fetch('api/update_appointment_status.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
                        if (data.success) { Swal.fire('موفق!', 'وضعیت با موفقیت تغییر کرد.', 'success').then(() => location.reload()); } else { Swal.fire('خطا!', data.message || 'مشکلی رخ داد.', 'error'); }
                    });
                } else { selectElement.selectedIndex = 0; }
            });
        }

        $(document).ready(function() {
            const modal = $('#bookingModal'),
                  form = $('#bookingForm'),
                  timeSlotsContainer = $('#time-slots-container'),
                  hiddenTimeInput = $('#appointment_time_hidden');
            let currentStep = 1;
            const totalSteps = 3;

            const clinicTimeSlots = ["09:00", "09:30", "10:00", "10:30", "11:00", "11:30", "12:00", "12:30", "16:00", "16:30", "17:00", "17:30"];
            
            function updateAvailableTimes() {
                const doctorId = $('input[name="doctor_id"]:checked').val();
                const date = $('#appointment_date_g_modal').val();
                
                hiddenTimeInput.val('');
                $('.time-slot').removeClass('selected');


                if (!doctorId || !date) return;

                timeSlotsContainer.html('<p class="text-slate-400 text-sm">در حال بارگذاری ساعات...</p>');

                fetch(`api/get_available_times.php?doctor_id=${doctorId}&date=${date}`)
                    .then(response => response.json())
                    .then(data => {
                        timeSlotsContainer.empty();
                        if (data.success) {
                            const bookedTimes = data.booked_times;
                            clinicTimeSlots.forEach(time => {
                                const isBooked = bookedTimes.includes(time);
                                const timeSlotDiv = `<div class="time-slot ${isBooked ? 'disabled' : ''}" data-time="${time}">${time}</div>`;
                                timeSlotsContainer.append(timeSlotDiv);
                            });
                        } else {
                            timeSlotsContainer.html('<p class="text-red-400 text-sm">خطا در دریافت ساعات.</p>');
                            Swal.fire('خطا', 'مشکلی در دریافت ساعات در دسترس رخ داد.', 'error');
                        }
                    })
                    .catch(() => {
                        timeSlotsContainer.html('<p class="text-red-400 text-sm">خطای ارتباط با سرور.</p>');
                        Swal.fire('خطای ارتباط', 'اتصال به سرور برای دریافت ساعات ممکن نبود.', 'error');
                    });
            }
            
            timeSlotsContainer.on('click', '.time-slot:not(.disabled)', function() {
                const selectedTime = $(this).data('time');
                $('.time-slot').removeClass('selected');
                $(this).addClass('selected');
                hiddenTimeInput.val(selectedTime);
            });

            $('#tab_date_picker_p').persianDatepicker({
                format: 'YYYY/MM/DD', autoClose: true,
                onSelect: unix => window.location.href = `?date=${new persianDate(unix).format('YYYY-MM-DD')}`
            });

$('#inline-datepicker-container').persianDatepicker({
    inline: true,
    altField: '#appointment_date_g_modal',
    altFormat: 'YYYY-MM-DD',
    // ✨ تغییر اصلی اینجاست
    minDate: new persianDate().startOf('day'), 
    onSelect: function() {
        updateAvailableTimes();
    }
});

            $('input[name="doctor_id"]').on('change', () => {
                if ($('#appointment_date_g_modal').val()) {
                    updateAvailableTimes();
                }
            });

            $('#openBookingModalBtn').on('click', function() {
                form[0].reset();
                currentStep = 1;
                showStep(currentStep);
                timeSlotsContainer.html('<p class="text-slate-400 text-sm">ابتدا تاریخ را انتخاب کنید...</p>');
                hiddenTimeInput.val('');
                $('.time-slot').removeClass('selected');
                const pdp = $('#inline-datepicker-container').data('datepicker');
                if (pdp) pdp.setDate(null);
                 $('#appointment_date_g_modal').val('');
                modal.removeClass('hidden');
            });

            $('#closeModalBtn').on('click', () => modal.addClass('hidden'));
            $('#nextBtn').on('click', () => { if (validateStep(currentStep) && currentStep < totalSteps) { showStep(++currentStep); } });
            $('#backBtn').on('click', () => { if (currentStep > 1) { showStep(--currentStep); } });

            function showStep(step) {
                $('.modal-step').removeClass('active');
                $(`#step${step}`).addClass('active');
                $('#backBtn').toggleClass('invisible', step === 1);
                $('#nextBtn').toggleClass('hidden', step === totalSteps);
                $('#submitBtn').toggleClass('hidden', step !== totalSteps);
            }

            function validateStep(step) {
                let isValid = true;
                $(`#step${step} [required]`).each(function() {
                    if (($(this).is(':radio') && !$(`input[name="${$(this).attr('name')}"]:checked`).length) || (!$(this).is(':radio') && !$(this).val())) {
                        isValid = false;
                        $(this).addClass('border-red-500').focus();
                        setTimeout(() => $(this).removeClass('border-red-500'), 3000);
                        return false;
                    }
                });
                if (!isValid) Swal.fire('خطا', 'لطفا تمام فیلدهای ستاره‌دار را تکمیل کنید.', 'error');
                return isValid;
            }

            form.on('submit', function(e) {
                e.preventDefault();
                if (!validateStep(currentStep)) return;

                Swal.fire({ title: 'در حال ثبت نوبت...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                fetch('api/create_appointment.php', { method: 'POST', body: new FormData(this) })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('موفقیت!', 'نوبت با موفقیت ثبت شد.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('خطا!', data.message || 'مشکلی در ثبت رخ داد.', 'error');
                        }
                    })
                    .catch(() => Swal.fire('خطای ارتباط!', 'لطفا اتصال اینترنت را بررسی کنید.', 'error'));
            });
        });
    </script>
</body>
</html>