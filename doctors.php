<?php
require_once "config.php";

// ------------ بخش PHP: خواندن اطلاعات کامل از دیتابیس ------------
$sql = "SELECT id, full_name, specialty, address, profile_image_path FROM doctors ORDER BY id DESC";
$result = $conn->query($sql);
$doctors = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیست پزشکان - پنل مدیریت</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { 
            font-family: 'Vazirmatn', sans-serif;
        }
        /* انیمیشن برای بارگذاری نرم و تدریجی */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeInUp {
            opacity: 0; /* شروع به صورت مخفی */
            animation: fadeInUp 0.6s ease-out forwards;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 flex">
    
    <?php require_once "inc/nav.php"; ?>

    <main class="flex-1 p-6">
        <div class="flex justify-between items-center mb-8 animate-fadeInUp">
            <h1 class="text-3xl font-bold text-white">لیست پزشکان</h1>
            <a href="add_doctor.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md flex items-center gap-2 transition-colors">
                <i class="fas fa-plus"></i>
                افزودن پزشک جدید
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php if (count($doctors) > 0): ?>
                <?php foreach ($doctors as $index => $doctor): ?>
                    <?php 
                        // محاسبه تاخیر برای انیمیشن آبشاری
                        $delay = $index * 100; 
                    ?>
                    <div class="bg-slate-800 p-5 rounded-xl shadow-lg flex flex-col sm:flex-row items-center gap-6 animate-fadeInUp" style="animation-delay: <?php echo $delay; ?>ms;">
                        
                        <div class="flex-grow flex items-start w-full gap-4">
                            <?php 
                                // مسیر عکس پروفایل به صورت داینامیک خوانده می‌شود
                                $image_path = !empty($doctor['profile_image_path']) ? htmlspecialchars($doctor['profile_image_path']) : 'img/default_avatar.png';
                            ?>
                            <img class="w-24 h-24 rounded-full object-cover border-4 border-slate-700 flex-shrink-0" src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($doctor['full_name']); ?>">
                            
                            <div class="flex-grow">
                                <h3 class="font-bold text-xl text-white"><?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                                <p class="text-slate-400 text-sm mb-4"><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                                
                                <p class="text-sm text-slate-400 mt-2 flex items-start gap-2">
                                    <i class="fas fa-map-marker-alt text-slate-500 mt-1 flex-shrink-0"></i>
                                    <span><?php echo htmlspecialchars($doctor['address']); ?></span>
                                </p>
                            </div>
                        </div>

                        <div class="w-full sm:w-auto flex-shrink-0 mt-4 sm:mt-0">
                            <a href="edit_doctor.php?id=<?php echo $doctor['id']; ?>" class="w-full sm:w-auto inline-block text-center bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-bold">
                                مدیریت
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-slate-800 text-center p-8 rounded-xl lg:col-span-2 animate-fadeInUp">
                    <p class="text-slate-400">هیچ پزشکی یافت نشد.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>