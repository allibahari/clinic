<?php
session_start();
require_once "config.php";

// دریافت لیست پزشکان برای نمایش در فرم
$doctors_result = $conn->query("SELECT id, full_name FROM doctors ORDER BY full_name");
$doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);

// پردازش فرم ثبت برنامه جدید
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_id = (int)$_POST['doctor_id'];
    $date = $_POST['date'];
    $start_time_str = $_POST['start_time'];
    $end_time_str = $_POST['end_time'];
    $duration = (int)$_POST['duration'];

    $start_datetime = "$date $start_time_str";
    $end_datetime = "$date $end_time_str";

    // ثبت قانون جدید در دیتابیس
    $sql = "INSERT INTO doctor_availability (doctor_id, start_time, end_time, slot_duration) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $doctor_id, $start_datetime, $end_datetime, $duration);
    $stmt->execute();
    
    $_SESSION['flash_message'] = "برنامه زمانی با موفقیت ثبت شد.";
    header("Location: schedule_manager.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مدیریت برنامه زمانی</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Vazirmatn', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-300">
    <div class="flex">
        <?php require_once "inc/nav.php"; // فرض می‌شود منوی پنل شما در این فایل است ?>
        <main class="flex-1 p-6">
            <h1 class="text-3xl font-bold text-white mb-8">تعریف برنامه پزشکان</h1>
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="p-4 mb-4 text-sm rounded-lg bg-green-800 text-green-300">
                    <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
                </div>
            <?php endif; ?>
            <div class="max-w-xl mx-auto bg-slate-800 p-8 rounded-xl shadow-lg">
                <form method="POST">
                    <div class="space-y-4">
                        <div>
                            <label for="doctor_id" class="block text-sm font-medium mb-1">پزشک:</label>
                            <select name="doctor_id" required class="w-full bg-slate-700 p-3 rounded-md">
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="date" class="block text-sm font-medium mb-1">تاریخ:</label>
                            <input type="date" name="date" required class="w-full bg-slate-700 p-3 rounded-md">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="start_time">از ساعت:</label>
                                <input type="time" name="start_time" required class="w-full bg-slate-700 p-3 rounded-md">
                            </div>
                            <div>
                                <label for="end_time">تا ساعت:</label>
                                <input type="time" name="end_time" required class="w-full bg-slate-700 p-3 rounded-md">
                            </div>
                        </div>
                        <div>
                            <label for="duration">فاصله هر نوبت (دقیقه):</label>
                            <input type="number" name="duration" value="20" required class="w-full bg-slate-700 p-3 rounded-md">
                        </div>
                    </div>
                    <div class="mt-8 text-left">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-md">ذخیره برنامه</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>