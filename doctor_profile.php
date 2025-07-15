<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "config.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("شناسه پزشک مشخص نشده است.");
}
$doctor_id = (int)$_GET['id'];

// ۱. دریافت اطلاعات پزشک
$stmt_doc = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt_doc->bind_param("i", $doctor_id);
$stmt_doc->execute();
$doctor = $stmt_doc->get_result()->fetch_assoc();
if (!$doctor) {
    die("پزشکی با این شناسه یافت نشد.");
}

// ۲. دریافت برنامه هفتگی پزشک
$stmt_schedule = $conn->prepare("SELECT * FROM doctor_weekly_schedule WHERE doctor_id = ? AND is_active = 1");
$stmt_schedule->bind_param("i", $doctor_id);
$stmt_schedule->execute();
$schedule_result = $stmt_schedule->get_result();
$weekly_schedule = [];
while ($row = $schedule_result->fetch_assoc()) {
    $weekly_schedule[$row['day_of_week']] = $row;
}

// ۳. دریافت تمام نوبت‌های رزرو شده برای ۷ روز آینده
$stmt_booked = $conn->prepare("SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_time BETWEEN NOW() AND NOW() + INTERVAL 7 DAY");
$stmt_booked->bind_param("i", $doctor_id);
$stmt_booked->execute();
$booked_result = $stmt_booked->get_result();
$booked_slots = [];
while ($row = $booked_result->fetch_assoc()) {
    $booked_slots[] = $row['appointment_time'];
}

$conn->close();

//----- منطق جدید برای ایجاد ماتریس زمان/روز -----

$today = new DateTime("now", new DateTimeZone('Asia/Tehran'));
$days = [];
$persian_days = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه', 'شنبه'];

// ایجاد لیست روزها برای ۷ روز آینده
for ($i = 0; $i < 7; $i++) {
    $day = (clone $today)->add(new DateInterval("P{$i}D"));
    $day_of_week_php = $day->format('w'); // 0 (یکشنبه) تا 6 (شنبه)

    // تطبیق با ایندکس دیتابیس شما
    if ($day_of_week_php == 6) { // شنبه در PHP ایندکس 6 است
        $our_day_index = 0; // در دیتابیس شما 0 است
    } else { // سایر روزها
        $our_day_index = $day_of_week_php + 1;
    }
    $days[] = [
        'date' => $day,
        'db_index' => $our_day_index,
        'persian_name' => ($i == 0) ? 'امروز' : $persian_days[$day_of_week_php]
    ];
}

