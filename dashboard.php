<?php
// فعال‌سازی نمایش خطا برای اشکال‌زدایی
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// اتصال به دیتابیس شما
require_once "config.php";
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'مدیر';

// --- توابع کمکی ---

/**
 * تابع تبدیل تاریخ میلادی به شمسی
 */
function gregorian_to_jalali($g_y, $g_m, $g_d) {
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
    return [$jy, $jm, $jd];
}

/**
 * تابع تبدیل اعداد انگلیسی به فارسی
 */
function convert_to_persian_digits($string) {
    $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace(range(0, 9), $persian_digits, $string);
}

/**
 * تابع نمایش تاریخ کامل شمسی امروز
 */
function get_today_jalali_date() {
    list($g_y, $g_m, $g_d) = explode('-', date('Y-m-d'));
    list($jy, $jm, $jd) = gregorian_to_jalali((int)$g_y, (int)$g_m, (int)$g_d);
    
    $j_day_of_week = (date('w') + 1) % 7;
    $j_days = ["یکشنبه", "دوشنبه", "سه‌شنبه", "چهارشنبه", "پنج‌شنبه", "جمعه", "شنبه"];
    $j_months = ["", "فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"];
    
    return $j_days[$j_day_of_week] . '، ' . convert_to_persian_digits($jd . ' ' . $j_months[$jm] . ' ' . $jy);
}


