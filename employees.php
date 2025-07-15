<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

// توابع کمکی
function convertNumbersToEnglish($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english = range(0, 9);
    return str_replace($persian, $english, str_replace($arabic, $english, $string));
}
function jalali_to_gregorian($jy, $jm, $jd) {
    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
    $jy += 1595;
    $days = -355668 + (365 * $jy) + (((int)($jy / 33)) * 8) + ((int)((($jy % 33) + 3) / 4)) + $jd;
    if ($jm < 7) { $days += ($jm - 1) * 31; } else { $days += 186 + (($jm - 7) * 30); }
    $gy = 400 * ((int)($days / 146097)); $days %= 146097;
    if ($days > 36524) { $gy += 100 * ((int)(--$days / 36524)); $days %= 36524; if ($days >= 365) $days++; }
    $gy += 4 * ((int)($days / 1461)); $days %= 1461;
    if ($days > 365) { $gy += (int)(($days - 1) / 365); $days = ($days - 1) % 365; }
    $gd = $days + 1;
    foreach ($g_days_in_month as $gm => $v) { if (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) { if ($gm == 1) $v++; } if ($gd <= $v) break; $gd -= $v; }
    return [$gy, $gm + 1, $gd];
}

require_once "config.php";

// متغیرهای لازم برای پر کردن فرم
$prefill_first_name = ''; $prefill_last_name = ''; $prefill_mobile = '';
$prefill_national_code = ''; $prefill_doctor_name = ''; $prefill_note = '';
$prefill_costs = []; $prefill_costs_json = '[]';
$readonly_attributes = '';
$readonly_class = '';

// دریافت لیست پزشکان
$doctors_sql = "SELECT id, full_name FROM doctors WHERE is_active = 1";
$doctors_result = $conn->query($doctors_sql);
$doctors_list = $doctors_result->fetch_all(MYSQLI_ASSOC);

// بررسی برای پر کردن خودکار فرم از طریق نوبت
if (isset($_GET['appointment_id']) && is_numeric($_GET['appointment_id'])) {
    $readonly_attributes = 'readonly';
    $readonly_class = 'bg-slate-600 cursor-not-allowed text-slate-400';
    $appointment_id = (int)$_GET['appointment_id'];
    $sql_app_info = "SELECT app.patient_name, app.patient_mobile, app.patient_national_code, doc.full_name as doctor_name, ser.name as service_name, ser.default_price as service_price FROM appointments app JOIN doctors doc ON app.doctor_id = doc.id LEFT JOIN services ser ON app.service_id = ser.id WHERE app.id = ?";
    $stmt_app_info = $conn->prepare($sql_app_info);
    if ($stmt_app_info) {
        $stmt_app_info->bind_param("i", $appointment_id);
        $stmt_app_info->execute();
        $result = $stmt_app_info->get_result();
        if ($result->num_rows > 0) {
            $app_data = $result->fetch_assoc();
            $patient_full_name = explode(' ', $app_data['patient_name'], 2);
            $prefill_first_name = $patient_full_name[0] ?? '';
            $prefill_last_name = $patient_full_name[1] ?? '';
            $prefill_mobile = $app_data['patient_mobile'];
            $prefill_national_code = $app_data['patient_national_code'];
            $prefill_doctor_name = $app_data['doctor_name'];
            $prefill_note = "پرونده تشکیل شده از نوبت با دکتر " . $app_data['doctor_name'];
            if (!empty($app_data['service_name'])) {
                $prefill_costs[] = ['item' => $app_data['service_name'], 'quantity' => 1, 'price' => $app_data['service_price'] ?? 0];
                $prefill_costs_json = json_encode($prefill_costs);
            }
        }
        $stmt_app_info->close();
    }
    $sql_update_status = "UPDATE appointments SET status = 'completed' WHERE id = ? AND status != 'completed'";
    $stmt_update = $conn->prepare($sql_update_status);
    if ($stmt_update) {
        $stmt_update->bind_param("i", $appointment_id);
        $stmt_update->execute();
        $stmt_update->close();
    }
}

// دریافت لیست کل خدمات
$services_sql = "SELECT name, default_price FROM services WHERE is_active = 1";
$services_result = $conn->query($services_sql);
$predefinedServices = $services_result ? $services_result->fetch_all(MYSQLI_ASSOC) : [];

$statusMessage = "";
$statusClass = "";
$defaultPhoto = 'img/profile.png';

