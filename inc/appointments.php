<?php
session_start();
require_once "config.php";
$username = $_SESSION['username'] ?? 'مدیر';

// دریافت تمام نوبت‌ها با اطلاعات پزشک
$sql = "SELECT app.*, doc.name as doctor_name 
        FROM appointments app 
        JOIN doctors doc ON app.doctor_id = doc.id 
        ORDER BY app.appointment_time DESC";
$appointments = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت نوبت‌ها</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { font-family: 'Vazirmatn', sans-serif; }
        .status-booked { background-color: rgba(59, 130, 246, 0.2); color: rgba(96, 165, 250, 1); }
        .status-confirmed { background-color: rgba(34, 197, 94, 0.2); color: rgba(74, 222, 128, 1); }
        .status-cancelled { background-color: rgba(239, 68, 68, 0.2); color: rgba(248, 113, 113, 1); }
        .status-completed { background-color: rgba(168, 85, 247, 0.2); color: rgba(192, 132, 252, 1); }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 font-sans">

    <div class="flex flex-col md:flex-row min-h-screen">
        <?php include "inc/nav.php"; ?>

        <main class="flex-1 p-4 sm:p-6">
            <header class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-white">مدیریت نوبت‌ها</h1>
                <a href="booking.php" target="_blank" class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-lg text-white text-sm transition-colors">افزودن نوبت جدید</a>
            </header>

            <div class="bg-slate-800 p-6 rounded-xl shadow-lg">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-400 uppercase bg-slate-700/50">
                            <tr>
                                <th scope="col" class="px-6 py-3">بیمار</th>
                                <th scope="col" class="px-6 py-3">پزشک</th>
                                <th scope="col" class="px-6 py-3">زمان نوبت</th>
                                <th scope="col" class="px-6 py-3">وضعیت</th>
                                <th scope="col" class="px-6 py-3 text-center">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($appointments && $appointments->num_rows > 0): ?>
                                <?php while($app = $appointments->fetch_assoc()): ?>
                                    <tr class="border-b border-slate-700 hover:bg-slate-700/30">
                                        <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($app['patient_name']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($app['doctor_name']); ?></td>
                                        <td class="px-6 py-4 text-cyan-400"><?php echo (new DateTime($app['appointment_time']))->format('Y-m-d H:i'); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 font-semibold text-xs rounded-full status-<?php echo $app['status']; ?>">
                                                <?php echo $app['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center space-x-2 space-x-reverse">
                                            <button onclick="updateStatus(<?php echo $app['id']; ?>, 'confirmed')" class="text-green-400 hover:text-green-300">تایید</button>
                                            <button onclick="updateStatus(<?php echo $app['id']; ?>, 'cancelled')" class="text-red-400 hover:text-red-300">لغو</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-8">هیچ نوبتی یافت نشد.</td></tr>
                            <?php endif; ?>
                            <?php $conn->close(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

<script>
function updateStatus(id, status) {
    const statusText = status === 'confirmed' ? 'تایید' : 'لغو';
    Swal.fire({
        title: `آیا از ${statusText} کردن این نوبت مطمئن هستید؟`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'بله، انجام بده!',
        cancelButtonText: 'خیر'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);

            fetch('api/update_appointment_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('انجام شد!', 'وضعیت نوبت با موفقیت تغییر کرد.', 'success')
                    .then(() => location.reload()); // بارگذاری مجدد صفحه برای دیدن تغییرات
                } else {
                    Swal.fire('خطا!', data.message, 'error');
                }
            });
        }
    })
}
</script>
</body>
</html>