<?php
// ... (کدهای اولیه PHP شما تا قبل از اتصال به دیتابیس)

// فعال‌سازی نمایش خطا
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ... (تابع convertNumbersToEnglish)

// اتصال به دیتابیس
require_once "config.php";

// ✨ بخش جدید: دریافت اطلاعات بیمار از نوبت
$patient_first_name = '';
$patient_last_name = '';
$appointment_id = null;

if (isset($_GET['appointment_id'])) {
    $appointment_id = (int)$_GET['appointment_id'];
    
    // واکشی نام بیمار از جدول نوبت‌ها
    $stmt_app = $conn->prepare("SELECT patient_name FROM appointments WHERE id = ?");
    $stmt_app->bind_param("i", $appointment_id);
    $stmt_app->execute();
    $result_app = $stmt_app->get_result();
    if ($result_app->num_rows > 0) {
        $appointment_data = $result_app->fetch_assoc();
        $patient_full_name = explode(' ', $appointment_data['patient_name'], 2);
        $patient_first_name = $patient_full_name[0] ?? '';
        $patient_last_name = $patient_full_name[1] ?? '';
    }
    $stmt_app->close();
}

// --- دریافت لیست خدمات پیش‌فرض از دیتابیس ---
// ... (ادامه کدهای PHP شما بدون تغییر)

// بررسی ارسال فرم
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ✨ بخش جدید: اگر فرم از طرف یک نوبت آمده، وضعیت آن را به 'completed' آپدیت کن
    $posted_appointment_id = $_POST['appointment_id'] ?? null;
    if ($posted_appointment_id) {
        $update_sql = "UPDATE appointments SET status = 'completed' WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $posted_appointment_id);
        $update_stmt->execute();
        $update_stmt->close();
    }

    // ... (ادامه کدهای پردازش فرم شما بدون تغییر)
}
?>
<!DOCTYPE html>
<body class="bg-slate-900 text-slate-300 font-sans">
    <div class="flex">
        <?php require_once "inc/nav.php"; ?>

        <main class="flex-1 p-6">
            <h1 class="text-3xl font-bold text-white mb-8">ایجاد پرونده برای زیباجو</h1>
            <?php if ($showDownloadButton): ?>
                    <?php else: ?>
                    <form id="main-form" method="POST" enctype="multipart/form-data" class="bg-slate-800 p-8 rounded-xl shadow-lg space-y-6">
                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment_id); ?>">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                           <div>
                                <label for="first_name" class="block text-sm font-medium mb-1">نام زیباجو:</label>
                                <input type="text" id="first_name" name="first_name" required class="w-full bg-slate-700 p-2 rounded-md" value="<?php echo htmlspecialchars($patient_first_name); ?>">
                           </div>
                           <div>
                                <label for="last_name" class="block text-sm font-medium mb-1">نام خانوادگی:</label>
                                <input type="text" id="last_name" name="last_name" required class="w-full bg-slate-700 p-2 rounded-md" value="<?php echo htmlspecialchars($patient_last_name); ?>">
                           </div>
                           </div>
                        
                        </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
    </body>
</html>