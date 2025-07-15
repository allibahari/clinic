<?php
// فعال‌سازی نمایش خطا برای اشکال‌زدایی و شروع سشن
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✨ اتصال به دیتابیس با مسیر کامل و دقیق برای جلوگیری از خطا
require_once __DIR__ . "/config.php";

// --- مدیریت فیلتر تاریخ ---
// اگر تاریخی مشخص نشده بود، بازه پیش‌فرض ماه جاری است
$start_date = $_POST['start_date'] ?? date('Y-m-01');
$end_date = $_POST['end_date'] ?? date('Y-m-t');

// برای نمایش در inputها، تاریخ‌ها را فرمت می‌کنیم
$start_date_formatted = date('Y-m-d', strtotime($start_date));
$end_date_formatted = date('Y-m-d', strtotime($end_date));

// --- کوئری‌های گزارش مالی ---
$total_income = 0;
$transaction_count = 0;
$financial_details = [];

// کوئری مجموع درآمد
$sql_income = "SELECT SUM(price) as total FROM employee_costs WHERE DATE(created_at) BETWEEN ? AND ?";
$stmt_income = $conn->prepare($sql_income);
$stmt_income->bind_param("ss", $start_date_formatted, $end_date_formatted);
$stmt_income->execute();
$result_income = $stmt_income->get_result()->fetch_assoc();
$total_income = $result_income['total'] ?? 0;

// کوئری جزئیات تراکنش‌ها
$sql_financial_details = "
    SELECT ec.price, ec.created_at, e.full_name 
    FROM employee_costs ec
    JOIN employees e ON ec.employee_id = e.id
    WHERE DATE(ec.created_at) BETWEEN ? AND ?
    ORDER BY ec.created_at DESC";
$stmt_financial_details = $conn->prepare($sql_financial_details);
$stmt_financial_details->bind_param("ss", $start_date_formatted, $end_date_formatted);
$stmt_financial_details->execute();
$financial_details = $stmt_financial_details->get_result()->fetch_all(MYSQLI_ASSOC);
$transaction_count = count($financial_details);


// --- کوئری‌های گزارش نوبت‌ها ---
$total_appointments = 0;
$completed_appointments = 0;
$pending_appointments = 0;
$appointment_details = [];

// کوئری آمار کلی نوبت‌ها
$sql_app_stats = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM appointments
    WHERE DATE(appointment_time) BETWEEN ? AND ?";
$stmt_app_stats = $conn->prepare($sql_app_stats);
$stmt_app_stats->bind_param("ss", $start_date_formatted, $end_date_formatted);
$stmt_app_stats->execute();
$result_app_stats = $stmt_app_stats->get_result()->fetch_assoc();
$total_appointments = $result_app_stats['total'] ?? 0;
$completed_appointments = $result_app_stats['completed'] ?? 0;
$pending_appointments = $result_app_stats['pending'] ?? 0;

// کوئری جزئیات نوبت‌ها
$sql_app_details = "
    SELECT a.appointment_time, a.status, e.full_name as patient_name, d.full_name as doctor_name
    FROM appointments a
    JOIN employees e ON a.employee_id = e.id
    JOIN doctors d ON a.doctor_id = d.id
    WHERE DATE(a.appointment_time) BETWEEN ? AND ?
    ORDER BY a.appointment_time DESC";
$stmt_app_details = $conn->prepare($sql_app_details);
$stmt_app_details->bind_param("ss", $start_date_formatted, $end_date_formatted);
$stmt_app_details->execute();
$appointment_details = $stmt_app_details->get_result()->fetch_all(MYSQLI_ASSOC);


