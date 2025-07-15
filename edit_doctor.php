<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "config.php";

$doctor = null;
$doctor_id = 0;

// ---- پردازش فرم ویرایش (POST) ----
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        $doctor_id = (int)$_POST['id'];
        $full_name = $_POST['full_name'];
        $specialty = $_POST['specialty'];
        $address = $_POST['address'];
        $profile_image_path = $_POST['existing_image_path'];

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_name = "doctor_" . time() . "_" . basename($_FILES['profile_image']['name']);
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                if (!empty($profile_image_path) && file_exists($profile_image_path)) {
                    @unlink($profile_image_path);
                }
                $profile_image_path = $target_file;
            }
        }
        
        $sql_doctor = "UPDATE doctors SET full_name = ?, specialty = ?, address = ?, profile_image_path = ? WHERE id = ?";
        $stmt_doctor = $conn->prepare($sql_doctor);
        $stmt_doctor->bind_param("ssssi", $full_name, $specialty, $address, $profile_image_path, $doctor_id);
        $stmt_doctor->execute();

        // ---- آپدیت برنامه هفتگی (حذف قبلی‌ها و ثبت جدید) ----
        $conn->query("DELETE FROM doctor_weekly_schedule WHERE doctor_id = $doctor_id");

        $schedule_data = $_POST['schedule'] ?? [];
        $slot_duration = (int)($_POST['slot_duration'] ?? 20);
        $sql_schedule = "INSERT INTO doctor_weekly_schedule (doctor_id, day_of_week, start_time, end_time, slot_duration, is_active) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt_schedule = $conn->prepare($sql_schedule);
        $days_map = ['saturday' => 0, 'sunday' => 1, 'monday' => 2, 'tuesday' => 3, 'wednesday' => 4, 'thursday' => 5, 'friday' => 6];

        foreach ($schedule_data as $day_name => $details) {
            if (isset($details['is_active']) && !empty($details['start_time']) && !empty($details['end_time'])) {
                $day_of_week = $days_map[$day_name];
                $stmt_schedule->bind_param("iissi", $doctor_id, $day_of_week, $details['start_time'], $details['end_time'], $slot_duration);
                $stmt_schedule->execute();
            }
        }
        $stmt_schedule->close();

        $conn->commit();
        $_SESSION['flash_message'] = "اطلاعات پزشک با موفقیت به‌روز شد.";
        header("Location: doctors.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_message'] = "خطا در به‌روزرسانی: " . $e->getMessage();
    }
}

