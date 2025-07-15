<?php
// فعال‌سازی نمایش خطا
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "config.php";

// بررسی وجود آی‌دی بیمار در URL
if (!isset($_GET['employee_id']) || !is_numeric($_GET['employee_id'])) {
    die("خطا: شناسه بیمار مشخص نشده است.");
}
$employee_id = (int)$_GET['employee_id'];

// --- واکشی اطلاعات بیمار و مسیر عکس‌ها ---
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("بیماری با این شناسه یافت نشد.");
}
$employee = $result->fetch_assoc();
$stmt->close();

$photo_paths = [];
for ($i = 1; $i <= 6; $i++) {
    if (!empty($employee['photo_path' . $i]) && $employee['photo_path' . $i] !== 'img/profile.png') {
        $photo_paths['photo_path' . $i] = $employee['photo_path' . $i];
    }
}

// --- پردازش آپلود عکس جدید ---
$upload_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['new_photo'])) {
    // پیدا کردن اولین جای خالی برای عکس
    $available_slot = '';
    for ($i = 1; $i <= 6; $i++) {
        if (empty($employee['photo_path' . $i]) || $employee['photo_path' . $i] === 'img/profile.png') {
            $available_slot = 'photo_path' . $i;
            break;
        }
    }

    if (empty($available_slot)) {
        $upload_message = '<div class="bg-red-500/20 text-red-400 p-3 rounded-md">ظرفیت عکس‌ها تکمیل است. ابتدا یک عکس را حذف کنید.</div>';
    } elseif ($_FILES['new_photo']['error'] === 0) {
        $user_dir = "uploads/" . preg_replace('/[^0-9]/', '', $employee['national_code']);
        if (!is_dir($user_dir)) mkdir($user_dir, 0777, true);
        
        $photo_file = $_FILES['new_photo'];
        $photo_name = time() . '_' . basename($photo_file['name']);
        $target_file = $user_dir . '/' . $photo_name;

        if (move_uploaded_file($photo_file['tmp_name'], $target_file)) {
            $update_stmt = $conn->prepare("UPDATE employees SET $available_slot = ? WHERE id = ?");
            $update_stmt->bind_param("si", $target_file, $employee_id);
            if ($update_stmt->execute()) {
                header("Location: gallery.php?employee_id=$employee_id&status=uploaded");
                exit();
            }
            $update_stmt->close();
        } else {
            $upload_message = '<div class="bg-red-500/20 text-red-400 p-3 rounded-md">خطا در آپلود فایل.</div>';
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کتابخانه تصاویر بیمار</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 font-sans">
<div class="flex">
    <?php require_once "inc/nav.php"; ?>
    <main class="flex-1 p-6">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">کتابخانه تصاویر: <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h1>
            <a href="employees.php" class="bg-slate-600 hover:bg-slate-700 px-4 py-2 rounded-lg text-white text-sm transition-colors">بازگشت به لیست بیماران</a>
        </header>

        <div class="bg-slate-800 p-6 rounded-xl shadow-lg mb-8">
            <h2 class="text-xl font-semibold mb-4 text-white">افزودن عکس جدید</h2>
            <?php echo $upload_message; ?>
            <form action="gallery.php?employee_id=<?php echo $employee_id; ?>" method="POST" enctype="multipart/form-data" class="flex items-center gap-4">
                <input type="file" name="new_photo" required accept="image/*" class="block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100"/>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-5 rounded-md whitespace-nowrap">آپلود عکس</button>
            </form>
        </div>

        <div class="bg-slate-800 p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-semibold mb-6 text-white">تصاویر موجود</h2>
            <?php if (empty($photo_paths)): ?>
                <p class="text-center text-slate-400 py-8">هیچ عکسی برای این بیمار یافت نشد.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php foreach ($photo_paths as $column_name => $path): ?>
                        <div class="relative group">
                            <img src="<?php echo htmlspecialchars($path); ?>" alt="تصویر بیمار" class="w-full h-56 object-cover rounded-lg shadow-md">
                            <div class="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-lg">
                                <button onclick="deletePhoto('<?php echo $employee_id; ?>', '<?php echo $column_name; ?>')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md">حذف</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function deletePhoto(employeeId, columnName) {
    Swal.fire({
        title: 'آیا از حذف این عکس مطمئن هستید؟',
        text: "این عمل غیرقابل بازگشت است!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بله، حذف کن!',
        cancelButtonText: 'خیر'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('employee_id', employeeId);
            formData.append('column_name', columnName);

            fetch('api/delete_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('حذف شد!', 'عکس با موفقیت حذف شد.', 'success')
                    .then(() => location.reload());
                } else {
                    Swal.fire('خطا!', data.message || 'مشکلی در حذف عکس پیش آمد.', 'error');
                }
            });
        }
    });
}
</script>
</body>
</html>