<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "config.php";

// ---- این بخش، فرم ارسال شده را پردازش می‌کند ----
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // شروع تراکنش برای اطمینان از ثبت کامل اطلاعات
    $conn->begin_transaction();

    try {
        // ۱. دریافت اطلاعات پزشک
        $full_name = $_POST['full_name'];
        $specialty = $_POST['specialty'];
        $address = $_POST['address'];
        $profile_image_path = '';

        // پردازش آپلود عکس پروفایل
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = "doctor_" . time() . "_" . basename($_FILES['profile_image']['name']);
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $profile_image_path = $target_file;
            }
        }

        // ۲. ذخیره اطلاعات اصلی پزشک
        $sql_doctor = "INSERT INTO doctors (full_name, specialty, address, profile_image_path) VALUES (?, ?, ?, ?)";
        $stmt_doctor = $conn->prepare($sql_doctor);
        $stmt_doctor->bind_param("ssss", $full_name, $specialty, $address, $profile_image_path);
        $stmt_doctor->execute();
        
        // دریافت ID پزشکی که همین الان اضافه شد
        $new_doctor_id = $conn->insert_id;
        if ($new_doctor_id == 0) {
            throw new Exception("خطا در ایجاد پزشک جدید.");
        }

        // ۳. ذخیره برنامه هفتگی پزشک
        $schedule_data = $_POST['schedule'] ?? [];
        $slot_duration = (int)($_POST['slot_duration'] ?? 20); // یک مدت زمان ثابت برای همه

        $sql_schedule = "INSERT INTO doctor_weekly_schedule (doctor_id, day_of_week, start_time, end_time, slot_duration, is_active) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt_schedule = $conn->prepare($sql_schedule);

        $days_map = ['saturday' => 0, 'sunday' => 1, 'monday' => 2, 'tuesday' => 3, 'wednesday' => 4, 'thursday' => 5, 'friday' => 6];

        foreach ($schedule_data as $day_name => $details) {
            if (isset($details['is_active'])) {
                $day_of_week = $days_map[$day_name];
                $start_time = $details['start_time'];
                $end_time = $details['end_time'];
                
                // فقط اگر ساعت شروع و پایان معتبر بود، ذخیره کن
                if (!empty($start_time) && !empty($end_time)) {
                    $stmt_schedule->bind_param("iissi", $new_doctor_id, $day_of_week, $start_time, $end_time, $slot_duration);
                    $stmt_schedule->execute();
                }
            }
        }
        $stmt_schedule->close();

        // اگر همه چیز موفق بود، تراکنش را نهایی کن
        $conn->commit();

        $_SESSION['flash_message'] = "پزشک جدید و برنامه زمانی او با موفقیت اضافه شد.";
        $_SESSION['flash_type'] = "success";
        header("Location: doctors.php");
        exit();

    } catch (Exception $e) {
        // اگر خطایی رخ داد، تمام تغییرات را به حالت اول برگردان
        $conn->rollback();
        $_SESSION['flash_message'] = "خطا: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>افزودن پزشک جدید</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        .time-inputs { transition: all 0.3s ease; }
        .time-inputs.disabled { opacity: 0.4; pointer-events: none; }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 font-sans">
    <div class="flex">
        <?php require_once "inc/nav.php"; ?>
        <main class="flex-1 p-6">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-white">افزودن پزشک جدید</h1>
                <a href="doctors.php" class="bg-slate-600 hover:bg-slate-500 px-4 py-2 rounded-md text-white text-sm">بازگشت</a>
            </div>

            <div class="max-w-3xl mx-auto">
                <form method="POST" action="add_doctor.php" enctype="multipart/form-data">
                    <div class="bg-slate-800 p-8 rounded-xl shadow-lg mb-8">
                        <h2 class="text-xl font-bold text-white mb-6 border-b border-slate-700 pb-3">اطلاعات پایه</h2>
                        <div class="space-y-6">
                            <div>
                                <label for="full_name" class="block text-sm font-medium mb-1">نام کامل پزشک:</label>
                                <input type="text" id="full_name" name="full_name" required class="w-full bg-slate-700 p-3 rounded-md">
                            </div>
                            <div>
                                <label for="specialty" class="block text-sm font-medium mb-1">تخصص:</label>
                                <input type="text" id="specialty" name="specialty" required class="w-full bg-slate-700 p-3 rounded-md">
                            </div>
                            <div>
                                <label for="address" class="block text-sm font-medium mb-1">آدرس مطب:</label>
                                <textarea id="address" name="address" rows="3" class="w-full bg-slate-700 p-3 rounded-md"></textarea>
                            </div>
                            <div>
                                <label for="profile_image" class="block text-sm font-medium mb-1">عکس پروفایل:</label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/*" class="w-full text-sm">
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-slate-800 p-8 rounded-xl shadow-lg">
                        <h2 class="text-xl font-bold text-white mb-6 border-b border-slate-700 pb-3">تنظیم برنامه هفتگی پیش‌فرض</h2>
                        <div class="space-y-4">
                             <div>
                                <label for="slot_duration" class="block text-sm font-medium mb-1">مدت زمان هر نوبت (دقیقه):</label>
                                <input type="number" id="slot_duration" name="slot_duration" value="20" class="w-full bg-slate-700 p-3 rounded-md">
                            </div>
                            <?php
                            $days = [
                                'saturday' => 'شنبه', 'sunday' => 'یکشنبه', 'monday' => 'دوشنبه', 
                                'tuesday' => 'سه‌شنبه', 'wednesday' => 'چهارشنبه', 'thursday' => 'پنج‌شنبه', 'friday' => 'جمعه'
                            ];
                            foreach ($days as $eng_day => $fa_day):
                            ?>
                            <div class="p-4 rounded-lg bg-slate-700/50 flex flex-wrap items-center gap-4">
                                <div class="flex items-center w-full md:w-auto">
                                    <input id="<?php echo $eng_day; ?>_active" name="schedule[<?php echo $eng_day; ?>][is_active]" type="checkbox" class="h-5 w-5 rounded" onchange="toggleTimeInputs(this, '<?php echo $eng_day; ?>')">
                                    <label for="<?php echo $eng_day; ?>_active" class="mr-3 font-bold text-white w-24"><?php echo $fa_day; ?></label>
                                </div>
                                <div id="<?php echo $eng_day; ?>_times" class="time-inputs disabled flex-grow grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="<?php echo $eng_day; ?>_start" class="text-xs">ساعت شروع:</label>
                                        <input type="time" id="<?php echo $eng_day; ?>_start" name="schedule[<?php echo $eng_day; ?>][start_time]" class="w-full bg-slate-600 p-2 rounded-md text-sm">
                                    </div>
                                    <div>
                                        <label for="<?php echo $eng_day; ?>_end" class="text-xs">ساعت پایان:</label>
                                        <input type="time" id="<?php echo $eng_day; ?>_end" name="schedule[<?php echo $eng_day; ?>][end_time]" class="w-full bg-slate-600 p-2 rounded-md text-sm">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mt-8 text-left">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-md transition-colors">
                            <i class="fas fa-check ml-2"></i>
                            ذخیره نهایی پزشک
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

<script>
function toggleTimeInputs(checkbox, day) {
    const timeInputsContainer = document.getElementById(day + '_times');
    if (checkbox.checked) {
        timeInputsContainer.classList.remove('disabled');
    } else {
        timeInputsContainer.classList.add('disabled');
    }
}
</script>

</body>
</html>