// ---- خواندن اطلاعات پزشک و برنامه هفتگی او برای نمایش در فرم ----
$weekly_schedule = [];
if (isset($_GET['id'])) {
    $doctor_id = (int)$_GET['id'];
    $sql_select_doctor = "SELECT * FROM doctors WHERE id = ?";
    $stmt_select_doctor = $conn->prepare($sql_select_doctor);
    $stmt_select_doctor->bind_param("i", $doctor_id);
    $stmt_select_doctor->execute();
    $result = $stmt_select_doctor->get_result();
    if ($result->num_rows > 0) {
        $doctor = $result->fetch_assoc();

        // خواندن برنامه هفتگی فعلی
        $sql_schedule_get = "SELECT * FROM doctor_weekly_schedule WHERE doctor_id = ?";
        $stmt_schedule_get = $conn->prepare($sql_schedule_get);
        $stmt_schedule_get->bind_param("i", $doctor_id);
        $stmt_schedule_get->execute();
        $schedule_result = $stmt_schedule_get->get_result();
        while($row = $schedule_result->fetch_assoc()) {
            $weekly_schedule[$row['day_of_week']] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <title>ویرایش اطلاعات پزشک</title>
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
            <?php if ($doctor): ?>
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-white">ویرایش: <?php echo htmlspecialchars($doctor['full_name']); ?></h1>
                    <a href="doctors.php" class="bg-slate-600 hover:bg-slate-500 px-4 py-2 rounded-md">بازگشت</a>
                </div>

                <form method="POST" action="edit_doctor.php" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $doctor['id']; ?>">
                    <div class="max-w-3xl mx-auto space-y-8">
                        <div class="bg-slate-800 p-8 rounded-xl shadow-lg">
                            <h2 class="text-xl font-bold text-white mb-6 border-b border-slate-700 pb-3">اطلاعات پایه</h2>
                            <input type="hidden" name="existing_image_path" value="<?php echo htmlspecialchars($doctor['profile_image_path']); ?>">
                             <div class="space-y-6">
                                <div>
                                    <label for="full_name">نام کامل:</label>
                                    <input type="text" name="full_name" required value="<?php echo htmlspecialchars($doctor['full_name']); ?>" class="w-full bg-slate-700 p-3 rounded-md">
                                </div>
                                <div>
                                    <label for="specialty">تخصص:</label>
                                    <input type="text" name="specialty" required value="<?php echo htmlspecialchars($doctor['specialty']); ?>" class="w-full bg-slate-700 p-3 rounded-md">
                                </div>
                                 <div>
                                    <label for="address">آدرس:</label>
                                    <textarea name="address" rows="3" class="w-full bg-slate-700 p-3 rounded-md"><?php echo htmlspecialchars($doctor['address']); ?></textarea>
                                </div>
                                <div>
                                    <label>عکس پروفایل فعلی:</label>
                                    <img src="<?php echo htmlspecialchars($doctor['profile_image_path'] ?: 'img/default_avatar.png'); ?>" class="w-24 h-24 rounded-lg object-cover mb-3">
                                    <label for="profile_image">تغییر عکس:</label>
                                    <input type="file" name="profile_image" class="w-full text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-800 p-8 rounded-xl shadow-lg">
                            <h2 class="text-xl font-bold text-white mb-6 border-b border-slate-700 pb-3">ویرایش برنامه هفتگی</h2>
                            <div class="space-y-4">
                                <div>
                                    <label for="slot_duration">مدت زمان هر نوبت (دقیقه):</label>
                                    <input type="number" name="slot_duration" value="<?php echo $weekly_schedule[0]['slot_duration'] ?? 20; ?>" class="w-full bg-slate-700 p-3 rounded-md">
                                </div>
                                <?php
                                $days = ['saturday' => 0, 'sunday' => 1, 'monday' => 2, 'tuesday' => 3, 'wednesday' => 4, 'thursday' => 5, 'friday' => 6];
                                $fa_days = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'];
                                foreach ($days as $eng_day => $day_index):
                                    $is_active = isset($weekly_schedule[$day_index]);
                                    $start_time = $weekly_schedule[$day_index]['start_time'] ?? '';
                                    $end_time = $weekly_schedule[$day_index]['end_time'] ?? '';
                                ?>
                                <div class="p-4 rounded-lg bg-slate-700/50 flex items-center gap-4">
                                    <input id="<?php echo $eng_day; ?>_active" name="schedule[<?php echo $eng_day; ?>][is_active]" type="checkbox" class="h-5 w-5" <?php echo $is_active ? 'checked' : ''; ?>>
                                    <label for="<?php echo $eng_day; ?>_active" class="font-bold w-24"><?php echo $fa_days[$day_index]; ?></label>
                                    <div class="flex-grow grid grid-cols-2 gap-4">
                                        <input type="time" name="schedule[<?php echo $eng_day; ?>][start_time]" value="<?php echo $start_time; ?>" class="w-full bg-slate-600 p-2 rounded-md text-sm">
                                        <input type="time" name="schedule[<?php echo $eng_day; ?>][end_time]" value="<?php echo $end_time; ?>" class="w-full bg-slate-600 p-2 rounded-md text-sm">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="text-left mt-8">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-md">به‌روزرسانی</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <p class="text-red-500">پزشکی با این شناسه یافت نشد.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>