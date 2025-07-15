<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رزرو آنلاین نوبت</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { 
            font-family: 'Vazirmatn', sans-serif;
        }
        /* ---- استایل برای افکت لودینگ ---- */
        .loader {
            width: 48px;
            height: 48px;
            border: 5px solid #cbd5e1; /* slate-300 */
            border-bottom-color: #3b82f6; /* blue-500 */
            border-radius: 50%;
            display: inline-block;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }
        @keyframes rotation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-300">

    <header class="bg-slate-800 shadow-md">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center py-4">
            <h1 class="font-bold text-xl text-blue-400">Doctorito</h1>
            <div>
                <a href="/login.php" class="text-slate-300 hover:text-blue-400 ml-4">ورود به پنل</a>
            </div>
        </nav>
    </header>

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <main>
            <h1 class="text-2xl font-bold text-white mb-6">بهترین پزشکان برای شما</h1>
            
            <div id="doctors-list-container" class="space-y-6">
                <div id="loader-container" class="text-center py-10">
                    <span class="loader"></span>
                    <p class="mt-4 text-slate-400">در حال بارگذاری لیست پزشکان...</p>
                </div>
            </div>
        </main>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const doctorsContainer = document.getElementById('doctors-list-container');
    const loaderContainer = document.getElementById('loader-container');

    fetch('api/get_doctors.php')
        .then(response => response.json())
        .then(doctors => {
            loaderContainer.style.display = 'none';
            if (doctors.length === 0) {
                doctorsContainer.innerHTML = '<p class="text-center text-slate-400">در حال حاضر پزشکی برای نمایش وجود ندارد.</p>';
                return;
            }
            let doctorsHtml = '';
            doctors.forEach(doctor => {
                const imagePath = doctor.profile_image_path ? doctor.profile_image_path : 'img/default_avatar.png';
                // Escape single quotes in doctor name for javascript function call
                const safeDoctorName = doctor.full_name.replace(/'/g, "\\'");
                doctorsHtml += `
                    <div class="bg-slate-800 p-5 rounded-xl shadow-lg flex flex-col sm:flex-row items-center gap-6">
                        <div class="flex-grow flex items-start gap-4">
                            <img class="w-24 h-24 rounded-full object-cover border-4 border-slate-700" src="${imagePath}" alt="${doctor.full_name}">
                            <div>
                                <h3 class="font-bold text-lg text-white">${doctor.full_name}</h3>
                                <p class="text-slate-400 text-sm">${doctor.specialty}</p>
                                <p class="text-sm text-slate-500 mt-3 flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                    <span>${doctor.address || 'آدرس ثبت نشده'}</span>
                                </p>
                            </div>
                        </div>
                        <div class="w-full sm:w-auto flex-shrink-0 text-center mt-4 sm:mt-0">
                            <button onclick="startBooking(${doctor.id}, '${safeDoctorName}')" class="w-full sm:w-auto inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-bold">
                                دریافت نوبت
                            </button>
                            <a href="doctor_profile.php?id=<?php echo $doctor['id']; ?>" class="... bg-blue-600 ...">
    دریافت نوبت
</a>
                        </div>
                    </div>
                `;
            });
            doctorsContainer.innerHTML = doctorsHtml;
        })
        .catch(error => {
            loaderContainer.innerHTML = '<p class="text-center text-red-400">خطا در بارگذاری اطلاعات. لطفا دوباره تلاش کنید.</p>';
            console.error('Fetch Error:', error);
        });
});

let selectedSlotTime; // متغیر برای نگهداری زمان انتخاب شده

// تابع برای هایلایت کردن اسلات زمانی
function selectSlot(element, datetime) {
    // This is a helper function to visually indicate which slot is selected
    const allSlots = document.querySelectorAll('.swal-time-slot');
    allSlots.forEach(btn => {
        btn.classList.remove('bg-blue-600');
        btn.classList.add('bg-slate-700');
    });
    element.classList.remove('bg-slate-700');
    element.classList.add('bg-blue-600');
    selectedSlotTime = datetime;
}

