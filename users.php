<?php
// فایل‌های اصلی و نمایش خطا
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "config.php";

/**
 * ✨ تابع تبدیل تاریخ جایگزین شد تا به افزونه intl نیازی نباشد
 * @param string|null $gregorian_date تاریخ میلادی مانند '2025-07-13 10:20:30'
 * @param string $format فرمت خروجی، مثلا 'Y/m/d'
 * @return string تاریخ شمسی فرمت‌شده
 */
function gregorian_to_jalali($gregorian_date, $format = 'Y/m/d') {
    if (empty($gregorian_date)) {
        return '---';
    }
    $timestamp = strtotime($gregorian_date);
    if ($timestamp === false) {
        return 'تاریخ نامعتبر';
    }
    
    $g_y = date('Y', $timestamp);
    $g_m = date('m', $timestamp);
    $g_d = date('d', $timestamp);

    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;

    $g_day_no = 365 * $gy + floor(($gy + 3) / 4) - floor(($gy + 99) / 100) + floor(($gy + 399) / 400);

    for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += $g_days_in_month[$i];
    }
    if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))) {
        $g_day_no++;
    }
    $g_day_no += $gd;
    $j_day_no = $g_day_no - 79;
    $j_np = floor($j_day_no / 12053);
    $j_day_no %= 12053;
    $jy = 979 + 33 * $j_np + 4 * floor($j_day_no / 1461);
    $j_day_no %= 1461;

    if ($j_day_no >= 366) {
        $jy += floor(($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }

    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
        $j_day_no -= $j_days_in_month[$i];
    }
    $jm = $i + 1;
    $jd = $j_day_no + 1;
    
    $date_string = str_replace(
        ['Y', 'm', 'd'],
        [$jy, str_pad($jm, 2, '0', STR_PAD_LEFT), str_pad($jd, 2, '0', STR_PAD_LEFT)],
        $format
    );
    
    // تبدیل اعداد به فارسی
    $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english_digits = range(0, 9);
    return str_replace($english_digits, $persian_digits, $date_string);
}


// ۱. دریافت پارامترهای جستجو
$search_first_name = $_GET['first_name'] ?? '';
$search_last_name = $_GET['last_name'] ?? '';
$search_national_code = $_GET['national_code'] ?? '';

// ۲. ساخت کوئری امن و اصلاح شده با JOIN برای دریافت لیست خدمات
$sql = "SELECT e.*, GROUP_CONCAT(ec.item_name SEPARATOR '، ') as service_list
        FROM employees e
        LEFT JOIN employee_costs ec ON e.id = ec.employee_id
        WHERE 1=1";
$params = [];
$types = '';

if (!empty($search_first_name)) {
    $sql .= " AND e.first_name LIKE ?";
    $params[] = "%" . $search_first_name . "%";
    $types .= 's';
}
if (!empty($search_last_name)) {
    $sql .= " AND e.last_name LIKE ?";
    $params[] = "%" . $search_last_name . "%";
    $types .= 's';
}
if (!empty($search_national_code)) {
    $sql .= " AND e.national_code LIKE ?";
    $params[] = "%" . $search_national_code . "%";
    $types .= 's';
}

$sql .= " GROUP BY e.id ORDER BY e.id DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("خطا در آماده‌سازی کوئری: " . $conn->error);
}