// ## شروع بلوک تکمیل شده ##
// بررسی ارسال فرم POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $national_code = $_POST['national_code'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $note = $_POST['note'] ?? '';
    $costs_data = $_POST['costs_data'] ?? '[]';
    $birth_date_jalali = $_POST['birth_date'] ?? '';
    $birth_date = null;
    
    // تبدیل تاریخ تولد شمسی به میلادی
    if (!empty($birth_date_jalali)) {
        $jalali_parts = explode('-', convertNumbersToEnglish($birth_date_jalali));
        if(count($jalali_parts) === 3) {
            list($jy, $jm, $jd) = $jalali_parts;
            list($gy, $gm, $gd) = jalali_to_gregorian((int)$jy, (int)$jm, (int)$jd);
            $birth_date = "$gy-$gm-$gd";
        }
    }
    
    // افزودن نام پزشک به یادداشت
    if (isset($_POST['manual_doctor_id']) && !empty($_POST['manual_doctor_id'])) {
        $doc_id = $_POST['manual_doctor_id'];
        $doc_stmt = $conn->prepare("SELECT full_name FROM doctors WHERE id = ?");
        $doc_stmt->bind_param("i", $doc_id);
        $doc_stmt->execute();
        $doc_res = $doc_stmt->get_result();
        if($doc_data = $doc_res->fetch_assoc()) {
            $note = "پزشک معالج: " . $doc_data['full_name'] . "\n" . $note;
        }
    }
    
    $national_code = convertNumbersToEnglish($national_code);
    $mobile = convertNumbersToEnglish($mobile);
    
    // ساخت پوشه برای کاربر بر اساس کد ملی
    $user_dir = "uploads/" . preg_replace('/[^0-9]/', '', $national_code);
    if (!is_dir($user_dir)) {
        if (!mkdir($user_dir, 0777, true)) {
            $statusMessage = "خطا در ایجاد پوشه کاربر.";
            $statusClass = "error";
        }
    }
    
    // پردازش آپلود عکس‌ها
    $photo_paths = [];
    if (empty($statusMessage)) {
        for ($i = 1; $i <= 6; $i++) {
            if (isset($_FILES['photo' . $i]) && $_FILES['photo' . $i]['error'] == 0) {
                $photo_file = $_FILES['photo' . $i];
                $photo_name = time() . '_' . basename($photo_file['name']);
                $target_file = $user_dir . '/' . $photo_name;
                if (move_uploaded_file($photo_file['tmp_name'], $target_file)) {
                    $photo_paths[$i - 1] = $target_file;
                } else {
                    $statusMessage = "خطا در آپلود عکس " . $i;
                    $statusClass = "error";
                    break;
                }
            } else {
                $photo_paths[$i - 1] = $defaultPhoto;
            }
        }
    }
    while (count($photo_paths) < 6) {
        $photo_paths[] = $defaultPhoto;
    }
    
    // ذخیره اطلاعات در دیتابیس با استفاده از تراکنش
    if (empty($statusMessage)) {
        $conn->begin_transaction();
        try {
            // ذخیره اطلاعات اصلی بیمار
            $sql_employee = "INSERT INTO employees (first_name, last_name, birth_date, national_code, mobile, photo_path1, photo_path2, photo_path3, photo_path4, photo_path5, photo_path6, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_employee = $conn->prepare($sql_employee);
            $stmt_employee->bind_param("ssssssssssss", $first_name, $last_name, $birth_date, $national_code, $mobile, $photo_paths[0], $photo_paths[1], $photo_paths[2], $photo_paths[3], $photo_paths[4], $photo_paths[5], $note);
            
            if ($stmt_employee->execute()) {
                $newUserId = $conn->insert_id;
                $costs = json_decode($costs_data, true);
                
                // ذخیره هزینه‌ها در صورت وجود
                if (is_array($costs) && !empty($costs)) {
                    $sql_cost = "INSERT INTO employee_costs (employee_id, item_name, quantity, price) VALUES (?, ?, ?, ?)";
                    $stmt_cost = $conn->prepare($sql_cost);
                    foreach ($costs as $cost) {
                        $stmt_cost->bind_param("isid", $newUserId, $cost['item'], $cost['quantity'], $cost['price']);
                        if (!$stmt_cost->execute()) {
                            throw new Exception("خطا در ذخیره هزینه: " . $stmt_cost->error);
                        }
                    }
                    $stmt_cost->close();
                }
                
                $conn->commit();
                
                // نمایش پیام موفقیت و ریدایرکت
                echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><title>در حال انتقال...</title><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background-color:#1e293b;'><script>Swal.fire({title:'موفقیت!',text:'پرونده بیمار با موفقیت ثبت شد. در حال انتقال...',icon:'success',timer:2000,showConfirmButton:false,timerProgressBar:true}).then(() => {window.location.href = 'employee_details.php?id=" . $newUserId . "';});</script></body></html>";
                $conn->close();
                exit();
                
            } else {
                throw new Exception("خطا در ذخیره اطلاعات کاربر: " . $stmt_employee->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            // بررسی خطای کد ملی تکراری
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $statusMessage = "کد ملی وارد شده تکراری است.";
            } else {
                $statusMessage = "خطای دیتابیس: " . $e->getMessage();
            }
            $statusClass = "error";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($statusMessage)) {
    $conn->close();
}
// ## پایان بلوک تکمیل شده ##
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فرم ایجاد پرونده بیمار</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        .message.success { color: #16a34a; background-color: #dcfce7; border: 1px solid #4ade80; }
        .message.error { color: #dc2626; background-color: #fee2e2; border: 1px solid #f87171; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.7); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background-color: #1e293b; padding: 1rem; border-radius: 0.75rem; width: 95%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        @media (min-width: 640px) { .modal-content { padding: 2rem; } }
        #costs-list { scrollbar-width: none; -ms-overflow-style: none; }
        #costs-list::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 font-sans">
    <div class="flex flex-col md:flex-row">
        <?php require_once "inc/nav.php"; ?>
        <main class="flex-1 p-4 sm:p-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-8">ایجاد پرونده بیمار</h1>
            <div class="max-w-4xl mx-auto">
                <?php if (!empty($statusMessage)): ?>
                    <div class="message <?php echo $statusClass; ?> p-4 rounded-md text-center mb-6"><?php echo htmlspecialchars($statusMessage); ?></div>
                <?php endif; ?>
                <form id="main-form" method="POST" enctype="multipart/form-data" class="bg-slate-800 p-6 sm:p-8 rounded-xl shadow-lg space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div><label for="first_name" class="block text-sm font-medium mb-1">نام زیباجو:</label><input type="text" id="first_name" name="first_name" required class="w-full bg-slate-700 p-2 rounded-md" value="<?php echo htmlspecialchars($prefill_first_name); ?>"></div>
                        <div><label for="last_name" class="block text-sm font-medium mb-1">نام خانوادگی:</label><input type="text" id="last_name" name="last_name" required class="w-full bg-slate-700 p-2 rounded-md" value="<?php echo htmlspecialchars($prefill_last_name); ?>"></div>
                        <div><label for="doctor_name" class="block text-sm font-medium mb-1">پزشک معالج:</label><?php if (!empty($prefill_doctor_name)): ?><input type="text" id="doctor_name" readonly class="w-full bg-slate-600 p-2 rounded-md text-slate-400 cursor-not-allowed" value="<?php echo htmlspecialchars($prefill_doctor_name); ?>"><?php else: ?><select name="manual_doctor_id" id="manual_doctor_id" class="w-full bg-slate-700 p-2 rounded-md"><option value="">انتخاب کنید...</option><?php foreach($doctors_list as $doctor): ?><option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['full_name']); ?></option><?php endforeach; ?></select><?php endif; ?></div>
                        <div><label for="birth_date_persian_input" class="block text-sm font-medium mb-1">تاریخ تولد:</label><input type="text" id="birth_date_persian_input" required class="w-full bg-slate-700 p-2 rounded-md" autocomplete="off"><input type="hidden" id="birth_date_gregorian" name="birth_date"></div>
                        <div><label for="national_code" class="block text-sm font-medium mb-1">کد ملی:</label><input type="text" id="national_code" name="national_code" required class="w-full bg-slate-700 p-2 rounded-md <?php echo $readonly_class; ?>" value="<?php echo htmlspecialchars($prefill_national_code); ?>" <?php echo $readonly_attributes; ?>></div>
                        <div><label for="mobile" class="block text-sm font-medium mb-1">شماره موبایل:</label><input type="text" id="mobile" name="mobile" required class="w-full bg-slate-700 p-2 rounded-md" maxlength="11" value="<?php echo htmlspecialchars($prefill_mobile); ?>"></div>
                    </div>
                    <div>
                        <label for="note" class="block text-sm font-medium mb-1">یادداشت:</label>
                        <div class="relative"><textarea id="note" name="note" class="w-full bg-slate-700 p-2 rounded-md pr-28" rows="4"><?php echo htmlspecialchars($prefill_note); ?></textarea><button type="button" id="refine-note-btn" class="absolute top-2 right-2 flex items-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold px-3 py-1.5 rounded-md transition-all"><svg id="refine-icon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg><svg id="refine-spinner" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span id="refine-btn-text">اصلاح با AI</span></button></div>
                    </div>
                    <input type="hidden" id="costs_data" name="costs_data" value='<?php echo htmlspecialchars($prefill_costs_json); ?>'>
                    <div class="border-t border-slate-700 pt-6"><h3 class="text-lg font-semibold mb-4">آپلود تصاویر</h3><div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4"><?php for($i=1;$i<=6;$i++):?><div class="relative"><label for="photo<?=$i?>" class="relative flex flex-col items-center justify-center w-full h-32 bg-slate-800 border-2 border-dashed border-slate-600 rounded-lg cursor-pointer hover:bg-slate-700"><div id="placeholder<?=$i?>" class="text-center p-2"><svg class="mx-auto h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg><p class="text-xs text-slate-400 mt-2">عکس <?=$i?></p></div><img id="preview<?=$i?>" class="absolute inset-0 w-full h-full object-cover rounded-lg hidden" src="#" alt="پیش‌نمایش"><button type="button" id="remove<?=$i?>" class="absolute top-1 right-1 bg-red-600/70 text-white rounded-full w-6 h-6 flex items-center justify-center hidden hover:bg-red-600 text-lg font-bold" onclick="removeFile(<?=$i?>)">&times;</button></label><input type="file" id="photo<?=$i?>" name="photo<?=$i?>" class="hidden" onchange="previewFile(this, <?=$i?>)" accept="image/*"></div><?php endfor;?></div></div>
                    <div class="pt-6 flex flex-col sm:flex-row items-center gap-4">
                        <button type="button" id="open-costs-modal" class="w-full sm:w-1/2 bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 px-4 rounded-md transition-colors">افزودن هزینه</button>
                        <button type="submit" class="w-full sm:w-1/2 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-md">ذخیره اطلاعات</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <div id="costs-modal" class="modal-overlay"><div class="modal-content"><h2 class="text-xl sm:text-2xl font-bold text-white mb-6">ثبت هزینه‌های خدمات</h2><div id="costs-list" class="space-y-4 mb-6 max-h-64 overflow-y-auto pr-2"></div><button type="button" id="add-cost-row" class="mb-6 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md">افزودن ردیف جدید</button><div class="text-left mt-4 border-t border-slate-600 pt-4"><p class="text-lg font-semibold">جمع کل: <span id="total-cost">0</span> تومان</p></div><div class="flex justify-end gap-4 mt-8"><button type="button" id="close-costs-modal" class="bg-slate-600 hover:bg-slate-700 text-white py-2 px-6 rounded-md">انصراف</button><button type="button" id="save-costs" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-md">ذخیره هزینه‌ها</button></div></div></div>
    <datalist id="services-datalist"><?php if(is_array($predefinedServices)||is_object($predefinedServices)) foreach($predefinedServices as $service):?><option value="<?php echo htmlspecialchars($service['name']);?>"><?php endforeach;?></datalist>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-date/dist/persian-date.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const predefinedServices = <?php echo json_encode($predefinedServices); ?>;
        $(document).ready(function() {
            $('#birth_date_persian_input').persianDatepicker({format:'YYYY-MM-DD',autoClose:true,altField:'#birth_date_gregorian',altFormat:'YYYY-MM-DD',observer:true,initialValue:false});
            window.previewFile=function(input,index){const file=input.files[0];if(file){const reader=new FileReader();reader.onload=function(e){$(`#preview${index}`).attr('src',e.target.result).removeClass('hidden');$(`#placeholder${index}`).addClass('hidden');$(`#remove${index}`).removeClass('hidden');}
            reader.readAsDataURL(file);}};
            window.removeFile=function(index){$(`#photo${index}`).val('');$(`#preview${index}`).attr('src','#').addClass('hidden');$(`#placeholder${index}`).removeClass('hidden');$(`#remove${index}`).addClass('hidden');};
            const costsModal=$('#costs-modal');const costsList=$('#costs-list');const totalCostEl=$('#total-cost');const costsDataInput=$('#costs_data');
            function initializeCosts(){try{const initialCostsJSON=costsDataInput.val();return initialCostsJSON&&initialCostsJSON!=='[]'?JSON.parse(initialCostsJSON):[];}catch(e){console.error("Error parsing costs JSON",e);return[];}}
            let tempCosts=[];
            function syncMainFormCosts(){const costsBtn=$('#open-costs-modal');const costsArray=JSON.parse(costsDataInput.val()||'[]');if(costsArray.length>0){costsBtn.removeClass('bg-yellow-600').addClass('bg-green-600').text(`هزینه‌ها (${costsArray.length} مورد)`);}else{costsBtn.removeClass('bg-green-600').addClass('bg-yellow-600').text('افزودن هزینه');}}
            
            // ## تابع اصلاح شده برای واکنش‌گرایی ##
            function addCostRow(item='',quantity=1,price=''){
                const newRowHTML=`
                    <div class="cost-row grid grid-cols-1 md:grid-cols-12 gap-y-2 md:gap-x-2 items-center">
                        <div class="md:col-span-5">
                            <label class="text-xs md:hidden mb-1 block">نام خدمت/کالا</label>
                            <input type="text" list="services-datalist" placeholder="نام خدمت/کالا" value="${item}" class="cost-item w-full bg-slate-700 p-2 rounded-md text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs md:hidden mb-1 block">تعداد</label>
                            <input type="number" placeholder="تعداد" value="${quantity}" min="1" class="cost-quantity w-full bg-slate-700 p-2 rounded-md text-sm">
                        </div>
                        <div class="md:col-span-4">
                            <label class="text-xs md:hidden mb-1 block">قیمت واحد</label>
                            <input type="number" placeholder="قیمت واحد" value="${price}" class="cost-price w-full bg-slate-700 p-2 rounded-md text-sm">
                        </div>
                        <div class="md:col-span-1 text-left md:text-center">
                            <button type="button" class="remove-cost-row text-red-500 hover:text-red-400 text-2xl font-bold">&times;</button>
                        </div>
                    </div>`;
                costsList.append(newRowHTML);
            }

            function updateTotal(){let total=0;$('.cost-row').each(function(){const quantity=parseFloat($(this).find('.cost-quantity').val())||0;const price=parseFloat($(this).find('.cost-price').val())||0;total+=quantity*price;});totalCostEl.text(total.toLocaleString('fa-IR'));}
            $('#open-costs-modal').on('click',function(){costsList.empty();try{tempCosts=JSON.parse(costsDataInput.val()||'[]');}catch(e){tempCosts=[];}
            if(tempCosts.length>0){tempCosts.forEach(cost=>addCostRow(cost.item,cost.quantity,cost.price));}else{addCostRow();}
            updateTotal();costsModal.css('display','flex');});
            $('#close-costs-modal').on('click',()=>costsModal.hide());$('#add-cost-row').on('click',()=>addCostRow());
            costsList.on('click','.remove-cost-row',function(){$(this).closest('.cost-row').remove();updateTotal();});
            $('#save-costs').on('click',function(){let finalCosts=[];$('.cost-row').each(function(){const item=$(this).find('.cost-item').val().trim();const quantity=$(this).find('.cost-quantity').val();const price=$(this).find('.cost-price').val();if(item&&quantity>0&&price>=0){finalCosts.push({item,quantity,price});}});costsDataInput.val(JSON.stringify(finalCosts));syncMainFormCosts();costsModal.hide();});
            const servicePriceMap=new Map(predefinedServices.map(s=>[s.name,s.default_price]));
            costsList.on('input','.cost-item',function(){const selectedItem=$(this).val();if(servicePriceMap.has(selectedItem)){const price=servicePriceMap.get(selectedItem);$(this).closest('.cost-row').find('.cost-price').val(price);updateTotal();}});
            costsList.on('input','.cost-quantity, .cost-price',updateTotal);
            syncMainFormCosts();
            $('#refine-note-btn').on('click',function(){const noteText=$('#note').val();if(!noteText.trim()){Swal.fire('توجه!','لطفا ابتدا یادداشتی برای اصلاح بنویسید.','warning');return;}
            const btn=$(this);btn.prop('disabled',true);$('#refine-icon').addClass('hidden');$('#refine-spinner').removeClass('hidden');$('#refine-btn-text').text('در حال پردازش...');
            const formData=new FormData();formData.append('note',noteText);
            fetch('api/refine_note.php',{method:'POST',body:formData}).then(response=>response.json()).then(data=>{if(data.success){$('#note').val(data.refined_text);Swal.fire('موفق!','یادداشت با موفقیت اصلاح شد.','success');}else{Swal.fire('خطا!','مشکلی در ارتباط با سرویس هوش مصنوعی رخ داد: '+data.message,'error');}}).catch(error=>Swal.fire('خطای شبکه!','لطفا اتصال اینترنت خود را بررسی کنید.','error')).finally(()=>{btn.prop('disabled',false);$('#refine-icon').removeClass('hidden');$('#refine-spinner').addClass('hidden');$('#refine-btn-text').text('اصلاح با AI');});});
        });
    </script>
</body>
</html>