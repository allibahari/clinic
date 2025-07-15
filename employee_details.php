<?php
// فعال‌سازی نمایش خطا و اتصال به دیتابیس
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "config.php";

/**
 * تابع تبدیل تاریخ میلادی به شمسی (بدون نیاز به افزونه intl)
 * @param string|null $gregorian_date تاریخ میلادی
 * @return string تاریخ شمسی فرمت‌شده
 */
function gregorian_to_jalali($gregorian_date) {
    if (empty($gregorian_date) || $gregorian_date === '0000-00-00') {
        return '---';
    }
    $timestamp = strtotime($gregorian_date);
    $g_y = date('Y', $timestamp); $g_m = date('m', $timestamp); $g_d = date('d', $timestamp);
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
    $date_string = "$jy/" . str_pad($jm, 2, '0', STR_PAD_LEFT) . "/" . str_pad($jd, 2, '0', STR_PAD_LEFT);
    $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace(range(0, 9), $persian_digits, $date_string);
}


$user = null;
if (isset($_GET['id'])) {
    $employee_id = (int)$_GET['id'];

    // کوئری اصلاح شد تا لیست خدمات را از جدول employee_costs دریافت کند
    $sql = "SELECT e.*, GROUP_CONCAT(ec.item_name SEPARATOR '، ') AS service_list
            FROM employees e
            LEFT JOIN employee_costs ec ON e.id = ec.employee_id
            WHERE e.id = ?
            GROUP BY e.id";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات کاربر</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 font-sans">

    <div class="flex">
        <?php require_once "inc/nav.php"; ?>

        <main class="flex-1 p-6">
            <?php if ($user): ?>
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-white">جزئیات: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                    <a href="users.php" class="bg-gray-500 hover:bg-gray-600 px-4 py-2 rounded-md text-white text-sm">بازگشت به لیست</a>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-1">
                        <div class="bg-slate-800 p-6 rounded-xl shadow-lg">
                            <h3 class="text-lg font-semibold text-white mb-4">گالری تصاویر</h3>
                            <div class="main-image-container mb-4">
                                <a id="mainImageLink" href="<?php echo htmlspecialchars($user['photo_path1'] ?? ''); ?>" data-lightbox="user-gallery" data-title="تصویر کاربر">
                                    <img id="mainImage" src="<?php echo htmlspecialchars($user['photo_path1'] ?? ''); ?>" alt="عکس اصلی" class="w-full h-80 object-cover rounded-lg cursor-pointer">
                                </a>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <?php if (!empty($user['photo_path' . $i])): ?>
                                        <img src="<?php echo htmlspecialchars($user['photo_path' . $i]); ?>" alt="عکس کوچک <?php echo $i; ?>" class="w-full h-20 object-cover rounded-md cursor-pointer hover:opacity-80 border-2 border-transparent hover:border-blue-500 transition-all" onclick="changeMainImage('<?php echo htmlspecialchars($user['photo_path' . $i]); ?>')">
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                             <div class="hidden">
                                <?php for ($i = 2; $i <= 6; $i++): ?>
                                     <?php if (!empty($user['photo_path' . $i])): ?>
                                        <a href="<?php echo htmlspecialchars($user['photo_path' . $i]); ?>" data-lightbox="user-gallery" data-title="تصویر کاربر <?php echo $i; ?>"></a>
                                     <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="bg-slate-800 p-6 rounded-xl shadow-lg">
                            <h3 class="text-lg font-semibold text-white mb-4">اطلاعات پرونده</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div class="p-2 border-b border-slate-700"><strong>شناسه سیستمی:</strong> <span class="float-left"><?php echo htmlspecialchars($user['id']); ?></span></div>
                                <div class="p-2 border-b border-slate-700"><strong>نام کامل:</strong> <span class="float-left"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span></div>
                                <div class="p-2 border-b border-slate-700"><strong>کد ملی:</strong> <span class="float-left"><?php echo htmlspecialchars($user['national_code'] ?? '---'); ?></span></div>
                                <div class="p-2 border-b border-slate-700"><strong>تاریخ تولد:</strong> <span class="float-left"><?php echo gregorian_to_jalali($user['birth_date']); ?></span></div>
                                <div class="p-2 border-b border-slate-700"><strong>موبایل:</strong> <span class="float-left"><?php echo htmlspecialchars($user['mobile'] ?? '---'); ?></span></div>
                                <div class="p-2 border-b border-slate-700"><strong>خدمات دریافتی:</strong> <span class="float-left text-sky-400"><?php echo htmlspecialchars($user['service_list'] ?? '---'); ?></span></div>
                            </div>
                            <div class="mt-4">
                                <strong class="block mb-2">یادداشت‌ها:</strong>
                                <p class="bg-slate-700/50 p-4 rounded-lg whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($user['note'] ?? '')); ?></p>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-700">
                                <a href="generate_invoice.php?id=<?php echo $user['id']; ?>" target="_blank" class="text-center bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md">مشاهده فاکتور</a>
                                <a href="edit_employee.php?id=<?php echo $user['id']; ?>" class="text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md">ویرایش</a>
                                <button onclick="openSmsModal()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md">ارسال پیامک</button>
                                <button id="delete-button" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md">حذف کاربر</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-slate-800 p-8 rounded-xl shadow-lg text-center">
                    <p class="text-red-500">کاربری با این شناسه یافت نشد.</p>
                    <a href="users.php" class="mt-4 inline-block bg-gray-500 hover:bg-gray-600 px-4 py-2 rounded-md text-white text-sm">بازگشت به لیست</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <div id="smsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-opacity duration-300 opacity-0" onclick="closeSmsModal()">
        <div class="bg-slate-800 p-6 rounded-xl shadow-lg w-full max-w-md" onclick="event.stopPropagation();">
            <h3 class="text-lg font-semibold text-white mb-4">ارسال پیامک به کاربر</h3>
            <form id="smsForm">
                <div>
                    <label for="smsContent" class="block text-sm font-medium mb-1">متن پیامک:</label>
                    <textarea id="smsContent" name="message" rows="4" required class="w-full bg-slate-700 p-2 rounded-md"></textarea>
                </div>
                <div class="mt-4">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md">ارسال</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // تابع برای تغییر عکس اصلی گالری
    function changeMainImage(newSrc) {
        document.getElementById('mainImage').src = newSrc;
        document.getElementById('mainImageLink').href = newSrc;
    }

    // توابع مودال پیامک
    const smsModal = document.getElementById('smsModal');
    function openSmsModal() {
        smsModal.classList.remove('hidden');
        setTimeout(() => smsModal.classList.remove('opacity-0'), 10);
    }
    function closeSmsModal() {
        smsModal.classList.add('opacity-0');
        setTimeout(() => smsModal.classList.add('hidden'), 300);
    }

    // ارسال فرم پیامک با ایجکس
    document.getElementById('smsForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const smsContent = document.getElementById('smsContent').value;
        const employeeId = '<?php echo $user["id"] ?? 0; ?>';
        if (employeeId === '0') { Swal.fire('خطا', 'شناسه کاربر نامعتبر است.', 'error'); return; }
        
        const formData = new FormData();
        formData.append('id', employeeId);
        formData.append('message', smsContent);

        fetch('api/send_sms.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            Swal.fire(data.success ? 'موفق' : 'خطا', data.message || 'خطایی رخ داد', data.success ? 'success' : 'error');
            if (data.success) { closeSmsModal(); document.getElementById('smsContent').value = ''; }
        }).catch(error => { Swal.fire('خطا', 'خطای ارتباطی.', 'error'); });
    });

    // رویداد برای دکمه حذف با SweetAlert
    const deleteButton = document.getElementById('delete-button');
    if(deleteButton) {
        deleteButton.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'آیا برای حذف این کاربر مطمئن هستید؟',
                text: "این عملیات قابل بازگشت نیست!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بله، حذف کن!',
                cancelButtonText: 'انصراف'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'delete_employee.php';
                    
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = 'id';
                    hiddenField.value = '<?php echo $user["id"] ?? 0; ?>';
                    form.appendChild(hiddenField);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            })
        });
    }

    // تنظیمات لایت باکس
    lightbox.option({ 'resizeDuration': 200, 'wrapAround': true });
    </script>
</body>
</html>