$conn->close();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گزارشات</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { font-family: 'Vazirmatn', sans-serif; }
        @media print {
            body * {
                visibility: hidden;
            }
            .printable-area, .printable-area * {
                visibility: visible;
            }
            .printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-300">

    <div class="flex flex-col md:flex-row min-h-screen">
        <?php 
        // ✨ فراخوانی سایدبار با مسیر کامل و دقیق
        include __DIR__ . "/inc/nav.php"; 
        ?>

        <main class="flex-1 p-4 sm:p-6">
            <header class="flex justify-between items-center mb-6 no-print">
                <h1 class="text-2xl sm:text-3xl font-bold text-white">گزارشات</h1>
            </header>

            <div class="bg-slate-800 p-4 rounded-xl mb-8 no-print">
                <form method="post" action="reports.php" class="flex flex-col sm:flex-row items-center gap-4">
                    <div class="w-full sm:w-auto">
                        <label for="start_date" class="block text-sm font-medium mb-1">از تاریخ:</label>
                        <input type="date" name="start_date" id="start_date" value="<?php echo $start_date_formatted; ?>" class="w-full bg-slate-700 text-white rounded-lg p-2 border-slate-600 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="w-full sm:w-auto">
                        <label for="end_date" class="block text-sm font-medium mb-1">تا تاریخ:</label>
                        <input type="date" name="end_date" id="end_date" value="<?php echo $end_date_formatted; ?>" class="w-full bg-slate-700 text-white rounded-lg p-2 border-slate-600 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="w-full sm:w-auto mt-4 sm:mt-0">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors mt-0 sm:mt-6">اعمال فیلتر</button>
                    </div>
                </form>
            </div>

            <div id="financial-report" class="printable-area bg-slate-800 p-6 rounded-xl mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">گزارش مالی</h2>
                    <button onclick="printReport('financial-report')" class="no-print bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">چاپ</button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-slate-700 p-4 rounded-lg">
                        <p class="text-sm text-slate-400">مجموع درآمد (تومان)</p>
                        <p class="text-2xl font-bold text-green-400"><?php echo number_format($total_income); ?></p>
                    </div>
                    <div class="bg-slate-700 p-4 rounded-lg">
                        <p class="text-sm text-slate-400">تعداد تراکنش‌ها</p>
                        <p class="text-2xl font-bold text-sky-400"><?php echo number_format($transaction_count); ?></p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-center">
                        <thead class="bg-slate-700/50">
                            <tr>
                                <th class="p-3">نام بیمار</th>
                                <th class="p-3">مبلغ (تومان)</th>
                                <th class="p-3">تاریخ تراکنش</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($financial_details)): ?>
                                <?php foreach ($financial_details as $item): ?>
                                    <tr class="border-b border-slate-700">
                                        <td class="p-3"><?php echo htmlspecialchars($item['full_name']); ?></td>
                                        <td class="p-3 text-green-400"><?php echo number_format($item['price']); ?></td>
                                        <td class="p-3 text-slate-400"><?php echo (new DateTime($item['created_at']))->format('Y-m-d H:i'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="p-4 text-slate-500">داده‌ای برای نمایش در این بازه زمانی یافت نشد.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="appointment-report" class="printable-area bg-slate-800 p-6 rounded-xl">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">گزارش نوبت‌ها</h2>
                    <button onclick="printReport('appointment-report')" class="no-print bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">چاپ</button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-slate-700 p-4 rounded-lg">
                        <p class="text-sm text-slate-400">کل نوبت‌ها</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($total_appointments); ?></p>
                    </div>
                    <div class="bg-slate-700 p-4 rounded-lg">
                        <p class="text-sm text-slate-400">نوبت‌های انجام شده</p>
                        <p class="text-2xl font-bold text-green-400"><?php echo number_format($completed_appointments); ?></p>
                    </div>
                    <div class="bg-slate-700 p-4 rounded-lg">
                        <p class="text-sm text-slate-400">نوبت‌های در انتظار</p>
                        <p class="text-2xl font-bold text-yellow-400"><?php echo number_format($pending_appointments); ?></p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-center">
                        <thead class="bg-slate-700/50">
                            <tr>
                                <th class="p-3">بیمار</th>
                                <th class="p-3">پزشک</th>
                                <th class="p-3">تاریخ نوبت</th>
                                <th class="p-3">وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($appointment_details)): ?>
                                <?php foreach ($appointment_details as $item): ?>
                                    <tr class="border-b border-slate-700">
                                        <td class="p-3"><?php echo htmlspecialchars($item['patient_name']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($item['doctor_name']); ?></td>
                                        <td class="p-3 text-slate-400"><?php echo (new DateTime($item['appointment_time']))->format('Y-m-d H:i'); ?></td>
                                        <td class="p-3">
                                            <?php
                                                $status_text = '';
                                                $status_color = '';
                                                switch ($item['status']) {
                                                    case 'completed': $status_text = 'انجام شده'; $status_color = 'bg-green-500/20 text-green-400'; break;
                                                    case 'pending': $status_text = 'در انتظار'; $status_color = 'bg-yellow-500/20 text-yellow-400'; break;
                                                    case 'canceled': $status_text = 'لغو شده'; $status_color = 'bg-red-500/20 text-red-400'; break;
                                                }
                                                echo "<span class='px-2 py-1 rounded-full text-xs {$status_color}'>{$status_text}</span>";
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="p-4 text-slate-500">داده‌ای برای نمایش در این بازه زمانی یافت نشد.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function printReport(elementId) {
            const reportArea = document.getElementById(elementId);
            const originalContents = document.body.innerHTML;
            // کلون کردن بخش پرینت برای حفظ استایل‌ها
            const printContents = reportArea.cloneNode(true);
            const allElements = document.body.getElementsByTagName("*");

            // پنهان کردن همه چیز
            for(let i = 0; i < allElements.length; i++){
                allElements[i].style.visibility = 'hidden';
            }
            
            // نمایش بخش قابل پرینت
            reportArea.style.visibility = 'visible';
            document.body.appendChild(printContents);
            
            // پرینت
            window.print();
            
            // برگرداندن به حالت اولیه و رفرش
            window.location.reload();
        }
    </script>
</body>
</html>