<?php
include "inc/nav.php";
include "./config.php";

?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات کاربر</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Lightbox CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive_991.css" media="(max-width:991px)">
    <link rel="stylesheet" href="css/responsive_768.css" media="(max-width:768px)">
    <link rel="stylesheet" href="css/font.css">
    <style>
        .employee-details {
            border: 1px solid #ccc;
            padding: 20px;
            margin-top: 20px;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .employee-details img {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
            border-radius: 5px;
        }
        .btn-custom {
            width: 100%;
            margin-top: 10px;
        }
        .details-group {
            margin-top: 10px;
        }
        .details-group p {
            margin-bottom: 5px;
        }
        .back-btn {
            margin-top: 20px;
            display: inline-block;
            color: #ffffff;
            background-color: #dc3545;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        .back-btn:hover {
            text-decoration: none;
            background-color: #c82333;
        }
        .multiline-text {
        white-space: pre-wrap; /* حفظ فواصل و خطوط جدید */
        word-wrap: break-word; /* جلوگیری از شکستن کلمات */
    }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mt-4 mb-4 text-center">جزئیات کاربر</h1>
        <?php

        // اتصال به دیتابیس
        include "config.php";
        
        if (isset($_GET['id'])) {
            $employee_id = $_GET['id'];
            
            // کوئری برای دریافت جزئیات کاربر خاص
            $sql = "SELECT * FROM employees1 WHERE id = $employee_id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo '<div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">';
                echo '<ol class="carousel-indicators">';
                for ($i = 0; $i < 6; $i++) {
                    $active = ($i == 0) ? 'active' : '';
                    echo '<li data-target="#carouselExampleIndicators" data-slide-to="' . $i . '" class="' . $active . '"></li>';
                }
                echo '</ol>';
                echo '<div class="carousel-inner">';
                for ($i = 1; $i <= 6; $i++) {
                    $active = ($i == 1) ? 'active' : '';
                    echo '<div class="carousel-item ' . $active . '">';
                    echo '<a href="' . $row["photo_path" . $i] . '" data-lightbox="image' . $employee_id . '" data-title="عکس ' . $i . '">';
                    echo '<img class="d-block w-100" src="' . $row["photo_path" . $i] . '" alt="عکس ' . $i . '">';
                    echo '</a>';
                    echo '</div>';
                }
                echo '</div>'; // End of carousel-inner
                echo '<a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">';
                echo '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
                echo '<span class="sr-only">قبلی</span>';
                echo '</a>';
                echo '<a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">';
                echo '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
                echo '<span class="sr-only">بعدی</span>';
                echo '</a>';
                echo '</div>'; // End of carousel

                echo '<div class="table-responsive">';
                echo '<table class="table table-bordered">';
                echo '<tbody>';
                echo '<tr>';
                echo '<th>شناسه سیستمی</th>';
                echo '<td>' . $row["id"] . '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<th>نام و نام خانوادگی</th>';
                echo '<td>' . $row["first_name"] . ' ' . $row["last_name"] . '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<th>کد ملی</th>';
                echo '<td>' . $row["national_code"] . '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<th>تاریخ تولد</th>';
                echo '<td>' . $row["birth_date"] . '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<th>ایمیل</th>';
                echo '<td>' . $row["email"] . '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<th>نوع خدمات</th>';
                echo '<td>' . $row["service_type"] . '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<th>شماره موبایل</th>';
                echo '<td>' . $row["mobile"] . '</td>';
                echo '</tr>';
          
                echo '<th>توضیحات</th>';
                echo '<td class="multiline-text">' . nl2br(htmlspecialchars($row["note"])) . '</td>';
                echo '</tr>';
                echo '</tbody>';
                echo '</table>';
                echo '</div>';

                echo '<div class="row">';
                echo '<div class="col-md-6">';
                echo '<a href="edit_employee.php?id=' . $employee_id . '" class="btn btn-warning btn-custom">ویرایش اطلاعات</a>';
                echo '</div>';
                echo '<div class="col-md-6">';
                echo '<form action="delete_employee.php" method="post" onsubmit="return confirm(\'آیا مطمئن هستید که می‌خواهید این کاربر را حذف کنید؟\');">';
                echo '<input type="hidden" name="id" value="' . $employee_id . '">';
                echo '<button type="submit" class="btn btn-danger btn-custom">حذف کاربر</button>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
            } else {
                echo "<p class='mt-4 mb-4'>هیچ اطلاعاتی یافت نشد.</p>";
            }
        } else {
            echo "<p class='mt-4 mb-4'>خطا در دریافت اطلاعات.</p>";
        }
        $conn->close();
        ?>

        <div class="form-group">
            <button type="button" class="btn btn-primary btn-custom" data-toggle="modal" data-target="#smsModal">
                ارسال پیامک
            </button>
        </div>

     <!-- Modal -->
<div class="modal fade" id="smsModal" tabindex="-1" role="dialog" aria-labelledby="smsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="smsModalLabel">ارسال پیامک به کاربر</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="smsForm">
                    <div class="form-group">
                        <label for="smsContent">متن پیامک:</label>
                        <textarea class="form-control" id="smsContent" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">ارسال</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // ارسال فرم پیامک
    document.getElementById('smsForm').addEventListener('submit', function(event) {
        event.preventDefault(); // جلوگیری از ارسال پیش‌فرض فرم

        var smsContent = document.getElementById('smsContent').value;
        var employeeId = '<?php echo $employee_id; ?>'; // شناسه کاربر

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'send_sms.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('پیامک با موفقیت ارسال شد!');
                $('#smsModal').modal('hide'); // بستن مدل
            } else {
                alert('خطا در ارسال پیامک.');
            }
        };
        xhr.send('id=' + employeeId + '&message=' + encodeURIComponent(smsContent));
    });
</script>


    <!-- Bootstrap JS and jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
</body>
</html>
