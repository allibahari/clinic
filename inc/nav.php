<?php
// همیشه سشن را در ابتدای فایل شروع کنید
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// خواندن اطلاعات کاربر از سشن
$current_user = $_SESSION['current_user'] ?? null;

// متغیر برای مسیر پایه
$base_path = '/clinic';

// تشخیص مسیر فعلی برای هایلایت کردن منو
$current_path = $_SERVER['REQUEST_URI'];

// --- تاریخ و ساعت داینامیک و شمسی به وقت تهران ---
$jalali_date_time = '';
// برای این بخش نیاز به فعال بودن افزونه intl در PHP است
if (class_exists('IntlDateFormatter')) {
    $formatter = new IntlDateFormatter(
        'fa_IR@calendar=persian',
        IntlDateFormatter::FULL,
        IntlDateFormatter::SHORT,
        'Asia/Tehran', // منطقه زمانی تهران
        IntlDateFormatter::TRADITIONAL,
        'EEEE، d MMMM yyyy | HH:mm' // فرمت نمایش
    );
    // time() تاریخ و ساعت لحظه‌ای سرور را دریافت می‌کند
    $jalali_date_time = $formatter->format(time());
} else {
    // اگر افزونه intl فعال نبود، تاریخ میلادی نمایش داده می‌شود
    date_default_timezone_set('Asia/Tehran');
    $jalali_date_time = date('Y-m-d H:i');
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کلینیک زیبایی</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }
        #overlay {
            transition: opacity 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-slate-100">

    <div class="relative min-h-screen md:flex">

        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 opacity-0 pointer-events-none md:hidden"></div>

        <nav id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-slate-800 shadow-lg p-4 z-40
                                flex flex-col
                                transform -translate-x-full transition-transform duration-300 ease-in-out
                                md:relative md:translate-x-0">
            
            <div>
                <div class="flex justify-between items-center mb-10">
                    <a href="<?php echo htmlspecialchars($base_path); ?>/dashboard">
                        <h2 class="text-2xl font-bold text-white">کلینیک زیبایی</h2>
                    </a>
                    <button id="close-menu-btn" class="text-slate-400 hover:text-white md:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <ul class="space-y-2">
                    <li>
                        <a href="<?php echo htmlspecialchars($base_path); ?>/dashboard" class="flex items-center gap-x-3 px-3 py-2 rounded-md text-slate-300 hover:bg-slate-700 hover:text-white transition-colors <?php echo ($current_path === $base_path . '/dashboard') ? 'bg-slate-700' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>
                            <span>پیشخوان</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo htmlspecialchars($base_path); ?>/users" class="flex items-center gap-x-3 px-3 py-2 rounded-md text-slate-300 hover:bg-slate-700 hover:text-white transition-colors <?php echo ($current_path === $base_path . '/users') ? 'bg-slate-700' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm-3 4a5 5 0 00-5 5v1a1 1 0 001 1h8a1 1 0 001-1v-1a5 5 0 00-5-5zm6.5-1.5a.5.5 0 000-1H18a.5.5 0 000 1h-2.5zM15 13.5a.5.5 0 000-1H18a.5.5 0 000 1h-3z" /></svg>
                            <span>بیماران</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo htmlspecialchars($base_path); ?>/employees" class="flex items-center gap-x-3 px-3 py-2 rounded-md text-slate-300 hover:bg-slate-700 hover:text-white transition-colors <?php echo ($current_path === $base_path . '/employees') ? 'bg-slate-700' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                            <span>افزودن بیمار</span>
                        </a>
                    </li>
                     <li>
                        <a href="<?php echo htmlspecialchars($base_path); ?>/appointments" class="flex items-center gap-x-3 px-3 py-2 rounded-md text-slate-300 hover:bg-slate-700 hover:text-white transition-colors <?php echo ($current_path === $base_path . '/appointments') ? 'bg-slate-700' : ''; ?>">
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                           <span>مدیریت نوبت‌ها</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo htmlspecialchars($base_path); ?>/doctors" class="flex items-center gap-x-3 px-3 py-2 rounded-md text-slate-300 hover:bg-slate-700 hover:text-white transition-colors <?php echo ($current_path === $base_path . '/doctors') ? 'bg-slate-700' : ''; ?>">
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm-3 4a5 5 0 00-5 5v1a1 1 0 001 1h8a1 1 0 001-1v-1a5 5 0 00-5-5zm8.293-4.293a1 1 0 011.414 0l2 2a1 1 0 01-1.414 1.414L14 8.414l-1.293 1.293a1 1 0 01-1.414-1.414l2-2z" /></svg>
                           <span>پزشکان</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo htmlspecialchars($base_path); ?>/settings" class="flex items-center gap-x-3 px-3 py-2 rounded-md text-slate-300 hover:bg-slate-700 hover:text-white transition-colors <?php echo ($current_path === $base_path . '/settings') ? 'bg-slate-700' : ''; ?>">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0L8.21 5.15a1.5 1.5 0 01-1.42 1.03l-2.02-.34a1.5 1.5 0 00-1.64 1.64l.34 2.02a1.5 1.5 0 01-1.03 1.42l-1.98-.3a1.5 1.5 0 000 2.98l1.98.3a1.5 1.5 0 011.03 1.42l-.34 2.02a1.5 1.5 0 001.64 1.64l2.02-.34a1.5 1.5 0 011.42 1.03l.3 1.98a1.5 1.5 0 002.98 0l.3-1.98a1.5 1.5 0 011.42-1.03l2.02.34a1.5 1.5 0 001.64-1.64l-.34-2.02a1.5 1.5 0 011.03-1.42l1.98-.3a1.5 1.5 0 000-2.98l-1.98-.3a1.5 1.5 0 01-1.03-1.42l.34-2.02a1.5 1.5 0 00-1.64-1.64l-2.02.34a1.5 1.5 0 01-1.42-1.03l-.3-1.98zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
                            <span>تنظیمات</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="mt-auto border-t border-slate-700 pt-4">
                 <div class="flex items-center gap-x-3">
                    <img src="https://i.pravatar.cc/40?u=<?php echo htmlspecialchars($current_user['username'] ?? 'guest'); ?>" alt="Profile Picture" class="w-10 h-10 rounded-full">
                    <div class="flex-1">
                        <?php if (isset($current_user)): ?>
                            <h4 class="text-sm font-semibold text-white"><?php echo htmlspecialchars($current_user['full_name']); ?></h4>
                            <p class="text-xs text-slate-400"><?php echo htmlspecialchars($current_user['username']); ?></p>
                        <?php else: ?>
                            <h4 class="text-sm font-semibold text-white">کاربر مهمان</h4>
                            <p class="text-xs text-slate-400">وارد نشده</p>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo htmlspecialchars($base_path); ?>/logout" title="خروج" class="text-slate-400 hover:text-red-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                 </div>
            </div>

        </nav>

        <main class="flex-1 p-6">
            <header class="flex justify-between items-center">
                <button id="open-menu-btn" class="text-slate-600 md:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="text-sm text-slate-500 font-medium">
                    <?php echo $jalali_date_time; ?>
                </div>
            </header>
            
            <div class="mt-8">
                </div>
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const openMenuBtn = document.getElementById('open-menu-btn');
        const closeMenuBtn = document.getElementById('close-menu-btn');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('opacity-0', 'pointer-events-none');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0', 'pointer-events-none');
        }

        openMenuBtn.addEventListener('click', openSidebar);
        closeMenuBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);
    </script>
</body>
</html>