// تابع اصلی برای شروع فرآیند رزرو
function startBooking(doctorId, doctorName) {
    selectedSlotTime = null; // ریست کردن انتخاب قبلی
    Swal.fire({
        title: `انتخاب روز برای ${doctorName}`,
        html: '<div class="loader"></div>',
        showConfirmButton: false, // دکمه تایید در این مرحله نیاز نیست
        showCancelButton: true,
        cancelButtonText: 'انصراف',
        background: '#1e293b',
        color: '#ffffff',
        didOpen: () => {
            fetch(`api/get_available_days.php?doctor_id=${doctorId}`)
                .then(response => response.json())
                .then(days => {
                    if (days.length === 0) {
                        Swal.update({ html: '<p>روز خالی برای این پزشک یافت نشد.</p>' });
                        return;
                    }
                    let daysHtml = '<div><p class="mb-3 text-slate-300">لطفا یکی از روزهای زیر را انتخاب کنید:</p><div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-4">';
                    days.forEach(day => {
                        daysHtml += `<button onclick="showSlotsForDay(${doctorId}, '${day.available_date}')" class="p-2 bg-slate-700 rounded-md hover:bg-slate-600">${day.formatted_date}</button>`;
                    });
                    daysHtml += '</div></div>';
                    Swal.update({ html: daysHtml });
                });
        }
    });
}

// تابع برای نمایش ساعت‌های یک روز خاص
function showSlotsForDay(doctorId, date) {
    Swal.update({
        title: `انتخاب ساعت برای تاریخ ${date}`,
        html: '<div class="loader"></div>',
        showConfirmButton: false, // We will add the confirm button dynamically
    });
    fetch(`api/get_available_slots.php?doctor_id=${doctorId}&date=${date}`)
        .then(response => response.json())
        .then(slots => {
            let slotsHtml = '';
            if (slots.length === 0) {
                slotsHtml = '<p>متاسفانه ساعت خالی برای این روز وجود ندارد.</p>';
            } else {
                slotsHtml = '<div><p class="mb-3 text-slate-300">لطفا یکی از ساعت‌های زیر را انتخاب کنید:</p><div class="grid grid-cols-3 md:grid-cols-4 gap-2 mt-4">';
                slots.forEach(slot => {
                    slotsHtml += `<button onclick="selectSlot(this, '${slot.datetime}')" class="swal-time-slot p-2 bg-slate-700 rounded-md hover:bg-slate-600">${slot.time}</button>`;
                });
                slotsHtml += '</div></div>';
            }
            
            Swal.update({
                html: slotsHtml,
                showConfirmButton: slots.length > 0, // Only show confirm if there are slots
                confirmButtonText: 'رزرو این ساعت',
            });
            // Re-attach event handler for the confirm button
            if (slots.length > 0) {
                 Swal.getConfirmButton().onclick = () => {
                    if (!selectedSlotTime) {
                        Swal.showValidationMessage('لطفا یک ساعت را انتخاب کنید!');
                        return;
                    }
                    getPatientInfo(doctorId, selectedSlotTime);
                };
            }
        });
}

// تابع برای گرفتن اطلاعات نهایی بیمار
function getPatientInfo(doctorId, appointmentTime) {
    Swal.fire({
        title: 'اطلاعات شما',
        background: '#1e293b', color: '#ffffff',
        html: `
            <p class="text-sm text-slate-300 mb-4">برای نهایی کردن رزرو در تاریخ ${appointmentTime.split(' ')[0]} ساعت ${appointmentTime.split(' ')[1].substring(0,5)}، اطلاعات زیر را وارد کنید.</p>
            <input id="swal-name" class="swal2-input bg-slate-700 text-white" placeholder="نام و نام خانوادگی">
            <input id="swal-mobile" class="swal2-input bg-slate-700 text-white" placeholder="شماره موبایل">
        `,
        confirmButtonText: 'ثبت نهایی',
        showCancelButton: true,
        cancelButtonText: 'انصراف',
        preConfirm: () => {
            const name = document.getElementById('swal-name').value;
            const mobile = document.getElementById('swal-mobile').value;
            if (!name || !mobile) {
                Swal.showValidationMessage('پر کردن تمام فیلدها الزامی است');
            }
            return { name, mobile };
        }
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('doctor_id', doctorId);
            formData.append('patient_name', result.value.name);
            formData.append('mobile', result.value.mobile);
            formData.append('appointment_time', appointmentTime);

            fetch('api/book_appointment.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({icon: 'success', title: 'موفق!', text: 'نوبت شما با موفقیت ثبت شد.', background: '#1e293b', color: '#ffffff'});
                    } else {
                        Swal.fire({icon: 'error', title: 'خطا!', text: data.message || 'مشکلی در ثبت نوبت پیش آمد.', background: '#1e293b', color: '#ffffff'});
                    }
                });
        }
    });
}
</script>
</body>
</html>