// --- کوئری‌های داشبورد ---
$total_employees_count = $conn->query("SELECT COUNT(*) AS count FROM employees")->fetch_assoc()['count'] ?? 0;
$today_appointments_count = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_time) = CURDATE()")->fetch_assoc()['count'] ?? 0;
$completed_today_count = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_time) = CURDATE() AND status = 'completed'")->fetch_assoc()['count'] ?? 0;
$monthly_income_raw = $conn->query("SELECT SUM(price) AS total FROM employee_costs WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;
$result_logs = $conn->query("SELECT ip_address, login_time FROM login_logs ORDER BY login_time DESC LIMIT 5");

// داده‌های نمودار
$chart_labels_fa = [];
$chart_data = [];
$j_months_map = ["", "فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"];
for ($i = 5; $i >= 0; $i--) {
    $date = new DateTime("-$i month");
    $year = (int)$date->format('Y');
    $month = (int)$date->format('m');
    
    list($jy, $jm, $jd) = gregorian_to_jalali($year, $month, 1);
    $chart_labels_fa[] = $j_months_map[$jm];

    $sql_chart = "SELECT COUNT(*) as count FROM employees WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
    $stmt_chart = $conn->prepare($sql_chart);
    $stmt_chart->bind_param("ii", $year, $month);
    $stmt_chart->execute();
    $count = $stmt_chart->get_result()->fetch_assoc()['count'] ?? 0;
    $chart_data[] = $count;
}
$today_jalali_date = get_today_jalali_date();

$conn->close();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدیریتی</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { font-family: 'Vazirmatn', sans-serif; }
        .chart-container { position: relative; height: 350px; width: 100%; }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 font-sans">
    
    <div class="flex flex-col md:flex-row min-h-screen">
        <?php include "inc/nav.php"; ?>

        <main class="flex-1 p-4 sm:p-6">
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-white mb-4 sm:mb-0">پیشخوان</h1>
                <div class="flex items-center space-x-4 space-x-reverse">
                    <span class="text-sm">خوش آمدید، <?php echo $username; ?></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg text-white text-sm transition-colors">خروج</a>
                </div>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-slate-800 p-6 rounded-xl shadow-lg flex items-center space-x-4 space-x-reverse">
                    <div class="bg-purple-500/20 p-3 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg></div>
                    <div>
                        <p class="text-sm text-slate-400">تعداد کل پرونده‌ها</p>
                        <p id="total-users-count" class="text-3xl font-bold text-white" data-target="<?php echo $total_employees_count; ?>">0</p>
                    </div>
                </div>

                <div class="bg-slate-800 p-6 rounded-xl shadow-lg">
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <div class="bg-blue-500/20 p-3 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>
                        <div>
                            <p class="text-sm text-slate-400">تعداد نوبت‌های امروز</p>
                            <p id="today-appointments-count" class="text-3xl font-bold text-white" data-target="<?php echo $today_appointments_count; ?>">0</p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 text-left mt-2"><?php echo $today_jalali_date; ?></p>
                </div>
                
                <div class="bg-slate-800 p-6 rounded-xl shadow-lg flex items-center space-x-4 space-x-reverse">
                    <div class="bg-green-500/20 p-3 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                    <div>
                        <p class="text-sm text-slate-400">تعداد ویزیت شده امروز</p>
                        <p id="completed-today-count" class="text-3xl font-bold text-white" data-target="<?php echo $completed_today_count; ?>">0</p>
                    </div>
                </div>

                <div class="bg-slate-800 p-6 rounded-xl shadow-lg flex items-center space-x-4 space-x-reverse">
                     <div class="bg-yellow-500/20 p-3 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg></div>
                    <div>
                        <p class="text-sm text-slate-400">درآمد این ماه (تومان)</p>
                        <p id="monthly-income" class="text-3xl font-bold text-white" data-target="<?php echo $monthly_income_raw; ?>">0</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-slate-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-lg font-semibold text-white mb-4">گزارش مراجعین (۶ ماه اخیر)</h3>
                    <div class="chart-container">
                        <canvas id="patientsChart"></canvas>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-slate-800 p-6 rounded-xl shadow-lg">
                        <h3 class="text-lg font-semibold text-white mb-4">آخرین ورودها به سیستم</h3>
                        <ul class="space-y-3">
                            <?php if ($result_logs && $result_logs->num_rows > 0): ?>
                                <?php while ($log = $result_logs->fetch_assoc()): ?>
                                    <li class="flex justify-between items-center text-sm p-3 bg-slate-700/50 rounded-lg">
                                        <span><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                        <span class="text-slate-400 text-xs"><?php echo (new DateTime($log['login_time']))->format('Y-m-d H:i'); ?></span>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li class="text-slate-400">لاگی یافت نشد.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div class="bg-slate-800 p-6 rounded-xl shadow-lg">
                        <h3 class="text-lg font-semibold text-white mb-4">دسترسی سریع</h3>
                        <div class="space-y-3">
                            <a href="/appointments" class="flex items-center space-x-3 space-x-reverse p-3 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span class="text-sm font-medium">ثبت نوبت جدید</span>
                            </a>
                            <a href="/employees" class="flex items-center space-x-3 space-x-reverse p-3 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors">
                               <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                                <span class="text-sm font-medium">افزودن پرونده بیمار</span>
                            </a>
                            <a href="/reports" class="flex items-center space-x-3 space-x-reverse p-3 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span class="text-sm font-medium">گزارشات مالی</span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Animate counter script remains the same...
    function animateCounter(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;
        const targetValue = parseFloat(element.dataset.target) || 0;
        if (targetValue === 0) {
            // نمایش عدد صفر فارسی برای مقادیر صفر
            const persianZero = new Intl.NumberFormat('fa-IR').format(0);
            element.textContent = persianZero;
            return;
        }

        const duration = 1500;
        const frameRate = 1000 / 60;
        const totalFrames = Math.round(duration / frameRate);
        const increment = targetValue / totalFrames;
        let current = 0;

        const counter = setInterval(() => {
            current += increment;
            if (current >= targetValue) {
                clearInterval(counter);
                current = targetValue;
            }
            // استفاده از toLocaleString('fa-IR') برای فرمت‌دهی صحیح اعداد
            element.textContent = Math.ceil(current).toLocaleString('fa-IR');
        }, frameRate);
    }
    
    animateCounter('total-users-count');
    animateCounter('today-appointments-count');
    animateCounter('completed-today-count');
    animateCounter('monthly-income');

    // Chart script remains the same...
    const chartData = <?php echo json_encode($chart_data); ?>;
    if (document.getElementById('patientsChart') && chartData.length > 0) {
        const ctx = document.getElementById('patientsChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels_fa); ?>,
                datasets: [{
                    label: 'تعداد مراجعین جدید',
                    data: chartData,
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 7,
                    pointHoverBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { color: 'rgb(156, 163, 175)', stepSize: 1, callback: function(value) { return new Intl.NumberFormat('fa-IR').format(value); } }, grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                    x: { ticks: { color: 'rgb(156, 163, 175)' }, grid: { display: false } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgb(15, 23, 42)',
                        titleFont: { family: 'Vazirmatn' },
                        bodyFont: { family: 'Vazirmatn' },
                        padding: 10,
                        cornerRadius: 5,
                        displayColors: false,
                        rtl: true,
                        callbacks: {
                            label: function(context) {
                                return (context.dataset.label || '') + ': ' + new Intl.NumberFormat('fa-IR').format(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

</body>
</html>