// ۳. تابع رندر کردن ردیف‌های جدول
function renderUserRows($users) {
    $html = '';
    if (!empty($users)) {
        foreach ($users as $user) {
            $services = !empty($user['service_list']) ? htmlspecialchars($user['service_list']) : '<span class="text-slate-500">---</span>';
            $html .= '<tr class="border-b border-slate-700 hover:bg-slate-700/50 transition-colors duration-200">
                        <td class="p-4">' . htmlspecialchars($user['id']) . '</td>
                        <td class="p-4"><img src="' . htmlspecialchars($user['photo_path1']) . '" alt="تصویر کاربر" class="w-14 h-14 object-cover rounded-md shadow-md"></td>
                        <td class="p-4 font-semibold">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>
                        <td class="p-4">' . htmlspecialchars($user['national_code']) . '</td>
                        <td class="p-4">' . htmlspecialchars($user['mobile']) . '</td>
                        <td class="p-4 text-sky-400">' . $services . '</td>
                        <td class="p-4 text-slate-400">' . gregorian_to_jalali($user['created_at'], 'Y/m/d') . '</td>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <a href="/clinic/employee_details?id=' . htmlspecialchars($user['id']) . '" class="flex items-center gap-1.5 bg-slate-600 hover:bg-slate-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md transition-colors" title="مشاهده جزئیات"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.022 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg><span>مشاهده</span></a>
                                <a href="/clinic/generate_invoice?id=' . htmlspecialchars($user['id']) . '" target="_blank" class="flex items-center gap-1.5 bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md transition-colors" title="صدور فاکتور"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" /></svg><span>فاکتور</span></a>
                                <a href="/clinic/edit_employee?id=' . htmlspecialchars($user['id']) . '" class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md transition-colors" title="ویرایش"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg><span>ویرایش</span></a>
                                <a href="/clinic/delete-user?id=' . htmlspecialchars($user['id']) . '" class="flex items-center gap-1.5 bg-red-600 hover:bg-red-500 text-white text-xs font-semibold px-3 py-1.5 rounded-md transition-colors" title="حذف" onclick="return confirm(\'آیا برای حذف این کاربر مطمئن هستید؟\');"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg><span>حذف</span></a>
                            </div>
                        </td>
                    </tr>';
        }
    } else {
        $html .= '<tr><td colspan="8" class="text-center p-8 text-slate-400">هیچ کاربری با این مشخصات یافت نشد.</td></tr>';
    }
    return $html;
}

// ۴. مدیریت درخواست‌های AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    echo renderUserRows($users);
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیست کاربران</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 font-sans">

    <div class="flex">
        <?php require_once "inc/nav.php"; ?>

        <main class="flex-1 p-6">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-white">لیست کاربران</h1>
                <a href="employees.php" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-md text-white text-sm">ایجاد کاربر جدید</a>
            </div>

            <div class="bg-slate-800 p-6 rounded-xl shadow-lg mb-8">
                <h3 class="text-lg font-semibold text-white mb-4">جستجوی پیشرفته</h3>
                <form id="search-form" method="GET" action="" autocomplete="off">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="text" name="first_name" class="bg-slate-700 p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="نام" value="<?php echo htmlspecialchars($search_first_name); ?>">
                        <input type="text" name="last_name" class="bg-slate-700 p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="نام خانوادگی" value="<?php echo htmlspecialchars($search_last_name); ?>">
                        <input type="text" name="national_code" class="bg-slate-700 p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="کد ملی" value="<?php echo htmlspecialchars($search_national_code); ?>">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-md">جستجو</button>
                    </div>
                </form>
            </div>

            <div class="bg-slate-800 rounded-xl shadow-lg overflow-hidden">
                <table class="min-w-full text-white text-sm">
                    <thead class="bg-slate-700/50">
                        <tr>
                            <th class="p-4 text-right font-semibold">شناسه</th>
                            <th class="p-4 text-right font-semibold">تصویر</th>
                            <th class="p-4 text-right font-semibold">نام کامل</th>
                            <th class="p-4 text-right font-semibold">کدملی</th>
                            <th class="p-4 text-right font-semibold">موبایل</th>
                            <th class="p-4 text-right font-semibold">خدمات دریافتی</th>
                            <th class="p-4 text-right font-semibold">تاریخ ثبت</th>
                            <th class="p-4 text-right font-semibold">عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body">
                        <?php echo renderUserRows($users); ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

<script>
$(document).ready(function() {
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

    // --- FUNCTION FETCHUSERS CORRECTED ---
    function fetchUsers() {
        const form = $('#search-form');
        
        // 1. Read the form data FIRST
        const formData = form.serialize() + '&ajax=true';

        // 2. NOW, disable the inputs for better UX
        form.find(':input').prop('disabled', true);
        
        $.ajax({
            url: window.location.pathname, 
            type: 'GET',
            data: formData, // 3. Use the stored form data
            success: function(response) {
                $('#user-table-body').html(response);
            },
            error: function() {
                $('#user-table-body').html('<tr><td colspan="8" class="text-center p-8 text-red-500">خطا در بارگذاری اطلاعات.</td></tr>');
            },
            complete: function() {
                 form.find(':input').prop('disabled', false);
            }
        });
    }

    const debouncedFetchUsers = debounce(fetchUsers, 400);

    $('#search-form input').on('keyup', debouncedFetchUsers);

    $('#search-form').on('submit', function(e) {
        e.preventDefault(); 
        fetchUsers();
    });
});
</script>
</body>
</html>
<?php
$conn->close();
?>