// پیدا کردن تمام اسلات‌های زمانی ممکن در هفته
$all_time_slots = [];
$min_duration = 60; // یک پیش‌فرض
foreach ($weekly_schedule as $rule) {
    $start = new DateTime($rule['start_time']);
    $end = new DateTime($rule['end_time']);
    $duration = (int)$rule['slot_duration'];
    $min_duration = min($min_duration, $duration); // برای اطمینان از پوشش همه اسلات‌ها
    
    while ($start < $end) {
        $time_key = $start->format('H:i');
        if (!in_array($time_key, $all_time_slots)) {
            $all_time_slots[] = $time_key;
        }
        $start->add(new DateInterval("PT{$duration}M"));
    }
}
sort($all_time_slots); // مرتب‌سازی زمان‌ها

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <title>رزرو نوبت برای <?php echo htmlspecialchars($doctor['full_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { font-family: 'Vazirmatn', sans-serif; background-color: #f1f5f9; }
        .slot-btn { transition: all 0.2s ease-in-out; }
        .slot-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .slot-btn[disabled] { opacity: 0.5; cursor: not-allowed; background-color: #e2e8f0; }
        .time-label { display: flex; align-items: center; justify-content: center; height: 40px; } /* برای هم‌ترازی با دکمه‌ها */
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-xl p-6 md:p-8">
        <div class="flex items-start pb-6 border-b">
            <img src="<?php echo htmlspecialchars($doctor['profile_image_path'] ?: 'img/default_avatar.png'); ?>" class="w-20 h-20 rounded-full ml-5">
            <div>
                <h1 class="text-2xl font-bold text-slate-800"><?php echo htmlspecialchars($doctor['full_name']); ?></h1>
                <p class="text-slate-600"><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                <p class="text-sm text-slate-500 mt-2 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                    <?php echo htmlspecialchars($doctor['address']); ?>
                </p>
            </div>
        </div>

        <div class="pt-6 overflow-x-auto">
            <h2 class="font-bold text-lg mb-4 text-slate-700">انتخاب نوبت</h2>
            <div class="grid grid-cols-8 gap-1 text-center">
                <div class="sticky top-0 bg-white z-10"></div>
                <?php foreach ($days as $day_info): ?>
                    <div class="p-2 rounded-lg bg-slate-50 sticky top-0 bg-white z-10 border-b-2">
                        <h3 class="font-semibold text-sm"><?php echo $day_info['persian_name']; ?></h3>
                        <p class="text-xs text-slate-500"><?php echo $day_info['date']->format('d / m'); ?></p>
                    </div>
                <?php endforeach; ?>
                
                <?php foreach ($all_time_slots as $time): ?>
                    <div class="p-2 flex items-center justify-center">
                       <span class="text-sm font-semibold text-slate-600"><?php echo $time; ?></span>
                    </div>

                    <?php foreach ($days as $day_info): ?>
                        <div class="p-1 flex items-center justify-center">
                            <?php
                            $slot_is_available = false;
                            $slot_is_booked = false;
                            
                            // آیا این روز در برنامه پزشک هست؟
                            if (isset($weekly_schedule[$day_info['db_index']])) {
                                $rule = $weekly_schedule[$day_info['db_index']];
                                $current_slot_time = new DateTime($time);
                                $slot_start_time = new DateTime($rule['start_time']);
                                $slot_end_time = new DateTime($rule['end_time']);

                                // آیا زمان فعلی در بازه کاری این روز قرار دارد؟
                                if ($current_slot_time >= $slot_start_time && $current_slot_time < $slot_end_time) {
                                    $slot_is_available = true;
                                    $full_datetime_str = $day_info['date']->format('Y-m-d') . ' ' . $time . ':00';
                                    if (in_array($full_datetime_str, $booked_slots)) {
                                        $slot_is_booked = true;
                                    }
                                }
                            }

                            if ($slot_is_available) {
                                ?>
                                <button
                                    class="slot-btn w-full text-sm font-semibold p-2 rounded-md <?php echo $slot_is_booked ? 'bg-slate-200 text-slate-400' : 'bg-blue-100 text-blue-700 hover:bg-blue-600 hover:text-white'; ?>"
                                    <?php echo $slot_is_booked ? 'disabled' : ''; ?>
                                    onclick="getPatientInfo('<?php echo $day_info['date']->format('Y-m-d') . ' ' . $time; ?>')">
                                    <?php echo $time; ?>
                                </button>
                                <?php
                            } else {
                                // نمایش یک فضای خالی یا خط تیره اگر نوبتی در این ساعت وجود ندارد
                                echo '<span class="text-slate-300">-</span>';
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<script>
function getPatientInfo(appointmentTime) {
    const doctorId = <?php echo $doctor_id; ?>;
    const timePart = appointmentTime.split(' ')[1];
    Swal.fire({
        title: 'تکمیل اطلاعات',
        html: `
            <p class="text-sm text-slate-600 mb-4">شما در حال رزرو نوبت برای ساعت ${timePart} هستید.</p>
            <input id="swal-name" class="swal2-input" placeholder="نام و نام خانوادگی">
            <input id="swal-mobile" class="swal2-input" placeholder="شماره موبایل">
        `,
        confirmButtonText: 'ثبت نهایی',
        showCancelButton: true,
        cancelButtonText: 'انصراف',
        preConfirm: () => {
            const name = document.getElementById('swal-name').value;
            const mobile = document.getElementById('swal-mobile').value;
            if (!name || !mobile) {
                Swal.showValidationMessage('پر کردن تمام فیلدها الزامی است');
            }
            return { name, mobile };
        }
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('doctor_id', doctorId);
            formData.append('patient_name', result.value.name);
            formData.append('mobile', result.value.mobile);
            formData.append('appointment_time', appointmentTime + ':00'); // اضافه کردن ثانیه برای تطابق با دیتابیس

            fetch('api/book_appointment.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('موفق!', 'نوبت شما با موفقیت ثبت شد.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('خطا!', data.message || 'مشکلی در ثبت نوبت پیش آمد.', 'error');
                    }
                });
        }
    });
}
</script>
</body>
</html>